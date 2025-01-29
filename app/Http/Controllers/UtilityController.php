<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\pendingCustomerSavingInterest;
use App\Models\User;
use App\Models\wb_account_details;
use App\Models\transactions;
use App\Models\user_wallet;
use App\Models\BranchWallet;

use DB;


class UtilityController extends Controller
{
    //
    public function reverseCron(){
        $user = User::where(['user_type_id'=>1,'status_id'=>1])->get();
        foreach($user as $userData){
            $wb_account_detailsData = wb_account_details::where('virtual_accnum',$userData->virtual_account)->first();
            if($wb_account_detailsData){
                if(isset($wb_account_detailsData->user)){
                    if(!isset($wb_account_detailsData->accountType))
                    { continue;}
                    $wbdataType=$wb_account_detailsData->accountType;
                    if(!isset($wbdataType->frequency->frequency)){
                        continue;
                    }
                    $results = PendingCustomerSavingInterest::where('wb_account_id', $wb_account_detailsData->id)
                    ->whereMonth('mark_date', '=', now()->month)
                    ->whereYear('mark_date', '=', now()->year)
                    ->get();

                    if($results){
                        if(count($results)>2){
                            $i = 0;
                        foreach($results as $result){
                            if (
                                $userData->created_at->format('Y-m') == $result->created_at->format('Y-m')
                            ) {
                                $i++;
                            }

                            // if($i > 0){
                            // $amount = $result->amount;
                            // $user_wallet = user_wallet::where('wb_account_detail_id', $result->wb_account_id)->first();
                            // if($user_wallet){
                            //     $transactionId = generate_txnID();
                            //     $transaction_type_id=59;
                            //     $ledger_cr =null;
                            // $ledger_dr =null;
                            // $ledger_Settings = get_CR_DR_TXN_Type_Ledger($transaction_type_id, $userData->company_id);
                            // if($ledger_Settings){
                            //     $ledger_cr = $ledger_Settings->ledger_cr;
                            //     $ledger_dr = $ledger_Settings->ledger_dr;
                            // }
                            //     $balanceBefore = $user_wallet->balance;
                            //     $balanceAfter =$balanceBefore - $amount;
                            //     $credit_debit_type_id =2;
                            //     $remark =2;
                            //     $created_by_id = $userData->id;
                            //     $backdate =date('Y-m-d');
                                echo "id: $result->id mark_date:$result->mark_date status:$result->status wb_account_id: $result->wb_account_id <br>";
                            //     $data = [
                            //         "balance_before" => $balanceBefore,
                            //         "amount" => $amount,
                            //         "withdrawal_charge" => 0,
                            //         "description" =>"Diplicate debit reversal!",
                            //         "account_id" => $result->wb_account_id,
                            //         "branch_id" => $wb_account_detailsData->user->branch_id,
                            //         "transaction_status_id" => 1,
                            //         "transaction_reference" => $transactionId,
                            //         "transaction_type_id" => $transaction_type_id, //debit 7
                            //         "credit_debit_type_id" => $credit_debit_type_id, //debit 2
                            //         "transaction_remarks_id" => $remark, //success 2
                            //         "balance_after" => $balanceAfter,
                            //         "created_date"  => $backdate,
                            //         "created_by" => $created_by_id,
                            //         "ledger_cr" => $ledger_cr,
                            //         "ledger_dr" => $ledger_dr,
                            //         "initiator" => "system reverser",
                            //         "currency_id"=>$wb_account_detailsData->currency_id
                            //     ];
                            //     // die(var_dump($data));
                            //     $transaction = transactions::create($data);

                            //     if ($transaction) {
                            //         user_wallet::where('wb_account_detail_id',$wb_account_detailsData->id)->update(['balance'=>$balanceAfter]);
                            //         $BranchWallet =BranchWallet::where('branch_id',$userData->branch_id)->first();
                            //         $BranchWallet->balance -= $amount;
                            //         $BranchWallet->save();
                            //         $result->status ='reversed';
                            //         $result->save();
                            //         fundVault(['type' => 'product_interest_vault', 'value' => (0+$amount), 'company_id' => $userData->company_id, 'created_by' => $userData->id, 'branch_id' => $userData->branch_id, 'transaction_id' => $transaction->id]);

                            //         $user_wallet = user_wallet::where('wb_account_detail_id', $result->wb_account_id)->update(['balance' => $balanceAfter]);
                            //     }
                            //     }
                            // }
                            $i++;

                        }
                    }

                    }
                }
            }


    }
}
}
