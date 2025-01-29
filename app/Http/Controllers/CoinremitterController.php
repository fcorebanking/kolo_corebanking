<?php
namespace App\Http\Controllers;
use App\Http\Requests\StoreCoinremitterRequest;
use App\Http\Requests\UpdateCoinremitterRequest;
use App\Models\Coinremitter as CoinremitterModel;
use Illuminate\Http\Request;
use Coinremitter\Coinremitter;
use App\Models\coinremitterWallet;
use App\Models\coinremitterwalletRate;
use App\Models\coinremitterCoin;
use App\Models\coinremitterCoinInvoice;
use App\Models\coinremitterTransaction;

class CoinremitterController extends Controller
{
    public function index()
    {
        $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
        $balance = $walletTXN->get_balance();
        return $balance;
    }
    public function balancecheck(){
        $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
        $balance = $walletTXN->get_balance();
        return $balance;
    }
    public function get_coin_rate(){
        $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
        $get_coin_rate = $walletTXN->get_coin_rate();
        $rate =[];
        foreach($get_coin_rate['data'] as $key => $value){
            $rate[]=['key'=>$key,'value'=>$value];
        }
        return json_encode($rate);
        // {key: NGN, value: {symbol: NGN, name: Naira, price: 17830.4}},

    }
    public function get_new_address(){
        try{
            $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
            $address = $walletTXN->get_new_address();
            $coinremitterCoin = coinremitterCoin::where('code','TCN')->first();
            if($coinremitterCoin > 0){
                return \response()->json(['status'=>false,'message'=>'Crypto not listed'], 400);
            }
            if(coinremitterWallet::where(['user_id'=>auth()->user()->id,'coinremitter_coin_id'=>$coinremitterCoin->id])->count() > 0){
                return \response()->json(['status'=>false,'message'=>'Wallet already Exist'], 400);
            }
            $coinremitterWallet = new coinremitterWallet;
            $coinremitterWallet->user_id = auth()->user()->id;
            $coinremitterWallet->coinremitter_coin_id=$coinremitterCoin->id;
            $coinremitterWallet->address=$address['address'];
            $coinremitterWallet->qr_code=$address['qr_code'];
            $coinremitterWallet->label=$address['label'];
            $coinremitterWallet->date_time=$address['date_time'];
            $coinremitterWallet->save();
            return \response()->json(['status'=>true,'message'=>'Wallet already Exist','address_info'=>$address], 200);
        }catch(Exception $e){
            return \response()->json(['status'=>false,'message'=>$e->getMessage()], 400);
        }
    }
    public function validateAddress($id){
        $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
        $param = [
            'address'=>$id
        ];
        $validate = $walletTXN->validate_address($param);
        return $validate;
    }
    public function withdraw(Request $request)
    {
        try{
            $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
            $param = [
                'to_address'=>$request->address,
                'amount'=>$request->amount,
            ];
            $withdraw = $walletTXN->withdraw($param);
            if($withdraw['flag'] == 1){
                $coinremitterTransaction= new coinremitterTransaction;
                $coinremitterTransaction->user_id = auth()->user()->id;
                $coinremitterTransaction->with_id = $withdraw['data']['id'];
                $coinremitterTransaction->txid = $withdraw['data']['txid'];
                $coinremitterTransaction->explorer_url = $withdraw['data']['explorer_url'];
                $coinremitterTransaction->amount = $withdraw['data']['amount'];
                $coinremitterTransaction->transaction_fees = $withdraw['data']['transaction_fees'];
                $coinremitterTransaction->processing_fees = $withdraw['data']['processing_fees'];
                $coinremitterTransaction->total_amount =$withdraw['data']['total_amount'];
                $coinremitterTransaction->to_address = $withdraw['data']['to_address'];
                $coinremitterTransaction->wallet_id = $withdraw['data']['wallet_id'];
                $coinremitterTransaction->wallet_name = $withdraw['data']['wallet_name'];
                $coinremitterTransaction->coin_short_name = $withdraw['data']['coin_short_name'];
                $coinremitterTransaction->wallet_id = $withdraw['data']['wallet_id'];
                $coinremitterTransaction->txn_date = $withdraw['data']['date'];
                $coinremitterTransaction->status_id = 1;
                $coinremitterTransaction->save();
                return \response()->json(['status'=>true,'message'=>$withdraw->msg], 200);
            }else{
                return \response()->json(['status'=>false,'message'=>$withdraw->msg], 400);
            }
        }catch(Exception $e){
            return \response()->json(['status'=>false,'message'=>$e->getMessage()], 400);
        }
    }
    public function transaction()
    {
        $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
        $param = [
            'id'=>'63916eb3fbba1c17010e5b75'
        ];
        $transaction = $walletTXN->get_transaction($param);
        return $transaction;
    }
    public function createInvoice(Request $request){
        $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
        $param = [
            'amount'=>$request->amount,      //required.
            // 'notify_url'=>'https://yourdomain.com/notify-url', //optional,url on which you wants to receive notification,
            // 'fail_url' => 'https://yourdomain.com/fail-url', //optional,url on which user will be redirect if user cancel invoice,
            // 'suceess_url' => 'https://yourdomain.com/success-url', //optional,url on which user will be redirect when invoice paid,
            'name'=>$request->name,//optional,
            'currency'=>'usd',//optional,
            'expire_time'=>'20',//optional, invoice will expire in 20 minutes.
            'description'=>$request->description,//optional.
        ];
        $invoice  = $walletTXN->create_invoice($param);
        if($invoice->status_code == 0){
            $coinremitterCoinInvoice = new coinremitterCoinInvoice;
            $coinremitterCoinInvoice->user_id =auth()->user()->id;
            $coinremitterCoinInvoice->rond_id=$invoice['data']['id'];
            $coinremitterCoinInvoice->invoice_id=$invoice['data']['invoice_id'];
            $coinremitterCoinInvoice->merchant_id=$invoice['data']['merchant_id'];
            $coinremitterCoinInvoice->url=$invoice['data']['url'];
            $coinremitterCoinInvoice->amountInCoin=$invoice['data']['total_amount']['TCN'];
            $coinremitterCoinInvoice->amountInUSD=$invoice['data']['usd_amount'];
            $coinremitterCoinInvoice->base_currency=$invoice['data']['base_currency'];
            $coinremitterCoinInvoice->coin=$invoice['data']['coin'];
            $coinremitterCoinInvoice->wallet_name=$invoice['data']['name'];
            $coinremitterCoinInvoice->address=$invoice['data']['address'];
            $coinremitterCoinInvoice->status=$invoice['data']['status'];
            $coinremitterCoinInvoice->status_code=$invoice['data']['status_code'];
            $coinremitterCoinInvoice->expire_on=$invoice['data']['expire_on'];
            $coinremitterCoinInvoice->invoice_date=$invoice['data']['invoice_date'];
            $coinremitterCoinInvoice->conversion_rate=$invoice['data']['conversion_rate'];
            $coinremitterCoinInvoice->last_updated_date=$invoice['data']['last_updated_date'];
            $coinremitterCoinInvoice->description = $request->description;
            $coinremitterCoinInvoice->save();
        }
        return $invoice;
    }
    public function getInvoiceData($invoiceID)
    {
        $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
        $param = [
            'invoice_id'=>$invoiceID
        ];
        $invoice = $walletTXN->get_invoice($param);
        return $invoice;
    }
    public function getCryptoRate()
    {
        $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
        $param = [
            'fiat_symbol' => 'USD',
            'fiat_amount' => 1
        ];
        $invoice = $walletTXN->get_fiat_to_crypto_rate($param);
        return $invoice;
    }
    public function get_transaction_by_address($address)
    {
        $walletTXN = new Coinremitter('TCN',auth()->user()->company_id);
        $param = [
            'address' => $address,
        ];
        $invoice = $walletTXN->get_transaction_by_address($param);
        return $invoice;
    }
    public function webhook(Request $request){
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header("$protocol 200 OK");
        return http_response_code(200);
        // return response()->json(['success' => 'success'], 200);
    }
}
