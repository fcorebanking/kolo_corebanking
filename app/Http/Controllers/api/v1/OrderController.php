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
        logInfo(auth()->user()->company_id, "Ecommerce category company id");
        $ra = ["status" => true, "data" => EcommerceCategory::where(['company_id' => auth()->user()->company_id])->get()];
        return response()->json($ra, 200);
    }

    public function makeOrder(Request $request)
    {
        logInfo($request->all(), "4ts Finova Received Webhook");

        // 🔹 Validate Input
        $validator = Validator::make($request->all(), [
            'account_id'     => 'required',
            'product'        => 'required|array',
            'contact_id'     => 'nullable|integer',
            'address'        => 'nullable|string',
            'contact'        => 'nullable|string',
            'deliveryMethod' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $errors = collect($validator->errors()->all())->implode(' ');
            return response()->json(['message' => $errors, 'status' => false], 406);
        }

        // 🔹 Fetch Wallet & User
        $wbAccount = wb_account_details::where('virtual_accnum', $request->account_id)->first();
        if (! $wbAccount) {
            return response()->json(['status' => false, 'message' => 'Invalid Account Selected!'], 400);
        }

        // Use authenticated user OR fallback to wallet owner
        $authUser = auth()->user();
        $userId   = $authUser ? $authUser->id : $wbAccount->user_id;
        logInfo($userId, "userId ");
        $companyId = $authUser->company_id ?? null;
        $branchId  = $authUser->branch_id ?? null;

        $userWallet = user_wallet::where('wb_account_detail_id', $wbAccount->id)->first();
        if (! $userWallet) {
            return response()->json(['status' => false, 'message' => 'Invalid Account Wallet!'], 400);
        }

        logInfo($userWallet->balance, "User Wallet Balance");

        // 🔹 Iterate through products
        foreach ($request->input('product') as $product) {
            $quantity  = $product['quantity'];
            $productId = $product['id'];

            $ecom = EcomerceProductModel::where([
                'id'         => $productId,
                'company_id' => $companyId,
            ])->first();

            if (! $ecom) {
                return response()->json(['status' => false, 'message' => 'Product not found'], 404);
            }

            if ($ecom->quantity < $quantity) {
                return response()->json(['status' => false, 'message' => 'Item Not Available!'], 404);
            }

            // 🔹 Handle address/contact
            $delivery = $this->getOrCreateDeliveryDetail($request, $userId);
            if (! $delivery) {
                return response()->json(['status' => false, 'message' => 'Enter Address and Contact!'], 400);
            }

            // 🔹 Payment Calculation
            $amount   = $ecom->amount;
            $totalPay = $amount * $quantity;

            logInfo($totalPay, "totalPay");

            if ($request->deliveryMethod === 'delivery' && $ecom->delivery_fee > 0) {
                $totalPay += $ecom->delivery_fee;
            }

            logInfo($totalPay, "totalPay after delivery");

            if ($userWallet->balance < $totalPay) {
                return response()->json(['status' => false, 'message' => 'Balance not enough!'], 400);
            }

            $balanceBefore = $userWallet->balance;
            $userWallet->balance -= $totalPay;

            logInfo($userWallet->balance, "balance  after deduction");

            // 🔹 Create Transaction
            $txn = $this->createTransaction(
                $wbAccount->id,
                $userId,
                $companyId,
                $branchId,
                77, // transaction type
                $balanceBefore,
                $totalPay,
                $userWallet->balance,
                "E-Commerce Purchase of "
            );

            if ($txn && $request->deliveryMethod === 'delivery' && $ecom->delivery_fee > 0) {
                $this->createTransaction(
                    $wbAccount->id,
                    $userId,
                    $companyId,
                    $branchId,
                    106, // delivery fee type
                    $balanceBefore,
                    $ecom->delivery_fee,
                    $userWallet->balance,
                    "E-Commerce Delivery Fee"
                );
            }

            $userWallet->save();

            // 🔹 Create Order
            $order = new OrderModel([
                'user_id'                   => $userId,
                'ecomerce_product_model_id' => $ecom->id,
                'qrty'                      => $quantity,
                'status_id'                 => 2,
                'wb_account_detail_id'      => $wbAccount->id,
                'transaction_id'            => $txn->id,
                'delivery_detail_model_id'  => $delivery->id,
                'amount'                    => $amount,
                'orderID'                   => time() . $txn->id,
                'total_amount'              => $totalPay,
            ]);

            if ($request->deliveryMethod === 'delivery' && $ecom->delivery_fee > 0) {
                $order->delivery_fee = $ecom->delivery_fee;
            }

            $order->save();

            // 🔹 Update Product Stock
            $ecom->decrement('quantity', $quantity);

            // 🔹 Update Transaction Description
            $txn->description .= $order->product->name . " with Order ID #{$order->orderID}";
            $txn->save();

            return response()->json([
                'status'  => true,
                'message' => 'Order Successful Awaiting Delivery',
                'orderID' => $order->orderID,
            ], 200);
        }

        return response()->json(['status' => false, 'message' => 'Order Failed'], 400);
    }

/**
 * Create or update delivery detail
 */
    private function getOrCreateDeliveryDetail(Request $request, $userId)
    {
        if ($request->contact_id) {
            $del = DeliveryDetailModel::where(['user_id' => $userId, 'id' => $request->contact_id])->first();
            if ($del) {
                return $del;
            }

        }

        if ($request->address && $request->contact) {
            $contact          = DeliveryDetailModel::firstOrNew(['user_id' => $userId]);
            $contact->user_id = $userId;
            $contact->address = $request->address;
            $contact->contact = $request->contact;
            $contact->save();
            return $contact;
        }

        return null;
    }

/**
 * Create transaction record
 */
    private function createTransaction($wbID, $userId, $companyId, $branchId, $txnTypeId, $before, $amount, $after, $description)
    {
        $ledger = get_CR_DR_TXN_Type_Ledger($txnTypeId, $companyId);
        $txnRef = generate_txnID();

        return transactions::create([
            'balance_before'         => $before,
            'amount'                 => $amount,
            'description'            => $description,
            'account_id'             => $wbID,
            'branch_id'              => $branchId,
            'transaction_status_id'  => 1,
            'transaction_reference'  => $txnRef,
            'transaction_type_id'    => $txnTypeId,
            'credit_debit_type_id'   => 2, // debit
            'transaction_remarks_id' => 2, // success
            'balance_after'          => $after,
            'created_date'           => now()->toDateString(),
            'created_by'             => $userId,
            'company_id'             => $companyId,
            'ledger_dr'              => $ledger->ledger_dr ?? null,
            'ledger_cr'              => $ledger->ledger_cr ?? null,
        ]);
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
