<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RemittaController;
use Illuminate\Support\Facades\Validator;
use App\Models\Remitasalaryhistory;
use App\Models\wb_account_details;

use DataTables;
use App\Models\RemitaMandate;
use App\Models\loan;
use App\Models\User;
use App\Models\RemitaCredential;
use App\Models\mandatePayments;

use App\Http\Controllers\LoanController;
use Illuminate\Support\Facades\Crypt;

class RemitasalaryhistoryController extends Controller
{
    // remittasalary.blade
    public function index(){
        $salary = Remitasalaryhistory::orderBy("id", "desc")->get();
        return view('backend.loan.remittasalary', compact('salary'));
    }
    public function ajaxGet(Request $request){
        $loans = Remitasalaryhistory::with('User')->orderBy("id", "desc");
		return Datatables::eloquent($loans)
			->editColumn('name', function ($loan) {
				return $loan->name;
			})
			->editColumn('phone', function ($loan) {
				return $loan->phone;
			})
			->filterColumn('accountNumber', function ($query, $keyword) {
                $query->where('accountNumber','like',"%$keyword%");
			}, true)
            ->addColumn('action', function ($loan) {
				return "<a href='/admin/members/".$loan->member->id."?tab=member_details'>View Member</a>";
			})
			->setRowId(function ($loan) {
				return "row_" . $loan->id;
			})
			->rawColumns(['type', 'action'])
			->make(true);
    }
    public function getSalaryHistory(Request $request){
        $RemittaController = new RemittaController();
        if($request->type=="phone"){
            $response =$RemittaController->getSalaryHistoryPhoneNumber($request->phone);
            // var_dump($response);
            return $response;
        }else{
            return ($RemittaController->getSalaryHistoryAccountNumber($request->accountNumber,$request->bankCode));
        }
    }
    public function postNotification(Request $request){
        $validator = Validator::make($request->all(), [
			'acnumber' => 'required',
			'loanid' => 'required',
			'customerId' => 'required',
			'phoneNumber' => 'required',
			'loanAmount' => 'required',
			'collectionAmount' => 'required',
			'dateOfDisbursement' => 'required',
			'totalCollectionAmount' => 'required',
			'dateOfCollection' => 'required',
			'numberOfRepayments' => 'required',
			'bankcode' => 'required',
		]);
		if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->all()]);
		}
        $RemittaController = new RemittaController;
        $response = $RemittaController->postMandate($request);
        return json_encode($response);
    }
    public function RemitaMandate($id){
        $RemitaMandate = RemitaMandate::where('loan_id',$id)->get();
        return response()->json($RemitaMandate);
    }
    public function StopMandate(Request $request){
        $RemittaController = new RemittaController;
        $response = $RemittaController->stopMandate($request);
        return json_encode($response);
    }
    public function RemitaMandatePayment(Request $request){
        $RemittaController = new RemittaController;
        $response = $RemittaController->viewMandate($request);
        return $response;
    }
    public function remittapayment(Request $request){
        $data = $request->json()->all();
        try{
        // {
        //     "id": 1509378261255,
        //     "amount": 2000,
        //     "statusCode": null,
        //     "modulename": "PAYDAYLOAN",
        //     "notificationSent": false,
        //     "dateNotificationSent": "2019-07-10",
        //     "firstNotificationSent": null,
        //     "dateFirstNotificationSent": null,
        //     "netSalary": 23278.5,
        //     "totalCredit": 2000,
        //     "customerPhoneNumber": null,
        //     "mandateRef": "190000196045",
        //     "balanceDue": 6000,
        //     "customer_id": "1366",
        //     "request_id": null,
        //     "payment_date": "24-08-2021 14:56:53+0000",
        //     "payment_status": "NEW",
        //     "status_reason": null
        // }
        $previouspayment = mandatePayments::where(['mandateID'=>$data['id'],'mandateRef'=>$data['mandateRef']])->count();
        if($previouspayment == 0) {
        $RemitaMandate =  RemitaMandate::where(['customerId'=>$data['customer_id'],'mandateReference'=>$data['mandateRef']])->first();
        if($RemitaMandate){



            $loan_id =$RemitaMandate->loan_id;
            $loans = loan::where('id',$loan_id)->first();
            $userId = $loans->user->id;
            $wb_account_details = wb_account_details::where('user_id',$userId)->first();
            $request->merge([
                'loanID'=> Crypt::encryptString($loan_id),
                'companyOwner'=>$loans->LoanOfficer,
                'amount'=>$data['amount'],
                'method'=>'remita',
                'payment'=>$data['dateNotificationSent'],
                'accountType'=>$wb_account_details->id,
            ]);
            $mandatePayments =new  mandatePayments;
            $mandatePayments->mandateID = $data['id'];
            $mandatePayments->mandateRef= $data['mandateRef'];
            $mandatePayments->amount = $data['amount'];
            $mandatePayments->data = json_encode($data);
            if($mandatePayments->save()){
            $payment = (new LoanController)->generalpayloan($request);
            if(!$payment['status']) return response()->json($payment);

                return response()->json([

                        "Response_code"=> "00",
                        "response_descr"=> "Successful",
                        "ack_id"=> "00"

                ]);
            }

        }else{
            return response()->json([

                "Response_code"=> "04",
                "response_descr"=> "record not found",
                "ack_id"=> "04"

        ]);
        }


        return response()->json([

            "Response_code"=> "19",
            "response_descr"=> "Empty",
            "ack_id"=> "19"

    ]);
}
    }catch(Exception $e){
            \logInfo($request, $e->getMessage());

    }
}
}
