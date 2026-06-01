<?php
namespace App\Jobs;

use App\Http\Controllers\ThirdParty\BridgeController;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BridgeCustomerAccountReview
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    public function handle()
    {
        \logInfo('Bridge CustomerAccountReview', 'Account Review');

        $today = now()->toDateString();

        User::whereNotNull('remittar_customer_id')
            ->whereRaw('CHAR_LENGTH(remittar_customer_id) > ?', [11])
            ->cursor() // generator
            ->each(function ($userData) use ($today) {

                try {
                    $bridgeController      = new BridgeController();
                    $bridgeCustomerAccount = $bridgeController->getBridgeCustomerInfo($userData->id);
                    logInfo(json_encode($bridgeCustomerAccount), "bridgeCustomerAccount Result");
                    if ($bridgeCustomerAccount['status'] == "incomplete") {
                        $currency_id = $userData->company->currency_id;
                        $body        = [
                            'place_of_birth'                => [
                                'country' => 'NGA',
                                'city'    => 'Lagos',
                            ],
                            'nationalities'                 => ['NGA'],
                            'account_purpose'               => 'other',
                            'account_purpose_other'         => 'Consulting income',
                            'employment_status'             => 'self_employed',
                            'expected_monthly_payments_usd' => '0_4999',
                            'acting_as_intermediary'        => false,
                            'most_recent_occupation'        => "111011",
                            'source_of_funds'               => 'savings',
                        ];

                        logInfo($body, "Bridge Customer body");

                        $baseUrl  = config('bridge.base_url_live');
                        $endpoint = "{$baseUrl}customers/{$userData->remittar_customer_id}";

                        logInfo($endpoint, "Bridge Cuustomer endpoint");

                        $updateAccount = Http::withHeaders($this->bridgeHeader('ide'))->put("{$baseUrl}{$endpoint}", $body);
                        // Check response
                        if ($updateAccount->successful()) {
                            $data = $updateAccount->json();
                            logInfo(json_encode($data), "Bridge Customer update account");
                            // Handle success
                        } else {
                            // Handle error
                            $error = $updateAccount->body();
                            logInfo(json_encode($error), "Bridge Customer update account error");
                        }

                    }

                    \logInfo('Message: Success BridgeCustomerAccountReview', 'Line: Success for ' . $userData->id);

                } catch (\Throwable $e) {

                    \Log::error('BridgeCustomerAccountReview failed for user ' . $userData->id, [
                        'error' => $e->getMessage(),
                    ]);

                }

            });

        return 0;
    }

    private function bridgeHeader($action)
    {
        logInfo(config('bridge.api_key_live'), "Bridge API key");

        $header = [
            "Api-Key"      => config('bridge.api_key_live'),
            "Content-Type" => "application/json",

        ];

        logInfo(json_encode($action), "Bridge Header action");

        if ($action == 'idemkey') {
            $header['Idempotency-Key'] = (string) Str::uuid();
        }

        logInfo(json_encode($header), "Bridge Header");

        return $header;
    }

}
