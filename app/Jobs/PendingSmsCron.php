<?php
namespace App\Jobs;

use App\Models\BranchWallet;
use App\Models\Temp_Charges;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PendingSmsCron
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
        \logInfo("Pending SMS Cron Job is Working currently", 'Cron as Started');
        $today  = date('Y-m-d');
        $return = 0;
        $day    = date('D');
        $date   = date('d');
        if ($day == 'Mon' || $date == 01) {
            //Code for monday
            $temp = Temp_Charges::where('status', 0)->groupBy('wb_account_detail_id')->where('date_due', '<=', $today)
                ->select(\DB::raw("SUM(`amount`) AS `amount`"), 'wb_account_detail_id', 'type', 'date_due', 'status', 'created_by', 'created_at', 'updated_at')
                ->get();
            try {
                if ($temp) {
                    if (count($temp) > 0) {
                        foreach ($temp as $tempData) {
                            // \logInfo("Pending SMS Cron Job is looping...",  'Looping through');
                            $userid = $tempData->wb_account_detail_id;
                            $amount = $tempData->amount;
                            // echo " amt = $amount | userid = $userid <br>";
                            $user = user_wallet::where('wb_account_detail_id', $userid)->first();
                            if ($user) {
                                if (isset($user->wb_account_details->user->company->setting->sms_charge_frequencie_id)) {
                                    $userbranch   = $user->wb_account_details->user->branch_id;
                                    $company_id   = $user->wb_account_details->user->company_id;
                                    $company      = $user->wb_account_details->user->company->setting->sms_charge_frequencie_id;
                                    $session      = User::where('company_id', $company_id)->first()->id;
                                    $sms_charge   = $tempData->amount;
                                    $BranchWallet = BranchWallet::where('branch_id', $userbranch)->first();
                                    if ($BranchWallet->balance >= $sms_charge) {
                                        $mybalance     = $user->balance;
                                        $user->balance = $user->balance - $sms_charge;
                                        if ($user->save()) {
                                            $amount = $tempData->amount;
                                            $BranchWallet->balance -= $amount;
                                            $BranchWallet->save();
                                            Temp_Charges::where('wb_account_detail_id', $userid)->update(['status' => 1]);
                                            $ledger_cr       = null;
                                            $ledger_dr       = null;
                                            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(33, $company_id);
                                            if ($ledger_Settings) {
                                                $ledger_cr = $ledger_Settings->ledger_cr;
                                                $ledger_dr = $ledger_Settings->ledger_dr;
                                            }
                                            $data = [
                                                "balance_before"         => $mybalance,
                                                "amount"                 => $sms_charge,
                                                "withdrawal_charge"      => 0,
                                                "description"            => 'Auto SMS Charge ' . date('Y-m-d h:i:s'),
                                                "account_id"             => $userid,
                                                "branch_id"              => $userbranch,
                                                "company_id"             => $company_id,
                                                "transaction_reference"  => generate_txnID(),
                                                "transaction_type_id"    => 33, //sms charge
                                                "credit_debit_type_id"   => 2,  //debit 2
                                                "transaction_remarks_id" => 2,  //success 2
                                                "balance_after"          => $user->balance,
                                                "created_date"           => date('Y-m-d'),
                                                "created_by"             => $session,
                                                "ledger_cr"              => $ledger_cr,
                                                "ledger_dr"              => $ledger_dr,
                                            ];
                                            if ($sms_charge > 0) {
                                                $return = transactions::create($data);
                                            }
                                        }
                                    }
                                }
                            } else {
                                \logInfo("wptable ID $userid not link to a wallet address", 'Looping failure');
                            }
                        }
                    }
                }
            } catch (Exception $e) {

                Log::error("Pending SMS  Cron Job has error: " . $e->getMessage() . $e->getLine());
                throw $e;
            }
        }
        return 0;

    }

}
