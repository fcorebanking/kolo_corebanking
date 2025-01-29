<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreotherIncomeTypeRequest;
use App\Http\Requests\UpdateotherIncomeTypeRequest;
use App\Models\otherIncomeType;
use App\Models\User;
use Illuminate\Http\Request;
class OtherIncomeTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        if(!cando('otherincome-type|root')) return abort(403);

        $data = [

            'category_name' => 'OtherIncome',
            'page_name' => 'OtherIncome_type',
            'page_menu' => 'OtherIncome_type',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'loanID' => false,
            'alt_menu' => 0,
          ];
          return view('company.other_income.type')->with($data);
    }

    public function edit($id)
    {
        if(!cando('otherincome-type|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        return json_encode(otherIncomeType::where(['id'=>$id,'company_id'=>auth()->user()->company_id])->first());
    }

    public function storeType(Request $request)
    {
        if(!cando('otherincome-type|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        try{
            if(otherIncomeType::where(['name'=>$request->name,'company_id'=>auth()->user()->company_id])->count() > 0){
                return json_encode(['status'=>0,'message'=>"Name Already Exist!"]);
            }
        $otherIncomeType = new otherIncomeType;
        $otherIncomeType->name = $request->name;
        $otherIncomeType->company_id = auth()->user()->company_id;
        $otherIncomeType->created_by = auth()->user()->id;
        $otherIncomeType->modified_by = auth()->user()->id;
        $otherIncomeType->updated_at = date('Y-m-d h:i:s');
        $otherIncomeType->save();
        return json_encode(['status'=>1,'message'=>'Product added']);
        }catch(Exception $e){
        return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
    public function update(Request $request)
    {
        if(!cando('otherincome-type|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

        try{
            if(otherIncomeType::where(['name'=>$request->name,'company_id'=>auth()->user()->company_id])->where('id','!=',$request->type_id)->count() > 0){
                return json_encode(['status'=>0,'message'=>"Name Already Exist!"]);
            }
        $otherIncomeType =  otherIncomeType::where(['id'=>$request->type_id,'company_id'=>auth()->user()->company_id])->first();
        $otherIncomeType->name = $request->name;
        $otherIncomeType->modified_by = auth()->user()->id;
        $otherIncomeType->save();
        return json_encode(['status'=>1,'message'=>'Product Save']);
        }catch(Exception $e){
        return json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }
    public function ajax_view(Request $request)
    {
        // if (!cando('view-customer-overdraft|root')) return abort(403);
        if(!cando('otherincome-type|root')) return json_encode(['status'=>0,'message'=>"Access Denied!"]);

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
        $query = otherIncomeType::join('users','users.id','=','other_income_types.created_by')
        ->where('other_income_types.company_id', auth()->user()->company_id)
        ->select('other_income_types.*','users.first_name','users.last_name');
        // $query =  $query->where(['users.type'=>1,'users.company_id'=>auth()->user()->company_id]);
        $is_root = cando('root');

        // if (!$is_root) {
        //     if (!cando('view-customer-overdraft'))
        //         $query = $query->where('users.account_officer_id', auth()->user()->id);
        // }

        if (!empty($filter)) {
            $query->where('users.first_name', 'like', '%' . $filter . '%');
            $query->where('users.last_name', 'like', '%' . $filter . '%');
            $query->orWhere('Incomother_income_typese.name', 'like', '%' . $filter . '%');
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
}
