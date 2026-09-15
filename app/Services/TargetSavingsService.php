<?php

namespace App\Services;

use App\Models\TargetSaving;
use App\Models\TargetSavingsTransaction;
use App\Models\CompanyFinancialEntry;
use App\Models\transactions;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TargetSavingsService
{
    /**
     * Transaction type IDs for Target Savings.
     *
     * 126 = Target Savings Deposit
     * 127 = Target Savings Early Withdrawal
     * 128 = Target Savings Maturity
     */
    private const TRANSACTION_TYPE_DEPOSIT = 126;
    private const TRANSACTION_TYPE_EARLY_WITHDRAWAL = 127;
    private const TRANSACTION_TYPE_MATURITY = 128;

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
    private const CATEGORY_TARGET_SAVINGS_INTEREST =
        'target_savings_interest';

    private const CATEGORY_TARGET_SAVINGS_EARLY_WITHDRAWAL_FEE =
        'target_savings_early_withdrawal_fee';

    /**
     * Create a Target Savings account.
     */
    public function createTarget(
        $user,
        string $targetName,
        float $targetAmount,
        string $withdrawalDate
    ): TargetSaving {
        return DB::transaction(function () use (
            $user,
            $targetName,
            $targetAmount,
            $withdrawalDate
        ) {

            $companyId = $user->company_id;

            /*
             * Resolve company settings.
             */
            $companySettings = $user->company->setting;

            if (!$companySettings) {
                throw new RuntimeException(
                    'Company savings settings could not be found.'
                );
            }

            /*
             * Validate maturity date.
             */
            $withdrawDate = Carbon::parse($withdrawalDate)
                ->startOfDay();

            if ($withdrawDate->lte(now()->startOfDay())) {
                throw new RuntimeException(
                    'Withdrawal date must be a future date.'
                );
            }

            /*
             * Resolve the customer's first account/wallet.
             */
            $account = $user->wb_account_details()
                ->orderBy('id')
                ->first();

            if (!$account) {
                throw new RuntimeException(
                    'Customer Savings account could not be found.'
                );
            }

            /*
             * Create Target Savings.
             *
             * Interest rate and early withdrawal fee are
             * snapshotted from the company's current settings.
             */
            return TargetSaving::create([
                'user_id' => $user->id,

                'company_id' => $companyId,

                'source_account_id' => $account->id,

                'destination_account_id' => $account->id,

                'target_name' => $targetName,

                'target_amount' => $targetAmount,

                'current_balance' => 0,

                'total_deposited' => 0,

                'total_interest_earned' => 0,

                'interest_rate' =>
                    $companySettings
                        ->target_savings_interest_rate ?? 0,

                'early_withdrawal_fee_percentage' =>
                    $companySettings
                        ->target_savings_early_withdrawal_fee_percentage
                        ?? 0,

                'withdrawal_date' => $withdrawDate,

                'status' => 'active',
            ]);
        });
    }

    /**
     * Deposit money into Target Savings.
     *
     * Money is debited from the customer's actual wallet.
     *
     * This does NOT create a company revenue or expense.
     */
    public function deposit(
        int $targetId,
        $user,
        float $amount
    ): array {
        if ($amount <= 0) {
            throw new RuntimeException(
                'Deposit amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $targetId,
            $user,
            $amount
        ) {

            /*
             * Lock the Target Savings row.
             */
            $target = TargetSaving::query()
                ->where('id', $targetId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$target) {
                throw new RuntimeException(
                    'Target Savings not found.'
                );
            }

            if ($target->status !== 'active') {
                throw new RuntimeException(
                    'This Target Savings is no longer active.'
                );
            }

            /*
             * Deposits are not allowed on/after maturity.
             */
            if (
                now()->startOfDay()->gte(
                    $target->withdrawal_date->startOfDay()
                )
            ) {
                throw new RuntimeException(
                    'This Target Savings has reached its maturity date.'
                );
            }

            /*
             * Use the account saved against Target Savings.
             */
            $accountId = $target->source_account_id;

            /*
             * Debit the customer's actual wallet.
             */
            $debit = debitCustomerOnly(
                $accountId,
                $amount,
                $user
            );

            if (!($debit['status'] ?? false)) {
                throw new RuntimeException(
                    $debit['message']
                    ?? 'Unable to debit customer wallet.'
                );
            }

            $balanceBefore = round(
                (float) $target->current_balance,
                2
            );

            $balanceAfter = round(
                $balanceBefore + $amount,
                2
            );

            $totalDeposited = round(
                (float) $target->total_deposited + $amount,
                2
            );

            /*
             * Update Target Savings.
             */
            $target->current_balance = $balanceAfter;
            $target->total_deposited = $totalDeposited;
            $target->save();

            /*
             * Target Savings product ledger.
             */
            $targetTransaction = TargetSavingsTransaction::create([
                'target_savings_id' =>
                    $target->id,

                'user_id' =>
                    $target->user_id,

                'company_id' =>
                    $target->company_id,

                'type' =>
                    'deposit',

                'amount' =>
                    $amount,

                'fee' =>
                    0,

                'balance_before' =>
                    $balanceBefore,

                'balance_after' =>
                    $balanceAfter,

                'transaction_reference' =>
                    'TSD-' . strtoupper(Str::random(20)),

                'status' =>
                    'successful',

                'description' =>
                    'Deposit into Target Savings',

                'metadata' => [
                    'source_account_id' =>
                        $target->source_account_id,
                ],
            ]);

            /*
             * Record the customer's actual wallet movement
             * in the main Finova transactions table.
             */
            $coreTransaction = $this->createCoreTransaction(
                target: $target,

                transactionTypeId:
                    self::TRANSACTION_TYPE_DEPOSIT,

                creditDebitTypeId:
                    self::DEBIT,

                accountId:
                    $target->source_account_id,

                amount:
                    $amount,

                balanceBefore:
                    (float) $debit['balance_before'],

                balanceAfter:
                    (float) $debit['balance_after'],

                companyAmount:
                    0,

                companyBalanceBefore:
                    0,

                companyBalanceAfter:
                    0,

                description:
                    'Target Savings Deposit',

                reference:
                    'TSD-' . strtoupper(Str::random(20))
            );

            return [
                'target_savings' =>
                    $target->fresh(),

                'target_savings_transaction' =>
                    $targetTransaction,

                'core_transaction' =>
                    $coreTransaction,

                'wallet_transaction' =>
                    $debit,
            ];
        });
    }

    /**
     * Withdraw from Target Savings.
     *
     * Maturity:
     *     Customer receives the full Target Savings balance.
     *
     * Early withdrawal:
     *     Customer receives balance minus fee.
     *     Fee becomes company revenue.
     *
     * Important:
     *     The company wallet balance is NOT modified.
     */
    public function withdraw(
        int $targetId,
        $user
    ): array {
        return DB::transaction(function () use (
            $targetId,
            $user
        ) {

            /*
             * Lock the Target Savings row.
             */
            $target = TargetSaving::query()
                ->where('id', $targetId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$target) {
                throw new RuntimeException(
                    'Target Savings not found.'
                );
            }

            if ($target->status !== 'active') {
                throw new RuntimeException(
                    'This Target Savings is not available for withdrawal.'
                );
            }

            $balance = round(
                (float) $target->current_balance,
                2
            );

            if ($balance <= 0) {
                throw new RuntimeException(
                    'Target Savings balance is empty.'
                );
            }

            $today = today()->startOfDay();

            $maturityDate = $target->withdrawal_date
                ->copy()
                ->startOfDay();

            /*
             * Maturity date reached.
             */
            $isMatured = $today->gte($maturityDate);

            $feePercentage = 0;
            $fee = 0;
            $customerAmount = $balance;

            /*
             * Calculate early withdrawal fee.
             */
            if (!$isMatured) {

                $feePercentage = (float)
                    $target->early_withdrawal_fee_percentage;

                $fee = round(
                    $balance * ($feePercentage / 100),
                    2
                );

                $customerAmount = round(
                    $balance - $fee,
                    2
                );

                if ($customerAmount < 0) {
                    $customerAmount = 0;
                }
            }

            /*
             * Credit the customer's actual wallet.
             *
             * This remains a real money movement.
             */
            $credit = [
                'status' => true,
                'amount' => 0,
                'balance_before' => 0,
                'balance_after' => 0,
            ];

            if ($customerAmount > 0) {

                $credit = creditCustomerOnly(
                    $target->destination_account_id,
                    $customerAmount
                );

                if (!($credit['status'] ?? false)) {
                    throw new RuntimeException(
                        $credit['message']
                        ?? 'Unable to credit customer wallet.'
                    );
                }
            }

            /*
             * Preserve Target Savings balance before closing.
             */
            $balanceBefore = $balance;

            /*
             * Close Target Savings.
             */
            $target->current_balance = 0;

            if ($isMatured) {
                $target->status = 'completed';
                $target->matured_at = now();
            } else {
                $target->status = 'early_withdrawal';
            }

            $target->withdrawn_at = now();
            $target->save();

            /*
             * Main Target Savings withdrawal transaction.
             */
            $withdrawalTransaction =
                TargetSavingsTransaction::create([
                    'target_savings_id' =>
                        $target->id,

                    'user_id' =>
                        $target->user_id,

                    'company_id' =>
                        $target->company_id,

                    'type' =>
                        $isMatured
                            ? 'maturity'
                            : 'early_withdrawal',

                    'amount' =>
                        $customerAmount,

                    'fee' =>
                        $fee,

                    'balance_before' =>
                        $balanceBefore,

                    'balance_after' =>
                        0,

                    'transaction_reference' =>
                        $isMatured
                            ? 'TSM-' . strtoupper(Str::random(20))
                            : 'TSW-' . strtoupper(Str::random(20)),

                    'status' =>
                        'successful',

                    'description' =>
                        $isMatured
                            ? 'Target Savings matured and funds released'
                            : 'Early withdrawal from Target Savings',

                    'metadata' => [
                        'withdrawal_type' =>
                            $isMatured
                                ? 'maturity'
                                : 'early_withdrawal',

                        'fee_percentage' =>
                            $feePercentage,

                        'fee_amount' =>
                            $fee,
                    ],
                ]);

            /*
             * Record early withdrawal fee separately
             * in the Target Savings ledger.
             */
            $feeTransaction = null;

            if ($fee > 0) {

                $feeTransaction =
                    TargetSavingsTransaction::create([
                        'target_savings_id' =>
                            $target->id,

                        'user_id' =>
                            $target->user_id,

                        'company_id' =>
                            $target->company_id,

                        'type' =>
                            'early_withdrawal_fee',

                        'amount' =>
                            $fee,

                        'fee' =>
                            $fee,

                        'balance_before' =>
                            $balanceBefore,

                        'balance_after' =>
                            0,

                        'transaction_reference' =>
                            'TSF-' . strtoupper(Str::random(20)),

                        'status' =>
                            'successful',

                        'description' =>
                            'Early withdrawal fee',

                        'metadata' => [
                            'fee_percentage' =>
                                $feePercentage,

                            'fee_amount' =>
                                $fee,

                            /*
                             * Important:
                             * this is accounting revenue.
                             * It does NOT represent a company
                             * wallet balance credit.
                             */
                            'company_wallet_affected' =>
                                false,
                        ],
                    ]);
            }

            /*
             * Main Finova customer transaction.
             *
             * Only the customer's wallet movement is represented
             * here. Company balance fields remain zero because
             * company_wallet.balance is NOT being changed.
             */
            $coreTransaction = $this->createCoreTransaction(
                target: $target,

                transactionTypeId:
                    $isMatured
                        ? self::TRANSACTION_TYPE_MATURITY
                        : self::TRANSACTION_TYPE_EARLY_WITHDRAWAL,

                creditDebitTypeId:
                    self::CREDIT,

                accountId:
                    $target->destination_account_id,

                amount:
                    $customerAmount,

                balanceBefore:
                    (float) ($credit['balance_before'] ?? 0),

                balanceAfter:
                    (float) ($credit['balance_after'] ?? 0),

                companyAmount:
                    0,

                companyBalanceBefore:
                    0,

                companyBalanceAfter:
                    0,

                description:
                    $isMatured
                        ? 'Target Savings Maturity'
                        : 'Target Savings Early Withdrawal',

                reference:
                    $isMatured
                        ? 'TSM-' . strtoupper(Str::random(20))
                        : 'TSW-' . strtoupper(Str::random(20))
            );

            /*
             * Early withdrawal fee = company revenue.
             *
             * We DO NOT modify company_wallet.balance.
             */
            $financialEntry = null;

            if (!$isMatured && $fee > 0) {

                $financialEntry =
                    $this->recordCompanyRevenue(
                        target: $target,
                        amount: $fee,
                        category:
                            self::CATEGORY_TARGET_SAVINGS_EARLY_WITHDRAWAL_FEE,
                        description:
                            'Target Savings early withdrawal fee revenue.',
                        reference:
                            'TSR-' . strtoupper(Str::random(20)),
                        transactionId:
                            $coreTransaction->id,
                        metadata: [
                            'fee_percentage' =>
                                $feePercentage,

                            'target_balance_before_withdrawal' =>
                                $balanceBefore,

                            'customer_amount_released' =>
                                $customerAmount,

                            'fee_amount' =>
                                $fee,

                            'target_savings_transaction_id' =>
                                $withdrawalTransaction->id,

                            'fee_transaction_id' =>
                                $feeTransaction?->id,

                            'company_wallet_affected' =>
                                false,
                        ]
                    );
            }

            return [
                'target_savings' =>
                    $target->fresh(),

                'amount_released' =>
                    $customerAmount,

                'fee' =>
                    $fee,

                'fee_percentage' =>
                    $feePercentage,

                'withdrawal_type' =>
                    $isMatured
                        ? 'maturity'
                        : 'early_withdrawal',

                'customer_credit' =>
                    $credit,

                /*
                 * Kept for backwards compatibility.
                 *
                 * This is now null because the company wallet
                 * is intentionally not modified.
                 */
                'company_credit' =>
                    null,

                'target_savings_transaction' =>
                    $withdrawalTransaction,

                'fee_transaction' =>
                    $feeTransaction,

                'financial_entry' =>
                    $financialEntry,

                'core_transaction' =>
                    $coreTransaction,
            ];
        });
    }

    /**
     * Process daily Target Savings interest.
     *
     * Interest is:
     *
     * 1. Added to Target Savings balance.
     * 2. Added to total_interest_earned.
     * 3. Recorded as a Target Savings transaction.
     * 4. Recorded as COMPANY EXPENSE.
     *
     * The customer's spendable wallet is NOT credited yet.
     */
    public function processDailyInterest(): void
    {
        /*
         * Process interest up to yesterday.
         *
         * Example:
         *
         * September 11, 00:00
         * -> process September 10 interest.
         */
        $processUntil = today()
            ->subDay()
            ->startOfDay();

        TargetSaving::query()
            ->where('status', 'active')
            ->whereDate('withdrawal_date', '>=', today())
            ->orderBy('id')
            ->chunkById(100, function ($targets) use ($processUntil) {

                foreach ($targets as $target) {

                    DB::transaction(function () use (
                        $target,
                        $processUntil
                    ) {

                        /*
                         * Lock current Target Savings record.
                         */
                        $target = TargetSaving::query()
                            ->lockForUpdate()
                            ->find($target->id);

                        if (!$target) {
                            return;
                        }

                        if ($target->status !== 'active') {
                            return;
                        }

                        /*
                         * Do not process targets whose maturity
                         * date has arrived today.
                         */
                        if (
                            $target->withdrawal_date
                                ->startOfDay()
                                ->lte(today()->startOfDay())
                        ) {
                            return;
                        }

                        /*
                         * First date that has not received interest.
                         */
                        $interestDate = $target->last_interest_date
                            ? Carbon::parse(
                                $target->last_interest_date
                            )
                                ->addDay()
                                ->startOfDay()
                            : $target->created_at
                                ->copy()
                                ->startOfDay();

                        /*
                         * Catch up missing dates.
                         */
                        while ($interestDate->lte($processUntil)) {

                            /*
                             * Never award interest on or after
                             * the maturity date.
                             */
                            if (
                                $interestDate->gte(
                                    $target->withdrawal_date
                                        ->copy()
                                        ->startOfDay()
                                )
                            ) {
                                break;
                            }

                            /*
                             * Prevent duplicate interest entries.
                             */
                            $existing =
                                TargetSavingsTransaction::query()
                                    ->where(
                                        'target_savings_id',
                                        $target->id
                                    )
                                    ->where(
                                        'type',
                                        'interest'
                                    )
                                    ->whereDate(
                                        'interest_date',
                                        $interestDate->toDateString()
                                    )
                                    ->first();

                            if ($existing) {

                                /*
                                 * Make sure an accounting expense
                                 * also exists for this interest.
                                 *
                                 * This protects old/incomplete records.
                                 */
                                $financialReference =
                                    'TSE-'
                                    . $target->id
                                    . '-'
                                    . $interestDate->format('Ymd');

                                $expenseExists =
                                    CompanyFinancialEntry::query()
                                        ->where(
                                            'reference',
                                            $financialReference
                                        )
                                        ->exists();

                                if (
                                    !$expenseExists
                                    && (float) $existing->amount > 0
                                ) {
                                    $this->recordCompanyExpense(
                                        target: $target,
                                        amount:
                                            (float) $existing->amount,
                                        category:
                                            self::CATEGORY_TARGET_SAVINGS_INTEREST,
                                        description:
                                            'Target Savings interest expense.',
                                        reference:
                                            $financialReference,
                                        transactionId:
                                            null,
                                        metadata: [
                                            'interest_date' =>
                                                $interestDate
                                                    ->toDateString(),

                                            'interest_transaction_id' =>
                                                $existing->id,

                                            'annual_rate' =>
                                                (float) $target->interest_rate,

                                            'company_wallet_affected' =>
                                                false,
                                        ]
                                    );
                                }

                                $target->last_interest_date =
                                    $interestDate->toDateString();

                                $target->save();

                                $interestDate->addDay();

                                continue;
                            }

                            $balanceBefore = round(
                                (float) $target->current_balance,
                                2
                            );

                            /*
                             * No money means no interest.
                             */
                            if ($balanceBefore <= 0) {

                                $target->last_interest_date =
                                    $interestDate->toDateString();

                                $target->save();

                                $interestDate->addDay();

                                continue;
                            }

                            $annualRate =
                                (float) $target->interest_rate;

                            /*
                             * Annual percentage divided by 365.
                             */
                            $interest = round(
                                $balanceBefore
                                * ($annualRate / 100)
                                / 365,
                                2
                            );

                            /*
                             * Do not create meaningless zero-value
                             * interest records.
                             */
                            if ($interest <= 0) {

                                $target->last_interest_date =
                                    $interestDate->toDateString();

                                $target->save();

                                $interestDate->addDay();

                                continue;
                            }

                            $balanceAfter = round(
                                $balanceBefore + $interest,
                                2
                            );

                            /*
                             * Update Target Savings.
                             */
                            $target->current_balance =
                                $balanceAfter;

                            $target->total_interest_earned =
                                round(
                                    (float)
                                    $target->total_interest_earned
                                    + $interest,
                                    2
                                );

                            $target->last_interest_date =
                                $interestDate->toDateString();

                            $target->save();

                            /*
                             * Record the interest in Target Savings ledger.
                             */
                            $targetTransaction =
                                TargetSavingsTransaction::create([
                                    'target_savings_id' =>
                                        $target->id,

                                    'user_id' =>
                                        $target->user_id,

                                    'company_id' =>
                                        $target->company_id,

                                    'type' =>
                                        'interest',

                                    'amount' =>
                                        $interest,

                                    'fee' =>
                                        0,

                                    'balance_before' =>
                                        $balanceBefore,

                                    'balance_after' =>
                                        $balanceAfter,

                                    'interest_date' =>
                                        $interestDate->toDateString(),

                                    'transaction_reference' =>
                                        'TSI-'
                                        . $interestDate->format('Ymd')
                                        . '-'
                                        . strtoupper(
                                            Str::random(12)
                                        ),

                                    'status' =>
                                        'successful',

                                    'description' =>
                                        'Daily Target Savings interest',

                                    'metadata' => [
                                        'annual_rate' =>
                                            $annualRate,

                                        'daily_rate' =>
                                            $annualRate / 365,

                                        'interest_date' =>
                                            $interestDate
                                                ->toDateString(),
                                    ],
                                ]);

                            /*
                             * Interest is a COMPANY EXPENSE.
                             *
                             * No company wallet balance is changed.
                             */
                            $financialReference =
                                'TSE-'
                                . $target->id
                                . '-'
                                . $interestDate->format('Ymd');

                            $this->recordCompanyExpense(
                                target: $target,
                                amount: $interest,
                                category:
                                    self::CATEGORY_TARGET_SAVINGS_INTEREST,
                                description:
                                    'Target Savings interest expense.',
                                reference:
                                    $financialReference,
                                transactionId:
                                    null,
                                metadata: [
                                    'annual_rate' =>
                                        $annualRate,

                                    'daily_rate' =>
                                        $annualRate / 365,

                                    'interest_date' =>
                                        $interestDate->toDateString(),

                                    'interest_transaction_id' =>
                                        $targetTransaction->id,

                                    'company_wallet_affected' =>
                                        false,
                                ]
                            );

                            $interestDate->addDay();
                        }
                    });
                }
            });
    }

    /**
     * Automatically mature eligible Target Savings.
     *
     * Customer receives the complete current Target Savings
     * balance, which includes previously accrued interest.
     *
     * The accrued interest has already been recognized as
     * company expense during daily interest processing.
     */
    public function processMaturities(): void
    {
        TargetSaving::query()
            ->where('status', 'active')
            ->whereDate(
                'withdrawal_date',
                '<=',
                today()
            )
            ->orderBy('id')
            ->chunkById(100, function ($targets) {

                foreach ($targets as $target) {

                    DB::transaction(function () use ($target) {

                        $target = TargetSaving::query()
                            ->lockForUpdate()
                            ->find($target->id);

                        if (!$target) {
                            return;
                        }

                        if ($target->status !== 'active') {
                            return;
                        }

                        /*
                         * Complete Target Savings balance.
                         *
                         * This includes:
                         *
                         * total_deposited
                         * +
                         * total_interest_earned
                         */
                        $amount = round(
                            (float) $target->current_balance,
                            2
                        );

                        /*
                         * Credit the customer's actual wallet.
                         */
                        $credit = [
                            'status' => true,
                            'amount' => 0,
                            'balance_before' => 0,
                            'balance_after' => 0,
                        ];

                        if ($amount > 0) {

                            $credit = creditCustomerOnly(
                                $target->destination_account_id,
                                $amount
                            );

                            if (!($credit['status'] ?? false)) {
                                throw new RuntimeException(
                                    $credit['message']
                                    ?? 'Unable to release Target Savings funds.'
                                );
                            }
                        }

                        /*
                         * Create main Finova transaction.
                         *
                         * Company balance values are zero because
                         * we are not modifying company_wallet.balance.
                         */
                        $coreTransaction = null;

                        if ($amount > 0) {

                            $coreTransaction =
                                $this->createCoreTransaction(
                                    target:
                                        $target,

                                    transactionTypeId:
                                        self::TRANSACTION_TYPE_MATURITY,

                                    creditDebitTypeId:
                                        self::CREDIT,

                                    accountId:
                                        $target
                                            ->destination_account_id,

                                    amount:
                                        $amount,

                                    balanceBefore:
                                        (float)
                                        $credit[
                                            'balance_before'
                                        ],

                                    balanceAfter:
                                        (float)
                                        $credit[
                                            'balance_after'
                                        ],

                                    companyAmount:
                                        0,

                                    companyBalanceBefore:
                                        0,

                                    companyBalanceAfter:
                                        0,

                                    description:
                                        'Target Savings Maturity',

                                    reference:
                                        'TSM-'
                                        . strtoupper(
                                            Str::random(20)
                                        )
                                );
                        }

                        /*
                         * Preserve the amount before closing.
                         */
                        $balanceBefore = $amount;

                        /*
                         * Close Target Savings.
                         */
                        $target->current_balance = 0;

                        $target->status = 'completed';

                        $target->matured_at = now();

                        $target->withdrawn_at = now();

                        $target->save();

                        /*
                         * Record maturity in Target Savings ledger.
                         */
                        TargetSavingsTransaction::create([
                            'target_savings_id' =>
                                $target->id,

                            'user_id' =>
                                $target->user_id,

                            'company_id' =>
                                $target->company_id,

                            'type' =>
                                'maturity',

                            'amount' =>
                                $amount,

                            'fee' =>
                                0,

                            'balance_before' =>
                                $balanceBefore,

                            'balance_after' =>
                                0,

                            'transaction_reference' =>
                                'TSM-'
                                . strtoupper(
                                    Str::random(20)
                                ),

                            'status' =>
                                'successful',

                            'description' =>
                                'Target Savings matured and funds released',

                            'metadata' => [
                                'destination_account_id' =>
                                    $target
                                        ->destination_account_id,

                                'principal_amount' =>
                                    round(
                                        (float)
                                        $target->total_deposited,
                                        2
                                    ),

                                'interest_amount' =>
                                    round(
                                        (float)
                                        $target
                                            ->total_interest_earned,
                                        2
                                    ),

                                /*
                                 * Interest has already been
                                 * recognized as company expense
                                 * during daily accrual.
                                 */
                                'interest_expense_recognized' =>
                                    round(
                                        (float)
                                        $target
                                            ->total_interest_earned,
                                        2
                                    ),
                            ],
                        ]);

                        /*
                         * No company revenue/expense is created here.
                         *
                         * The interest expense was already recorded
                         * when each interest amount was accrued.
                         */
                    });
                }
            });
    }

    /**
     * Record a company revenue entry.
     *
     * IMPORTANT:
     * This does not change company_wallet.balance.
     */
    protected function recordCompanyRevenue(
        TargetSaving $target,
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

        return CompanyFinancialEntry::create([
            'company_id' =>
                $target->company_id,

            'user_id' =>
                $target->user_id,

            'target_savings_id' =>
                $target->id,

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
     * Record a company expense entry.
     *
     * IMPORTANT:
     * This does not change company_wallet.balance.
     */
    protected function recordCompanyExpense(
        TargetSaving $target,
        float $amount,
        string $category,
        string $description,
        string $reference,
        ?int $transactionId = null,
        ?array $metadata = null
    ): CompanyFinancialEntry {
        if ($amount <= 0) {
            throw new RuntimeException(
                'Expense amount must be greater than zero.'
            );
        }

        /*
         * Prevent duplicate accounting entries.
         */
        $existing = CompanyFinancialEntry::query()
            ->where('reference', $reference)
            ->first();

        if ($existing) {
            return $existing;
        }

        return CompanyFinancialEntry::create([
            'company_id' =>
                $target->company_id,

            'user_id' =>
                $target->user_id,

            'target_savings_id' =>
                $target->id,

            'transaction_id' =>
                $transactionId,

            'type' =>
                self::FINANCIAL_EXPENSE,

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
     * The company balance fields are populated only when
     * an actual company wallet movement occurs.
     *
     * Target Savings no longer performs such a company
     * wallet movement, so those values are zero here.
     */
    protected function createCoreTransaction(
        TargetSaving $target,
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

        /*
         * Resolve CR/DR ledger configuration.
         */
        $ledgerSettings = get_CR_DR_TXN_Type_Ledger(
            $transactionTypeId,
            $target->company_id
        );

        if ($ledgerSettings) {
            $ledgerCr = $ledgerSettings->ledger_cr;
            $ledgerDr = $ledgerSettings->ledger_dr;
        }

        /*
         * Resolve branch from the customer's account/user.
         */
        $account = wb_account_details::with('user')
            ->find($accountId);

        $branchId = $account?->user?->branch_id;

        return transactions::create([
            'account_id' =>
                $accountId,

            'transaction_reference' =>
                $reference
                ?? 'TS-' . strtoupper(Str::random(20)),

            'balance_before' =>
                $balanceBefore,

            'amount' =>
                $amount,

            'balance_after' =>
                $balanceAfter,

            /*
             * No company wallet movement for Target Savings.
             */
            'company_balance_before' =>
                $companyBalanceBefore,

            'company_amount' =>
                $companyAmount,

            'company_balance_after' =>
                $companyBalanceAfter,

            'description' =>
                $description
                ?? 'Target Savings transaction',

            'system_description' =>
                $description
                ?? 'Target Savings transaction',

            'branch_id' =>
                $branchId,

            'parent_transaction_id' =>
                null,

            'transaction_status_id' =>
                1,

            'company_id' =>
                $target->company_id,

            'transaction_type_id' =>
                $transactionTypeId,

            /*
             * Existing successful transaction examples
             * use remark ID 2.
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