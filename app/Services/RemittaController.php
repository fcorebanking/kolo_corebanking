<?php
namespace App\Services;

use App\Models\RemitaMandate;
use App\Models\Remitasalaryhistory;
use App\Models\User;
use App\Models\WbaCharge;
use Exception;
use Illuminate\Support\Facades\Http;

class RemittaController
{
    private $apiKey;
    private $apiURL;
    private $machant;
    private $apiToken;
    private $requestID;
    private $apiHash;
    private $authorization;
    private $header;
    private $authcode;
    private $iswbaAPI;
    public function __construct()
    {
        /*    $RemitaCredential = RemitaCredential::where(['company_id' => auth()->user()->company_id, 'ownership' => 1])->first();
        if (! $RemitaCredential) {
            $RemitaCredential = RemitaCredential::where(['iswba' => 1])->first();

        }
        if (! $RemitaCredential) {return null;}
        $this->iswbaAPI      = $RemitaCredential->iswba;
        $this->apiURL        = env('APP_ENV') ? env('REMITTAL_LIVE_URL') : env('REMITTAL_TEST_URL');
        $this->machant       = $RemitaCredential->machant;
        $this->apiToken      = $RemitaCredential->apiToken;
        $this->apiKey        = $RemitaCredential->apiKey;
        $this->requestID     = utf8_encode(round(microtime(true) * 1000));
        $this->apiHash       = hash('sha512', $this->apiKey . $this->requestID . $this->apiToken);
        $this->authorization = "remitaConsumerKey=$this->apiKey, remitaConsumerToken=$this->apiHash";
        $this->authcode      = rand(0, 1101232);
        $this->header        = [
            'Content-Type'  => 'application/json',
            'Api_Key'       => $this->apiKey,
            'Merchant_id'   => $this->machant,
            'Request_id'    => $this->requestID,
            'Authorization' => $this->authorization,
        ];*/
    }

    public function buyElectricity($transactionId, $electrictyDisco, $meterType, $meterNumber, $amount, $phoneNumber)
    {

        if ($meterType == "prepaid") {
            $productCode = "312";
        } else {
            $productCode = "313";
        }

        $body = [
            "productCode"     => $productCode,
            "clientReference" => $transactionId,
            "amount"          => $amount,
            "data"            => [
                "accountNumber" => $meterNumber,
                "phoneNumber"   => $phoneNumber,
            ],
        ];

        $buyElectricity = $this->remitaPostRequest($body);

        logInfo($buyElectricity, "Remita Buy Electricity Response Log");

        try {

            if ($buyElectricity['status'] == 'pending') {
                return [
                    "status"  => true,
                    'message' => "Transaction is Processing. you will receive a token shortly",
                ];
            }
            if ($buyElectricity['status'] == "00") {
                if (isset($buyElectricity['data']['token'])) {

                    $returnTypeResponse = $meterType == 'prepaid' ? [
                        'balance' => "0.0",
                        'token'   => $buyElectricity['data']['token'],
                        'unit'    => $buyElectricity['data']['unit'] ?? null,
                    ] : [
                        'balance' => "0.0",
                    ];

                    return [
                        "status"  => true,
                        'message' => "Electricity Unit Purchase Successful",
                        'data'    => $returnTypeResponse,
                    ];
                } else {
                    return ["status" => false, 'message' => $buyElectricity['response_description']];
                }
            } else {
                return ["status" => false, 'message' => "Failed to Purchase Electricity"];
            }
        } catch (Exception $e) {
            \logInfo(json_encode($e->getMessage()), "Remita Buy Electricity Error Response Log");
            return ["status" => false, 'message' => "Sorry, Unable to get token. Kindly contact support"];
        }
    }

    public function getElectricityCategory()
    {
        // $endpoint = "https://api-gateway.remita.net/services/connect-gateway/api/v1/vending/products?page=0&pageSize=20&countryCode=NGA&categoryCode=electricity";

        $endpoint = "https://api-gateway.remita.net/api/v1/vending/products?page=0&pageSize=20&countryCode=NGA&categoryCode=electricity";
        logInfo($endpoint, "Remita Get rndpoint info");

        $result = $this->remitaGetRequest($endpoint);

        logInfo(json_encode($result), "Remita Get Electricity info");
    }

