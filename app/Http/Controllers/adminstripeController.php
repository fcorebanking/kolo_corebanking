<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Stripe;
use Session;
use App\Models\currencies;
use Exception;
class adminstripeController extends Controller
{
    public function index(){
        return view('stripe_admin.index');
    }
    public function success(){
        return view('stripe_admin.receivingpay');
    }
    public function fail(){
        return view('stripe_admin.fail');
    }
    public function stripePost(Request $request)
    {
        try{
            $amount = $request->amount;
            $charge = (3.8/100)*$amount;

            $currencies = currencies::where('code','USD')->first();
            if(!$currencies){
                Session::flash('fail', 'USD not supported!');
                return redirect('/admin-stripe-fail');
            }
            $created_by = auth()->user();
        $transactionId = generate_txnID();
        $SystemLegder= systemLedgerAccount('Customer Saving Deposit', $created_by->company_id)->id;
        $ledger = systemLedgerAccount('Cash', $created_by->company_id)->id;
        $transferId = getTransactionIdByName('Customer Wallet TopUp');
        $transaction_type_id = $transferId->id;
        $data = [
            "amount" =>  $amount,
            "description" => 'Topup wallet with card',
            "branch_id" => $created_by->branch_id,
            "transaction_status_id" => 1,
            "transaction_reference" => $transactionId,
            "transaction_type_id" => $transaction_type_id, //debit 7
            "credit_debit_type_id" => 1, //debit 2
            "transaction_remarks_id" => 2, //success 2
            "created_date"  => date('Y-m-d'),
            "created_by" => $created_by->id,
            "ledger_cr" => $SystemLegder,
            "ledger_dr" => $ledger,
            "initiator" =>'self',
        ];

            $data['userParamType'] = 2;
            $data['remark'] = 2;
            $data['chargeamt'] = $charge;
            try{
            Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            $charge =Stripe\Charge::create ([
            "amount" => intval(($amount+$charge)*100),
            "currency" => "USD",
            "source" => $request->stripeToken,
            "description" => "Account topup",
            "receipt_email"=>$created_by->email
            ]);
            }catch(Exception $error){
                Session::flash('fail', 'Please enter correct Card Details!');
                return redirect('/admin-stripe-fail');
            }
            if($charge['status'] == 'succeeded') {
                $data['amount'] = $charge['amount']/100;
                    $data['chargeamt'] = 0;
                    $transferCompanyId = getTransactionIdByName('Company Mobile Money Transaction');
                    $company_data=[];
                    $company_data['auth'] =auth()->user();
                    $company_data['transaction_id']=$transferCompanyId->id;
                    $company_data['desc'] =$data['description'];
                    $company_data['currency']='USD';
                    $company_data['amount'] =$amount;
                    $company_data["instrumentCode"]=$request->reference;
                    $company_data['credit_debit_type_id'] = 1;
                    $comnpanycharge = debitCreditCompany($company_data);
                    if (!$comnpanycharge) {
                        Session::flash('fail', 'Error processing transaction');
                        return redirect('/admin-stripe-fail');
                    }
                return redirect('/stripe-success');
            }else{
                Session::flash('fail', 'Payment Failed!');
                return redirect('/admin-stripe-fail');
            }
        }catch(Exception $error){
            Session::flash('fail', $error->getMessage(). $error->getLine());
            return redirect('/admin-stripe-fail');

        }
    }
}
