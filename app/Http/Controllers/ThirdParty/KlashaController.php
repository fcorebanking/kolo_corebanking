<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;


class KlashaController extends Controller
{
    private $privateKey;
    private $publicKey;
    private $baseUrl;
    private $encryptionKey;

    function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->privateKey = config('klasha.private_key_test');
            $this->publicKey = config('klasha.public_key_test');
            $this->encryptionKey = config('klasha.encryption_key_test');
            // $this->secretHash = config('klasha.private_key_test');
            $this->baseUrl = config('klasha.base_url_test');
        } else {
            $this->privateKey = config('klasha.private_key_live');
            $this->publicKey = config('klasha.public_key_live');
            $this->encryptionKey = config('klasha.encryption_key_live');
            // $this->secretHash = config('klasha.private_key_live');
            $this->baseUrl = config('klasha.base_url_live');
        }
    }


    public function getExchangeRate($exchangeAmount, $sourceCurrency, $destinationCurrency)
    {
        $body = ["amount" => $exchangeAmount, "destination_currency" => $sourceCurrency, "source_currency" => $destinationCurrency];
        $url = "transfers/rates?amount=$exchangeAmount&destination_currency=$destinationCurrency&source_currency=$sourceCurrency";

        logInfo("We are converting $exchangeAmount from $sourceCurrency to $destinationCurrency");
        try {
            $exhangeRate = $this->klashaPostRequest($url, $body);

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
            logInfo($e->getMessage(), "Klasha Error");

            return ['status' => false, 'message' => 'Failed to get Exchange rates'];
        }
    }


    public function verifyTransaction($transactionId)
    {
        $url = "nucleus/tnx/merchant/status";

        try {

            $body = [
                "tnxRef" => $transactionId
            ];

            $verifyTransaction = $this->klashaPostRequest($url, $body);

            \logInfo($verifyTransaction, "Verify Klash Transaction Response");

            if ($verifyTransaction['status'] == "successful") {

                return [
                    'status' => true,
                    'message' => "Transaction verified successfully",
                    'data' => [
                        'amount' => $verifyTransaction['sourceAmount'],
                        'source_currency' => $verifyTransaction['sourceCurrency']
                    ]
                ];
            } else {
                return ['status' => false, 'message' => "Failed to verify transaction"];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Klasha Verify transaction log");

            return ['status' => false, 'message' => "Failed to verify transaction"];
        }
    }

    public function getExchangeRater($exchangeAmount, $sourceCurrency, $destinationCurrency)
    {
        $url = "transfers/rates?amount=$exchangeAmount&destination_currency=$destinationCurrency&source_currency=$sourceCurrency";

        logInfo("We are converting $exchangeAmount from $sourceCurrency to $destinationCurrency");
        try {

            $mainCurrency = $destinationCurrency == "NGN" ? $sourceCurrency : $destinationCurrency;

            $responses = Http::pool(fn (Pool $pool) => [
                $pool->withHeaders($this->klashaHeader())->get("$this->baseUrl$url"),
                $pool->withHeaders($this->klashaHeader())->get($this->baseUrl . "transfers/fee?amount=$exchangeAmount&currency=$mainCurrency&type=mobilemoney")
            ]);

            \logInfo($responses[0], "First Request response");
            \logInfo($responses[1], "Second Request response");


            if ($responses[0]->ok() && $responses[1]->ok()) {

                $exhangeRate = $responses[0]->json();
                $exchangeFees = $responses[1]->json();

                \logInfo($exhangeRate, "Exchange Rate New");
                \logInfo($exchangeFees, "Exchange Fees new");

                if ($exhangeRate['status'] == 'success' && $exchangeFees['status'] == 'success') {
                    $rate = $exhangeRate["data"]["rate"];
                    $amount = $exhangeRate["data"]["source"]["amount"];

                    $fee = $exchangeFees['data'][0]['fee'] > 0 ? $exchangeFees['data'][0]['fee'] : $exchangeFees['data'][1]['fee'];
                    $chargeby = $exchangeFees['data'][0]['fee'] > 0 ? $exchangeFees['data'][0]['fee_type'] : $exchangeFees['data'][1]['fee_type'];

                    $finalAmount = $amount;

                    return [
                        'status' => true,
                        'message' => "Exchange rate received",
                        'data' => [
                            'rate' => $rate,
                            'amount' => $amount,
                            'final_amount' => $finalAmount,
                            'fee' => $fee,
                            'charge_by' => $chargeby
                        ]
                    ];
                } else {
                    return ['status' => false, 'message' => 'Failed to get Exchange rates'];
                }
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Klasha Error");

            return ['status' => false, 'message' => 'Failed to get Exchange rates'];
        }
    }

    public function getExchangeFees($exchangeAmount, $currency)
    {
        $url = "transfers/fee?amount=$exchangeAmount&currency=$currency&type=mobilemoney";

        logInfo("getting exchange fees for currency for $exchangeAmount");

        try {
            $exhangeRate = $this->klashaGetRequest($url);

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
            logInfo($e->getMessage(), "Klasha Error");

            return ['status' => false, 'message' => 'Failed to get Exchange fees'];
        }
    }

    public function klashaHeader()
    {
        $header = [
            'Content-Type' => 'application/json',
        ];

        return $header;
    }

    public function fundVirtualCard($cardReference, $amount, $currencyCode)
    {
        try {
            $body = [
                "debit_currency" => "NGN",
                "amount" => $amount
            ];

            $endpoint = "virtual-cards/$cardReference/fund";

            $createCard = $this->klashaPostRequest($endpoint, $body);

            \logInfo($createCard, "fund card log");

            if ($createCard["status"] == 'success') {
                return [
                    'status' => true,
                    'message' => "Card Funded Successfully"
                ];
            } else {
                return ['status' => false, 'message' => "Failed to fund virtual Card"];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Klasha Error");

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

            $createCard = $this->klashaPostRequest($endpoint, $body);

            \logInfo($createCard, "Klasha withdraw log");

            if ($createCard["status"] == 'success') {

                return [
                    'status' => true,
                    'message' => "Virtual Card Withdrawal Successful"
                ];
            } else {
                return ['status' => false, 'message' => "Failed to Withdraw virtual Card"];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Klasha Error");

            return ['status' => false, 'message' => 'Failed to withdraw from virtual card'];
        }
    }

    public function klashaPostRequest($endpoint, $body)
    {
        \logInfo("$this->baseUrl$endpoint");
        \logInfo($this->klashaHeader(), "Klasha header log");
        \logInfo($body, "Klasha body log");
        $response = Http::withHeaders($this->klashaHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function klashaGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint", "Convertion url");
        $response = Http::withHeaders($this->klashaHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }
}
