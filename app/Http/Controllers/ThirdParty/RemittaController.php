<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RemittaController extends Controller
{
    public function __construct()
    {
    }

    public function buyElectricity($transactionId, $electrictyDisco, $meterType, $meterNumber, $amount, $phoneNumber)
    {

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $electrictyDisco,
            "variation_code" => $meterType,
            "billersCode"    => $meterNumber,
            "amount"         => $amount,
            "phone"          => $phoneNumber,
        ];

        $endpoint = "pay";

        $buyElectricity = $this->vtpassPostRequest($endpoint, $body);

        logInfo($buyElectricity, "VTPass Buy Electricity Response Log");

        try {

            if (isset($buyElectricity)) {
                if ($buyElectricity["code"] == "000") {

                    if ($buyElectricity['content']['transactions']['status'] == 'pending') {
                        return [
                            "status"  => true,
                            'message' => "Transaction is Processing. you will receive a token shortly",
                        ];
                    }

                    $electricityToken = isset($buyElectricity['Token']) ? $buyElectricity['Token'] : $buyElectricity['token'];

                    $returnTypeResponse = $meterType == 'prepaid' ? [
                        'balance'         => "0.0",
                        'token'           => $electricityToken,
                        'purchased_units' => $buyElectricity['PurchasedUnits'] ?? $buyElectricity['Units'] ?? $buyElectricity['units'] ?? null,
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
            \logInfo(json_encode($e->getMessage()), "VTPass Buy Electricity Error Response Log");
            return ["status" => false, 'message' => "Sorry, Unable to get token. Kindly contact support"];
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        logInfo(json_encode($payload), "VTPASSWebHook");

        try {
            $this->processTransaction($payload);
        } catch (\Exception $e) {
            logError($e->getMessage(), "VTPASSWebHookError");
        }

        return response()->json(["response" => "success"], 200);
    }

    public function vtPassRequestId()
    {
        $result     = '';
        $dateString = \str_replace("-", "", Carbon::now()->format('Y-m-d H:i:s'));
        $dateString = \str_replace(" ", "", $dateString);
        $dateString = \str_replace(":", "", $dateString);
        // return $dateString . $this->randomNumber(8);
        return $dateString;
    }

    public function vtPassBaseUrl($endpoint)
    {
        return env('APP_MODE') == 'test' ? config('vtpass.base_url_test') . $endpoint : config('vtpass.base_url_live') . $endpoint;
    }

    public function vtpassGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->vtpassHeader())->get($this->vtPassBaseUrl($endpoint));
        return $response->json();
    }

    public function vtpassPostRequest($endpoint, $body)
    {
        logInfo($this->vtPassBaseUrl($endpoint));
        $response = Http::withHeaders($this->vtpassHeader())->post($this->vtPassBaseUrl($endpoint), $body);
        return $response->json();
    }

    public function vtpassHeader()
    {
        $header         = [];
        $basicAuthToken = '';

        if (env('APP_MODE') == 'test') {
            $basicAuthToken = base64_encode(config('vtpass.username_test') . ":" . config('vtpass.password_test'));
        } else {
            $basicAuthToken = base64_encode(config('vtpass.username_live') . ":" . config('vtpass.password_live'));
        }
        $header = ['Authorization' => 'Basic ' . $basicAuthToken];

        return $header;
    }

    public function updateTransactionAsSuccessful($transaction, $balance = 0, $updateBalance = true, $modifierId = null)
    {
        if ($updateBalance) {
            $transaction->balance_after = $balance;
        }

        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        // prefer provided modifier id, fallback to auth user id, else null
        $transaction->modified_by = $modifierId ?? auth()->user()->id;
        $transaction->save();
    }

}
