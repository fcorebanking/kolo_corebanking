<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\StorePendingRepaymentRequest;
use App\Http\Requests\UpdatePendingRepaymentRequest;
use App\Models\PendingRepayment;
use App\Models\branch;
use App\Models\loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class PendingRepaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!cando('view-loan-pending-repayment|root')) return abort(403);
        $data = [
            'category_name' => 'loan',
            'page_name' => 'pending_loan_repayment',
            'page_menu' => 'pending_loan_repayment',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'branch'=>branch::where('company_id',auth()->user()->company_id)->get(),
        ];
        return view('company.loan.pendingrepayment')->with($data);
    }
    public function display_pending(Request $request)
    {
        if (!cando('view-loan-pending-repayment|root')) return [];

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'id',
            1 => 'loans.ref',
            2 => 'users.first_name',
            3 => 'accountType',
            4 => 'amount',
            5 => 'method',
            6 => 'created_by',
            7 => 'status_id',
            8 => 'payment',
            9 => 'created_at',
        );
        $query = PendingRepayment::join('loans','loans.id','=','pending_repayments.loan_id')
        ->join('users','users.id','=','loans.user_id')->where(['pending_repayments.company_id'=>auth()->user()->company_id,'pending_repayments.status_id'=>2])
        ->select('pending_repayments.*','users.first_name','users.last_name','loans.ref');
        $is_root = cando('root');
        if (!$is_root) {
            $query = $query->where('pending_repayments.created_by', auth()->user()->id);
        }
        if (!empty($filter)) {
            $query->where('loans.ref', 'like', '%' . $filter . '%');
        }
        if ($request->staff != null) {
            $staffid = User::where(['uuid' => $request->staff, 'company_id' => auth()->user()->company_id])->first();
            if ($staffid) {
                $query->where('pending_repayments.created_by', $staffid->id);
            } else {
                $query->where('pending_repayments.created_by', 0);
            }
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
        foreach ($info as $infoData) {
            $loop++;
            $btnlink = $infoData->id;
            $created_by = User::find($infoData->created_by);
            $btn = '<input type="checkbox" name="pendingid[]" value="' . $infoData->id . '">';
            $json['data'][] = [
                $loop,
                $infoData->ref,
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->account_type,
                number_format($infoData->amount, 2),
                $infoData->method,
                $created_by->first_name . ' ' . $created_by->last_name,
                $infoData->payment,
                $infoData->created_at,
                $btn,
            ];
        }

        return $json;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function batch_approval_rejected(Request $request)
    {//pendingid
        if (!cando('approve-loan-repayment|root')) return abort(403);

        $success = '';
        $fail = '';
        try{
            $pendingid =$request->pendingid;
            if($pendingid != null ){
        for($i=0;$i<count($pendingid);$i++){
            $repayID = $pendingid[$i];
            if($request->action ==1){
            $pend = PendingRepayment::where(['id'=>$repayID,'company_id'=>auth()->user()->company_id])->first();
            if($pend){
                $pend->loanID= Crypt::encryptString($pend->loan_id);
                $repayment = (app('App\Http\Controllers\LoanController')->generalpayloan($pend));
                if($repayment['status']==1){
                    unset($pend->loanID);
                    $success .= '<br>'.$repayment['message'];
                    $pend->status_id =1;
                    $pend->save();
                }else{
                    $fail .= '<br>'.$repayment['message'];
                }
            }else{
                $fail .= '<br>Unaccessable Data!';
            }
        }else{
            if(PendingRepayment::where(['id'=>$repayID,'company_id'=>auth()->user()->company_id])->delete()){
                $success .= '<br> Request Deleted<br>';
            }else{
                $fail .= '<br>Fail to delete request!';
            }
        }
        }
        return json_encode(['status'=>1,'message'=>$success.' '.$fail]);
    }
    return json_encode(['status'=>0,'message'=>"Error!"]);

    }catch(Exception $e){
        return json_encode(['status'=>0,'message'=>$e->getMessage()]);
    }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePendingRepaymentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function transferRepaymentAuto(Request $request)
    {
        if (!cando('root')) return abort(403);

        $request->loanid = Crypt::decryptString($request->loanid);
        if($request->to != null && $request->loanid != null){
            $loanData = loan::where(['id'=>$request->loanid])->first();
            // return redirect()->back()->with('fail', 'Invalid Loan Data!'.$request->loanid);

            if($loanData){
                if(User::where(['id'=>$loanData->user_id,'company_id'=>auth()->user()->company_id])->count() > 0){
                    $todata = User::where(['uuid'=>$request->to,'company_id'=>auth()->user()->company_id])->first();

                    $repay = LoanRepayment::where(['loan_id'=>$loanData->id])->update(['created_by'=>$todata->id]);
                        return redirect()->back()->with('success', 'All repayment for this Loan as been transfer to the set user!');
                }
            }else{
            return redirect()->back()->with('fail', 'Invalid Loan Data!');

            }
        }else{
            return redirect()->back()->with('fail', 'Invalid Input!');

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PendingRepayment  $pendingRepayment
     * @return \Illuminate\Http\Response
     */
    public function swapall()
    {
        $repay = LoanRepayment::all();
        foreach($repay as $repayData){
            $repay = LoanRepayment::where(['id'=>$repayData->id])->update(['initiator_id'=>$repayData->created_by]);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PendingRepayment  $pendingRepayment
     * @return \Illuminate\Http\Response
     */
    public function edit(PendingRepayment $pendingRepayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePendingRepaymentRequest  $request
     * @param  \App\Models\PendingRepayment  $pendingRepayment
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePendingRepaymentRequest $request, PendingRepayment $pendingRepayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PendingRepayment  $pendingRepayment
     * @return \Illuminate\Http\Response
     */
    public function destroy(PendingRepayment $pendingRepayment)
    {
        //
    }
}
