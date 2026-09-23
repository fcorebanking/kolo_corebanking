<?php

namespace App\Services;

use App\Models\Ajo;
use App\Models\AjoAccount;
use App\Models\AjoCollectionCycle;
use App\Models\AjoInvitation;
use App\Models\AjoMember;
use App\Models\AjoTransaction;
use App\Models\CompanyFinancialEntry;
use App\Models\transactions;
use App\Models\User;
use App\Models\wb_account_details;
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
 * 1. AjoAccount is the user's separate personal Ajo wallet.
 * 2. AjoMember::balance is the user's committed balance inside a specific
 *    Ajo. It is NOT part of their spendable AjoAccount balance.
 * 3. The Ajo creator is NEVER an AjoMember. The creator is only the overseer.
 * 4. Scheduled collections debit the personal AjoAccount, not the main wallet.
 * 5. Scheduled collections are allowed to take the personal AjoAccount below 0.
 * 6. Every 3rd insufficient-balance event (3rd, 6th, 9th, ...) creates a fee.
 * 7. Insufficient-balance, maturity, and early-leave fees are split 40/60:
 *      Company = 40%
 *      Creator = 60%
 * 8. Creation fee belongs 100% to the company.
 * 9. Company fee income is recognized with CompanyFinancialEntry. We do not
 *    directly mutate company_wallet.balance, matching TargetSavingsService.
 * 10. Maturity returns go to the member's personal AjoAccount, not the main wallet.
 */
class AjoService
{
    /*
     * Core Finova transaction type IDs.
     *
     * These should exist in transaction_types / the application's ledger setup.
     */
    private const TRANSACTION_TYPE_AJO_ACCOUNT_DEPOSIT = 131;
    private const TRANSACTION_TYPE_AJO_ACCOUNT_WITHDRAWAL = 132;
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

            $company = $creator->company;
            $companySettings = $company?->setting;

            if (!$companySettings) {
                throw new RuntimeException(
                    'Company Ajo settings could not be found.'
                );
            }

            $ajoAccount = $this->ensureAjoAccount($creator);

            $name = trim((string) ($data['ajo_name'] ?? ''));

            if ($name === '') {
                throw new RuntimeException('Ajo name is required.');
            }

            $collectionAmount = round(
                (float) ($data['collection_amount'] ?? 0),
                2
            );

            if ($collectionAmount <= 0) {
                throw new RuntimeException(
                    'Ajo collection amount must be greater than zero.'
                );
            }

            $frequency = strtolower(
                trim((string) ($data['collection_frequency'] ?? ''))
            );

