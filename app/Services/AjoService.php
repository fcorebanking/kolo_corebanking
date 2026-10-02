<?php

namespace App\Services;

use App\Models\Ajo;
use App\Models\AjoCollectionCycle;
use App\Models\AjoAccount;
use App\Models\AjoInvitation;
use App\Models\AjoMember;
use App\Models\AjoTransaction;
use App\Models\CompanyFinancialEntry;
use App\Models\transactions;
use App\Models\User;
use App\Models\wb_account_details;
use App\Models\user_wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Central Ajo business/service layer.
 *
 * This service is intentionally application-agnostic so Core Finova can
 * expose the same Ajo functionality to other applications such as KoloPay.
 *
 * IMPORTANT ACCOUNTING RULES
 * --------------------------
 * 1. There is NO personal Ajo wallet. All scheduled member collections debit
 *    the member's main Finova wallet.
 * 2. AjoMember::total_contributed is the historical gross amount actually
 *    collected from the member's main wallet for this Ajo.
 * 3. AjoMember::balance is the member's current unpaid/returnable contribution
 *    position. It is zeroed only when early leave or maturity returns the money.
 * 4. The Ajo creator may also join the Ajo and becomes an ordinary AjoMember.
 *    Creator privileges (invite/approve) remain based on creator_user_id.
 * 5. A scheduled collection never makes a main wallet negative. If the wallet
 *    cannot cover all outstanding missed cycles plus the current cycle, nothing
 *    is debited and the current cycle remains an outstanding missed cycle.
 * 6. Every 3rd insufficient-balance event (3rd, 6th, 9th, ...) creates the
 *    configured insufficient-balance fee obligation. The fee is tracked separately
 *    from contribution and is only debited when the member's main wallet can
 *    cover the fee without going negative.
 * 7. Insufficient-balance, maturity, and early-leave fees are split 40/60:
 *      Company = 40%
 *      Creator = 60%
 * 8. Creation fee belongs 100% to the company and is paid from the creator's main wallet.
 * 9. Company fee income is recognized with CompanyFinancialEntry. We do not
 *    directly mutate company_wallet.balance, matching TargetSavingsService.
 * 10. Maturity and early-leave net returns go to the member's main wallet.
 */
class AjoService
{
    /*
     * Core Finova transaction type IDs.
     *
     * These should exist in transaction_types / the application's ledger setup.
     */
    // private const TRANSACTION_TYPE_AJO_ACCOUNT_DEPOSIT = 131;
    // private const TRANSACTION_TYPE_AJO_ACCOUNT_WITHDRAWAL = 132;
    private const TRANSACTION_TYPE_AJO_SCHEDULED_COLLECTION = 133;
    private const TRANSACTION_TYPE_AJO_MATURITY_FEE = 134;
    private const TRANSACTION_TYPE_AJO_MATURITY_RETURN = 135;
    private const TRANSACTION_TYPE_AJO_EARLY_LEAVE_PENALTY = 136;
    private const TRANSACTION_TYPE_AJO_EARLY_LEAVE_RETURN = 137;
    private const TRANSACTION_TYPE_AJO_INSUFFICIENT_BALANCE_FEE = 138;
    private const TRANSACTION_TYPE_AJO_REVERSAL = 139;
    private const TRANSACTION_TYPE_AJO_CREATION_FEE = 140;

    /*
     * Existing Finova credit/debit type IDs.
     */
    private const CREDIT = 1;
    private const DEBIT = 2;

    /*
     * Existing financial entry types.
     */
    private const FINANCIAL_REVENUE = 'revenue';

    /*
     * Financial categories.
     */
    private const CATEGORY_CREATION_FEE = 'ajo_creation_fee';
    private const CATEGORY_MATURITY_FEE = 'ajo_maturity_fee';
    private const CATEGORY_EARLY_LEAVE_PENALTY = 'ajo_early_leave_penalty';
    private const CATEGORY_INSUFFICIENT_BALANCE_FEE = 'ajo_insufficient_balance_fee';

    /*
     * Ajo status values.
     */
    private const STATUS_DRAFT = 'draft';
    private const STATUS_ACTIVE = 'active';
    private const STATUS_MATURED = 'matured';
    private const STATUS_CANCELLED = 'cancelled';

    /*
     * Fee split defined by the product rules.
     */
    private const COMPANY_FEE_SHARE_PERCENTAGE = 40.00;
    private const CREATOR_FEE_SHARE_PERCENTAGE = 60.00;

    /*
     * Insufficient-balance threshold.
     *
     * A fee is applied on the 3rd, 6th, 9th... insufficient event.
     */
    private const INSUFFICIENT_BALANCE_FEE_EVERY = 3;

    /*
     * Existing TargetSavingsService writes successful core transactions with
     * created_by = 1. Keep that convention for Core Finova transactions.
     */
    private const CORE_CREATED_BY = 1;

    /**
     * Create a new Ajo.
     *
     * The creator is an overseer only and is not inserted into ajo_members.
     *
     * Required data:
     * - ajo_name
     * - collection_amount
     * - collection_frequency: daily|weekly|monthly
     * - collection_time: HH:MM
     * - start_date: YYYY-MM-DD
     * - maturity_date: YYYY-MM-DD
     *
     * Optional data:
     * - collection_interval
     * - collection_day
     * - description
     * - early_leave_penalty_type: fixed|percentage
     * - early_leave_penalty_value
     * - insufficient_balance_fee_type: fixed|percentage
     * - insufficient_balance_fee_value
     * - max_members
     * - visibility: public|private
     * - terms
     */
    public function createAjo($creator, array $data): Ajo
    {
        return DB::transaction(function () use ($creator, $data) {
            $this->validateUserForAjo($creator);

            $ajoAccount = $this->ensureAjoAccount($creator, true);

            $company = $creator->company;
            $companySettings = $company?->setting;

            if (!$companySettings) {
                throw new RuntimeException('Company Ajo settings could not be found.');
            }

            $name = trim((string) ($data['ajo_name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Ajo name is required.');
            }

            $collectionAmount = round((float) ($data['collection_amount'] ?? 0), 2);
            if ($collectionAmount <= 0) {
                throw new RuntimeException('Ajo collection amount must be greater than zero.');
            }

            $frequency = strtolower(trim((string) ($data['collection_frequency'] ?? '')));
            if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
                throw new RuntimeException('Collection frequency must be daily, weekly or monthly.');
            }

            $interval = (int) ($data['collection_interval'] ?? 1);
            if ($interval < 1) {
                throw new RuntimeException('Collection interval must be at least 1.');
            }

            $collectionTime = $this->normaliseCollectionTime($data['collection_time'] ?? null);
            $startDate = $this->parseDate($data['start_date'] ?? null, 'Ajo start date')->startOfDay();
            $maturityDate = $this->parseDate($data['maturity_date'] ?? null, 'Ajo maturity date')->startOfDay();

            if ($maturityDate->lte($startDate)) {
                throw new RuntimeException('Ajo maturity date must be after the start date.');
            }

            $collectionDay = $this->normaliseCollectionDay(
                $frequency,
                $data['collection_day'] ?? null,
                $startDate
            );

            $creationFee = round((float) ($companySettings->ajo_creation_fee ?? 0), 2);
            if ($creationFee < 0) {
                throw new RuntimeException('Invalid Ajo creation fee configured for this company.');
            }

            $maturityFeePercentage = round((float) ($companySettings->ajo_maturity_fee_percentage ?? 0), 4);
            if ($maturityFeePercentage < 0 || $maturityFeePercentage > 100) {
                throw new RuntimeException('Invalid Ajo maturity fee percentage configured for this company.');
            }

            $earlyLeavePenaltyType = strtolower(trim((string) ($data['early_leave_penalty_type'] ?? 'percentage')));
            $earlyLeavePenaltyValue = round((float) ($data['early_leave_penalty_value'] ?? 0), 4);
            $this->validateFeeConfiguration($earlyLeavePenaltyType, $earlyLeavePenaltyValue, 'early leave penalty');

            $insufficientFeeType = strtolower(trim((string) ($data['insufficient_balance_fee_type'] ?? 'fixed')));
            $insufficientFeeValue = round((float) ($data['insufficient_balance_fee_value'] ?? 0), 4);
            $this->validateFeeConfiguration($insufficientFeeType, $insufficientFeeValue, 'insufficient-balance fee');

            $maxMembers = array_key_exists('max_members', $data) && $data['max_members'] !== null
                ? (int) $data['max_members']
                : null;
            if ($maxMembers !== null && $maxMembers < 1) {
                throw new RuntimeException('Maximum members must be at least 1.');
            }

            $visibility = strtolower(trim((string) ($data['visibility'] ?? 'public')));
            if (!in_array($visibility, ['public', 'private'], true)) {
                throw new RuntimeException('Ajo visibility must be public or private.');
            }

            $nextCollectionAt = $this->calculateInitialCollectionAt(
                $startDate,
                $frequency,
                $interval,
                $collectionDay,
                $collectionTime
            );

            $terms = $this->buildTerms(
                name: $name,
                description: $data['terms'] ?? null,
                collectionAmount: $collectionAmount,
                collectionFrequency: $frequency,
                collectionInterval: $interval,
                collectionDay: $collectionDay,
                collectionTime: $collectionTime,
                startDate: $startDate,
                maturityDate: $maturityDate,
                creationFee: $creationFee,
                maturityFeePercentage: $maturityFeePercentage,
                earlyLeavePenaltyType: $earlyLeavePenaltyType,
                earlyLeavePenaltyValue: $earlyLeavePenaltyValue,
                insufficientFeeType: $insufficientFeeType,
                insufficientFeeValue: $insufficientFeeValue,
                visibility: $visibility,
                maxMembers: $maxMembers
            );

            $ajo = Ajo::create([
                'creator_user_id' => $creator->id,
                'creator_ajo_account_id' => $ajoAccount->id,
                'company_id' => $creator->company_id,
                'ajo_name' => $name,
                'description' => !empty($data['description']) ? trim((string) $data['description']) : null,
                'collection_amount' => $collectionAmount,
                'collection_frequency' => $frequency,
                'collection_interval' => $interval,
                'collection_day' => $collectionDay,
                'collection_time' => $collectionTime,
                'start_date' => $startDate->toDateString(),
                'maturity_date' => $maturityDate->toDateString(),
                'next_collection_at' => $nextCollectionAt,
                'creation_fee' => $creationFee,
                'maturity_fee_percentage' => $maturityFeePercentage,
                'early_leave_penalty_type' => $earlyLeavePenaltyType,
                'early_leave_penalty_value' => $earlyLeavePenaltyValue,
                'insufficient_balance_fee_type' => $insufficientFeeType,
                'insufficient_balance_fee_value' => $insufficientFeeValue,
                'company_fee_share_percentage' => self::COMPANY_FEE_SHARE_PERCENTAGE,
                'creator_fee_share_percentage' => self::CREATOR_FEE_SHARE_PERCENTAGE,
                'max_members' => $maxMembers,
                'visibility' => $visibility,
                'terms' => $terms,
                'terms_version' => '1.0',
                'status' => self::STATUS_DRAFT,
            ]);

            $creationFeeTransaction = null;
            $creationFinancialEntry = null;
            $creationCoreTransaction = null;

            if ($creationFee > 0) {
                $mainAccount = $this->getMainAccount($creator);
                $debit = debitCustomerOnly($mainAccount->id, $creationFee, $creator);

                if (!($debit['status'] ?? false)) {
                    throw new RuntimeException($debit['message'] ?? 'Unable to charge Ajo creation fee.');
                }

                $reference = $this->reference('AJC');
                $creationCoreTransaction = $this->createCoreTransaction(
                    companyId: (int) $creator->company_id,
                    transactionTypeId: self::TRANSACTION_TYPE_AJO_CREATION_FEE,
                    creditDebitTypeId: self::DEBIT,
                    accountId: (int) $mainAccount->id,
                    amount: $creationFee,
                    balanceBefore: (float) $debit['balance_before'],
                    balanceAfter: (float) $debit['balance_after'],
                    description: 'Ajo Creation Fee',
                    reference: $reference
                );

                $creationFeeTransaction = AjoTransaction::create([
                    'ajo_id' => $ajo->id,
                    'ajo_member_id' => null,
                    'ajo_collection_cycle_id' => null,
                    'user_id' => $creator->id,
                    'company_id' => $creator->company_id,
                    'source_ajo_account_id' => null,
                    'destination_ajo_account_id' => null,
                    'main_account_id' => $mainAccount->id,
                    'finova_transaction_id' => $creationCoreTransaction->id,
                    'company_financial_entry_id' => null,
                    'type' => AjoTransaction::TYPE_CREATION_FEE,
                    'amount' => $creationFee,
                    'fee' => $creationFee,
                    'company_fee_share' => $creationFee,
                    'creator_fee_share' => 0,
                    'source_balance_before' => (float) $debit['balance_before'],
                    'source_balance_after' => (float) $debit['balance_after'],
                    'destination_balance_before' => null,
                    'destination_balance_after' => null,
                    'transaction_reference' => $this->reference('AJO-FEE'),
                    'status' => 'successful',
                    'description' => 'Ajo creation fee charged to creator main wallet.',
                    'metadata' => [
                        'fee_type' => 'creation_fee',
                        'total_fee' => $creationFee,
                        'company_share' => $creationFee,
                        'creator_share' => 0,
                        'company_share_percentage' => 100,
                        'creator_share_percentage' => 0,
                        'company_wallet_affected' => false,
                    ],
                ]);

                $creationFinancialEntry = $this->recordCompanyRevenue(
                    ajo: $ajo,
                    amount: $creationFee,
                    category: self::CATEGORY_CREATION_FEE,
                    description: 'Ajo creation fee revenue.',
                    reference: $this->reference('AJCR'),
                    transactionId: $creationCoreTransaction->id,
                    metadata: [
                        'ajo_id' => $ajo->id,
                        'fee_type' => 'creation_fee',
                        'total_fee' => $creationFee,
                        'company_share' => $creationFee,
                        'creator_share' => 0,
                        'ajo_transaction_id' => $creationFeeTransaction->id,
                        'company_wallet_affected' => false,
                    ]
                );

                $creationFeeTransaction->company_financial_entry_id = $creationFinancialEntry->id;
                $creationFeeTransaction->save();
            }

            $ajo->status = self::STATUS_ACTIVE;
            $ajo->save();

            return $ajo->fresh();
        });
    }

