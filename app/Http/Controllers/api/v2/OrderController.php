<?php

namespace App\Http\Controllers\api\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EcomerceProductModel;
use App\Models\OrderModel;
use App\Models\wb_account_details;
use App\Models\user_wallet;
use App\Models\transactions;
use App\Models\DeliveryDetailModel;

use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(){
        $ra = ["status" => true, "product" =>EcomerceProductModel::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),'userAccounts' => auth()->user()->wb_account_details,'contacts'=>DeliveryDetailModel::where('user_id',auth()->user()->id)->get()];
            return response()->json($ra, 200);
    }
    public function makeOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'qrty' => 'required|numeric|min:1',
            'account_id' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $errors,
                'status' => false,
            ];
            return response()->json($datax, 406);
        }
        $qrt = $request->qrty;
        $wb_account_details =wb_account_details::where('virtual_accnum',$request->account_id)->first();
        if($wb_account_details){

            $wbID =$wb_account_details->id;
            $user_wallet = user_wallet::where('wb_account_detail_id',$wbID)->first();
            if($user_wallet){
                $balanceBefore = $balance = $user_wallet->balance;
                $ecom = EcomerceProductModel::where(['id'=>$request->product_id,'company_id'=>auth()->user()->company_id])->first();
                if($ecom->quantity < $qrt){
                    $datax = [
                        'message' => "Item No Available!",
                        'status' => false,
                    ];
                    return response()->json($datax, 404);
                }
                if($ecom){
                    $address='';
                    $contact='';
                    $addressID =null;
                    $haveContact = $request->haveContact;
                    if($haveContact != null){
                        $del = DeliveryDetailModel::where(['user_id'=>auth()->user()->id,'id'=>$haveContact])->first();
                        if($del){
                            $address=$del->address;
                            $contact=$del->contact;
                            $addressID = $del->id;
                        }else{
                            return response()->json(['status'=>false,'message'=>'Invalide address!'], 400);
                        }
                    }else{
                        if($request->address != null && $request->contact != null){
                            $address=$request->address;
                            $contact=$request->contact;
                        }else{
                            return response()->json(['status'=>false,'message'=>'Enter Address and Contact!'], 400);
                        }
                    }
                    $amount = $ecom->amount;
                    $totalpay =$amount* $qrt;
                    if($balance < $totalpay){
                        return response()->json(['status'=>false,'message'=>'Balance not enough!'], 400);
                    }
                    $balance -=$totalpay;
                    $user_wallet->balance = $balance;
                    $data = [
                        "balance_before" => $balanceBefore,
                        "amount" =>  $totalpay,
                        "description" => "E-Commerce Purchase of ",
                        "account_id" => $wbID,
                        "branch_id" => auth()->user()->branch_id,
                        "transaction_status_id" => 1,
                        "transaction_reference" => generate_txnID(),
                        "transaction_type_id" => 77, //debit 7
                        "credit_debit_type_id" => 2, //debit 2
                        "transaction_remarks_id" => 2, //success 2
                        "balance_after" => $balance,
                        "created_date"  => date("Y-m-d"),
                        "created_by" => auth()->user()->id
                    ];

                    $txn = transactions::create($data);
                    if($txn){
                        $user_wallet->save();
                        if($addressID == null){
                            $DeliveryDetailModel =new DeliveryDetailModel;
                            $DeliveryDetailModel->user_id = auth()->user()->id;
                            $DeliveryDetailModel->address = $address;
                            $DeliveryDetailModel->contact = $contact;
                            $DeliveryDetailModel->save();
                            $addressID = $DeliveryDetailModel->id;
                            //delivery_detail_model_id
                        }
                    $OrderModel =new OrderModel;
                    $OrderModel->user_id =auth()->user()->id;
                    $OrderModel->ecomerce_product_model_id =$ecom->id;
                    $OrderModel->qrty =$request->qrty;
                    $OrderModel->status_id =2;
                    $OrderModel->wb_account_detail_id=$wbID;
                    $OrderModel->transaction_id=$txn->id;
                    $OrderModel->delivery_detail_model_id=$addressID;
                    $OrderModel->amount =$amount;
                    $OrderModel->orderID = time().$txn->id;
                    $OrderModel->total_amount = $totalpay;
                    $OrderModel->save();
                    $ecom->quantity -= $qrt;
                    $ecom->save();
                    $txn->description .= $OrderModel->product->name."with Order ID #$OrderModel->orderID";
                    $txn->save();
                    return response()->json(['status'=>true,'message'=>'Order Successful Awaiting Delivery','orderID'=>$OrderModel->orderID], 200);
                    }
                    return response()->json(['status'=>true,'message'=>'Order Failed untraced error'], 400);

                }else{
                    return response()->json(['status'=>false,'message'=>'Order failed'], 400);
                }
            }else{
                return response()->json(['status'=>false,'message'=>'Invalide Account Wallet!'], 400);
            }
        }else{
            return response()->json(['status'=>false,'message'=>'Invalide Account selected!'], 400);

        }
    }
    public function showOrdersAll(){
        $ra = ["status" => true, "message" =>OrderModel::with('DeliveryDetails')->with('product')->where('user_id',auth()->user()->id)->get()];
            return response()->json($ra, 200);
    }
    public function showOrderPending(){
        $ra = ["status" => true, "message" =>OrderModel::with('DeliveryDetails')->with('product')->where(['user_id'=>auth()->user()->id,'status_id'=>2])->get()];
            return response()->json($ra, 200);
    }
    public function cancelOrders(Request $request){
        if($request->order_id==null){
            return response()->json(['status'=>false,'message'=>'No Order ID!'], 400);
        }
       $order = OrderModel::where(['user_id'=>auth()->user()->id,'status_id'=>2,'id'=>$request->order_id])->first();
      if($order){
        $wb_account_detail_id = $order->wb_account_detail_id;
       $order->status_id = 3;
       if($order->save()){
        $user_wallet = user_wallet::where('wb_account_detail_id',$wb_account_detail_id)->first();
        $user_wallet->balance += $order->total_amount;
        $user_wallet->save();
       }
        $ra = ["status" => true, "message" =>"Order Cancel Successfully!"];
            return response()->json($ra, 200);
      }else{
        $ra = ["status" => false, "message" =>"Order Cancel Failed!"];
        return response()->json($ra, 200);
      }
    }
}
