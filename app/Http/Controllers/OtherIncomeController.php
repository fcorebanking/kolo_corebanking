<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOtherIncomeRequest;
use App\Http\Requests\UpdateOtherIncomeRequest;
use App\Models\OtherIncome;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\otherIncomeType;

class OtherIncomeController extends Controller
{
    public function index()
    {
        if(!cando('view-otherincome|add-otherincome|root')) return abort(403);

        $data = [

          'category_name' => 'OtherIncome',
          'page_name' => 'OtherIncome',
          'page_menu' => 'OtherIncome',
          'has_scrollspy' => 0,
          'scrollspy_offset' => '',
          'loanID' => false,
          'alt_menu' => 0,
          'otherIncomeType'=>otherIncomeType::where('company_id',auth()->user()->company_id)->get(),
        ];
        return view('company.other_income.income')->with($data);
    }
    public function viewpending()
    {
        if(!cando('View-Pending-otherincome|root')) return abort(403);
        $data = [
          'category_name' => 'OtherIncome',
          'page_name' => 'PendingOtherIncome',
          'page_menu' => 'PendingOtherIncome',
          'has_scrollspy' => 0,
          'scrollspy_offset' => '',
          'loanID' => false,
          'alt_menu' => 0,
          'otherIncomeType'=>otherIncomeType::where('company_id',auth()->user()->company_id)->get(),
        ];
        return view('company.other_income.pending_income')->with($data);
    }
    public function viewreject()
    {
        if(!cando('View-Pending-otherincome|root')) return abort(403);
        $data = [
          'category_name' => 'OtherIncome',
          'page_name' => 'RejectedOtherIncome',
          'page_menu' => 'RejectedOtherIncome',
          'has_scrollspy' => 0,
          'scrollspy_offset' => '',
          'loanID' => false,
          'alt_menu' => 0,
          'otherIncomeType'=>otherIncomeType::where('company_id',auth()->user()->company_id)->get(),
        ];
        return view('company.other_income.reject')->with($data);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view(Request $requesr)

    {
        //
    }

    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreOtherIncomeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function storeType(Request $request)
    {
        if(!cando('add-otherincome|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);
        if(otherIncomeType::where(['company_id'=>auth()->user()->company_id,'id'=>$request->other_income_type_id])->count() == 0) return json_encode(['status'=>0,'message'=>"Access Denied!"]);
        try{
            $OtherIncome = new OtherIncome;
            $OtherIncome->other_income_type_id = $request->other_income_type_id;
            $OtherIncome->amount = $request->amount;
            $OtherIncome->ledger_cr = $request->ledger_cr;
            $OtherIncome->income_date = $request->income_date;
            $OtherIncome->is_recurring = $request->is_recurring;
            $OtherIncome->recurring_income_period = $request->recurring_expense_period;
            $OtherIncome->description = $request->description;
            $OtherIncome->status_id = 2;
            $OtherIncome->created_by = auth()->user()->id;
            $OtherIncome->modified_by = auth()->user()->id;
            if($request->file('invoice_receipt')){
                $destinationPath = 'income_invoice_receipt';
                $file = $request->file('invoice_receipt');
                $filename = str_replace(' ','_',time().auth()->user()->first_name.$file->getClientOriginalName());
                $path = $file->move($destinationPath,$filename);
                $coverlogo = $destinationPath.'/'.$filename;
                $OtherIncome->invoice_receipt = ($coverlogo);
                // $Petition->cover_path = $coverlogo;
            }
            $OtherIncome->save();
            return json_encode(['status'=>1,'message'=>'OtherIncome Added Successfully!']);
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OtherIncome  $OtherIncome
     * @return \Illuminate\Http\Response
     */
    public function show(OtherIncome $OtherIncome)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\OtherIncome  $OtherIncome
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if(!cando('add-otherincome|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        $query = OtherIncome::join('users','users.id','=','other_incomes.created_by')
        ->join('other_income_types','other_incomes.other_income_type_id','=','other_income_types.id')
        ->where(['other_income_types.company_id'=> auth()->user()->company_id,'other_incomes.id'=>$id])
        ->select('other_incomes.*','users.first_name','users.last_name','other_income_types.name')->first();
        return json_encode($query);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateOtherIncomeRequest  $request
     * @param  \App\Models\OtherIncome  $OtherIncome
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if(!cando('add-otherincome|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);
        if(otherIncomeType::where(['company_id'=>auth()->user()->company_id,'id'=>$request->other_income_type_id])->count() == 0) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        try{
            $OtherIncome =  OtherIncome::where('id',$request->type_id)->first();
            $OtherIncome->other_income_type_id = $request->other_income_type_id;
            $OtherIncome->amount = $request->amount;
            $OtherIncome->income_date = $request->income_date;
            $OtherIncome->ledger_cr = $request->ledger_cr;
            $OtherIncome->is_recurring = $request->is_recurring;
            $OtherIncome->recurring_income_period = $request->recurring_expense_period;
            $OtherIncome->description = $request->description;
            $OtherIncome->modified_by = auth()->user()->id;
            $OtherIncome->updated_at = date('Y-m-d h:i:s');
            if($request->file('invoice_receipt')){
                $destinationPath = 'income_invoice_receipt';
                $file = $request->file('invoice_receipt');
                $filename = str_replace(' ','_',time().auth()->user()->first_name.$file->getClientOriginalName());
                $path = $file->move($destinationPath,$filename);
                $coverlogo = $destinationPath.'/'.$filename;
                $OtherIncome->invoice_receipt = ($coverlogo);
                // $Petition->cover_path = $coverlogo;
            }
            $OtherIncome->save();
            return json_encode(['status'=>1,'message'=>'OtherIncome Updated Successfully!']);
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
    public function ajax_view(Request $request)
    {
        if(!cando('view-otherincome|add-otherincome|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        // if (!cando('view-customer-overdraft|root')) return abort(403);
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];

        $sortColumns = array(
            0 => 'id',
            1 => 'name',
            2 => 'amount',
            3 => 'OtherIncome_date',
            4 => 'is_recurring',
            6 =>  'recurring_expense_period',
            7 => 'description',
            8 => 'invoice_receipt',
            9 => 'users.first_name',
            10 => 'users.first_name',
            11=> 'created_at',
            12 => 'updated_at',
            13 => 'updated_at',
        );
        $query = OtherIncome::join('users','users.id','=','other_incomes.created_by')
        ->join('other_income_types','other_incomes.other_income_type_id','=','other_income_types.id')
        ->where('other_income_types.company_id', auth()->user()->company_id)
        ->select('other_incomes.*','users.first_name','users.last_name','other_income_types.name');
        // $query =  $query->where(['users.type'=>1,'users.company_id'=>auth()->user()->company_id]);
        $is_root = cando('view-otherincome|add-otherincome|root');
        if ($request->pending != null) {
                $query = $query->where('other_incomes.status_id', 2);
        }else if($request->reject != null){
            $query = $query->where('other_incomes.status_id', 3);
        }else{
            $query = $query->where('other_incomes.status_id', 1);
        }
        if($request->from != null){
            $query->where('other_incomes.income_date', '>=',$request->from);
        }
        if($request->to != null){
            $query->where('other_incomes.income_date', '<=',$request->to);
        }
        if($request->type != null){
            $query =  $query->where('other_incomes.other_income_type_id',$request->type);
        }
        if (!empty($filter)) {
            $query->where('users.first_name', 'like', '%' . $filter . '%');
            $query->orwhere('users.last_name', 'like', '%' . $filter . '%');
            $query->orWhere('other_income_types.name', 'like', '%' . $filter . '%');
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
        $loop = 0;
        $info = $query->get();
        $amtTotaL = 0;
        foreach ($info as $infoData) {
            $loop++;
            $btnlink = $infoData->id;
            $bydata = User::find($infoData->modified_by);
            $byname = '';
            if ($bydata) {
                $byname = $bydata->first_name . ' ' . $bydata->last_name;
            }

            $userReference = $infoData->uuid;

            if ($is_root) {
                $pending ='';
                if ($request->pending != null) {

                    if(cando('approve-Pending-otherincome|root')){
                        $pending ='<a class="dropdown-item" href="#" onclick=approvePendingOtherIncome('.$btnlink.')>Approve</a>';
                    }
                    if(cando('reject-Pending-otherincome|root')){
                        $pending .='<a class="dropdown-item" href="#" onclick=rejectPendingOtherIncome('.$btnlink.')>Reject</a>';
                    }
                }
                if($request->reject != null){
                    if(cando('approve-Pending-otherincome|root')){
                        $pending ='<a class="dropdown-item" href="#" onclick=approvePendingOtherIncome('.$btnlink.')>Approve</a>';
                    }
                }

                $btn = "<button class='btn btn-outline-danger btn-sm' onclick=";
                $btn .= "deletefunc('$btnlink','$userReference') >Remove</button>";
                $btn ='<div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  Action
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                  <a class="dropdown-item" href="#" onclick=editfunction('.$btnlink.')>Edit</a>
                  '.$pending.'
                  <a class="dropdown-item" href="#"  onclick=removeIncome('.$btnlink.')>Remove</a>
                </div>
              </div>';
            } else {
                $btn = "";
            }
            $url = url("$infoData->invoice_receipt");
            $i =$infoData->recurring_expense_period;
            $strpost ='';
                            if(substr($i, -1) == 1){
                                $strpost = 'st';
                            }else if(substr($i, -1) == 2){
                                $strpost = 'nd';
                            }else if(substr($i, -1) == 3){
                                $strpost = 'rd';
                            }else{
                                $strpost = 'th';
                            }
                            $amtTotaL +=$infoData->amount;
            $json['data'][] = [
                $loop,
                $infoData->name,
                $infoData->amount,
                $infoData->OtherIncome_date,
                ($infoData->is_recurring)?'Yes':'No',
                ($i)?$i.$strpost:'--',
                $infoData->description,
                "<a href='".$url."' target='_blank'>View Page</a>",
                $infoData->first_name . ' ' . $infoData->last_name,
                $byname,
                $infoData->created_at,
                $infoData->updated_at,
                $btn
            ];
        }
        $json['data'][] = [
            '',
            '',
            '<b>'.$amtTotaL.'</b>',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ];

        return $json;
    }
    public function approve($id){
        try{

        if(!cando('approve-Pending-otherincome|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);
        $query = OtherIncome::join('users','users.id','=','other_incomes.created_by')
        ->join('other_income_types','other_incomes.other_income_type_id','=','other_income_types.id')
        ->where(['other_income_types.company_id'=> auth()->user()->company_id,'other_incomes.id'=>$id])
        ->select('other_incomes.*')->count();
            if($query < 1){
                return json_encode(['status'=>0,'message'=>"Invalide access"]);
            }
        $other = OtherIncome::where('id',$id)->first();
        $other->status_id = 1;
        if($other->save()){
            return json_encode(['status'=>1,'message'=>'OtherIncome Approved Successfully!']);
        }
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
    public function reject($id){
        try{

        if(!cando('reject-Pending-otherincome|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);
        $query = OtherIncome::join('users','users.id','=','other_incomes.created_by')
        ->join('other_income_types','other_incomes.other_income_type_id','=','other_income_types.id')
        ->where(['other_income_types.company_id'=> auth()->user()->company_id,'other_incomes.id'=>$id])
        ->select('other_incomes.*')->count();
            if($query < 1){
                return json_encode(['status'=>0,'message'=>"Invalide access"]);
            }
        $other = OtherIncome::where('id',$id)->first();
        $other->status_id = 3;
        if($other->save()){
            return json_encode(['status'=>1,'message'=>'OtherIncome Rejected Successfully!']);
        }
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OtherIncome  $OtherIncome
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!cando('add-otherincome|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        try{
            $query = OtherIncome::join('users','users.id','=','other_incomes.created_by')
        ->join('other_income_types','other_incomes.other_income_type_id','=','other_income_types.id')
        ->where(['other_income_types.company_id'=> auth()->user()->company_id,'other_incomes.id'=>$id])
        ->select('other_incomes.*')->count();
            if($query < 1){
                return json_encode(['status'=>0,'message'=>"Invalide access"]);
            }
        OtherIncome::where('id',$id)->delete();
        return json_encode(['status'=>1,'message'=>'OtherIncome Deleted Successfully!']);
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
}
