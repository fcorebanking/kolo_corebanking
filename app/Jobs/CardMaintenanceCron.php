<?php
namespace App\Jobs;

use App\Models\transactions;
use App\Models\User;
use App\Models\UserCard;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CardMaintenanceCron
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
        \logInfo('CardMaintenanceCron  job', 'Maintenance');

        try {

            foreach ($this->activeCardUsersStream() as $userCardData) {

                $wb_account_detailsData = wb_account_details::where('user_id', $userCardData->user_id)->first();
                $user_wallet            = user_wallet::where('wb_account_detail_id', $wb_account_detailsData->id)->first();

                logInfo(json_encode($user_wallet), "User Wallet debitUserWithChargeforCard");

                $user = User::where('id', $userCardData->user_id)->first();

                logInfo(json_encode($user), "User Details forCard");

                $createdBy              = $user->company_id;
                $card_withdrawal_charge = $this->getCompanySettingsCardMaintenanceFee($user->company_id);
                $transactionId          = generate_txnID();

                $balanceBefore = $user_wallet->balance;
                $balanceAfter  = $balanceBefore - $card_withdrawal_charge;

                $user_wallet->balance = $balanceAfter;
                $user_wallet->save();

                $session         = User::where('company_id', $user->company_id)->first()->id;
                $amount          = $card_withdrawal_charge;
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(23, $user->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $data = [
                    "balance_before"         => $balanceBefore,
                    "amount"                 => $amount,
                    "description"            => "Card Maintenance Fee",
                    "account_id"             => $runninginvestment->wb_accountdetails_id,
                    "branch_id"              => $userAccount->user->branch_id,
                    "company_id"             => $userAccount->user->company_id,
                    "transaction_reference"  => $transactionId,
                    "transaction_type_id"    => 23,
                    "credit_debit_type_id"   => 1,
                    "transaction_remarks_id" => 2, //success 2
                    "balance_after"          => $balanceAfter,
                    "created_date"           => date("Y-m-d"),
                    "created_by"             => $session,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ];
                $transaction = transactions::create($data);

                \logInfo('Message: Success Card maintenance', 'Line: Success');
            }

        } catch (\Exception $e) {
            \Log::error("CardMaintenanceCron Job has error: " . $e->getMessage());
            throw $e;
        }

        return 0;
    }

/**
 * Stream users using a generator
 */
    private function activeCardUsersStream(): \Generator
    {
        $query = UserCard::where('currency_id', 1)
            ->select('id', 'user_id'); // fetch only fields needed

        foreach ($query->cursor() as $cardUser) {
            yield $cardUser;
        }
    }

    private function getCompanySettingsCardMaintenanceFee($companyId)
    {
        $company_settings_data = company_settings::where('company_id', '=', $companyId)->get();
        foreach ($company_settings_data as $company_settings_dataid);
        $company_settings_id = $company_settings_dataid->id;
        $company_settings    = company_settings::findorfail($company_settings_id);

        return $company_settings->physical_card_maintenance_charge ?? 0;
    }

}
