<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduleEsusuProduct;
use App\Models\ScheduleEsusuRepayment;
use App\Models\ScheduleEsusu as ScheduleEsusuModel;
use App\Models\User;
use App\Models\StaffWallet;
use App\Models\transactions;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;


use App\Models\wb_account_details;
class scheduleEsusu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduleesusu:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "Welcome";
        try{
        DB::beginTransaction();

        $esusu = ScheduleEsusuModel::where('status','active')->get();
        if($esusu){
        echo "Esusu";

            foreach($esusu as $selectEsusu){
                if($selectEsusu->started_debiting_at < date('Y-m-d')){

                    $checkLastPayment = ScheduleEsusuRepayment::where(['schedule_esusu_id'=>$selectEsusu->id,'type'=>'credit','started_debiting_at'=>$selectEsusu->started_debiting_at])->orderBy('id','desc')->first();
                    if ($checkLastPayment && Carbon::parse($checkLastPayment->created_at)->isToday()) {
                        // The last payment was made today
                        // Your logic here
                        echo "already <br>";
                        // continue;
                    } else {
                        // Get the created_at date
                        $createdAt = Carbon::parse($checkLastPayment->created_at??$selectEsusu->started_debiting_at);

                        // Get the current date
                        $today = Carbon::now();

                        // Calculate the difference in days
                        $differenceInDays = $createdAt->diffInDays($today);


                        // The last payment was not made today
                        $amount = $selectEsusu->amount_paid * $differenceInDays;
                        // Your logic here
                        $wb_account_details = wb_account_details::where(['user_id' => $selectEsusu->user_id])->with('user')->first();
                        $balanceBefore = $wb_account_details->user_wallet->balance;
                        $ledg =getTransactionIdByName('FoodStash Deposit');
                        $get_ledger_setting_Commission = get_CR_DR_TXN_Type_Ledger($ledg->id, $selectEsusu->company_id);
                        if (!$get_ledger_setting_Commission) {
                            DB::rollBack();
                            continue;
                        }
                        $balanceAfter =$balanceBefore - $amount;
                        $data = [
                            "balance_before" => $balanceBefore,
                            "amount" =>  $amount,
                            "description" => "Schedule Esusu Deposit",
                            "account_id" => $wb_account_details->id,
                            "branch_id" => $wb_account_details->user->branch_id,
                            "transaction_status_id" => 1,
                            "transaction_reference" => generate_txnID(),
                            "transaction_type_id" => $ledg->id, //debit 7
                            "credit_debit_type_id" => 2, //debit 2
                            "transaction_remarks_id" => 2, //success 2
                            "balance_after" => $balanceAfter,
                            "created_date"  => date('Y-m-d'),
                            "created_by" => $selectEsusu->user_id,
                            "instrumentCode" => null,
                            "ledger_cr" => $get_ledger_setting_Commission->ledger_cr, //
                            "ledger_dr" => $get_ledger_setting_Commission->ledger_dr,
                            "initiator" => null,
                        ];
                        $data['userParamType'] = 2;
                        $data['userId'] = $wb_account_details->id;
                        $data['remark'] = 2;
                        $data['chargeamt'] = 0;
                        $data['companyVault'] = null;
                        $data['sendSMS'] = 0;
                        $CreditCustomer = DebitCreditcustomer($data);
                        if (!$CreditCustomer['status']) {
                            echo "fail";
                            DB::rollBack();

                            continue;
                        }
                        ScheduleEsusuRepayment::create(['num_of_days'=>1,'company_id'=>$selectEsusu->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>$selectEsusu->user_id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$selectEsusu->id,'amount'=>$amount,'type'=>'credit']);
                        $credit = ScheduleEsusuRepayment::where(['company_id'=>$selectEsusu->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>$selectEsusu->user_id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$selectEsusu->id,'type'=>'credit'])->sum('amount');
                        $debit = ScheduleEsusuRepayment::where(['company_id'=>$selectEsusu->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>$selectEsusu->user_id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$selectEsusu->id,'type'=>'debit'])->sum('amount');
                        $selectEsusu->wallet = $credit +$debit;
                        $selectEsusu->save();
                        echo "complete";
                        DB::commit();
                    }
                }
                if($selectEsusu->end_date <= date('Y-m-d')){
                    echo "start <br>";
                    $wb_account_details = wb_account_details::where(['user_id' => $selectEsusu->user_id])->with('user')->first();
                    $amount =$selectEsusu->wallet;
                    $circle_out_charge =($selectEsusu->product->circle_out_charge/100)* $amount;
                    ScheduleEsusuRepayment::create(['num_of_days'=>0,'company_id'=>$selectEsusu->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>$selectEsusu->user_id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$selectEsusu->id,'amount'=>$amount,'type'=>'debit']);
                    $selectEsusu->wallet = 0;
                    $selectEsusu->status = 'complete';
                    $selectEsusu->save();
                        $balanceBefore = $wb_account_details->user_wallet->balance;
                        $ledg =getTransactionIdByName('FoodStash withdraw');
                        $get_ledger_setting_Commission = get_CR_DR_TXN_Type_Ledger($ledg->id, $selectEsusu->company_id);
                        if (!$get_ledger_setting_Commission) {
                            DB::rollBack();
                            echo "Transaction fail";
                        }
                        $balanceAfter =$balanceBefore + $amount;
                        $data = [
                            "balance_before" => $balanceBefore,
                            "amount" =>  $amount,
                            "description" => "FoodStash Withdrawal",
                            "account_id" => $wb_account_details->id,
                            "branch_id" => $wb_account_details->user->branch_id,
                            "transaction_status_id" => 1,
                            "transaction_reference" => generate_txnID(),
                            "transaction_type_id" => $ledg->id, //debit 7
                            "credit_debit_type_id" => 1, //debit 2
                            "transaction_remarks_id" => 2, //success 2
                            "balance_after" => $balanceAfter,
                            "created_date"  => date('Y-m-d'),
                            "created_by" => $selectEsusu->user_id,
                            "instrumentCode" => null,
                            "ledger_cr" => $get_ledger_setting_Commission->ledger_cr, //
                            "ledger_dr" => $get_ledger_setting_Commission->ledger_dr,
                            "initiator" => null,
                        ];
                        $data['userParamType'] = 2;
                        $data['userId'] = $wb_account_details->id;
                        $data['remark'] = 2;
                        $data['chargeamt'] = 0;
                        $data['companyVault'] = null;
                        $data['sendSMS'] = 0;

                        $CreditCustomer = DebitCreditcustomer($data);
                        echo "esusu break ***";

                        if (!$CreditCustomer['status']) {
                            // return $CreditCustomer;
                            DB::rollBack();

                            echo "fail";
                            continue;
                        }else{
                            if($circle_out_charge >0){
                            $ledg_charge =getTransactionIdByName('Esusu Accum. Charge');
                            $data['amount'] = $circle_out_charge;
                            $data["credit_debit_type_id"] =2;
                            $data["description"] = "Schedule Esusu Charges";
                            $data["transaction_type_id"] = $ledg_charge->id;
                            $data["balance_before"] = $balanceAfter;
                            $data["balance_after"] = $balanceAfter - $circle_out_charge;
                            $CreditCustomerStaff = DebitCreditcustomer($data);
                            if (!$CreditCustomerStaff['status']) {
                                DB::rollBack();
                                // return $CreditCustomer;
                                echo "fail to credit company";
                                continue;
                            }
                        }
                            echo "$selectEsusu->created_by";
                            if($selectEsusu->product->agent_commission){

                            $created_by = User::where('id',$selectEsusu->created_by)->first();
                            if(!isset($created_by->staff_wallet->commission)){
                                echo "no se";
                                $created_by->staff_wallet = StaffWallet::create([
                                    'accountno'=>rand(1000000000, 9999999999),
                                    'balance'=>0,
                                    'user_id'=>$selectEsusu->created_by,
                                    'created_by'=>$selectEsusu->created_by,
                                    'modified_by'=>$selectEsusu->created_by,
                                    'commission'=>0,
                                ]);
                                if(!$created_by->staff_wallet){
                                    DB::rollBack();
                                }
                            }
                                $teller_commission = ($selectEsusu->product->agent_commission/100)* $amount;
                                $tellerbalance = $created_by->staff_wallet->commission + $teller_commission;
                                $dataray =[
                                    "balance_before" => $created_by->staff_wallet->commission,
                                    "amount" => $teller_commission,
                                    "description" => "FoodStash Commission",
                                    "branch_id" => $created_by->branch_id,
                                    "transaction_reference" => generate_txnID(),
                                    "transaction_type_id" => 20, //teller_commission
                                    "transaction_status_id" => 1,
                                    "credit_debit_type_id" => 2, //debit 2
                                    "transaction_remarks_id" => 2, //success 2
                                    "balance_after" => $tellerbalance,
                                    "created_date"  => date('Y-m-d'),
                                    "created_by" => $created_by->id,
                                    "ledger_cr" => $get_ledger_setting_Commission->ledger_cr, //
                                    "ledger_dr" => $get_ledger_setting_Commission->ledger_dr,
                                ];
                                if(transactions::create($dataray)){
                                    StaffWallet::where(['id' => $created_by->staff_wallet->id])->update(['commission' => $tellerbalance]);
                                }
                            }

                            DB::commit();


                        }
                }

            }
        }
        return Command::SUCCESS;
    }catch(Exception $e){
        DB::rollBack();
        }
    }

}
