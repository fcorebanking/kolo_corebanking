<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryDetailModel;
use App\Models\EcomerceProductModel;
use App\Models\EcommerceCategory;
use App\Models\OrderModel;
use App\Models\transactions;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = EcomerceProductModel::with('category')->where(['company_id' => auth()->user()->company_id, 'status_id' => 1]);
        if ($request->category) {
            if ($request->category > 0) {
                $query = $query->where('ecommerce_categorie_id', $request->category);
            }
        }
        $query    = $query->get();
        $contacts = DeliveryDetailModel::where('user_id', auth()->user()->id)->get();
        $accounts = auth()->user()->wb_account_details;
        $ra       = ["status" => true, 'account_id' => auth()->user()->accounts->first()->virtual_accnum, 'contacts' => $contacts, "product" => $query, 'userAccounts' => $accounts];
        return response()->json($ra, 200);
    }

    public function category()
    {
        $ra = ["status" => true, "data" => EcommerceCategory::where(['company_id' => auth()->user()->company_id])->get()];
        return response()->json($ra, 200);
    }

    public function makeOrder(Request $request)
    {
        \logInfo($request->all(), "4ts Finova Received Webhook");
        $validator = Validator::make($request->all(), [

            'account_id'     => 'required',
            'product'        => 'required',
            'contact_id'     => 'nullable',
            'address'        => 'nullable',
            'contact'        => 'nullable',
            'deliveryMethod' => 'nullable',

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
                'status'  => false,
            ];
            return response()->json($datax, 406);
        }

        $wb_account_details = wb_account_details::where('virtual_accnum', $request->account_id)->first();
        logInfo($wb_account_details->user_id, "User Order detail");
        logInfo(auth()->user()->id, "User Order detail ID");
        $wbID        = $wb_account_details->id;
        $user_wallet = user_wallet::where('wb_account_detail_id', $wbID)->first();
        logInfo($user_wallet->balance, "User Order detail balance");
        $products = $request->input('product');

        if ($wb_account_details) {
            foreach ($products as $product) {
                $qrt = $product['quantity'];

                $product_id = $product['id'];

                if ($user_wallet) {
                    $balanceBefore = $balance = $user_wallet->balance;
                    $ecom          = EcomerceProductModel::where(['id' => $product_id, 'company_id' => auth()->user()->company_id])->first();
                    if ($ecom->quantity < $qrt) {
                        $datax = [
                            'message' => "Item No Available!",
                            'status'  => false,
                        ];
                        return response()->json($datax, 404);
                    }
                    if ($ecom) {
                        $address     = '';
                        $contact     = '';
                        $addressID   = null;
                        $haveContact = $request->contact_id;
                        if ($haveContact != null) {
                            $del = DeliveryDetailModel::where(['user_id' => auth()->user()->id, 'id' => $haveContact])->first();
                            if ($del) {
                                $address   = $del->address;
                                $contact   = $del->contact;
                                $addressID = $del->id;
                            }
                        }

                        if ($request->address != null && $request->contact != null) {
                            $address = $request->address;
                            $contact = $request->contact;
                            logInfo(auth()->user()->id, "User Order update contact");
                            $contact          = DeliveryDetailModel::where('user_id', auth()->user()->id)->first();
                            $contact->contact = $request->contact;
                            $contact->address = $request->address;
                            $contact->save();
                        } else {
                            return response()->json(['status' => false, 'message' => 'Enter Address and Contact!'], 400);
                        }

                        $amount   = $ecom->amount;
                        $totalpay = $amount * $qrt;
                        if ($request->deliveryMethod) {
                            if ($request->deliveryMethod == 'delivery') {
                                $totalpay += $ecom->delivery_fee;
                            }
                        }
                        if ($balance < $totalpay) {
                            return response()->json(['status' => false, 'message' => 'Balance not enough!'], 400);
                        }
                        $balance -= $totalpay;
                        $user_wallet->balance = $balance;
                        $ledger_cr            = null;
                        $ledger_dr            = null;
                        $ledger_Settings      = get_CR_DR_TXN_Type_Ledger(77, auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $data = [
                            "balance_before"         => $balanceBefore,
                            "amount"                 => $totalpay,
                            "description"            => "E-Commerce Purchase of ",
                            "account_id"             => $wbID,
                            "branch_id"              => auth()->user()->branch_id,
                            "transaction_status_id"  => 1,
                            "transaction_reference"  => generate_txnID(),
                            "transaction_type_id"    => 77, //debit 7
                            "credit_debit_type_id"   => 2,  //debit 2
                            "transaction_remarks_id" => 2,  //success 2
                            "balance_after"          => $balance,
                            "created_date"           => date("Y-m-d"),
                            "created_by"             => auth()->user()->id,
                            "company_id"             => auth()->user()->company_id,
                            "ledger_dr"              => $ledger_dr,
                            "ledger_cr"              => $ledger_cr,
                        ];
                        if ($request->deliveryMethod) {
                            if ($request->deliveryMethod == 'delivery' && $ecom->delivery_fee > 0) {
                                $deliveryTransactionID = 106;
                                $ledger_cr             = null;
                                $ledger_dr             = null;
                                $ledger_Settings       = get_CR_DR_TXN_Type_Ledger($deliveryTransactionID, auth()->user()->company_id);
                                if ($ledger_Settings) {
                                    $ledger_cr = $ledger_Settings->ledger_cr;
                                    $ledger_dr = $ledger_Settings->ledger_dr;
                                }
                                $dataFee = [
                                    "balance_before"         => $balanceBefore,
                                    "amount"                 => $ecom->delivery_fee,
                                    "description"            => "E-Commerce Delivery Fee",
                                    "account_id"             => $wbID,
                                    "branch_id"              => auth()->user()->branch_id,
                                    "transaction_status_id"  => 1,
                                    "transaction_reference"  => generate_txnID(),
                                    "transaction_type_id"    => $deliveryTransactionID, //debit 7
                                    "credit_debit_type_id"   => 2,                      //debit 2
                                    "transaction_remarks_id" => 2,                      //success 2
                                    "balance_after"          => $balance,
                                    "created_date"           => date("Y-m-d"),
                                    "created_by"             => auth()->user()->id,
                                    "company_id"             => auth()->user()->company_id,
                                    "ledger_dr"              => $ledger_dr,
                                    "ledger_cr"              => $ledger_cr,
                                ];
                                transactions::create($dataFee);
                            }
                        }
                        $txn = transactions::create($data);
                        if ($txn) {
                            $user_wallet->save();
                            if ($addressID == null) {
                                $DeliveryDetailModel          = new DeliveryDetailModel;
                                $DeliveryDetailModel->user_id = auth()->user()->id;
                                $DeliveryDetailModel->address = $address;
                                $DeliveryDetailModel->contact = $contact;
                                $DeliveryDetailModel->save();
                                $addressID = $DeliveryDetailModel->id;
                            }
                            $OrderModel                            = new OrderModel;
                            $OrderModel->user_id                   = auth()->user()->id;
                            $OrderModel->ecomerce_product_model_id = $ecom->id;
                            $OrderModel->qrty                      = $product['quantity'];
                            $OrderModel->status_id                 = 2;
                            $OrderModel->wb_account_detail_id      = $wbID;
                            $OrderModel->transaction_id            = $txn->id;
                            $OrderModel->delivery_detail_model_id  = $addressID;
                            if ($request->deliveryMethod) {
                                if ($request->deliveryMethod == 'delivery' && $ecom->delivery_fee > 0) {
                                    $OrderModel->delivery_fee = $ecom->delivery_fee;
                                }
                            }

                            $OrderModel->amount       = $amount;
                            $OrderModel->orderID      = time() . $txn->id;
                            $OrderModel->total_amount = $totalpay;
                            $OrderModel->save();
                            $ecom->quantity -= $qrt;
                            $ecom->save();
                            $txn->description .= $OrderModel->product->name . "with Order ID #$OrderModel->orderID";
                            $txn->save();
                            return response()->json(['status' => true, 'message' => 'Order Successful Awaiting Delivery', 'orderID' => $OrderModel->orderID], 200);
                        }
                        return response()->json(['status' => true, 'message' => 'Order Failed untraced error'], 400);

                    } else {
                        return response()->json(['status' => false, 'message' => 'Order failed'], 400);
                    }
                } else {
                    return response()->json(['status' => false, 'message' => 'Invalide Account Wallet!'], 400);
                }
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Invalide Account selected!'], 400);

        }
    }
    public function showOrdersAll()
    {
        //$data = OrderModel::with('DeliveryDetails')->with('product')->where('user_id', auth()->user()->id)->get();
        $data = OrderModel::select('id', 'user_id', 'ecomerce_product_model_id', 'status_id', 'total_amount', 'orderID')
            ->with(['product:id,name,cover_img,cover_path,description'])
            ->where('user_id', auth()->user()->id)->get();

        $ra = ["status" => true, "message" => "Ordees fetched sccessfully", "data" => $data];

        return response()->json($ra, 200);
    }
    public function showOrderPending()
    {
        //  $ra = ["status" => true, "message" => OrderModel::with('DeliveryDetails')->with('product')->where(['user_id' => auth()->user()->id, 'status_id' => 2])->get()];
        $data = OrderModel::select('id', 'user_id', 'ecomerce_product_model_id', 'status_id', 'total_amount', 'orderID')
            ->with(['product:id,name,cover_img,cover_path,description'])
            ->where(['user_id' => auth()->user()->id, 'status_id' => 2])->get();
        $ra = ["status" => true, "message" => "Ordees fetched sccessfully", "data" => $data];
        return response()->json($ra, 200);
    }
    public function cancelOrders(Request $request)
    {
        if ($request->order_id == null) {
            return response()->json(['status' => false, 'message' => 'No Order ID!'], 400);
        }
        $order = OrderModel::where(['user_id' => auth()->user()->id, 'status_id' => 2, 'id' => $request->order_id])->first();
        if ($order) {
            $wb_account_detail_id = $order->wb_account_detail_id;
            $order->status_id     = 3;
            if ($order->save()) {
                $user_wallet = user_wallet::where('wb_account_detail_id', $wb_account_detail_id)->first();
                $user_wallet->balance += $order->total_amount;
                $user_wallet->save();
            }
            $ra = ["status" => true, "message" => "Order Cancel Successfully!"];
            return response()->json($ra, 200);
        } else {
            $ra = ["status" => false, "message" => "Order Cancel Failed!"];
            return response()->json($ra, 200);
        }
    }
}
