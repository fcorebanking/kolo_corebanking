<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderModelRequest;
use App\Http\Requests\UpdateOrderModelRequest;
use App\Models\OrderModel;
use Illuminate\Http\Request;
use App\Models\user_wallet;

class OrderModelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!auth()->user()->company->setting->access_ecommerce){
            return abort(403);
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'ecommerce_orderproduct',
            'page_menu' => 'ecommerce_orderproduct',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        return view('company.product_ecommerce.pending_order')->with($data);
    }
    public function orders()
    {
        if(!auth()->user()->company->setting->access_ecommerce){
            return abort(403);
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'ecommerce_orderproduct_other',
            'page_menu' => 'ecommerce_orderproduct_other',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        return view('company.product_ecommerce.orders')->with($data);
    }
    public function display_product(Request $request)
    {
        if(!auth()->user()->company->setting->access_ecommerce){
            return abort(403);
        }
        if (!cando('manage-esusu|root')) return abort(403);
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'order_models.id',
            1 => 'ecomerce_product_models.cover_img',
            2 => 'users.first_name',
            3 => 'wb_account_details.virtual_accnum',
            4 => 'order_models.orderID',
            5 => 'ecomerce_product_models.name',
            6 => 'order_models.qrty',
            7 => 'order_models.amount',
            8 => 'order_models.total_amount',
            9 => 'order_models.status_id',
            10 => 'created_at',
            11 => 'created_at',
        );
        $query = OrderModel::join('ecomerce_product_models','ecomerce_product_models.id','=','order_models.ecomerce_product_model_id')
        ->join('users','users.id','=','order_models.user_id')
        ->join('wb_account_details','wb_account_details.id','=','order_models.wb_account_detail_id')
        ->select('order_models.*','ecomerce_product_models.cover_img','ecomerce_product_models.name','users.first_name','users.last_name','wb_account_details.virtual_accnum')
        ->where(['ecomerce_product_models.company_id' => auth()->user()->company_id]);
        if($request->pending != null){
            $query = $query->where('order_models.status_id',2);
        }else{
            $query = $query->where('order_models.status_id','!=',2);
        }
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('ecomerce_product_models.name', 'like', '%' . $filter . '%');
                $query->orWhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('order_models.orderID', 'like', '%' . $filter . '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
                $query->orWhere('users.last_name', 'like', '%' . $filter . '%');
                $query->orWhere('order_models.transaction_id', 'like', '%' . $filter . '%');
                $query->orWhere('order_models.amount', 'like', '%' . $filter . '%');
                $query->orWhere('order_models.total_amount', 'like', '%' . $filter . '%');
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
            $loop++;
            $btnlink = $infoData->id;
            $btn = "<button data-toggle='modal'
                    data-target='#exampleModal' class='btn btn-outline-primary btn-sm' onclick=";
            $btn .= "edit_product('$btnlink') ><i class='fa fa-edit'></i></button>";
            $btn .= "<button class='btn btn-outline-danger btn-sm' onclick=";
            $btn .= "delete_product('$btnlink') ><i class='fa fa-trash'></i></button>";
            $btnPending = ($infoData->status_id==2)?"<a class='dropdown-item' href='#' onclick=comfirm_to_complete('$btnlink')>Complete</a>
            <a class='dropdown-item' href='#' onclick=comfirm_to_cancil('$btnlink')>Cancel</a>
            ":(($infoData->status_id==3)?"<a class='dropdown-item' href='#' onclick=comfirm_to_delete('$btnlink')>Delete</a>":"");
            $btn = "<div class='btn-group'>
                <button type='button' class='btn btn-danger'>Action</button>
                <button type='button' class='btn btn-danger dropdown-toggle dropdown-toggle-split' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                    <span class='sr-only'>Toggle Dropdown</span>
                </button><div class='dropdown-menu'>
                    <a class='dropdown-item' href='#' onclick=showOrderdetails('$btnlink')>View</a>
                        $btnPending
                </div>
                </div>";
            $json['data'][] = [
                $loop,
                "<img src='$infoData->cover_img' width='30'>",
                $infoData->first_name.' '.$infoData->last_name,
                $infoData->virtual_accnum,
                $infoData->orderID,
                ($infoData->name),
                ($infoData->qrty),
                number_format($infoData->amount, 2),
                number_format($infoData->total_amount, 2),
                ($infoData->status_id==1)?'<span class="badge badge-success">Complete</span>':(($infoData->status_id==2)?'<span class="badge badge-warning">Waiting Delivery</span>':'<span class="badge badge-danger">Failed</span>'),
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
    public function create()
    {
        //
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreOrderModelRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrderModelRequest $request)
    {
        //
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OrderModel  $orderModel
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $query = OrderModel::join('ecomerce_product_models','ecomerce_product_models.id','=','order_models.ecomerce_product_model_id')
        ->join('users','users.id','=','order_models.user_id')
        ->join('delivery_detail_models','delivery_detail_models.id','=','order_models.delivery_detail_model_id')
        ->select('order_models.*','ecomerce_product_models.cover_img','ecomerce_product_models.name','users.first_name','users.last_name','delivery_detail_models.address','delivery_detail_models.contact')
        ->where(['ecomerce_product_models.company_id' => auth()->user()->company_id,'order_models.id'=>$id])->first();
        return json_encode($query);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\OrderModel  $orderModel
     * @return \Illuminate\Http\Response
     */
    public function complete($id)
    {
        $order = OrderModel::join('ecomerce_product_models','ecomerce_product_models.id','=','order_models.ecomerce_product_model_id')
        ->select('order_models.*','ecomerce_product_models.cover_img','ecomerce_product_models.name')
        ->where(['ecomerce_product_models.company_id' => auth()->user()->company_id,'order_models.id'=>$id])->first();
        if($order){
            $ord = OrderModel::where('id',$id)->first();
            $ord->status_id = 1;
            $ord->save();
            return json_encode(['status'=>1,'message'=>'Order Completed']);
        }else{
            return json_encode(['status'=>0,'message'=>'Operation Failed']);
        }
    }
    public function cancelOrders(Request $request){
        if($request->order_id==null){
            return response()->json(['status'=>false,'message'=>'No Order ID!'], 400);
        }
        $order2 = OrderModel::join('ecomerce_product_models','ecomerce_product_models.id','=','order_models.ecomerce_product_model_id')
        ->select('order_models.*','ecomerce_product_models.cover_img','ecomerce_product_models.name')
        ->where(['ecomerce_product_models.company_id' => auth()->user()->company_id,'order_models.id'=>$request->order_id])->first();
        if($order2){
        $order = OrderModel::where(['status_id'=>2,'id'=>$request->order_id])->first();
        if($order){
            $wb_account_detail_id = $order->wb_account_detail_id;
        $order->status_id = 3;
        if($order->save()){
            $user_wallet = user_wallet::where('wb_account_detail_id',$wb_account_detail_id)->first();
            $user_wallet->balance += $order->total_amount;
            $user_wallet->save();
        }
            $ra = ["status" => true, "message" =>"Order Cancelled Successfully!"];
                return response()->json($ra, 200);
        }else{
            $ra = ["status" => false, "message" =>"Failed to Cancel Order!"];
            return response()->json($ra, 400);
        }
        }else{
            $ra = ["status" => true, "message" =>"Order Error!"];
                return response()->json($ra, 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OrderModel  $orderModel
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order= OrderModel::where(['id'=>$id,'status_id'=>3])->delete();
        if($order){
            return json_encode(['status'=>1,'message'=>'Order Deleted']);
        }else{
            return json_encode(['status'=>0,'message'=>'Only Cancelled orders can be deleted']);

        }
    }
}
