<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ThirdParty\BaniController;
use App\Http\Controllers\ThirdParty\GTBController;
use App\Models\banks;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\branch;
use App\Models\BranchWallet;
use App\Models\company_wallet;
use App\Models\EmploymentType;
use App\Models\StaffPayRoll;
use App\Models\StaffPayRollLog;
use App\Models\StaffWallet;
use App\Models\status;
use Illuminate\Support\Facades\Crypt;
use App\Models\StaffpermissionTable;
use Illuminate\Support\Facades\Validator;
use App\Models\Staffrole;
use Illuminate\Support\Facades\DB;
use App\Models\currencies;

class CompanyController extends Controller
{
    // function __construct()
    // {
    //$this->middleware("custom_permission:manage-staff|manage-branch|root");
    //}

    public function index()
    {
        echo "u are in company";
        //  return view('dashboard2');
    }
    public function branch($company_name)
    {
        if (!cando('manage-branch|root'))  abort(403);
        $companyBranchSetting = auth()->user()->company->setting->allow_edit_branch_subscription_status;
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5S',
            'page_menu' => 'company_branch_menu',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'currency'=>currencies::all(),
            'status' => ($companyBranchSetting == 0) ? status::where('id', 1)->orwhere('id', 2)->get() : status::where('id', 1)->orwhere('id', 2)->orwhere('id', 14)->get(),
        ];
        return view('company.branch.branch')->with($data);
    }
    public function get_branch_staff($company_name, $Userid)
    {
        $restrict_sub_status_id = 14;
        $id = (is_numeric($Userid)) ? $Userid : Crypt::decryptString($Userid);
        $user = (is_numeric($Userid)) ?
            User::where(['company_id' => auth()->user()->company_id, 'user_type_id' => 2])->where('status_id', '!=', $restrict_sub_status_id)->select('id', 'uuid', 'first_name', 'last_name', 'username')->get()
            :
            User::where(['company_id' => auth()->user()->company_id, 'branch_id' => $id, 'user_type_id' => 2])->where('status_id', '!=', $restrict_sub_status_id)->select('id', 'uuid', 'first_name', 'last_name', 'username')->get();
        return json_encode($user);
    }

    public function get_staff_list($company_name, $id)
    {
        $id = Crypt::decryptString($id);
        $companyBranchSetting = auth()->user()->company->setting->allow_edit_branch_subscription_status;
        $sub_restriction_id = 14;
        $user = User::where(['company_id' => auth()->user()->company_id, 'id' => $id, 'user_type_id' => 2])->select('first_name', 'last_name', 'email', 'uuid', 'phone', 'profile_pic', 'username', 'status_id', 'branch_id');
        if (!$companyBranchSetting) {
            $user = $user->where('status_id', '!=', $sub_restriction_id);
        }
        $user = $user->get();
        return json_encode($user);
    }
    public function list_all_staff(Request $request)
    {

        $search = $request->searchTerm;
        $user =  User::where(['company_id' => auth()->user()->company_id, 'user_type_id' => 2])->select('first_name', 'last_name', 'email', 'uuid', 'phone', 'profile_pic', 'username', 'status_id', 'branch_id'); //->whereDate('start_date','>=',date('Y-m-d'))

        if ($search != null) {

            $user = $user->where('first_name', 'like', '%' . $search . '%');

        }
        $user = $user->paginate(60);


        $userinfo = [];
        if ($user) {
            foreach ($user as $userdata) {
                $userinfo[] = ['id' => $userdata->uuid, 'text' => $userdata->first_name . ' (' . $userdata->username. ') '];
            }
            return (json_encode($userinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }

    public function branch_data($id)
    {
        $id = Crypt::decryptString($id);
        return response()->json(branch::where(['id' => $id, 'company_id' => auth()->user()->company_id])->get());
    }
    public function ajax_add_branch($company_name, Request $request)
    {
        try{
        if (!cando('manage-branch|root')) return abort(403);
        $token = csrf_token();
        $validation = Validator::make($request->all(), [
            "branch_name" => 'required',
            "address" => 'required',
            "currency_id"=>'required'
        ]);
        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            $ra = array("status" => 0, "token" => $token, "message" => "$errors");
            return response()->json($ra);
        }
        if($request->code){
            $countBranchCodeCheck = branch::where(['company_id' => auth()->user()->company_id,'code'=>$request->code])->count();
            if($countBranchCodeCheck > 0){
                $ra = array("status" => 0, "message" => "Branch code already used");
                return response()->json($ra);
            }
        }
        $sub_restricted_status_id = 14;
        $countbranch = branch::where(['company_id' => auth()->user()->company_id])->where('status_id','!=',$sub_restricted_status_id);
        $allcount = $countbranch->count();
        if (auth()->user()->company->sub->plan->max_branch > $allcount) {
            $chkbranch = $countbranch->where(['branch_name' => $request->branch_name])->count();
            if ($chkbranch == 0) {
                $acno = time();
                while (BranchWallet::where(['accountno' => $acno])->count() > 0) {
                    $acno = $acno++;
                }
                $branch = new branch;
                $branch->code = $request->code;
                $branch->branch_name = $request->branch_name;
                $branch->currency_id = $request->currency_id;
                $branch->address = $request->address;
                $branch->company_id = auth()->user()->company_id;
                $branch->status_id = 1;
                $branch->created_by = auth()->user()->id;
                if ($branch->save()) {
                    BranchWallet::create([
                        'accountno' => $acno,
                        'balance' => 0.0,
                        'branch_id' => $branch->id,
                        'created_by' => auth()->user()->id
                    ]);
                    return response()->json(["status" => 1, "token" => $token, 'message' => "Branch Added"]);
                } else {
                    return response()->json(["status" => 0, "token" => $token, 'message' => "Operation Failed"],);
                }
            } else {
                return response()->json(["status" => 0, "token" => $token, 'message' => "Duplicate Branch name!"]);
            }
        } else {
            $errormsg = (cando('root|manage-subscription')) ? "<br> <a href='" . url('company/' . Auth()->user()->company->short_name . '/plan') . "'>click to upgrade Plan </a>" : "";
            return response()->json(["status" => 0, "token" => $token, 'message' => "Max Branch Reached Upgrade Plan! $errormsg"]);
        }
    }catch(Exception $error){
        return response()->json(["status" => 0, 'message' => "Error processing request. Note Branch Code is a number"]);
    }
    }
    public function ajax_update_branch($company_name, Request $request)
    {
        try{
        if (!cando('manage-branch|root')) return abort(403);
        $auth = auth()->user();
        $companyBranchSetting = $auth->company->setting->allow_edit_branch_subscription_status;
        $token = csrf_token();
        $sub_restricted_status_id = 14;
        $request->id = Crypt::decryptString($request->id);
        $branch =  branch::find($request->id);
        $user = User::where(['uuid' => $request->manager, 'company_id' => auth()->user()->company_id])->first();
        $branch->branch_name = $request->branch_name;
        $branch->address = $request->address;
        // if($request->code){
        //     $countBranchCodeCheck = branch::where(['company_id' => auth()->user()->company_id,'code'=>$request->code]);
        //     if($countBranchCodeCheck){
        //         $ra = array("status" => 0, "message" => "Branch code already used");
        //         return response()->json($ra);
        //     }
        // }
        if ($request->status == 14 && $companyBranchSetting != 1) {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Invalide Option!"]);
        }
        if ($branch->status_id == $sub_restricted_status_id && $request->status != $sub_restricted_status_id) {
            if ($companyBranchSetting == 1) {
                $totalBranch = count($auth->company->branch);
                if ($totalBranch >= $auth->company->sub->plan->max_branch) {
                    return response()->json(["status" => 0, "token" => $token, 'message' => "Invalide action upgrade your plan!"]);
                }
            } else {
                return response()->json(["status" => 0, "token" => $token, 'message' => "Invalide Option!"]);
            }
        }
        $branch->code = $request->code;
        $branch->status_id = $request->status;
        $branch->branch_manager = ($request->manager != null) ? $user->id : $branch->branch_manager;
        $branch->modified_by = auth()->user()->id;
        $branch->currency_id = $request->currency_id;
        $branch->updated_at = date("Y-m-d h:i:s");

        if ($branch->save()) {
            return response()->json(["status" => 1, "token" => $token, 'message' => "Branch Update!"]);
        } else {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Operation Failed!"]);
        }
    }catch(Exception $error){
        return response()->json(["status" => 0, 'message' => "Error processing request"]);
    }
    }
    public function destroy_branch($company_name, $id)
    {
        if (!cando('manage-branch|root')) {
            abort(403);
            return 0;
        }
        $token = csrf_token();
        $id = Crypt::decryptString($id);

        if (User::where('branch_id', $id)->count() == 0) {
            if (branch::where(['id' => $id, 'company_id' => auth()->user()->company_id])->count() > 0) {
                BranchWallet::where('branch_id', $id)->delete();
                branch::where(['id' => $id, 'company_id' => auth()->user()->company_id])->delete();
                return response()->json(["status" => 1, "token" => $token, 'message' => "Branch Deleted!"]);
            }
        } else {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Operation failed: Some user are register to this branch!"]);
        }

    }

    public function getStaffPayrolDetails($company_name, $id)
    {
        if (!cando('manage-payroll|root')) {
            abort(403);
            return 0;
        }
        $token = csrf_token();

        $staffPayroll = StaffPayRoll::with('bank')->where('staff_id', $id)->first();
        if ($staffPayroll) {

            return response()->json(["status" => 1, "token" => $token, 'message' => "Branch Deleted!", "data" => $staffPayroll]);
        } else {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Operation failed: Some user are register to this branch!"]);
        }
    }
    public function staff($company_name)
    {
        if (!cando('manage-staff|root')) return  abort(403);
        $branch_subscription_status_id = 14;
        $companyBranchSetting = auth()->user()->company->setting->allow_edit_branch_subscription_status;

        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html55',
            'page_menu' => 'company_staff_menu',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'status' => (!$companyBranchSetting) ? status::where(['id' => 1])->orwhere(['id' => 2])->get() : status::where(['id' => 1])->orwhere(['id' => 2])->orwhere('id', $branch_subscription_status_id)->get(),
            'roles' => Staffrole::where(['company_id' => auth()->user()->company_id])->get(),
            'branch' => auth()->user()->company->branch,

        ];
        return view('company.staff.staff')->with($data);
    }

    public function staffPayroll($company_name)
    {

        $branch_subscription_status_id = 14;
        if (!cando('manage-payroll|root')) return  abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'staff_payroll_menu',
            'page_menu' => 'staff_payroll_menu',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'employment_types' => EmploymentType::all(),
            'banks' => banks::all(),
            'roles' => Staffrole::where(['company_id' => auth()->user()->company_id])->get(),
            'branch' => branch::has('author')->where('company_id', auth()->user()->company_id)->where('status_id', '!=', $branch_subscription_status_id)->get(),
        ];

        return view('company.staff.staff-payroll')->with($data);
    }

    public function staffPayrollReport($company_name)
    {
        if (!cando('manage-payroll|root')) return  abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'staff_payroll_report',
            'page_menu' => 'staff_payroll_report',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'branch' => auth()->user()->company->branch,
            'alt_menu' => 0,
        ];

        return view('company.staff.staff-payroll-report')->with($data);
    }
    public function ajax_generate_payroll_report(Request $request)
    {
        if (!cando('manage-payroll|root')) return  abort(403);
        \logInfo("Testing", "Generate user details");
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'desc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'users.created_at',
        );
        $query = StaffPayRollLog::join('users', 'users.id', '=', 'staff_pay_roll_logs.staff_id')
            ->join('statuses', 'statuses.id', '=', 'staff_pay_roll_logs.status_id')
            ->select('staff_pay_roll_logs.*', 'statuses.name as status', 'users.first_name', 'users.last_name', 'users.branch_id', DB::raw('staff_pay_roll_logs.created_at as pay_created_at'));
        $query =  $query->where(['users.user_type_id' => '2', 'users.company_id' => auth()->user()->company_id]);

        $recordsTotal = $query->count();
        $recordsSql = $query->toSql();
        logInfo($recordsTotal, "Total Salary logs");
        logInfo($recordsSql, "Salary sql logs");

        if ($request->from != null && $request->to != null) {
            $query =  $query->whereDate('staff_pay_roll_logs.created_at', '<=', add_day_to_date(0, $request->to))
                ->whereDate('staff_pay_roll_logs.created_at', '>=', add_day_to_date(0, $request->from));
        }
        if ($request->branch != 0) {
            $query =  $query->where('users.branch_id', $request->branch);
        }
        if (!empty($filter)) {
            $query->where('users.first_name', 'like', '%' . $filter . '%');
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
        $sn = 0;

        $deduction = 0;
        $net = 0;
        $gross = 0;
        $difference = 0;

        \logInfo(\count($info), "We are logged in");
        foreach ($info as $infoData) {
            $sn++;
            $btnlink = $infoData->id;
            $deduction += $infoData->total_deduction;
            $net += $infoData->net_salary;
            $gross += $infoData->gross_salary;

            $statusText = $infoData->status_id == 3 ? 'success' : 'warning';

            $statusBadge = "<div class='badge badge-$statusText'>$infoData->status</div>";

            $json["caption"] = "We are going home";

            $json['data'][] = [
                $sn,
                date("Y-m-d", strtotime($infoData->pay_created_at)),
                $infoData->transaction_reference,
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->net_salary,
                $infoData->account_name == null ? "Internal Account" : "Bank Transfer",
                $infoData->payment_mode == 0 ? "Automatic" : "Manual",
                $statusBadge,

                // "<a class='btn btn-info' href='#'>View Payslip</a>",
                $infoData->accountsavingsp,
            ];
        }

        return $json;
    }


    public function fetch_staff_ajax(Request $request)
    {
        $isroot = cando('manage-staff|root');
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'desc'));
        $filter = $search['value'];
        $sortColumns = array( //'branch_id', 'company_valut_id', 'transaction_id' ,'value'
            0 => 'users.id',
            1 => 'users.branch_id',
            2 => 'users.phone',
            3 => 'users.status_id',
            4 => 'users.created_at',
            5 => 'users.created_at',
            6 => 'users.created_at',
            7 => 'users.created_at',
            8 => 'users.created_at',
        );
        $query = User::where(['company_id' => auth()->user()->company_id, 'user_type_id' => 2])->where('status_id', '!=', 12);
        $companyBranchSetting = auth()->user()->company->setting->allow_edit_branch_subscription_status;
        if (!$companyBranchSetting) {
            $restrict_sub_status_id = 14;
            $query = $query->where('status_id', '!=', $restrict_sub_status_id);
        }
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orwhere('users.last_name', 'like', '%' . $filter . '%');
                $query->orwhere('users.phone', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal = $query->count();

        $sortColumnName = isset($sortColumns[$order[0]['column']]) ? ($sortColumns[$order[0]['column']]) : 'users.created_at';
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
        $sn = 0;
        foreach ($info as $infoData) {
            $sn++;
            $btnlink = $infoData->id;
            $staffrole = $infoData->staffpermission ? $infoData->staffpermission->staffrole->name : '';
            $id_decrypt = Crypt::encryptString($btnlink);
            $roleID = $infoData->staffpermission ? $infoData->staffpermission->staffrole->id : 0;
            $editbtn = "'$id_decrypt','$roleID'";
            $uuid = "'$infoData->uuid'";
            //
            $owner = User::where('company_id', auth()->user()->company_id)->first();
            $showdelete = ($owner->id != $infoData->id) ? '<a class="dropdown-item" href="#" onclick=delete_staff("' . $infoData->uuid . '")>Delete</a>' : '';
            $showedit = ($owner->id != $infoData->id) ? '<a class="dropdown-item" href="#" onclick=edit_staff(' . $editbtn . ')>Edit</a>' : '';
            $showvault = cando('manage-vault|root') ? '<a class="dropdown-item" href="#" onclick=popvault(' . $uuid . ')>Vault</a>' : '';
            $showcomission = cando('credit-debit-staff-commission|root') ? '<a class="dropdown-item" href="#" onclick=popcomission(' . $uuid . ')>Commission</a>' : '';
            $btn = (cando('manage-staff|root')) ? '<div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Action
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              ' . $showedit . '
              ' . $showdelete . '
              ' . $showcomission . '
              ' . $showvault . '
            </div>
            </div>' : '<button class="btn btn-secondary" disabled>Delete</button>';
            $json['data'][] = [
                $sn,
                $infoData->first_name . ' ' . $infoData->last_name,
                @$infoData->staff_wallet->accountno,
                @$infoData->staff_wallet->balance,
                @$infoData->staff_wallet->commission,
                @number_format($infoData->staff_settlement_wallet->balance,2)??'None',
                @($infoData->staff_settlement_wallet->wb_account_details->virtual_accnum)??'None',
                ($infoData->branch_id != null) ? $infoData->branch->branch_name : '',
                @$infoData->phone,
                '<span class="shadow-none badge badge-primary">' . (($infoData->status_id != null) ? $infoData->status->name : '--') . '</span>',
                $staffrole,
                ($infoData->created_by != null) ? $infoData->author->first_name : 'Self',
                $infoData->created_at,
                $btn
            ];
        }


        return $json;
    }
    public function fetch_branch_ajax(Request $request)
    {
        $isroot = cando('manage-branch|root');
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'desc'));
        $filter = $search['value'];
        $sortColumns = array( //'branch_id', 'company_valut_id', 'transaction_id' ,'value'
            0 => 'id',
            1 => 'code',
            2 => 'branch_name',
            3 => 'branch_name',
            4 => 'address',
            5 => 'status',
            6 => 'created_at',
            7 => 'created_at',
            8 => 'created_at',
            9 => 'created_at',
            10 => 'created_at',
            11 => 'created_at',
        );
        $query = branch::where(['company_id' => auth()->user()->company_id]);
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('branch_name', 'like', '%' . $filter . '%');
                $query->orwhere('address', 'like', '%' . $filter . '%');
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
        $sn = 0;
        foreach ($info as $infoData) {
            $sn++;
            $btnlink = $infoData->id;
            $id_decrypt = Crypt::encryptString($btnlink);
            $editbtn = "'$id_decrypt'";
            $infoData->address = str_replace('"', '', $infoData->address);
            $editbtnbview = "edit_branch('$id_decrypt','$infoData->branch_manager')";
            $editbtnDelete = "delete_branch('$id_decrypt')";
            $btn = '<div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Action
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <a class="dropdown-item" href="#" onclick=' . $editbtnbview . '>Edit</a>
            <a class="dropdown-item" href="#" onclick=' . $editbtnDelete . '>Delete</a>
            </div>
            </div>';
            $json['data'][] = [
                $sn,
                $infoData->code,
                $infoData->branch_manager ? $infoData->manager->first_name : 'none',
                $infoData->branch_name,
                @$infoData->currency->name,
                (strlen($infoData->address) > 20) ? substr($infoData->address, 0, 20) . '...' : $infoData->address,
                ($infoData->status_id == 1) ? "<span class='shadow-none badge badge-success'>" . $infoData->status->name . "</span>" : "<span class='shadow-none badge badge-warning'>Disabled</span>",
                $infoData->author->first_name,
                $infoData->modified_by ? $infoData->editor->first_name : 'none',
                $infoData->updated_at,
                $infoData->created_at,
                $btn
            ];
        }


        return $json;
    }
    public function fetch_staff_payroll_ajax($company_name)
    {
        if (!cando('manage-payroll|root')) return  abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'company_branch_view',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'staff' => User::where(['status_id' => 1, 'company_id' => auth()->user()->company_id, 'user_type_id' => 2])->get(),
            'alt_menu' => 0,

        ];

        return view('company.staff.staff-payroll-view')->with($data);
    }
    public function ajax_add_staff($company_name, Request $request)
    {
        if (!cando('manage-staff|root')) return  abort(403);
        $token = csrf_token();
        Validator::extend('without_spaces', function ($attr, $value) {
            return preg_match('/^\S*$/u', $value);
        });
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|max:100',
            'lastname' => 'required|max:100',
            'username' => 'required|unique:users|without_spaces|max:30|regex:/\w*$/',
            'email' => 'required|email|unique:users|max:191',
            'password' => 'required',
            'phone' => 'required|regex:/^[-0-9\+]+$/',
            'branch' => 'required',
            'permission' => 'required',
        ], [
            'username.without_spaces' => 'Whitespace not allowed.',
            'email.without_spaces' => 'Whitespace not allowed.',
        ]);
        // if(is_numeric($request->username)){
        //     return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Username should be Alphabt"]);

        // }
        $request->branch = Crypt::decryptString($request->branch);
        if (is_numeric($request->username)) {
            return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => 'Username Should contains at least one letter  or a letter and number']);
        }
        if ($validator->passes()) {
            $allcount = User::where(['company_id' => auth()->user()->company_id, 'user_type_id' => 2])->where('status_id', '!=', 14)->where('status_id', '!=', 12)->count();
            if (auth()->user()->company->sub->plan->max_staff > $allcount) {
                $user = new User;
                $user->first_name = $request->firstname;
                $user->last_name = $request->lastname;
                $user->company_id = auth()->user()->company_id;
                $user->email = $request->email;
                $user->phone = $request->phone;
                $user->branch_id = $request->branch;
                $user->created_by = auth()->user()->id;
                $user->uuid = uniqid();
                $user->status_id = 1;
                $user->sms = 1;
                $user->user_type_id = 2;
                $user->username = $request->username;
                $user->password = bcrypt($request->password);
                $user->save();
                $acno = time();
                while (StaffWallet::where(['accountno' => $acno])->count() > 0) {
                    $acno = $acno++;
                }
                $staffWallet = StaffWallet::create([
                    'accountno' => $acno,
                    'balance' => 0.0,
                    'user_id' => $user->id,
                    'created_by' => auth()->user()->id
                ]);
                if ($user && $staffWallet) {
                    $assignrole = new StaffpermissionTable;
                    $assignrole->staffrole_id = $request->permission;
                    $assignrole->user_id = $user->id;
                    $assignrole->created_by  = auth()->user()->id;
                    ($assignrole->save());
                    return response()->json(["status" => 'bg-success', "token" => $token, 'message' => "Staff Added"]);
                } else {
                    return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Operation Failed"]);
                }
            } else {
                return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Maximum Staff Limit Reached, Upgrade Plan"]);
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
            return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "$errors"]);
        }
    }

    public function ajax_setup_payroll($company_name, Request $request)
    {
        if (!cando('manage-payroll|root')) return  abort(403);
        \logInfo($request->all(), "Payroll setup request");
        $token = csrf_token();

        $validator = Validator::make($request->all(), [
            'employment_type' => 'required|min:0',
            'basic_salary' => 'required|min:0',
            'day_of_payment' => 'required',
            'house_allowance' => 'required|min:0',
            'medical_allowance' => 'required|min:0',
            'special_allowance' => 'required|min:0',
            'provident_fund_contribution' => 'required|min:0',
            'other_allowances' => 'required|min:0',
            'tax_deductions' => 'required|min:0',
            'provident_fund_deduction' => 'required|min:0',
            'other_deductions' => 'required|min:0',
            'gross_salary' => 'required|min:0',
            'staff_id' => 'required|min:0',
            'total_deductions' => 'required|min:0',
            'net_salary' => 'required|min:0',
            'payment_mode' => 'required'
        ]);

        if ($validator->passes()) {
            $payroll = StaffPayRoll::where(['staff_id' => $request->staff_id])->first();

            $bank = banks::where('bank_code', $request->bank)->first();

            if (!$payroll) {

                StaffPayRoll::create([
                    "staff_id" => $request->staff_id,
                    "employment_type_id" => $request->employment_type,
                    "basic_salary" => $request->basic_salary,
                    "day_of_payment" => $request->day_of_payment,
                    "house_allowance" => $request->house_allowance,
                    "medical_allowance" => $request->medical_allowance,
                    "special_allowance" => $request->special_allowance,
                    "pension_deduction" => $request->pension_deduction,
                    "provident_fund_allowance" => $request->provident_fund_contribution,
                    "other_allowances" => $request->other_allowances,
                    "tax_deductions" => $request->tax_deductions,
                    "provident_fund_deduction" => $request->provident_fund_deduction,
                    "other_deductions" => $request->other_deductions,
                    "gross_salary" => $request->gross_salary,
                    "total_deduction" => $request->total_deductions,
                    "net_salary" => $request->net_salary,
                    "bank_id" => $bank ? $bank->id : "",
                    "account_number" => $request->account_number,
                    "account_name" => $request->account_name,
                    "payment_mode" => $request->paymentMethod,
                    "overtime_allowance" => $request->overtime,
                    "transport_allowance" => $request->transport_allowance,
                    "wallet_account_number" => $request->wallet_account_number,
                    "wallet_account_name" => $request->wallet_account_name,
                    "paid_leave" => $request->paid_leave,
                    "unpaid_leave_deduction" => $request->unpaid_leave,
                    "loan_deduction" => $request->loan_deduction

                ]);
            } else {
                $payroll->employment_type_id = $request->employment_type;
                $payroll->basic_salary = $request->basic_salary;
                $payroll->day_of_payment = $request->day_of_payment;
                $payroll->house_allowance = $request->house_allowance;
                $payroll->medical_allowance = $request->medical_allowance;
                $payroll->special_allowance = $request->special_allowance;
                $payroll->pension_deduction = $request->pension_deduction;
                $payroll->provident_fund_allowance = $request->provident_fund_contribution;
                $payroll->other_allowances = $request->other_allowances;
                $payroll->tax_deductions = $request->tax_deductions;
                $payroll->provident_fund_deduction = $request->provident_fund_deduction;
                $payroll->other_deductions = $request->other_deductions;
                $payroll->gross_salary = $request->gross_salary;
                $payroll->total_deduction = $request->total_deductions;
                $payroll->net_salary = $request->net_salary;
                $payroll->bank_id = $bank ? $bank->id : "";
                $payroll->account_number = $request->account_number;
                $payroll->payment_method = $request->paymentMethod;
                $payroll->account_name = $request->account_name;
                $payroll->payment_mode = $request->payment_mode;
                $payroll->overtime_allowance = $request->overtime;
                $payroll->wallet_account_number = $request->wallet_account_number;
                $payroll->wallet_account_name = $request->wallet_account_name;
                $payroll->transport_allowance = $request->transport_allowance;
                $payroll->paid_leave = $request->paid_leave;
                $payroll->unpaid_leave_deduction = $request->unpaid_leave;
                $payroll->loan_deduction = $request->loan_deduction;
                $payroll->save();
            }

            User::where('id', $request->staff_id)->update(['payroll_setup' => 1]);

            return response()->json(["status" => 1, "token" => $token, 'message' => "Staff Payroll setup"]);
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
            // echo $request->email;
            // var_dump( $errors);
            return response()->json(["status" => 0, "token" => $token, 'message' => "$errors"]);
        }
    }

    public function ajax_update_staff($company_name, Request $request)
    {
        $token = csrf_token();

        if (!cando('manage-staff|root')) return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Staff Restricted ( manage Staff Permission)!"]);
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'firstname' => 'required|max:100',
            'lastname' => 'required|max:100',
            'permission' => 'required',
            'phone' => 'required',
            'branch' => 'required',
            'email' => 'required',
        ]);

        if ($validator->passes()) {
            if (!restrict_to_just_my_company(['user_uuid' => $request->id])) return abort(403);
            $auth = auth()->user();
            $request->branch = Crypt::decryptString($request->branch);
            $sub_restricted_status_id = 14;
            $user = User::where('uuid', $request->id)->first();

            $user->first_name = $request->firstname;
            $user->email = $request->email;
            $user->username = $request->username;
            $user->last_name = $request->lastname;
            if ($request->status != null) {
                if ($user->status_id == $sub_restricted_status_id && $request->status != $sub_restricted_status_id) {
                    $totalStaff = count($auth->company->allStaff);
                    if ($totalStaff >= $auth->company->sub->plan->max_staff) {
                        return response()->json(["status" => 0, "token" => $token, 'message' => "Invalide action upgrade your plan!"]);
                    }
                }
                $user->status_id = $request->status;
            }
            $user->phone = $request->phone;
            $user->branch_id = $request->branch;

            $user->sms = 1;
            if ($user->save()) {
                $owner = User::where('company_id', auth()->user()->company_id)->first();
                if ($owner->id != $user->id) {
                    DB::table('staffpermission')->where('user_id', $request->id)->delete();
                    $assignrole =  StaffpermissionTable::where(['user_id' => $user->id]);
                    if ($assignrole->count() > 0) {
                        $assignrole = $assignrole->first();
                    } else {
                        $assignrole = new StaffpermissionTable;
                    }
                    $assignrole->staffrole_id = $request->permission;
                    $assignrole->user_id = $user->id;
                    $assignrole->created_by  = auth()->user()->id;
                    $assignrole->modified_by  = auth()->user()->id;
                    ($assignrole->save());
                }
                return response()->json(["status" => 'bg-success', "token" => $token, 'message' => "Staff Updated!"]);
            } else {
                return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Operation Failed"]);
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
            // echo $request->email;
            // var_dump( $errors);
            return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "$errors"]);
        }
    }
    public function ajax_recycle_staff($company_name, Request $request)
    {
        if (!cando('manage-staff|root')) return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Staff Restricted ( manage Staff Permission)!"]);
        $status = 2;
        $user = User::where(['company_id' => auth()->user()->company_id, 'uuid' => $request->id])->update(['status_id' => $request->status]);
        $token = csrf_token();
        if ($status == 2) {
            return response()->json(["status" => 'bg-success', "token" => $token, 'message' => "Staff Moved to Recycle!"]);
        } else if ($status == 1) {
            return response()->json(["status" => 'bg-success', "token" => $token, 'message' => "Staff Restored!"]);
        }
    }
    public function ajax_deletestaff($company_name, $id)
    {
        if (!cando('manage-staff|root')) return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Staff Restricted ( manage Staff Permission)!"]);

        $user = User::where(['company_id' => auth()->user()->company_id, 'uuid' => $id]);
        $iserid = $user->first();
        StaffpermissionTable::where('user_id', $iserid->id)->delete();

        StaffWallet::where('user_id', $iserid->id)->delete();
        $usergone = $user->delete();
        $token = csrf_token();
        return response()->json(["status" => 'bg-success', "token" => $token, 'message' => "Staff Deleted!"]);
    }

    public function smstest()
    {
        //rest sm,s  api
        if (!cando('root')) {
            abort(403);
            return 0;
        }
        $msg =  "hello welcome just testing this interswitch testing";

        echo sendSMS(7, "$msg", true, false, 'Company Registration', '', 0);
    }
    public function select2Staff(Request $request)
    {
        $search = $request->searchTerm;
        $company_id = auth()->user()->company_id;
        $userID = auth()->user()->id;
        $is_root = cando('root');
        $user = User::where(['status_id' => 1, 'user_type_id' => '2', 'company_id' => auth()->user()->company_id]);

        if (!$is_root) {

            $user = $user->where('account_officer_id', $userID);
        }
        if ($search != null) {
            $user->where(function ($user) use ($search) {
                $user->orWhere('email', 'like', '%' . $search . '%');
                $user->orwhere('first_name', 'like', '%' . $search . '%');
                $user->orWhere('last_name', 'like', '%' . $search . '%');
                $user->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $user = $user->paginate(10);

        $userinfo = [];
        if ($user) {
            foreach ($user as $userdata) {
                $userinfo[] = ['id' => $userdata->uuid, 'text' => $userdata->first_name . ' ' . $userdata->last_name . ' - ' . $userdata->username];
            }
            return (json_encode($userinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function smsAPI(Request $request)
    {
        return sendSMS($request->user_id, "$request->msg", true, true, $request->subject);
    }


    public function createCompanyGtbAccounts(Request $request)
    {
        logInfo($request, "Vpay Webhook Incoming");

        $companyWallets = company_wallet::where(['bank_replaced' => 0])->get();

        $companyNames = [];

        foreach ($companyWallets as $companyWallet) {
            $oldAccount  = $companyWallet->vaccount;

            $adminUser = User::where('company_id', $companyWallet->company_id)->first();

            if ($adminUser->bvn == null) {
                $companyLog = $companyWallet->company->company_name . " does not have a BVN";
            } else {

                $gtbController = new GTBController();
                $requestReference = generateAccountReference();


                $response = $gtbController->createCompanyVirtualAccount($companyWallet->company->company_name, $adminUser->bvn, changePhoneFormatLeadingZero($adminUser->phone), $requestReference);

                if (!$response['status']) {
                    $response['status'] = 0;
                    $companyLog = $response['message'];
                } else {

                    $customer_reference = $requestReference;
                    $virtual_acccount_number = $companyWallet->company->currency->code == "NGN" ? $response['data']['virtual_account_number'] : $response['data']['virtual_account_number'] . "_";

                    $companyWallet->bank_id = 100;
                    $companyWallet->bank_replaced = 1;
                    $companyWallet->vaccount = $virtual_acccount_number;
                    $companyWallet->refaccount = $customer_reference;
                    $companyWallet->save();

                    $companyLog = $companyWallet->company->company_name . " Account number was changed from $oldAccount to $companyWallet->vaccount";
                }
            }

            array_push($companyNames, $companyLog);
        }

        logInfo($companyNames);

        return response()->json(['status' => true, 'message' => 'Companies Account Changed', 'data' => $companyNames]);
    }

    public function createCompanyBaniAccounts(Request $request)
    {

        ini_set('memory_limit', '500000M');
        $companyWallets = company_wallet::where(['bank_replaced' => 0])->get();

        $companyNames = [];

        foreach ($companyWallets as $companyWallet) {
            $oldAccount  = $companyWallet->vaccount;

            $adminUser = User::where('company_id', $companyWallet->company_id)->first();

            if ($adminUser->bvn == null) {
                $companyLog = $companyWallet->company->company_name . " does not have a BVN";
            } else {

                $accountBankType = rand(1, 10);

                $bankName = $accountBankType % 2 == 0 ? "guaranty trust bank" : "9 payment service bank";
                $bankNameTxt = $accountBankType % 2 == 0 ? "Guaranty Trust Bank" : "9 Payment Service Bank";
                $bank_id = $accountBankType % 2 == 0 ? 100 : 232;

                $baniController = new BaniController();
                $requestReference = generateAccountReference();

                $modifiedString = preg_replace('/(WL)/', '$1 ', $companyWallet->company->company_name, 1);

                $response = $baniController->createAccount($modifiedString, "...", changePhoneFormatLeadingZero($adminUser->phone), $adminUser->address, $adminUser->email, $bankName, $adminUser->bvn);

                if (!$response['status']) {
                    $response['status'] = 0;
                    $companyLog = $response['message'];
                } else {

                    $customer_reference = $requestReference;
                    $virtual_acccount_number = $companyWallet->company->currency->code == "NGN" ? $response['data']['virtual_account_number'] : $response['data']['virtual_account_number'] . "_";

                    $companyWallet->bank_id =  $bank_id;
                    $companyWallet->bank_replaced = 1;
                    $companyWallet->vaccount = $virtual_acccount_number;
                    $companyWallet->refaccount = $customer_reference;
                    $companyWallet->save();

                    $companyLog = $modifiedString . " Account number was changed from $oldAccount to $companyWallet->vaccount";

                    $message = "Hi, your old GTB Virtual Business Account ($oldAccount) with Wired Banking Africa has been overridden. Your new account is $companyWallet->vaccount and the Bank Name: $bankNameTxt or Asset Matrix MFB";
                    $smsstate = sendSMS($adminUser->id, $message,true, false, 'Account Replacement', '', 0, false, 2);

                }
            }



            array_push($companyNames, $companyLog);
        }

        logInfo($companyNames);

        return response()->json(['status' => true, 'message' => 'Companies Account Changed', 'data' => $companyNames]);
    }


    public function returnAllBranchAndStaff()
    {
        return getallBranchAndStaff();
    }
}
