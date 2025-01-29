<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpensesRequest;
use App\Http\Requests\UpdateExpensesRequest;
use App\Models\Expenses;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\expenseType;

class ExpensesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!cando('view-expenses|add-expenses|root')) return abort(403) ;
        $data = [

          'category_name' => 'expenses',
          'page_name' => 'expenses',
          'page_menu' => 'expenses',
          'has_scrollspy' => 0,
          'scrollspy_offset' => '',
          'loanID' => false,
          'alt_menu' => 0,
          'expenseType'=>expenseType::where('company_id',auth()->user()->company_id)->get(),
        ];
        return view('company.expenses.expenses')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function viewpending()
    {
        if(!cando('View-Pending-Expenses|root')) return abort(403);
        $data = [
          'category_name' => 'expenses',
          'page_name' => 'PendingExpenses',
          'page_menu' => 'PendingExpenses',
          'has_scrollspy' => 0,
          'scrollspy_offset' => '',
          'loanID' => false,
          'alt_menu' => 0,
          'ExpensesType'=>expenseType::where('company_id',auth()->user()->company_id)->get(),
        ];
        return view('company.expenses.pending_expenses')->with($data);
    }
    public function viewreject()
    {
        if(!cando('View-Pending-Expenses|root')) return abort(403);
        $data = [
          'category_name' => 'expenses',
          'page_name' => 'RejectedExpenses',
          'page_menu' => 'RejectedExpenses',
          'has_scrollspy' => 0,
          'scrollspy_offset' => '',
          'loanID' => false,
          'alt_menu' => 0,
          'ExpensesType'=>expenseType::where('company_id',auth()->user()->company_id)->get(),
        ];
        return view('company.expenses.reject')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreExpensesRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function storeType(Request $request)
    {
        if(!cando('add-expenses|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);
        try{
        if(expenseType::where(['company_id'=>auth()->user()->company_id,'id'=>$request->expense_type_id])->count() == 0) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

            $Expenses = new Expenses;
            $Expenses->expense_type_id = $request->expense_type_id;
            $Expenses->amount = $request->amount;
            $Expenses->expenses_date = $request->expenses_date;
            $Expenses->is_recurring = $request->is_recurring;
            $Expenses->ledger_dr = $request->ledger_dr;
            $Expenses->recurring_expense_period = $request->recurring_expense_period;
            $Expenses->description = $request->description;
            $Expenses->status_id = 2;
            $Expenses->expenses_date = date("Y-m-d h:i:s");
            $Expenses->created_by = auth()->user()->id;
            $Expenses->modified_by = auth()->user()->id;
            if($request->file('invoice_receipt')){
                $destinationPath = 'invoice_receipt';
                $file = $request->file('invoice_receipt');
                $filename = str_replace(' ','_',time().auth()->user()->first_name.$file->getClientOriginalName());
                $path = $file->move($destinationPath,$filename);
                $coverlogo = $destinationPath.'/'.$filename;
                $Expenses->invoice_receipt = ($coverlogo);
                // $Petition->cover_path = $coverlogo;
            }
            $Expenses->save();
            return json_encode(['status'=>1,'message'=>'Expenses Added Successfully moved to Pending!']);
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }

    public function edit($id)
    {
        if(!cando('view-expenses|add-expenses|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);;

        $query = Expenses::join('users','users.id','=','expenses.created_by')
        ->join('expense_types','expenses.expense_type_id','=','expense_types.id')
        ->where(['expense_types.company_id'=> auth()->user()->company_id,'expenses.id'=>$id])
        ->select('expenses.*','users.first_name','users.last_name','expense_types.name')->first();
        return json_encode($query);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateExpensesRequest  $request
     * @param  \App\Models\Expenses  $expenses
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if(!cando('add-expenses|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);;

        try{
        if(expenseType::where(['company_id'=>auth()->user()->company_id,'id'=>$request->expense_type_id])->count() == 0) return json_encode(['status'=>0,'message'=>"Access Denied!"]);
            $Expenses =  Expenses::where('id',$request->type_id)->first();
            $Expenses->expense_type_id = $request->expense_type_id;
            $Expenses->amount = $request->amount;
            $Expenses->expenses_date = $request->expenses_date;
            $Expenses->ledger_dr = $request->ledger_dr;
            $Expenses->is_recurring = $request->is_recurring;
            $Expenses->recurring_expense_period = $request->recurring_expense_period;
            $Expenses->description = $request->description;
            $Expenses->modified_by = auth()->user()->id;
            $Expenses->updated_at = date('Y-m-d h:i:s');
            if($request->file('invoice_receipt')){
                $destinationPath = 'invoice_receipt';
                $file = $request->file('invoice_receipt');
                $filename = str_replace(' ','_',time().auth()->user()->first_name.$file->getClientOriginalName());
                $path = $file->move($destinationPath,$filename);
                $coverlogo = $destinationPath.'/'.$filename;
                $Expenses->invoice_receipt = ($coverlogo);
                // $Petition->cover_path = $coverlogo;
            }
            $Expenses->save();
            return json_encode(['status'=>1,'message'=>'Expenses Updated Successfully!']);
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
    public function ajax_view(Request $request)
    {
        // if (!cando('view-customer-overdraft|root')) return abort(403);
        if(!cando('view-expenses|add-expenses|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);;

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
            3 => 'expenses_date',
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
        $query = Expenses::join('users','users.id','=','expenses.created_by')
        ->join('expense_types','expenses.expense_type_id','=','expense_types.id')
        ->where('expense_types.company_id', auth()->user()->company_id)
        ->select('expenses.*','users.first_name','users.last_name','expense_types.name');
        // $query =  $query->where(['users.type'=>1,'users.company_id'=>auth()->user()->company_id]);
        $is_root = cando('root');

        // if (!$is_root) {
        //     if (!cando('view-customer-overdraft'))
        //         $query = $query->where('users.account_officer_id', auth()->user()->id);
        // }
        if ($request->pending != null) {
            $query = $query->where('expenses.status_id', 2);
        }else if($request->reject != null){
            $query = $query->where('expenses.status_id', 3);
        }else{
            $query = $query->where('expenses.status_id', 1);
        }
        if($request->from != null){
            $query->where('expenses.expenses_date', '>=',$request->from);
        }
        if($request->to != null){
            $query->where('expenses.expenses_date', '<=',$request->to);
        }
        if($request->type != null){
            $query =  $query->where('expenses.expense_type_id',$request->type);
        }
        if (!empty($filter)) {
            $query->where('users.first_name', 'like', '%' . $filter . '%');
            $query->orwhere('users.last_name', 'like', '%' . $filter . '%');
            $query->orWhere('expense_types.name', 'like', '%' . $filter . '%');
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

                    if(cando('approve-Pending-expenses|root')){
                        $pending ='<a class="dropdown-item" href="#" onclick=approvePending('.$btnlink.')>Approve</a>';
                    }
                    if(cando('reject-Pending-expenses|root')){
                        $pending .='<a class="dropdown-item" href="#" onclick=rejectPending('.$btnlink.')>Reject</a>';
                    }
                }
                if($request->reject != null){
                    if(cando('approve-Pending-expenses|root')){
                        $pending ='<a class="dropdown-item" href="#" onclick=approvePending('.$btnlink.')>Approve</a>';
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
                  <a class="dropdown-item" href="#"  onclick=removeExpenses('.$btnlink.')>Remove</a>
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
                $infoData->expenses_date,
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

        if(!cando('approve-Pending-expenses|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);
        $query = Expenses::join('users','users.id','=','expenses.created_by')
        ->join('expense_types','expenses.expense_type_id','=','expense_types.id')
        ->where(['expense_types.company_id'=> auth()->user()->company_id,'expenses.id'=>$id])
        ->select('expenses.*')->count();
            if($query < 1){
                return json_encode(['status'=>0,'message'=>"Invalide access"]);
            }
        $other = Expenses::where('id',$id)->first();
        $other->status_id = 1;
        if($other->save()){
            return json_encode(['status'=>1,'message'=>'Expenses Approved Successfully!']);
        }
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
    public function reject($id){
        try{

        if(!cando('reject-Pending-expenses|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);
        $query = Expenses::join('users','users.id','=','expenses.created_by')
        ->join('expense_types','expenses.expense_type_id','=','expense_types.id')
        ->where(['expense_types.company_id'=> auth()->user()->company_id,'expenses.id'=>$id])
        ->select('expenses.*')->count();
            if($query < 1){
                return json_encode(['status'=>0,'message'=>"Invalide access"]);
            }
        $other = Expenses::where('id',$id)->first();
        $other->status_id = 3;
        if($other->save()){
            return json_encode(['status'=>1,'message'=>'Expenses Rejected Successfully!']);
        }
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Expenses  $expenses
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!cando('add-expenses|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);;
        try{
            $query = Expenses::join('users','users.id','=','expenses.created_by')
        ->join('expense_types','expenses.expense_type_id','=','expense_types.id')
        ->where(['expense_types.company_id'=> auth()->user()->company_id,'expenses.id'=>$id])
        ->select('expenses.*')->count();
            if($query < 1){
                return json_encode(['status'=>0,'message'=>"Invalide access"]);
            }
            Expenses::where('id',$id)->delete();
        return json_encode(['status'=>1,'message'=>'Expenses Deleted Successfully!']);
        }catch(Exception $e){
            return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }

}
