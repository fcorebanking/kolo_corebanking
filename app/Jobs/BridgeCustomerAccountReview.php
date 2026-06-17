<?php
namespace App\Jobs;

use App\Http\Controllers\ThirdParty\BridgeController;
use App\Models\savings_product;
use App\Models\User;
use App\Models\wb_account_details;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

                        logInfo(json_encode($bridgeCustomerAccount), "bridgeCustomerAccount Incomplete Status");

                    } else {
                        $account = $this->getForeignVirtualAccount($userData);
                        if (! $account['status']) {
                            $account = $this->createForeignVirtualAccount($userData);
                        }
                        $this->updateForeignVirtualAccount($userData, $account);
                    }
                    \logInfo('Message: Success BridgeCustomerAccountReview', 'Line: Success for ' . $userData->id);

                } catch (\Throwable $e) {

                    \Log::error('BridgeCustomerAccountReview failed for user ' . $userData->id, [
                        'error' => $e->getMessage(),
                        'line'  => $e->getLine(),
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

    public function createForeignVirtualAccount($userData)
    {
        $createAccount = $bridgeController->updateForeignVirtualAccount($userData);
        return $createAccount;
    }

    public function getForeignVirtualAccount($userData)
    {
        $bridgeController = new BridgeController();
        $account          = $bridgeController->getForeignVirtualAccount($userData);
        return $account;
    }

    public function updateForeignVirtualAccount($userData, $account)
    {

        $saving = savings_product::where([
            'company_id' => $userData->company_id,
            // 'id'         => $request->savings_product_type,
        ])->first();

        \logInfo(json_encode($saving), "api saving account product Bridge");

        $accountWBDetail = wb_account_details::create([
            'user_id'            => $userData->id,
            'account_product_id' => $saving->id,
            'bankid'             => $account['data']['bank_routing_number'],
            'virtual_accnum'     => $account['data']['account_number'],
            'acc_reference'      => $account['data']['account_reference'],
            'has_atm_card'       => 0,
            'currency_id'        => $account['data']['account_number']['currency_id'],
        ]);

        user_wallet::create([
            'wb_account_detail_id' => $accountWBDetail->id,
            'balance'              => 0,
        ]);

        $userData->update([
            'remittar_customer_id' => $account['data']['account_number'],

        ]);

    }

}
