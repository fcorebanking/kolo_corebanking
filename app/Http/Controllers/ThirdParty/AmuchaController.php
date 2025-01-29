<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\status;
use App\Models\UnusedProvidusAccounts;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AmuchaController extends WebhookController
{
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    private $accountId;
    private $token;

    function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->baseUrl = config('amucha.base_url_test');
            $this->accountId = config('amucha.account_id_test');
            $this->clientSecret = config('amucha.client_secret_test');
            $this->clientId = config('amucha.client_id_test');
        } else {
            $this->baseUrl = config('amucha.base_url_test');
            $this->accountId = config('amucha.account_id_test');
            $this->clientSecret = config('amucha.client_secret_test');
            $this->clientId = config('amucha.client_id_test');
        }
    }

    public function transferFunds($amount, $receiverAccountId, $merchantRef, $accountId)
    {
        $body = [
            "amount" => $amount,
            "receiverAccountId" => $receiverAccountId,
            "merchantTxRef" => $merchantRef
        ];

        logInfo($body, "Amucha request body");

        $loginResult = $this->login();

        if (!$loginResult['status']) {
            return $loginResult;
        }

        try {
            $response = $this->amuchaPostRequest("transfers/wallet/$accountId", $body);

            logInfo($response, "Amucha Result");

            if (isset($response['code'])) {
                if ($response['code'] == "00") {

                    return [
                        'status' => true,
                        'message' => $response['description'],
                        'data' => $response['data'],
                    ];
                } else {
                    return ['status' => false, 'message' => $response['description']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }


    public function assignTerminal($serialNumber, $terminalLabel, $accountId)
    {
        $body = [
            "serialNumber" => $serialNumber,
            "terminalLabel" => $terminalLabel
        ];

        logInfo($body, "Amucha request body");

        $loginResult = $this->login();

        if (!$loginResult['status']) {
            return $loginResult;
        }

        try {
            $response = $this->amuchaPostRequest("terminals/assign/$accountId", $body);

            logInfo($response, "Amucha Result");

            if (isset($response['code'])) {
                if ($response['code'] == "00") {

                    return [
                        'status' => true,
                        'message' => $response['description'],
                        'data' => $response['data'],
                    ];
                } else {
                    return ['status' => false, 'message' => $response['description']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function unassignTerminal($serialNumber, $terminalLabel)
    {
        $body = [
            "serialNumber" => $serialNumber,
            "terminalLabel" => $terminalLabel
        ];

        logInfo($body, "Amucha request body");

        $loginResult = $this->login();

        if (!$loginResult['status']) {
            return $loginResult;
        }

        try {
            $response = $this->amuchaPostRequest("terminals/unassign", $body);

            logInfo($response, "Amucha Result");

            if (isset($response['code'])) {
                if ($response['code'] == "00") {

                    return [
                        'status' => true,
                        'message' => $response['description'],
                        'data' => $response['data'],
                    ];
                } else {
                    return ['status' => false, 'message' => $response['description']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function getTerminalBalance($terminalId, $serialNumber, $pin)
    {

        $body = [
            "action_type" => "pre-balance-check-auth", //// Return customer balance  if serial number match with customer pin
            "action_id" => "q6cf8t96-7fg8-432e-98c8-d4cb8f5b",
            "version" => "v1.1.0",
            "data" => [
                "terminal" => [
                    "terminalId" => $terminalId,
                    "serialNo" => $serialNumber,
                    "pin" => $pin,
                    "signature" => "plainKeyPassedByUserAndHashedWithMerchantSecretKey"
                ]
            ]
        ];


        logInfo($body, "Amucha request body");

        try {
            $response = $this->amuchaPostRequest("terminals/unassign", $body);

            logInfo($response, "Amucha Result");

            if (isset($response['code'])) {
                if ($response['code'] == "00") {

                    return [
                        'status' => true,
                        'message' => $response['description'],
                        'data' => $response['data'],
                    ];
                } else {
                    return ['status' => false, 'message' => $response['description']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }


    public function registerCustomer($accountRef, $phoneNumber, $email, $bvn, $pin, $accountName, $callbackUrl, $expiryDate)
    {
        $body = [
            "accountRef" => $accountRef,
            "phoneNumber" => $phoneNumber,
            "email" => $email,
            "bvn" => $bvn,
            "pin" => $pin,
            "accountName" => $accountName,
            "currency" => "NGN",
            "callbackUrl" => $callbackUrl,
            "expiryDate" => $expiryDate
        ];

        logInfo($body, "Amucha request body");

        $loginResult = $this->login();

        if (!$loginResult['status']) {
            return $loginResult;
        }

        try {
            $response = $this->amuchaPostRequest("accounts", $body);

            logInfo($response, "Amucha Result");

            if (isset($response['code'])) {
                if ($response['code'] == "00") {

                    return [
                        'status' => true,
                        'message' => $response['description'],
                        'data' => $response['data']
                    ];
                } else {
                    return ['status' => false, 'message' => $response['description']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }


    public function verifyTransaction($transactionReference)
    {

        $loginResult = $this->login();

        if (!$loginResult['status']) {
            return $loginResult;
        }

        $verifyPaymentResponse = $this->amuchaGetRequest("checkout/transaction?idType=ORDER_ID&id=$transactionReference");

        try {
            logInfo($verifyPaymentResponse, "Verifying Tranaction Response");

            if (isset($verifyPaymentResponse['code'])) {
                return [
                    'status' => $verifyPaymentResponse['data']['success'],
                    'message' => $verifyPaymentResponse['data']['message'],
                    'data' => $verifyPaymentResponse['data']['transactionDetails']
                ];
            } else {
                return ['status' => false, 'message' => "Failed to verify Transaction"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");
            return ['status' => false, 'message' => "Failed to Verify Transaction... Please contact support"];
        }
    }

    public function verifyIncomingWebhook(Request $request)
    {

        $currentRequest = $request->all();


        if ($currentRequest['data']['merchant']['userId'] != $this->accountId) {
            return ['status' => false, "requestSuccessful" => false, 'sessionId' => $request->sessionId, 'message' => "Failed to verify webhook"];
        }

        return ['status' => true, 'message' => 'Webhook Verified Successfully'];
    }

    public function getamuchaHeader()
    {
        $stringTxt = "$this->clientId:$this->clientSecret";
        return hash('sha512', $stringTxt);
    }


    public function createAccount($accountName, $accountReference)
    {
        $body = [
            "accountName" => $accountName,
            "accountRef" => $accountReference
        ];

        logInfo($body, "Amucha request body");

        $loginResult = $this->login();

        if (!$loginResult['status']) {
            return $loginResult;
        }

        try {
            $createAccount = $this->amuchaPostRequest("accounts/virtual", $body);

            logInfo($createAccount, "Amucha Result");

            if (isset($createAccount['code'])) {
                if ($createAccount['code'] == "00") {

                    return [
                        'status' => true,
                        'message' => $createAccount['description'],
                        'data' => [
                            'account_number' => $createAccount['data']['bankAccountNumber'],
                        ]
                    ];
                } else {
                    return ['status' => false, 'message' => $createAccount['description']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function checkoutOrder($amount)
    {
        $body = [
            "order" => [
                "amount" => $amount,
                "currency" => "NGN",
                "customerEmail" => "dsshema4all@gmail.com",
                "orderReference" => generateTransactionReference()
            ],
            "tokenizeCard" => true
        ];

        try {
            $createAccount = $this->amuchaPostRequest("checkout/order", $body);

            logInfo($createAccount, "Amucha Order Result");

            if (isset($createAccount['code'])) {
                if ($createAccount['code'] == "00") {

                    return [
                        'status' => true,
                        'message' => $createAccount['description'],
                        'data' => [
                            'order_reference' => $createAccount['data']['orderReference'],
                        ]
                    ];
                } else {
                    return ['status' => false, 'message' => $createAccount['description']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function verifyCardOtp($otp, $orderReference, $transactionId)
    {

        $loginResult = $this->login();

        if (!$loginResult['status']) {
            return $loginResult;
        }

        $body = [
            "transactionId" => $transactionId,
            "orderReference" => $orderReference,
            "otp" => $otp,
        ];

        try {
            $verifyOtpResult = $this->amuchaPostRequest("checkout/checkout-card-otp", $body);

            logInfo($verifyOtpResult, "Amucha Verify Otp Result");

            if (isset($verifyOtpResult['code'])) {
                if ($verifyOtpResult['code'] == "00") {
                    return $verifyOtpResult['data'];
                } else {
                    return ['status' => false, 'message' => $verifyOtpResult['description']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function checkoutCardDetails($cvv, $cardExpiryMonth, $cardExpiryYear, $cardNumber, $cardPin, $orderRef)
    {

        $cardDetails = [
            "cardCVV" => $cvv,
            "cardExpiryMonth" => $cardExpiryMonth,
            "cardExpiryYear" => $cardExpiryYear,
            "cardNumber" => $cardNumber,
            "cardPin" => $cardPin
        ];

        $body = [
            "cardDetails" => json_encode($cardDetails),
            "deviceInformation" => [
                "deviceChannel" => "Browser",
                "httpBrowserColorDepth" => "30",
                "httpBrowserJavaEnabled" => "true",
                "httpBrowserJavaScriptEnabled" => "true",
                "httpBrowserLanguage" => "en-GB",
                "httpBrowserScreenHeight" => "900",
                "httpBrowserScreenWidth" => "1500",
                "httpBrowserTimeDifference" => "-60",
                "userAgentBrowserValue" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)"
            ],
            "key" => "<string>",
            "orderReference" => $orderRef,
            "saveCard" => "false"
        ];

        try {
            $checkoutCardResult = $this->amuchaPostRequest("checkout/checkout-card-detail", $body);

            logInfo($checkoutCardResult, "Amucha Checkout Card Result");

            if (isset($checkoutCardResult['code'])) {
                if ($checkoutCardResult['code'] == "00") {

                    return [
                        'status' => true,
                        'message' => $checkoutCardResult['data']['message'],
                        'data' => [
                            'order_reference' => $orderRef,
                            'transaction_id' => $checkoutCardResult['data']['transactionId']
                        ]
                    ];
                } else {
                    return ['status' => false, 'message' => $checkoutCardResult['description']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function processOrder($amount, $cvv, $cardExpiryMonth, $cardExpiryYear, $cardNumber, $cardPin)
    {

        $loginResult = $this->login();

        if (!$loginResult['status']) {
            return $loginResult;
        }

        $checkoutResult = $this->checkoutOrder($amount);

        if (!$checkoutResult['status']) {
            return $checkoutResult;
        }

        $orderRef = $checkoutResult['data']['order_reference'];

        $cardCheckoutResult = $this->checkoutCardDetails($cvv, $cardExpiryMonth, $cardExpiryYear, $cardNumber, $cardPin, $orderRef);

        try {
            logInfo($cardCheckoutResult, "Amucha Result");

            if (isset($cardCheckoutResult['status'])) {
                if ($cardCheckoutResult['status']) {
                    return $cardCheckoutResult;
                } else {
                    return ['status' => false, 'message' => $cardCheckoutResult['message']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function fundPhysicalCard($email, $phone, $amount, $pan, $firstName, $lastName, $transactionId)
    {
        $body = [
            "clientSecret" => $this->clientSecret,
            "clientId" => $this->clientId,
            "currencyCode" => "566",
            "emailAddress" => $email,
            "firstName" => $firstName,
            "lastName" => $lastName,
            "mobileNr" => $phone,
            "amount" => $amount,
            "pan" => $pan,
            "PaymentRef" => $transactionId
        ];

        logInfo($body, "Amucha request body");

        try {
            $cardResult = $this->amuchaPostRequest('PrepaidCardFundCard', $body);

            logInfo($cardResult, "Amucha Fund card result body");

            if (isset($cardResult['responseCode'])) {
                if ($cardResult['responseCode'] == "90000") {
                    return ['status' => "00", 'message' => "Card Funded Successfully"];
                } else if ($cardResult['responseCode'] == "99" || $cardResult['responseCode'] == "") {
                    return ['status' => "01", 'message' => "Card funding failed and funds have already been reversed"];
                } else {
                    return ['status' => "02", 'message' => "Card funding is processing.. Kindly check your card balance before you contact support"];
                }
            } else {
                return ['status' => "03", 'message' => "Card funding is processing.. Kindly check your card balance before you contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");
            return ['status' => "04", 'message' => "Failed to Fund Card... Please contact support"];
        }
    }

    public function getPhysicalCardBalance($cardNumber)
    {


        logInfo($cardNumber, "getPhysicalCardBalance request body");

        $url = "PrepaidCardGetCardBalance?CardNo=$cardNumber&clientSecret=wired_banking&clientId=w!r39_6A4k1n9.P0";

        try {
            $cardResult = $this->amuchaGetRequest($url);

            logInfo($cardResult, "Amucha Fund card result body");

            if (isset($cardResult['StatusCode'])) {
                if ($cardResult['StatusCode'] == "200") {
                    $decodedResult = json_decode($cardResult["JsonData"], true);
                    return ['status' => true, 'message' => "Card Balance gotten Successfully", "data" => ['available_balance' => \moneyFormat($decodedResult['availableBalance'] / 100)]];
                } else {
                    return ['status' => false, 'message' => "Failed to Get Card Balance... Please contact support"];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Get Card Balance... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Amucha Error");
            return ['status' => false, 'message' => "Failed to Get Card Balance... Please contact support"];
        }
    }

    public function createVirtualAccount($name, $bvn)
    {

        $curl = curl_init();
        $bvn = $bvn == '' ? '' : $bvn;

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://vps.providusbank.com/vps/api/PiPCreateReservedAccountNumber',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "account_name":"' . $name . '",
            "bvn":"' . $bvn . '"
            
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Client-Id: key',
                'X-Auth-Signature: key'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $createAccount = \json_decode($response, true);

        if (isset($createAccount['requestSuccessful'])) {
            if ($createAccount['requestSuccessful']) {
                return [
                    'status' => true,
                    'message' => $createAccount['responseMessage'],
                    'data' => [
                        'account_number' => $createAccount['account_number']
                    ]
                ];
            } else {
                return ['status' => false, 'message' => $createAccount['responseMessage']];
            }
        } else {
            return ['status' => false, 'message' => "Failed to create account... Please contact support"];
        }
    }

    public function vtpassGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->amuchaHeaderV2())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function amuchaPostRequest($endpoint, $body)
    {
        $response = Http::withHeaders($this->amuchaHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function amuchaGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->amuchaHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function amuchaHeader()
    {
        $header = [];
        $header = [
            'Content-Type' => 'application/json',
            'accountId' => $this->accountId,
            'Authorization' => "Bearer $this->token",
        ];

        return $header;
    }

    public function login()
    {
        $body = [
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "grant_type" => "client_credentials"
        ];

        $header = [
            'Content-Type' => 'application/json',
            'accountId' => $this->accountId,
        ];
        
        logInfo($body, "Amucha Login Request Body");
        logInfo( $header, "Amucha Login Request Header");

        try {
            
            $response = Http::withHeaders($header)->post($this->baseUrl . "auth/token/issue", $body);
           
            $response = $response->json();
  /*          
            $body = [
            "client_id" => "b5795c6c-db89-47a4-9a47-7bf62878f49",
            "client_secret" => "0cK1nyXjemRJmt0uNQitbglLtxNHHqSxtvWlra21kc7PvP3ImhRYA+MtnR02WthpTdDZHApXJvzMGmTp5uiYKQ==",
            "grant_type" => "client_credentials"
        ];
            
            $curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "https://api.nomba.com/v1/auth/token/issue",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => json_encode($body),
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "accountId: 8da10ebb-a8e1-4b7e-b92e-c03d7c8b87bf"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

//$response = json_decode($response, true);
*/
            logInfo($response, "Amucha Login");
            
 //            logInfo($err, "Amucha Login Error");

            if ($response['code'] == "00") {
                $this->token = $response["data"]['access_token'];
                return ['status' => true, 'message' => 'Login success'];
            } else {
                return ['status' => false, 'message' => $response['description']];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Amucha Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }
}
