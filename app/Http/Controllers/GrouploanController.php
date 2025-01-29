<?php

namespace App\Http\Controllers;

use App\Models\Grouploan;
use App\Models\UserGroupLoan;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
class GrouploanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!cando('view-group-loan|root')) return abort(403) ;
        $data = [
            'category_name' => 'group-loan',
            'page_name' => 'view-group-loan',
            'page_menu' => 'view-group-loan',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'loanID' => false,
            'alt_menu' => 0,
          ];
          return view('company.loan.group.grouploan')->with($data);
    }
    public function groupDatalist(Request $request)
    {
        $search = $request->searchTerm;
        $company_id = auth()->user()->company_id;
        $is_root = cando('root');
        $group = Grouploan::where('company_id',$company_id);

        if ($search != null) {
            $group->where(function ($group) use ($search) {
                $group->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $group = $group->paginate(10);

        $groupinfo = [];
        if ($group) {
            foreach ($group as $groupdata) {
                $groupinfo[] = ['id' => $groupdata->id, 'text' => $groupdata->name];
            }
            return (json_encode($groupinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function assignCustomer()
    {
        if(!cando('assign-group-loan|root')) return abort(403) ;
        $data = [
            'category_name' => 'group-loan',
            'page_name' => 'assign-group',
            'page_menu' => 'assign-group',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'loanID' => false,
            'alt_menu' => 0,
          ];
          return view('company.loan.group.assign')->with($data);
    }
    /** assign-group
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function viewUsersWithGroup(Request $request)
    {
        if(!cando('assign-group-loan|root')) return['message'=>'Unauthorize'];
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 100);
        $order = $request->query('order', array(1, 'asc'));
        $filter = $search['value'];

        $sortColumns = array(
            0 => 'users.id',
            1 => 'users.first_name',
            2 => 'users.last_name',
            3 => 'users.email',
            4 => 'users.username',
            5 => 'wb_account_details.virtual_accnum',
            6 => 'user_wallets.balance',
            7 => 'users.id',
        );

            $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
                ->join('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
                ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
                ->select('users.*','wb_account_details.virtual_accnum', DB::raw('wb_account_details.id as accountID'), DB::raw('savings_products.savings_product as accountsavingsp'), DB::raw('round(SUM(user_wallets.balance),1) as balance'));

            $query =  $query->where('users.status_id', 1);
        $is_root = cando('root|allow-staff-view-all-customers');
        if (!$is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }
        $query =  $query->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id])->groupBy('users.id');
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orwhere('users.last_name', 'like', '%' . $filter . '%');
                $query->orwhere(DB::raw('concat(first_name," ",last_name)'), 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('users.username', 'like', '%' . $filter . '%');
                $query->orWhere('users.email', 'like', '%' . $filter . '%');
                $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
                $query->orWhere('user_wallets.balance', 'like', '%' . $filter . '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
            });
        }

            $sortColumnName = $sortColumns[$order[0]['column']];
            $query->orderBy($sortColumnName, $order[0]['dir'])
                // ->take( $length)
                ->take( $length)
                ->skip($start);

        $recordsTotal = $query->count();
        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );
        $info = $query->get();

        $loop = 0;
        foreach ($info as $infoData) {
            // $loop++;
            $btnlink = $infoData->uuid;
            $UserGroupLoan = UserGroupLoan::where('user_id',$infoData->id)->first();
            $shortname = Auth()->user()->company->short_name;
            $btn = '<input type="checkbox" name="Userid[]" class="checkSingle" value="' . $infoData->id . '"> ';
            $loop++;
            $json['data'][] = [
                $loop,
                $infoData->first_name.' '.$infoData->last_name ,
                @$UserGroupLoan->Group->name,
                $infoData->phone,
                $infoData->virtual_accnum,
                number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                $btn
            ];
        }

        return $json;
    }
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if(!cando('create-group-loan|root')) return['message'=>'Unauthorize'];

        try{
        $request->validate([
            'name' => 'required|string|max:255',
            'max_member' => 'required|integer',
            'max_principal' => 'required|numeric',
            'max_interest' => 'required|numeric',
        ]);
        $secretary =$request->input('secretary')? User::where('uuid',$request->input('secretary'))->first():null;
        $grouploan = $request->group_id ? Grouploan::findOrFail($request->group_id) : new Grouploan();
        $grouploan->name = $request->input('name');
        if($request->input('leader') != null){
            $leader =User::where('uuid',$request->input('leader'))->first();
            $grouploan->leader =$leader->id;
        }
        if($request->input('secretary') != null){
            $leader =User::where('uuid',$request->input('secretary'))->first();
            $grouploan->secretary =$secretary->id;
        }
        if($request->input('manager_id') != null){
            $manager =User::where('uuid',$request->input('manager_id'))->first();
            $grouploan->manager_id =$manager->id;
        }

        $grouploan->max_member = $request->input('max_member');
        $grouploan->max_principal = $request->input('max_principal');
        $grouploan->max_interest = $request->input('max_interest');
        $grouploan->company_id = auth()->user()->company_id;
        $grouploan->save();
        return response()->json(['status'=>true,
            'message' => 'Grouploan created successfully',
            'data' => $grouploan
        ]);
        } catch (Exception $e) {
            return response()->json(['status'=>false,
            'message' => 'An error occurred while creating the group loan: ' . $e->getMessage()
        ]);
        }
    }
    public function edit($id)
    {
        if(!cando('view-group-loan|root')) return abort(403) ;

        $id = Crypt::decryptString($id);
        $grouploan = Grouploan::with(['leaderInfo','secretaryInfo','managerInfo'])->where(['company_id'=>auth()->user()->company_id,'id'=>$id])->first();
        return response()->json($grouploan);
    }
    public function view()
    {
        if(!cando('view-group-loan|root')) return abort(403) ;

        $grouploan = Grouploan::where('company_id',auth()->user()->company_id)->get();
        return response()->json($grouploan);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Grouploan  $grouploan
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request){
       if(!cando('view-group-loan|root')) return response()->json(['status'=>0,'message'=>'Access Denied!']);

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 100);
        $order = $request->query('order', array(1, 'asc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'id',
            1 => 'name',
            2 => 'leader',
            3 => 'secretary',
            4 => 'max_member',
            5 => 'max_principal',
            6 => 'max_interest',
            7 => 'max_interest',
            8 => 'manager_id',
        );
        $query = Grouploan::where(['company_id'=>auth()->user()->company_id]);
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('name', 'like', '%' . $filter . '%');
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
        foreach ($info as $infoData) {
             $btnlink =Crypt::encryptString($infoData->id);
            //  $editGroup =
            $btnedit = "editGroup('$btnlink')";
            $btndelete = "deleteGroup('$btnlink')";
            $GroupDetails = "GroupDetails('$btnlink')";
            // GroupDetails
            $btn = '<div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item"  href="#" onclick="'.$GroupDetails.'">View Loan Summery</a>
                <a class="dropdown-item delete-btn"  href="/company/_/loan/Ongoing?group='.$btnlink.'" >View Loans</a>
                <a class="dropdown-item edit-btn" href="#"  onclick="'.$btnedit.'">Edit</a>
                    <a class="dropdown-item delete-btn"  href="#" onclick="'.$btndelete.'" >Delete</a>
                </div>
            </div>';
                $loop++;
                $json['data'][] = [
                    $loop,
                    $infoData->name,
                    @$infoData->leaderInfo->first_name.' '.@$infoData->leaderInfo->last_name,
                    @$infoData->secretaryInfo->first_name.' '.@$infoData->secretaryInfo->last_name,
                    $infoData->max_member,
                    $infoData->max_principal,
                    $infoData->max_interest.'%',
                    count(@$infoData->UserGroupLoan),
                    @$infoData->managerInfo->first_name.' '.@$infoData->managerInfo->last_name,
                    $btn
                ];
            }
        return $json;
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGrouploanRequest  $request
     * @param  \App\Models\Grouploan  $grouploan
     * @return \Illuminate\Http\Response
     */
    public function PostAssignCustomer(Request $request)
    {
        if(!cando('assign-group-loan|root')) return response()->json(['status'=>false,'message' => 'Unauthorize access ' ]);
        try{
            $request->validate([
                'group_id' => 'required',
                'Userid' => 'required',
                'action' => 'required',
            ]);
            $auth =auth()->user();
            $company_id = $auth->company_id;
            $GroupLoan = Grouploan::where(['id'=>$request->group_id,'company_id'=>$company_id])->first();
            if($GroupLoan){
                $totalUser = count(@$GroupLoan->UserGroupLoan);
                $expectedUser = $totalUser + count($request->Userid);
                $maxCustomer = $GroupLoan->max_member;
                $available = $maxCustomer - $totalUser;
                if($expectedUser > $maxCustomer)  return response()->json(['status'=>false,'message' => 'Maximum Member Exceeded Available space is '.$available]);
                foreach($request->Userid as $uid){
                    $user = User::where(['id'=>$uid,'company_id'=>$company_id])->first();
                    if(!$user)return response()->json(['status'=>false,'message' => 'Error Processing User' ]);
                    $UserGroupLoan = UserGroupLoan::where(['user_id'=>$uid])->delete();
                    if($request->action == 'assign'){
                        $NewUserGroupLoan =new UserGroupLoan;
                        $NewUserGroupLoan->user_id = $uid;
                        $NewUserGroupLoan->group_id = $request->group_id;
                        $NewUserGroupLoan->created_by = $auth->id;
                        $NewUserGroupLoan->save();
                    }
                }
                return response()->json(['status'=>true,
            'message' => 'Done '
        ]);
            }
            return response()->json(['status'=>false,
            'message' => 'An Error Occured '
        ]);
        } catch (Exception $e) {
            return response()->json(['status'=>false,
            'message' => 'An error occurred while creating the group loan: ' . $e->getMessage()
        ]);
        }
    }
    public function viewGroupLoanDetails($id){
        if(!cando('view-group-loan|root')) return response()->json(['status'=>false,'message' => 'Unauthorize access ' . $e->getMessage()]);
        $id =Crypt::decryptString($id);


        $loanDetails = Grouploan::where(['grouploans.company_id'=>auth()->user()->company_id,'grouploans.id'=>$id,'loans.loan_status_id'=>1])
        ->join('user_group_loans','user_group_loans.group_id','grouploans.id')
        ->join('users','users.id','user_group_loans.user_id')
        ->join('loans','loans.user_id','users.id')
        ->select(DB::raw('sum(loans.loanBalance) as balance'),DB::raw('sum(loans.principal) as principal'),DB::raw('sum(loans.principalLeft) as principalLeftx'),DB::raw('sum(loans.InterestLeft) as InterestLeftx'))->first();
        $ray = ['loanBalance'=>$loanDetails->balance??0,'principal'=>$loanDetails->principal??0,'principalLeft'=>$loanDetails->principalLeftx??0,'InterestLeft'=>$loanDetails->InterestLeftx??0];
        $loanRepayment = Grouploan::where(['grouploans.company_id'=>auth()->user()->company_id,'grouploans.id'=>$id])
        ->join('user_group_loans','user_group_loans.group_id','grouploans.id')
        ->join('users','users.id','user_group_loans.user_id')
        ->join('loans','loans.user_id','users.id')
        ->join('loan_repayments','loan_repayments.loan_id','loans.id')
        ->select(DB::raw('sum(loan_repayments.amount) as totalPaid'))->first();
        $ray['paid']=$loanRepayment->totalPaid??0;
        $CustomerBalance = Grouploan::where(['grouploans.company_id'=>auth()->user()->company_id,'grouploans.id'=>$id])
        ->join('user_group_loans','user_group_loans.group_id','grouploans.id')
        ->join('users','users.id','user_group_loans.user_id')
        ->join('wb_account_details','users.id','wb_account_details.user_id')
        ->join('user_wallets','user_wallets.wb_account_detail_id','wb_account_details.id')
        ->select('user_wallets.id',DB::raw('sum(user_wallets.balance) as savingBalance'))->first();
        $ray['savingBalance']=$CustomerBalance->savingBalance??0;

        return $ray;
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Grouploan  $grouploan
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!cando('create-group-loan|root')) return response()->json(['status'=>false,'message' => 'Unauthorize access ' . $e->getMessage()]);

        $id = Crypt::decryptString($id);
        Grouploan::where(['company_id'=>auth()->user()->id,'id'=>$id])->delete();
        return response()->json(['status'=>true,
        'message' => 'Group Deleted!'
    ]);
    }
}