    public function remitaBaseUrl()
    {
        return env('APP_MODE') == 'test' ? "https://api-demo.systemspecsng.com/services/connect-gateway/api/v1/vending/transactions" : "https://api-demo.systemspecsng.com/services/connect-gateway/api/v1/vending/transactions";
    }

    public function remitaPostRequest($body)
    {
        logInfo($this->remitaBaseUrl());
        $response = Http::withHeaders($this->remitaHeader())
            ->withOptions([
                'force_ip_resolve' => 'v4',
            ])
            ->post($this->remitaBaseUrl(), $body);
        return $response->json();
    }

    public function remitaGetRequest($url)
    {
        $response = Http::withHeaders($this->remitaHeader())
            ->withOptions([
                'force_ip_resolve' => 'v4',
            ])
            ->get("$url");
        return $response->json();
    }

    public function remitaHeader()
    {
        $header = [
            'Content-Type' => 'application/json',
            'secretKey'    => 'sk_live_+/sHp4DCskZEfFkliedPOZL+uYsDkHh+C9OeV631lqX1bRrp+rt2MiWdxCDVuPht',

        ];

        return $header;
    }

    public function getSalaryHistoryPhoneNumber($phone)
    {
        $fields = [
            'authorisationCode'    => $this->authcode,
            'phoneNumber'          => "$phone",
            'authorisationChannel' => 'USSD',
        ];
        $salary = Remitasalaryhistory::where(['phone' => $phone])->first();
        if ($salary) {
            return $salary->data;
        }
        $member = User::where(['phone' => $phone])->first();
        if (! $member) {
            return '[]';
        }

        if ($this->iswbaAPI) {
            $wbacharge = WbaCharge::where('title', 'Remitta Salary History Charge')->first();
            if (! $wbacharge) {
                $wbacharge                        = new WbaCharge;
                $wbacharge->title                 = 'Remitta Salary History Charge';
                $wbacharge->charge_by             = 0;
                $wbacharge->charge                = 150;
                $wbacharge->minimum_charge_amount = 0;
                $wbacharge->maximum_charge_amount = 200;
                $wbacharge->created_at            = now();
                $wbacharge->updated_at            = now();
                $wbacharge->save();
            }
            $charge   = $wbacharge->charge;
            $response = debitCompanyWithCharge($member->company_id, $charge, 0);
            if ($response['status']) {
                $auth          = auth()->user();
                $transactionId = generate_txnID();
                $transaction   = transactions::create([
                    "balance_before"         => $response['balance_before'],
                    "amount"                 => $charge,
                    "balance_after"          => $response['balance_after'],
                    "description"            => "Remitta phone salary history debit",
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 2,
                    "company_id"             => $auth->company_id,
                    "transaction_type_id"    => 102,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $auth->id,
                ]);
            }
        }
        $response = Http::withHeaders($this->header)
            ->post($this->apiURL . 'remita/exapp/api/v1/send/api/loansvc/data/api/v2/payday/salary/history/ph', $fields);
        $convert                         = json_decode($response->body());
        $Remitasalaryhistory             = new Remitasalaryhistory;
        $Remitasalaryhistory->phone      = $phone;
        $Remitasalaryhistory->customerId = $convert->data->customerId ?? 'nill';
        $Remitasalaryhistory->user_id    = $member->id;
        $Remitasalaryhistory->type       = "phone";
        $Remitasalaryhistory->name       = ($convert->data->customerName) ?? 'nil';
        $Remitasalaryhistory->data       = $response->body();
        $Remitasalaryhistory->authcode   = $fields['authorisationCode'];
        $Remitasalaryhistory->save();
        // $expenses = new Request;
        // $expenses->merge([
        //     'expense_date'        => date("Y-m-d H:i"),
        //     'expense_category_id' => 5,//salaryHistoryPhone
        //     'amount'              => 25,
        // ]);
        // (new ExpenseController)->store($expenses);

        return $response->json();
        if (isset($convert->data->customerId)) {
            User::where(['phone' => $phone])->update(['remittar_customer_id' => $convert->data->customerId]);
        }
        return $response->body();
    }
    public function getSalaryHistoryAccountNumber($accountNumber, $bankCode)
    {
        $fields = [
            'authorisationCode'    => $this->authcode,
            'accountNumber'        => "$accountNumber",
            'bankCode'             => "$bankCode",
            'authorisationChannel' => 'USSD',
        ];
        $salary = Remitasalaryhistory::where(['accountNumber' => $accountNumber, 'bankCode' => $bankCode])->first();
        if ($salary) {
            return $salary->data;
        }
        $member = User::where(['remittar_bankCode' => $bankCode, 'remittar_accountNo' => $accountNumber])->first();
        if (! $member) {
            return '[]';
        }

        if ($this->iswbaAPI) {
            $wbacharge = WbaCharge::where('title', 'Remitta Salary History Charge')->first();
            if (! $wbacharge) {
                $wbacharge                        = new WbaCharge;
                $wbacharge->title                 = 'Remitta Salary History Charge';
                $wbacharge->charge_by             = 0;
                $wbacharge->charge                = 150;
                $wbacharge->minimum_charge_amount = 0;
                $wbacharge->maximum_charge_amount = 200;
                $wbacharge->created_at            = now();
                $wbacharge->updated_at            = now();
                $wbacharge->save();
            }
            $charge = $wbacharge->charge;

            $response = debitCompanyWithCharge($member->company_id, $charge, 0);
            if ($response['status']) {
                $auth          = auth()->user();
                $transactionId = generate_txnID();
                $transaction   = transactions::create([
                    "balance_before"         => $response['balance_before'],
                    "amount"                 => $charge,
                    "balance_after"          => $response['balance_after'],
                    "description"            => "Remitta phone salary history debit",
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 2,
                    "company_id"             => $auth->company_id,
                    "transaction_type_id"    => 102,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $auth->id,
                ]);
            }
        }
        $response                           = Http::withHeaders($this->header)->post($this->apiURL . 'remita/exapp/api/v1/send/api/loansvc/data/api/v2/payday/salary/history/ph', $fields);
        $convert                            = json_decode($response->body());
        $Remitasalaryhistory                = new Remitasalaryhistory;
        $Remitasalaryhistory->accountNumber = $accountNumber;
        $Remitasalaryhistory->bankCode      = $bankCode;
        $Remitasalaryhistory->type          = "bank";
        $Remitasalaryhistory->user_id       = $member->id;
        $Remitasalaryhistory->customerId    = $convert->data->customerId ?? 'nill';
        $Remitasalaryhistory->name          = ($convert->data->customerName) ?? 'nil';
        $Remitasalaryhistory->data          = $response->body();
        $Remitasalaryhistory->authcode      = $fields['authorisationCode'];
        $Remitasalaryhistory->save();
        // $expenses = new Request;
        // $expenses->merge([
        //     'expense_date'        => date("Y-m-d H:i"),
        //     'expense_category_id' => 6,//salaryHistory
        //     'amount'              => 25,
        // ]);
        // (new ExpenseController)->store($expenses);
        if (isset($convert->data->customerId)) {
            User::where(['remittar_bankCode' => $bankCode, 'remittar_accountNo' => $accountNumber])->update(['remittar_customer_id' => $convert->data->customerId]);
        }
        return $response->body();
    }
    public function postMandate($request)
    {
        $acnumber              = $request->input('acnumber');
        $loanid                = $request->input('loanid'); //jump for now
        $customerId            = $request->input('customerId');
        $phoneNumber           = $request->input('phoneNumber');
        $accountNumber         = $request->input('acnumber');
        $loanAmount            = $request->input('loanAmount');
        $collectionAmount      = $request->input('collectionAmount');
        $dateOfDisbursement    = $request->input('dateOfDisbursement');
        $dateOfCollection      = $request->input('dateOfCollection');
        $totalCollectionAmount = $request->input('totalCollectionAmount');
        $numberOfRepayments    = $request->input('numberOfRepayments');
        $bankcode              = $request->input('bankcode');
        $Remitasalaryhistory   = Remitasalaryhistory::where('customerId', $customerId)->first();
        $authcode;
        if ($Remitasalaryhistory) {
            $authcode = $Remitasalaryhistory->authcode;
        }

        $noUpload = 0;
        $fields   = [
            "authorisationChannel"  => 'USSD',
            "customerId"            => "$customerId",
            "authorisationCode"     => "$authcode",
            "authorisationChannel"  => "USSD",
            "phoneNumber"           => "$phoneNumber",
            "accountNumber"         => "$acnumber",
            "currency"              => "NGN",
            "loanAmount"            => $loanAmount,
            "collectionAmount"      => $collectionAmount,
            "dateOfDisbursement"    => $dateOfDisbursement . ' 10:16:18+0000',
            "dateOfCollection"      => $dateOfCollection . ' 10:16:18+0000',
            "totalCollectionAmount" => $totalCollectionAmount,
            "numberOfRepayments"    => $numberOfRepayments,
            "bankCode"              => $bankcode,
        ];
        $response = Http::withHeaders($this->header)->post($this->apiURL . 'remita/exapp/api/v1/send/api/loansvc/data/api/v2/payday/post/loan', $fields);
        $rec      = json_decode($response);
        if ($rec->status == "success" && $rec->responseMsg == 'SUCCESS') {
            $RemitaMandate                    = new RemitaMandate;
            $RemitaMandate->mandateReference  = $rec->data->mandateReference;
            $RemitaMandate->customerId        = $rec->data->customerId;
            $RemitaMandate->data              = $response;
            $RemitaMandate->authorisationCode = $this->authcode;
            $RemitaMandate->responseId        = $rec->responseId;
            $RemitaMandate->loan_id           = $loanid;
            $RemitaMandate->status            = 'Active';
            $RemitaMandate->save();
            $noUpload++;
        }
        if ($noUpload > 0) {
            return ['status' => true, 'message' => 'Post Disbursment Successful!'];
        } else {
            return ['status' => false, 'message' => $rec->responseMsg];
        }
    }

