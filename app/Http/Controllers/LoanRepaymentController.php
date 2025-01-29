<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\LoanRepayment;
use App\Models\loan;
use App\Models\loan_product;
use App\Http\Controllers\LoanController;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

class LoanRepaymentController extends Controller
{
public function txnReceipt($id)
    {
        $auth =auth()->user();
        // $is_root = cando('manage-saving|credit-customer|debit-customer|root');
        // if (!$is_root) {

        // } else {
        //     $query = LoanRepayment::where('transaction_reference', $id);
        // }
        $query = LoanRepayment::
                join('loans', 'loans.id', '=', 'loan_repayments.loan_id')
                ->join('users', 'users.id', '=', 'loans.user_id')
                ->where(['loan_repayments.id' => $id]);
        $query = $query->first();
        $data = [
            'category_name' => 'apps',
            'page_name' => 'form',
            'page_menu' => 'sub_invoice',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 1,
            'txn' => $query,
            'success' => "Transaction Successful!"
        ];
        return view('company.loan.inc.loanReciept')->with($data);
    }
    public function resetLoanPage(){
        $loan_product = loan_product::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get();
        $data = ['loan_product'=>$loan_product];
        return view('company.loan.inc.resetRepayment')->with($data);
    }
    public function resetLoanPayment(Request $request){
        $auth = auth()->user();
        if(!(isset($request->product))){
            return "select product";
        }
        $loan = loan::join('users', 'users.id', '=', 'loans.user_id')
        ->where('users.company_id', $auth->company_id)->where('loans.loan_product_id',$request->product)
        ->select(DB::raw('loans.*'),DB::raw('loans.id as ID'), 'users.company_id')
        ->get();
        if($loan){
            foreach($loan as $loanData){
                LoanRepayment::where(['loan_id'=>$loanData->ID,'status_id'=>1])->update(['status_id'=>3]);
                $LoanRepaymentP = LoanRepayment::where(['loan_id'=>$loanData->ID,'status_id'=>3])->sum('P');
                $LoanRepaymentI = LoanRepayment::where(['loan_id'=>$loanData->ID,'status_id'=>3])->sum('I');
                if($LoanRepaymentP==0 && $LoanRepaymentI==0){
                    continue;
                }
                $totalPaid = ($LoanRepaymentP +$LoanRepaymentI);
                $principalLeft = $loanData->principalLeft+$LoanRepaymentP;
                $InterestLeft =$loanData->InterestLeft+$LoanRepaymentI;
                $loanBalance = $loanData->loanBalance +$totalPaid;
                loan::where('id',$loanData->ID)->update(['loanBalance'=>$loanBalance,'InterestLeft'=>$InterestLeft,'principalLeft'=>$principalLeft]);
                LoanRepayment::where(['loan_id'=>$loanData->ID,'status_id'=>3])->update(['status_id'=>4]);
            }
        }
        return json_encode($loan);
    }
    public function payLoanMirrow($id){
        try{
        $LoanRepayment = LoanRepayment::where(['loan_id'=>$id,'status_id'=>4])->get();
        if($LoanRepayment){
            foreach($LoanRepayment as $LoanRepaymentData){
                $newReq = new Request;
                $newReq->merge([
                    'amount'=> $LoanRepaymentData->amount,
                    'method' => $LoanRepaymentData->method==1?'cash':'wallet',//bvn
                    'repaymentExist'=> $LoanRepaymentData->id,
                    'payment'=> $LoanRepaymentData->paymentDate,
                    'accountType'=>null,
                    'created_by'=>$LoanRepaymentData->created_by,
                    'loan_initiator_id'=>$LoanRepaymentData->initiator_id,
                    'loanID'=>Crypt::encryptString($LoanRepaymentData->loan_id)
                ]);
                //in loan payment make sure to capture created By
                $loan = loan::where('id',$LoanRepaymentData->loan_id)->first();

                $loanPaymentStateData = app('App\Http\Controllers\LoanController')->getLoanCurrentRepayment($loan,$LoanRepaymentData->amount);
                if(empty($loanPaymentStateData)){
                    echo "error <br>";
                }
                echo "<h3>Entry data for $LoanRepaymentData->amount</h3>";
                var_dump($loanPaymentStateData);
                if($loanPaymentStateData['status']){
                $LoanRepaymentData->P = $loanPaymentStateData['paymentP'];
                $LoanRepaymentData->I = $loanPaymentStateData['paymentI'];
                $LoanRepaymentData->remark = $loanPaymentStateData['message'];
                $loan->InterestLeft=$loanPaymentStateData['Ileft'];
                $loan->principalLeft=$loanPaymentStateData['Pleft'];
                $loan->save();
                $LoanRepaymentData->status_id = 1;
                $LoanRepaymentData->save();
                // $res =(app('App\Http\Controllers\LoanController')->generalpayloan($newReq));
                // if($res['status'] ==1){
                //     if(LoanRepayment::where(['id'=>$LoanRepaymentData->id,'status_id'=>4])->update(['status_id'=>1])){
                echo "------ Payment reversed -----";
                //     }
                // }else{
                //     echo var_dump($res);
                // }
                }else{
                    echo "LoanRepaymentData ID $LoanRepaymentData->id <br>";
                }
            }
        }
        }catch(Exception $e){
            echo $e->getMessage()."<br>";
        }
    }
    public function resetLoanData(Request $request){
        $auth = auth()->user();
        if(!(isset($request->product))){
            return "select product";
        }
        $loan = loan::join('users', 'users.id', '=', 'loans.user_id')
        ->where(['users.company_id'=>$auth->company_id])
        ->where('loans.loan_product_id',$request->product)
        ->select(DB::raw('loans.*'),DB::raw('loans.id as ID'), 'users.company_id')
        ->get();
        if($loan){
            foreach($loan as $loanData){
                $this->payLoanMirrow($loanData->id);
            }
        }
    }
}
