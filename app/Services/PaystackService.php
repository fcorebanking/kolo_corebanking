<?php
namespace App\Services;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\Customersavings;
use App\Models\Pendingcustomersaving;
use App\Models\SavingsInvoice;
use App\Models\Savingstransactionlog;
use App\Models\Savingsubscriptionlog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaystackService
{
    public $secret_key = '';
    public $public_key = '';
    public $base_url   = 'https://api.paystack.co/';
    public function __construct()
    {
        $this->secret_key = env('PAYSTACK_SECRET');
        $this->public_key = env('PAYSTACK_PUBLIC');
    }

    public function createPlan($amount, $name, $period, $duration)
    {
        return Http::withHeaders(['Authorization' => "Bearer $this->secret_key"])->post($this->base_url . 'plan', [
            "name"          => "$name",
            "interval"      => "$period",
            "amount"        => $amount * 100,
            "invoice_limit" => $duration,
        ]);
    }
    public function createSubscription($email, $plan, $amount)
    {
        return Http::withHeaders(['Authorization' => "Bearer $this->secret_key"])->post($this->base_url . 'transaction/initialize', [
            "email"  => "$email",
            "plan"   => "$plan",
            "amount" => $amount * 100,
        ]);
    }
    public function createListenSubscription($email, $plan, $amount, $start_date)
    {
        return Http::withHeaders(['Authorization' => "Bearer $this->secret_key"])->post($this->base_url . 'subscription', [
            "customer"   => "$email",
            "plan"       => "$plan",
            "amount"     => $amount * 100,
            "start_date" => $start_date,
        ]);
    }
    public function changeCard($code)
    {
        return Http::withHeaders(['Authorization' => "Bearer $this->secret_key"])->get($this->base_url . 'subscription/' . $code . '/manage/link');
    }
    public function paymentCallBack($reference)
    {
        $request = Http::withHeaders(['Authorization' => "Bearer $this->secret_key"])->get($this->base_url . 'transaction/verify/' . $reference);
        // $payload = $request->getContent();
        $data = json_decode($request, true);
        return $data;

    }
    public function handleWebhook(Request $request)
    {
        $response_status = $request->all();

        // file_put_contents(public_path('PaystackWebHook.txt'), PHP_EOL . json_encode($request->all()), FILE_APPEND);

        \logInfo(json_encode($request->all()), "PaystackWebHook");

        $email     = $response_status['data']['customer']['email'];
        $amount    = $response_status['data']['amount'] ?? null;
        $reference = $response_status['data']['reference'];
        $meta      = $response_status['data']['metadata'] ?? null;
        $date      = $response_status['data']['paid_at'] ?? null;

        \logInfo($email, "PaystackWebHook email");
        \logInfo($amount, "PaystackWebHook amount");
        \logInfo($reference, "PaystackWebHook reference");
        \logInfo($meta, "PaystackWebHook metadata");
        \logInfo($date, "PaystackWebHook data");

        $this->setUpWebhookController($response_status . $request);
        return http_response_code(200);

    }

    public function setUpWebhookController($response_status, $request)
    {
        $webhookController = new WebhookController;
        $description       = "You received Funds from " . $response_status['data']['metadata']['virtualAccount'];

        $webhookController->transactionReference = $response_status['data']['reference'];
        $webhookController->wbaCharge            = getWbaTransferCharges(2, $response_status['data']['amount'] / 100);
        $webhookController->virtualAccount       = $response_status['data']['metadata']['virtualAccount'];
        $webhookController->amount               = $response_status['data']['amount'];
        $webhookController->webhookType          = 'paystack';
        $webhookController->description          = $description;
        $webhookController->transactionDate      = date('Y-m-d');
        $webhookController->request              = $request;
        $webhookController->fromTempWebhook      = 'false';

        $webhookController->handleWebhook();

    }

    public function checkEvent($data)
    {
        $event = $data['event'];
        \logInfo($data, "Paystack Event");
        if ($event == "subscription.create") {
            $this->subscriptionCreate($event, $data);

        }

        if ($event == "charge.success") {
            $this->chargeSuccess($event, $data);
        }

        if ($event == "subscription.disable") {
            $this->subscriptionDisable($event, $data);
        }

        if ($event == "subscription.not_renew") {
            $this->subscriptionNotRenewed($event, $data);
        }

        if ($event == "invoice.create" || $event == "invoice.payment_failed") {
            $this->invoice($event, $data);
        }

    }

    public function subscriptionCreate($event, $data)
    {
        try {
            \logInfo($data, "Subscription created for paystack");
            if ($data['data']['status'] = 'active') {
                $customersavings = Customersavings::where(['plan_code' => $data['data']['plan']['plan_code']])->first();
                if ($customersavings) {
                    $SavingExist                               = Savingstransactionlog::where(['subscription_code' => $data['data']['subscription_code'], 'customer_code' => $data['data']['customer']['customer_code']])->first();
                    $savingstransactionlog                     = ($SavingExist) ? $SavingExist : new Savingstransactionlog;
                    $savingstransactionlog->uuid               = uniqid();
                    $savingstransactionlog->customersaving_id  = $customersavings->id;
                    $savingstransactionlog->status             = $data['data']['status'];
                    $savingstransactionlog->subscription_code  = $data['data']['subscription_code'];
                    $savingstransactionlog->amount             = $data['data']['amount'];
                    $savingstransactionlog->cron_expression    = $data['data']['cron_expression'];
                    $savingstransactionlog->next_payment_date  = $data['data']['next_payment_date'];
                    $savingstransactionlog->createdAt          = $data['data']['createdAt'];
                    $savingstransactionlog->planName           = $data['data']['plan']['name'];
                    $savingstransactionlog->plan_code          = $data['data']['plan']['plan_code'];
                    $savingstransactionlog->interval           = $data['data']['plan']['interval'];
                    $savingstransactionlog->authorization_code = $data['data']['authorization']['authorization_code'];
                    $savingstransactionlog->card_type          = $data['data']['authorization']['card_type'];
                    $savingstransactionlog->bank               = $data['data']['authorization']['bank'];
                    $savingstransactionlog->bin                = $data['data']['authorization']['bin'];
                    $savingstransactionlog->last4              = $data['data']['authorization']['last4'];
                    $savingstransactionlog->exp_year           = $data['data']['authorization']['exp_year'];
                    $savingstransactionlog->exp_month          = $data['data']['authorization']['exp_month'];
                    $savingstransactionlog->account_name       = $data['data']['authorization']['account_name'];
                    $savingstransactionlog->customer_code      = $data['data']['customer']['customer_code'];
                    $savingstransactionlog->email              = $data['data']['customer']['email'];
                    $savingstransactionlog->save();
                    $customersavings->next_pay_date = $data['data']['next_payment_date'];
                    $customersavings->customer_code = $data['data']['customer']['customer_code'];
                    $customersavings->save();

                }
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Subscription created for paystack Error");
        }
    }

    public function chargeSuccess($event, $data)
    {

        try {
            if ($data['data']['status'] = 'success') {
                $checkSavingstransactionlog = Savingstransactionlog::where(['reference' => $data['data']['reference'], 'customer_code' => $data['data']['customer']['customer_code']])->count();
                if ($checkSavingstransactionlog == 0) {
                    $customersavings                          = Customersavings::where(['plan_code' => $data['data']['plan']])->first();
                    $savingstransactionlog                    = new Savingstransactionlog;
                    $savingstransactionlog->uuid              = uniqid();
                    $savingstransactionlog->customersaving_id = $customersavings->id;
                    $savingstransactionlog->amount            = $data['data']['amount'] / 100;
                    $savingstransactionlog->reference         = $data['data']['reference'];
                    $savingstransactionlog->gateway_response  = $data['data']['gateway_response'];
                    $savingstransactionlog->paid_at           = $data['data']['paid_at'];
                    $savingstransactionlog->createdAt         = $data['data']['created_at'];
                    $savingstransactionlog->channel           = $data['data']['channel'];
                    $savingstransactionlog->currency          = $data['data']['currency'];
                    $savingstransactionlog->ip_address        = $data['data']['ip_address'];
                    $savingstransactionlog->metadata          = $data['data']['metadata'];
                    $savingstransactionlog->log               = json_encode($data['data']['log']);
                    $savingstransactionlog->email             = $data['data']['customer']['email'];
                    $savingstransactionlog->customer_code     = $data['data']['customer']['customer_code'];
                    $savingstransactionlog->authorization     = json_encode($data['data']['authorization']);
                    $savingstransactionlog->save();
                    if ($data['data']['gateway_response'] == 'Successful' && $data['data']['status'] == 'success') {
                        $pendingcustomersaving = Pendingcustomersaving::where(function ($query) use ($request) {
                            $query->whereNull('reference')
                                ->orWhere('reference', '!=', $data['data']['reference']);
                        })
                            ->where('status', 'pending')
                            ->where('customersaving_id', $customersavings->id)
                            ->first();
                        if ($pendingcustomersaving) {
                            $pendingcustomersaving->paid_at   = $data['data']['paid_at'];
                            $pendingcustomersaving->status    = 'complete';
                            $pendingcustomersaving->reference = $data['data']['reference'];
                            $pendingcustomersaving->save();
                            $customersavings->balance += $data['data']['amount'] / 100;
                            $customersavings->save();
                        }
                    }
                }
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Subscription transaction for paystack Error");
        }

    }

    public function subscriptionDisable($event, $data)
    {

        try {
            $savingsubscriptionlog = Savingsubscriptionlog::where('subscription_code', $data['data']['subscription_code'])->first();
            if ($savingsubscriptionlog) {
                $customersavings = Customersavings::where(['id' => $savingsubscriptionlog->customersaving_id])->first();
                if ($customersavings) {
                    $customersavings->status = $data['data']['status'];
                    $customersavings->save();
                }
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Subscription Disabled for paystack Error");
        }

    }

    public function subscriptionNotRenewed($event, $data)
    {

        try {
            $savingsubscriptionlog = Savingsubscriptionlog::where('subscription_code', $data['data']['subscription_code'])->first();
            if ($savingsubscriptionlog) {
                $customersavings = Customersavings::where(['id' => $savingsubscriptionlog->customersaving_id])->first();
                if ($customersavings) {
                    $customersavings->status = $data['data']['status'];
                    $customersavings->save();
                }
                $savingsubscriptionlog->description = $data['data']['plan']['description'];
                $savingsubscriptionlog->save();
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Subscription non_renew for paystack Error");
        }

    }

    public function invoice($event, $data)
    {

        try {
            $exist                                 = SavingsInvoice::where('transaction_reference', $data['data']['invoice_code'])->first();
            $savingsInvoice                        = ($exist) ? $exist : new SavingsInvoice;
            $savingsInvoice->status                = $data['data']['status'];
            $savingsInvoice->invoice_code          = $data['data']['invoice_code'];
            $savingsInvoice->amount                = $data['data']['amount'];
            $savingsInvoice->period_start          = $data['data']['period_start'];
            $savingsInvoice->period_end            = $data['data']['period_end'];
            $savingsInvoice->paid                  = $data['data']['paid'];
            $savingsInvoice->paid_at               = $data['data']['paid_at'];
            $savingsInvoice->description           = $data['data']['description'];
            $savingsInvoice->transaction_reference = (isset($data['data']['transaction']['reference'])) ? $data['data']['transaction']['reference'] : null;
            $savingsInvoice->save();
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Subscription create fail or success invoice for paystack Error");
        }
    }

}
