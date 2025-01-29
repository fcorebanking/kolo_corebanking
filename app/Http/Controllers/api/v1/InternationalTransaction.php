<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\ThirdParty\BaniController;
use Illuminate\Support\Facades\Validator;
use App\Models\PendingSaving;
use App\Models\savings_product;
use App\Models\user_wallet;
use App\Models\branch;
use App\Models\transactions;
use DB;
use App\Services\FlutterWave;
use App\Services\LipilaService;

use App\Models\Beneficiary;
use App\Models\company_wallet;

use App\Models\wb_account_details;
use App\Models\User;
use Exception;

// class company_wallet extends Model
// {
//     use HasFactory;
//     protected $fillable = [
//         'balance',
//         'sms',
//         'ussd',
//         'whatsapp'

//     ];

class InternationalTransaction extends Controller
{
    public function transferFunds(Request $request){
        try{
        $validatedData = $request->validate([
            'receiver_currency'=>'required',
            'accountType' => 'required',
            'receiver_account_num' => 'required',
            'receiver_account_name' => 'required',
            'sender_amount' => 'required',
            'transfer_note' => 'required',
        ]);
        DB::beginTransaction();
        $created_by = auth()->user();
        $currencyData = ['XAF'=>['momo_currency'=> 'XAF','momo_country'=>'CM' ],'ZMW'=>['momo_currency'=> 'ZMW','momo_country'=>'ZM' ],'NGN'=>['momo_currency'=> 'NGN','momo_country'=>'NG' ]];//XAF response['message']['data']['data'];
        $countrydata = $currencyData[$validatedData['receiver_currency']];
        $wb_account_detail_data =wb_account_details::with('currency')->where('virtual_accnum',$validatedData['accountType'])->first();
        if(!$wb_account_detail_data){
            return response()->json(['status' => false, 'message' => 'Invalide Wallet!']);
        }
        if(!isset($wb_account_detail_data->currency->code)){
            return response()->json(['status' => false, 'message' => 'Invalide Wallet currency!']);
        }
        if($wb_account_detail_data->currency->code != $validatedData['receiver_currency']){
            return response()->json(['status' => false, 'message' => 'Select your wallet with the receiver currency! '.$wb_account_detail_data->currency->code.' '.$validatedData['receiver_currency']]);
        }
        $transferId = getTransactionIdByName('Mobile Money Transfer');
        $transaction_type_id = $transferId->id;
        $amount =$validatedData['sender_amount'];
        $baniController = new FlutterWave();
        $lipilaService =new LipilaService();
        $data =[];
        $transactionId = generate_txnID();
        $data["currency"] = $validatedData['receiver_currency'];//
        $data["amount"] =$amount;//
        if($validatedData['receiver_currency'] == 'XAF'){
            $data["account_number"] = $validatedData['receiver_account_num'];//
            $data["debit_currency"] ='NGN';
            $data["tx_ref"] = $transactionId;//
            $data["beneficiary_name"] = $validatedData['receiver_account_name'];//
            $data["narration"] = $validatedData['transfer_note'];//
        }elseif($validatedData['receiver_currency'] == 'ZMW'){

            $data["currency"] = $validatedData['receiver_currency'];//
            $data["accountNumber"] = $validatedData['receiver_account_num'];
            $data["fullName"] = auth()->user()->company->company_name;//$validatedData['receiver_account_name'];
            $data["phoneNumber"]= $validatedData['receiver_account_num'];
            $data["email"] =  auth()->user()->email;
            $data["externalId"]= "$transactionId";
            $data["narration"]= "Topup $amount".$validatedData['receiver_currency'];
        }else{
            return response()->json(['status' => false, 'message' => 'Currency not available!']);
        }
        $ledger = systemLedgerAccount('Customer Saving Deposit', $created_by->company_id)->id;
        $SystemLegder = systemLedgerAccount('Cash', $created_by->company_id)->id;
        $company_wallet =company_wallet::where(['company_id'=>$created_by->company_id,'currency_code'=>$data["currency"]])->first();
        if(!$company_wallet){
            return response()->json(['status' => false, 'message' => 'Company error!']);
        }
        if($company_wallet->balance < $amount){
            return response()->json(['status' => false, 'message' => 'insufficient balance!']);
        }
        $dataT = [
            "amount" =>  $amount,
            "description" => $validatedData['transfer_note'],
            "account_id" => $wb_account_detail_data->id,
            "branch_id" => $created_by->branch_id,
            "transaction_status_id" => 1,
            "transaction_reference" => $transactionId,
            "transaction_type_id" => $transaction_type_id, //debit 7
            "credit_debit_type_id" => 2, //debit 2
            "transaction_remarks_id" => 2, //success 2
            "created_date"  => date('Y-m-d'),
            "created_by" => $created_by->id,
            "ledger_cr" => $SystemLegder,
            "ledger_dr" => $ledger,
            "initiator" =>'self',
        ];
            $dataT['userParamType'] = 2;
            $dataT['userId'] = $wb_account_detail_data->id;
            $dataT['remark'] = 2;
            $dataT['chargeamt'] = 5;
            //
            $CreditCustomer = DebitCreditcustomer($dataT);
            if (!$CreditCustomer['status']) {
                DB::rollBack();
                return $CreditCustomer;
            }
            $transferCompanyId = getTransactionIdByName('Company Mobile Money Transaction'); //
        $company_data=[];
        $company_data['auth'] =$created_by;
        $company_data['transaction_id']=$transferCompanyId->id;
        $company_data['desc'] =$validatedData['transfer_note'];
        $company_data['currency']=$validatedData['receiver_currency'];
        $company_data['amount'] =$amount;
        $company_data['credit_debit_type_id'] = 2;
        $companytxn = debitCreditCompany($company_data);
        $response =($validatedData['receiver_currency'] == 'XAF')? ($baniController->sendMoney($data)):($lipilaService->disburseMobileMoney($data));
            if(!$companytxn){
                return response()->json(['status' => false, 'message' => 'Error: Debitting']);

            }
        if($response['status'] =='success' || $response['status'] =='Successful'){
            $txnID = ($validatedData['receiver_currency'] == 'XAF')? $response['data']['id']:$response['transactionId'];
            transactions::where('id',$CreditCustomer['data']->id)->update(['instrumentCode'=>$txnID]);
            transactions::where('id',$companytxn->id)->update(['instrumentCode'=>$txnID]);
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Operation complete','data'=>$response]);
        }else{
            return response()->json(['status' => false, 'message' => 'Error:'.$response['message']]);
        }
        }catch(Exception $e){
            return response()->json(['status' => false, 'message' => $e->getMessage()]);

        }
    }
    public function receiveFund(Request $request){
        try{
        $validatedData = $request->validate([
            'pay_amount' => 'required',
            'pay_currency' => 'required',
            'holder_phone' => 'required',
            'holder_ip_address' => 'required',
            'transfer_note'=>'required',
            'accountType'=>'required',
            // 'name'=>'required',
        ]);
        $currencyData = ['XAF'=>['momo_currency'=> 'XAF','momo_country'=>'CM' ],'ZMW'=>['momo_currency'=> 'ZMW','momo_country'=>'ZM' ],'NGN'=>['momo_currency'=> 'NGN','momo_country'=>'NG' ]];//XAF response['message']['data']['data'];

        $wb_account_detail_data =wb_account_details::with('currency')->where('virtual_accnum',$validatedData['accountType'])->first();
        if(!$wb_account_detail_data){
            return response()->json(['status' => false, 'message' => 'Invalide Wallet!']);
        }
        if(!isset($wb_account_detail_data->currency->code)){
            return response()->json(['status' => false, 'message' => 'Invalide Wallet currency!']);
        }
        if($wb_account_detail_data->currency->code != $validatedData['pay_currency']){
            return response()->json(['status' => false, 'message' => 'Select your wallet with the sending currency!']);
        }
        $amount = $validatedData['pay_amount'];
            $transactionId = generate_txnID();
        $baniController = new FlutterWave();
        $lipilaService =new LipilaService();

        // $data["pay_va_step"] = "direct";
        if($validatedData['pay_currency'] == 'XAF'){
        $dataT["phone_number"] = $validatedData['holder_phone'];
        $dataT["amount"] = $validatedData['pay_amount'];
        $dataT["narration"] = $validatedData['transfer_note'];
        $dataT["email"] = auth()->user()->email;
        $dataT["fullname"] =$request->name??'Wired Banking';
        $dataT["tx_ref"] = $transactionId;
        }elseif($validatedData['pay_currency'] == 'ZMW'){
        $dataT["currency"] = $validatedData['pay_currency'];//
        $dataT["accountNumber"] = $validatedData['holder_phone'];
        $dataT["fullName"] = $request->name??'Wired Banking';
        $dataT["phoneNumber"]= $validatedData['holder_phone'];
        $dataT["email"] =  auth()->user()->email;
        $dataT["externalId"]= "$transactionId";
        $dataT["narration"]= "Wired banking Loan";
        $dataT["amount"] = $validatedData['pay_amount'];

        }


        // $data["custom_data"] = array("color" => "blue", "item_name" => $validatedData['transfer_note']);
        $created_by =auth()->user();
        // Convert the array to JSON format
        $SystemLegder= systemLedgerAccount('Customer Saving Deposit', $created_by->company_id)->id;
        $ledger = systemLedgerAccount('Cash', $created_by->company_id)->id;
        $transaction_type_id = getTransactionIdByName('Receive Payment')->id;

        $data = [
            "amount" =>  $validatedData['pay_amount'],
            "description" => $validatedData['transfer_note'],
            "account_id" => $wb_account_detail_data->id,
            "branch_id" => $created_by->branch_id,
            "transaction_status_id" => 1,
            "transaction_reference" => $transactionId,
            "transaction_type_id" => $transaction_type_id, //debit 7
            "credit_debit_type_id" => 1, //debit 2
            "transaction_remarks_id" => 1, //success 2
            "created_date"  => date('Y-m-d'),
            "created_by" => $created_by->id,
            "ledger_cr" => $SystemLegder,
            "ledger_dr" => $ledger,
            "initiator" =>'self',

        ];

            $response = ($validatedData['pay_currency'] == 'XAF')?($baniController->receiveMoney($dataT)) : $lipilaService->receiveMobileMoney($dataT);
if(isset($response['status'])){
            if($response['status'] =='success' || $response['status'] =='Successful' || $response['status'] =='Pending'){
                $txnID = ($validatedData['pay_currency'] == 'XAF')? $response['data']['id']:$response['transactionId'];
                $data['instrumentCode'] =  $txnID;
                transactions::create($data);
                DB::commit();
            return response()->json(['status' => true, 'message' => 'Operation complete','data'=>$response]);
        }else{
            DB::rollBack();
        return response()->json(['status' => false, 'message' => $response['message']]);
        }
    }else{
        return response()->json(['status' => false, 'message' => 'Error processing','data'=>$response]);

    }
    }catch(Exception $e){
        DB::rollBack();
        return response()->json(['status' => false, 'message' => $e->getMessage()]);

    }
    }
    public function currencies(){
        return response()->json(['status' => true, 'message' => 'currency fetched successfully', 'data' => [['name'=>'NGN'],['name'=>'ZMW'],['name'=>'CFA']]]);
    }
    public function conversion(Request $request){
        try{

        $validatedData = $request->validate([
            'from_currency' => 'required',
            'to_currency' => 'required',
            'convert_amount' => 'required',
        ]);
        $baniController = new BaniController();
        $data = [];
        $data['from_currency'] = $validatedData['from_currency'];
        $data['to_currency'] = $validatedData['to_currency'];
        $data['convert_amount'] =$validatedData['convert_amount'];
        $response = $baniController->fiatConversion($data);
        return response()->json(['status' => true, 'message' => 'Operation complete','data'=>$response]);

        }catch(Exception $e){
            return response()->json((['status' => 0, 'message' => 'Invalide Loan!'.$e->getMessage()]));
        }
    }
    public function listMemo(){
        $baniController = new BaniController();
        $response = $baniController->listSupportedMomo();
        return response()->json(['status' => 0, 'message' =>'Fetch Success!', 'data'=>$response]);
    }
    public function saveInternationTransferRequest(Request $request){
        try{
        $validatedData = $request->validate([
            'beneficiary_iban' => 'required',
            'beneficiary_account_number' => 'required',
            'beneficiary_sort_code' => 'required',
            'beneficiary_name' => 'required',
            'beneficiary_country' => 'required',
            'account_type' => 'required',
            'amount' => 'required',
            'currency' => 'required',
            'beneficiary_bank_name' => 'required',
            'beneficiary_bank_address' => 'required',
            'beneficiary_swift_code' => 'required',
            'beneficiary_email' => 'required',
            'beneficiary_phone' => 'required',
            'beneficiary_address' => 'required',
            'desc' => 'required',
        ]);
        $auth =auth()->user();
        $wb_account_details = wb_account_details::where(['user_id'=>$auth->id,'virtual_accnum'=>$validatedData['account_type']])->first();
        if(!$wb_account_details){
            return ['status' => 0, 'message' => 'Invalid account!'];
        }
        $transaction_type_id = 7;//withdrawal

        $account_type =$wb_account_details->id;

        $get_ledger_setting = get_CR_DR_TXN_Type_Ledger($transaction_type_id, $auth->company_id);
        $ledger_cr = $get_ledger_setting->ledger_cr;
        $ledger_dr = $get_ledger_setting->ledger_dr;
        if (PendingSaving::where(['account_type' => $account_type, 'pending' => 'debit', 'status_id' => 1])->count() > 0) {
            return ['status' => 0, 'message' => 'Request Already Sent !'];
        }
        $pandingsaving = PendingSaving::create([
            'uuid' =>uniqid(),
            'amount' => $validatedData['amount'],
            'company_id' => $auth->company_id,
            'desc' =>$validatedData['desc'],
            'account_type' => $account_type,
            'status_id' => 1,
            'pending' => 'debit',
            'created_by' =>  $auth->id,
            'transaction_type_id' => $transaction_type_id,
            "ledger_dr" => $ledger_cr,
            "ledger_cr" => $ledger_dr,

        ]);
        if ($pandingsaving) {
            $beneficiary = new Beneficiary;

            // Assign the values from the request to the model attributes
            $beneficiary->beneficiary_iban = $validatedData['beneficiary_iban'];
            $beneficiary->beneficiary_account_number = $validatedData['beneficiary_account_number'];
            $beneficiary->beneficiary_sort_code = $validatedData['beneficiary_sort_code'];
            $beneficiary->beneficiary_name = $validatedData['beneficiary_name'];
            $beneficiary->beneficiary_country = $validatedData['beneficiary_country'];
            $beneficiary->currency = $validatedData['currency'];
            $beneficiary->beneficiary_bank_name = $validatedData['beneficiary_bank_name'];
            $beneficiary->beneficiary_bank_address = $validatedData['beneficiary_bank_address'];
            $beneficiary->beneficiary_swift_code = $validatedData['beneficiary_swift_code'];
            $beneficiary->beneficiary_email = $validatedData['beneficiary_email'];
            $beneficiary->beneficiary_phone = $validatedData['beneficiary_phone'];
            $beneficiary->beneficiary_address = $validatedData['beneficiary_address'];
            $beneficiary->name  =$validatedData['beneficiary_name'];
            $beneficiary->accountNo  = $validatedData['beneficiary_account_number'];
            $beneficiary->pending_saving_id  = $pandingsaving->id;
            $beneficiary->save();
            return ['status' => 1, 'message' => 'Deposit Request Pending!'];
        } else {
            return (['status' => 0, 'message' => 'Rquest Failed!']);
        }
        }catch(Exception $e){
            return (['status' => 0, 'message' =>$e->getMessage()]);
        }
    }
    public function add_account(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'product_id' => 'required',
                'selected_branch_currency' => 'required',
            ]);
            $user = User::where(['user_type_id' => '1', 'id' => auth()->user()->id, 'company_id' => auth()->user()->company_id]);
            $branchData = (branch::where('id',$request->selected_branch_currency)->first());

            $user = $user->first();
            $bankId = 231;

                $saving = savings_product::where(['company_id' => auth()->user()->company_id, 'id' =>$validatedData['product_id']])->first();
                $virtual_acccount_number = rand(1000000000, 9999999999);
                $customer_reference = rand(1000000000, 9999999999);
                if(isset($saving->code)){
                        if($saving->code != null){
                        $length = strlen($saving->code);
                        $virtual_acccount_number = substr_replace($virtual_acccount_number, $saving->code, 0, $length);
                    }
                }
                if(isset($branchData->code)){
                    if($branchData->code != null){
                    $branchCodeLength = strlen($branchData->code);
                    $branchCodeLength -=1;
                    $virtual_acccount_number = substr_replace($virtual_acccount_number, $branchData->code, -$branchCodeLength);
                    }
                }

            if ($saving) {
                if ($user) {
                    $wb =   wb_account_details::where(['account_product_id' => $saving->id, 'user_id' => $user->id])->count();
                    if ($wb > 0 ) {
                        return response()->json(['status' => 0, 'message' => 'Error! Account Type Already Exist']);
                    }
                    $wbcc =   wb_account_details::where(['currency_id'=>$branchData->currency_id, 'user_id' => $user->id])->count();
                    if ($wbcc > 0 ) {
                        return response()->json(['status' => 0, 'message' => 'Error! Account Currency Already Exist']);
                    }

                    $wb_account_details =  wb_account_details::create([
                        'user_id' => $user->id,
                        'account_product_id' => $saving->id,
                        'virtual_accnum' => $virtual_acccount_number,
                        'acc_reference' => $customer_reference,
                        'has_atm_card' => 0,
                        'bankid'=>$bankId,
                        'currency_id'=>$branchData->currency_id??1

                    ]);

                    user_wallet::create([
                        'wb_account_detail_id' => $wb_account_details->id,
                        'balance' => 0,
                    ]);

                    return response()->json(['status' => 1, 'message' => 'Account Added!']);
                } else {
                    return response()->json(['status' => 0, 'message' => 'Account Registration Failed!']);
                }
            } else {
                return response()->json(['status' => 0, 'message' => 'Error: Account Registration Failed!']);
            }
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'message' => "An error occured!"]);
        }
    }
    public function get_currency(Request $request)
    {
        $saving = savings_product::where(['company_id' => auth()->user()->company_id])->get();
        return response()->json(['status' => true, 'message' => 'currency fetched successfully', 'data' => $saving]);
    }
    public function webhook(Request $request)
    {
        $FLutterController = new FlutterWave();
        $lipilaService =new LipilaService();
        if($request->currency =='XAF'){
            $returndata = $FLutterController->webhook($request->reference);
        }else{
            $returndata = $lipilaService->checkTransactionStatus($request->reference);
        }
        if(!isset($returndata['status'])){
        return response()->json(['status' => false, 'message' => 'Failed to run','data'=>$returndata]);
        }
        if($returndata['status']=='success' || $returndata['status']=='Successful'){
            if($returndata['data']['status']=='successful' || $returndata['status']=='Successful'){
                $transaction =   transactions::where(['instrumentCode'=>$request->reference,'transaction_remarks_id'=>1])->first();
                if($transaction){
                    $data = [
                        "amount" =>  $transaction->amount,
                        "description" => $transaction->description,
                        "account_id" => $transaction->account_id,
                        "branch_id" => $transaction->branch_id,
                        "transaction_status_id" => 1,
                        "transaction_reference" => $transaction->transaction_reference,
                        "transaction_type_id" => $transaction->transaction_type_id, //debit 7
                        "credit_debit_type_id" => 1, //debit 2
                        "transaction_remarks_id" => 2, //success 2
                        "created_date"  => $transaction->created_date,
                        "created_by" => $transaction->created_by,
                        "ledger_cr" => $transaction->ledger_cr,
                        "ledger_dr" => $transaction->ledger_dr,
                        "initiator" =>'self',
                    ];
                    $data['userParamType'] = 2;
                    $data['userId'] = $transaction->account_id;
                    $data['remark'] = 2;
                    $data['chargeamt'] = 0;
                    $transaction->transaction_remarks_id=2;
                    $transaction->save();
                    $data['transactionExist'] = $transaction;
                    $CreditCustomer = DebitCreditcustomer($data);
                    if (!$CreditCustomer['status']) {
                        return $CreditCustomer;
                    }
                    $transferCompanyId = getTransactionIdByName('Company Mobile Money Transaction');
                    $created_by = $transaction->author;
                    $company_data=[];
                    $company_data['auth'] =$created_by;
                    $company_data['transaction_id']=$transferCompanyId->id;
                    $company_data['desc'] =$request->reference;
                    $company_data['currency']='XAF';
                    $company_data['amount'] =$transaction->amount;
                    $company_data['credit_debit_type_id'] = 1;
                    $companytxn = debitCreditCompany($company_data);

                    $txnID = ($request->currency == 'XAF')? $returndata['data']['id']:$returndata['transactionId'];
                    transactions::where('id',$CreditCustomer['data']->id)->update(['instrumentCode'=>$txnID]);
                    transactions::where('id',$companytxn->id)->update(['instrumentCode'=>$txnID]);
                    return response()->json(['status' => true, 'message' => 'Success','data'=>$returndata]);
                }
            }
    }
        return response()->json(['status' => false, 'message' => 'Failed','data'=>$returndata]);

    }
}
