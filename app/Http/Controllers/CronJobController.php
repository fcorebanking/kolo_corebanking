<?php

namespace App\Http\Controllers;

use App\Models\Cron_Job;
use App\Models\DomAccount;
use App\Models\wb_account_details;
use App\Models\transactions;
use App\Http\Requests\StoreCron_JobRequest;
use App\Http\Requests\UpdateCron_JobRequest;
use App\Models\Investmentbooking;
use App\Models\user_wallet;
use App\Models\Temp_Charges;
use App\Models\BranchWallet;
use App\Models\User;
use App\Models\loan;
use App\Models\payLoanPenalty;
use App\Models\LoanRepayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

class CronJobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        //  $this->reg_cron_dom_job();
        //  $this->cal_investment_plan();
        //$this->monthlySavingsFee();
        // $this->loanCron();
        $this->Temp_sms_cronjob();
    }
    public function index()
    {
        //$this->reg_cron_dom_job();
        //$this->cal_investment_plan();
        // $this->Temp_sms_cronjob();
        //$this->birthday_notifier();
    }
    public function reg_cron_dom_job()
    {
        echo "dom .. ";
        $return = 0;

        $allUser = User::where('users.user_type_id', 1)->get();
        foreach ($allUser as $allUserdata) {
            $Wpdetails_Data = wb_account_details::where('user_id', $allUserdata->id)->get();
            foreach ($Wpdetails_Data as $wipedata) {
                $userdata = User::find($wipedata->user_id);
                $accountID = $wipedata->id;
                $insert_ray = ['wb_account_detail_id' => $accountID, 'status' => 0];
                $sessiondata = User::where('company_id',  $userdata->company_id)->first();
                $session = $sessiondata->id;

                $company_dom = $sessiondata->company->setting->dom_account_duration;

                if (DomAccount::where($insert_ray)->count() == 0) {
                    $user_reg_date = $wipedata->user->updated_at;
                    $today = date('Y-m-d H:i:s');

                    $difdate =  diff_btw_date($user_reg_date, $today);

                    if ($difdate >= $company_dom) {

                        $querytxn = transactions::where('transactions.account_id', '=',  $accountID)
                            ->orderBy('transactions.id', 'desc')->take(1)->first();

                        if ($querytxn) {
                            $difdate2 =  diff_btw_date($querytxn->created_date, $today);

                            if ($difdate2 >= $company_dom) {
                                $insert_ray['created_by'] = $session;
                                $insert_ray['uuid'] = uniqid();
                                DomAccount::create($insert_ray);
                                $userdata->status_id = 8;
                                $userdata->save();
                                $accno = $userdata->virtual_account;
                                $todayx = date('Y-m-d');
                                $msg = "We've noticed that your account ID $accno has been inactive for over $difdate2 consecutive days. According to our terms of service, your account is now marked as dormant ($todayx). From the moment your account has been marked as dormant, you'll have $company_dom days to stop the process.";
                                //$return = sendSMS($wipedata->id, $msg, true, true, 'Dorm Account');
                            }
                        }
                    }
                }
            }
        }
        return $return;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    //investment cron-job
    public function cal_investment_plan()
    {
        echo "ivestment ..";
        $allinvestment = Investmentbooking::where('status', 1)->get();
        $complete = 3;
        $running = 1;
        $return = '';
        foreach ($allinvestment as $runninginvestment) {
            $lastupdate = date_format($runninginvestment->updated_at, "Y-m-d");
            if ($lastupdate < date("Y-m-d")) {
                $plan_interest = $runninginvestment->plan->interest_rate;
                $investamount =  $runninginvestment->amount;
                $cash_precent = ($plan_interest / 100) * $investamount;
                $date_started = $runninginvestment->start_date;
                $today = date("Y-m-d");

                $end_date = $runninginvestment->end_date;
                if ($end_date > $today) {
                    $days_so_far = diff_btw_date($date_started, $today);
                    $status = $running;
                } else {
                    $days_so_far = diff_btw_date($date_started, $end_date);
                    $status = $complete;
                }

                $duration = $runninginvestment->duration;
                $expected_cash = $investamount + $cash_precent;

                $amt_per_day = $expected_cash / $duration;

                $amt_earned = $days_so_far * $amt_per_day;
                $wallet = $days_so_far * ($cash_precent / $duration);

                $investment_update = Investmentbooking::findorfail($runninginvestment->id);

                $balance =  $amt_earned;
                $investment_update->update([

                    'wallet' => $wallet,
                    'balance' => $balance,
                    'expected_balance' => $expected_cash,

                    'status' => $status,
                ]);
                if ($status == $complete) {
                    $transactionId = generate_txnID();
                    $userAccount = wb_account_details::find($runninginvestment->wb_accountdetails_id);
                    $balanceBefore = $userAccount->user_wallet->balance;
                    $balanceAfter = $balanceBefore +  $investment_update->wallet;
                    $session = User::where('company_id', $userAccount->user->company_id)->first()->id;
                    $amount = $investment_update->wallet;
                    $data = [
                        "balance_before" => $balanceBefore,
                        "amount" =>  $investment_update->wallet,
                        "description" => "Return of Investment ",
                        "account_id" => $runninginvestment->wb_accountdetails_id,
                        "branch_id" => $userAccount->user->branch_id,
                        "company_id" => $userAccount->user->company_id,
                        "transaction_reference" => $transactionId,
                        "transaction_type_id" => 21,
                        "credit_debit_type_id" => 1,
                        "transaction_remarks_id" => 2, //success 2
                        "balance_after" => $balanceAfter,
                        "created_date"  => date("Y-m-d"),
                        "created_by" => $session
                    ];
                    $transaction = transactions::create($data);
                    $investment_update->update([ 'wallet' => 0]);
                    $BranchWallet = BranchWallet::where('branch_id', $userAccount->user->branch_id)->first();
                    $BranchWallet->balance += $amount;
                    $BranchWallet->save();
                    user_wallet::where('wb_account_detail_id', $runninginvestment->wb_accountdetails_id)->update(['balance' => $balanceAfter]);
                    $return = response(json_encode(['status' => 1, 'message' => "success"]));
                } else {
                    $return =  response(json_encode(['status' => 1, 'message' => "$days_so_far *$amt_per_day"]));
                }
            } else {
                $return = response(json_encode(['status' => 1, 'message' => "Already Updated"]));
            }
        }
        return $return;
    }
    public function Temp_sms_cronjob()
    {
        // echo "hello";
        // \logInfo("Pending SMS Cron Job is Working currently",  'Cron as Started');
        $today = date('Y-m-d');
        $return = 0;
        $day = date('D');
        $date = date('d');
        if($day == 'Mon' || $date == 01){
        //Code for monday
        echo "Code for monday";
        $temp = Temp_Charges::where('status', 0)->groupBy('wb_account_detail_id')->where('date_due', '<=', $today)
        ->select(\DB::raw("SUM(`amount`) AS `amount`"), 'wb_account_detail_id','type','date_due','status','created_by','created_at','updated_at')
        ->get();
        if($temp){
            if(count($temp) > 0){
                foreach ($temp as $tempData) {
                    $userid = $tempData->wb_account_detail_id;
                    $amount = $tempData->amount;
                    echo " amt = $amount | userid = $userid <br>";
                    $user = user_wallet::where('wb_account_detail_id',$userid)->first();
                    if($user){
                        $userbranch = $user->wb_account_details->user->branch_id;
                        $company_id = $user->wb_account_details->user->company_id;
                        $company = $user->wb_account_details->user->company->setting->sms_charge_frequencie_id;
                    // echo date('Y-m-01');
                        //die("$company $userbranch");
                        $session = User::where('company_id', $company_id)->first()->id;
                        $sms_charge = $tempData->amount;
                        $BranchWallet = BranchWallet::where('branch_id', $userbranch)->first();
                        if ($BranchWallet->balance >= $sms_charge) {
                            $mybalance = $user->balance;
                            $user->balance = $user->balance - $sms_charge;
                            if ($user->save()) {
                                $amount = $tempData->amount;
                                $BranchWallet->balance -= $amount;
                                $BranchWallet->save();
                                Temp_Charges::where('wb_account_detail_id',$userid)->update(['status'=>1]);
                                echo "<br>";
                                // \logInfo("amt = $sms_charge | userid = $userid  $userbranch","Pending SMS description");
                                $data = [
                                    "balance_before" => $mybalance,
                                    "amount" => $sms_charge,
                                    "withdrawal_charge" => 0,
                                    "description" => 'SMS Charge',
                                    "account_id" => $userid,
                                    "branch_id" => $userbranch,
                                    "company_id" => $company_id,
                                    "transaction_reference" => generate_txnID(),
                                    "transaction_type_id" => 33, //sms charge
                                    "credit_debit_type_id" => 2, //debit 2
                                    "transaction_remarks_id" => 2, //success 2
                                    "balance_after" => $user->balance,
                                    "created_date"  => date('Y-m-d'),
                                    "created_by" => $session
                                ];
                                $return = transactions::create($data);
                            }
                        }
                    }else{
                        \logInfo("wptable ID $userid not link to a wallet address",  'Looping failure');
                    }
                }
            }
        }
    }
        return 0;
    }
    //end cron
    public function birthday_notifier()
    {
        $today = User::whereMonth('dob', '=', Carbon::now()->format('m'))->whereDay('dob', '=', Carbon::now()->format('d'))->get();
        if ($today) {
            foreach ($today as $todaydata) {
                $company = $todaydata->company;
                $setting = $company->setting;
                $channel = $setting->birthday_msg_channel;
                $msg = $setting->birthday_msg;
                $return = sendSMS($todaydata->id, $msg, true, true, 'Happy Birthday', '', 0, false, $channel, []);
            }
        }
    }
    public function monthlySavingsFee()
    {
        $today = date("Y-m-d");
        try{
        $wb_account_details = wb_account_details::all();
        foreach($wb_account_details as $wb_account_detailsData){
            \logInfo("Saving Cron Job is looping... ID: ".$wb_account_detailsData->user->id,  'Looping through');
            echo "wb Detail";
            $wbdataType=$wb_account_detailsData->accountType;
            $interest = $wbdataType->interest_rate;
            $user = $wb_account_detailsData->user;
            $createdBy = User::where('company_id',$user->company_id)->first();
            $maintenance_charges = $wbdataType->maintenance_charges;
            $duration = $wbdataType->duration;
            if($wb_account_detailsData->user_wallet){
                echo "Userwallet ";
                $balanceBefore = $bal = $wb_account_detailsData->user_wallet->balance;
                if($duration){
                    echo "wb duration--";
                    $txn = transactions::where(['account_id'=>$wb_account_detailsData->id])->orderBy('id','desc')->first();
                    $proceed =0;
                    if($txn){
                        echo "wb txn--";
                        $withdraw_diff = date("Y-m-d", strtotime($txn->created_date . "+$duration day"));
                            echo "wb over duration $withdraw_diff <br><br><br>";
                        if($withdraw_diff < date('Y-m-d')){
                            echo "wb over duration $duration ";
                            $proceed =1;
                        }
                    }
                    if($proceed){
                        echo "wb over proceed  ";
                        if($bal > 0 && $wbdataType->min_amt_for_interest > 0 && $wbdataType->min_amt_for_interest <= $bal && $interest >0){
                            $interestReturn = ($interest/100)*$bal;
                            $balanceAfter = ($balanceBefore + $interestReturn);
                            $desc = "Savings Deposit interest Return";
                            $transactionId = generate_txnID();
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
                                "created_by" => $createdBy->id
                            ];
                        \logInfo($Creditdata,  'Looping maintenace rate');
                        echo "Intereste<br><br><br>";
                            $transaction = transactions::create($Creditdata);
                            user_wallet::where('wb_account_detail_id',$wb_account_detailsData->id)->update(['balance'=>$balanceAfter]);
                            sendSMS(['txnid' => $transaction->id, 'userid' => $wb_account_detailsData->id], "Credit Amt:$interestReturn  Desc: $desc Avail Bal:$balanceAfter Date:$today Ref:$transactionId", false, true, 'Credit Transaction');
                        }
                        $current_acc = user_wallet::where('wb_account_detail_id',$wb_account_detailsData->id)->first();
                        $balanceBefore = $current_acc->balance;
                        $userbalance = $current_acc->balance -= $maintenance_charges;
                        $desc = "Maintenance Charge";
                        $transactionId = generate_txnID();
                        $debitdata = [
                            "balance_before" => $balanceBefore,
                            "amount" =>  $maintenance_charges,
                            "description" => $desc,
                            "account_id" => $wb_account_detailsData->id,
                            "branch_id" => $user->branch_id,
                            "transaction_status_id" => 1,
                            "transaction_reference" =>$transactionId,
                            "transaction_type_id" => 58, //debit 7
                            "credit_debit_type_id" => 2, //debit 2
                            "transaction_remarks_id" =>2, //success 2
                            "balance_after" => $userbalance,
                            "created_date"  => $today,
                            "created_by" => $createdBy->id
                        ];
                        $transactionDebit = transactions::create($debitdata);
                        $current_acc->balance = $userbalance;
                        $current_acc->save();
                        \logInfo($debitdata,  'Looping maintenace rate');
                        sendSMS(['txnid' => $transactionDebit->id, 'userid' => $wb_account_detailsData->id], "Debit Amt:$maintenance_charges  Desc: $desc Avail Bal:$userbalance Date:$today Ref:$transactionId", false, true, 'Debit Transaction');
                    }
                }
            }
        }
        }catch(Exception $e){
            \logInfo('Message: '.$e->getMessage(),  'Line: '.$e->getLine());
        }
    }
    public function loanCron(){
        $infoData = loan::where(['loan_status_id'=>1])->where('user_id','!=',0)->get();
        foreach($infoData as $info){
            $EncloanID =  Crypt::encryptString($info->id);
            $penaltyChargeMethod = $info->LoanProduct->penaltyChargeMethod;
            $repayment = json_decode(app('App\Http\Controllers\LoanController')->loanRepaymentBreakDown($EncloanID,new Request));
            $dateNow =date("Y-m-d");
                echo "welcome to loan cron";
            if($repayment->missed <= 0){
                echo "ongoing section";
                foreach($repayment->repaymentTable as $repaymentData){
                    if(isset($repaymentData->repaymentDateDueloan)){
                        if($repaymentData->currentRepayStatus ==2){
                            if($repaymentData->repaymentDateDueloan == $dateNow){
                                if($info->auto_pay == 1){
                                    $amtToPay = $repaymentData->EachRepayment;
                                    echo "to pay $amtToPay";
                                    $this->loanpayer($info,$amtToPay);
                                    echo "<<<>>> <br>";
                                }
                            }
                        }
                    }
                }
            }else{
                echo "missed section";
                $amtToPay = $repayment->missed;
                echo "to pay $amtToPay";
                $penalty =$this->penaltyCharge($repayment,$info,$amtToPay);
                $loanpayer = $this->loanpayer($info,$amtToPay);
                echo "<<<>>> <br>";
            }
        }
    }
    public function loanpayer($info,$amtToPay)
    {
        echo "Payment function";
        $user =User::where(['id'=>$info->user_id])->first();
        foreach($user->wb_account_details as $wb_account_details){
            $wbbal =$wb_account_details->user_wallet->balance;
            if($wbbal > 0){
                $account_typeid = $wb_account_details->id;
                $balanceBefore =$wb_account_details->user_wallet->balance;
                if($amtToPay  > $balanceBefore){
                    $amtToPay = $balanceBefore;
                }
                $balance = $balanceBefore -$amtToPay;
                $branch_id =$user->branch_id;
                $backdate =$paymentDate =date("Y-m-d");
                $wallet = user_wallet::where('id',$wb_account_details->user_wallet->id)->update(['balance'=>$balance]);
                if($wallet){
                    $auth =User::where('company_id',$info->user->company_id)->first();
                    $AmtInterest = ($info->rate/100)*$amtToPay;
                    $principal4rAmount =$amtToPay -$AmtInterest;
                    $loancharges = app('App\Http\Controllers\LoanController')->LoanMethodPercentReturn($principal4rAmount,$AmtInterest,$info->LoanFee,$info->LoanFeeMethod,$info->mrgtFee,$info->mrgtFeeMethod);
                    $loanPaymentStateData = app('App\Http\Controllers\LoanController')->getLoanCurrentRepayment($info,$amtToPay);
                    $principalLeft =$loanPaymentStateData['Pleft'];
                    $interestLeft =$loanPaymentStateData['Ileft'];
                    $loanBalance=$info->loanBalance - $amtToPay;
                    $loanFee=$loancharges['loanFee'];
                    $mrgFee=$loancharges['mrgFee'];
                    $loanPayment = new LoanRepayment;
                    $loanPayment->loan_id = $info->id;
                    $loanPayment->paymentDate = $paymentDate;
                    $loanPayment->amount = $amtToPay;
                    $loanPayment->branch_id = $branch_id;
                    $loanPayment->mgrtFee = $mrgFee;
                    $loanPayment->loanFee = $loanFee;
                    $loanPayment->P = $loanPaymentStateData['paymentP'];
                    $loanPayment->I = $loanPaymentStateData['paymentI'];
                    $loanPayment->remark = $loanPaymentStateData['message'];
                    $loanPayment->payment_ref = str_shuffle(time());
                    $loanPayment->method = 2;
                    $loanPayment->balance = $loanBalance;
                    $loanPayment->created_by = $auth->id;
                    $loanPayment->created_by = $auth->id;
                    $loanPayment->save();
                    if($loanPayment){
                    $loanTXN =transactions::create([
                        "balance_before" => $balanceBefore,
                        "amount" => $amtToPay,
                        "description" => "Loan auto repayment of $amtToPay from wallet",
                        "account_id" => $account_typeid,
                        "branch_id" => $branch_id,
                        "transaction_status_id" => 1,
                        "transaction_reference" => generate_txnID(),
                        "transaction_type_id" => 56, //Loan repayment 56
                        "credit_debit_type_id" => 2, //debit 2
                        "transaction_remarks_id" => 2, //success 2
                        "balance_after" => $balance,
                        "created_date"  => $backdate,
                        "created_by" => $auth->id,
                        "loan_repayment_id"=>$loanPayment->id
                    ]);
                        $loan_status_id = $info->loan_status_id;
                        if(($principalLeft + $interestLeft) <= 0){
                            $loan_status_id = 10;
                        }
                        loan::where(['id'=>$info->id])->update(['principalLeft'=>$principalLeft,'InterestLeft'=>$interestLeft,'loanBalance'=>($principalLeft+$interestLeft),'loan_status_id'=>$loan_status_id]);
                        $companName = $info->user->company->company_name;
                        $info->loanBalance = $principalLeft+$interestLeft;
                        $appMSG = "Your repayment of loan of $amtToPay via (Auto-Debit is acknowledged and your current loan balance is $info->loanBalance";
                        sendSMS($info->user_id, "$appMSG", true, true, 'Loan Repayment');
                        //return json_encode(['status'=>1,'message'=>'Cash Payment Successful!']);
                        \logInfo($loanTXN,  $info->user_id.' Loan Auto-Debit Payment Successful!');
                    }
                }
            }
        }
    }
    public function penaltyCharge($repayment,$info,$amtToPay){
        $penaltyMethod = $info->LoanProduct->penaltyChargeMethod;
        $principal = $info->principal;
        $missedFee = $info->missedFee;
        $interest = ($info->rate/100)*$principal ;
        $penaltycharge = 0;
        if($penaltyMethod == 1){
            $penaltycharge = ($missedFee/100) * $principal;
        }else if($penaltyMethod == 2){
            $penaltycharge = ($missedFee/100) * $interest;
        }else if($penaltyMethod == 3){
            $penaltycharge = ($missedFee/100) * ($interest + $principal);
        }else{
            $penaltycharge = ($missedFee/100) * $amtToPay;
        }
        $lastmissed = $repayment->lastmissed;
        $payLoanPenalty =payLoanPenalty::where(['loan_id'=>$info->id])->whereDate('lastDate','>',$lastmissed)->count();
        if($payLoanPenalty == 0 && $penaltycharge > 0){
            try{
            $payLoanPenalty = new payLoanPenalty;
            $payLoanPenalty->lastDate = $lastmissed;
            $payLoanPenalty->amount = $penaltycharge;
            $payLoanPenalty->loan_id = $info->id;
            $payLoanPenalty->save();
            $user =User::where(['id'=>$info->user_id])->first();
            $comparewallet =0;
            $usewb_account_details =null;
            foreach($user->wb_account_details as $wb_account_details){
                if($comparewallet <= $wb_account_details->user_wallet->balance){
                    $usewb_account_details = $wb_account_details;
                    $comparewallet = $wb_account_details->user_wallet->balance;
                }
            }
            if($usewb_account_details != null){
                $balanceBefore =$usewb_account_details->user_wallet->balance;
                $account_typeid = $usewb_account_details->id;
                $balance = $balanceBefore -$penaltycharge;
                $auth =User::where('company_id',$info->user->company_id)->first();
                $wallet = user_wallet::where('id',$usewb_account_details->user_wallet->id)->update(['balance'=>$balance]);
                $loanTXN =transactions::create([
                    "balance_before" => $balanceBefore,
                    "amount" => $penaltycharge,
                    "description" => "Loan Penalty debit $penaltycharge from wallet",
                    "account_id" => $account_typeid,
                    "branch_id" => $user->branch_id,
                    "transaction_status_id" => 1,
                    "transaction_reference" => generate_txnID(),
                    "transaction_type_id" => 62, //Loan repayment penlty
                    "credit_debit_type_id" => 2, //debit 2
                    "transaction_remarks_id" => 2, //success 2
                    "balance_after" => $balance,
                    "created_date"  => date("Y-m-d"),
                    "created_by" => $auth->id,
                ]);
                loan::where('id',$info->id)->update(['missedLoan'=>$amtToPay,'missedLoanLeft'=>$penaltycharge]);
                \logInfo($info,'penalty logged for ');
            }   else{
                \logInfo('penalty error ',"usewb_account_details is null at Userid: ".$info->user_id);
            }
            }catch(Exception $e){
                \logInfo(['loaninfo'=>$info,"error"=>'Message: '.$e->getMessage().'Line: '.$e->getLine()],'error logg');

            }
        }

    }

}
