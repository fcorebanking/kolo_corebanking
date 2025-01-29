<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Staffrole;
use App\Models\StaffpermissionTable;
use App\Models\Custom_role_as_permission;
use Illuminate\Support\Facades\Validator;

class StaffpermissionController extends Controller
{
    function __construct()
    {
        $this->middleware("custom_permission:manage-role|role-edit|role-create|role-delete|role-list|root");
    }

    public function index($company_name)
    {
        if (!cando('role-list|role-manage|root')) {
            abort(403);
            return 0;
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'role_permisson',    
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'permission' => Permission::all(),
        ];
        // $pageName = 'html5';

        return view('company.permission.permission')->with($data);
    }
    public function view_roles($company_name)
    {
        if (!cando('role-list|role-manage|root')) {
            abort(403);
            return 0;
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'role_permisson2',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'roles' => Staffrole::where(['company_id' => auth()->user()->company_id])->get(),

        ];
        // $pageName = 'html5';

        return view('company.permission.permission_table')->with($data);
    }
    public function get_permission($company_name, $id)
    {

        $company = auth()->user()->company_id;

        $permission =  Custom_role_as_permission::whereHas('staffrole', function ($q) use (&$company) {
            $q->where(['company_id' => $company]);
        })->where(['staffrole_id' => $id])->get();
        return  json_encode($permission);
    }
    public function add_role(Request $request)
    {
        if (!cando('role-create|role-manage|root|role-list')) {
            abort(403);
            return 0;
        }
        $token = csrf_token();

        $validator = Validator::make($request->all(), [

            'role_name' => 'required|string',
            'permission' => 'required',

        ]);
        if ($validator->passes()) {
            $company = auth()->user()->company_id;
            if (Staffrole::where(['name' => $request->role_name, 'company_id' => $company])->count() == 0) {
                $issert = 0;
                $staffrole = new Staffrole;
                $staffrole->name = $request->role_name;
                $staffrole->company_id = $company;
                $staffrole->created_by = auth()->user()->id;
                if ($staffrole->save()) {
                    if (!empty($request->permission)) {

                        foreach ($request->permission as $value) {

                            $insertdata = new Custom_role_as_permission;
                            $insertdata->staffrole_id = $staffrole->id;
                            $insertdata->permission_id = $value;
                            if ($insertdata->save()) {
                                $issert++;
                            }
                        }
                    }
                }

                if ($issert) {

                    return response()->json(["status" => 'bg-success', "token" => $token, 'message' => "Permission Added!"]);
                } else {
                    return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Operation Failed!"]);
                }
            } else {
                return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Role name already exist"]);
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }

            return response()->json(['status' => 'bg-danger', 'token' => $token, 'message' => $errors]);
        }
    }

    public function update_role(Request $request)
    {
        if (!cando('role-edit|role-manage|root|role-list')) {
            abort(403);
            return 0;
        }
        $token = csrf_token();

        $validator = Validator::make($request->all(), [

            'edit_role_name' => 'required|string',
            'edit_permission' => 'required',
            'id' => 'required|integer',

        ]);
        if ($validator->passes()) {

            $staffrole =  Staffrole::where(['id' => $request->id, 'company_id' => auth()->user()->company_id])
                ->update(['name' => $request->edit_role_name]);


            $issert = 0;



            if ($staffrole) {
                if (!empty($request->edit_permission)) {
                    Custom_role_as_permission::where(['staffrole_id' => $request->id])->delete();
                    foreach ($request->edit_permission as $value) {

                        $insertdata = new Custom_role_as_permission;
                        $insertdata->staffrole_id = $request->id;
                        $insertdata->permission_id = $value;
                        if ($insertdata->save()) {
                            $issert++;
                        }
                    }
                }
            }

            if ($issert) {

                return response()->json(["status" => 'bg-success', "token" => $token, 'message' => "Permission Added!"]);
            } else {
                return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "Operation Failed!"]);
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }

            return response()->json(['status' => 'bg-danger', 'token' => $token, 'message' => $errors]);
        }
    }
    public function destroy($company_name, $id)
    {
        if (!cando('role-delete|role-manage|root|role-list')) {
            abort(403);
            return 0;
        }
        $token = csrf_token();
        $del =  Custom_role_as_permission::where(['staffrole_id' => $id])->delete();

        if ($del) {
            $staffrole =  Staffrole::findorfail($id)->delete();
            return response()->json(['status' => 'bg-success', 'token' => $token, 'message' => "Record deleted!"]);
        } else {
            return response()->json(['status' => 'bg-danger', 'token' => $token, 'message' => "Operation failed!"]);
        }
    }
}
