<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\wb_account_details;
use App\Models\transactions;
use App\Models\user_wallet;
use App\Models\User;
use Carbon\Carbon;
use App\Models\BranchWallet;
use App\Models\pendingCustomerSavingMaintenance;
ini_set('memory_limit', '500000M');

class maintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        \logInfo('Maintenance cron job',  'Maintenance');

        $today = date("Y-m-d");
        try{
            $user = User::where(['user_type_id'=>1,'status_id'=>1])->get();
            foreach($user as $userData){
                $wb_account_detailsData = wb_account_details::where('virtual_accnum', $userData->virtual_account)
    ->where(function ($query) use ($today) {
        $query->where('next_maintenance_date', '<', $today)
            ->orWhereNull('next_maintenance_date');
    })
    ->first();
                if($wb_account_detailsData){
                // $wbCurrentID = $wb_account_detailsData->id;
                if(!isset($wb_account_detailsData->accountType)){

                    continue;}

                $wbdataType=$wb_account_detailsData->accountType;
                    if(!isset($wb_account_detailsData->user)){

                         continue;}

                    $user = $wb_account_detailsData->user;
                    $createdBy = User::where('company_id',$user->company_id)->first();
                    if(!$createdBy){ continue;}
                    // $frequency =(isset($wbdataType->frequency->frequency))? strtolower($wbdataType->frequency->frequency):'day';
                    $frequency ='day';

                    $maintenance_charges = $wbdataType->maintenance_charges;
                    if($maintenance_charges > 0){
                        $duration = $wbdataType->maintenace_duration;
                        if($duration <= 0){

                            continue;
                        }

                        if(isset($wb_account_detailsData->user_wallet)){


                                $txn = transactions::where(['account_id'=>$wb_account_detailsData->id,'transaction_type_id'=>58])->orderBy('id','desc')->first();
                                $proceed =0;
                                if($txn){
                                    $withdraw_diff = date("Y-m-d", strtotime($txn->created_at . "+$duration $frequency"));
                                    if($withdraw_diff < date('Y-m-d')){
                                        $proceed =1;
                                    }
                                }else{
                                    $withdraw_diff = date("Y-m-d", strtotime($user->created_at . "+$duration $frequency"));
                                    if($withdraw_diff < date('Y-m-d')){
                                        $proceed =1;
                                    }
                                }

                                if($proceed){

                                    $lastPayment =null;
                                    $ExistingPending = pendingCustomerSavingMaintenance::where('wb_account_id',$wb_account_detailsData->id)->orderBy('id','desc')->first();
                                    if($ExistingPending){
                                        $lastPayment=date("Y-m-d", strtotime($ExistingPending->mark_date . "+$duration $frequency"));
                                        if($lastPayment < date('Y-m-d')){

                                            $save = new pendingCustomerSavingMaintenance;
                                            $save->wb_account_id = $wb_account_detailsData->id;
                                            $save->amount =$maintenance_charges;
                                            $save->status ='pending';
                                            $save->mark_date =date('Y-m-d');
                                            $save->save();
                                        }
                                    }else{
                                        $save = new pendingCustomerSavingMaintenance;
                                        $save->wb_account_id = $wb_account_detailsData->id;
                                        $save->amount =$maintenance_charges;
                                        $save->status ='pending';
                                        $save->mark_date =date('Y-m-d');
                                        $save->save();
                                    }
                                    \logInfo('Message: Success maintenance',  'Line: Success');

                                }else{
                                    echo "Exiting \n";
                                }

                        }else{
                            echo "fail wallet-- ** $duration";
                        }
                    }else{
                    }

            }
        }
        }catch(Exception $e){
            \logInfo('Message: '.$e->getMessage(),  'Line: '.$e->getLine());

        }
        return 0;

    }
}
