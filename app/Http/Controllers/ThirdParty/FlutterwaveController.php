<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;


class FlutterwaveController extends Controller
{
    private $secretKey;
    private $publicKey;
    private $baseUrl;
    private $encryptionKey;
    private $secretHash;

    function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->secretKey = config('flutterwave.secret_key_test');
            $this->publicKey = config('flutterwave.public_key_test');
            $this->encryptionKey = config('flutterwave.encryption_key_test');
            // $this->secretHash = config('flutterwave.secret_key_test');
            $this->baseUrl = config('flutterwave.base_url_test');
        } else {
            $this->secretKey = config('flutterwave.secret_key_live');
            $this->publicKey = config('flutterwave.public_key_live');
            $this->encryptionKey = config('flutterwave.encryption_key_live');
            // $this->secretHash = config('flutterwave.secret_key_live');
            $this->baseUrl = config('flutterwave.base_url_live');
        }
    }


    public function getExchangeRate($exchangeAmount, $sourceCurrency, $destinationCurrency)
    {
        $body = ["amount" => $exchangeAmount, "destination_currency" => $sourceCurrency, "source_currency" => $destinationCurrency];
        $url = "transfers/rates?amount=$exchangeAmount&destination_currency=$destinationCurrency&source_currency=$sourceCurrency";

        logInfo("We are converting $exchangeAmount from $sourceCurrency to $destinationCurrency");
        try {
            $exhangeRate = $this->flutterWavePostRequest($url, $body);

            logInfo($exhangeRate, "Exchange Rates");

            if ($exhangeRate['status'] == 'success') {
                $rate = $exhangeRate["data"]["rate"];
                $amount = $exhangeRate["data"]["source"]["amount"];

                $finalAmount = $amount;

                return [
                    'status' => true,
                    'message' => "Exchange rate received",
                    'data' => [
                        'rate' => $rate,
                        'amount' => $amount,
                        'final_amount' => $finalAmount
                    ]
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to get Exchange rates'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Flutterwave Error");

            return ['status' => false, 'message' => 'Failed to get Exchange rates'];
        }
    }

    public function verifyTransaction($transactionId)
    {
        $url = "transactions/$transactionId/verify";

        try {
            $verifyTransaction = $this->flutterWaveGetRequest($url);

            if ($verifyTransaction['status'] == "success") {
                if ($verifyTransaction['data']['status'] == 'successful') {

                    return ['status' => true, 'message' => "Transaction verified successfully", 'data' => $verifyTransaction['data']];
                } else {
                    return ['status' => false, 'message' => "Failed to verify transaction"];
                }
            } else {
                return ['status' => false, 'message' => "Failed to verify transaction"];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Flutterwave Verify transaction log");

            return ['status' => false, 'message' => "Failed to verify transaction"];
        }
    }

    public function initiateFundWalletTransaction($body)
    {
        $url = 'payments';

        try {
            $verifyTransaction = $this->flutterwavePostRequest($url, $body);

            logInfo($verifyTransaction, "Initiate Response Flutterwave");

            if ($verifyTransaction['status'] == "success") {
                if ($verifyTransaction['data']) {

                    return [
                        'status' => true,
                        'message' => "Transaction verified successfully",
                        'data' => [
                            'link' => $verifyTransaction['data']['link']
                        ]
                    ];
                } else {
                    return ['status' => false, 'message' => "Failed to Initiate transaction"];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Initiate transaction"];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Flutterwave Initiate transaction log");

            return ['status' => false, 'message' => "Failed to Initiate transaction"];
        }
    }

    public function getExchangeRater($exchangeAmount, $sourceCurrency, $destinationCurrency)
    {
      

                    return [
                        'status' => true,
                        'message' => "Exchange rate received",
                        'data' => [
                            'rate' => 1600,
                            'amount' => 1600 * $exchangeAmount,
                            'final_amount' => 1600 * $exchangeAmount,
                            'fee' => 0,
                            'charge_by' => 'fee'
                        ]
                    ];
             
    }

    public function getExchangeFees($exchangeAmount, $currency)
    {
        $url = "transfers/fee?amount=$exchangeAmount&currency=$currency&type=mobilemoney";

        logInfo("getting exchange fees for currency for $exchangeAmount");

        try {
            $exhangeRate = $this->flutterWaveGetRequest($url);

            logInfo($exhangeRate, "Exchange Rates");

            if ($exhangeRate['status'] == 'success') {

                $fee = $exhangeRate['data'][0]['fee'];

                return [
                    'status' => true,
                    'message' => "Exchange fees received",
                    'data' => [
                        'fee' => $fee
                    ]
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to get Exchange fees'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Flutterwave Error");

            return ['status' => false, 'message' => 'Failed to get Exchange fees'];
        }
    }

    public function flutterwaveHeader()
    {
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->secretKey
        ];

        return $header;
    }

    public function createVirtualCard($cardName, $amount, $currency, $first_name, $last_name, $date_of_birth, $email, $phone)
    {

        try {
            $body = [
                "currency" => $currency,
                "amount" => $amount,
                "billing_name" => $cardName,
                "debit_currency" => "NGN",
                "first_name" => $first_name,
                "last_name" => $last_name,
                "date_of_birth" => $date_of_birth,
                "email" => $email,
                "phone" => $phone,
                "title" => "Mr",
                "gender" => "M",
                "billing_address" => "333 Freemont Road",
                "billing_city" => "San Francisco",
                "billing_state" => "CA",
                "billing_postal_code" => "984105",
                "billing_country" => "US",
                "debit_currency" => "NGN",
                "callback_url" => url("/api/v1/transactions/flutterwave-webhook")
            ];
            $endpoint = "virtual-cards";

            $createCard = $this->flutterwavePostRequest($endpoint, $body);

            \logInfo($createCard, "Flutterwave createCard Response log");

            if ($createCard["status"] == 'success') {

                $cardType = $createCard['data']['card_type'];

                $cardTypeId = 1;

                if ($cardType == 'visa') {
                    $cardTypeId = 1;
                } else if ($cardType == 'verve') {
                    $cardTypeId = 2;
                } else if ($cardType == 'mastercard') {
                    $cardTypeId = 3;
                }
                return [
                    'status' => true,
                    'message' => "Card Created Successfully",
                    'data' => [
                        'card_type' => $cardTypeId,
                        'name_on_card' => $createCard['data']["name_on_card"],
                        'card_pan' => $createCard['data']["card_pan"],
                        'masked_pan' => $createCard['data']["masked_pan"],
                        'id' => $createCard['data']["id"],
                        'expiration' => $createCard['data']["expiration"],
                        'cvv' => $createCard['data']["cvv"]
                    ]
                ];
            } else {
                return ['status' => false, 'message' => "Failed to create virtual Card"];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Flutterwave Card Error");

            return ['status' => false, 'message' => 'Failed to create virtual card'];
        }
    }


    public function fundVirtualCard($cardReference, $amount, $currencyCode)
    {
        try {
            $body = [
                "debit_currency" => "NGN",
                "amount" => $amount
            ];

            $endpoint = "virtual-cards/$cardReference/fund";

            $createCard = $this->flutterwavePostRequest($endpoint, $body);

            \logInfo($createCard, "Flutterwave Fund card log");

            if ($createCard["status"] == 'success') {
                return [
                    'status' => true,
                    'message' => "Card Funded Successfully"
                ];
            } else {
                return ['status' => false, 'message' => "Failed to fund virtual Card"];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Flutterwave fund Card Error");

            return ['status' => false, 'message' => 'Failed to fund virtual card'];
        }
    }

    public function withdrawVirtualCard($cardReference, $amount)
    {
        try {

            $body = [
                "amount" => $amount
            ];

            $endpoint = "virtual-cards/$cardReference/withdraw";

            $createCard = $this->flutterwavePostRequest($endpoint, $body);

            \logInfo($createCard, "Flutterwave card withdraw log");

            if ($createCard["status"] == 'success') {

                return [
                    'status' => true,
                    'message' => "Virtual Card Withdrawal Successful"
                ];
            } else {
                return ['status' => false, 'message' => "Failed to Withdraw virtual Card"];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Flutterwave Card Error");

            return ['status' => false, 'message' => 'Failed to withdraw from virtual card'];
        }
    }


    public function getVirtualCardBalance($cardReference)
    {
        $endpoint = "virtual-cards/$cardReference";

        try {

            $cardDetailsFromFlutterWave = $this->flutterwaveGetRequest($endpoint);;

            logInfo($cardDetailsFromFlutterWave, "Flutterwave Get Virtual Card Response log");

            if ($cardDetailsFromFlutterWave['status'] == 'success')
                $availableCardBalance = [
                    'status' => true,
                    'message' => "Balance fetched successfully",
                    'data' => [
                        'balance' => $cardDetailsFromFlutterWave['data']['amount']
                    ]
                ];
            else {
                $availableCardBalance = ['status' => false, 'message' => "Failed to fetch balance", 'data' => ['balance' => "Not available"]];
            }
        } catch (Exception $e) {
            $availableCardBalance = ['status' => false, 'message' => 'Failed to get card balance'];
        }

        return $availableCardBalance;
    }

    public function getCardTransactionHistory($cardReference, $from, $to, $size)
    {
        $endpoint = "virtual-cards/$cardReference/transactions?from=$from&to=$to&index=1&size=$size";

        try {

            $cardDetailsFromFlutterWave = $this->flutterwaveGetRequest($endpoint);;

            logInfo($cardDetailsFromFlutterWave);

            if ($cardDetailsFromFlutterWave['status'] == 'success') {
                $availableCardBalance = ['status' => true, 'message' => "Transaction History fetched Successfully", 'data' => [$cardDetailsFromFlutterWave['data']]];
            } else {
                $availableCardBalance = ['status' => false, 'message' => "Failed to fetch balance", 'data' => ['balance' => "Not available"]];
            }
        } catch (Exception $e) {
            $availableCardBalance = ['status' => false, 'message' => 'Failed to get card balance'];
        }

        return $availableCardBalance;
    }


    public function flutterwavePostRequest($endpoint, $body)
    {
        $response = Http::withHeaders($this->flutterwaveHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function flutterwaveGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint", "Convertion url");
        $response = Http::withHeaders($this->flutterwaveHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }
}
