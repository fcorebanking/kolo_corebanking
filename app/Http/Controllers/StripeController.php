<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe;
use Session;
use App\Models\wb_account_details;
use App\Models\currencies;
use App\Models\User;
use App\Models\transactions;

use Exception;
class StripeController extends Controller
{
    public function index(){
        return view('stripe.index');
    }
    public function success(){
        return view('stripe.success');
    }
    public function fail(){
        return view('stripe.fail');
    }
    public function stripePost(Request $request)
    {
        try{
            $amount = $request->amount;
            $charge = (2.8/100)*$amount;
            $charge2 = (0.5/100)*$amount;
            $virtual_account = $request->virtual_account;
            $totalcharge = ($charge + $charge2);
            $UserData = User::where(['uuid'=>$virtual_account])->first();
            if(!$UserData){
                Session::flash('fail', 'Invalide account information, User account information!');
                return redirect('/stripe-fail');
            }
            $currencies = currencies::where('code','USD')->first();
            if(!$currencies){
                Session::flash('fail', 'USD not supported!');
                return redirect('/stripe-fail');
            }
            $wb_account_details = wb_account_details::where(['user_id'=>$UserData->id,'currency_id'=>$currencies->id])->first();
            if(!$wb_account_details){
                Session::flash('fail', 'Invalide account information, please ensure you have added USD currency!');
                return redirect('/stripe-fail');
            }
            $created_by =$wb_account_details->user;
        $transactionId = generate_txnID();
        $SystemLegder= systemLedgerAccount('Customer Saving Deposit', $created_by->company_id)->id;
        $ledger = systemLedgerAccount('Cash', $created_by->company_id)->id;
        $transferId = getTransactionIdByName('Customer Wallet TopUp');
        $transaction_type_id = $transferId->id;
        $data = [
            "amount" =>  $amount,
            "description" => 'Topup wallet with card',
            "account_id" => $wb_account_details->id,
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
            "instrumentCode"=>$request->reference
        ];

            $data['userParamType'] = 2;
            $data['userId'] = $wb_account_details->id;
            $data['remark'] = 2;
            $data['chargeamt'] = $charge;
            $data['currency'] = 'USD';

            try{
            Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            $charge =Stripe\Charge::create ([
            "amount" => intval(($amount+$totalcharge)*100),
            "currency" => "USD",
            "source" => $request->stripeToken,
            "description" => "Account topup",
            "receipt_email"=>$created_by->email
            ]);
            }catch(Exception $error){
                Session::flash('fail', 'Please enter correct Card Details!');
                return redirect('/stripe-fail');
            }
            if($charge['status'] == 'succeeded') {
                // $data['amount'] = $charge['amount']/100;
                    $data['chargeamt'] = ($totalcharge);
                    $transferCompanyId = getTransactionIdByName('Company Mobile Money Transaction');
                    $company_data=[];
                    $company_data['auth'] =$created_by;
                    $company_data['transaction_id']=$transferCompanyId->id;
                    $company_data['desc'] =$data['description'];
                    $company_data['currency']='USD';
                    $company_data['amount'] =$amount+$charge2;
                    $company_data["instrumentCode"]=$request->reference;
                    $company_data['credit_debit_type_id'] = 1;
                    $comnpanycharge = debitCreditCompany($company_data);
                    $CreditCustomer = DebitCreditcustomer($data);
                    // if(isset($comnpanycharge->id)){
                    // $transactions = transactions::where('id',$comnpanycharge->id)->first();
                    // $transactions->transaction_remarks_id =2;
                    // $transactions->save();
                    // }

                    if (!$CreditCustomer['status']) {
                        Session::flash('fail', $CreditCustomer['message']);
                        return redirect('/stripe-fail');
                    }
                return redirect('/stripe-success');
            }else{
                Session::flash('fail', 'Payment Failed!');
                return redirect('/stripe-fail');
            }
        }catch(Exception $error){
            Session::flash('fail', $error->getMessage());
            return redirect('/stripe-fail');

        }
    }
}
