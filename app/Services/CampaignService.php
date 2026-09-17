<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignTransaction;
use App\Models\CompanyFinancialEntry;
use App\Models\transactions;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CampaignService
{
    /**
     * Transaction type IDs for Campaigns.
     *
     * IMPORTANT:
     * Confirm these IDs are unused in your transaction_types table
     * before deploying to production.
     *
     * 129 = Campaign Deposit
     * 130 = Campaign Withdrawal
     */
    private const TRANSACTION_TYPE_DEPOSIT = 129;
    private const TRANSACTION_TYPE_WITHDRAWAL = 130;

    /**
     * Credit / debit type IDs used by Finova.
     *
     * 1 = Credit
     * 2 = Debit
     */
    private const CREDIT = 1;
    private const DEBIT = 2;

    /**
     * Financial entry types.
     */
    private const FINANCIAL_REVENUE = 'revenue';
    private const FINANCIAL_EXPENSE = 'expense';

    /**
     * Financial categories.
     */
    private const CATEGORY_CAMPAIGN_WITHDRAWAL_FEE =
        'campaign_withdrawal_fee';

    /**
     * Campaign statuses.
     */
    private const STATUS_ACTIVE = 'active';
    private const STATUS_CLOSED = 'closed';

    /**
     * Create a new Campaign.
     *
     * The creator's account number is supplied when the Campaign
     * is created.
     *
     * The account must belong to the authenticated creator.
     *
     * The account number is stored on the Campaign and the internal
     * wb_account_details ID is also stored for future wallet credits.
     */
    public function createCampaign(
        $user,
        string $campaignName,
        float $targetAmount,
        string $creatorAccountNumber,
        ?string $description = null
    ): Campaign {
        if ($targetAmount <= 0) {
            throw new RuntimeException(
                'Campaign target amount must be greater than zero.'
            );
        }

        $creatorAccountNumber = trim($creatorAccountNumber);

        if ($creatorAccountNumber === '') {
            throw new RuntimeException(
                'Creator account number is required.'
            );
        }

        return DB::transaction(function () use (
            $user,
            $campaignName,
            $targetAmount,
            $creatorAccountNumber,
            $description
        ) {

            if (empty($user->company_id)) {
                throw new RuntimeException(
                    'Your account is not linked to a company.'
                );
            }

            /*
             * Resolve company settings.
             */
            $companySettings = $user->company->setting;

            if (!$companySettings) {
                throw new RuntimeException(
                    'Company settings could not be found.'
                );
            }

            /*
             * Campaign fee is snapshotted at creation time.
             *
             * This means changing the company's Campaign fee later
             * will only affect newly-created Campaigns.
             */
            $feePercentage = round(
                (float) (
                    $companySettings->campaign_fee_percentage
                    ?? 0
                ),
                4
            );

            if ($feePercentage < 0 || $feePercentage > 100) {
                throw new RuntimeException(
                    'Invalid Campaign fee percentage configured for this company.'
                );
            }

            /*
             * Resolve the creator's account using the supplied
             * account number.
             *
             * IMPORTANT:
             * This assumes wb_account_details has an
             * account_number column.
             */
            $account = wb_account_details::query()
                ->where(
                    'account_number',
                    $creatorAccountNumber
                )
                ->where(
                    'user_id',
                    $user->id
                )
                ->first();

            if (!$account) {
                throw new RuntimeException(
                    'The supplied creator account number does not belong to you.'
                );
            }

            /*
             * Create Campaign.
             */
            return Campaign::create([
                'user_id' =>
                    $user->id,

                'company_id' =>
                    $user->company_id,

                /*
                 * Internal account ID used for future wallet credits.
                 */
                'creator_account_id' =>
                    $account->id,

                /*
                 * Original account number supplied during creation.
                 */
                'creator_account_number' =>
                    $creatorAccountNumber,

                'campaign_name' =>
                    trim($campaignName),

                'description' =>
                    $description
                        ? trim($description)
                        : null,

                /*
                 * This is only a benchmark.
                 *
                 * It does NOT restrict contributions.
                 */
                'target_amount' =>
                    round($targetAmount, 2),

                /*
                 * No contributions yet.
                 */
                'total_contributed' =>
                    0,

                'withdrawable_amount' =>
                    0,

                /*
                 * Snapshot company fee.
                 */
                'campaign_fee_percentage' =>
                    $feePercentage,

                'status' =>
                    self::STATUS_ACTIVE,

                'closed_at' =>
                    null,

                'last_withdrawn_at' =>
                    null,
            ]);
        });
    }

    /**
     * Deposit money into a Campaign.
     *
     * Any eligible user can contribute while the Campaign
     * is active.
     *
     * There is NO maximum based on target_amount.
     *
     * Example:
     *
     * Target = ₦100,000
     * Current = ₦95,000
     * Deposit = ₦20,000
     *
     * Result:
     * Current = ₦115,000
     *
     * The target is only a benchmark.
     */
    public function deposit(
        int $campaignId,
        $user,
        float $amount
    ): array {
        if ($amount <= 0) {
            throw new RuntimeException(
                'Campaign contribution amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $campaignId,
            $user,
            $amount
        ) {

            /*
             * Lock the Campaign row.
             */
            $campaign = Campaign::query()
                ->where('id', $campaignId)
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                throw new RuntimeException(
                    'Campaign not found.'
                );
            }

            /*
             * Deposits are only allowed while active.
             */
            if ($campaign->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException(
                    'This Campaign is closed and no longer accepts contributions.'
                );
            }

            /*
             * The contributor's first account/wallet is used.
             *
             * This follows the existing Target Savings account
             * resolution pattern.
             */
            $account = $user->wb_account_details()
                ->orderBy('id')
                ->first();

            if (!$account) {
                throw new RuntimeException(
                    'Customer Savings account could not be found.'
                );
            }

            $accountId = $account->id;

            /*
             * Debit the contributor's actual wallet.
             */
            $debit = debitCustomerOnly(
                $accountId,
                $amount,
                $user
            );

            if (!($debit['status'] ?? false)) {
                throw new RuntimeException(
                    $debit['message']
                    ?? 'Unable to debit contributor wallet.'
                );
            }

            /*
             * Campaign balance before contribution.
             */
            $balanceBefore = round(
                (float) $campaign->withdrawable_amount,
                2
            );

            /*
             * Campaign balance after contribution.
             */
            $balanceAfter = round(
                $balanceBefore + $amount,
                2
            );

            /*
             * Lifetime amount ever contributed.
             */
            $totalContributed = round(
                (float) $campaign->total_contributed
                + $amount,
                2
            );

            /*
             * Update Campaign.
             */
            $campaign->withdrawable_amount =
                $balanceAfter;

            $campaign->total_contributed =
                $totalContributed;

            $campaign->save();

            /*
             * Campaign product ledger.
             */
            $transactionReference =
                'CPD-' . strtoupper(Str::random(20));

            $campaignTransaction =
                CampaignTransaction::create([
                    'campaign_id' =>
                        $campaign->id,

                    /*
                     * User who actually contributed.
                     */
                    'user_id' =>
                        $user->id,

                    /*
                     * Company behind the Campaign creator.
                     *
                     * This keeps Campaign financial reporting
                     * attached to the Campaign's company.
                     */
                    'company_id' =>
                        $campaign->company_id,

                    'type' =>
                        'deposit',

                    'amount' =>
                        round($amount, 2),

                    'fee' =>
                        0,

                    'balance_before' =>
                        $balanceBefore,

                    'balance_after' =>
                        $balanceAfter,

                    'transaction_reference' =>
                        $transactionReference,

                    'status' =>
                        'successful',

                    'description' =>
                        'Contribution to Campaign',

                    'metadata' => [
                        'contributor_user_id' =>
                            $user->id,

                        'contributor_company_id' =>
                            $user->company_id,

                        'source_account_id' =>
                            $accountId,

                        'target_amount' =>
                            round(
                                (float)
                                $campaign->target_amount,
                                2
                            ),

                        'target_reached' =>
                            $balanceAfter >=
                            (float)
                            $campaign->target_amount,
                    ],
                ]);

            /*
             * Record the contributor's actual wallet movement
             * in the main Finova transactions table.
             *
             * The transaction company is the contributor's own
             * company because this is their wallet being debited.
             */
            $coreTransaction = $this->createCoreTransaction(
                campaign:
                    $campaign,

                transactionTypeId:
                    self::TRANSACTION_TYPE_DEPOSIT,

                creditDebitTypeId:
                    self::DEBIT,

                accountId:
                    $accountId,

                transactionCompanyId:
                    (int) $user->company_id,

                amount:
                    $amount,

                balanceBefore:
                    (float)
                    $debit['balance_before'],

                balanceAfter:
                    (float)
                    $debit['balance_after'],

                companyAmount:
                    0,

                companyBalanceBefore:
                    0,

                companyBalanceAfter:
                    0,

                description:
                    'Campaign Contribution',

                reference:
                    $transactionReference
            );

            return [
                'campaign' =>
                    $campaign->fresh(),

                'campaign_transaction' =>
                    $campaignTransaction,

                'core_transaction' =>
                    $coreTransaction,

                'wallet_transaction' =>
                    $debit,
            ];
        });
    }

    /**
     * Withdraw all currently withdrawable Campaign funds.
     *
     * ONLY the Campaign creator can withdraw.
     *
     * The Campaign may be active OR closed.
     *
     * The target amount has no impact on withdrawal.
     *
     * Example:
     *
     * Campaign balance = ₦100,000
     * Fee = 3%
     *
     * Fee = ₦3,000
     * Creator receives = ₦97,000
     *
     * Campaign balance = ₦0
     *
     * Company wallet = unchanged
     *
     * Company financial entry:
     * revenue = ₦3,000
     */
    public function withdraw(
        int $campaignId,
        $user
    ): array {
        return DB::transaction(function () use (
            $campaignId,
            $user
        ) {

            /*
             * Lock Campaign.
             */
            $campaign = Campaign::query()
                ->where('id', $campaignId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                throw new RuntimeException(
                    'Campaign not found or you are not the Campaign creator.'
                );
            }

            /*
             * The Campaign can be withdrawn from while:
             *
             * active
             * closed
             *
             * Only creator ownership matters here.
             */
            if (!in_array(
                $campaign->status,
                [
                    self::STATUS_ACTIVE,
                    self::STATUS_CLOSED,
                ],
                true
            )) {
                throw new RuntimeException(
                    'This Campaign is not available for withdrawal.'
                );
            }

            /*
             * Current Campaign funds available for withdrawal.
             */
            $grossAmount = round(
                (float) $campaign->withdrawable_amount,
                2
            );

            if ($grossAmount <= 0) {
                throw new RuntimeException(
                    'There are no funds currently available for withdrawal.'
                );
            }

            /*
             * Resolve the creator's stored destination account.
             *
             * We use creator_account_id because it was resolved and
             * stored during Campaign creation.
             */
            $account = wb_account_details::query()
                ->where(
                    'id',
                    $campaign->creator_account_id
                )
                ->where(
                    'user_id',
                    $campaign->user_id
                )
                ->first();

            if (!$account) {
                throw new RuntimeException(
                    'Campaign creator withdrawal account could not be found.'
                );
            }

            /*
             * Make sure the stored account number still matches
             * the Campaign record.
             */
            if (
                (string) $account->account_number
                !==
                (string) $campaign->creator_account_number
            ) {
                throw new RuntimeException(
                    'The Campaign withdrawal account could not be verified.'
                );
            }

            /*
             * Resolve the snapshotted Campaign fee percentage.
             */
            $feePercentage = round(
                (float)
                $campaign->campaign_fee_percentage,
                4
            );

            if ($feePercentage < 0 || $feePercentage > 100) {
                throw new RuntimeException(
                    'Invalid Campaign withdrawal fee percentage.'
                );
            }

            /*
             * Calculate company fee.
             */
            $fee = round(
                $grossAmount
                * ($feePercentage / 100),
                2
            );

            /*
             * Net amount going to creator.
             */
            $creatorAmount = round(
                $grossAmount - $fee,
                2
            );

            if ($creatorAmount < 0) {
                $creatorAmount = 0;
            }

            /*
             * Credit the creator's actual wallet.
             *
             * IMPORTANT:
             * This remains a real customer wallet movement.
             */
            $credit = [
                'status' =>
                    true,

                'amount' =>
                    0,

                'balance_before' =>
                    0,

                'balance_after' =>
                    0,
            ];

            if ($creatorAmount > 0) {

                $credit = creditCustomerOnly(
                    $campaign->creator_account_id,
                    $creatorAmount
                );

                if (!($credit['status'] ?? false)) {
                    throw new RuntimeException(
                        $credit['message']
                        ?? 'Unable to credit Campaign creator wallet.'
                    );
                }
            }

            /*
             * Preserve Campaign balance before withdrawal.
             */
            $balanceBefore = $grossAmount;

            /*
             * Remove the FULL gross amount from the Campaign.
             *
             * This is important because:
             *
             * creator receives net amount
             * company earns fee
             *
             * Therefore the entire Campaign balance has been
             * consumed by the withdrawal event.
             */
            $campaign->withdrawable_amount = 0;

            /*
             * Campaign remains active if it was active.
             *
             * Campaign remains closed if it was already closed.
             *
             * This means an active Campaign can later receive new
             * contributions after the creator withdraws its current
             * balance.
             */
            $campaign->last_withdrawn_at = now();

            $campaign->save();

            /*
             * Main Campaign withdrawal reference.
             */
            $withdrawalReference =
                'CPW-' . strtoupper(Str::random(20));

            /*
             * Record Campaign withdrawal.
             *
             * amount = net amount received by creator
             * fee    = company fee
             *
             * amount + fee = gross Campaign amount.
             */
            $campaignTransaction =
                CampaignTransaction::create([
                    'campaign_id' =>
                        $campaign->id,

                    'user_id' =>
                        $campaign->user_id,

                    'company_id' =>
                        $campaign->company_id,

                    'type' =>
                        'withdrawal',

                    'amount' =>
                        $creatorAmount,

                    'fee' =>
                        $fee,

                    'balance_before' =>
                        $balanceBefore,

                    'balance_after' =>
                        0,

                    'transaction_reference' =>
                        $withdrawalReference,

                    'status' =>
                        'successful',

                    'description' =>
                        'Campaign funds withdrawn by creator',

                    'metadata' => [
                        'gross_amount' =>
                            $grossAmount,

                        'fee_percentage' =>
                            $feePercentage,

                        'fee_amount' =>
                            $fee,

                        'creator_amount' =>
                            $creatorAmount,

                        'creator_account_id' =>
                            $campaign->creator_account_id,

                        'creator_account_number' =>
                            $campaign
                                ->creator_account_number,

                        /*
                         * This withdrawal does not modify
                         * company_wallet.balance.
                         */
                        'company_wallet_affected' =>
                            false,
                    ],
                ]);

            /*
             * Create the actual Finova customer wallet transaction.
             *
             * Only the creator wallet is being credited.
             * Company balance fields remain zero.
             */
            $coreTransaction =
                $this->createCoreTransaction(
                    campaign:
                        $campaign,

                    transactionTypeId:
                        self::TRANSACTION_TYPE_WITHDRAWAL,

                    creditDebitTypeId:
                        self::CREDIT,

                    accountId:
                        $campaign->creator_account_id,

                    transactionCompanyId:
                        (int) $campaign->company_id,

                    amount:
                        $creatorAmount,

                    balanceBefore:
                        (float)
                        ($credit['balance_before'] ?? 0),

                    balanceAfter:
                        (float)
                        ($credit['balance_after'] ?? 0),

                    companyAmount:
                        0,

                    companyBalanceBefore:
                        0,

                    companyBalanceAfter:
                        0,

                    description:
                        'Campaign Withdrawal',

                    reference:
                        $withdrawalReference
                );

            /*
             * Campaign withdrawal fee is COMPANY REVENUE.
             *
             * IMPORTANT:
             *
             * This does NOT modify company_wallet.balance.
             */
            $financialEntry = null;

            if ($fee > 0) {

                $financialEntry =
                    $this->recordCompanyRevenue(
                        campaign:
                            $campaign,

                        amount:
                            $fee,

                        category:
                            self::CATEGORY_CAMPAIGN_WITHDRAWAL_FEE,

                        description:
                            'Campaign withdrawal fee revenue.',

                        reference:
                            'CPR-' . strtoupper(
                                Str::random(20)
                            ),

                        transactionId:
                            $coreTransaction->id,

                        metadata: [
                            'gross_amount' =>
                                $grossAmount,

                            'fee_percentage' =>
                                $feePercentage,

                            'fee_amount' =>
                                $fee,

                            'creator_amount' =>
                                $creatorAmount,

                            'campaign_transaction_id' =>
                                $campaignTransaction->id,

                            'company_wallet_affected' =>
                                false,
                        ]
                    );
            }

            return [
                'campaign' =>
                    $campaign->fresh(),

                'gross_amount' =>
                    $grossAmount,

                'fee' =>
                    $fee,

                'fee_percentage' =>
                    $feePercentage,

                'amount_released' =>
                    $creatorAmount,

                'creator_credit' =>
                    $credit,

                'campaign_transaction' =>
                    $campaignTransaction,

                'financial_entry' =>
                    $financialEntry,

                'core_transaction' =>
                    $coreTransaction,
            ];
        });
    }

    /**
     * Update a Campaign's benchmark target.
     *
     * Only the creator can edit the target.
     *
     * The Campaign must still be active.
     *
     * Changing the target does NOT change:
     *
     * total_contributed
     * withdrawable_amount
     */
    public function updateTarget(
        int $campaignId,
        $user,
        float $targetAmount
    ): Campaign {
        if ($targetAmount <= 0) {
            throw new RuntimeException(
                'Campaign target amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $campaignId,
            $user,
            $targetAmount
        ) {

            $campaign = Campaign::query()
                ->where('id', $campaignId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                throw new RuntimeException(
                    'Campaign not found or you are not the Campaign creator.'
                );
            }

            if ($campaign->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException(
                    'A closed Campaign cannot be edited.'
                );
            }

            /*
             * Only the benchmark changes.
             *
             * Contributions and available money remain untouched.
             */
            $campaign->target_amount =
                round($targetAmount, 2);

            $campaign->save();

            return $campaign->fresh();
        });
    }

    /**
     * Close a Campaign by its creator.
     *
     * Closing does NOT delete the Campaign.
     *
     * Existing funds remain withdrawable.
     *
     * New deposits are rejected after closing.
     */
    public function closeCampaign(
        int $campaignId,
        $user
    ): Campaign {
        return DB::transaction(function () use (
            $campaignId,
            $user
        ) {

            $campaign = Campaign::query()
                ->where('id', $campaignId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                throw new RuntimeException(
                    'Campaign not found or you are not the Campaign creator.'
                );
            }

            if ($campaign->status === self::STATUS_CLOSED) {
                throw new RuntimeException(
                    'Campaign is already closed.'
                );
            }

            if ($campaign->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException(
                    'Campaign cannot be closed from its current status.'
                );
            }

            /*
             * Close the Campaign.
             *
             * Money remains untouched.
             */
            $campaign->status =
                self::STATUS_CLOSED;

            $campaign->closed_at =
                now();

            $campaign->save();

            return $campaign->fresh();
        });
    }

    /**
     * Close a Campaign by an administrator.
     *
     * The controller should perform the existing
     * apiCando('root|admin') authorization before calling this.
     *
     * The service additionally restricts the administrator to
     * Campaigns belonging to their own company.
     */
    public function adminCloseCampaign(
        int $campaignId,
        $adminUser
    ): Campaign {
        return DB::transaction(function () use (
            $campaignId,
            $adminUser
        ) {

            if (empty($adminUser->company_id)) {
                throw new RuntimeException(
                    'Administrator account is not linked to a company.'
                );
            }

            $campaign = Campaign::query()
                ->where('id', $campaignId)
                ->where(
                    'company_id',
                    $adminUser->company_id
                )
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                throw new RuntimeException(
                    'Campaign not found in your company.'
                );
            }

            if ($campaign->status === self::STATUS_CLOSED) {
                throw new RuntimeException(
                    'Campaign is already closed.'
                );
            }

            if ($campaign->status !== self::STATUS_ACTIVE) {
                throw new RuntimeException(
                    'Campaign cannot be closed from its current status.'
                );
            }

            $campaign->status =
                self::STATUS_CLOSED;

            $campaign->closed_at =
                now();

            $campaign->save();

            return $campaign->fresh();
        });
    }

    /**
     * Record company revenue.
     *
     * IMPORTANT:
     * This does NOT change company_wallet.balance.
     *
     * It creates a financial/P&L entry only.
     */
    protected function recordCompanyRevenue(
        Campaign $campaign,
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

        /*
         * Protect against duplicate accounting entries.
         */
        $existing = CompanyFinancialEntry::query()
            ->where(
                'reference',
                $reference
            )
            ->first();

        if ($existing) {
            return $existing;
        }

        return CompanyFinancialEntry::create([
            'company_id' =>
                $campaign->company_id,

            'user_id' =>
                $campaign->user_id,

            'target_savings_id' =>
                null,

            'campaign_id' =>
                $campaign->id,

            'transaction_id' =>
                $transactionId,

            'type' =>
                self::FINANCIAL_REVENUE,

            'category' =>
                $category,

            'amount' =>
                round($amount, 2),

            'reference' =>
                $reference,

            'description' =>
                $description,

            'metadata' =>
                $metadata,

            'status' =>
                'successful',
        ]);
    }

    /**
     * Create the actual Finova transactions record.
     *
     * transactionCompanyId is passed separately because:
     *
     * - Campaign deposit:
     *   contributor's company owns the wallet being debited.
     *
     * - Campaign withdrawal:
     *   creator's company owns the destination account.
     *
     * Company financial revenue for the fee is separately associated
     * with the Campaign's company.
     */
    protected function createCoreTransaction(
        Campaign $campaign,
        int $transactionTypeId,
        int $creditDebitTypeId,
        int $accountId,
        int $transactionCompanyId,
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

        /*
         * Resolve CR/DR ledger configuration using the company
         * that owns the actual wallet transaction.
         */
        $ledgerSettings = get_CR_DR_TXN_Type_Ledger(
            $transactionTypeId,
            $transactionCompanyId
        );

        if ($ledgerSettings) {
            $ledgerCr =
                $ledgerSettings->ledger_cr;

            $ledgerDr =
                $ledgerSettings->ledger_dr;
        }

        /*
         * Resolve branch from the account/user.
         */
        $account = wb_account_details::with('user')
            ->find($accountId);

        $branchId =
            $account?->user?->branch_id;

        return transactions::create([
            'account_id' =>
                $accountId,

            'transaction_reference' =>
                $reference
                ?? 'CP-'
                . strtoupper(Str::random(20)),

            'balance_before' =>
                $balanceBefore,

            'amount' =>
                $amount,

            'balance_after' =>
                $balanceAfter,

            /*
             * Campaign financial accounting does NOT change
             * company_wallet.balance.
             */
            'company_balance_before' =>
                $companyBalanceBefore,

            'company_amount' =>
                $companyAmount,

            'company_balance_after' =>
                $companyBalanceAfter,

            'description' =>
                $description
                ?? 'Campaign transaction',

            'system_description' =>
                $description
                ?? 'Campaign transaction',

            'branch_id' =>
                $branchId,

            'parent_transaction_id' =>
                null,

            'transaction_status_id' =>
                1,

            /*
             * Use the company that owns the wallet movement.
             */
            'company_id' =>
                $transactionCompanyId,

            'transaction_type_id' =>
                $transactionTypeId,

            /*
             * Existing successful transactions use remark ID 2.
             */
            'transaction_remarks_id' =>
                2,

            'credit_debit_type_id' =>
                $creditDebitTypeId,

            'created_by' =>
                1,

            'created_date' =>
                today(),

            'ledger_cr' =>
                $ledgerCr,

            'ledger_dr' =>
                $ledgerDr,
        ]);
    }
}