<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Image;

class FincraController extends WebhookController
{

    private $username;
    private $apiKey;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->token = "";
        if (env('APP_MODE') == 'test') {

            $this->username = config('fincra.email_test');
            $this->apiKey   = config('fincra.api_key_test');
            $this->baseUrl  = config('fincra.base_url_test');
        } else {
            $this->username = config('fincra.email_live');
            $this->apiKey   = config('fincra.api_key_live');
            $this->baseUrl  = config('fincra.base_url_live');
        }
    }

    public function login()
    {

        $response = Http::withHeaders([
            'Origin' => "finova.ng",
        ])->get(
            'https://ep-green-mountain-ad9fuebm.neonauth.c-2.us-east-1.aws.neon.tech/api/auth/get-session'
        );
        return $response->json();
    }

    public function createAccount($requestReference, $virtualAccount)
    {
        $currency      = $this->getCurrency($virtualAccount->country) ?? "";
        $utilityBill   = "";
        $bankStatement = "";

        if ($request->hasFile('bankstatement')) {
            $type        = "bankstatement";
            $utilityBill = $this->upload($request, $type);
        }

        if ($request->hasFile('utilitybill')) {
            $type          = "utilitybill";
            $bankStatement = $this->upload($request, $type);
        }

        if ("currency" == "USD") {
            $body = [
                "currency"       => $currency,
                "utilityBill"    => $utilityBill,
                "bankStatement"  => $bankStatement,
                "KYCInformation" => [
                    "sourceOfIncome"   => $request->sourceOfIncome,
                    "employmentStatus" => $request->employmentStatus,

                ],
            ];
        } else {
            $body = [
                "currency"       => $currency,
                "utilityBill"    => $utilityBill,
                "bankStatement"  => $bankStatement,
                "KYCInformation" => [
                    "firstName"        => $virtualAccount->firstName,
                    "lastName"         => $virtualAccount->lastName,
                    "email"            => $virtualAccount->email,
                    "phone"            => $virtualAccount->phone,
                    "birthDate"        => Carbon::parse($virtualAccount->dob)->format('d M Y'),
                    "nationality"      => $currency,
                    "sourceOfIncome"   => "salary",
                    "employmentStatus" => "employed",

                ],
            ];
        }

        $jsonBody = json_encode($body);

        $url = "{$this->baseUrl}/profile/virtual-accounts/requests ";

        try {

            \logInfo($jsonBody, "Fincra Request body");

            $createAccountResponse = $this->fincraPostRequest($url, $body);

            logInfo($createAccountResponse, "Kuda Create Account Response");

            if ($createAccountResponse->successful()) {

                //    $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["status"]) {
                    return [
                        'status'  => false,
                        'message' => $createAccountResponse["message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Created Successfully",
                        'data'    => [
                            'account_number'         => $createAccountResponse['data']['accountNumber'],
                            'virtual_account_number' => $createAccountResponse['data']['accountNumber'],
                        ],
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Account CreationFailed . Kindly contactsupportfor assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Laravel Kuda Error");

            return ['status' => false, 'message' => 'Error CreatingAccount . Kindly contactsupportfor assistance'];
        }
    }

    public function addAccount($requestReference, $virtualAccount)
    {
        $currency      = $this->getCurrency($virtualAccount->currencyCode);
        $utilityBill   = "";
        $bankStatement = "";

        if ($request->hasFile('bankstatement')) {
            $type        = "bankstatement";
            $utilityBill = $this->upload($request, $type);
        }

        if ($request->hasFile('utilitybill')) {
            $type          = "utilitybill";
            $bankStatement = $this->upload($request, $type);
        }

        if ("currency" == "USD") {
            $body = [
                "currency"       => $currency,
                "utilityBill"    => $utilityBill,
                "bankStatement"  => $bankStatement,
                "KYCInformation" => [

                    "sourceOfIncome"   => "salary",
                    "employmentStatus" => "employed",

                ],
            ];
        } else {
            $body = [
                "currency"       => $currency,
                "utilityBill"    => $utilityBill,
                "bankStatement"  => $bankStatement,
                "KYCInformation" => [
                    "firstName"        => $virtualAccount->firstName,
                    "lastName"         => $virtualAccount->lastName,
                    "email"            => $virtualAccount->email,
                    "phone"            => $virtualAccount->phone,
                    "birthDate"        => Carbon::parse($virtualAccount->dob)->format('d M Y'),
                    "nationality"      => $currency,
                    "sourceOfIncome"   => "salary",
                    "employmentStatus" => "employed",

                ],
            ];
        }

        $jsonBody = json_encode($body);

        $url = "https://kuda-openapi.kuda.com/v2.1";

        try {

            \logInfo($jsonBody, "Fincra Request body");

            $createAccountResponse = $this->fincraPostRequest($url, $body);

            logInfo($createAccountResponse, "Kuda Create Account Response");

            if ($createAccountResponse->successful()) {

                //    $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["status"]) {
                    return [
                        'status'  => false,
                        'message' => $createAccountResponse["message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Created Successfully",
                        'data'    => [
                            'account_number'         => $createAccountResponse['data']['accountNumber'],
                            'virtual_account_number' => $createAccountResponse['data']['accountNumber'],
                        ],
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Account CreationFailed . Kindly contactsupportfor assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Laravel Kuda Error");

            return ['status' => false, 'message' => 'Error CreatingAccount . Kindly contactsupportfor assistance'];
        }
    }

    public function verifyBankAccount($bankCode, $accountNumber)
    {

        $requestReference = \generate_txnID();

        $body = [
            "servicetype" => "NAME_ENQUIRY",
            "requestRef"  => $requestReference,
            "Data"        => [
                "BeneficiaryAccountNumber" => $accountNumber,
                "BeneficiaryBankCode"      => $bankCode,

            ],
        ];
        $jsonBody = \json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        $url = "https://kuda-openapi.kuda.com/v2.1";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable . try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequestUpdate($url, $body);

            logInfo($createAccountResponse, "Kuda Verify Account Response");

            if ($createAccountResponse->successful()) {

                //$response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["status"]) {
                    return [
                        'status'  => false,
                        'message' => $createAccountResponse["message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'account_name'      => $createAccountResponse["data"]["beneficiaryName"],
                            'bank_code'         => $createAccountResponse["data"]["beneficiaryBankCode"],
                            'request_reference' => $requestReference,
                            //  'sessionID'         => $createAccountResponse["data"]["SessionID"],
                        ],
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Webhook withdrawalfailed . Kindly contactsupportfor assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Kuda Error");

            return ['status' => false, 'message' => 'Error completingwithdrawal . Kindly contactsupportfor assistance'];
        }
    }

    public function verifyTransaction($transactionReference)
    {

        $requestReference = \generate_txnID();

        $body = [
            "servicetype" => "TRANSACTION_STATUS_QUERY",
            "requestref"  => $requestReference,
            "data"        => [
                "isThirdPartyBankTransfer"    => false,
                "transactionRequestReference" => $transactionReference,
            ],
        ];
        $jsonBody = \json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        $url = "https://kuda-openapi.kuda.com/v2.1";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => "03", 'message' => 'Bank Unavailable . try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequestUpdate($url, $body);

            logInfo($createAccountResponse, "Kuda Verify Transaction Response");

            if ($createAccountResponse->successful()) {

                //$response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["status"]) {
                    if ($createAccountResponse["responseCode"] == "-4" || $createAccountResponse["responseCode"] == "-2" || $createAccountResponse["responseCode"] == "-1") {
                        return [
                            'status'  => "02",
                            'message' => "Your transaction has been found and as well reversed successfully",
                        ];
                    } else if ($createAccountResponse["responseCode"] == "k25") {
                        return [
                            'status'  => "03",
                            'message' => $createAccountResponse["message"],
                        ];
                    } else {
                        return [
                            'status'  => "04",
                            'message' => $createAccountResponse["message"],
                        ];
                    }
                } else {
                    return [
                        'status'  => "00",
                        'message' => "Transaction Verified Successfully (Session ID: " . $createAccountResponse["sessionID"] . ")",
                        'data'    => $createAccountResponse["data"],
                    ];
                }
            } else {
                return ['status' => "05", 'message' => 'Verify transactionfailed . Kindly contactsupportfor assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Kuda Error");

            return ['status' => "06", 'message' => 'Error verifyingtransaction . Kindly contactsupportfor assistance'];
        }
    }

    public function bankTransfer($requestReference, $accountNumber, $bankCode, $sessionId, $amount, $accountName, $description, $type)
    {

        //************** Old one *************************
        // $body = [
        //     "servicetype" => "VIRTUAL_ACCOUNT_FUND_TRANSFER",
        //     "requestref" => $requestReference,
        //     "data" => [
        //         "beneficiaryAccount" => $accountNumber,
        //         "beneficiarybankCode" => $bankCode,
        //         "nameEnquirySessionID" => $sessionId,
        //         "amount" => $amount * 100,
        //         "ClientAccountNumber" => "payout account",
        //         "beneficiaryName" => $accountName,
        //         "trackingReference" => "payout account",
        //         "senderName" => "Wired Banking Lite",
        //         "narration" => $description
        //     ]
        // ];

        //************** tracking Ref changed one *************************

        if ($type == 1) {
            $body = [
                "servicetype" => "SINGLE_FUND_TRANSFER",
                "requestRef"  => $requestReference,
                "Data"        => [
                    "BeneficiaryAccount"  => $accountNumber,
                    "beneficiaryBankCode" => $bankCode,
                    "amount"              => $amount * 100,
                    "ClientAccountNumber" => "3002284739",
                    "beneficiaryName"     => $accountName,
                    "SenderName"          => $description,
                    "narration"           => $description,
                ],
            ];
        } else {
            //************** New one *************************
            $body = [
                "servicetype" => "SINGLE_FUND_TRANSFER",
                "requestRef"  => $requestReference,
                "Data"        => [
                    "BeneficiaryAccount"  => $accountNumber,
                    "beneficiaryBankCode" => $bankCode,
                    "amount"              => $amount * 100,
                    "ClientAccountNumber" => "3002284739",
                    "beneficiaryName"     => $accountName,
                    "SenderName"          => $description,
                    "narration"           => $description,
                ],
            ];
        }

        $jsonBody = \json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        $url = "https://kuda-openapi.kuda.com/v2.1";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable . try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequestUpdate($url, $body);

            logInfo($createAccountResponse, "Kuda Bank Transfer Response");

            if ($createAccountResponse->successful()) {

                //    $response = $this->sanitizeResponse($createAccountResponse['data']);

                if ($createAccountResponse['responseCode'] == "-2") {

                    $newType = $type == 1 ? 15 : 1;
                    $this->bankTransfer($requestReference, $accountNumber, $bankCode, $sessionId, $amount, $accountName, $description, $newType);
                }

                if (! $createAccountResponse["status"]) {
                    return [
                        'status'  => true,
                        'message' => "Bank Transfer Processing",
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Bank Transfer Successful",
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Bank Transferfailed . if you havebeendebitedkindlycontactsupportfor reversal'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Kuda Error");

            return ['status' => false, 'message' => 'Error withBankTransfer . if you havebeendebitedkindlycontactsupportfor reversal'];
        }
    }

    private function getCurrency($currency_id)
    {
        $type = match ($currency_id) {
            4       => 'GBP',
            7       => 'EUR',

            default => 'USD', // fallback
        };
    }

    private function getNationality($request)
    {
        $type = match ($currency_id) {
            4       => 'GBP',
            7       => 'EUR',

            default => 'USD', // fallback
        };
    }

    private function fincraHeader()
    {
        $header = [
            'Content - Type' => 'application / json',
            'api - key'      => '' . $this->apiKey,
        ];

        return $header;
    }

    private function upload(Request $request, $type)
    {
        try {

            if ($type == 'bankstatement') {
                $image = $request->file('bankstatement');
            } else {
                $image = $request->file('utilitybill');
            }

            if ($image) {
                // create a new filename for the resized image
                $filename   = time() . str_replace('', '_', $image->getClientOriginalExtension());
                $uploadPath = "public/company/customer/$type/$user->uuid";

                // resize the image
                $resizedImage = Image::make($image)
                    ->resize(200, 200, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->encode();

                // upload the resized image to storage
                $upload = Storage::put($uploadPath . $filename, $resizedImage);
                // $file1 = $doc->storeAs("$uploadPath/$user->id/$filename", $resizedImage);

                $uploadInfo = $uploadPath . $filename;

                logInfo(json_encode($uploadInfo), "User $type Upload");

                return $uploadInfo;
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Fincra Error");
        }
    }

    private function fincraPostRequest($endpoint, $body)
    {
        logInfo(json_encode($body), "Fincra Process Endpoint Body");
        $response = Http::withHeaders($this->fincraHeader())->post($endpoint, $body);
        return $response;
    }

}
