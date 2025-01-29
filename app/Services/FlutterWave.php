<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
class FlutterWave{
    public $token ='';
    public function __construct()
    {
        $this->token=env('FLUTTER_WAVE_TOKEN');
    }
    public function sendMoney($payload)
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
        ])->post('https://api.flutterwave.com/v3/transfers', [
            'account_bank' => 'FMM',
            'account_number' => $payload['account_number'],
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'debit_currency' => $payload['debit_currency'],
            'narration' => $payload['narration'],
            'tx_ref' => $payload['tx_ref'],
            'beneficiary_name' => $payload['beneficiary_name'],
            'meta' => [
                'sender' => 'WIRED BANKING AFRICA',
                'sender_country' => 'NGN',
                'mobile_number' => '2349030306672',
            ],
        ]);
        return $response->json();
    }
    public function receiveMoney($payload)
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
        ])->post('https://api.flutterwave.com/v3/charges?type=mobile_money_franco', [
            'tx_ref' => $payload['tx_ref'],
            'amount' => $payload['amount'],
            'currency' => 'XAF',
            'narration' => $payload['narration'],
            'country' =>'CM',
            'email' => $payload['email']??'dsshema4all@gmail.com',
            'phone_number' => $payload['phone_number'],
            'fullname' => $payload['fullname'],
            'debit_currency' => 'XAF',
        ]);
        return $response->json();
    }
    public function webhook($transactionID)
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
        ])->get('https://api.flutterwave.com/v3/transactions/'.$transactionID.'/verify');
        return $response->json();
    }

}