    public function viewMandate($request)
    {
        $customerId    = $request->customerId;
        $mandateRef    = $request->mandateRef;
        $RemitaMandate = RemitaMandate::where(['customerId' => $customerId, 'mandateReference' => $mandateRef])->first();
        if (! $RemitaMandate) {
            return ['status' => false, 'message' => "Invalid Payload"];
        }

        $fields = [
            'authorisationCode' => $RemitaMandate->authorisationCode,
            'customerId'        => "$customerId",
            'mandateRef'        => "$mandateRef",
        ];
        //LoanPaymentController
        $response = Http::withHeaders($this->header)->post($this->apiURL . 'remita/exapp/api/v1/send/api/loansvc/data/api/v2/payday/loan/payment/history', $fields);
        return $response;
    }
    public function stopMandate($request)
    {
        $customerId    = $request->customerId;
        $mandateRef    = $request->mandateRef;
        $RemitaMandate = RemitaMandate::where(['customerId' => $customerId, 'mandateReference' => $mandateRef])->first();
        if (! $RemitaMandate) {
            return ['status' => false, 'message' => "Invalid Payload"];
        }

        $fields = [
            'authorisationCode' => $RemitaMandate->authorisationCode,
            'customerId'        => "$customerId",
            'mandateReference'  => "$mandateRef",
        ];
        $response = Http::withHeaders($this->header)->post($this->apiURL . 'remita/exapp/api/v1/send/api/loansvc/data/api/v2/payday/stop/loan', $fields);
        $rec      = json_decode($response);
        if ($rec->status == "success" && $rec->responseMsg == 'SUCCESS') {
            return ['status' => true, 'message' => 'Mandate Stopped Successful!'];
        }
        return ['status' => false, 'message' => $rec->responseMsg];
    }
}