    /**
 * Ensure a user's personal Ajo Account exists.
 */
protected function ensureAjoAccount($user, bool $lock = true): AjoAccount
{
    $query = AjoAccount::query()
        ->where('user_id', $user->id);

    if ($lock) {
        $query->lockForUpdate();
    }

    $account = $query->first();

    if ($account) {
        if ((int) $account->company_id !== (int) $user->company_id) {
            throw new RuntimeException(
                'Your personal Ajo Account is linked to a different company.'
            );
        }

        return $account;
    }

    return AjoAccount::create([
        'user_id' => $user->id,
        'company_id' => $user->company_id,
        'balance' => 0,
        'status' => 'active',
    ]);
}

    /**
     * Return a user's permanent personal Ajo Account.
     *
     * This also safely creates it on demand for older users who registered
     * before the registration flow was updated. New registration should still
     * create it automatically as part of registration as planned.
     */


    /**
     * Voluntarily move money from the user's MAIN wallet to their personal
     * Ajo Account.
     *
     * This is NOT tied to any specific Ajo group.
     */


    /**
     * Voluntarily move money from the user's personal Ajo Account back to
     * their MAIN wallet.
     *
     * Funds already moved into an active AjoMember position are not included
     * in this balance and therefore cannot be withdrawn here.
     */


    /**
     * Invite a user by username.
     *
     * Only the Ajo creator can send invitations.
     */
    public function inviteMember(int $ajoId, $creator, string $username): AjoInvitation
    {
        return DB::transaction(function () use ($ajoId, $creator, $username) {
            $ajo = $this->getAjoForUpdate($ajoId);
            $this->assertCreator($ajo, $creator);

            if ($ajo->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException('This Ajo is not accepting members.');
            }

            $username = trim($username);
            if ($username === '') {
                throw new RuntimeException('Member username is required.');
            }

            $invitee = User::query()->where('username', $username)->first();
            if (!$invitee) {
                throw new RuntimeException('User with the supplied username could not be found.');
            }

            if ($ajo->max_members !== null && $ajo->members()
                ->whereIn('status', [AjoMember::STATUS_ACTIVE, AjoMember::STATUS_LEAVE_REQUESTED])
                ->count() >= (int) $ajo->max_members) {
                throw new RuntimeException('This Ajo has reached its maximum member capacity.');
            }

            $alreadyMember = AjoMember::query()
                ->where('ajo_id', $ajo->id)
                ->where('user_id', $invitee->id)
                ->whereIn('status', [AjoMember::STATUS_ACTIVE, AjoMember::STATUS_LEAVE_REQUESTED])
                ->exists();

            if ($alreadyMember) {
                throw new RuntimeException('This user is already a member of the Ajo.');
            }

            $pending = AjoInvitation::query()
                ->where('ajo_id', $ajo->id)
                ->where('invited_user_id', $invitee->id)
                ->where('status', AjoInvitation::STATUS_PENDING)
                ->first();

            if ($pending) return $pending;

            return AjoInvitation::create([
                'ajo_id' => $ajo->id,
                'invited_by' => $creator->id,
                'invited_user_id' => $invitee->id,
                'status' => AjoInvitation::STATUS_PENDING,
                'terms_snapshot' => $ajo->terms,
                'terms_version' => $ajo->terms_version,
                'sent_at' => now(),
                'responded_at' => null,
            ]);
        });
    }

    /**
     * Join a public Ajo directly.
     *
     * No money is taken during joining.
     * The user must explicitly confirm the terms.
     */
    public function joinAjo(int $ajoId, $user, bool $acceptTerms = false): AjoMember
    {
        return DB::transaction(function () use ($ajoId, $user, $acceptTerms) {
            $this->validateUserForAjo($user);
            if (!$acceptTerms) {
                throw new RuntimeException('You must accept the Ajo terms and conditions before joining.');
            }

            $ajo = $this->getAjoForUpdate($ajoId);
            if ($ajo->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException('This Ajo is not accepting members.');
            }
            if (
                $ajo->visibility !== 'public'
                && (int) $ajo->creator_user_id !== (int) $user->id
            ) {
                throw new RuntimeException('This is a private Ajo. You must accept an invitation to join.');
            }

            return $this->createMembership($ajo, $user, $ajo->terms, $ajo->terms_version);
        });
    }

    /**
     * Accept a creator invitation.
     */
    public function acceptInvitation(int $invitationId, $user, bool $acceptTerms = false): AjoMember
    {
        return DB::transaction(function () use ($invitationId, $user, $acceptTerms) {
            $this->validateUserForAjo($user);
            if (!$acceptTerms) {
                throw new RuntimeException('You must explicitly accept the Ajo terms and conditions.');
            }

            $invitation = AjoInvitation::query()->where('id', $invitationId)->lockForUpdate()->first();
            if (!$invitation) throw new RuntimeException('Ajo invitation not found.');
            if ((int) $invitation->invited_user_id !== (int) $user->id) {
                throw new RuntimeException('This invitation does not belong to you.');
            }
            if ($invitation->status !== AjoInvitation::STATUS_PENDING) {
                throw new RuntimeException('This invitation has already been processed.');
            }

            $ajo = $this->getAjoForUpdate($invitation->ajo_id);
            if ($ajo->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException('This Ajo is no longer accepting members.');
            }

            $membership = $this->createMembership(
                $ajo,
                $user,
                $invitation->terms_snapshot,
                $invitation->terms_version
            );

            $invitation->status = AjoInvitation::STATUS_ACCEPTED;
            $invitation->responded_at = now();
            $invitation->save();

            return $membership;
        });
    }

