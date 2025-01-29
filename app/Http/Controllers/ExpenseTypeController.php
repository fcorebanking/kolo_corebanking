<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreexpenseTypeRequest;
use App\Http\Requests\UpdateexpenseTypeRequest;
use App\Models\expenseType;
use App\Models\User;
use Illuminate\Http\Request;

class ExpenseTypeController extends Controller
{

    public function index()
    {
        if(!cando('expenses-type|root')) return abort(403) ;

        $data = [

            'category_name' => 'expenses',
            'page_name' => 'expenses_type',
            'page_menu' => 'expenses_type',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'loanID' => false,
            'alt_menu' => 0,
          ];
          return view('company.expenses.type')->with($data);
    }

    public function edit($id)
    {
        if(!cando('expenses-type|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        return json_encode(expenseType::where(['id'=>$id,'company_id'=>auth()->user()->company_id])->first());
    }

    public function storeType(Request $request)
    {
        if(!cando('expenses-type|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        try{
            if(expenseType::where(['name'=>$request->name,'company_id'=>auth()->user()->company_id])->count() > 0){
                return json_encode(['status'=>0,'message'=>"Name Already Exist!"]);
            }
        $expenseType = new expenseType;
        $expenseType->name = $request->name;
        $expenseType->company_id = auth()->user()->company_id;
        $expenseType->created_by = auth()->user()->id;
        $expenseType->modified_by = auth()->user()->id;
        $expenseType->updated_at = date('Y-m-d h:i:s');
        $expenseType->save();
        return json_encode(['status'=>1,'message'=>'Product added']);
        }catch(Exception $e){
        return json_encode(['status'=>0,'message'=>$e->getMessage()]);

        }
    }
    public function update(Request $request)
    {
        if(!cando('expenses-type|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        try{
            if(expenseType::where(['name'=>$request->name,'company_id'=>auth()->user()->company_id])->where('id','!=',$request->type_id)->count() > 0){
                return json_encode(['status'=>0,'message'=>"Name Already Exist!"]);
            }
        $expenseType =  expenseType::where(['id'=>$request->type_id,'company_id'=>auth()->user()->company_id])->first();
        $expenseType->name = $request->name;
        $expenseType->modified_by = auth()->user()->id;
        $expenseType->save();
        return json_encode(['status'=>1,'message'=>'Product Save']);
        }catch(Exception $e){
        return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
    public function ajax_view(Request $request)
    {
        // if (!cando('view-customer-overdraft|root')) return abort(403);
        if(!cando('expenses-type|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];

        $sortColumns = array(
            0 => 'id',
            1 => 'name',
            2 => 'users.first_name',
            3 => 'users.first_name',
            4 => 'created_at',
            5 => 'updated_at',
        );
        $query = expenseType::join('users','users.id','=','expense_types.created_by')
        ->where('expense_types.company_id', auth()->user()->company_id)
        ->select('expense_types.*','users.first_name','users.last_name');
        // $query =  $query->where(['users.type'=>1,'users.company_id'=>auth()->user()->company_id]);
        $is_root = cando('root');

        // if (!$is_root) {
        //     if (!cando('view-customer-overdraft'))
        //         $query = $query->where('users.account_officer_id', auth()->user()->id);
        // }

        if (!empty($filter)) {
            $query->where('users.first_name', 'like', '%' . $filter . '%');
            $query->where('users.last_name', 'like', '%' . $filter . '%');
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
        foreach ($info as $infoData) {
            $loop++;
            $btnlink = $infoData->id;
            $bydata = User::find($infoData->modified_by);
            $byname = '';
            if ($bydata) {
                $byname = $bydata->first_name . ' ' . $bydata->last_name;
            }

            $userReference = $infoData->uuid;

            if ($is_root || cando('view-customer-overdraft')) {
                $btn = "<button class='btn btn-outline-danger btn-sm' onclick=";
                $btn .= "deletefunc('$btnlink','$userReference') >Remove</button>";
                $btn ='<div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  Action
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                  <a class="dropdown-item" href="#" onclick=editfunc('.$btnlink.')>Edit</a>
                  <a class="dropdown-item" href="#"  onclick=deletefunc('.$btnlink.')>Remove</a>
                </div>
              </div>';
            } else {
                $btn = "";
            }

            $json['data'][] = [
                $loop,
                $infoData->name,
                $infoData->first_name . ' ' . $infoData->last_name,
                $byname,
                $infoData->created_at,
                $infoData->updated_at,
                $btn
            ];
        }

        return $json;
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\expenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\expenseType  $expenseType
     * @return \Illuminate\Http\Response
     */


    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateexpenseTypeRequest  $request
     * @param  \App\Models\expenseType  $expenseType
     * @return \Illuminate\Http\Response
     */

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\expenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function destroy(expenseType $expenseType)
    {
        //
    }
}
