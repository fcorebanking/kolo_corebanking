<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompanySettlement;
use App\Models\User;
use App\Models\company_wallet;
use App\Models\transactions;
use Exception;
use Illuminate\Support\Facades\DB;
ini_set('memory_limit', '500000M');

class CompanySettimentThreshold extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CompanySettimentThreshold:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Company Settiment Threshold';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            \logInfo("Settlement Cron Job");
            if(env('APP_ENV') != 'local'){
                $CompanySettlement = CompanySettlement::where('minimum_balance_threshold', '>', 0)->orWhere('maximum_balance', '>', 0)->get();
                if ($CompanySettlement) {

                    foreach ($CompanySettlement as $CompanySettlementData) {

                        $auth = User::where('company_id', $CompanySettlementData->company->id)->first();
                        if ($CompanySettlementData->company->wallet->balance <= $CompanySettlementData->minimum_balance_threshold) {
                            //email
                            $message = "WBA Lite, You just reached your minimum Balance threshold, top up to avoid failed eTransactions";
                            $smsstate = sendSMS($auth->id, $message, true, false, 'Minimum Balance Threshold', '', 0, false, 2);
                        }
                        if ($CompanySettlementData->company->wallet->balance > $CompanySettlementData->maximum_balance) {
                            //transfer
                            $amount_to_transfer = $CompanySettlementData->company->wallet->balance - $CompanySettlementData->maximum_balance;
                            $amount_to_transfer =intval($amount_to_transfer);
                            $amount_to_transfer += 30;
                            $company_wallet = company_wallet::where('company_id', $auth->company_id)->first();
                            if (!$company_wallet) {
                                return response()->json(['status' => 0, 'message' => 'Error!'], 400);
                            }
                            if ($company_wallet->balance < $amount_to_transfer) {
                                return response()->json(['status' => 0, 'message' => 'Error(409) Insufficient Fund!'], 400); //useless check
                            }
                            $company_wallet->balance -= $amount_to_transfer;
                            DB::beginTransaction();
                            $desc =  'WBALite, Maximum Balance Auto Sweep Settlement Transaction, Debit of ' . $amount_to_transfer;
                            $transactionId = generate_txnID();
                            $virtual_accnum = $CompanySettlementData->accountNumber;
                            $customerName = $auth->first_name;
                            $bankcode = $CompanySettlementData->bank->bank_code;
                            $ledger_cr =null;
                            $ledger_dr =null;
                            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(98, $auth->company_id);
                            if($ledger_Settings){
                                $ledger_cr = $ledger_Settings->ledger_cr;
                                $ledger_dr = $ledger_Settings->ledger_dr;
                            }
                            $data_company = [
                                "company_balance_before" => $CompanySettlementData->company->wallet->balance,
                                "company_amount" => $amount_to_transfer,
                                "company_balance_after" => $company_wallet->balance,
                                "balance_before" => 0,
                                "balance_after" => 0,
                                "amount" => 0,
                                "withdrawal_charge" => 0,
                                "description" => $desc,
                                "company_id" => $CompanySettlementData->company->id,
                                "branch_id" => $auth->branch_id,
                                "transaction_reference" => $transactionId,
                                "transaction_type_id" => 98, //debit 7
                                "credit_debit_type_id" => 2, //debit 2
                                "transaction_remarks_id" => 1, //success 2
                                "created_date"  => date('Y-m-d H:i:s'),
                                "created_by" => $auth->id,
                                "ledger_dr"=>$ledger_dr,
                                "ledger_cr"=>$ledger_cr
                            ];
                            $transactionCompany = transactions::create($data_company);

                            $amount_to_transfer -= 30;
                            $company_wallet->save();
                            $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => 'https://safe.wbalite.com/api/v1/ussd-apis/bank-transfer',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS => '{
                                "amount": "' . $amount_to_transfer . '",
                                "transaction_reference": "' . $transactionId . '",
                                "destination_account": "' . $virtual_accnum . '",
                                "receipient_name": "' . $customerName . '",
                                "bank_code": "' . $bankcode . '",
                                "description": "' . $desc . '",
                                "api_key": "8RuvwDLa8sxIpvLNKscIa3JZZIJKsyQZIbirFHv6HtQwim4fMPqNDiaki5aw9UlOm2BlttJ9Rl4xQbbJjzRFbEblmvajTH5VVtpMANiEziQZzU3ljsu16YbEF1zgX5n0"
                            }',
                                CURLOPT_HTTPHEADER => array(
                                    'Content-Type: application/json'
                                ),
                            ));
                            $response = curl_exec($curl);
                            curl_close($curl);
                            $cur = json_decode($response);
                            if ($cur->status) {
                                $transactionCompany->transaction_remarks_id = 2;
                                $transactionCompany->save();
                                sendSMS($auth->id, $desc, true, false, 'Auto Settlement', '', 0, false, 2);
                            }
                            DB::commit();
                        }
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            \logInfo("Company threshold",  ["status" => 0, "message" => $e->getMessage() . $e->getLine()]);
        }
        return 0;
    }
}