    /**
     * Decline an invitation.
     */
    public function declineInvitation(
        int $invitationId,
        $user
    ): AjoInvitation {
        return DB::transaction(function () use ($invitationId, $user) {
            $invitation = AjoInvitation::query()
                ->where('id', $invitationId)
                ->lockForUpdate()
                ->first();

            if (!$invitation) {
                throw new RuntimeException('Ajo invitation not found.');
            }

            if ((int) $invitation->invited_user_id !== (int) $user->id) {
                throw new RuntimeException(
                    'This invitation does not belong to you.'
                );
            }

            if ($invitation->status !== AjoInvitation::STATUS_PENDING) {
                throw new RuntimeException(
                    'This invitation has already been processed.'
                );
            }

            $invitation->status = AjoInvitation::STATUS_DECLINED;
            $invitation->responded_at = now();
            $invitation->save();

            return $invitation->fresh();
        });
    }

    /**
     * Member requests to leave an active Ajo early.
     *
     * The creator must approve this request before the financial transfer occurs.
     */
    public function requestEarlyLeave(
        int $ajoId,
        $user,
        ?string $reason = null
    ): AjoMember {
        return DB::transaction(function () use (
            $ajoId,
            $user,
            $reason
        ) {
            $this->validateUserForAjo($user);

            $member = AjoMember::query()
                ->where('ajo_id', $ajoId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$member) {
                throw new RuntimeException(
                    'You are not a member of this Ajo.'
                );
            }

            if ($member->status !== AjoMember::STATUS_ACTIVE) {
                throw new RuntimeException(
                    'This membership is not available for an early-leave request.'
                );
            }

            $member->status = AjoMember::STATUS_LEAVE_REQUESTED;
            $member->early_leave_requested_at = now();
            $member->early_leave_processed_at = null;
            $member->early_leave_processed_by = null;
            $member->early_leave_reason = $reason
                ? trim($reason)
                : null;
            $member->save();

            return $member->fresh();
        });
    }

    /**
     * Creator approves a member's early leave.
     *
     * Financial flow:
     *   Member Ajo position
     *          |
     *          +--> total penalty
     *                  |
     *                  +--> 40% Company revenue
     *                  +--> 60% Creator personal Ajo Account
     *
     * Remaining member balance goes to the member's personal Ajo Account.
     */
    public function approveEarlyLeave(int $ajoMemberId, $creator): array
    {
        return DB::transaction(function () use ($ajoMemberId, $creator) {
            $member = AjoMember::query()->where('id', $ajoMemberId)->lockForUpdate()->first();
            if (!$member) throw new RuntimeException('Ajo member not found.');

            $ajo = $this->getAjoForUpdate($member->ajo_id);
            $this->assertCreator($ajo, $creator);

            if ($member->status !== AjoMember::STATUS_LEAVE_REQUESTED) {
                throw new RuntimeException('This member does not have a pending early-leave request.');
            }
            if ($ajo->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException('This Ajo is not active.');
            }

            $gross = round((float) $member->balance, 2);
            $penalty = min(
                $this->calculateConfiguredFee(
                    $ajo->early_leave_penalty_type,
                    (float) $ajo->early_leave_penalty_value,
                    $gross
                ),
                $gross
            );
            $remaining = round($gross - $penalty, 2);

            $split = $ajo->splitFee($penalty);
            $penaltyTransaction = null;
            $penaltyFinancialEntry = null;
            $creatorCredit = null;

            if ($penalty > 0) {
                $creatorAccount = $this->getMainAccount($creator);
                $creatorBefore = $this->getAccountBalance($creatorAccount);
                $creatorCredit = creditCustomerOnly($creatorAccount->id, $split['creator_share']);
                if (!($creatorCredit['status'] ?? false)) {
                    throw new RuntimeException($creatorCredit['message'] ?? 'Unable to credit creator fee share.');
                }

                $creatorFeeCoreTransaction = $this->createCoreTransaction(
                    companyId: (int) $creator->company_id,
                    transactionTypeId: self::TRANSACTION_TYPE_AJO_EARLY_LEAVE_PENALTY,
                    creditDebitTypeId: self::CREDIT,
                    accountId: (int) $creatorAccount->id,
                    amount: $split['creator_share'],
                    balanceBefore: $creatorBefore,
                    balanceAfter: (float) $creatorCredit['balance_after'],
                    description: 'Ajo Early Leave Creator Fee Share',
                    reference: $this->reference('AJO-ELP-CR')
                );

                $penaltyReference = $this->reference('AJO-ELP');
                $penaltyTransaction = AjoTransaction::create([
                    'ajo_id' => $ajo->id,
                    'ajo_member_id' => $member->id,
                    'ajo_collection_cycle_id' => null,
                    'user_id' => $member->user_id,
                    'company_id' => $ajo->company_id,
                    'source_ajo_account_id' => null,
                    'destination_ajo_account_id' => null,
                    'main_account_id' => $this->getMainAccount($member->user)->id,
                    'finova_transaction_id' => $creatorFeeCoreTransaction->id,
                    'company_financial_entry_id' => null,
                    'type' => AjoTransaction::TYPE_EARLY_LEAVE_PENALTY,
                    'amount' => $penalty,
                    'fee' => $penalty,
                    'company_fee_share' => $split['company_share'],
                    'creator_fee_share' => $split['creator_share'],
                    'source_balance_before' => $gross,
                    'source_balance_after' => $remaining,
                    'destination_balance_before' => $creatorBefore,
                    'destination_balance_after' => (float) $creatorCredit['balance_after'],
                    'transaction_reference' => $penaltyReference,
                    'status' => 'successful',
                    'description' => 'Ajo early-leave penalty charged to member contribution.',
                    'metadata' => [
                        'gross_contribution' => $gross,
                        'penalty' => $penalty,
                        'company_share' => $split['company_share'],
                        'creator_share' => $split['creator_share'],
                        'creator_share_destination' => 'main_wallet',
                        'company_wallet_affected' => false,
                    ],
                ]);

                $penaltyFinancialEntry = $this->recordCompanyRevenue(
                    ajo: $ajo,
                    amount: $split['company_share'],
                    category: self::CATEGORY_EARLY_LEAVE_PENALTY,
                    description: 'Ajo early-leave penalty company revenue share.',
                    reference: $this->reference('AJO-ELR'),
                    transactionId: null,
                    metadata: [
                        'ajo_id' => $ajo->id,
                        'ajo_member_id' => $member->id,
                        'total_fee' => $penalty,
                        'company_share' => $split['company_share'],
                        'creator_share' => $split['creator_share'],
                        'ajo_transaction_id' => $penaltyTransaction->id,
                        'company_wallet_affected' => false,
                    ]
                );
                $penaltyTransaction->company_financial_entry_id = $penaltyFinancialEntry->id;
                $penaltyTransaction->save();
            }

            $memberAccount = $this->getMainAccount($member->user);
            $memberBefore = $this->getAccountBalance($memberAccount);
            $returnCredit = null;

            if ($remaining > 0) {
                $returnCredit = creditCustomerOnly($memberAccount->id, $remaining);
                if (!($returnCredit['status'] ?? false)) {
                    throw new RuntimeException($returnCredit['message'] ?? 'Unable to return Ajo funds to member main wallet.');
                }
            } else {
                $returnCredit = [
                    'status' => true,
                    'balance_before' => $memberBefore,
                    'balance_after' => $memberBefore,
                ];
            }

            $returnReference = $this->reference('AJO-ELRETURN');
            $returnCoreTransaction = null;
            if ($remaining > 0) {
                $returnCoreTransaction = $this->createCoreTransaction(
                    companyId: (int) $member->user->company_id,
                    transactionTypeId: self::TRANSACTION_TYPE_AJO_EARLY_LEAVE_RETURN,
                    creditDebitTypeId: self::CREDIT,
                    accountId: (int) $memberAccount->id,
                    amount: $remaining,
                    balanceBefore: $memberBefore,
                    balanceAfter: (float) $returnCredit['balance_after'],
                    description: 'Ajo Early Leave Return',
                    reference: $returnReference
                );
            }
            $returnTransaction = AjoTransaction::create([
                'ajo_id' => $ajo->id,
                'ajo_member_id' => $member->id,
                'ajo_collection_cycle_id' => null,
                'user_id' => $member->user_id,
                'company_id' => $ajo->company_id,
                'source_ajo_account_id' => null,
                'destination_ajo_account_id' => null,
                'main_account_id' => $memberAccount->id,
                'finova_transaction_id' => $returnCoreTransaction?->id,
                'company_financial_entry_id' => null,
                'type' => AjoTransaction::TYPE_EARLY_LEAVE_RETURN,
                'amount' => $remaining,
                'fee' => 0,
                'company_fee_share' => 0,
                'creator_fee_share' => 0,
                'source_balance_before' => $remaining,
                'source_balance_after' => 0,
                'destination_balance_before' => $memberBefore,
                'destination_balance_after' => (float) $returnCredit['balance_after'],
                'transaction_reference' => $returnReference,
                'status' => 'successful',
                'description' => 'Remaining Ajo contribution returned to member main wallet.',
                'metadata' => [
                    'gross_contribution' => $gross,
                    'penalty' => $penalty,
                    'net_return' => $remaining,
                    'destination' => 'main_wallet',
                    'historical_total_contributed_preserved' => true,
                ],
            ]);

            $member->balance = 0;
            $member->total_early_leave_penalties = round((float) $member->total_early_leave_penalties + $penalty, 2);
            $member->status = AjoMember::STATUS_LEFT;
            $member->left_at = now();
            $member->early_leave_processed_at = now();
            $member->early_leave_processed_by = $creator->id;
            $member->save();

            return [
                'ajo' => $ajo->fresh(),
                'member' => $member->fresh(),
                'gross_contributed' => $gross,
                'penalty' => $penalty,
                'remaining' => $remaining,
                'fee_split' => $split,
                'penalty_transaction' => $penaltyTransaction,
                'return_transaction' => $returnTransaction,
                'financial_entry' => $penaltyFinancialEntry,
                'creator_wallet' => $creatorCredit,
                'member_wallet' => $returnCredit,
            ];
        });
    }

