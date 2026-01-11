<?php
namespace App\Jobs;

use App\Models\company_wallet;
use App\Models\transactions;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CardNairaPartnerMaintenanceCron
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */

    public function handle()
    {
        \logInfo('CardNairaPartnerMaintenanceCron  job', 'Maintenance');

        try {

            foreach ($this->activeCardUsersStream() as $companyCardData) {

                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(23, $transaction->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                } else {
                    return ['status' => 0, 'message' => 'Transaction failed, set Bank Transfer Charge ledger for this transaction type in ledger settings !'];
                }

                $companyWallet = company_wallet::where('company_id', $companyCardData)->lockForUpdate()->first();

                logInfo(json_encode($companyWallet), "Company Wallet debitCompanyWithChargeforCard");

                $chargeAmount = 3000;

                $companyBalanceBefore        = $companyWallet->card_balance;
                $companyBalanceAfter         = $companyBalanceBefore - $chargeAmount;
                $companyWallet->card_balance = $companyBalanceAfter;
                $companyWallet->save();

                $companyChargeBalanceBefore = $chargeAmount;
                $companyChargeBalanceAfter  = $chargeAmount;

                $transactionRef2 = generateTransactionReference();

                transactions::create([
                    "company_balance_before" => $companyChargeBalanceBefore,
                    "company_amount"         => $chargeAmount,
                    "account_id"             => null,
                    "company_balance_after"  => $companyChargeBalanceAfter,
                    "description"            => "Card Maintenance Quartely",
                    "transaction_reference"  => $transactionRef2,
                    "balance_before"         => 0,
                    "amount"                 => 0,
                    "balance_after"          => 0,
                    "company_id"             => $companyCardData,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 23,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $companyCardData,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                \logInfo('Message: Success CardNairaPartnerMaintenanceCron maintenance', 'Line: Success');
            }

        } catch (\Exception $e) {
            \Log::error("CardNairaPartnerMaintenanceCron Job has error: " . $e->getMessage());
            throw $e;
        }

        return 0;
    }

/**
 * Stream users using a generator
 */
    private function activeCardCompanyStream(): \Generator
    {
        $query = UserCard::query()
            ->join('users', 'users.id', '=', 'user_cards.user_id')
            ->where('user_cards.currency_id', 1)
            ->select('users.company_id')
            ->distinct();

        foreach ($query->cursor() as $row) {
            yield $row->company_id;
        }
    }

}
