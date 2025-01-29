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

class savingscron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'savings:name';

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
    public function handle()
    {
        \logInfo('Savings cron job',  'Savings');
        $today = date("Y-m-d");
        try{
            $user = User::where(['user_type_id'=>1,'status_id'=>1])->get();
            foreach($user as $userData){
                $wb_account_detailsData = wb_account_details::where('virtual_accnum',$userData->virtual_account)->first();
                if($wb_account_detailsData){
                    if(isset($wb_account_detailsData->user)){
                        if(!isset($wb_account_detailsData->accountType)){ continue;}
                        $wbdataType=$wb_account_detailsData->accountType;
                        if(!isset($wbdataType->frequency->frequency)){
                            continue;
                        }
                        $frequency = strtolower($wbdataType->frequency->frequency);
                        $interest = $wbdataType->interest_rate;

                        if($interest >0){
                            if(!isset($wb_account_detailsData->user)){ continue;}
                            $user = $wb_account_detailsData->user;
                            $createdBy = User::where('company_id',$user->company_id)->first();
                            if(!isset($createdBy)){ continue;}
                            $duration = $wbdataType->duration;
                            if(isset($wb_account_detailsData->user_wallet)){
                                $balanceBefore = $bal = $wb_account_detailsData->user_wallet->balance;
                                $lastBalanceUpdated = $wb_account_detailsData->user_wallet->updated_at;

                                if($duration){
                                    $txn = transactions::where(['account_id' => $wb_account_detailsData->id,'credit_debit_type_id'=>2])
                                    ->whereNotIn('transaction_type_id', [42, 33, 62, 41, 34,17,58])
                                    ->orderBy('id', 'desc')
                                    ->first();
                                    $proceed =0;
                                    $dateDiff=0;
                                    if($txn){
                                        $dateDiff =$withdraw_diff = date("Y-m-d", strtotime($txn->created_date . "+$duration $frequency"));
                                        if($withdraw_diff < date('Y-m-d')){
                                            $proceed =1;
                                            // echo "Proceed looping";
                                        }
                                    }
                                    // else{
                                    //     if($balanceBefore > 1){
                                            $txn = transactions::where(['account_id' => $wb_account_detailsData->id,'credit_debit_type_id'=>1])
                                            ->where('transaction_type_id', 59)
                                            ->orderBy('id', 'desc')->first();
                                            if($txn){
                                                $dateDiff = $last30days = date("Y-m-d", strtotime($txn->created_date . "+$duration $frequency"));
                                                if($last30days < date('Y-m-d')){
                                                    $proceed =1;
                                                    // echo "Proceed looping";
                                                }else{
                                                    $proceed =0;

                                                }
                                            }else{
                                                $dateDiff = $withdraw_diff = date("Y-m-d", strtotime($lastBalanceUpdated . "+$duration $frequency"));
                                                if($withdraw_diff < date('Y-m-d')){
                                                    $proceed =1;
                                                }
                                            }
                                    //     }
                                    // }

                                    if($proceed){
                                        $interestReturn = ($interest/100)*$bal;
                                        if($interestReturn == 0){
                                            continue;
                                        }
                                        $lastPayment =null;
                                        $ExistingPending = pendingCustomerSavingInterest::where('wb_account_id',$wb_account_detailsData->id)->orderBy('id','desc')->first();
                                        if($ExistingPending){

                                            $lastPayment=date("Y-m-d", strtotime($ExistingPending->mark_date . "+$duration $frequency"));
                                            if($lastPayment < date('Y-m-d')){
                                                    echo "last payment lastdate: $ExistingPending->mark_date duration: $duration frequency:$frequency Exist Saving $wb_account_detailsData->id  $interestReturn ".date('Y-m-d');


                                                $save = new pendingCustomerSavingInterest;
                                                $save->wb_account_id = $wb_account_detailsData->id;
                                                $save->amount =$interestReturn;
                                                $save->status ='pending';
                                                $save->mark_date =date('Y-m-d');
                                                $save->save();
                                            }
                                        }else{
                                            echo "New Saving $wb_account_detailsData->id  $interestReturn ".date('Y-m-d');

                                            $save = new pendingCustomerSavingInterest;
                                            $save->wb_account_id = $wb_account_detailsData->id;
                                            $save->amount =$interestReturn;
                                            $save->status ='pending';
                                            $save->mark_date =date('Y-m-d');
                                            $save->save();
                                        }
                                        \logInfo('Saving save Done',  'Savings');

                                    }else{
                                        \logInfo('Cannot proceed: date diff'.$dateDiff,  'Savings');

                                    }
                                }else{
                                \logInfo('No  Duration:'.$duration,  'Savings');
                                }
                            }else{
                                \logInfo('No wallet for :'.$wb_account_detailsData->id,  'Savings');

                            }
                        }else{
                        \logInfo('Interest is 0  product ID:'.$wbdataType->id,  'Savings');

                        }
                    }else{
                        \logInfo('no account for '.$wb_account_detailsData->id,  'Savings');
                    }
                }
            }
        }catch(Exception $e){
            \logInfo('Message: '.$e->getMessage(),  ' Savings pending Failed at Line: '.$e->getLine());
        }
        return 0;
    }
}
