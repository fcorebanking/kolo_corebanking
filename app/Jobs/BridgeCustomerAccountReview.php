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
        \logInfo('pending Customer Saving Maintenance cron job', 'Maintenance');

        $today = now()->toDateString();

        User::whereNotNull('remittar_customer_id')
            ->whereRaw('CHAR_LENGTH(remittar_customer_id) > ?', [11])
            ->cursor() // generator
            ->each(function ($userData) use ($today) {

                try {
                    $bridgeController      = new BridgeController();
                    $bridgeCustomerAccount = $bridgeController->getBridgeCustomerInfo($userData->id);
                    if ($bridgeCustomerAccount['status'] == "incomplete") {
                        $currency_id = $customer->company->currency_id;
                        $body        = [
                            "identifying_information" => [
                                [
                                    "type"            => match ($currency_id) {
                                        4       => 'nino',
                                        7       => 'nino',
                                        8       => 'number',

                                        default => 'number'
                                    },
                                    "issuing_country" => match ($currency_id) {
                                        4       => 'GBP',
                                        7       => 'EUR',
                                        8       => 'GHS',

                                        default => 'NGN'
                                    }, // fallback,
                                    "number"          => $userData->bvn,
                                ],
                            ],
                        ];

                        logInfo($body, "Bridge Cuustomer body");

                        $baseUrl  = config('bridge.api_key_live');
                        $endpoint = "{$baseUrl}customers/{$userData->remittar_customer_id}";

                        logInfo($endpoint, "Bridge Cuustomer endpoint");

                        $updateAccount = Http::withHeaders($this->bridgeHeader('ide'))

                            ->put("{$baseUrl}{$endpoint}", $body);

                        logInfo($updateAccount, "Bridge Cuustomer update account");
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