    /**
     * Creator rejects an early-leave request.
     */
    public function rejectEarlyLeave(
        int $ajoMemberId,
        $creator,
        ?string $reason = null
    ): AjoMember {
        return DB::transaction(function () use (
            $ajoMemberId,
            $creator,
            $reason
        ) {
            $member = AjoMember::query()
                ->where('id', $ajoMemberId)
                ->lockForUpdate()
                ->first();

            if (!$member) {
                throw new RuntimeException('Ajo member not found.');
            }

            $ajo = $this->getAjoForUpdate($member->ajo_id);
            $this->assertCreator($ajo, $creator);

            if ($member->status !== AjoMember::STATUS_LEAVE_REQUESTED) {
                throw new RuntimeException(
                    'This member does not have a pending early-leave request.'
                );
            }

            $member->status = AjoMember::STATUS_ACTIVE;
            $member->early_leave_processed_at = now();
            $member->early_leave_processed_by = $creator->id;
            $member->early_leave_reason = $reason
                ? trim($reason)
                : $member->early_leave_reason;
            $member->save();

            return $member->fresh();
        });
    }

    /**
     * Process all scheduled Ajo collections that are due.
     *
     * The caller can run this from a Laravel scheduler/command in Core Finova.
     * Other applications such as KoloPay can trigger the same service through
     * their integration layer without duplicating the collection logic.
     */
    public function processScheduledCollections(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?: now())->copy();
        $processed = 0; $failed = 0; $skipped = 0;

        Ajo::query()
            ->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('next_collection_at')
            ->where('next_collection_at', '<=', $asOf)
            ->whereDate('maturity_date', '>', $asOf->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($ajos) use ($asOf, &$processed, &$failed, &$skipped) {
                foreach ($ajos as $ajoSnapshot) {
                    $guard = 0;
                    while ($guard++ < 500) {
                        $ajo = Ajo::query()->where('id', $ajoSnapshot->id)->lockForUpdate()->first();
                        if (!$ajo || $ajo->status !== self::STATUS_ACTIVE) break;

                        $scheduledFor = $ajo->next_collection_at ? Carbon::parse($ajo->next_collection_at) : null;
                        if (!$scheduledFor || $scheduledFor->gt($asOf)) break;
                        if ($scheduledFor->startOfDay()->gte(Carbon::parse($ajo->maturity_date)->startOfDay())) break;

                        $members = AjoMember::query()
                            ->where('ajo_id', $ajo->id)
                            ->whereIn('status', [AjoMember::STATUS_ACTIVE, AjoMember::STATUS_LEAVE_REQUESTED])
                            ->where('joined_at', '<=', $scheduledFor)
                            ->orderBy('id')->get();

                        foreach ($members as $memberSnapshot) {
                            try {
                                $result = $this->processSingleCollection((int)$ajo->id, (int)$memberSnapshot->id, $scheduledFor);
                                if (($result['status'] ?? null) === 'successful') $processed++; else $skipped++;
                            } catch (Throwable $e) {
                                $failed++;
                                $this->recordFailedCollection((int)$ajo->id, (int)$memberSnapshot->id, $scheduledFor, $e);
                            }
                        }

                        DB::transaction(function () use ($ajo, $scheduledFor) {
                            $lockedAjo = Ajo::query()->where('id', $ajo->id)->lockForUpdate()->first();
                            if (!$lockedAjo || $lockedAjo->status !== self::STATUS_ACTIVE) return;
                            $currentNext = $lockedAjo->next_collection_at ? Carbon::parse($lockedAjo->next_collection_at) : null;
                            if (!$currentNext || !$currentNext->equalTo($scheduledFor)) return;
                            $lockedAjo->next_collection_at = $this->calculateNextCollectionAt($lockedAjo, $scheduledFor);
                            $lockedAjo->save();
                        });
                    }
                }
            });

