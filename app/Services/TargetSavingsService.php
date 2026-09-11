<?php

namespace App\Services;

use App\Models\TargetSaving;
use App\Models\TargetSavingsTransaction;
use App\Models\transactions;
use App\Models\wb_account_details;
use App\Models\company_wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TargetSavingsService
{
    /**
     * Transaction type IDs for Target Savings.
     *
     * 26 = Target Savings Deposit
     * 27 = Target Savings Early Withdrawal
     * 28 = Target Savings Maturity
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

            $companySettings = $user->company->setting;

            if (!$companySettings) {
                throw new RuntimeException(
                    'Company savings settings could not be found.'
                );
            }

            $withdrawDate = Carbon::parse($withdrawalDate)
                ->startOfDay();

            if ($withdrawDate->lte(now()->startOfDay())) {
                throw new RuntimeException(
                    'Withdrawal date must be a future date.'
                );
            }

            /*
             * Resolve the customer's first Savings account.
             *
             * Account ID is never accepted from the API request.
             */
            $account = $user->wb_account_details()
                ->whereHas('accountProduct', function ($query) {
                    $query->where('account_type', 'Savings');
                })
                ->first();

            if (!$account) {
                throw new RuntimeException(
                    'Savings account could not be found.'
                );
            }

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

                /*
                 * Snapshot company settings at creation time.
                 */
                'interest_rate' =>
                    $companySettings->target_savings_interest_rate ?? 0,

                'early_withdrawal_fee_percentage' =>
                    $companySettings
                        ->target_savings_early_withdrawal_fee_percentage ?? 0,

                'withdrawal_date' => $withdrawDate,

                'status' => 'active',
            ]);
        });
    }

    /**
     * Deposit money into Target Savings.
     *
     * Money is debited from the customer's saved Savings account.
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
             * Deposits are not allowed on/after the maturity date.
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
             * Use the account saved against the Target Savings.
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
             * Update Target Savings balance.
             */
            $target->current_balance = $balanceAfter;
            $target->total_deposited = $totalDeposited;
            $target->save();

            /*
             * Target Savings product ledger.
             */
            $targetTransaction = TargetSavingsTransaction::create([
                'target_savings_id' => $target->id,
                'user_id' => $target->user_id,
                'company_id' => $target->company_id,

                'type' => 'deposit',

                'amount' => $amount,
                'fee' => 0,

                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,

                'transaction_reference' =>
                    'TSD-' . strtoupper(Str::random(20)),

                'status' => 'successful',

                'description' =>
                    'Deposit into Target Savings',

                'metadata' => [
                    'source_account_id' =>
                        $target->source_account_id,
                ],
            ]);

            /*
             * Record the actual customer wallet movement
             * in the main Finova transactions table.
             */
            $coreTransaction = $this->createCoreTransaction(
                target: $target,
                transactionTypeId: self::TRANSACTION_TYPE_DEPOSIT,
                creditDebitTypeId: self::DEBIT,
                accountId: $target->source_account_id,
                amount: $amount,
                balanceBefore: (float) $debit['balance_before'],
                balanceAfter: (float) $debit['balance_after'],
                companyAmount: 0,
                companyBalanceBefore: 0,
                companyBalanceAfter: 0,
                description: 'Target Savings Deposit',
                reference: 'TSD-' . strtoupper(Str::random(20))
            );

            return [
                'target_savings' => $target->fresh(),

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
     * If the maturity date has arrived:
     *     Full balance goes to customer.
     *
     * If withdrawal is early:
     *     Fee is deducted.
     *     Net amount goes to customer.
     *     Fee goes to company wallet.
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
             * If today is the maturity date or later,
             * this is a maturity withdrawal.
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
             * Credit the customer wallet.
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
             * Credit early withdrawal fee to company wallet.
             */
            $companyCredit = null;

            if ($fee > 0) {

                $companyCredit =
                    $this->creditCompanyTargetSavingsFee(
                        $target->company_id,
                        $fee
                    );

                if (!($companyCredit['status'] ?? false)) {
                    throw new RuntimeException(
                        $companyCredit['message']
                        ?? 'Unable to credit company wallet with withdrawal fee.'
                    );
                }
            }

            /*
             * Preserve the target balance before closing it.
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
             * Target Savings product ledger.
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
             * Record the fee separately in the Target Savings ledger.
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

                            'company_id' =>
                                $target->company_id,

                            'company_wallet_credit' =>
                                $companyCredit,
                        ],
                    ]);
            }

            /*
             * Main Finova transaction record.
             *
             * Early withdrawal:
             * customer gets net amount
             * company gets the fee.
             *
             * Maturity:
             * customer gets full amount
             * company amount = 0.
             */
            $coreTransaction = $this->createCoreTransaction(
                target: $target,
                transactionTypeId:
                    $isMatured
                        ? self::TRANSACTION_TYPE_MATURITY
                        : self::TRANSACTION_TYPE_EARLY_WITHDRAWAL,

                creditDebitTypeId: self::CREDIT,

                accountId:
                    $target->destination_account_id,

                amount:
                    $customerAmount,

                balanceBefore:
                    (float) ($credit['balance_before'] ?? 0),

                balanceAfter:
                    (float) ($credit['balance_after'] ?? 0),

                companyAmount:
                    $fee,

                companyBalanceBefore:
                    (float) ($companyCredit['balance_before'] ?? 0),

                companyBalanceAfter:
                    (float) ($companyCredit['balance_after'] ?? 0),

                description:
                    $isMatured
                        ? 'Target Savings Maturity'
                        : 'Target Savings Early Withdrawal',

                reference:
                    $isMatured
                        ? 'TSM-' . strtoupper(Str::random(20))
                        : 'TSW-' . strtoupper(Str::random(20))
            );

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

                'company_credit' =>
                    $companyCredit,

                'target_savings_transaction' =>
                    $withdrawalTransaction,

                'fee_transaction' =>
                    $feeTransaction,

                'core_transaction' =>
                    $coreTransaction,
            ];
        });
    }

    /**
     * Process daily Target Savings interest.
     *
     * Interest rate is treated as an annual percentage
     * and accrued daily using a 365-day year.
     *
     * The processor catches up missed dates when required.
     *
     * Important:
     * Interest is stored inside Target Savings and does NOT
     * go into the customer's spendable wallet until maturity
     * or withdrawal.
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
            ->whereDate('withdrawal_date', '>', today())
            ->orderBy('id')
            ->chunkById(100, function ($targets) use ($processUntil) {

                foreach ($targets as $target) {

                    DB::transaction(function () use (
                        $target,
                        $processUntil
                    ) {

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
                         * First date that has not yet received interest.
                         */
                        $interestDate = $target->last_interest_date
                            ? Carbon::parse(
                                $target->last_interest_date
                            )->addDay()->startOfDay()
                            : $target->created_at
                                ->copy()
                                ->startOfDay();

                        /*
                         * Catch up all missing dates.
                         */
                        while ($interestDate->lte($processUntil)) {

                            /*
                             * Never award interest after the
                             * maturity date.
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
                             * Protect against duplicate interest entries.
                             */
                            $existing = TargetSavingsTransaction::query()
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
                                ->exists();

                            if ($existing) {

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
                             *
                             * Still advance the date to avoid
                             * repeatedly processing the same date.
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
                             * Record the interest in the Target
                             * Savings ledger.
                             */
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
                                        $interestDate->toDateString(),
                                ],
                            ]);

                            $interestDate->addDay();
                        }
                    });
                }
            });
    }

    /**
     * Automatically mature eligible Target Savings.
     */
    public function processMaturities(): void
    {
        TargetSaving::query()
            ->where('status', 'active')
            ->whereDate('withdrawal_date', '<=', today())
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

                        $amount = round(
                            (float) $target->current_balance,
                            2
                        );

                        /*
                         * Credit the customer's Savings wallet.
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
                         * Create the main Finova transactions record
                         * before closing the Target Savings.
                         */
                        $coreTransaction = null;

                        if ($amount > 0) {

                            $coreTransaction =
                                $this->createCoreTransaction(
                                    target: $target,
                                    transactionTypeId:
                                        self::TRANSACTION_TYPE_MATURITY,

                                    creditDebitTypeId:
                                        self::CREDIT,

                                    accountId:
                                        $target->destination_account_id,

                                    amount:
                                        $amount,

                                    balanceBefore:
                                        (float) $credit['balance_before'],

                                    balanceAfter:
                                        (float) $credit['balance_after'],

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
                         * Close the Target Savings.
                         */
                        $target->current_balance = 0;
                        $target->status = 'completed';
                        $target->matured_at = now();
                        $target->withdrawn_at = now();

                        $target->save();

                        /*
                         * Record maturity in the Target Savings ledger.
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
                                    'maturity',

                                'amount' =>
                                    $amount,

                                'fee' =>
                                    0,

                                'balance_before' =>
                                    $amount,

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
                                        $target->destination_account_id,
                                ],
                            ]);

                        /*
                         * Nothing is returned because this method
                         * is designed for the scheduler.
                         */
                    });
                }
            });
    }

    /**
     * Credit a company's wallet with an early withdrawal fee.
     */
    protected function creditCompanyTargetSavingsFee(
        int $companyId,
        float $amount
    ): array {
        if ($amount <= 0) {
            return [
                'status' => false,
                'message' => 'Invalid company fee amount.',
            ];
        }

        $companyWallet = company_wallet::query()
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->first();

        if (!$companyWallet) {
            return [
                'status' => false,
                'message' => 'Company wallet not found.',
            ];
        }

        $balanceBefore = round(
            (float) $companyWallet->balance,
            2
        );

        $balanceAfter = round(
            $balanceBefore + $amount,
            2
        );

        $companyWallet->balance = $balanceAfter;
        $companyWallet->save();

        return [
            'status' => true,
            'company_id' => $companyId,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ];
    }

    /**
     * Create the actual Finova transactions record.
     *
     * This is used for:
     *
     * 26 = Target Savings Deposit
     * 27 = Target Savings Early Withdrawal
     * 28 = Target Savings Maturity
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
         * Resolve CR/DR ledger configuration using the same
         * Finova helper used by your existing transactions.
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
         * Resolve branch from the account/user.
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