<?php

namespace App\Services;

use App\Models\FixedSaving;
use App\Models\FixedSavingsTransaction;
use App\Models\CompanyFinancialEntry;
use App\Models\transactions;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FixedSavingsService
{
    /**
     * Transaction type IDs for Fixed Savings.
     *
     * 126 = Fixed Savings Deposit
     * 127 = Fixed Savings Early Withdrawal
     * 128 = Fixed Savings Maturity
     */
    private const TRANSACTION_TYPE_DEPOSIT = 141;
    private const TRANSACTION_TYPE_EARLY_WITHDRAWAL = 142;
    private const TRANSACTION_TYPE_MATURITY = 143;

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
    private const CATEGORY_FIXED_SAVINGS_INTEREST =
        'fixed_savings_interest';

    private const CATEGORY_FIXED_SAVINGS_EARLY_WITHDRAWAL_FEE =
        'fixed_savings_early_withdrawal_fee';

    /**
     * Create a Fixed Savings account.
     */
    public function createFixed(
        $user,
        string $fixedName,
        float $fixedAmount,
        string $withdrawalDate
    ): FixedSaving {
        return DB::transaction(function () use (
            $user,
            $fixedName,
            $fixedAmount,
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
             * Create Fixed Savings.
             *
             * Interest rate and early withdrawal fee are
             * snapshotted from the company's current settings.
             */
            return FixedSaving::create([
                'user_id' => $user->id,

                'company_id' => $companyId,

                'source_account_id' => $account->id,

                'destination_account_id' => $account->id,

                'fixed_name' => $fixedName,

                'fixed_amount' => $fixedAmount,

                'current_balance' => 0,

                'total_deposited' => 0,

                'total_interest_earned' => 0,

                'interest_rate' =>
                    $companySettings
                        ->fixed_savings_interest_rate ?? 0,

                'early_withdrawal_fee_percentage' =>
                    $companySettings
                        ->fixed_savings_early_withdrawal_fee_percentage
                        ?? 0,

                'withdrawal_date' => $withdrawDate,

                'status' => 'active',
            ]);
        });
    }

    /**
     * Deposit money into Fixed Savings.
     *
     * Money is debited from the customer's actual wallet.
     *
     * This does NOT create a company revenue or expense.
     */
    public function deposit(
        int $fixedId,
        $user,
        float $amount
    ): array {
        if ($amount <= 0) {
            throw new RuntimeException(
                'Deposit amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $fixedId,
            $user,
            $amount
        ) {

            /*
             * Lock the Fixed Savings row.
             */
            $fixed = FixedSaving::query()
                ->where('id', $fixedId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$fixed) {
                throw new RuntimeException(
                    'Fixed Savings not found.'
                );
            }

            if ($fixed->status !== 'active') {
                throw new RuntimeException(
                    'This Fixed Savings is no longer active.'
                );
            }

            /*
             * Deposits are not allowed on/after maturity.
             */
            if (
                now()->startOfDay()->gte(
                    $fixed->withdrawal_date->startOfDay()
                )
            ) {
                throw new RuntimeException(
                    'This Fixed Savings has reached its maturity date.'
                );
            }

            /*
             * Use the account saved against Fixed Savings.
             */
            $accountId = $fixed->source_account_id;

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
                (float) $fixed->current_balance,
                2
            );

            $balanceAfter = round(
                $balanceBefore + $amount,
                2
            );

            $totalDeposited = round(
                (float) $fixed->total_deposited + $amount,
                2
            );

            /*
             * Update Fixed Savings.
             */
            $fixed->current_balance = $balanceAfter;
            $fixed->total_deposited = $totalDeposited;
            $fixed->save();

            /*
             * Fixed Savings product ledger.
             */
            $fixedTransaction = FixedSavingsTransaction::create([
                'fixed_savings_id' =>
                    $fixed->id,

                'user_id' =>
                    $fixed->user_id,

                'company_id' =>
                    $fixed->company_id,

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
                    'Deposit into Fixed Savings',

                'metadata' => [
                    'source_account_id' =>
                        $fixed->source_account_id,
                ],
            ]);

            /*
             * Record the customer's actual wallet movement
             * in the main Finova transactions table.
             */
            $coreTransaction = $this->createCoreTransaction(
                fixed: $fixed,

                transactionTypeId:
                    self::TRANSACTION_TYPE_DEPOSIT,

                creditDebitTypeId:
                    self::DEBIT,

                accountId:
                    $fixed->source_account_id,

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
                    'Fixed Savings Deposit',

                reference:
                    'TSD-' . strtoupper(Str::random(20))
            );

            return [
                'fixed_savings' =>
                    $fixed->fresh(),

                'fixed_savings_transaction' =>
                    $fixedTransaction,

                'core_transaction' =>
                    $coreTransaction,

                'wallet_transaction' =>
                    $debit,
            ];
        });
    }

    /**
     * Withdraw from Fixed Savings.
     *
     * Maturity:
     *     Customer receives the full Fixed Savings balance.
     *
     * Early withdrawal:
     *     Customer receives balance minus fee.
     *     Fee becomes company revenue.
     *
     * Important:
     *     The company wallet balance is NOT modified.
     */
    public function withdraw(
        int $fixedId,
        $user
    ): array {
        return DB::transaction(function () use (
            $fixedId,
            $user
        ) {

            /*
             * Lock the Fixed Savings row.
             */
            $fixed = FixedSaving::query()
                ->where('id', $fixedId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$fixed) {
                throw new RuntimeException(
                    'Fixed Savings not found.'
                );
            }

            if ($fixed->status !== 'active') {
                throw new RuntimeException(
                    'This Fixed Savings is not available for withdrawal.'
                );
            }

            $balance = round(
                (float) $fixed->current_balance,
                2
            );

            if ($balance <= 0) {
                throw new RuntimeException(
                    'Fixed Savings balance is empty.'
                );
            }

            $today = today()->startOfDay();

            $maturityDate = $fixed->withdrawal_date
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
                    $fixed->early_withdrawal_fee_percentage;

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
                    $fixed->destination_account_id,
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
             * Preserve Fixed Savings balance before closing.
             */
            $balanceBefore = $balance;

            /*
             * Close Fixed Savings.
             */
            $fixed->current_balance = 0;

            if ($isMatured) {
                $fixed->status = 'completed';
                $fixed->matured_at = now();
            } else {
                $fixed->status = 'early_withdrawal';
            }

            $fixed->withdrawn_at = now();
            $fixed->save();

            /*
             * Main Fixed Savings withdrawal transaction.
             */
            $withdrawalTransaction =
                FixedSavingsTransaction::create([
                    'fixed_savings_id' =>
                        $fixed->id,

                    'user_id' =>
                        $fixed->user_id,

                    'company_id' =>
                        $fixed->company_id,

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
                            ? 'Fixed Savings matured and funds released'
                            : 'Early withdrawal from Fixed Savings',

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
             * in the Fixed Savings ledger.
             */
            $feeTransaction = null;

            if ($fee > 0) {

                $feeTransaction =
                    FixedSavingsTransaction::create([
                        'fixed_savings_id' =>
                            $fixed->id,

                        'user_id' =>
                            $fixed->user_id,

                        'company_id' =>
                            $fixed->company_id,

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
                fixed: $fixed,

                transactionTypeId:
                    $isMatured
                        ? self::TRANSACTION_TYPE_MATURITY
                        : self::TRANSACTION_TYPE_EARLY_WITHDRAWAL,

                creditDebitTypeId:
                    self::CREDIT,

                accountId:
                    $fixed->destination_account_id,

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
                        ? 'Fixed Savings Maturity'
                        : 'Fixed Savings Early Withdrawal',

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
                        fixed: $fixed,
                        amount: $fee,
                        category:
                            self::CATEGORY_FIXED_SAVINGS_EARLY_WITHDRAWAL_FEE,
                        description:
                            'Fixed Savings early withdrawal fee revenue.',
                        reference:
                            'TSR-' . strtoupper(Str::random(20)),
                        transactionId:
                            $coreTransaction->id,
                        metadata: [
                            'fee_percentage' =>
                                $feePercentage,

                            'fixed_balance_before_withdrawal' =>
                                $balanceBefore,

                            'customer_amount_released' =>
                                $customerAmount,

                            'fee_amount' =>
                                $fee,

                            'fixed_savings_transaction_id' =>
                                $withdrawalTransaction->id,

                            'fee_transaction_id' =>
                                $feeTransaction?->id,

                            'company_wallet_affected' =>
                                false,
                        ]
                    );
            }

            return [
                'fixed_savings' =>
                    $fixed->fresh(),

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

                'fixed_savings_transaction' =>
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
     * Process daily Fixed Savings interest.
     *
     * Interest is:
     *
     * 1. Added to Fixed Savings balance.
     * 2. Added to total_interest_earned.
     * 3. Recorded as a Fixed Savings transaction.
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

        FixedSaving::query()
            ->where('status', 'active')
            ->whereDate('withdrawal_date', '>=', today())
            ->orderBy('id')
            ->chunkById(100, function ($fixeds) use ($processUntil) {

                foreach ($fixeds as $fixed) {

                    DB::transaction(function () use (
                        $fixed,
                        $processUntil
                    ) {

                        /*
                         * Lock current Fixed Savings record.
                         */
                        $fixed = FixedSaving::query()
                            ->lockForUpdate()
                            ->find($fixed->id);

                        if (!$fixed) {
                            return;
                        }

                        if ($fixed->status !== 'active') {
                            return;
                        }

                        /*
                         * Do not process fixeds whose maturity
                         * date has arrived today.
                         */
                        if (
                            $fixed->withdrawal_date
                                ->startOfDay()
                                ->lte(today()->startOfDay())
                        ) {
                            return;
                        }

                        /*
                         * First date that has not received interest.
                         */
                        $interestDate = $fixed->last_interest_date
                            ? Carbon::parse(
                                $fixed->last_interest_date
                            )
                                ->addDay()
                                ->startOfDay()
                            : $fixed->created_at
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
                                    $fixed->withdrawal_date
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
                                FixedSavingsTransaction::query()
                                    ->where(
                                        'fixed_savings_id',
                                        $fixed->id
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
                                    . $fixed->id
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
                                        fixed: $fixed,
                                        amount:
                                            (float) $existing->amount,
                                        category:
                                            self::CATEGORY_FIXED_SAVINGS_INTEREST,
                                        description:
                                            'Fixed Savings interest expense.',
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
                                                (float) $fixed->interest_rate,

                                            'company_wallet_affected' =>
                                                false,
                                        ]
                                    );
                                }

                                $fixed->last_interest_date =
                                    $interestDate->toDateString();

                                $fixed->save();

                                $interestDate->addDay();

                                continue;
                            }

                            $balanceBefore = round(
                                (float) $fixed->current_balance,
                                2
                            );

                            /*
                             * No money means no interest.
                             */
                            if ($balanceBefore <= 0) {

                                $fixed->last_interest_date =
                                    $interestDate->toDateString();

                                $fixed->save();

                                $interestDate->addDay();

                                continue;
                            }

                            $annualRate =
                                (float) $fixed->interest_rate;

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

                                $fixed->last_interest_date =
                                    $interestDate->toDateString();

                                $fixed->save();

                                $interestDate->addDay();

                                continue;
                            }

                            $balanceAfter = round(
                                $balanceBefore + $interest,
                                2
                            );

                            /*
                             * Update Fixed Savings.
                             */
                            $fixed->current_balance =
                                $balanceAfter;

                            $fixed->total_interest_earned =
                                round(
                                    (float)
                                    $fixed->total_interest_earned
                                    + $interest,
                                    2
                                );

                            $fixed->last_interest_date =
                                $interestDate->toDateString();

                            $fixed->save();

                            /*
                             * Record the interest in Fixed Savings ledger.
                             */
                            $fixedTransaction =
                                FixedSavingsTransaction::create([
                                    'fixed_savings_id' =>
                                        $fixed->id,

                                    'user_id' =>
                                        $fixed->user_id,

                                    'company_id' =>
                                        $fixed->company_id,

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
                                        'Daily Fixed Savings interest',

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
                                . $fixed->id
                                . '-'
                                . $interestDate->format('Ymd');

                            $this->recordCompanyExpense(
                                fixed: $fixed,
                                amount: $interest,
                                category:
                                    self::CATEGORY_FIXED_SAVINGS_INTEREST,
                                description:
                                    'Fixed Savings interest expense.',
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
                                        $fixedTransaction->id,

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
     * Automatically mature eligible Fixed Savings.
     *
     * Customer receives the complete current Fixed Savings
     * balance, which includes previously accrued interest.
     *
     * The accrued interest has already been recognized as
     * company expense during daily interest processing.
     */
    public function processMaturities(): void
    {
        FixedSaving::query()
            ->where('status', 'active')
            ->whereDate(
                'withdrawal_date',
                '<=',
                today()
            )
            ->orderBy('id')
            ->chunkById(100, function ($fixeds) {

                foreach ($fixeds as $fixed) {

                    DB::transaction(function () use ($fixed) {

                        $fixed = FixedSaving::query()
                            ->lockForUpdate()
                            ->find($fixed->id);

                        if (!$fixed) {
                            return;
                        }

                        if ($fixed->status !== 'active') {
                            return;
                        }

                        /*
                         * Complete Fixed Savings balance.
                         *
                         * This includes:
                         *
                         * total_deposited
                         * +
                         * total_interest_earned
                         */
                        $amount = round(
                            (float) $fixed->current_balance,
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
                                $fixed->destination_account_id,
                                $amount
                            );

                            if (!($credit['status'] ?? false)) {
                                throw new RuntimeException(
                                    $credit['message']
                                    ?? 'Unable to release Fixed Savings funds.'
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
                                    fixed:
                                        $fixed,

                                    transactionTypeId:
                                        self::TRANSACTION_TYPE_MATURITY,

                                    creditDebitTypeId:
                                        self::CREDIT,

                                    accountId:
                                        $fixed
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
                                        'Fixed Savings Maturity',

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
                         * Close Fixed Savings.
                         */
                        $fixed->current_balance = 0;

                        $fixed->status = 'completed';

                        $fixed->matured_at = now();

                        $fixed->withdrawn_at = now();

                        $fixed->save();

                        /*
                         * Record maturity in Fixed Savings ledger.
                         */
                        FixedSavingsTransaction::create([
                            'fixed_savings_id' =>
                                $fixed->id,

                            'user_id' =>
                                $fixed->user_id,

                            'company_id' =>
                                $fixed->company_id,

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
                                'Fixed Savings matured and funds released',

                            'metadata' => [
                                'destination_account_id' =>
                                    $fixed
                                        ->destination_account_id,

                                'principal_amount' =>
                                    round(
                                        (float)
                                        $fixed->total_deposited,
                                        2
                                    ),

                                'interest_amount' =>
                                    round(
                                        (float)
                                        $fixed
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
                                        $fixed
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
        FixedSaving $fixed,
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
                $fixed->company_id,

            'user_id' =>
                $fixed->user_id,

            'fixed_savings_id' =>
                $fixed->id,

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
        FixedSaving $fixed,
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
                $fixed->company_id,

            'user_id' =>
                $fixed->user_id,

            'fixed_savings_id' =>
                $fixed->id,

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
     * Fixed Savings no longer performs such a company
     * wallet movement, so those values are zero here.
     */
    protected function createCoreTransaction(
        FixedSaving $fixed,
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
            $fixed->company_id
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
             * No company wallet movement for Fixed Savings.
             */
            'company_balance_before' =>
                $companyBalanceBefore,

            'company_amount' =>
                $companyAmount,

            'company_balance_after' =>
                $companyBalanceAfter,

            'description' =>
                $description
                ?? 'Fixed Savings transaction',

            'system_description' =>
                $description
                ?? 'Fixed Savings transaction',

            'branch_id' =>
                $branchId,

            'parent_transaction_id' =>
                null,

            'transaction_status_id' =>
                1,

            'company_id' =>
                $fixed->company_id,

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