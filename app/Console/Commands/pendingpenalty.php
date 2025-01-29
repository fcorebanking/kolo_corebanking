<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\payLoanPenalty;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\transactions;
use App\Models\BranchWallet;
ini_set('memory_limit', '500000M');

class pendingpenalty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pendingpenalty:cron';

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
        try {
            //code...
            $payLoanPenalty =  payLoanPenalty::where('status','pending')->get();
            if($payLoanPenalty){
                foreach ($payLoanPenalty as $penaltyData) {
                    # code...
                    $penaltycharge = $penaltyData->amount;
                    $userID =$penaltyData->Loan->user_id;
                    $user =User::where(['id'=>$userID])->first();
                    $usewb_account_details =null;
                    foreach($user->wb_account_details as $wb_account_details){
                        if($penaltycharge <= $wb_account_details->user_wallet->balance){
                            $usewb_account_details = $wb_account_details;
                            break;
                        }
                    }
                    if($usewb_account_details != null){
                        $balanceBefore =$usewb_account_details->user_wallet->balance;
                        $account_typeid = $usewb_account_details->id;
                        $balance = $balanceBefore -$penaltycharge;
                        $auth =User::where('company_id',$user->company_id)->first();
                        $wallet = user_wallet::where('id',$usewb_account_details->user_wallet->id)->update(['balance'=>$balance]);
                        $BranchWallet =BranchWallet::where('branch_id',$auth->branch_id)->first();
                        $BranchWallet->balance -= $penaltycharge;
                        $BranchWallet->save();
                        $ledger_cr =null;
                        $ledger_dr =null;
                        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(62, $auth->company_id);
                        if($ledger_Settings){
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
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
                            "ledger_cr" =>$ledger_cr,
                            "ledger_dr"=>$ledger_dr,
                        ]);
                        $payLoanPenalty = payLoanPenalty::where('id',$penaltyData->id)->first();
                        $payLoanPenalty->status = 'paid';
                        $payLoanPenalty->save();
                    }
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
            echo  $th->getMessage();
        }
        return 0;
    }
}