        return ['processed' => $processed, 'failed' => $failed, 'skipped' => $skipped];
    }

    /**
     * Process one member's collection for one specific schedule timestamp.
     *
     * This is intentionally idempotent. A scheduler can safely run multiple
     * times without collecting the same member twice for the same timestamp.
     */
    protected function processSingleCollection(int $ajoId, int $memberId, Carbon $scheduledFor): array
    {
        return DB::transaction(function () use ($ajoId, $memberId, $scheduledFor) {
            $ajo = Ajo::query()->where('id', $ajoId)->lockForUpdate()->first();
            if (!$ajo || $ajo->status !== self::STATUS_ACTIVE) return ['status' => 'skipped'];

            $member = AjoMember::query()->where('id', $memberId)->where('ajo_id', $ajoId)->lockForUpdate()->first();
            if (!$member) return ['status' => 'skipped'];
            if (!in_array($member->status, [AjoMember::STATUS_ACTIVE, AjoMember::STATUS_LEAVE_REQUESTED], true)) return ['status' => 'skipped'];
            if (!$member->joined_at || Carbon::parse($member->joined_at)->gt($scheduledFor)) return ['status' => 'skipped'];

            $existingCycle = AjoCollectionCycle::query()
                ->where('ajo_id', $ajo->id)->where('ajo_member_id', $member->id)->where('scheduled_for', $scheduledFor)
                ->lockForUpdate()->first();
            if ($existingCycle) return ['status' => $existingCycle->status === AjoCollectionCycle::STATUS_SUCCESSFUL ? 'successful' : 'skipped', 'collection_cycle' => $existingCycle];

            $collectionAmount = round((float)$ajo->collection_amount, 2);
            if ($collectionAmount <= 0) throw new RuntimeException('Ajo collection amount is invalid.');

            $pendingCycles = AjoCollectionCycle::query()
                ->where('ajo_member_id', $member->id)
                ->where('status', AjoCollectionCycle::STATUS_FAILED)
                ->where('insufficient_balance', true)
                ->get()
                ->filter(function ($cycle) {
                    return !array_key_exists(
                        'settled_by_collection_cycle_id',
                        (array) $cycle->metadata
                    );
                })
                ->values();
            $pendingBefore = round($pendingCycles->sum(fn($c) => (float)$c->amount), 2);
            $required = round($pendingBefore + $collectionAmount, 2);

            $mainAccount = $this->getMainAccount($member->user);
            $mainAccount = wb_account_details::query()->where('id', $mainAccount->id)->lockForUpdate()->first();
            $sourceBalanceBefore = $this->getAccountBalance($mainAccount);

            $cycleNumber = (int)(AjoCollectionCycle::query()->where('ajo_member_id', $member->id)->max('cycle_number') ?? 0) + 1;

            if ($sourceBalanceBefore < $required) {
                $newCount = (int)$member->insufficient_balance_count + 1;
                $reference = $this->reference('AJCOLMISS');
                $cycle = AjoCollectionCycle::create([
                    'ajo_id' => $ajo->id,
                    'ajo_member_id' => $member->id,
                    'cycle_number' => $cycleNumber,
                    'scheduled_for' => $scheduledFor,
                    'processed_at' => now(),
                    'amount' => $collectionAmount,
                    'source_balance_before' => $sourceBalanceBefore,
                    'source_balance_after' => $sourceBalanceBefore,
                    'member_balance_before' => (float)$member->balance,
                    'member_balance_after' => (float)$member->balance,
                    'insufficient_balance' => true,
                    'insufficient_balance_count' => $newCount,
                    'fee_amount' => 0,
                    'company_fee_share' => 0,
                    'creator_fee_share' => 0,
                    'status' => AjoCollectionCycle::STATUS_FAILED,
                    'failure_reason' => 'Insufficient main wallet balance for this collection and all outstanding missed collections.',
                    'transaction_reference' => $reference,
                    'metadata' => [
                        'required_for_catch_up' => $required,
                        'previous_missed_amount' => $pendingBefore,
                        'current_collection_amount' => $collectionAmount,
                        'actual_debited' => 0,
                        'main_wallet_balance' => $sourceBalanceBefore,
                        'fee_separate_from_contribution' => true,
                    ],
                ]);
                $member->insufficient_balance_count = $newCount;

                $newPending = round(
                    (float) $member->pending_collection_amount + $collectionAmount,
                    2
                );
                $member->pending_collection_amount = $newPending;

                if (
                    $newCount > 0 &&
                    $newCount % self::INSUFFICIENT_BALANCE_FEE_EVERY === 0
                ) {
                    $feeDue = $this->calculateConfiguredFee(
                        $ajo->insufficient_balance_fee_type,
                        (float) $ajo->insufficient_balance_fee_value,
                        $collectionAmount
                    );

                    $member->pending_insufficient_balance_fees = round(
                        (float) $member->pending_insufficient_balance_fees + $feeDue,
                        2
                    );

                    $metadata = (array) $cycle->metadata;
                    $metadata['insufficient_fee_due'] = $feeDue;
                    $cycle->metadata = $metadata;
                    $cycle->save();
                }

                $member->save();

                return ['status' => 'skipped', 'reason' => 'insufficient_balance', 'collection_cycle' => $cycle];
            }

            $memberBalanceBefore = round((float)$member->balance, 2);
            $memberBalanceAfter = round($memberBalanceBefore + $required, 2);
            $debit = debitCustomerOnly($mainAccount->id, $required, $member->user);
            if (!($debit['status'] ?? false)) throw new RuntimeException($debit['message'] ?? 'Unable to debit member main wallet.');

            $actualBefore = (float)$debit['balance_before'];
            $actualAfter = (float)$debit['balance_after'];
            $collectionReference = $this->reference('AJCOL');

            $collectionCoreTransaction = $this->createCoreTransaction(
                companyId: (int) $member->user->company_id,
                transactionTypeId: self::TRANSACTION_TYPE_AJO_SCHEDULED_COLLECTION,
                creditDebitTypeId: self::DEBIT,
                accountId: (int) $mainAccount->id,
                amount: $required,
                balanceBefore: $actualBefore,
                balanceAfter: $actualAfter,
                description: 'Ajo Scheduled Collection',
                reference: $collectionReference
            );

            $cycle = AjoCollectionCycle::create([
                'ajo_id' => $ajo->id,
                'ajo_member_id' => $member->id,
                'cycle_number' => $cycleNumber,
                'scheduled_for' => $scheduledFor,
                'processed_at' => now(),
                'amount' => $collectionAmount,
                'source_balance_before' => $actualBefore,
                'source_balance_after' => $actualAfter,
                'member_balance_before' => $memberBalanceBefore,
                'member_balance_after' => $memberBalanceAfter,
                'insufficient_balance' => $pendingBefore > 0,
                'insufficient_balance_count' => (int)$member->insufficient_balance_count,
                'fee_amount' => 0,
                'company_fee_share' => 0,
                'creator_fee_share' => 0,
                'status' => AjoCollectionCycle::STATUS_SUCCESSFUL,
                'failure_reason' => null,
                'transaction_reference' => $collectionReference,
                'metadata' => [
                    'previous_missed_amount' => $pendingBefore,
                    'current_collection_amount' => $collectionAmount,
                    'actual_debited' => $required,
                    'main_wallet_balance_before' => $actualBefore,
                    'main_wallet_balance_after' => $actualAfter,
                    'catch_up' => $pendingBefore > 0,
                    'missed_cycle_ids' => $pendingCycles->pluck('id')->values()->all(),
                ],
            ]);

            $collectionTransaction = AjoTransaction::create([
                'ajo_id' => $ajo->id,
                'ajo_member_id' => $member->id,
                'ajo_collection_cycle_id' => $cycle->id,
                'user_id' => $member->user_id,
                'company_id' => $ajo->company_id,
                'source_ajo_account_id' => null,
                'destination_ajo_account_id' => null,
                'main_account_id' => $mainAccount->id,
                'finova_transaction_id' => $collectionCoreTransaction->id,
                'company_financial_entry_id' => null,
                'type' => AjoTransaction::TYPE_SCHEDULED_COLLECTION,
                'amount' => $required,
                'fee' => 0,
                'company_fee_share' => 0,
                'creator_fee_share' => 0,
                'source_balance_before' => $actualBefore,
                'source_balance_after' => $actualAfter,
                'destination_balance_before' => $memberBalanceBefore,
                'destination_balance_after' => $memberBalanceAfter,
                'transaction_reference' => $collectionReference,
                'status' => 'successful',
                'description' => $pendingBefore > 0 ? 'Ajo scheduled collection with missed-cycle catch-up from main wallet.' : 'Automatic scheduled Ajo collection from main wallet.',
                'metadata' => [
                    'collection_cycle_id' => $cycle->id,
                    'scheduled_for' => $scheduledFor->toIso8601String(),
                    'previous_missed_amount' => $pendingBefore,
                    'current_collection_amount' => $collectionAmount,
                    'actual_debited' => $required,
                    'main_wallet_affected' => true,
                    'missed_cycle_ids' => $pendingCycles->pluck('id')->values()->all(),
                ],
            ]);

            $member->balance = $memberBalanceAfter;
            $member->total_contributed = round((float)$member->total_contributed + $required, 2);
            $member->pending_collection_amount = 0;

            $pendingFee = round((float) $member->pending_insufficient_balance_fees, 2);
            $feeTransaction = null;
            $feeFinancialEntry = null;
            if ($pendingFee > 0) {
                $walletAfterCollection = (float) $actualAfter;
                if ($walletAfterCollection >= $pendingFee) {
                    $feeDebit = debitCustomerOnly($mainAccount->id, $pendingFee, $member->user);
                    if (!($feeDebit['status'] ?? false)) {
                        throw new RuntimeException($feeDebit['message'] ?? 'Unable to debit insufficient-balance fee.');
                    }

                    $feeReference = $this->reference('AJIBF');
                    $feeCoreTransaction = $this->createCoreTransaction(
                        companyId: (int) $member->user->company_id,
                        transactionTypeId: self::TRANSACTION_TYPE_AJO_INSUFFICIENT_BALANCE_FEE,
                        creditDebitTypeId: self::DEBIT,
                        accountId: (int) $mainAccount->id,
                        amount: $pendingFee,
                        balanceBefore: (float) $feeDebit['balance_before'],
                        balanceAfter: (float) $feeDebit['balance_after'],
                        description: 'Ajo Insufficient Balance Fee',
                        reference: $feeReference
                    );

                    $feeSplit = $ajo->splitFee($pendingFee);
                    $creator = User::query()->find($ajo->creator_user_id);
                    if (!$creator) throw new RuntimeException('Ajo creator could not be found.');
                    $creatorAccount = $this->getMainAccount($creator);
                    $creatorBefore = $this->getAccountBalance($creatorAccount);
                    $creatorCredit = creditCustomerOnly($creatorAccount->id, $feeSplit['creator_share']);
                    if (!($creatorCredit['status'] ?? false)) throw new RuntimeException($creatorCredit['message'] ?? 'Unable to credit creator insufficient-balance fee share.');

                    $feeTransaction = AjoTransaction::create([
                        'ajo_id' => $ajo->id,
                        'ajo_member_id' => $member->id,
                        'ajo_collection_cycle_id' => $cycle->id,
                        'user_id' => $member->user_id,
                        'company_id' => $ajo->company_id,
                        'source_ajo_account_id' => null,
                        'destination_ajo_account_id' => null,
                        'main_account_id' => $mainAccount->id,
                        'finova_transaction_id' => $feeCoreTransaction->id,
                        'company_financial_entry_id' => null,
                        'type' => AjoTransaction::TYPE_INSUFFICIENT_BALANCE_FEE,
                        'amount' => $pendingFee,
                        'fee' => $pendingFee,
                        'company_fee_share' => $feeSplit['company_share'],
                        'creator_fee_share' => $feeSplit['creator_share'],
                        'source_balance_before' => (float) $feeDebit['balance_before'],
                        'source_balance_after' => (float) $feeDebit['balance_after'],
                        'destination_balance_before' => $creatorBefore,
                        'destination_balance_after' => (float) $creatorCredit['balance_after'],
                        'transaction_reference' => $feeReference,
                        'status' => 'successful',
                        'description' => 'Ajo insufficient-balance fee collected separately from contribution.',
                        'metadata' => [
                            'fee_type' => 'insufficient_balance_fee',
                            'fee_paid_from_main_wallet' => true,
                            'company_share' => $feeSplit['company_share'],
                            'creator_share' => $feeSplit['creator_share'],
                            'company_wallet_affected' => false,
                        ],
                    ]);

                    $feeFinancialEntry = $this->recordCompanyRevenue(
                        ajo: $ajo,
                        amount: $feeSplit['company_share'],
                        category: self::CATEGORY_INSUFFICIENT_BALANCE_FEE,
                        description: 'Ajo insufficient-balance fee company revenue share.',
                        reference: $this->reference('AJO-IBFR'),
                        transactionId: null,
                        metadata: [
                            'ajo_id' => $ajo->id,
                            'ajo_member_id' => $member->id,
                            'total_fee' => $pendingFee,
                            'company_share' => $feeSplit['company_share'],
                            'creator_share' => $feeSplit['creator_share'],
                            'ajo_transaction_id' => $feeTransaction->id,
                            'company_wallet_affected' => false,
                        ]
                    );
                    $feeTransaction->company_financial_entry_id = $feeFinancialEntry->id;
                    $feeTransaction->save();
                    $member->total_insufficient_balance_fees = round(
                        (float) $member->total_insufficient_balance_fees + $pendingFee,
                        2
                    );
                    $member->pending_insufficient_balance_fees = 0;
                    $actualAfter = (float) $feeDebit['balance_after'];
                    $cycle->fee_amount = $pendingFee;
                    $cycle->company_fee_share = $feeSplit['company_share'];
                    $cycle->creator_fee_share = $feeSplit['creator_share'];
                    $cycle->source_balance_after = $actualAfter;
                    $cycle->metadata = array_merge((array) $cycle->metadata, [
                        'insufficient_fee_transaction_id' => $feeTransaction->id,
                        'company_financial_entry_id' => $feeFinancialEntry->id,
                    ]);
                    $cycle->save();
                }
            }

            $member->save();

            // Mark the old missed cycles as settled without rewriting their historical status.
            foreach ($pendingCycles as $missed) {
                $metadata = (array)$missed->metadata;
                $metadata['settled_by_collection_cycle_id'] = $cycle->id;
                $metadata['settled_at'] = now()->toIso8601String();
                $missed->metadata = $metadata;
                $missed->save();
            }

            $cycle->metadata = array_merge((array)$cycle->metadata, ['ajo_transaction_id' => $collectionTransaction->id]);
            $cycle->save();

            return [
                'status' => 'successful',
                'ajo' => $ajo->fresh(),
                'member' => $member->fresh(),
                'collection_cycle' => $cycle->fresh(),
                'collection_transaction' => $collectionTransaction,
                'actual_debited' => $required,
                'previous_missed_amount' => $pendingBefore,
                'current_collection_amount' => $collectionAmount,
                'insufficient_fee_transaction' => $feeTransaction,
                'insufficient_fee_financial_entry' => $feeFinancialEntry,
            ];
        });
    }

/**
 * Record a failed collection attempt without mutating balances.
 *
 * The source account is the member's normal/main wallet because Ajo
 * collections no longer use a Personal Ajo Account.
 */
