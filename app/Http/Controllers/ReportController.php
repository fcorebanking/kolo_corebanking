<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Investmentbooking;
use App\Models\savings_product;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Models\transactions;
use App\Models\branch;
use App\Models\StaffWallet;
use App\Models\loan;
use App\Models\payLoanPenalty;
use App\Models\LoanRepayment;
use App\Models\loan_product;
use App\Models\CompanyValut;
use App\Models\expenseType;
use App\Models\otherIncomeType;
use App\Models\Expenses;
use App\Models\Esusu;


use Illuminate\Support\Facades\Crypt;

class ReportController extends Controller
{
    public function balancesheetpage(){

        $data = [
            'category_name' => 'accounting',
            'page_name' => 'html5',
            'page_menu' => 'balancesheet',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        // $pageName = 'html5';
        return view('company.report.balancesheet')->with($data);
    }
    public function balancesheet(Request $request){
        try{
        // $activeLoan = $CompletedPrincipalLoan=$liquidatePrincipalLoan =$writeoffPrincipalLoan= ;
        $auth =auth()->user();
        $activeLoan = loan::whereHas('user', function ($q) use ($auth) {
            $q->where(['company_id'=>$auth->company_id,'status_id'=>1]);
        })->where('loan_status_id',1);
        if($request->from != null){
            $activeLoan = $activeLoan->where('created_at','>=',$request->from);
        }
        if($request->to != null){
            $activeLoan = $activeLoan->where('created_at','<=',$request->to);
        }
        $activeLoan = $activeLoan->sum('loanBalance');
        $InterestLeft = loan::whereHas('user', function ($q) use ($auth) {
            $q->where(['company_id'=>$auth->company_id,'status_id'=>1]);
        })->where('loan_status_id',1);
        if($request->from != null){
            $InterestLeft = $InterestLeft->where('created_at','>=',$request->from);
        }
        if($request->to != null){
            $InterestLeft = $InterestLeft->where('created_at','<=',$request->to);
        }
        $InterestLeft =$InterestLeft->sum('InterestLeft');

        $PrincipalLeft = loan::whereHas('user', function ($q) use ($auth) {
            $q->where(['company_id'=>$auth->company_id,'status_id'=>1]);
        })->where('loan_status_id',1);
        if($request->from != null){
            $PrincipalLeft = $PrincipalLeft->where('created_at','>=',$request->from);
        }
        if($request->to != null){
            $PrincipalLeft = $PrincipalLeft->where('created_at','<=',$request->to);
        }
        $PrincipalLeft =$PrincipalLeft->sum('principalLeft');
        $activePrincipal =  loan::whereHas('user', function ($q) use ($auth) {
            $q->where(['company_id'=>$auth->company_id,'status_id'=>1]);
        })->where('loan_status_id',1);
        if($request->from != null){
            $activePrincipal = $activePrincipal->where('created_at','>=',$request->from);
        }
        if($request->to != null){
            $activePrincipal = $activePrincipal->where('created_at','<=',$request->to);
        }
        $activePrincipal =$activePrincipal->sum('principal');
        $CompletedPrincipalLoan = loan::whereHas('user', function ($q) use ($auth) {
            $q->where(['company_id'=>$auth->company_id,'status_id'=>1]);
        })->where('loan_status_id',10);
        if($request->from != null){
            $CompletedPrincipalLoan = $CompletedPrincipalLoan->where('created_at','>=',$request->from);
        }
        if($request->to != null){
            $CompletedPrincipalLoan = $CompletedPrincipalLoan->where('created_at','<=',$request->to);
        }
        $CompletedPrincipalLoan=$CompletedPrincipalLoan->sum('loans.principal');
        $luquidatebalance  = loan::whereHas('user', function ($q) use ($auth) {
            $q->where(['company_id'=>$auth->company_id,'status_id'=>1]);
        })->where('loan_status_id',8);
        if($request->from != null){
            $luquidatebalance = $luquidatebalance->where('created_at','>=',$request->from);
        }
        if($request->to != null){
            $luquidatebalance = $luquidatebalance->where('created_at','<=',$request->to);
        }
        $luquidatebalance =$luquidatebalance->sum('loanBalance');
        $liquidatePrincipalLoan = loan::whereHas('user', function ($q) use ($auth) {
            $q->where(['company_id'=>$auth->company_id,'status_id'=>1]);
        })->where('loan_status_id',8);
        if($request->from != null){
            $liquidatePrincipalLoan = $liquidatePrincipalLoan->where('created_at','>=',$request->from);
        }
        if($request->to != null){
            $liquidatePrincipalLoan = $liquidatePrincipalLoan->where('created_at','<=',$request->to);
        }
        $liquidatePrincipalLoan =$liquidatePrincipalLoan->sum('principal');
        $writeoffPrincipalLoan = loan::whereHas('user', function ($q) use ($auth) {
            $q->where(['company_id'=>$auth->company_id,'status_id'=>1]);
        })->where('loan_status_id',7);
        if($request->from != null){
            $writeoffPrincipalLoan = $writeoffPrincipalLoan->where('created_at','>=',$request->from);
        }
        if($request->to != null){
            $writeoffPrincipalLoan = $writeoffPrincipalLoan->where('created_at','<=',$request->to);
        }
        $writeoffPrincipalLoan =$writeoffPrincipalLoan->sum('principal');
        //repayment amount paid
        $loanPayment = LoanRepayment::join('loans','loans.id','=','loan_repayments.loan_id')
        ->join('users','users.id','=','loans.user_id')->where(['users.company_id'=>$auth->company_id,'users.status_id'=>1])
        ->select( \DB::raw('SUM(loan_repayments.amount) AS InitPamount'));
        if($request->from != null){
            $loanPayment = $loanPayment->where('loan_repayments.paymentDate','>=',$request->from);
        }
        if($request->to != null){
            $loanPayment = $loanPayment->where('loan_repayments.paymentDate','<=',$request->to);
        }
        $loanPayment =$loanPayment->first();
        // dd($loanPayment);
        $payLoanPenalty =payLoanPenalty::join('loans','loans.id','=','pay_loan_penalty.loan_id')
        ->join('users','users.id','=','loans.user_id')->where(['users.company_id'=>$auth->company_id,'pay_loan_penalty.status'=>'paid'])
        ->select( \DB::raw('SUM(pay_loan_penalty.amount) AS InitPamount'));
        if($request->from != null){
            $payLoanPenalty = $payLoanPenalty->where('pay_loan_penalty.created_at','>=',$request->from);
        }
        if($request->to != null){
            $payLoanPenalty = $payLoanPenalty->where('pay_loan_penalty.created_at','<=',$request->to);
        }
        $payLoanPenalty =$payLoanPenalty->first();
        //transactions
        $missed =$this->missedloan($request->from,$request->to);
        return [
            'status'=>1,
            'activePrincipal'=>$activePrincipal,
            'PrincipalLeft'=>$PrincipalLeft,
            'writtenoffprincipal'=>$writeoffPrincipalLoan,
            'liquidateprincipal'=>$liquidatePrincipalLoan,
            'active'=>$activeLoan,
            'completedloan'=>$CompletedPrincipalLoan,
            'loanpaid'=>($loanPayment->InitPamount)??0,
            'penaltyPaid'=>($payLoanPenalty->InitPamount)??0,
            'missedloan'=>$missed['message'],
            'user'=>$this->savingsData($request->from,$request->to),
            'vault'=>$this->transaction_charge($request->from,$request->to),
            'expenses'=>$this->expense($request->from,$request->to),
            'otherIncome'=>$this->otherIncome($request->from,$request->to),
            'currency' => auth()->user()->company->currency->symbol,
            'InterestLeft'=>$InterestLeft,
            'luquidatebalance'=>$luquidatebalance,
            'esusu'=>$this->esusu(),
            'investment'=>$this->investment($request->from,$request->to),
        ];
        }catch(Exception $e){
            return ['status'=>0,'message'=>'An Error Occured!'];
        }
    }
    public function investment($from,$to){
        $query= Investmentbooking::join('investment_products', 'investment_products.id', '=', 'investmentbooking.investment_product_id')
        ->join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
        ->where('investment_products.company_id',auth()->user()->company_id);

        if($from != null){
            $query = $query->where('investmentbooking.created_at','>=',$from);
        }
        if($to != null){
            $query = $query->where('investmentbooking.created_at','<=',$to);
        }
        $query = $query->sum('investmentbooking.amount');
        return $query;
    }
    public function esusu(){
        return Esusu::join('esusu_types', 'esusu_types.id', '=', 'esusus.esusu_type_id')
        ->where('esusu_types.company_id',auth()->user()->company_id)
        ->select(\DB::raw('SUM(esusus.withdrawable) as withdrawable'), \DB::raw('SUM(esusus.wallet) as wallet'),\DB::raw('SUM(esusus.accum_charge) as acccharge'),\DB::raw('SUM(esusus.overdraft) as overdraft'))
        ->first();
    }
    public function missedloan($from,$to){
        $auth =auth()->user();
        // if($from == null)  return (['message'=>0,'status'=>0]);
        $loan =loan::whereHas('user', function ($q) use ($auth) {
            $q->where(['company_id'=>$auth->company_id]);
        })->where('loan_status_id',1);
        if($from != null){
            $loan = $loan->where('created_at','>=',$from);
        }
        if($to != null){
            $loan = $loan->where('created_at','<=',$to);
        }
        $loan = $loan->get();
        $sn =0;
        $loanrec = [];
        $missedloan =0;
        if($loan){
            foreach($loan as $loanData){
                $accBal =0;
                foreach($loanData->user->wb_account_details as $useraccount){
                    $accBal+=$useraccount->user_wallet->balance;
                }
                $EncloanID =  Crypt::encryptString($loanData->id);
                $repayment = json_decode(app('App\Http\Controllers\LoanController')->loanRepaymentBreakDown($EncloanID,new Request));
                $missedamt =0;
                foreach($repayment->repaymentTable as $repaymentData){
                    if(isset($repaymentData->currentRepayStatus)){
                        if($repaymentData->currentRepayStatus==3){
                            $sn++;
                            $missedamt =$repaymentData->outstandingpay;
                        }
                    }
                }
                $missedloan +=$missedamt;
            }
        }else{
            return (['status'=>0,'message'=>'No Record Found!']);
        }
        return (['message'=>$missedloan,'status'=>1]);
    }
    public function savingsData($from,$to){
        $user = User::join('wb_account_details','wb_account_details.user_id','=','users.id')
        ->join('savings_products','wb_account_details.account_product_id','=','savings_products.id')
        ->join('user_wallets','wb_account_details.id','=','user_wallets.wb_account_detail_id')
        ->where(['users.company_id'=>auth()->user()->company_id,'status_id'=>1])
        ->groupBy('wb_account_details.account_product_id','savings_products.savings_product')
        ->select(\DB::raw('SUM(user_wallets.balance) AS balance'),'savings_products.savings_product');
        if($from != null){
            $user = $user->where('wb_account_details.created_at','>=',$from);
        }
        if($to != null){
            $user = $user->where('wb_account_details.created_at','<=',$to);
        }
        $user = $user->get();
        return $user;

    }
    public function transaction_charge($from,$to){
        $returnData = CompanyValut::where(['company_id' => auth()->user()->company_id])->select('vault','value');
        if($from != null){
            $returnData = $returnData->where('created_at','>=',$from);
        }
        if($to != null){
            $returnData = $returnData->where('created_at','<=',$to);
        }
        $returnData = $returnData->get();
        return $returnData;
    }
    public  function expense($from,$to){
        $expenseType = expenseType::join('expenses','expenses.expense_type_id','=','expense_types.id')
        ->where('expense_types.company_id',auth()->user()->company_id);
        if($from != null){
            $expenseType = $expenseType->where('expenses.created_at','>=',$from);
        }
        if($to != null){
            $expenseType = $expenseType->where('expenses.created_at','<=',$to);
        }
        $expenseType = $expenseType->sum('expenses.amount');
        return $expenseType;
    }

    public  function otherIncome($from,$to){
        $other_income_types = otherIncomeType::join('other_incomes','other_incomes.other_income_type_id','=','other_income_types.id')
        ->where('other_income_types.company_id',auth()->user()->company_id);
        if($from != null){
            $other_income_types = $other_income_types->where('other_incomes.created_at','>=',$from);
        }
        if($to != null){
            $other_income_types = $other_income_types->where('other_incomes.created_at','<=',$to);
        }
        $other_income_types = $other_income_types->sum('other_incomes.amount');
        return $other_income_types;
    }
}