            if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
                throw new RuntimeException(
                    'Collection frequency must be daily, weekly or monthly.'
                );
            }

            $interval = (int) ($data['collection_interval'] ?? 1);

            if ($interval < 1) {
                throw new RuntimeException(
                    'Collection interval must be at least 1.'
                );
            }

            $collectionTime = $this->normaliseCollectionTime(
                $data['collection_time'] ?? null
            );

            $startDate = $this->parseDate(
                $data['start_date'] ?? null,
                'Ajo start date'
            )->startOfDay();

            $maturityDate = $this->parseDate(
                $data['maturity_date'] ?? null,
                'Ajo maturity date'
            )->startOfDay();

            if ($maturityDate->lte($startDate)) {
                throw new RuntimeException(
                    'Ajo maturity date must be after the start date.'
                );
            }

            $collectionDay = $this->normaliseCollectionDay(
                $frequency,
                $data['collection_day'] ?? null,
                $startDate
            );

            $creationFee = round(
                (float) ($companySettings->ajo_creation_fee ?? 0),
                2
            );

            if ($creationFee < 0) {
                throw new RuntimeException(
                    'Invalid Ajo creation fee configured for this company.'
                );
            }

            $maturityFeePercentage = round(
                (float) (
                    $companySettings->ajo_maturity_fee_percentage
                    ?? 0
                ),
                4
            );

            if (
                $maturityFeePercentage < 0 ||
                $maturityFeePercentage > 100
            ) {
                throw new RuntimeException(
                    'Invalid Ajo maturity fee percentage configured for this company.'
                );
            }

            $earlyLeavePenaltyType = strtolower(
                trim((string) (
                    $data['early_leave_penalty_type'] ?? 'percentage'
                ))
            );

            $earlyLeavePenaltyValue = round(
                (float) (
                    $data['early_leave_penalty_value'] ?? 0
                ),
                4
            );

            $this->validateFeeConfiguration(
                $earlyLeavePenaltyType,
                $earlyLeavePenaltyValue,
                'early leave penalty'
            );

            $insufficientFeeType = strtolower(
                trim((string) (
                    $data['insufficient_balance_fee_type'] ?? 'fixed'
                ))
            );

            $insufficientFeeValue = round(
                (float) (
                    $data['insufficient_balance_fee_value'] ?? 0
                ),
                4
            );

            $this->validateFeeConfiguration(
                $insufficientFeeType,
                $insufficientFeeValue,
                'insufficient-balance fee'
            );

            if (
                $insufficientFeeType === 'percentage' &&
                $insufficientFeeValue > 100
            ) {
                throw new RuntimeException(
                    'Insufficient-balance percentage fee cannot exceed 100%.'
                );
            }

            $maxMembers = array_key_exists('max_members', $data)
                && $data['max_members'] !== null
                ? (int) $data['max_members']
                : null;

            if ($maxMembers !== null && $maxMembers < 1) {
                throw new RuntimeException(
                    'Maximum members must be at least 1.'
                );
            }

            $visibility = strtolower(
                trim((string) ($data['visibility'] ?? 'public'))
            );

            if (!in_array($visibility, ['public', 'private'], true)) {
                throw new RuntimeException(
                    'Ajo visibility must be public or private.'
                );
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

            /*
             * Create the Ajo first as draft so the creation fee transaction
             * can be linked to a real Ajo record inside this same DB transaction.
             */
            $ajo = Ajo::create([
                'creator_user_id' => $creator->id,
                'company_id' => $creator->company_id,
                'creator_ajo_account_id' => $ajoAccount->id,
                'ajo_name' => $name,
                'description' => !empty($data['description'])
                    ? trim((string) $data['description'])
                    : null,
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

            /*
             * Creation fee is paid from the creator's MAIN wallet.
             * It belongs 100% to the company.
             */
            $creationFeeTransaction = null;
            $creationFinancialEntry = null;
            $creationCoreTransaction = null;

            if ($creationFee > 0) {
                $mainAccount = $this->getMainAccount($creator);

                $debit = debitCustomerOnly(
                    $mainAccount->id,
                    $creationFee,
                    $creator
                );

                if (!($debit['status'] ?? false)) {
                    throw new RuntimeException(
                        $debit['message']
                        ?? 'Unable to charge Ajo creation fee.'
                    );
                }

                $reference = $this->reference('AJC');

                $creationCoreTransaction =
                    $this->createCoreTransaction(
                        companyId: (int) $creator->company_id,
                        transactionTypeId:
                            self::TRANSACTION_TYPE_AJO_CREATION_FEE,
                        creditDebitTypeId: self::DEBIT,
                        accountId: (int) $mainAccount->id,
                        amount: $creationFee,
                        balanceBefore: (float) $debit['balance_before'],
                        balanceAfter: (float) $debit['balance_after'],
                        description: 'Ajo Creation Fee',
                        reference: $reference
                    );

                $creationTransactionReference = $this->reference('AJO-FEE');

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
                    'source_balance_before' =>
                        (float) $debit['balance_before'],
                    'source_balance_after' =>
                        (float) $debit['balance_after'],
                    'destination_balance_before' => null,
                    'destination_balance_after' => null,
                    'transaction_reference' => $creationTransactionReference,
                    'status' => 'successful',
                    'description' =>
                        'Ajo creation fee charged to creator.',
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

                $creationFinancialEntry =
                    $this->recordCompanyRevenue(
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
                            'company_share_percentage' => 100,
                            'creator_share_percentage' => 0,
                            'ajo_transaction_id' => $creationFeeTransaction->id,
                            'company_wallet_affected' => false,
                        ]
                    );

                $creationFeeTransaction->company_financial_entry_id =
                    $creationFinancialEntry->id;
                $creationFeeTransaction->save();
            }

            $ajo->status = self::STATUS_ACTIVE;
            $ajo->save();

            return $ajo->fresh();
        });
    }

    /**
     * Return a user's permanent personal Ajo Account.
     *
     * This also safely creates it on demand for older users who registered
     * before the registration flow was updated. New registration should still
     * create it automatically as part of registration as planned.
     */
    public function getOrCreateAjoAccount($user): AjoAccount
    {
        return DB::transaction(function () use ($user) {
            $this->validateUserForAjo($user);

            return $this->ensureAjoAccount($user);
        });
    }

    /**
     * Voluntarily move money from the user's MAIN wallet to their personal
     * Ajo Account.
     *
     * This is NOT tied to any specific Ajo group.
     */
    public function depositToAjoAccount($user, float $amount): array
    {
        if ($amount <= 0) {
            throw new RuntimeException(
                'Ajo Account deposit amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use ($user, $amount) {
            $this->validateUserForAjo($user);

            $ajoAccount = $this->ensureAjoAccount($user, true);
            $mainAccount = $this->getMainAccount($user);

            $debit = debitCustomerOnly(
                $mainAccount->id,
                $amount,
                $user
            );

            if (!($debit['status'] ?? false)) {
                throw new RuntimeException(
                    $debit['message']
                    ?? 'Unable to debit main account.'
                );
            }

            $sourceBalanceBefore = (float) $debit['balance_before'];
            $sourceBalanceAfter = (float) $debit['balance_after'];

            $ajoAccount = AjoAccount::query()
                ->where('id', $ajoAccount->id)
                ->lockForUpdate()
                ->firstOrFail();

            $destinationBalanceBefore = round(
                (float) $ajoAccount->balance,
                2
            );

            $destinationBalanceAfter = round(
                $destinationBalanceBefore + $amount,
                2
            );

            $ajoAccount->balance = $destinationBalanceAfter;
            $ajoAccount->save();

            $reference = $this->reference('AJAD');

            $coreTransaction = $this->createCoreTransaction(
                companyId: (int) $user->company_id,
                transactionTypeId:
                    self::TRANSACTION_TYPE_AJO_ACCOUNT_DEPOSIT,
                creditDebitTypeId: self::DEBIT,
                accountId: (int) $mainAccount->id,
                amount: $amount,
                balanceBefore: $sourceBalanceBefore,
                balanceAfter: $sourceBalanceAfter,
                description: 'Ajo Account Deposit',
                reference: $reference
            );

            $ajoTransaction = AjoTransaction::create([
                'ajo_id' => null,
                'ajo_member_id' => null,
                'ajo_collection_cycle_id' => null,
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'source_ajo_account_id' => null,
                'destination_ajo_account_id' => $ajoAccount->id,
                'main_account_id' => $mainAccount->id,
                'finova_transaction_id' => $coreTransaction->id,
                'company_financial_entry_id' => null,
                'type' => AjoTransaction::TYPE_DEPOSIT,
                'amount' => round($amount, 2),
                'fee' => 0,
                'company_fee_share' => 0,
                'creator_fee_share' => 0,
                'source_balance_before' => $sourceBalanceBefore,
                'source_balance_after' => $sourceBalanceAfter,
                'destination_balance_before' => $destinationBalanceBefore,
                'destination_balance_after' => $destinationBalanceAfter,
                'transaction_reference' => $reference,
                'status' => 'successful',
                'description' => 'Voluntary deposit into personal Ajo Account.',
                'metadata' => [
                    'movement' => 'main_to_ajo_account',
                    'main_account_id' => $mainAccount->id,
                    'ajo_account_id' => $ajoAccount->id,
                    'company_wallet_affected' => false,
                ],
            ]);

            return [
                'ajo_account' => $ajoAccount->fresh(),
                'ajo_transaction' => $ajoTransaction,
                'core_transaction' => $coreTransaction,
                'wallet_transaction' => $debit,
            ];
        });
    }

    /**
     * Voluntarily move money from the user's personal Ajo Account back to
     * their MAIN wallet.
     *
     * Funds already moved into an active AjoMember position are not included
     * in this balance and therefore cannot be withdrawn here.
     */
    public function withdrawFromAjoAccount($user, float $amount): array
    {
        if ($amount <= 0) {
            throw new RuntimeException(
                'Ajo Account withdrawal amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use ($user, $amount) {
            $this->validateUserForAjo($user);

            $ajoAccount = AjoAccount::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$ajoAccount) {
                throw new RuntimeException(
                    'Personal Ajo Account could not be found.'
                );
            }

            $sourceBalanceBefore = round(
                (float) $ajoAccount->balance,
                2
            );

            if ($sourceBalanceBefore <= 0) {
                throw new RuntimeException(
                    'Your Ajo Account has no available balance for withdrawal.'
                );
            }

            if ($amount > $sourceBalanceBefore) {
                throw new RuntimeException(
                    'Withdrawal amount exceeds your available Ajo Account balance.'
                );
            }

            $mainAccount = $this->getMainAccount($user);

            $sourceBalanceAfter = round(
                $sourceBalanceBefore - $amount,
                2
            );

            $ajoAccount->balance = $sourceBalanceAfter;
            $ajoAccount->save();

            $credit = creditCustomerOnly(
                $mainAccount->id,
                $amount
            );

            if (!($credit['status'] ?? false)) {
                throw new RuntimeException(
                    $credit['message']
                    ?? 'Unable to credit main account.'
                );
            }

            $reference = $this->reference('AJAW');

            $coreTransaction = $this->createCoreTransaction(
                companyId: (int) $user->company_id,
                transactionTypeId:
                    self::TRANSACTION_TYPE_AJO_ACCOUNT_WITHDRAWAL,
                creditDebitTypeId: self::CREDIT,
                accountId: (int) $mainAccount->id,
                amount: $amount,
                balanceBefore: (float) $credit['balance_before'],
                balanceAfter: (float) $credit['balance_after'],
                description: 'Ajo Account Withdrawal',
                reference: $reference
            );

            $ajoTransaction = AjoTransaction::create([
                'ajo_id' => null,
                'ajo_member_id' => null,
                'ajo_collection_cycle_id' => null,
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'source_ajo_account_id' => $ajoAccount->id,
                'destination_ajo_account_id' => null,
                'main_account_id' => $mainAccount->id,
                'finova_transaction_id' => $coreTransaction->id,
                'company_financial_entry_id' => null,
                'type' => AjoTransaction::TYPE_WITHDRAWAL,
                'amount' => round($amount, 2),
                'fee' => 0,
                'company_fee_share' => 0,
                'creator_fee_share' => 0,
                'source_balance_before' => $sourceBalanceBefore,
                'source_balance_after' => $sourceBalanceAfter,
                'destination_balance_before' =>
                    (float) $credit['balance_before'],
                'destination_balance_after' =>
                    (float) $credit['balance_after'],
                'transaction_reference' => $reference,
                'status' => 'successful',
                'description' => 'Withdrawal from personal Ajo Account.',
                'metadata' => [
                    'movement' => 'ajo_account_to_main',
                    'main_account_id' => $mainAccount->id,
                    'ajo_account_id' => $ajoAccount->id,
                    'company_wallet_affected' => false,
                ],
            ]);

            return [
                'ajo_account' => $ajoAccount->fresh(),
                'ajo_transaction' => $ajoTransaction,
                'core_transaction' => $coreTransaction,
                'wallet_transaction' => $credit,
            ];
        });
    }

    /**
     * Invite a user by username.
     *
     * Only the Ajo creator can send invitations.
     */
    public function inviteMember(
        int $ajoId,
        $creator,
        string $username
    ): AjoInvitation {
        return DB::transaction(function () use (
            $ajoId,
            $creator,
            $username
        ) {
            $ajo = $this->getAjoForUpdate($ajoId);

            $this->assertCreator($ajo, $creator);

            if ($ajo->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException(
                    'This Ajo is not accepting members.'
                );
            }

            $username = trim($username);

            if ($username === '') {
                throw new RuntimeException('Member username is required.');
            }

            $invitee = User::query()
                ->where('username', $username)
                ->first();

            if (!$invitee) {
                throw new RuntimeException(
                    'User with the supplied username could not be found.'
                );
            }

            if ((int) $invitee->id === (int) $creator->id) {
                throw new RuntimeException(
                    'The Ajo creator cannot be invited as a member.'
                );
            }

            if (
                $ajo->max_members !== null &&
                $ajo->members()
                    ->whereIn('status', [
                        AjoMember::STATUS_ACTIVE,
                        AjoMember::STATUS_LEAVE_REQUESTED,
                    ])
                    ->count() >= (int) $ajo->max_members
            ) {
                throw new RuntimeException(
                    'This Ajo has reached its maximum member capacity.'
                );
            }

            $alreadyMember = AjoMember::query()
                ->where('ajo_id', $ajo->id)
                ->where('user_id', $invitee->id)
                ->whereIn('status', [
                    AjoMember::STATUS_ACTIVE,
                    AjoMember::STATUS_LEAVE_REQUESTED,
                ])
                ->exists();

            if ($alreadyMember) {
                throw new RuntimeException(
                    'This user is already a member of the Ajo.'
                );
            }

            $pending = AjoInvitation::query()
                ->where('ajo_id', $ajo->id)
                ->where('invited_user_id', $invitee->id)
                ->where('status', AjoInvitation::STATUS_PENDING)
                ->first();

            if ($pending) {
                return $pending;
            }

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
    public function joinAjo(
        int $ajoId,
        $user,
        bool $acceptTerms = false
    ): AjoMember {
        return DB::transaction(function () use (
            $ajoId,
            $user,
            $acceptTerms
        ) {
            $this->validateUserForAjo($user);

            if (!$acceptTerms) {
                throw new RuntimeException(
                    'You must accept the Ajo terms and conditions before joining.'
                );
            }

            $ajo = $this->getAjoForUpdate($ajoId);

            if ($ajo->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException(
                    'This Ajo is not accepting members.'
                );
            }

            if ($ajo->visibility !== 'public') {
                throw new RuntimeException(
                    'This is a private Ajo. You must accept an invitation to join.'
                );
            }

            if ((int) $ajo->creator_user_id === (int) $user->id) {
                throw new RuntimeException(
                    'The Ajo creator is the overseer and cannot join as a member.'
                );
            }

            return $this->createMembership(
                $ajo,
                $user,
                $ajo->terms,
                $ajo->terms_version
            );
        });
    }

    /**
     * Accept a creator invitation.
     */
    public function acceptInvitation(
        int $invitationId,
        $user,
        bool $acceptTerms = false
    ): AjoMember {
        return DB::transaction(function () use (
            $invitationId,
            $user,
            $acceptTerms
        ) {
            $this->validateUserForAjo($user);

            if (!$acceptTerms) {
                throw new RuntimeException(
                    'You must explicitly accept the Ajo terms and conditions.'
                );
            }

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

            $ajo = $this->getAjoForUpdate($invitation->ajo_id);

            if ($ajo->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException(
                    'This Ajo is no longer accepting members.'
                );
            }

            if ((int) $ajo->creator_user_id === (int) $user->id) {
                throw new RuntimeException(
                    'The Ajo creator cannot become a member.'
                );
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
    public function approveEarlyLeave(
        int $ajoMemberId,
        $creator
    ): array {
        return DB::transaction(function () use (
            $ajoMemberId,
            $creator
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

            if ($ajo->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException(
                    'This Ajo is not active.'
                );
            }

            $memberBalance = round(
                (float) $member->balance,
                2
            );

            $memberAjoAccount = AjoAccount::query()
                ->where('id', $member->ajo_account_id)
                ->lockForUpdate()
                ->first();

            if (!$memberAjoAccount) {
                throw new RuntimeException(
                    'Member personal Ajo Account could not be found.'
                );
            }

            $creatorAjoAccount = AjoAccount::query()
                ->where('id', $ajo->creator_ajo_account_id)
                ->lockForUpdate()
                ->first();

            if (!$creatorAjoAccount) {
                throw new RuntimeException(
                    'Creator personal Ajo Account could not be found.'
                );
            }

            $penalty = $this->calculateConfiguredFee(
                $ajo->early_leave_penalty_type,
                (float) $ajo->early_leave_penalty_value,
                $memberBalance
            );

            /*
             * A member position cannot become negative.
             */
            $penalty = min($penalty, $memberBalance);

            $remaining = round(
                $memberBalance - $penalty,
                2
            );

            $penaltyTransaction = null;
            $penaltyFinancialEntry = null;

            if ($penalty > 0) {
                $split = $ajo->splitFee($penalty);

                $creatorBalanceBefore = round(
                    (float) $creatorAjoAccount->balance,
                    2
                );

                $creatorAjoAccount->balance = round(
                    $creatorBalanceBefore + $split['creator_share'],
                    2
                );
                $creatorAjoAccount->save();

                $penaltyReference = $this->reference('AJO-ELP');

                $penaltyTransaction = AjoTransaction::create([
                    'ajo_id' => $ajo->id,
                    'ajo_member_id' => $member->id,
                    'ajo_collection_cycle_id' => null,
                    'user_id' => $member->user_id,
                    'company_id' => $ajo->company_id,
                    'source_ajo_account_id' => null,
                    'destination_ajo_account_id' => $creatorAjoAccount->id,
                    'main_account_id' => null,
                    'finova_transaction_id' => null,
                    'company_financial_entry_id' => null,
                    'type' => AjoTransaction::TYPE_EARLY_LEAVE_PENALTY,
                    'amount' => $penalty,
                    'fee' => $penalty,
                    'company_fee_share' => $split['company_share'],
                    'creator_fee_share' => $split['creator_share'],
                    'source_balance_before' => $memberBalance,
                    'source_balance_after' => $remaining,
                    'destination_balance_before' => $creatorBalanceBefore,
                    'destination_balance_after' =>
                        (float) $creatorAjoAccount->balance,
                    'transaction_reference' => $penaltyReference,
                    'status' => 'successful',
                    'description' =>
                        'Ajo early-leave penalty charged to member.',
                    'metadata' => [
                        'fee_type' => 'early_leave_penalty',
                        'penalty_type' => $ajo->early_leave_penalty_type,
                        'penalty_value' =>
                            (float) $ajo->early_leave_penalty_value,
                        'total_fee' => $split['total_fee'],
                        'company_share' => $split['company_share'],
                        'creator_share' => $split['creator_share'],
                        'company_share_percentage' =>
                            $split['company_percentage'],
                        'creator_share_percentage' =>
                            $split['creator_percentage'],
                        'company_wallet_affected' => false,
                    ],
                ]);

                $penaltyFinancialEntry =
                    $this->recordCompanyRevenue(
                        ajo: $ajo,
                        amount: $split['company_share'],
                        category: self::CATEGORY_EARLY_LEAVE_PENALTY,
                        description:
                            'Ajo early-leave penalty company revenue share.',
                        reference: $this->reference('AJO-ELR'),
                        transactionId: null,
                        metadata: [
                            'ajo_id' => $ajo->id,
                            'ajo_member_id' => $member->id,
                            'fee_type' => 'early_leave_penalty',
                            'total_fee' => $split['total_fee'],
                            'company_share' => $split['company_share'],
                            'creator_share' => $split['creator_share'],
                            'company_share_percentage' =>
                                $split['company_percentage'],
                            'creator_share_percentage' =>
                                $split['creator_percentage'],
                            'ajo_transaction_id' => $penaltyTransaction->id,
                            'company_wallet_affected' => false,
                        ]
                    );

                $penaltyTransaction->company_financial_entry_id =
                    $penaltyFinancialEntry->id;
                $penaltyTransaction->save();
            }

            $memberAccountBalanceBefore = round(
                (float) $memberAjoAccount->balance,
                2
            );

            $memberAccountBalanceAfter = round(
                $memberAccountBalanceBefore + $remaining,
                2
            );

            $memberAjoAccount->balance = $memberAccountBalanceAfter;
            $memberAjoAccount->save();

            $returnReference = $this->reference('AJO-ELRETURN');

            $returnTransaction = AjoTransaction::create([
                'ajo_id' => $ajo->id,
                'ajo_member_id' => $member->id,
                'ajo_collection_cycle_id' => null,
                'user_id' => $member->user_id,
                'company_id' => $ajo->company_id,
                'source_ajo_account_id' => null,
                'destination_ajo_account_id' => $memberAjoAccount->id,
                'main_account_id' => null,
                'finova_transaction_id' => null,
                'company_financial_entry_id' => null,
                'type' => AjoTransaction::TYPE_EARLY_LEAVE_RETURN,
                'amount' => $remaining,
                'fee' => 0,
                'company_fee_share' => 0,
                'creator_fee_share' => 0,
                'source_balance_before' => $remaining,
                'source_balance_after' => 0,
                'destination_balance_before' => $memberAccountBalanceBefore,
                'destination_balance_after' => $memberAccountBalanceAfter,
                'transaction_reference' => $returnReference,
                'status' => 'successful',
                'description' =>
                    'Remaining Ajo member balance returned to personal Ajo Account.',
                'metadata' => [
                    'remaining_member_balance' => $remaining,
                    'member_ajo_account_id' => $memberAjoAccount->id,
                    'company_wallet_affected' => false,
                ],
            ]);

            $member->balance = 0;
            $member->total_early_leave_penalties = round(
                (float) $member->total_early_leave_penalties + $penalty,
                2
            );
            $member->status = AjoMember::STATUS_LEFT;
            $member->left_at = now();
            $member->early_leave_processed_at = now();
            $member->early_leave_processed_by = $creator->id;
            $member->save();

            return [
                'ajo' => $ajo->fresh(),
                'member' => $member->fresh(),
                'penalty' => $penalty,
                'remaining' => $remaining,
                'fee_split' => $penalty > 0
                    ? $split
                    : [
                        'total_fee' => 0,
                        'company_percentage' =>
                            (float) $ajo->company_fee_share_percentage,
                        'creator_percentage' =>
                            (float) $ajo->creator_fee_share_percentage,
                        'company_share' => 0,
                        'creator_share' => 0,
                    ],
                'penalty_transaction' => $penaltyTransaction,
                'return_transaction' => $returnTransaction,
                'financial_entry' => $penaltyFinancialEntry,
                'creator_ajo_account' => $creatorAjoAccount->fresh(),
                'member_ajo_account' => $memberAjoAccount->fresh(),
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
        $processed = 0;
        $failed = 0;
        $skipped = 0;

        Ajo::query()
            ->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('next_collection_at')
            ->where('next_collection_at', '<=', $asOf)
            ->whereDate('maturity_date', '>', $asOf->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($ajos) use (
                $asOf,
                &$processed,
                &$failed,
                &$skipped
            ) {
                foreach ($ajos as $ajoSnapshot) {
                    $guard = 0;

                    while ($guard < 500) {
                        $guard++;

                        $ajo = Ajo::query()
                            ->where('id', $ajoSnapshot->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$ajo) {
                            break;
                        }

                        if ($ajo->status !== self::STATUS_ACTIVE) {
                            break;
                        }

                        $scheduledFor = $ajo->next_collection_at
                            ? Carbon::parse($ajo->next_collection_at)
                            : null;

                        if (!$scheduledFor || $scheduledFor->gt($asOf)) {
                            break;
                        }

                        /*
                         * Once the maturity date arrives, processMaturity()
                         * becomes responsible for the Ajo. Do not create a
                         * new collection on/after the maturity date.
                         */
                        if ($scheduledFor->startOfDay()->gte(
                            Carbon::parse($ajo->maturity_date)->startOfDay()
                        )) {
                            break;
                        }

                        $members = AjoMember::query()
                            ->where('ajo_id', $ajo->id)
                            ->whereIn('status', [
                                AjoMember::STATUS_ACTIVE,
                                AjoMember::STATUS_LEAVE_REQUESTED,
                            ])
                            ->where('joined_at', '<=', $scheduledFor)
                            ->orderBy('id')
                            ->get();

                        foreach ($members as $memberSnapshot) {
                            try {
                                $result = $this->processSingleCollection(
                                    (int) $ajo->id,
                                    (int) $memberSnapshot->id,
                                    $scheduledFor
                                );

                                if (($result['status'] ?? null) === 'successful') {
                                    $processed++;
                                } else {
                                    $skipped++;
                                }
                            } catch (Throwable $e) {
                                $failed++;

                                $this->recordFailedCollection(
                                    ajoId: (int) $ajo->id,
                                    memberId: (int) $memberSnapshot->id,
                                    scheduledFor: $scheduledFor,
                                    error: $e
                                );
                            }
                        }

                        /*
                         * Advance exactly once for this group schedule.
                         * processSingleCollection() is idempotent against the
                         * same Ajo + member + scheduled_for combination.
                         */
                        DB::transaction(function () use ($ajo, $scheduledFor) {
                            $lockedAjo = Ajo::query()
                                ->where('id', $ajo->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$lockedAjo) {
                                return;
                            }

                            if ($lockedAjo->status !== self::STATUS_ACTIVE) {
                                return;
                            }

                            $currentNext = $lockedAjo->next_collection_at
                                ? Carbon::parse($lockedAjo->next_collection_at)
                                : null;

                            /*
                             * Only move forward if another process has not
                             * already advanced this schedule.
                             */
                            if (
                                !$currentNext ||
                                !$currentNext->equalTo($scheduledFor)
                            ) {
                                return;
                            }

                            $lockedAjo->next_collection_at =
                                $this->calculateNextCollectionAt(
                                    $lockedAjo,
                                    $scheduledFor
                                );

                            $lockedAjo->save();
                        });
                    }
                }
            });

        return [
            'processed' => $processed,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    /**
     * Process one member's collection for one specific schedule timestamp.
     *
     * This is intentionally idempotent. A scheduler can safely run multiple
     * times without collecting the same member twice for the same timestamp.
     */
    protected function processSingleCollection(
        int $ajoId,
        int $memberId,
        Carbon $scheduledFor
    ): array {
        return DB::transaction(function () use (
            $ajoId,
            $memberId,
            $scheduledFor
        ) {
            $ajo = Ajo::query()
                ->where('id', $ajoId)
                ->lockForUpdate()
                ->first();

            if (!$ajo || $ajo->status !== self::STATUS_ACTIVE) {
                return ['status' => 'skipped'];
            }

            $member = AjoMember::query()
                ->where('id', $memberId)
                ->where('ajo_id', $ajoId)
                ->lockForUpdate()
                ->first();

            if (!$member) {
                return ['status' => 'skipped'];
            }

            if (!in_array($member->status, [
                AjoMember::STATUS_ACTIVE,
                AjoMember::STATUS_LEAVE_REQUESTED,
            ], true)) {
                return ['status' => 'skipped'];
            }

            if (
                !$member->joined_at ||
                Carbon::parse($member->joined_at)->gt($scheduledFor)
            ) {
                return ['status' => 'skipped'];
            }

            $existingCycle = AjoCollectionCycle::query()
                ->where('ajo_id', $ajo->id)
                ->where('ajo_member_id', $member->id)
                ->where('scheduled_for', $scheduledFor)
                ->lockForUpdate()
                ->first();

            if ($existingCycle) {
                return [
                    'status' => $existingCycle->status ===
                        AjoCollectionCycle::STATUS_SUCCESSFUL
                        ? 'successful'
                        : 'skipped',
                    'collection_cycle' => $existingCycle,
                ];
            }

            $memberAjoAccount = AjoAccount::query()
                ->where('id', $member->ajo_account_id)
                ->lockForUpdate()
                ->first();

            if (!$memberAjoAccount) {
                throw new RuntimeException(
                    'Member personal Ajo Account could not be found.'
                );
            }

            $sourceBalanceBefore = round(
                (float) $memberAjoAccount->balance,
                2
            );

            $collectionAmount = round(
                (float) $ajo->collection_amount,
                2
            );

            if ($collectionAmount <= 0) {
                throw new RuntimeException(
                    'Ajo collection amount is invalid.'
                );
            }

            $sourceBalanceAfterCollection = round(
                $sourceBalanceBefore - $collectionAmount,
                2
            );

            $memberBalanceBefore = round(
                (float) $member->balance,
                2
            );

            $memberBalanceAfter = round(
                $memberBalanceBefore + $collectionAmount,
                2
            );

            $insufficient = $sourceBalanceBefore < $collectionAmount;

            $newInsufficientCount = (int) $member->insufficient_balance_count;

            if ($insufficient) {
                $newInsufficientCount++;
            }

            $cycleNumber = (int) (
                AjoCollectionCycle::query()
                    ->where('ajo_member_id', $member->id)
                    ->max('cycle_number')
                ?? 0
            ) + 1;

            $collectionReference = $this->reference('AJCOL');

            $memberAjoAccount->balance = $sourceBalanceAfterCollection;
            $memberAjoAccount->save();

            $member->balance = $memberBalanceAfter;
            $member->total_contributed = round(
                (float) $member->total_contributed + $collectionAmount,
                2
            );
            $member->insufficient_balance_count = $newInsufficientCount;
            $member->save();

            $cycle = AjoCollectionCycle::create([
                'ajo_id' => $ajo->id,
                'ajo_member_id' => $member->id,
                'cycle_number' => $cycleNumber,
                'scheduled_for' => $scheduledFor,
                'processed_at' => now(),
                'amount' => $collectionAmount,
                'source_balance_before' => $sourceBalanceBefore,
                'source_balance_after' => $sourceBalanceAfterCollection,
                'member_balance_before' => $memberBalanceBefore,
                'member_balance_after' => $memberBalanceAfter,
                'insufficient_balance' => $insufficient,
                'insufficient_balance_count' => $newInsufficientCount,
                'fee_amount' => 0,
                'company_fee_share' => 0,
                'creator_fee_share' => 0,
                'status' => AjoCollectionCycle::STATUS_SUCCESSFUL,
                'failure_reason' => null,
                'transaction_reference' => $collectionReference,
                'metadata' => [
                    'scheduled_for' => $scheduledFor->toIso8601String(),
                    'collection_amount' => $collectionAmount,
                    'source_ajo_account_id' => $memberAjoAccount->id,
                    'insufficient_balance' => $insufficient,
                ],
            ]);

            $collectionTransaction = AjoTransaction::create([
                'ajo_id' => $ajo->id,
                'ajo_member_id' => $member->id,
                'ajo_collection_cycle_id' => $cycle->id,
                'user_id' => $member->user_id,
                'company_id' => $ajo->company_id,
                'source_ajo_account_id' => $memberAjoAccount->id,
                'destination_ajo_account_id' => null,
                'main_account_id' => null,
                'finova_transaction_id' => null,
                'company_financial_entry_id' => null,
                'type' => AjoTransaction::TYPE_SCHEDULED_COLLECTION,
                'amount' => $collectionAmount,
                'fee' => 0,
                'company_fee_share' => 0,
                'creator_fee_share' => 0,
                'source_balance_before' => $sourceBalanceBefore,
                'source_balance_after' => $sourceBalanceAfterCollection,
                'destination_balance_before' => $memberBalanceBefore,
                'destination_balance_after' => $memberBalanceAfter,
                'transaction_reference' => $collectionReference,
                'status' => 'successful',
                'description' =>
                    'Automatic scheduled collection into Ajo membership.',
                'metadata' => [
                    'collection_cycle_id' => $cycle->id,
                    'scheduled_for' => $scheduledFor->toIso8601String(),
                    'insufficient_balance' => $insufficient,
                    'insufficient_balance_count' => $newInsufficientCount,
                    'source_ajo_account_id' => $memberAjoAccount->id,
                    'company_wallet_affected' => false,
                ],
            ]);

            $cycle->metadata = array_merge(
                (array) $cycle->metadata,
                [
                    'ajo_transaction_id' => $collectionTransaction->id,
                ]
            );
            $cycle->save();

            $insufficientFee = 0.0;
            $feeSplit = [
                'total_fee' => 0,
                'company_percentage' =>
                    (float) $ajo->company_fee_share_percentage,
                'creator_percentage' =>
                    (float) $ajo->creator_fee_share_percentage,
                'company_share' => 0,
                'creator_share' => 0,
            ];

            $feeTransaction = null;
            $feeFinancialEntry = null;

            /*
             * Every third insufficient event creates the configured fee:
             * 3rd, 6th, 9th, ...
             */
            if (
                $insufficient &&
                $newInsufficientCount > 0 &&
                $newInsufficientCount % self::INSUFFICIENT_BALANCE_FEE_EVERY === 0
            ) {
                $insufficientFee = $this->calculateConfiguredFee(
                    $ajo->insufficient_balance_fee_type,
                    (float) $ajo->insufficient_balance_fee_value,
                    $collectionAmount
                );

                if ($insufficientFee > 0) {
                    $feeSplit = $ajo->splitFee($insufficientFee);

                    /*
                     * The insufficient-balance fee is also charged to the
                     * personal Ajo Account, so that account can become even
                     * more negative.
                     */
                    $feeSourceBefore = round(
                        (float) $memberAjoAccount->balance,
                        2
                    );

                    $feeSourceAfter = round(
                        $feeSourceBefore - $insufficientFee,
                        2
                    );

                    $memberAjoAccount->balance = $feeSourceAfter;
                    $memberAjoAccount->save();

                    $creatorAjoAccount = AjoAccount::query()
                        ->where('id', $ajo->creator_ajo_account_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$creatorAjoAccount) {
                        throw new RuntimeException(
                            'Creator personal Ajo Account could not be found.'
                        );
                    }

                    $creatorBalanceBefore = round(
                        (float) $creatorAjoAccount->balance,
                        2
                    );

                    $creatorBalanceAfter = round(
                        $creatorBalanceBefore + $feeSplit['creator_share'],
                        2
                    );

                    $creatorAjoAccount->balance = $creatorBalanceAfter;
                    $creatorAjoAccount->save();

                    $feeReference = $this->reference('AJIBF');

                    $feeTransaction = AjoTransaction::create([
                        'ajo_id' => $ajo->id,
                        'ajo_member_id' => $member->id,
                        'ajo_collection_cycle_id' => $cycle->id,
                        'user_id' => $member->user_id,
                        'company_id' => $ajo->company_id,
                        'source_ajo_account_id' => $memberAjoAccount->id,
                        'destination_ajo_account_id' =>
                            $creatorAjoAccount->id,
                        'main_account_id' => null,
                        'finova_transaction_id' => null,
                        'company_financial_entry_id' => null,
                        'type' => AjoTransaction::TYPE_INSUFFICIENT_BALANCE_FEE,
                        'amount' => $insufficientFee,
                        'fee' => $insufficientFee,
                        'company_fee_share' => $feeSplit['company_share'],
                        'creator_fee_share' => $feeSplit['creator_share'],
                        'source_balance_before' => $feeSourceBefore,
                        'source_balance_after' => $feeSourceAfter,
                        'destination_balance_before' =>
                            $creatorBalanceBefore,
                        'destination_balance_after' =>
                            $creatorBalanceAfter,
                        'transaction_reference' => $feeReference,
                        'status' => 'successful',
                        'description' =>
                            'Ajo insufficient-balance fee charged on threshold event.',
                        'metadata' => [
                            'fee_type' => 'insufficient_balance_fee',
                            'threshold' => self::INSUFFICIENT_BALANCE_FEE_EVERY,
                            'insufficient_balance_count' => $newInsufficientCount,
                            'fee_calculation_type' =>
                                $ajo->insufficient_balance_fee_type,
                            'configured_fee_value' =>
                                (float) $ajo->insufficient_balance_fee_value,
                            'calculation_base' => $collectionAmount,
                            'total_fee' => $feeSplit['total_fee'],
                            'company_share' => $feeSplit['company_share'],
                            'creator_share' => $feeSplit['creator_share'],
                            'company_share_percentage' =>
                                $feeSplit['company_percentage'],
                            'creator_share_percentage' =>
                                $feeSplit['creator_percentage'],
                            'company_wallet_affected' => false,
                        ],
                    ]);

                    $feeFinancialEntry =
                        $this->recordCompanyRevenue(
                            ajo: $ajo,
                            amount: $feeSplit['company_share'],
                            category: self::CATEGORY_INSUFFICIENT_BALANCE_FEE,
                            description:
                                'Ajo insufficient-balance fee company revenue share.',
                            reference: $this->reference('AJO-IBFR'),
                            transactionId: null,
                            metadata: [
                                'ajo_id' => $ajo->id,
                                'ajo_member_id' => $member->id,
                                'collection_cycle_id' => $cycle->id,
                                'fee_type' => 'insufficient_balance_fee',
                                'insufficient_balance_count' => $newInsufficientCount,
                                'total_fee' => $feeSplit['total_fee'],
                                'company_share' => $feeSplit['company_share'],
                                'creator_share' => $feeSplit['creator_share'],
                                'company_share_percentage' =>
                                    $feeSplit['company_percentage'],
                                'creator_share_percentage' =>
                                    $feeSplit['creator_percentage'],
                                'ajo_transaction_id' => $feeTransaction->id,
                                'company_wallet_affected' => false,
                            ]
                        );

                    $feeTransaction->company_financial_entry_id =
                        $feeFinancialEntry->id;
                    $feeTransaction->save();

                    $cycle->fee_amount = $insufficientFee;
                    $cycle->company_fee_share =
                        $feeSplit['company_share'];
                    $cycle->creator_fee_share =
                        $feeSplit['creator_share'];
                    $cycle->source_balance_after = $feeSourceAfter;
                    $cycle->metadata = array_merge(
                        (array) $cycle->metadata,
                        [
                            'insufficient_fee_transaction_id' =>
                                $feeTransaction->id,
                            'company_financial_entry_id' =>
                                $feeFinancialEntry->id,
                        ]
                    );
                    $cycle->save();
                }
            }

            return [
                'status' => 'successful',
                'ajo' => $ajo->fresh(),
                'member' => $member->fresh(),
                'collection_cycle' => $cycle->fresh(),
                'collection_transaction' => $collectionTransaction,
                'fee_transaction' => $feeTransaction,
                'financial_entry' => $feeFinancialEntry,
                'fee' => $insufficientFee,
                'fee_split' => $feeSplit,
                'ajo_account' => $memberAjoAccount->fresh(),
            ];
        });
    }

    /**
     * Record a failed collection attempt without mutating balances.
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
                'amount' => round((float) $ajo->collection_amount, 2),
                'source_balance_before' =>
                    $member->ajoAccount
                    ? (float) $member->ajoAccount->balance
                    : 0,
                'source_balance_after' =>
                    $member->ajoAccount
                    ? (float) $member->ajoAccount->balance
                    : 0,
                'member_balance_before' =>
                    (float) $member->balance,
                'member_balance_after' =>
                    (float) $member->balance,
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

    $processed = 0;
    $failed = 0;
    $skipped = 0;

    Ajo::query()
        ->where('status', self::STATUS_ACTIVE)
        ->whereDate(
            'maturity_date',
            '<=',
            $asOf->toDateString()
        )
        ->orderBy('id')
        ->chunkById(
            100,
            function ($ajos) use (
                &$processed,
                &$failed,
                &$skipped,
                $asOf
            ) {
                foreach ($ajos as $ajoSnapshot) {
                    try {
                        $result = DB::transaction(function () use ($ajoSnapshot, $asOf) {
                            $ajo = Ajo::query()
                                ->where('id', $ajoSnapshot->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$ajo) {
                                return ['status' => 'skipped'];
                            }

                            if ($ajo->status !== self::STATUS_ACTIVE) {
                                return ['status' => 'skipped'];
                            }

                            $maturityDate = Carbon::parse(
                                $ajo->maturity_date
                            )->startOfDay();

                            if ($asOf->copy()->startOfDay()->lt($maturityDate)) {
                                return ['status' => 'skipped'];
                            }

                            $creatorAjoAccount = AjoAccount::query()
                                ->where('id', $ajo->creator_ajo_account_id)
                                ->lockForUpdate()
                                ->first();

                            if (!$creatorAjoAccount) {
                                throw new RuntimeException(
                                    'Creator personal Ajo Account could not be found.'
                                );
                            }

                            $members = AjoMember::query()
                                ->where('ajo_id', $ajo->id)
                                ->whereIn('status', [
                                    AjoMember::STATUS_ACTIVE,
                                    AjoMember::STATUS_LEAVE_REQUESTED,
                                ])
                                ->orderBy('id')
                                ->lockForUpdate()
                                ->get();

                            $memberResults = [];

                            foreach ($members as $member) {
                                $memberAjoAccount = AjoAccount::query()
                                    ->where('id', $member->ajo_account_id)
                                    ->lockForUpdate()
                                    ->first();

                                if (!$memberAjoAccount) {
                                    throw new RuntimeException(
                                        'Member personal Ajo Account could not be found.'
                                    );
                                }

                                $memberBalance = round(
                                    (float) $member->balance,
                                    2
                                );

                                $fee = round(
                                    $memberBalance
                                    * (
                                        (float)
                                        $ajo->maturity_fee_percentage
                                        / 100
                                    ),
                                    2
                                );

                                $fee = min($fee, $memberBalance);

                                $remaining = round(
                                    $memberBalance - $fee,
                                    2
                                );

                                $feeSplit = [
                                    'total_fee' => 0,
                                    'company_percentage' =>
                                        (float) $ajo->company_fee_share_percentage,
                                    'creator_percentage' =>
                                        (float) $ajo->creator_fee_share_percentage,
                                    'company_share' => 0,
                                    'creator_share' => 0,
                                ];

                                $feeTransaction = null;
                                $financialEntry = null;

                                if ($fee > 0) {
                                    $feeSplit = $ajo->splitFee($fee);

                                    $creatorBalanceBefore = round(
                                        (float) $creatorAjoAccount->balance,
                                        2
                                    );

                                    $creatorBalanceAfter = round(
                                        $creatorBalanceBefore
                                        + $feeSplit['creator_share'],
                                        2
                                    );

                                    $creatorAjoAccount->balance =
                                        $creatorBalanceAfter;
                                    $creatorAjoAccount->save();

                                    $feeReference = $this->reference('AJMFEE');

                                    $feeTransaction = AjoTransaction::create([
                                        'ajo_id' => $ajo->id,
                                        'ajo_member_id' => $member->id,
                                        'ajo_collection_cycle_id' => null,
                                        'user_id' => $member->user_id,
                                        'company_id' => $ajo->company_id,
                                        'source_ajo_account_id' => null,
                                        'destination_ajo_account_id' =>
                                            $creatorAjoAccount->id,
                                        'main_account_id' => null,
                                        'finova_transaction_id' => null,
                                        'company_financial_entry_id' => null,
                                        'type' => AjoTransaction::TYPE_MATURITY_FEE,
                                        'amount' => $fee,
                                        'fee' => $fee,
                                        'company_fee_share' =>
                                            $feeSplit['company_share'],
                                        'creator_fee_share' =>
                                            $feeSplit['creator_share'],
                                        'source_balance_before' =>
                                            $memberBalance,
                                        'source_balance_after' => $remaining,
                                        'destination_balance_before' =>
                                            $creatorBalanceBefore,
                                        'destination_balance_after' =>
                                            $creatorBalanceAfter,
                                        'transaction_reference' => $feeReference,
                                        'status' => 'successful',
                                        'description' =>
                                            'Ajo maturity fee charged to member.',
                                        'metadata' => [
                                            'fee_type' => 'maturity_fee',
                                            'maturity_fee_percentage' =>
                                                (float)
                                                $ajo->maturity_fee_percentage,
                                            'total_fee' => $feeSplit['total_fee'],
                                            'company_share' =>
                                                $feeSplit['company_share'],
                                            'creator_share' =>
                                                $feeSplit['creator_share'],
                                            'company_share_percentage' =>
                                                $feeSplit['company_percentage'],
                                            'creator_share_percentage' =>
                                                $feeSplit['creator_percentage'],
                                            'company_wallet_affected' => false,
                                        ],
                                    ]);

                                    $financialEntry =
                                        $this->recordCompanyRevenue(
                                            ajo: $ajo,
                                            amount: $feeSplit['company_share'],
                                            category: self::CATEGORY_MATURITY_FEE,
                                            description:
                                                'Ajo maturity fee company revenue share.',
                                            reference: $this->reference('AJMFR'),
                                            transactionId: null,
                                            metadata: [
                                                'ajo_id' => $ajo->id,
                                                'ajo_member_id' => $member->id,
                                                'fee_type' => 'maturity_fee',
                                                'maturity_fee_percentage' =>
                                                    (float)
                                                    $ajo->maturity_fee_percentage,
                                                'total_fee' =>
                                                    $feeSplit['total_fee'],
                                                'company_share' =>
                                                    $feeSplit['company_share'],
                                                'creator_share' =>
                                                    $feeSplit['creator_share'],
                                                'company_share_percentage' =>
                                                    $feeSplit['company_percentage'],
                                                'creator_share_percentage' =>
                                                    $feeSplit['creator_percentage'],
                                                'ajo_transaction_id' =>
                                                    $feeTransaction->id,
                                                'company_wallet_affected' => false,
                                            ]
                                        );

                                    $feeTransaction->company_financial_entry_id =
                                        $financialEntry->id;
                                    $feeTransaction->save();
                                }

                                $memberAccountBefore = round(
                                    (float) $memberAjoAccount->balance,
                                    2
                                );

                                $memberAccountAfter = round(
                                    $memberAccountBefore + $remaining,
                                    2
                                );

                                $memberAjoAccount->balance = $memberAccountAfter;
                                $memberAjoAccount->save();

                                $returnReference = $this->reference('AJMRETURN');

                                $returnTransaction = AjoTransaction::create([
                                    'ajo_id' => $ajo->id,
                                    'ajo_member_id' => $member->id,
                                    'ajo_collection_cycle_id' => null,
                                    'user_id' => $member->user_id,
                                    'company_id' => $ajo->company_id,
                                    'source_ajo_account_id' => null,
                                    'destination_ajo_account_id' =>
                                        $memberAjoAccount->id,
                                    'main_account_id' => null,
                                    'finova_transaction_id' => null,
                                    'company_financial_entry_id' => null,
                                    'type' => AjoTransaction::TYPE_MATURITY_RETURN,
                                    'amount' => $remaining,
                                    'fee' => 0,
                                    'company_fee_share' => 0,
                                    'creator_fee_share' => 0,
                                    'source_balance_before' => $remaining,
                                    'source_balance_after' => 0,
                                    'destination_balance_before' =>
                                        $memberAccountBefore,
                                    'destination_balance_after' =>
                                        $memberAccountAfter,
                                    'transaction_reference' => $returnReference,
                                    'status' => 'successful',
                                    'description' =>
                                        'Ajo matured and remaining member funds returned to personal Ajo Account.',
                                    'metadata' => [
                                        'gross_member_balance' => $memberBalance,
                                        'maturity_fee' => $fee,
                                        'remaining_amount' => $remaining,
                                        'member_ajo_account_id' =>
                                            $memberAjoAccount->id,
                                        'company_wallet_affected' => false,
                                    ],
                                ]);

                                $member->balance = 0;
                                $member->total_maturity_fees = round(
                                    (float) $member->total_maturity_fees + $fee,
                                    2
                                );
                                $member->status = AjoMember::STATUS_MATURED;
                                $member->left_at = null;
                                $member->save();

                                $memberResults[] = [
                                    'member_id' => $member->id,
                                    'gross_balance' => $memberBalance,
                                    'maturity_fee' => $fee,
                                    'remaining' => $remaining,
                                    'fee_split' => $feeSplit,
                                    'fee_transaction' => $feeTransaction,
                                    'return_transaction' => $returnTransaction,
                                    'financial_entry' => $financialEntry,
                                ];
                            }

                            $ajo->status = self::STATUS_MATURED;
                            $ajo->next_collection_at = null;
                            $ajo->save();

                            return [
                                'status' => 'successful',
                                'ajo' => $ajo->fresh(),
                                'members' => $memberResults,
                                'creator_ajo_account' =>
                                    $creatorAjoAccount->fresh(),
                            ];
                        });

                        if (($result['status'] ?? null) === 'successful') {
                            $processed++;
                        } else {
                            $skipped++;
                        }
                    } catch (Throwable $e) {
                        $failed++;

                        logInfo([
                            'ajo_id' => $ajoSnapshot->id,
                            'error' => $e->getMessage(),
                            'line' => $e->getLine(),
                        ], 'Ajo maturity processing error');
                    }
                }
            });

        return [
            'processed' => $processed,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
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
    protected function createMembership(
        Ajo $ajo,
        $user,
        ?string $termsSnapshot,
        ?string $termsVersion
    ): AjoMember {
        if ((int) $ajo->creator_user_id === (int) $user->id) {
            throw new RuntimeException(
                'The Ajo creator is the overseer and cannot be a member.'
            );
        }

        $existing = AjoMember::query()
            ->where('ajo_id', $ajo->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [
                AjoMember::STATUS_ACTIVE,
                AjoMember::STATUS_LEAVE_REQUESTED,
            ])
            ->first();

        if ($existing) {
            throw new RuntimeException(
                'You are already a member of this Ajo.'
            );
        }

        $activeMembers = AjoMember::query()
            ->where('ajo_id', $ajo->id)
            ->whereIn('status', [
                AjoMember::STATUS_ACTIVE,
                AjoMember::STATUS_LEAVE_REQUESTED,
            ])
            ->count();

        if (
            $ajo->max_members !== null &&
            $activeMembers >= (int) $ajo->max_members
        ) {
            throw new RuntimeException(
                'This Ajo has reached its maximum member capacity.'
            );
        }

        $ajoAccount = $this->ensureAjoAccount($user);

        return AjoMember::create([
            'ajo_id' => $ajo->id,
            'ajo_account_id' => $ajoAccount->id,
            'user_id' => $user->id,
            'balance' => 0,
            'total_contributed' => 0,
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
        $lines = [];

        $lines[] = 'AJO TERMS AND CONDITIONS';
        $lines[] = 'Ajo Name: ' . $name;
        $lines[] = '';

        if ($description) {
            $lines[] = 'Description: ' . trim($description);
            $lines[] = '';
        }

        $lines[] = '1. ROLE OF CREATOR';
        $lines[] = 'The Ajo creator is the overseer of the Ajo and is NOT an Ajo member.';
        $lines[] = 'The creator is therefore not subject to the scheduled member collections or the maturity fee charged to members.';
        $lines[] = '';

        $lines[] = '2. PERSONAL AJO ACCOUNT';
        $lines[] = 'Every user has a separate personal Ajo Account.';
        $lines[] = 'Users voluntarily fund their personal Ajo Account from their main account and may withdraw available funds from it back to their main account.';
        $lines[] = 'Money already moved into an active Ajo member position is not part of the personal Ajo Account balance and is not available for ordinary Ajo Account withdrawal.';
        $lines[] = '';

        $lines[] = '3. SCHEDULED COLLECTION';
        $lines[] = 'Collection amount: ₦' . number_format($collectionAmount, 2);
        $lines[] = 'Collection frequency: ' . ucfirst($collectionFrequency);
        $lines[] = 'Collection interval: every ' . $collectionInterval . ' ' . $collectionFrequency . ($collectionInterval === 1 ? '' : 's') . '.';
        $lines[] = 'Collection time: ' . $collectionTime;
        $lines[] = 'Start date: ' . $startDate->toDateString();
        $lines[] = 'Maturity date: ' . $maturityDate->toDateString();

        if ($collectionDay !== null) {
            $dayDescription = $collectionFrequency === 'weekly'
                ? $this->weekdayLabel($collectionDay)
                : 'day ' . $collectionDay . ' of the month';

            $lines[] = 'Scheduled collection day: ' . $dayDescription . '.';
        }

        $lines[] = 'Scheduled collections are taken from the member\'s personal Ajo Account, NOT from the member\'s main wallet.';
        $lines[] = 'If the member\'s personal Ajo Account does not have enough funds, the full scheduled amount is still collected and the personal Ajo Account may become negative.';
        $lines[] = '';

        $lines[] = '4. INSUFFICIENT-BALANCE FEE';
        $lines[] = 'Every 3rd insufficient-balance event (3rd, 6th, 9th, and so on) attracts an insufficient-balance fee.';
        $lines[] = 'Fee configuration: ' . $this->feeLabel($insufficientFeeType, $insufficientFeeValue, $collectionAmount) . '.';
        $lines[] = '';

        $lines[] = '5. MATURITY FEE';
        $lines[] = 'At maturity, each member pays a maturity fee of ' . number_format($maturityFeePercentage, 4) . '% of that member\'s Ajo balance.';
        $lines[] = 'The creator is not a member and therefore does not pay or receive a maturity payout as a member.';
        $lines[] = 'After the maturity fee, the remaining member balance is credited to the member\'s personal Ajo Account, not the member\'s main wallet.';
        $lines[] = '';

        $lines[] = '6. EARLY LEAVE';
        $lines[] = 'A member may request to leave before maturity, but the creator must approve the request.';
        $lines[] = 'Early-leave penalty: ' . $this->feeLabel($earlyLeavePenaltyType, $earlyLeavePenaltyValue, null) . '.';
        $lines[] = 'The penalty is deducted from the member\'s Ajo position.';
        $lines[] = 'After the penalty is deducted, the remaining balance is returned to the member\'s personal Ajo Account.';
        $lines[] = '';

        $lines[] = '7. FEE SHARING';
        $lines[] = 'Insufficient-balance fees, maturity fees and early-leave penalties are shared as follows:';
        $lines[] = 'Company: 40% of the applicable fee.';
        $lines[] = 'Ajo Creator: 60% of the applicable fee.';
        $lines[] = 'The creator\'s 60% share is credited to the creator\'s personal Ajo Account.';
        $lines[] = 'The company\'s 40% share is recognized as company revenue.';
        $lines[] = 'The Ajo creation fee is different: 100% of the creation fee belongs to the company and 0% belongs to the creator.';
        $lines[] = '';

        $lines[] = '8. CREATION FEE';
        $lines[] = 'Ajo creation fee: ₦' . number_format($creationFee, 2) . '.';
        $lines[] = 'This fee is paid by the creator from the creator\'s main account when the Ajo is created.';
        $lines[] = '100% of the creation fee belongs to the company.';
        $lines[] = '';

        $lines[] = '9. VISIBILITY';
        $lines[] = 'Ajo visibility: ' . ucfirst($visibility) . '.';

        if ($maxMembers !== null) {
            $lines[] = 'Maximum members: ' . $maxMembers . '.';
        } else {
            $lines[] = 'Maximum members: No fixed maximum configured.';
        }

        $lines[] = '';
        $lines[] = '10. MEMBER ACCEPTANCE';
        $lines[] = 'By joining the Ajo, a member confirms that they have reviewed and accepted these terms, including the collection schedule, the treatment of insufficient balances, all applicable fees, and the 40% company / 60% creator fee-sharing arrangement.';

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
            . '% of the member\'s Ajo balance';
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
