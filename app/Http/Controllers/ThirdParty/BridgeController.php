<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\ApiKey;
use App\Models\Company;
use App\Models\User;
use App\Models\wb_account_details;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Image;

class BridgeController extends WebhookController
{

    private $username;
    private $apiKey;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->token = "";
        if (env('APP_MODE') == 'test') {

            $this->apiKey  = config('bridge.api_key_test');
            $this->baseUrl = config('bridge.base_url_test');
        } else {

            $this->apiKey  = config('bridge.api_key_live');
            $this->baseUrl = config('bridge.base_url_live');
        }
    }

    private function getCurrencyBode($currency)
    {

    }

    public function createAccount(Request $request)
    {
        $customer       = auth('api')->user();
        $createCustomer = $this->createBridgeCustomer($request, $customer);
        return [
            'status'  => true,
            'data'    => [
                'account_reference' => $createCustomer['id'],
            ],
            'message' => "Account Pending: Your Account is under review",
        ];

    }

    public function createVirtualAccount($customer)
    {
        $customerID = $customer->remittar_customer_id;

        $currency_id = $customer->company->currency_id;
        /* match ($currency_id) {
            4       => 'GBP',
            7       => 'EUR',
            8       => 'GHS',

            default => 'USD', // fallback
        };*/

        $body = [
            "developer_fee_percent" => "0.1",
            "source"                => [
                "currency" => match ($currency_id) {
                    4       => 'gbp',
                    7       => 'eu',
                    default => 'usd', // fallback
                },
            ],
            "destination"           => [
                "currency"     => match ($currency_id) {
                    7       => 'eurc',
                    default => 'usdc', // fallback
                },
                "payment_rail" => match ($currency_id) {
                    7       => 'solana',
                    default => 'base', // fallback
                },

            ],
        ];

        $jsonBody = json_encode($body);

        $url = "{$this->baseUrl}customers/{$customerID}/virtual_accounts";

        \logInfo($url, "Bridge Request url");
        \logInfo($customerID, "Bridge Request customer id");

        try {
            \logInfo($jsonBody, "Bridge Request body");

            $action = 'idemkey';

            $createAccountResponse = $this->bridgePostRequest($url, $body, $action);

            logInfo($createAccountResponse, "bridge Create Account Response");

            if ($createAccountResponse->successful()) {

                //    $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["status"] != "activated") {
                    return [
                        'status'  => false,
                        'message' => $createAccountResponse["message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Pending",
                        'data'    => [

                            'account_number'         => $createAccountResponse['source_deposit_instructions']['bank_account_number'],
                            'virtual_account_number' => $createAccountResponse['source_deposit_instructions']['bank_account_number'],
                            'account_reference'      => $customerID,
                            'bank_routing_number'    => $createAccountResponse['source_deposit_instructions']['bank_routing_number'],
                            'currency_id'            => match ($currency_id) {
                                4       => 'GBP',
                                7       => 'EUR',
                                8       => 'GHS',

                                default => 2, // fallback
                            },
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

    public function createBridgeCustomer($request, $customer)
    {
        $firstName = $request->first_name ?? $customer->first_name;
        $lastName  = $request->last_name ?? $customer->last_name;
        $email     = $request->email ?? $customer->email;
        $phone     = $request->phone ?? $customer->phone;
        $address   = $request->address ?? $customer->address;

        $identifying_information_nino = [
            "type"            => "nino",
            "issuing_country" => "GBR",
            "number"          => $request->nino ?? $request->number,

        ];

        $identifying_information_tin = [
            "type"            => "tin",
            "issuing_country" => "NGA",
            "number"          => $request->number,

        ];

        $body_identifying_information = [
            "type"            => $request->type,
            "issuing_country" => $request->issuing_country,
            "number"          => $request->number,
            "description"     => $request->description,
            "expiration"      => $request->expiration,
            "image_front"     => $request->image_front,
            "image_back"      => $request->image_back,
        ];

        if ($request->issuing_country == "GBR") {
            $identifying_information = [
                $body_identifying_information, $identifying_information_nino,
            ];
        }

        if ($request->issuing_country == "NGA") {
            $identifying_information = [
                $body_identifying_information, $identifying_information_tin,
            ];
        }

        $body = [
            "type"                          => "individual",
            "first_name"                    => $firstName,
            "last_name"                     => $lastName,
            "phone"                         => $phone,
            "email"                         => $email,

            "residential_address"           => [
                "street_line_1" => $address ?? "test address",
                "city"          => $request->city ?? "",
                "country"       => $request->country ?? "",
                "street_line_2" => "",
                "postal_code"   => $request->postal_code,

            ],

            "birth_date"                    => $customer->dob,
            "signed_agreement_id"           => $request->signed_agreement_id,
            "endorsements"                  => [
                "base", "sepa",
            ],

            "identifying_information"       => $identifying_information,
            "place_of_birth"                => [
                "country" => $request->issuing_country,
                "city"    => $request->city ?? "",
            ],
            "nationalities"                 => [[
                $request->issuing_country,
            ]],
            "account_purpose"               => $request->issuing_country == "NGA" ? "receive_payment_for_freelancing" : $request->account_purpose,
            "employment_status"             => $request->issuing_country == "NGA" ? "self_employed" : $request->employment_status,
            "expected_monthly_payments_usd" => $request->issuing_country == "NGA" ? "0_4999" : $request->expected_monthly_payments_usd,
            "acting_as_intermediary"        => false,
            "most_recent_occupation"        => $request->issuing_country == "NGA" ? "111011" : $request->most_recent_occupation,
            "source_of_funds"               => $request->issuing_country == "NGA" ? "salary" : $request->source_of_funds,
        ];

        logInfo($body, "Bridge Customer Register request body");

        $endpoint = "$this->baseUrl/customers";
        $action   = 'idemkey';

        try {
            $result = $this->bridgePostRequest($endpoint, $body, $action);

            logInfo(json_encode($result), "Bridge Customer Register Result");

            if (isset($result['id'])) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to create customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Bridge Customer Register Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function getBridgeCustomerInfo($user_id)
    {
        $customer   = auth('api')->user() ?? User::where('id', $user_id)->first();
        $customerID = $customer->remittar_customer_id;
        $endpoint   = "$this->baseUrl/customers/$customerID";

        logInfo(json_encode($endpoint), "Bride Customer Get endpoint");

        try {
            $result = $this->bridgeGetRequest($endpoint);

            logInfo(json_encode($result), "Bride Customer Get Result");

            if (isset($result['id'])) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Bridge Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function updateForeignVirtualAccount($user)
    {
        try {

            $response = $this->createVirtualAccount($user);
            if ($saving) {

                \logInfo(json_encode($response), "updateForeignVirtualAccount for Bridge ");

                $companyRecord = Company::where(['id' => $user->company_id])->first();

                $customer_reference      = $response['data']['accountInformation']['reference'];
                $virtual_acccount_number = $response['data']['accountInformation']['accountNumber'];

                $wb_account_details = wb_account_details::where('user_id', $user->id)->first();
                $wb_account_details->update([
                    'virtual_accnum' => $virtual_acccount_number,
                    'acc_reference'  => $customer_reference,
                    'bankid'         => $bankId,
                    "currency_id"    => $user->company->currency_id,
                ]);

                $user->virtual_account   = $virtual_acccount_number;
                $user->account_reference = $customer_reference;
                $user->save();
            } else {
                return response()->json(['status' => 0, 'message' => 'Account Registration Failed!']);
            }

        } catch (Exception $e) {
            return response()->json(['status' => 0, 'message' => "An error occured!"]);
        }
    }

    public function getTOSLink()
    {
        $customer   = auth('api')->user();
        $customerID = $customer->accounts()
            ->where('currency_id', $customer->company->currency_id)
            ->value('acc_reference');

        logInfo(json_encode($customerID), "Bride Customer Result customerID)");

        $customerBridgeID = null;
        try {
            if ($customerBridgeID != null) {
                $endpoint = "https://api.bridge.xyz/v0/customers/$customerID/tos_acceptance_link";
                $result   = $this->bridgeGetRequest($endpoint);
            } else {
                $endpoint = "https://api.bridge.xyz/v0/customers/tos_links";
                $body     = [];
                $action   = 'idemkey';
                $result   = $this->bridgePostRequest($endpoint, $body, $action);
            }

            logInfo(json_encode($result), "Bride Customer Result");

            if (isset($result['url'])) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get bridge customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Bridge Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function createVirtualCard($request, $customer, $customer_account_id)
    {
        /**
         * 1. Resolve or create Sudo Customer
         */
        $customer_account_id = $customer_account_id ?? $this->getCustomerMapleradID($customer);

        if (is_null($customer_account_id)) {
            // No Sudo ID → create new Sudo customer now
            $mapleradCustomer = $this->createMapleradCustomer($request, $customer);

            // Ensure valid structure returned
            if (! isset($mapleradCustomer['data']['reference'])) {
                return ['status' => false, 'message' => "Failed to create Sudo customer"];
            }

            $customer_account_id = $mapleradCustomer['data']['reference'];
        }

        /**
         * 2. Build request body
         */
        $body = [
            "customer_id"  => $customer_account_id,
            "type"         => "VIRTUAL",
            "currency"     => "USD",
            "auto_approve" => true,
            "brand"        => "MASTERCARD",

        ];

        logInfo(json_encode($body), "Maplerad Customer Register Virtual Card body");

        $endpoint = "{$this->baseUrl}/issuing";

        /**
         * 3. Execute request
         */
        try {
            $result = $this->mapleradPostRequest($endpoint, $body);

            logInfo(json_encode($result), "Maplerad Customer Register Virtual Card Result");

            if (isset($result['status'])) {
                return [
                    'status'      => true,
                    'message'     => $result['message'],
                    'result'      => $result,
                    "customer_id" => $mapleradCustomer['data']['reference'],
                ];
            }

            return ['status' => false, 'message' => $result['message'] ?? "Failed to create card"];
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Maplerad Customer Register Virtual Card Error");
            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
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

    private function getCurrency($currency_id)
    {
        $type = match ($currency_id) {
            4       => 'GBP',
            7       => 'EUR',
            8       => 'GHS',

            default => 'USD', // fallback
        };
    }

    private function bridgeHeader($action)
    {
        logInfo($this->apiKey, "Bridge API key");

        $header = [
            "Api-Key"      => $this->apiKey,
            "Content-Type" => "application/json",

        ];

        logInfo(json_encode($action), "Bridge Header action");

        if ($action == 'idemkey') {
            $header['Idempotency-Key'] = (string) Str::uuid();
        }

        logInfo(json_encode($header), "Bridge Header");

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

    private function bridgePostRequest($endpoint, $body, $action)
    {
        logInfo(json_encode($body), "Bridge Process Endpoint Body");
        logInfo(json_encode($action), "Bridge Process Endpoint action");
        $response = Http::withHeaders($this->bridgeHeader($action))->post($endpoint, $body);
        return $response->json();
    }

    public function bridgeGetRequest($endpoint)
    {
        $action   = null;
        $response = Http::withHeaders($this->bridgeHeader($action))->get("$endpoint");
        return $response->json();
    }

}