protected function recordFailedCollection(
    int $ajoId,
    int $memberId,
    Carbon $scheduledFor,
    Throwable $error
): void {
    DB::transaction(function () use (
        $ajoId,
        $memberId,
        $scheduledFor,
        $error
    ) {
        $ajo = Ajo::query()
            ->where('id', $ajoId)
            ->lockForUpdate()
            ->first();

        if (!$ajo) {
            return;
        }

        $member = AjoMember::query()
            ->where('id', $memberId)
            ->where('ajo_id', $ajoId)
            ->lockForUpdate()
            ->first();

        if (!$member) {
            return;
        }

        $existing = AjoCollectionCycle::query()
            ->where('ajo_id', $ajoId)
            ->where('ajo_member_id', $memberId)
            ->where('scheduled_for', $scheduledFor)
            ->first();

        if ($existing) {
            return;
        }

        $mainAccount = $this->getMainAccount($member->user);

        $mainAccount = wb_account_details::query()
            ->where('id', $mainAccount->id)
            ->lockForUpdate()
            ->first();

        $mainWalletBalance = $this->getAccountBalance($mainAccount);

        $cycleNumber = (int) (
            AjoCollectionCycle::query()
                ->where('ajo_member_id', $memberId)
                ->max('cycle_number')
            ?? 0
        ) + 1;

        $reference = $this->reference('AJCOLFAIL');

        AjoCollectionCycle::create([
            'ajo_id' => $ajoId,
            'ajo_member_id' => $memberId,
            'cycle_number' => $cycleNumber,
            'scheduled_for' => $scheduledFor,
            'processed_at' => now(),

            'amount' => round(
                (float) $ajo->collection_amount,
                2
            ),

            'source_balance_before' => $mainWalletBalance,
            'source_balance_after' => $mainWalletBalance,

            'member_balance_before' => (float) $member->balance,
            'member_balance_after' => (float) $member->balance,

            'insufficient_balance' => false,

            'insufficient_balance_count' =>
                (int) $member->insufficient_balance_count,

            'fee_amount' => 0,
            'company_fee_share' => 0,
            'creator_fee_share' => 0,

            'status' => AjoCollectionCycle::STATUS_FAILED,

            'failure_reason' => $error->getMessage(),

            'transaction_reference' => $reference,

            'metadata' => [
                'error_line' => $error->getLine(),
                'source_account' => 'main_wallet',
                'main_wallet_balance' => $mainWalletBalance,
            ],
        ]);
    });
}

    /**
     * Process all Ajos whose maturity date has arrived.
     *
     * Maturity fee:
     *   member position * snapshotted maturity fee percentage.
     *
     * The fee is split 40/60.
     * Remaining funds go to the member's personal Ajo Account.
     * Creator receives no maturity payout because creator is not a member.
     */
    public function processMaturity(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?: now())->copy();
        $processed = 0; $failed = 0; $skipped = 0;

        Ajo::query()->where('status', self::STATUS_ACTIVE)
            ->whereDate('maturity_date', '<=', $asOf->toDateString())
            ->orderBy('id')->chunkById(100, function ($ajos) use (&$processed, &$failed, &$skipped) {
                foreach ($ajos as $ajoSnapshot) {
                    try {
                        $result = DB::transaction(function () use ($ajoSnapshot) {
                            $ajo = Ajo::query()->where('id', $ajoSnapshot->id)->lockForUpdate()->first();
                            if (!$ajo || $ajo->status !== self::STATUS_ACTIVE) return ['status' => 'skipped'];

                            $members = AjoMember::query()
                                ->where('ajo_id', $ajo->id)
                                ->whereIn('status', [AjoMember::STATUS_ACTIVE, AjoMember::STATUS_LEAVE_REQUESTED])
                                ->orderBy('id')->lockForUpdate()->get();

                            $memberResults = [];
                            foreach ($members as $member) {
                                $gross = round((float)$member->total_contributed, 2);
                                $fee = min(round($gross * ((float)$ajo->maturity_fee_percentage / 100), 2), $gross);
                                $remaining = round($gross - $fee, 2);
                                $split = $ajo->splitFee($fee);

                                $feeTransaction = null;
                                $financialEntry = null;
                                $creatorCredit = null;

                                if ($fee > 0) {
                                    $creator = User::query()->find($ajo->creator_user_id);
                                    if (!$creator) throw new RuntimeException('Ajo creator could not be found.');
                                    $creatorAccount = $this->getMainAccount($creator);
                                    $creatorBefore = $this->getAccountBalance($creatorAccount);
                                    $creatorCredit = creditCustomerOnly($creatorAccount->id, $split['creator_share']);
                                    if (!($creatorCredit['status'] ?? false)) throw new RuntimeException($creatorCredit['message'] ?? 'Unable to credit creator maturity fee share.');
                                    $creatorFeeCoreTransaction = $this->createCoreTransaction(
                                        companyId: (int) $creator->company_id,
                                        transactionTypeId: self::TRANSACTION_TYPE_AJO_MATURITY_FEE,
                                        creditDebitTypeId: self::CREDIT,
                                        accountId: (int) $creatorAccount->id,
                                        amount: $split['creator_share'],
                                        balanceBefore: $creatorBefore,
                                        balanceAfter: (float) $creatorCredit['balance_after'],
                                        description: 'Ajo Maturity Creator Fee Share',
                                        reference: $this->reference('AJMFEE-CR')
                                    );

                                    $feeTransaction = AjoTransaction::create([
                                        'ajo_id' => $ajo->id,
                                        'ajo_member_id' => $member->id,
                                        'ajo_collection_cycle_id' => null,
                                        'user_id' => $member->user_id,
                                        'company_id' => $ajo->company_id,
                                        'source_ajo_account_id' => null,
                                        'destination_ajo_account_id' => null,
                                        'main_account_id' => $creatorAccount->id,
                                        'finova_transaction_id' => $creatorFeeCoreTransaction->id,
                                        'company_financial_entry_id' => null,
                                        'type' => AjoTransaction::TYPE_MATURITY_FEE,
                                        'amount' => $fee,
                                        'fee' => $fee,
                                        'company_fee_share' => $split['company_share'],
                                        'creator_fee_share' => $split['creator_share'],
                                        'source_balance_before' => $gross,
                                        'source_balance_after' => $remaining,
                                        'destination_balance_before' => $creatorBefore,
                                        'destination_balance_after' => (float)$creatorCredit['balance_after'],
                                        'transaction_reference' => $this->reference('AJMFEE'),
                                        'status' => 'successful',
                                        'description' => 'Ajo maturity fee charged from member contribution.',
                                        'metadata' => [
                                            'gross_contribution' => $gross,
                                            'maturity_fee_percentage' => (float)$ajo->maturity_fee_percentage,
                                            'company_share' => $split['company_share'],
                                            'creator_share' => $split['creator_share'],
                                            'creator_share_destination' => 'main_wallet',
                                            'company_wallet_affected' => false,
                                        ],
                                    ]);

                                    $financialEntry = $this->recordCompanyRevenue(
                                        ajo: $ajo,
                                        amount: $split['company_share'],
                                        category: self::CATEGORY_MATURITY_FEE,
                                        description: 'Ajo maturity fee company revenue share.',
                                        reference: $this->reference('AJO-MFR'),
                                        transactionId: null,
                                        metadata: [
                                            'ajo_id' => $ajo->id,
                                            'ajo_member_id' => $member->id,
                                            'total_fee' => $fee,
                                            'company_share' => $split['company_share'],
                                            'creator_share' => $split['creator_share'],
                                            'ajo_transaction_id' => $feeTransaction->id,
                                            'company_wallet_affected' => false,
                                        ]
                                    );
                                    $feeTransaction->company_financial_entry_id = $financialEntry->id;
                                    $feeTransaction->save();
                                }

                                $memberAccount = $this->getMainAccount($member->user);
                                $memberBefore = $this->getAccountBalance($memberAccount);
                                $returnCredit = $remaining > 0
                                    ? creditCustomerOnly($memberAccount->id, $remaining)
                                    : ['status'=>true,'balance_before'=>$memberBefore,'balance_after'=>$memberBefore];
                                if (!($returnCredit['status'] ?? false)) throw new RuntimeException($returnCredit['message'] ?? 'Unable to return maturity funds to member main wallet.');

                                $returnCoreTransaction = null;
                                if ($remaining > 0) {
                                    $returnCoreTransaction = $this->createCoreTransaction(
                                        companyId: (int) $member->user->company_id,
                                        transactionTypeId: self::TRANSACTION_TYPE_AJO_MATURITY_RETURN,
                                        creditDebitTypeId: self::CREDIT,
                                        accountId: (int) $memberAccount->id,
                                        amount: $remaining,
                                        balanceBefore: $memberBefore,
                                        balanceAfter: (float) $returnCredit['balance_after'],
                                        description: 'Ajo Maturity Return',
                                        reference: $this->reference('AJMRETURN')
                                    );
                                }

                                $returnTransaction = AjoTransaction::create([
                                    'ajo_id' => $ajo->id,
                                    'ajo_member_id' => $member->id,
                                    'ajo_collection_cycle_id' => null,
                                    'user_id' => $member->user_id,
                                    'company_id' => $ajo->company_id,
                                    'source_ajo_account_id' => null,
                                    'destination_ajo_account_id' => null,
                                    'main_account_id' => $memberAccount->id,
                                    'finova_transaction_id' => $returnCoreTransaction?->id,
                                    'company_financial_entry_id' => null,
                                    'type' => AjoTransaction::TYPE_MATURITY_RETURN,
                                    'amount' => $remaining,
                                    'fee' => 0,
                                    'company_fee_share' => 0,
                                    'creator_fee_share' => 0,
                                    'source_balance_before' => $remaining,
                                    'source_balance_after' => 0,
                                    'destination_balance_before' => $memberBefore,
                                    'destination_balance_after' => (float)$returnCredit['balance_after'],
                                    'transaction_reference' => $this->reference('AJMRETURN'),
                                    'status' => 'successful',
                                    'description' => 'Ajo matured and net contribution returned to member main wallet.',
                                    'metadata' => [
                                        'gross_contribution' => $gross,
                                        'maturity_fee' => $fee,
                                        'net_return' => $remaining,
                                        'destination' => 'main_wallet',
                                        'historical_total_contributed_preserved' => true,
                                    ],
                                ]);

                                $member->balance = 0;
                                $member->total_maturity_fees = round((float)$member->total_maturity_fees + $fee, 2);
                                $member->status = AjoMember::STATUS_MATURED;
                                $member->left_at = null;
                                $member->save();

                                $memberResults[] = [
                                    'member_id' => $member->id,
                                    'gross_contributed' => $gross,
                                    'maturity_fee' => $fee,
                                    'remaining' => $remaining,
                                    'fee_split' => $split,
                                    'fee_transaction' => $feeTransaction,
                                    'return_transaction' => $returnTransaction,
                                    'financial_entry' => $financialEntry,
                                ];
                            }

                            $ajo->status = self::STATUS_MATURED;
                            $ajo->next_collection_at = null;
                            $ajo->save();
                            return ['status'=>'successful','ajo'=>$ajo->fresh(),'members'=>$memberResults];
                        });

                        if (($result['status'] ?? null) === 'successful') $processed++; else $skipped++;
                    } catch (Throwable $e) {
                        $failed++;
                        logInfo(['ajo_id'=>$ajoSnapshot->id,'error'=>$e->getMessage(),'line'=>$e->getLine()], 'Ajo maturity processing error');
                    }
                }
            });

        return ['processed'=>$processed,'failed'=>$failed,'skipped'=>$skipped];
    }

    /**
     * Return the conditions text that the UI/API can display.
     */
    public function getConditions(Ajo $ajo): string
    {
        return (string) $ajo->terms;
    }

    /**
     * Calculate the fee split for an arbitrary amount using this Ajo's
     * snapshotted 40/60 split.
     */
    public function calculateFeeSplit(Ajo $ajo, float $fee): array
    {
        return $ajo->splitFee($fee);
    }

    /**
     * Ensure a user's personal Ajo Account exists.
     */


    /**
     * Resolve a user's first main/wallet account using the same relationship
     * pattern used by TargetSavingsService.
     */
    protected function getMainAccount($user): wb_account_details
    {
        $account = $user->wb_account_details()
            ->orderBy('id')
            ->first();

        if (!$account) {
            throw new RuntimeException(
                'Customer main account could not be found.'
            );
        }

        return $account;
    }

    protected function getAccountBalance(wb_account_details $account): float
    {
        $wallet = user_wallet::query()
            ->where('wb_account_detail_id', $account->id)
            ->lockForUpdate()
            ->first();

        if (!$wallet) {
            throw new RuntimeException('Customer main wallet could not be found.');
        }

        return round((float) $wallet->balance, 2);
    }

    /**
     * Retrieve and lock an Ajo.
     */
    protected function getAjoForUpdate(int $ajoId): Ajo
    {
        $ajo = Ajo::query()
            ->where('id', $ajoId)
            ->lockForUpdate()
            ->first();

        if (!$ajo) {
            throw new RuntimeException('Ajo not found.');
        }

        return $ajo;
    }

    /**
     * Create an Ajo membership.
     */
    protected function createMembership(Ajo $ajo, $user, ?string $termsSnapshot, ?string $termsVersion): AjoMember
    {
        $existing = AjoMember::query()
            ->where('ajo_id', $ajo->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [AjoMember::STATUS_ACTIVE, AjoMember::STATUS_LEAVE_REQUESTED])
            ->first();
        if ($existing) throw new RuntimeException('You are already a member of this Ajo.');

        $activeMembers = AjoMember::query()->where('ajo_id', $ajo->id)
            ->whereIn('status', [AjoMember::STATUS_ACTIVE, AjoMember::STATUS_LEAVE_REQUESTED])->count();
        if ($ajo->max_members !== null && $activeMembers >= (int)$ajo->max_members) {
            throw new RuntimeException('This Ajo has reached its maximum member capacity.');
        }

        return AjoMember::create([
            'ajo_id' => $ajo->id,
            'user_id' => $user->id,
            'balance' => 0,
            'total_contributed' => 0,
            'pending_collection_amount' => 0,
            'pending_insufficient_balance_fees' => 0,
            'insufficient_balance_count' => 0,
            'total_insufficient_balance_fees' => 0,
            'total_maturity_fees' => 0,
            'total_early_leave_penalties' => 0,
            'status' => AjoMember::STATUS_ACTIVE,
            'terms_snapshot' => $termsSnapshot,
            'terms_version' => $termsVersion,
            'terms_accepted_at' => now(),
            'joined_at' => now(),
            'left_at' => null,
            'early_leave_requested_at' => null,
            'early_leave_processed_at' => null,
            'early_leave_processed_by' => null,
            'early_leave_reason' => null,
        ]);
    }

    /**
     * Validate creator/member user basics.
     */
    protected function validateUserForAjo($user): void
    {
        if (!$user || empty($user->id)) {
            throw new RuntimeException('Valid user account is required.');
        }

        if (empty($user->company_id)) {
            throw new RuntimeException(
                'Your account is not linked to a company.'
            );
        }
    }

    /**
     * Confirm the authenticated user is the Ajo creator.
     */
    protected function assertCreator(Ajo $ajo, $user): void
    {
        $this->validateUserForAjo($user);

        if ((int) $ajo->creator_user_id !== (int) $user->id) {
            throw new RuntimeException(
                'Only the Ajo creator can perform this action.'
            );
        }
    }

    /**
     * Validate fixed / percentage fee configuration.
     */
    protected function validateFeeConfiguration(
        string $type,
        float $value,
        string $label
    ): void {
        if (!in_array($type, ['fixed', 'percentage'], true)) {
            throw new RuntimeException(
                ucfirst($label) . ' type must be fixed or percentage.'
            );
        }

        if ($value < 0) {
            throw new RuntimeException(
                ucfirst($label) . ' cannot be negative.'
            );
        }

        if ($type === 'percentage' && $value > 100) {
            throw new RuntimeException(
                ucfirst($label) . ' percentage cannot exceed 100%.'
            );
        }
    }

    /**
     * Calculate a configured fee.
     *
     * For percentage fees the supplied $baseAmount is used.
     */
    protected function calculateConfiguredFee(
        string $type,
        float $configuredValue,
        float $baseAmount
    ): float {
        $configuredValue = max($configuredValue, 0);
        $baseAmount = max($baseAmount, 0);

        if ($configuredValue <= 0 || $baseAmount <= 0) {
            return 0;
        }

        if ($type === 'fixed') {
            return round($configuredValue, 2);
        }

        return round(
            $baseAmount * ($configuredValue / 100),
            2
        );
    }

    /**
     * Validate and normalise collection time.
     */
    protected function normaliseCollectionTime(?string $time): string
    {
        $time = trim((string) $time);

        if ($time === '') {
            throw new RuntimeException(
                'Collection time is required because the creator controls when automatic collection occurs.'
            );
        }

        try {
            $parsed = Carbon::createFromFormat('H:i', $time);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Collection time must use HH:MM format.'
            );
        }

        if (!$parsed || $parsed->format('H:i') !== $time) {
            throw new RuntimeException(
                'Collection time must use HH:MM format.'
            );
        }

        return $parsed->format('H:i:s');
    }

    /**
     * Resolve collection day.
     *
     * Weekly: ISO weekday 1..7 (Monday..Sunday)
     * Monthly: day-of-month 1..31
     * Daily: null
     */
    protected function normaliseCollectionDay(
        string $frequency,
        $collectionDay,
        Carbon $startDate
    ): ?int {
        if ($frequency === 'daily') {
            return null;
        }

        if ($collectionDay === null || $collectionDay === '') {
            return $frequency === 'weekly'
                ? (int) $startDate->isoWeekday()
                : (int) $startDate->day;
        }

        $collectionDay = (int) $collectionDay;

        if ($frequency === 'weekly') {
            if ($collectionDay < 1 || $collectionDay > 7) {
                throw new RuntimeException(
                    'Weekly collection day must be between 1 and 7 (Monday to Sunday).'
                );
            }

            return $collectionDay;
        }

        if ($collectionDay < 1 || $collectionDay > 31) {
            throw new RuntimeException(
                'Monthly collection day must be between 1 and 31.'
            );
        }

        return $collectionDay;
    }

    /**
     * Parse a date in the application timezone.
     */
    protected function parseDate($value, string $label): Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new RuntimeException("{$label} is required.");
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (Throwable $e) {
            throw new RuntimeException(
                "{$label} must use YYYY-MM-DD format."
            );
        }

        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new RuntimeException(
                "{$label} must use YYYY-MM-DD format."
            );
        }

        return $date->startOfDay();
    }

    /**
     * Calculate the first scheduled collection timestamp.
     */
    protected function calculateInitialCollectionAt(
        Carbon $startDate,
        string $frequency,
        int $interval,
        ?int $collectionDay,
        string $collectionTime
    ): Carbon {
        [$hour, $minute] = array_map(
            'intval',
            explode(':', substr($collectionTime, 0, 5))
        );

        $candidate = $startDate->copy()
            ->setTime($hour, $minute, 0);

        if ($frequency === 'daily') {
            while ($candidate->lte(now())) {
                $candidate->addDays($interval);
            }

            return $candidate;
        }

        if ($frequency === 'weekly') {
            $daysForward = (
                ($collectionDay - $candidate->isoWeekday()) + 7
            ) % 7;

            if ($daysForward === 0 && $candidate->lte(now())) {
                $daysForward = 7 * $interval;
            }

            $candidate->addDays($daysForward);

            while ($candidate->lte(now())) {
                $candidate->addWeeks($interval);
            }

            return $candidate;
        }

        $candidate->day = min(
            $collectionDay,
            $candidate->daysInMonth
        );

        while ($candidate->lte(now())) {
            $candidate = $this->addMonthsKeepingDay(
                $candidate,
                $interval,
                $collectionDay,
                $hour,
                $minute
            );
        }

        return $candidate;
    }

    /**
     * Calculate the next collection after an already-executed schedule.
     */
    protected function calculateNextCollectionAt(
        Ajo $ajo,
        Carbon $current
    ): Carbon {
        $time = substr((string) $ajo->collection_time, 0, 5);
        [$hour, $minute] = array_map(
            'intval',
            explode(':', $time)
        );

        $frequency = $ajo->collection_frequency;
        $interval = max((int) $ajo->collection_interval, 1);

        if ($frequency === 'daily') {
            return $current->copy()
                ->addDays($interval)
                ->setTime($hour, $minute, 0);
        }

        if ($frequency === 'weekly') {
            return $current->copy()
                ->addWeeks($interval)
                ->setTime($hour, $minute, 0);
        }

        return $this->addMonthsKeepingDay(
            $current,
            $interval,
            (int) $ajo->collection_day,
            $hour,
            $minute
        );
    }

    /**
     * Monthly schedule helper which safely clamps dates such as the 31st to
     * the last valid day of shorter months.
     */
    protected function addMonthsKeepingDay(
        Carbon $date,
        int $interval,
        int $desiredDay,
        int $hour,
        int $minute
    ): Carbon {
        $next = $date->copy()->startOfMonth()->addMonthsNoOverflow($interval);

        $next->day = min(
            $desiredDay,
            $next->daysInMonth
        );

        $next->setTime($hour, $minute, 0);

        return $next;
    }

    /**
     * Build the mandatory conditions shown to the creator/member.
     *
     * The creator-supplied custom terms are preserved and the mandatory
     * financial/scheduling rules are appended explicitly so there is no
     * ambiguity about the 40/60 fee sharing.
     */
    protected function buildTerms(
        string $name,
        ?string $description,
        float $collectionAmount,
        string $collectionFrequency,
        int $collectionInterval,
        ?int $collectionDay,
        string $collectionTime,
        Carbon $startDate,
        Carbon $maturityDate,
        float $creationFee,
        float $maturityFeePercentage,
        string $earlyLeavePenaltyType,
        float $earlyLeavePenaltyValue,
        string $insufficientFeeType,
        float $insufficientFeeValue,
        string $visibility,
        ?int $maxMembers
    ): string {
        $lines = [
            "AJO TERMS AND CONDITIONS",
            "Ajo Name: {$name}",
            "",
        ];
        if ($description) {
            $lines[] = "Description: " . trim($description);
            $lines[] = "";
        }
        $lines[] = "1. CREATOR AND MEMBERS";
        $lines[] = "The Ajo creator is the overseer and may also join this Ajo as a member.";
        $lines[] = "When the creator joins, the creator has an ordinary member position and all member collections are taken from the creator's main wallet.";
        $lines[] = "";
        $lines[] = "2. COLLECTION";
        $lines[] = "Collection amount: ₦" . number_format($collectionAmount, 2);
        $lines[] = "Collection frequency: " . ucfirst($collectionFrequency);
        $lines[] = "Collection interval: every {$collectionInterval} {$collectionFrequency}" . ($collectionInterval === 1 ? "" : "s") . ".";
        $lines[] = "Collection time: {$collectionTime}";
        $lines[] = "Start date: " . $startDate->toDateString();
        $lines[] = "Maturity date: " . $maturityDate->toDateString();
        if ($collectionDay !== null) {
            $lines[] = "Scheduled collection day: " . ($collectionFrequency === 'weekly' ? $this->weekdayLabel($collectionDay) : "day {$collectionDay} of the month") . ".";
        }
        $lines[] = "Every scheduled collection is debited from the member's MAIN WALLET.";
        $lines[] = "A main wallet is never allowed to become negative because of an Ajo collection.";
        $lines[] = "If the wallet cannot cover all outstanding missed collections plus the current collection, nothing is debited and the obligation remains outstanding.";
        $lines[] = "";
        $lines[] = "3. CONTRIBUTION HISTORY";
        $lines[] = "A member's total contributed amount is the exact cumulative amount successfully debited from that member's main wallet for this Ajo.";
        $lines[] = "Amounts that were scheduled but not successfully debited are not counted as contributions.";
        $lines[] = "When funds become sufficient, missed collections are caught up together with the current collection in one auditable debit.";
        $lines[] = "";
        $lines[] = "4. INSUFFICIENT-BALANCE FEE";
        $lines[] = "Every 3rd insufficient-balance event (3rd, 6th, 9th, and so on) creates the configured insufficient-balance fee obligation.";
        $lines[] = "Fee configuration: " . $this->feeLabel($insufficientFeeType, $insufficientFeeValue, $collectionAmount) . ".";
        $lines[] = "Insufficient-balance fees are separate from member contribution amounts and do not increase total_contributed.";
        $lines[] = "";
        $lines[] = "5. MATURITY";
        $lines[] = "At maturity, the maturity fee is calculated from the member's actual gross total_contributed amount.";
        $lines[] = "Net maturity return = gross total_contributed minus the applicable maturity fee.";
        $lines[] = "The net maturity return is credited to the member's main wallet.";
        $lines[] = "";
        $lines[] = "6. EARLY LEAVE";
        $lines[] = "A member may request early leave, subject to creator approval.";
        $lines[] = "The early-leave penalty is calculated from the member's current contributed position.";
        $lines[] = "After the penalty, the remaining amount is credited to the member's main wallet.";
        $lines[] = "Historical total_contributed is never rewritten by the penalty or return.";
        $lines[] = "";
        $lines[] = "7. FEE SHARING";
        $lines[] = "Insufficient-balance fees, maturity fees and early-leave penalties are split 40% to the company and 60% to the Ajo creator.";
        $lines[] = "The creator's 60% fee share is credited to the creator's main wallet.";
        $lines[] = "The company's 40% share is recognized as company revenue.";
        $lines[] = "The creation fee of ₦" . number_format($creationFee, 2) . " belongs 100% to the company and is paid by the creator from the creator's main wallet.";
        $lines[] = "";
        $lines[] = "8. VISIBILITY AND CAPACITY";
        $lines[] = "Visibility: " . ucfirst($visibility) . ".";
        $lines[] = $maxMembers !== null ? "Maximum members: {$maxMembers}." : "Maximum members: No fixed maximum configured.";
        $lines[] = "";
        $lines[] = "9. ACCEPTANCE";
        $lines[] = "By joining, a member confirms acceptance of the collection schedule, main-wallet debit rules, missed-cycle catch-up rules, applicable fees and the 40% company / 60% creator fee-sharing arrangement.";
        return implode("\n", $lines);
    }

    /**
     * Human-readable fee label for the Ajo terms.
     */
    protected function feeLabel(
        string $type,
        float $value,
        ?float $percentageBase = null
    ): string {
        if ($type === 'fixed') {
            return '₦' . number_format($value, 2) . ' fixed fee';
        }

        if ($percentageBase !== null) {
            return number_format($value, 4)
                . '% of the scheduled collection amount';
        }

        return number_format($value, 4)
            . "% of the member's Ajo balance";
    }

    /**
     * Human-readable weekly day label.
     */
    protected function weekdayLabel(int $day): string
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return $days[$day] ?? 'Unknown';
    }

    /**
     * Core Finova transaction helper.
     *
     * This follows the existing TargetSavingsService structure:
     * - get_CR_DR_TXN_Type_Ledger()
     * - resolve branch from wb_account_details -> user
     * - transaction_status_id = 1
     * - transaction_remarks_id = 2
     * - company wallet fields are zero unless a real company wallet movement
     *   is explicitly introduced later.
     */
    protected function createCoreTransaction(
        int $companyId,
        int $transactionTypeId,
        int $creditDebitTypeId,
        int $accountId,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        float $companyAmount = 0,
        float $companyBalanceBefore = 0,
        float $companyBalanceAfter = 0,
        ?string $description = null,
        ?string $reference = null
    ): transactions {
        $ledgerCr = null;
        $ledgerDr = null;

        $ledgerSettings = get_CR_DR_TXN_Type_Ledger(
            $transactionTypeId,
            $companyId
        );

        if ($ledgerSettings) {
            $ledgerCr = $ledgerSettings->ledger_cr;
            $ledgerDr = $ledgerSettings->ledger_dr;
        }

        $account = wb_account_details::with('user')
            ->find($accountId);

        $branchId = $account?->user?->branch_id;

        return transactions::create([
            'account_id' => $accountId,
            'transaction_reference' =>
                $reference
                ?? $this->reference('AJ'),
            'balance_before' => $balanceBefore,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'company_balance_before' => $companyBalanceBefore,
            'company_amount' => $companyAmount,
            'company_balance_after' => $companyBalanceAfter,
            'description' => $description ?? 'Ajo transaction',
            'system_description' => $description ?? 'Ajo transaction',
            'branch_id' => $branchId,
            'parent_transaction_id' => null,
            'transaction_status_id' => 1,
            'company_id' => $companyId,
            'transaction_type_id' => $transactionTypeId,
            'transaction_remarks_id' => 2,
            'credit_debit_type_id' => $creditDebitTypeId,
            'created_by' => self::CORE_CREATED_BY,
            'created_date' => today(),
            'ledger_cr' => $ledgerCr,
            'ledger_dr' => $ledgerDr,
        ]);
    }

    /**
     * Record company revenue without changing company_wallet.balance.
     *
     * CompanyFinancialEntry currently follows the Target Savings / Campaign
     * pattern, so Ajo linkage is preserved in metadata rather than assuming a
     * new ajo_id column exists on that legacy financial table.
     */
    protected function recordCompanyRevenue(
        Ajo $ajo,
        float $amount,
        string $category,
        string $description,
        string $reference,
        ?int $transactionId = null,
        ?array $metadata = null
    ): CompanyFinancialEntry {
        if ($amount <= 0) {
            throw new RuntimeException(
                'Revenue amount must be greater than zero.'
            );
        }

        $existing = CompanyFinancialEntry::query()
            ->where('reference', $reference)
            ->first();

        if ($existing) {
            return $existing;
        }

        return CompanyFinancialEntry::create([
            'company_id' => $ajo->company_id,
            'user_id' => $ajo->creator_user_id,
            'target_savings_id' => null,
            'campaign_id' => null,
            'transaction_id' => $transactionId,
            'type' => self::FINANCIAL_REVENUE,
            'category' => $category,
            'amount' => round($amount, 2),
            'reference' => $reference,
            'description' => $description,
            'metadata' => $metadata,
            'status' => 'successful',
        ]);
    }

    /**
     * Generate an application-level transaction reference.
     */
    protected function reference(string $prefix): string
    {
        return $prefix . '-' . strtoupper(Str::random(20));
    }
}
