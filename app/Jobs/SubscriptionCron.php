<?php
namespace App\Jobs;

use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Models\Company;
use App\Models\company_wallet;
use App\Models\plans;
use App\Models\sub;
use App\Models\sublog;
use App\Models\transactions;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubscriptionCron
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {

            $getCompanySubcriptions = $this->getCompanySubcriptions();
            /*  foreach ($getCompanySubcriptions as $subCompany) {
                $plan = $this->getPlan($subCompany->plan_id);
                if ($plan != null) {
                    $duration   = $plan->duration;
                    $company    = $this->getCompany($subCompany->company_id);
                    $amt_to_pay = $this->getAmountPaid($plan, $company, $duration);

                    $companybalance = $company->wallet->balance;
                    $company_name   = $company->short_name;
                    if ($companybalance >= $amt_to_pay) {
                        $this->subscribe($subCompany, $company, $amt_to_pay, $plan, $duration);
                    }

                }
            }*/

        } catch (Exception $e) {

            Log::error("SubscriptionCron Job has error: " . $e->getMessage());
            throw $e;
        }

    }
    public function getCompanySubcriptions()
    {

        $expiredSubs = sub::where('end_date', '<', now())->get();
        logInfo(json_encode($expiredSubs), "Company expired Subcription");
        return $expiredSubs;
    }

    public function getPlan($plan_id)
    {

        $plan = plans::find($plan_id);
        logInfo(json_encode($plan), "Company expired Subcription");
        return $plan;
    }

    public function getAmountPaid($plan, $company, $duration)
    {
        $plan_unit = $plan->daily_rates;

        $monthlyamount = $plan_unit * 31;
        $amt_to_pay    = $monthlyamount * $duration;
        logInfo(json_encode($amt_to_pay), "Company Subcription amount to pay before exchange");
        $amt_to_pay = $this->getExchangeRater($amt_to_pay, $company);
        return $amt_to_pay;
    }

    public function getExchangeRater($amt_to_pay, $company)
    {
        $flutterwaveController = new FlutterwaveController();
        $code                  = $company->currency->code;
        $currencyExchange      = $flutterwaveController->getExchangeRater($amt_to_pay, $code, "USD");
        if (! $currencyExchange['status']) {
            \logInfo($currencyExchange['message'], "Plan amount converted fail");
            return;
        }
        $amt_to_pay = $currencyExchange['data']['final_amount'];
        \logInfo($amt_to_pay, "Plan amount converted");
    }

    public function getCompany($company_id)
    {
        $company = Company::where('id', $company_id)->first();
        return $company;
    }

    public function subscribe($subCompany, $company, $amt_to_pay, $plan, $duration)
    {
        $fdate         = date("Y-m-d");
        $transactionId = generate_txnID();
        $daysToAdd     = 31;
        $myoldplanend  = $subCompany->end_date;
        $endpayment    = add_day_to_date($daysToAdd, $myoldplanend);
        $transaction   = $this->createTransaction($company, $amt_to_pay, $transactionId);
        if ($transaction != null) {
            $this->paySubscription($company, $amt_to_pay, $transaction, $subCompany, $plan, $duration, $fdate, $endpayment);
        }

    }

    public function createTransaction($company, $amt_to_pay, $transactionId)
    {
        return transactions::create([
            "balance_before"         => $company->wallet->balance,
            "amount"                 => $amt_to_pay,
            'company_id'             => $company->id,
            "description"            => "Finova Subsciption",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 18,
            "company_balance_before" => $company->wallet->balance,
            "company_amount"         => $amt_to_pay,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $company->id,
        ]);
    }

    public function paySubscription($company, $amt_to_pay, $transaction, $subCompany, $plan, $duration, $fdate, $endpayment)
    {
        $companywallet          = company_wallet::where('company_id', $company->id)->first();
        $companywallet->balance = $companywallet->balance - $amt_to_pay;
        $balanceBefore          = $companywallet->sms;
        $companywallet->sms += 200;
        $subCompany->update([
            'transaction_id' => $transaction->id,
            'plan_id'        => $plan->id,
            'quantity'       => $duration,
            'start_date'     => $fdate,
            'end_date'       => $endpayment,
            'status'         => 1,
        ]);

        sublog::create([
            'company_id'     => $company->id,
            'transaction_id' => $transaction->id,
            'plan_id'        => $plan->id,
            'quantity'       => $duration,
            'start_date'     => $fdate,
            'end_date'       => $endpayment,

        ]);

        $transaction = $transaction->update([
            "transaction_remarks_id" => 2,
            "balance_after"          => $companywallet->balance,
            "company_balance_after"  => $companywallet->balance,
            "modified_by"            => $company->id,
        ]);

        $bonusTransactionId = \generate_txnID();

        return transactions::create([
            "balance_before"         => $balanceBefore,
            "amount"                 => 200,
            "balance_after"          => $companywallet->sms,
            'company_id'             => $company->id,
            "description"            => "Subscription Bonus SMS unit",
            "transaction_reference"  => $bonusTransactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 67,
            "company_balance_before" => $balanceBefore,
            "company_amount"         => 200,
            "company_balance_after"  => $companywallet->sms,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $company->id,
        ]);
    }

}
