<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\wb_account_details;
use App\Models\transactions;
use App\Models\user_wallet;
use App\Models\User;
use App\Models\BranchWallet;
use App\Models\pendingCustomerSavingInterest;
ini_set('memory_limit', '500000M');

class SavingpackCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'savingpack:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to credit interest users and debit maintanace fee';

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
        \logInfo('Savings charge cron job',  'Savings');

            $savingPending =pendingCustomerSavingInterest::where('status','pending')->get();
                try{
                    if($savingPending){
                        foreach($savingPending as $savingPendingData){
                    $wb_account_detailsData = $savingPendingData->wb_account;
                    $wbdataType=$wb_account_detailsData->accountType;
                    $balanceBefore = $bal = $wb_account_detailsData->user_wallet->balance??0;
                    $interest = $wbdataType->interest_rate;
                    $user = $wb_account_detailsData->user;
                    $createdBy = User::where('company_id',$user->company_id)->first();
                    if($bal > 0 && $interest >0){
                        if($bal  >= $savingPendingData->amount){
                            echo "enter amount looping".$wb_account_detailsData->id;
                            $interestReturn = ($interest/100)*$bal;
                            $balanceAfter = ($balanceBefore + $interestReturn);
                            $previousMonthName = date('F', strtotime('-1 month'));
                            $desc = "Savings Deposit interest Return for ".$previousMonthName;
                            $transactionId = generate_txnID();
                            $today=date('Y-m-d');
                            $ledger_cr =null;
                            $ledger_dr =null;
                            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(59, $user->company_id);
                            if($ledger_Settings){
                                $ledger_cr = $ledger_Settings->ledger_cr;
                                $ledger_dr = $ledger_Settings->ledger_dr;
                            }
                            $Creditdata = [
                                "balance_before" => $balanceBefore,
                                "amount" =>  $interestReturn,
                                "description" => $desc,
                                "account_id" => $wb_account_detailsData->id,
                                "branch_id" => $user->branch_id,
                                "transaction_status_id" => 1,
                                "transaction_reference" =>$transactionId,
                                "transaction_type_id" => 59, //debit 7
                                "credit_debit_type_id" => 1, //debit 2
                                "transaction_remarks_id" =>2, //success 2
                                "balance_after" => $balanceAfter,
                                "created_date"  => $today,
                                "created_by" => $createdBy->id,
                                "ledger_cr"=>$ledger_cr,
                                "ledger_dr"=>$ledger_dr,
                            ];
                            $transaction = transactions::create($Creditdata);
                            user_wallet::where('wb_account_detail_id',$wb_account_detailsData->id)->update(['balance'=>$balanceAfter]);
                            $BranchWallet =BranchWallet::where('branch_id',$user->branch_id)->first();
                            $BranchWallet->balance += $interestReturn;
                            $BranchWallet->save();
                            $savingPendingData->status ='paid';
                            $savingPendingData->save();
                            fundVault(['type' => 'product_interest_vault', 'value' => (0-$interestReturn), 'company_id' => $createdBy->company_id, 'created_by' => $createdBy->id, 'branch_id' => $createdBy->branch_id, 'transaction_id' => $transaction->id]);
                            sendSMS(['txnid' => $transaction->id, 'userid' => $wb_account_detailsData->id], "Credit Amt:$interestReturn  Desc: $desc Avail Bal:$balanceAfter Date:$today Ref:$transactionId", false, true, 'Credit Transaction');
                        }else{
                            echo "\n insufficient balance";
                        }
                    }

            }
        }
    }catch(Exception $e){
        \logInfo($e->getMessage(),  'Savings Pack Failed at '.$e->getLine());
    }

        return 0;
    }
}
