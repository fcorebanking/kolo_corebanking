<?php
namespace App\Console\Commands;

use App\Models\transactions;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\WebhookServer\WebhookCall;

class WebhookCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        logInfo("transaction for webhooks checking");

        $compayIDs = [49];

        foreach ($compayIDs as $companyID) {
            $transactions = $this->getBankTransferTransactions($companyID);
            $this->createWebHooks($transactions, $companyID);
        }
    }

    public function getBankTransferTransactions($companyID)
    {
        $transactions = $transactions = transactions::where('company_id', $companyID)
            ->where(['transaction_remarks_id' => 2, 'transaction_type_id' => 6])
            ->orWhere(['transaction_remarks_id' => 2, 'transaction_type_id' => 8])
            ->whereDate('created_at', Carbon::today())
            ->select('id', 'transaction_reference')
            ->orderBy('id', 'ASC')
            ->limit(10)
            ->get();
        logInfo(json_encode($transactions), "transaction for webhooks");
        return $transactions;
    }

    public function createWebHooks($transactions, $companyID)
    {
        foreach ($transactions as $transaction) {
            $transaction_id = $transaction->transaction_reference;
            $webHookLog     = $this->getWebHookLog($transaction_id);
            if (isset($webHookLog->body)) {

                $url                = config("webhookcompany.{$companyID}");
                $data               = $webHookLog->body;
                $webHookLog->status = "success";
                $webHookLog->save();
                $this->createSingleWebhook($url, $data);

            }
        }
    }

    public function createSingleWebhook($url, $data)
    {
        logInfo(json_encode($url), "webhook url");
        logInfo(json_encode($data), "webhook data");

        $webhook = WebhookCall::create()
            ->url("https://core.finova.ng/v1/test-finova-webhook")
            ->payload(['data' => $data])
            ->useSecret('finova-my-secret')
            ->dispatch();

        return WebhookCall::create()
            ->url($url)
            ->payload(['data' => $data])
            ->useSecret('finova-my-secret')
            ->dispatch();
    }

    public function getWebHookLog($transaction_id)
    {
        $webHookLog = WebhookLog::where('unique_reference', $transaction_id)->whereNull('status')->first();
        return $webHookLog;
    }

}
