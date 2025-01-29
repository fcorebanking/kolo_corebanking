<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CustomerRestriction;
use App\Models\MainCustomerRestriction;
use App\Models\CustomerPermission;
use App\Http\Requests\StoreCustomerRestrictionRequest;
use App\Http\Requests\UpdateCustomerRestrictionRequest;

use Illuminate\Http\Request;

class CustomerRestrictionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    function __construct()
    {
        $this->middleware("custom_permission:customer-restriction|allow-staff-view-all-customers|root");
    }
    public function index()
    {
        if (!cando('customer-restriction|root')) return abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'customer_restriction',
            'page_menu' => 'customer_restriction',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'permission' => CustomerPermission::all(),

        ];
        // $pageName = 'html5';

        return view('company.restriction.restriction')->with($data);
    }
    public function ajax_view(Request $request)
    {
        if (!cando('customer-restriction|view-customer-overdraft|root')) return abort(403);

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];

        $sortColumns = array(
            0 => 'users.first_name',
            1 => 'users.last_name',
            2 => 'users.phone',
            3 => 'main_customer_restrictions.remark',
        );
        $query = MainCustomerRestriction::join('users', 'users.id', '=', 'main_customer_restrictions.user_id')
            ->join('customer_permissions', 'customer_permissions.id', '=', 'main_customer_restrictions.customer_permission_id')
            ->select('main_customer_restrictions.*', 'users.phone', 'users.virtual_account', 'users.last_name', 'users.first_name', 'users.uuid', 'customer_permissions.permission');
        // $query =  $query->where(['users.type'=>1,'users.company_id'=>auth()->user()->company_id]);
        $is_root = cando('root');
        if (!$is_root && !cando('allow-staff-view-all-customers')) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }
        if (!empty($filter)) {
            $query->where(function($query2) use ($filter){
            $query2->where('users.first_name', 'like', '%' . $filter . '%');
            $query2->where('users.last_name', 'like', '%' . $filter . '%');
            $query2->orWhere('users.virtual_account', 'like', '%' . $filter . '%');
            $query2->orWhere('main_customer_restrictions.remark', 'like', '%' . $filter . '%');
        });
    }

        $query->where('main_customer_restrictions.company_id', '=', \auth()->user()->company_id);

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
        foreach ($info as $infoData) {
            $btnlink = $infoData->id;
            $bydata = User::find($infoData->created_by);
            $byname = '';
            if ($bydata) {
                $byname = $bydata->first_name . ' ' . $bydata->last_name;
            }

            $userReference = $infoData->uuid;

            if ($is_root || cando('allow-staff-remove-any-customer-restriction')) {
                $btn = "<button class='btn btn-outline-danger btn-sm' onclick=";
                $btn .= "deletefunc('$btnlink','$userReference') >Remove</button>";
            } else {
                $btn = "";
            }

            $json['data'][] = [
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->phone,
                $infoData->virtual_account,
                $infoData->permission,
                $infoData->remark,
                $byname,
                $btn
            ];
        }

        return $json;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreCustomerRestrictionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCustomerRestrictionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CustomerRestriction  $customerRestriction
     * @return \Illuminate\Http\Response
     */
    public function show(CustomerRestriction $customerRestriction)
    {
        //
    }
    public function UserRestrictionInfo($id)
    {
        $customerddata2 = $customerddata = User::where(['users.uuid' => $id, 'users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id]);
        $customerddata = $customerddata
            ->join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->select(
                'users.id',
                'users.last_name',
                'users.first_name',
                'users.profile_pic',
                'users.phone',
                'users.username',
                'users.email',
                'wb_account_details.virtual_accnum',
                'users.signature',
                'users.created_at',
                'savings_products.savings_product',
                'savings_products.maximum_deposit',
                'savings_products.minimum_withdraw',
                'user_wallets.balance',
                'user_wallets.balance'
            );
        $is_root = cando('root|allow-staff-view-all-customers');
        if (!$is_root) {
            $customerddata = $customerddata->where('users.account_officer_id', auth()->user()->id);
            $customerddata2 = $customerddata2->where('users.account_officer_id', auth()->user()->id);
        }//
        $customerddata = $customerddata->get();
        $userdata = $customerddata2->first();
        $permission = MainCustomerRestriction::where('user_id', $userdata->id)
            ->join('customer_permissions', 'main_customer_restrictions.customer_permission_id', '=', 'customer_permissions.id')
            ->select('main_customer_restrictions.*', 'customer_permissions.permission')
            ->get();
        $customer = [];
        $permissions = [];
        foreach ($permission as $permdata) {
            $permissions[] = $permdata;
        }
        foreach ($customerddata as $data) {
            $customer[] = $data;
        }
        return json_encode(['main' => $customer, 'permission' => $permissions]);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CustomerRestriction  $customerRestriction
     * @return \Illuminate\Http\Response
     */
    public function edit(CustomerRestriction $customerRestriction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCustomerRestrictionRequest  $request
     * @param  \App\Models\CustomerRestriction  $customerRestriction
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerRestrictionRequest $request, CustomerRestriction $customerRestriction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CustomerRestriction  $customerRestriction
     * @return \Illuminate\Http\Response
     */
    public function destroy(CustomerRestriction $customerRestriction)
    {
        //
    }
}
