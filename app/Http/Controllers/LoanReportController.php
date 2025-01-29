<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\loan;
use App\Models\User;
use App\Models\LoanRepayment;
use App\Models\branch;
use App\Models\loan_product;
use App\Models\AddedLoanLog;

use Illuminate\Support\Facades\Crypt;

class LoanReportController extends Controller
{
    public function dueLoan(Request $request){

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 1000);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];

        $loan =loan::whereHas('user', function ($q){
            $q->where(['company_id'=>auth()->user()->company_id]);
        })->where('loan_status_id',1);
        $length =1000;
        if($request->branch != null && $request->branch != ''){
            $request->branch = Crypt::decryptString($request->branch);
            $loan->where('LoanBranch_id',$request->branch);
        }
        if($request->officer != null || $request->officer != 'null'){
            $usertemp =User::where('uuid',$request->officer)->first();
            if($usertemp){
            $loan->where('LoanOfficer_id',$usertemp->id);
            }

        }
        $loan = $loan->get();

        $sn =0;
        $loanrec = array(
            'draw' => 1,
            'recordsTotal' => 1,
            'recordsFiltered' => 1,
            'data' => [],
        );
        $loanrec =[];
        if($loan){
            foreach($loan as $loanData){
                //$Paments = $loanData->Paments->amount;
                $LoanRepayment = LoanRepayment::where(['loan_id'=>$loanData->id,'status_id'=>1])->sum('amount');
                $accBal =0;
                foreach($loanData->user->wb_account_details as $useraccount){
                    $accBal+=$useraccount->user_wallet->balance;
                }
                $EncloanID =  Crypt::encryptString($loanData->id);
                $repayment = json_decode(app('App\Http\Controllers\LoanController')->loanRepaymentBreakDown($EncloanID,new Request));
                $loanmissed =0;
                foreach($repayment->repaymentTable as $repaymentData){
                    if(isset($repaymentData->repaymentDateDueloan)){
                        // return $repaymentData;
                        if($repaymentData->currentRepayStatus == 3) {$loanmissed +=  $repayment->repaymentAmut;}
                        if(($request->from==null && $request->to==null) || ($repaymentData->repaymentDateDueloan >= $request->from && $repaymentData->repaymentDateDueloan <= $request->to)){
                            $sn++;
                            $link =url('company/'.Auth()->user()->company->short_name.'/loan/pay-loan/'.$EncloanID);
                            // return $repaymentData;

                            $loanrec['recordsFiltered']=1;
                            $loanrec['data'][] =[
                                $sn,
                                $loanData->user->first_name.' '.$loanData->user->last_name,
                                $loanData->user->phone,
                                $loanData->user->myOfficer->first_name.' '.$loanData->user->myOfficer->last_name,
                                "<a class='btn btn-flat' href='".$link."'>".$loanData->ref."</a>",
                                $loanData->LoanFee,
                                $loanData->rate,
                                $repaymentData->repaymentDateDueloan,
                                $repayment->repaymentAmut,
                                $repaymentData->outstandingpay,//due
                                $repayment->totalPaid,
                                ($repayment->IPLeftNo),
                                $repayment->maturity,
                                $loanData->branch->branch_name,
                                $loanData->author->first_name.' '.$loanData->author->last_name,
                                $accBal,
                                $LoanRepayment,
                                $repaymentData->loanBalanceRecurring,
                                $repaymentData->outstandingpay,//pending

                            ];
                        }
                    }
                }
            }
        }else{
            return json_encode(['status'=>0,'message'=>'No Record Found!']);
        }
        return $loanrec;
    }

    public function MissedLoan()
    {
        if (!cando('view-loan-missed|loan-missed|root')) return abort(403);
        $data = [
            'category_name' => 'loan',
            'page_name' => 'loan_missed',
            'page_menu' => 'loan_missed',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'branch'=>branch::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),

        ];
        return view('company.loan.report.missloan')->with($data);
    }
    public function missedLoan_report(Request $request){
        if (!cando('view-loan-missed|loan-missed|root')) return abort(403);

        $loan =loan::whereHas('user', function ($q){
            $q->where(['company_id'=>auth()->user()->company_id]);
        })->where('loan_status_id',1);
        if($request->branch != null){
            $request->branch = Crypt::decryptString($request->branch);
            $loan->where('LoanBranch_id',$request->branch);
        }
        if($request->officer != null){
            $usertemp =User::where('uuid',$request->officer)->first();
            $loan->where('LoanOfficer_id',$usertemp->id);
        }
        $loan = $loan->get();
        $sn =0;
        $loanrec = [];
        if($loan){
            foreach($loan as $loanData){
                $accBal =0;
                foreach($loanData->user->wb_account_details as $useraccount){
                    $accBal+=$useraccount->user_wallet->balance;
                }
                $EncloanID =  Crypt::encryptString($loanData->id);
                $repayment = json_decode(app('App\Http\Controllers\LoanController')->loanRepaymentBreakDown($EncloanID,new Request));
                foreach($repayment->repaymentTable as $repaymentData){
                    if(isset($repaymentData->currentRepayStatus)){
                        if($repaymentData->repaymentDateDueloan >= $request->from && $repaymentData->repaymentDateDueloan <= $request->to && $repaymentData->currentRepayStatus==3){
                            //echo "$repaymentData->repaymentDateDueloan > $request->from && $repaymentData->repaymentDateDueloan <= $request->to";
                            $sn++;
                            $link =url('company/'.Auth()->user()->company->short_name.'/loan/pay-loan/'.$EncloanID);
                            $loanrec[] =[
                                'sn'=>$sn,
                                'name'=>$loanData->user->first_name.' '.$loanData->user->last_name,
                                'phone'=>$loanData->user->phone,
                                'accountOff'=>$loanData->user->myOfficer->first_name.' '.$loanData->user->myOfficer->last_name,
                                'ref'=>"<a class='btn btn-flat' href='".$link."'>".$loanData->ref."</a>",
                                'repaymentDate'=>$repaymentData->repaymentDateDueloan,
                                'loanInterest'=>$loanData->rate,
                                'loanFee'=>$loanData->LoanFee,
                                'branch'=>$loanData->branch->branch_name,
                                'repaymentAmt'=>$repayment->repaymentAmut,
                                'amountpaid'=>$repayment->totalPaid,
                                'totalDue'=>($repayment->IPLeftNo),
                                'maturityDate'=>$repayment->maturity,
                                'loanstatus'=>$repaymentData->loanBalanceRecurring,
                                'branch'=>$loanData->branch->branch_name,
                                'accountBalance'=>$accBal,
                                'officer'=>$loanData->LoanOfficer->first_name.' '.$loanData->LoanOfficer->last_name,
                            ];
                        }
                    }
                }
            }
        }else{
            return json_encode(['status'=>0,'message'=>'No Record Found!']);
        }
        return json_encode(['message'=>$loanrec,'status'=>1]);
    }
    public function DisbursedLoan()
    {
        if (!cando('loan-disbursed|root')) return abort(403);
        $data = [
            'category_name' => 'loan',
            'page_name' => 'loan_disbursed',
            'page_menu' => 'loan_disbursed',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'branch'=>branch::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),
        ];
        return view('company.loan.report.loandisbursment')->with($data);
    }
    public function disbursedLoan_report(Request $request){

        $loan =loan::whereHas('user', function ($q){
            $q->where(['company_id'=>auth()->user()->company_id]);
        })->where('loan_status_id',1);
        if($request->searchType != null){
            if($request->searchType == 'created_date'){
                $loan = $loan->whereDate('created_at','>',$request->from)->whereDate('created_at','<',$request->to);
            }else if($request->searchType == 'loan_start_date'){
                $loan = $loan->whereDate('start_at','>',$request->from)->whereDate('start_at','<',$request->to);
            }else if($request->searchType == 'loan_grace_date'){
                $loan = $loan->whereDate('grace_at','>',$request->from)->whereDate('grace_at','<',$request->to);
            }
        }else{
            $loan = $loan->whereDate('created_at','>',$request->from)->whereDate('created_at','<',$request->to);
        }
        if($request->branch != null){
            $request->branch = Crypt::decryptString($request->branch);
            $loan->where('LoanBranch_id',$request->branch);
        }
        if($request->officer != null){
            $usertemp =User::where('uuid',$request->officer)->first();
            $loan->where('LoanOfficer_id',$usertemp->id);
        }
        $loan = $loan->get();
        $sn =0;
        $loanrec = [];
        if($loan){
            $totalDisbursedAmount=0;
            foreach($loan as $loanData){
                $accBal =0;
                foreach($loanData->user->wb_account_details as $useraccount){
                    $accBal+=$useraccount->user_wallet->balance;
                }
                $EncloanID =  Crypt::encryptString($loanData->id);
                $sn++;
                $link =url('company/'.Auth()->user()->company->short_name.'/loan/pay-loan/'.$EncloanID);
                $totalDisbursedAmount += $loanData->principal;
                $loanrec[] =[
                    'sn'=>$sn,
                    'name'=>$loanData->user->first_name.' '.$loanData->user->last_name,
                    'phone'=>$loanData->user->phone,
                    'accountOff'=>$loanData->user->myOfficer->first_name.' '.$loanData->user->myOfficer->last_name,
                    'ref'=>"<a class='btn btn-flat' href='".$link."'>".$loanData->ref."</a>",
                    'graceDate'=>$loanData->grace_at,
                    'loanInterest'=>$loanData->rate,
                    'loanFee'=>$loanData->LoanFee,
                    'branch'=>$loanData->branch->branch_name,
                    'repaymentAmt'=>$loanData->EachRepayment,
                    'branch'=>$loanData->branch->branch_name,
                    'accountBalance'=>$accBal,
                    'loanstatus'=>'<span class="badge badge-success">Disbursed</span>',
                    'principal'=>$loanData->principal,
                    'officer'=>$loanData->LoanOfficer->first_name.' '.$loanData->LoanOfficer->last_name,
                    'created_by'=>$loanData->author->first_name.' '.$loanData->author->last_name,
                    'approved'=>$loanData->editor->first_name.' '.$loanData->editor->last_name
                ];
            }
            // $loanrec[] =[
            //     '#',
            //     '',
            //     '',
            //     '',
            //     '',
            //     '',
            //     '',
            //     '',
            //     '',
            //     '',
            //     '',
            //     '',
            //     '',
            //     '<span class="btn btn-success">'.$totalDisbursedAmount.'</span>',
            //     '',
            //     '',
            //     '',


            // ];
        }else{
            return json_encode(['status'=>0,'message'=>'No Record Found!']);
        }
        return json_encode(['message'=>$loanrec,'status'=>1]);
    }
    public function repaymentReport(){
        if (!cando('repayment-report|root')) return   abort(403);
        $data = [
            'category_name' => 'loan',
            'page_name' => 'loan_repayment_data',
            'page_menu' => 'loan_repayment_data',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'branch'=>branch::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),
        ];
        return view('company.loan.report.repayment')->with($data);
    }
    public function repaymentReportData(Request $request){
        if (!cando('repayment-report|root')) return json_encode(['status'=>0,'message'=>'Access Denied!']);
        //Display Product
         $search = $request->query('search', array('value' => '', 'regex' => false));
         $draw = $request->query('draw', 0);
         $start = $request->query('start', 0);
         $length = $request->query('length', 10);
         $order = $request->query('order', array(1, 'asc'));
         $filter = $search['value'];
         $sortColumns = array(
             0 => 'loan_repayments.id',
             1 => 'users.first_name',
             2 => 'users.phone',
             3 => 'loans.ref',
             4 => 'loan_repayments.payment_ref',
             5 => 'loan_repayments.I',
             6 => 'loan_repayments.P',
             7 => 'loan_repayments.loanFee',
             8 => 'loan_repayments.mgrtFee',
             9 => 'loan_repayments.amount',
             10 => 'loan_repayments.balance',
             11 => 'loan_repayments.remark',
             12 => 'loan_repayments.branch_name',
             13 => 'loan_repayments.initiator_id',
             14 => 'loan_repayments.created_by',
             15 => 'loan_repayments.paymentDate',
             16 => 'loan_repayments.method',
             17 => 'loan_repayments.created_at',
         );

         $query = LoanRepayment::join('loans','loans.id','loan_repayments.loan_id')
         ->join('users','loans.user_id','users.id')
         ->join('branch','branch.id','loan_repayments.branch_id')
         ->where(['users.company_id'=>auth()->user()->company_id,'loan_repayments.status_id'=>1])
         ->select('loan_repayments.*','loans.ref','users.virtual_account','users.first_name','users.last_name','branch.branch_name');
        // if($request->all != null){
            if($request->from != null){
                $paymentDate = "loan_repayments.".$request->date_type ;
                $query = $query->where($paymentDate,'>=',$request->from);
            }
            if($request->to != null){
                $paymentDate = "loan_repayments.".$request->date_type ;
                $query = $query->where($paymentDate,'<=',$request->to);
            }

            if($request->branch != null){
                $request->branch = Crypt::decryptString($request->branch);
                $query = $query->where('loan_repayments.branch_id',$request->branch);
            }
            if($request->officer != null && $request->officer != 'null'){
                $user = User::where('uuid',$request->officer)->first();
                $query = $query->where('loan_repayments.created_by',$user->id);
            }
        // }
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('loans.ref', 'like', '%' . $filter . '%');
                $query->orWhere('users.virtual_account', 'like', '%' . $filter . '%');
                $query->orWhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.last_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('users.email', 'like', '%' . $filter . '%');
                $query->orWhere('loan_repayments.amount', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir'])
            ->take($length)
            ->skip($start);
        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );
        $info = $query->get();
        $loop = 0;

        $totalRepaymentAmount =0;
        $totalLoanFeeAmount =0;
        $totalLoanMrgtAmount =0;
        $totalLoanBalance =0;
        $totalLoanInterestPaid =0;
        $totalLoanPrincipalPaid =0;
        foreach ($info as $infoData) {
            $btnlink =Crypt::encryptString($infoData->id);
            $created_by = User::where('id',$infoData->created_by)->first();
            $initiated = User::where('id',@$infoData->initiator_id)->first();
            // $btn = '<div class="dropdown">
            //     <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            //         Action
            //     </button>
            //     <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            //     <a class="dropdown-item" href="#" onclick='.$approve.'>View</a>
            //     </div>
            // </div>';

            $totalRepaymentAmount +=$infoData->amount;
        $totalLoanFeeAmount +=$infoData->loanFee;
        $totalLoanMrgtAmount +=$infoData->mgrtFee;
        $totalLoanBalance +=$infoData->balance;
        $totalLoanInterestPaid +=$infoData->I;
        $totalLoanPrincipalPaid +=$infoData->P;
            $remark = "remarkText('$infoData->remark')";
                $loop++;
                $json['data'][] = [
                    $loop,
                    $infoData->first_name.' '.$infoData->last_name,
                    $infoData->virtual_account,
                    $infoData->ref,
                    $infoData->payment_ref,
                    $infoData->I,
                    $infoData->P,
                    $infoData->loanFee,
                    $infoData->mgrtFee,
                    $infoData->amount,
                    $infoData->balance,
                    '<a href="#" onclick="'.$remark.'">'.Stringlimiter($infoData->remark,15).'</a>',
                    $infoData->branch_name,
                    @$initiated->first_name.' '.@$initiated->last_name,
                    $created_by->first_name.' '.$created_by->last_name,
                    $infoData->paymentDate,
                    ($infoData->method==1)?'<span class="badge badge-info">Cash</span>':'<span class="badge badge-primary">Wallet</span>',
                    $infoData->created_at,
                ];
            }
            $json['data'][] = [
                '<b>#</b>',
                'Total',
                '',
                '',
                '',
                '<span class="badge badge-secondary">'.$totalLoanInterestPaid.'</span>',
                '<span class="badge badge-warning">'.$totalLoanPrincipalPaid.'</span>',
                '<span class="badge badge-primary">'.$totalLoanFeeAmount.'</span>',
                '<span class="badge badge-danger">'.$totalLoanMrgtAmount.'</span>',
                '<span class="badge badge-success">'.$totalRepaymentAmount.'</span>',
                '<span class="badge badge-info">'.$totalLoanBalance.'</span>',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        return $json;
    }
    public function areaReport(){
        if (!cando('loan-arrear-report|root')) return json_encode(['status'=>0,'message'=>'Access Denied!']);
        $data = [
            'category_name' => 'loan',
            'page_name' => 'loan_area',
            'page_menu' => 'loan_area',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'product'=>loan_product::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),
            'branch'=>branch::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),
        ];
        return view('company.loan.report.arrears')->with($data);
    }
    public function generateArrearReport(Request $request){
        if (!cando('loan-arrear-report|root')) return json_encode(['status'=>0,'message'=>'Access Denied!']);
        $loan =loan::whereHas('user', function ($q){
            $q->where(['company_id'=>auth()->user()->company_id]);
        })->where('loan_status_id',1);
        if($request->branch != null){
            $request->branch = Crypt::decryptString($request->branch);
            $loan->where('LoanBranch_id',$request->branch);
        }
        if($request->officer != null){
            $usertemp =User::where('uuid',$request->officer)->first();
            $loan->where('LoanOfficer_id',$usertemp->id);
        }
        //loan_product_id
        if($request->loan_product_id != null){
            $request->loan_product_id = Crypt::decryptString($request->loan_product_id);
            $loan->where('loan_product_id',$request->loan_product_id);
        }
        $loan = $loan->get();
        $sn =0;
        $loanrec = [];
        $json = [];
        if($loan){
            foreach($loan as $loanData){
                $accBal =0;
                foreach($loanData->user->wb_account_details as $useraccount){
                    $accBal+=$useraccount->user_wallet->balance;
                }
                $EncloanID =  Crypt::encryptString($loanData->id);
                $repayment = json_decode(app('App\Http\Controllers\LoanController')->loanRepaymentBreakDown($EncloanID,new Request));
                $arrear_in_days = 0;
                $overdueTotal = 0;
                foreach($repayment->repaymentTable as $repaymentData){
                    if(isset($repaymentData->repaymentDateDueloan) && isset($repaymentData->daysmissed)){
                        if(($repaymentData->daysmissed) > 0){

                            if($request->searchType == 'date' && $request->from  != null && $request->to  != null){
                                if($repaymentData->repaymentDateDueloan >= $request->from && $repaymentData->repaymentDateDueloan <= $request->to){
                                    if($arrear_in_days == 0){
                                        $arrear_in_days = $repaymentData->daysmissed;//initiate to the first missed date
                                        }
                                        $overdueTotal = $repaymentData->currentOutstanding;//Getting the last outstanding loan balance which some to all the loan balance to that date
                                }
                            }else if($request->searchType == 'day' && $request->from  != null && $request->to  != null){
                                if($request->startfrom != null){
                                    if($repaymentData->repaymentDateDueloan >= $request->startfrom){

                                    if($repaymentData->daysmissed >= $request->from && $repaymentData->daysmissed <= $request->to ){
                                        if($arrear_in_days == 0){
                                            $arrear_in_days = $repaymentData->daysmissed;//initiate to the first missed date
                                        }
                                            $overdueTotal = $repaymentData->currentOutstanding;//Getting the last outstanding loan balance which some to all the loan balance to that date
                                    }
                                }
                                }else{
                                    if($repaymentData->daysmissed >= $request->from && $repaymentData->daysmissed <= $request->to ){
                                        if($arrear_in_days == 0){
                                            $arrear_in_days = $repaymentData->daysmissed;//initiate to the first missed date
                                        }
                                            $overdueTotal = $repaymentData->currentOutstanding;//Getting the last outstanding loan balance which some to all the loan balance to that date
                                    }
                                }
                            }else{
                                if($arrear_in_days == 0){
                                    $arrear_in_days = $repaymentData->daysmissed;//initiate to the first missed date
                                }
                                    $overdueTotal = $repaymentData->currentOutstanding;//Getting the last outstanding loan balance which some to all the loan balance to that date
                            }

                    }
                }
                }
                if($arrear_in_days > 0){ //if the system got a missed loan in the repayment loop
                    $sn++;
                    $extended =$loanData->AddedLoanLog()->sum('amount');
                    $interestI  = ($loanData->rate/100)*$overdueTotal;
                    $principalP =$overdueTotal - $interestI;
                    $json[] =[
                        'sn'=>$sn,
                        'start_date'=>$loanData->start_at,
                        'loan_officer'=>$loanData->LoanOfficer->first_name.' '.$loanData->LoanOfficer->last_name,
                        'borrower'=>$loanData->user->first_name.' '.$loanData->user->last_name,
                        'phone'=>$loanData->user->phone,
                        'loanref'=>$loanData->ref,
                        'days_in_arrears'=>$arrear_in_days,
                        'overdue_P'=>$principalP,
                        'overdue_I'=>$interestI,
                        'overdue_Fee'=>$loanData->missedFee?$loanData->missedFee:0,
                        'overdue_Penalty'=>$loanData->missedLoanLeft?$loanData->missedLoanLeft:0,
                        'overdue_Balance'=>$overdueTotal+$extended,
                        'totalPoutstanding'=>$loanData->principalLeft,

                        'status'=>"<span class='badge badge-danger'>missed</span>",
                        'extended'=>$extended,
                    ];

                }
            }
        }else{
            return json_encode(['status'=>0,'message'=>'No Record Found!']);
        }
        return json_encode(['message'=>$json,'status'=>1]);
    }
    public function officerPerformance(){
        if (!cando('loanOfficer-performance-report|root')) return json_encode(['status'=>0,'message'=>'Access Denied!']);
        $data = [
            'category_name' => 'loan',
            'page_name' => 'loan_officer_performance',
            'page_menu' => 'loan_officer_performance',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'product'=>loan_product::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),
            'branch'=>branch::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),
        ];
        return view('company.loan.report.officer-performance')->with($data);
    }
    public function generateOfficerPerformanceReport(Request $request){
        if (!cando('loanOfficer-performance-report|root')) return json_encode(['status'=>0,'message'=>'Access Denied!']);
        if($request->officer != null){
            $usertempAs =User::where(['uuid'=>$request->officer,'company_id'=>auth()->user()->company_id,'user_type_id' => 2])->get();
        }else{
            $usertempAs =User::where(['company_id'=>auth()->user()->company_id,'user_type_id' => 2])->get();
        }
        // if($request->branch != null){
            // $request->branch = Crypt::decryptString($request->branch);
            // $loan->where('LoanBranch_id',$request->branch);
        // }
        $json = [];
        $sn =0;
        if($usertempAs){
            foreach($usertempAs as $usertemp){
                $totalSaving = 0;
                $branch = $usertemp->branch->branch_name;//var
                $NoClient = count($usertemp->clients);//var
                $loan  =loan::where('LoanOfficer_id',$usertemp->id)->where('loan_status_id',1);

                $clientLoan =loan::where('LoanOfficer_id',$usertemp->id)->where('loan_status_id',1);
                // $noClientWithLoan =loan::where('LoanOfficer_id',$usertemp->id)->count();
                $noClientWithLoan = Loan::where('LoanOfficer_id', $usertemp->id)->where('loan_status_id',1);

                if($request->from != null && $request->to != null){
                    $loan =$loan->where('start_at','>=',$request->from)
                    ->where('start_at','<=',$request->to);
                    $clientLoan = $clientLoan->where('start_at','>=',$request->from)
                    ->where('start_at','<=',$request->to);
                    $noClientWithLoan =$noClientWithLoan->where('start_at','>=',$request->from)
                    ->where('start_at','<=',$request->to);
                }
                // if($request->branch != null){
                //     $loan->where('branch_id','>=',$request->branch);
                // }
                $noClientWithLoan = $noClientWithLoan->distinct('user_id')->count();//var

                $totalP = $loan->sum('principalLeft');//var
                $totalI = $loan->sum('InterestLeft');//var
                // dd("$totalP $totalI");
                $P = $loan->sum('principalLeft');//var where('loan_status_id',1)
                $I = $loan->sum('InterestLeft');//var where('loan_status_id',1)
                $totalOutstanding = $loan->sum('loanBalance');//var where('loan_status_id',1)
                $totalDisbursed = $loan->count();//var ->where('loan_status_id',1)
                $totalDisbursedAMT = $loan->sum('principal');//var ->where('loan_status_id',1)
                $writingOffLoan = $loan->where('loan_status_id',7)->count();//
                $writingOffLoanAMT = $loan->where('loan_status_id',7)->sum('loanBalance');//
                $sn++;
                if(count($usertemp->clients)>0){
                    foreach($usertemp->clients as $userdata){
                        if(count($userdata->wb_account_details)>0){
                            foreach($userdata->wb_account_details as $userdataWbData){
                                if(isset($userdataWbData->user_wallet->balance)){
                                    $totalSaving += $userdataWbData->user_wallet->balance;
                                }
                            }
                        }
                    }
                }
                $json[] =[
                        'sn'=>$sn,
                        'uuid'=>$usertemp->uuid,
                        'branch'=>$branch,
                        'loan_officer'=>$usertemp->first_name.' '.$usertemp->last_name,
                        'no_client'=>$NoClient,
                        'no_client_loan'=>$noClientWithLoan,
                        'principal'=>number_format($P),
                        'interest'=>number_format($I),
                        'outstanding'=>number_format($totalOutstanding),
                        'totalDisbursed'=>($totalDisbursed),
                        'totalDisbursedAMT'=>number_format($totalDisbursedAMT),
                        'writingOffLoan'=>($writingOffLoan),
                        'writingOffLoanAmt'=>number_format($writingOffLoanAMT),
                        'totalP'=>number_format($totalP),
                        'totalI'=>number_format($totalI),
                        'totalSaving'=>number_format($totalSaving),
                        'totalLoans'=>$clientLoan->count()
                        // 'status'=>"<span class='badge badge-primary'>missed</span>",
                    ];
                // }
            }
        }
        return json_encode(['message'=>$json,'status'=>1]);
    }
    public function generateStaffClient($id){
        $clientsOfficer = User::where(['uuid'=>$id])->first();
        $clients =  User::with(['wb_account_details', 'wb_account_details.user_wallet'])
        ->where(['user_type_id' => 1, 'account_officer_id' => $clientsOfficer->id])
        ->get();
        return $clients;
    }
    public function generateStaffClientWithLoan($id,Request $request){
        $clientsOfficer = User::where(['uuid' => $id])->first();

        $ClientLoan = loan::with('user')
            ->where('LoanOfficer_id', $clientsOfficer->id)->where('loan_status_id', 1);
        // if ($request->status == 'active') {
            // $ClientLoan = $ClientLoan->;
        // }
        if($request->clientURL == 'loan-client'){
            $ClientLoan=  $ClientLoan->groupBy('user_id');
        }
        return $ClientLoan->get();
    }
    public function dueLoanCollection(Request $request){

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 1000);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];

        $loan =loan::whereHas('user', function ($q){
            $q->where(['company_id'=>auth()->user()->company_id]);
        })->where('loan_status_id',1);
        $length =1000;
        if($request->branch != null && $request->branch != ''){
            $request->branch = Crypt::decryptString($request->branch);
            $loan->where('LoanBranch_id',$request->branch);
        }
        if($request->officer != null || $request->officer != 'null'){
            $usertemp =User::where('uuid',$request->officer)->first();
            if($usertemp){
            $loan->where('LoanOfficer_id',$usertemp->id);
            }

        }
        $loan = $loan->get();

        $sn =0;
        $loanrec = array(
            'draw' => 1,
            'recordsTotal' => 1,
            'recordsFiltered' => 1,
            'data' => [],
        );
        $loanrec =[];
        if($loan){
            foreach($loan as $loanData){
                //$Paments = $loanData->Paments->amount;
                $LoanRepayment = LoanRepayment::where(['loan_id'=>$loanData->id,'status_id'=>1])->sum('amount');
                $accBal =0;
                foreach($loanData->user->wb_account_details as $useraccount){
                    $accBal+=$useraccount->user_wallet->balance;
                }
                $EncloanID =  Crypt::encryptString($loanData->id);
                $repayment = json_decode(app('App\Http\Controllers\LoanController')->loanRepaymentBreakDown($EncloanID,new Request));
                $loanmissed =0;
                foreach($repayment->repaymentTable as $repaymentData){
                    if(isset($repaymentData->repaymentDateDueloan)){
                        // return $repaymentData;
                        if($repaymentData->currentRepayStatus == 3) {$loanmissed +=  $repayment->repaymentAmut;}
                        if(($request->from==null && $request->to==null) || ($repaymentData->repaymentDateDueloan >= $request->from && $repaymentData->repaymentDateDueloan <= $request->to)){
                            $sn++;
                            $link =url('company/'.Auth()->user()->company->short_name.'/loan/pay-loan/'.$EncloanID);
                            // return $repaymentData;

                            $loanrec['recordsFiltered']=1;
                            $loanrec['data'][] =[
                                $sn,
                                "<a class='btn btn-flat' href='".$link."'>".$loanData->ref."</a>",
                                $loanData->user->first_name.' '.$loanData->user->last_name,
                                '   |   ',
                                $accBal,
                                $loanData->principal,
                                $loanData->LoanFee,
                                $loanData->loanBalance,
                                $loanData->grace_at,
                                $LoanRepayment,
                                $repayment->repaymentAmut,
                                $repaymentData->loanBalanceRecurring,
                                '',
                                $repaymentData->outstandingpay,
                                $loanData->user->myOfficer->first_name.' '.$loanData->user->myOfficer->last_name,

                                // $loanData->user->phone,
                                // $loanData->rate,
                                // $repaymentData->repaymentDateDueloan,

                                // $repaymentData->outstandingpay,//due
                                // $repayment->totalPaid,
                                // ($repayment->IPLeftNo),
                                // $repayment->maturity,
                                // $loanData->branch->branch_name,
                                // $loanData->author->first_name.' '.$loanData->author->last_name,


                                //pending

                            ];
                        }
                    }
                }
            }
        }else{
            return json_encode(['status'=>0,'message'=>'No Record Found!']);
        }
        return $loanrec;
    }
}
