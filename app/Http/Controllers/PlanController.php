<?php
namespace App\Http\Controllers;

use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Models\Company;
use App\Models\company_wallet;
use App\Models\plans;
use App\Models\sub;
use App\Models\sublog;
use App\Models\transactions;
use Illuminate\Http\Request;

class PlanController extends Controller
{

    private $flutterwaveController;
    public function __construct()
    {
        $this->middleware("custom_permission:manage-subscription|root");
        $this->flutterwaveController = new FlutterwaveController();
    }
    public function plan()
    {
        $data = [
            'category_name'    => 'components',
            'page_name'        => 'pricing_table',
            'page_menu'        => 'subscrption',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'plans'            => plans::all(),
            'paymentLink'      => url("/transaction/payment-link/" . auth()->user()->company->wallet->refaccount),

        ];
        // $pageName = 'pricing_table';
        return view('plan.main')->with($data);
    }
    public function subscribe(Request $request)
    {

        logInfo($request->all(), "Subscription Request");
        $auth = auth()->user();

        if ($request->qrt > 0) {
            $duration  = $request->qrt;
            $fdate     = date("Y-m-d");
            $daysToAdd = $duration * 31;

            $plan = $this->getPlan($request);
            //max_staff max_branch max_customers
            $this->getMaxCustomers($auth, $plan);

            $companybalance = $auth->company->wallet->balance;
            $company_name   = $auth->company->short_name;
            $amt_to_pay     = $this->getAmountPaid($plan, $auth, $duration, $companybalance, $company_name);
            \logInfo($amt_to_pay, "Plan amount converted");

            $sameplan     = 0;
            $myoldplanend = '';
            $sub          = $this->checkSub($auth, $plan, $sameplan, $myoldplanend, $fdate);

            if (isset($sub['sameplan'])) {
                $sameplan = $sub['sameplan'];
            }

            if (isset($sub['myoldplanend'])) {
                $myoldplanend = $sub['myoldplanend'];
            }

            if (isset($sub['hassub'])) {
                $hassub = $sub['hassub'];
            } else {
                $hassub = 0;
            }

            if (isset($sub['sub'])) {
                $sub = $sub['sub'];
            }

            $moneyleft = 0;

            $this->paySubscription($amt_to_pay, $companybalance, $sameplan, $auth, $daysToAdd, $myoldplanend, $fdate, $moneyleft, $hassub, $sub, $duration, $plan, $company_name);

            return redirect()->back()->with('fail', "An Error Occured!");
        }
    }
    public function subinvoicereview($company_name, $id)
    {
        $data = [
            'category_name'    => 'apps',
            'page_name'        => 'form',
            'page_menu'        => 'sub_invoice',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 1,
            'sub'              => sub::where('company_id', auth()->user()->company_id)->first(),
            'success'          => "Subscription Successful!",
        ];
        // $pageName = 'invoice';

        return view('company.invoice.subscription')->with($data);
    }
    public function subloglist($company_name)
    {
        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html5',
            'page_menu'        => 'sub_invoice_list',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 1,
            'listtxn'          => sublog::where(['company_id' => auth()->user()->company_id])->get(),

        ];
        // $pageName = 'invoice';

        return view('company.invoice.listinvoice')->with($data);
    }
    public function viewsubpage()
    {
        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html',
            'page_menu'        => 'sub_plan',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,

        ];

        return view('company.statement.subscription_history')->with($data);
    }

    public function transaction(Request $request)
    {

        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 25);
        $order       = $request->query('order', [1, 'asc']);
        $filter      = $search['value'];
        $sortColumns = [
            0  => 'quantity',
            1  => 'start_date',
            2  => 'end_date',
            3  => 'end_date',
            4  => 'start_date',
            5  => 'end_date',
            6  => 'transaction_id',
            7  => 'transaction_id',
            8  => 'transaction_id',
            9  => 'transaction_id',
            10 => 'transaction_id',
            11 => 'transaction_id',
            12 => 'transaction_id',
        ];
        $mycompany = auth()->user()->company_id;
        $query     = sublog::whereHas('TXNlog', function ($q) {
            $q->where(['transaction_type_id' => '18']);
        })->where('company_id', $mycompany);
        if (! empty($filter)) {
            $query->orWhere('end_date', 'like', '%' . $filter . '%');
            $query->orWhere('start_date', 'like', '%' . $filter . '%');
        }
        $recordsTotal   = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir'])
            ->take($length)
            ->skip($start);
        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];

        $info = $query->get();
        $loop = 0;
        foreach ($info as $infoData) {
            $loop++;
            $json['data'][] = [
                $loop,
                $infoData->plan->plan_name,
                $infoData->start_date,
                $infoData->end_date,
                $infoData->TXNlog->remark->transaction_remark,
                $infoData->TXNlog->balance_before,
                $infoData->TXNlog->amount,
                $infoData->TXNlog->balance_after,
                $infoData->TXNlog->transaction_reference,
                $infoData->TXNlog->description,
                $infoData->TXNlog->creditDebitType->type,
                ($infoData->TXNlog->author->first_name != null) ? $infoData->TXNlog->author->first_name . ' ' . $infoData->TXNlog->author->last_name : 'WBA Default',
                $infoData->TXNlog->created_at,

            ];
        }

        return $json;
    }

    public function getDailyRates($sub)
    {
        return plans::where('id', $sub->plan_id)->first()->daily_rates;
    }

    private function getPlan(Request $request)
    {

        $plan = plans::find($request->id);
        logInfo(json_encode($plan), "Company Plan");
        return $plan;
    }

    private function getMaxCustomers($auth, $plan)
    {
        $totalStaff    = count($auth->company->allStaff);
        $totalBranch   = count($auth->company->branch);
        $totalCustomer = count($auth->company->allCustomer);
        if ($totalStaff > $plan->max_staff) {
            return redirect()->back()->with('fail', "The maximum staff limit of the selected plan does not match your current subscription.");
        }
        if ($totalBranch > $plan->max_branch) {
            return redirect()->back()->with('fail', "The maximum branch limit of the selected plan does not match your current subscription. you have " . $totalBranch . " branch and you are allowed " . $plan->max_branch);
        }
        if ($totalCustomer >= $plan->max_customers) {
            return redirect()->back()->with('fail', "The maximum customer limit of the selected plan does not match your current subscription.");
        }
    }

    //  getAmountPaid($plan, $auth, $duration, $companybalance, $company_name)
    private function getAmountPaid($plan, $auth, $duration, $companybalance, $company_name)
    {

        $plan_unit     = $plan->daily_rates;
        $moneyleft     = 0;
        $plan_discount = $plan->discount_percent;
        $monthlyamount = $plan_unit * 31;

        if (($duration >= 12) && ($plan_discount > 0)) {
            $amt_to_pay = (($plan_discount / 100) * ($monthlyamount * $duration));
        } else {
            $amt_to_pay = $monthlyamount * $duration;
        }

        logInfo(json_encode($amt_to_pay), "Company Subcription amount to pay before exchange");
        //  $amt_to_pay = $this->getExchangeRater($amt_to_pay, $company);

        $currencyExchange = $this->getExchangeRater($amt_to_pay, $auth);
        $amt_to_pay       = $currencyExchange['data']['final_amount'];
        logInfo(json_encode($amt_to_pay), "Company Subcription amount to pay ater exchange");
        return $amt_to_pay;
    }

    private function getExchangeRater($amt_to_pay, $auth)
    {
        $currencyExchange = $this->flutterwaveController->getExchangeRater($amt_to_pay, $auth->company->currency->code, "USD");
        if (! $currencyExchange['status']) {
            return redirect()->back()->with('fail', $currencyExchange['message']);
        }
        return $currencyExchange;
    }

    //checkSub($auth, $plan, $sameplan, $myoldplanend, $fdate)
    public function checkSub($auth, $plan, $sameplan, $myoldplanend, $fdate)
    {
        $sub    = sub::where(['company_id' => $auth->company_id])->first();
        $hassub = 0;

        if ($sub) {
            $hassub       = 1;
            $myoldplanend = $sub->end_date;
            if ($sub->end_date > $fdate) {
                if ($plan->id > $sub->plan_id) {
                    if (isset($sub->TXNlog->amount)) {
                        \logInfo(json_encode($sub), "TXNLog sub");
                        \logInfo(json_encode($sub->TXNlog->amount), "TXNLog Amount");
                        $myoldplanqrt = $sub->quantity;
                        \logInfo(json_encode($myoldplanqrt), "TXNLog myoldplanqrt");
                        // echo "myoldplanqrt: $myoldplanqrt <br>";
                        $txnamt = $sub->TXNlog->amount;
                        // echo "txn : $txnamt <br>";
                        $oldunit    = $txnamt / $myoldplanqrt;
                        $dailyRates = $sub->plan->daily_rates ?? $this->getDailyRates($sub);
                        // echo "oldunit : $oldunit <br>";
                        if ($myoldplanend > $fdate) {
                            $daysleft = diff_btw_date($fdate, $myoldplanend);
                            \logInfo(json_encode($daysleft), "TXNLog daysleft");
                            \logInfo(json_encode($dailyRates), "TXNLog dailyrates");
                            // echo "$fdate, $myoldplanend <br>";
                            $currencyExchange = $this->getExchangeRater($amt_to_pay, $auth);
                            $oldunit          = $currencyExchange['data']['final_amount'];
                            $moneyleft        = $oldunit * $daysleft;
                            // echo "$oldunit * $daysleft <br>";
                        }
                    } else {
                        $sameplan = 1;
                    }
                } else if ($plan->id == $sub->plan_id) {
                    $sameplan = 1;
                } else {
                    return redirect()->back()->with('fail', "You cannot downgrade while on a higher plan!");
                }
            }
        }

        $checkSub['sameplan']     = $sameplan;
        $checkSub['myoldplanend'] = $myoldplanend;
        $checkSub['hassub']       = $hassub;
        $checkSub['sub']          = $sub;
        return $checkSub;
    }

    private function createTransaction($auth, $finalpayment, $transactionId)
    {
        return transactions::create([
            "balance_before"         => $auth->company->wallet->balance,
            "amount"                 => $finalpayment,
            'company_id'             => $auth->company_id,
            "description"            => "Finova Subscription",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 18,
            "company_balance_before" => $auth->company->wallet->balance,
            "company_amount"         => $finalpayment,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $auth->id,
        ]);
    }

    //paySubscription($amt_to_pay, $companybalance, $sameplan, $auth, $daysToAdd, $myoldplanend, $fdate, $moneyleft)
    public function paySubscription($amt_to_pay, $companybalance, $sameplan, $auth, $daysToAdd, $myoldplanend, $fdate, $moneyleft, $hassub, $sub, $duration, $plan, $company_name)
    {
        $finalpayment = $amt_to_pay;
        if ($finalpayment < 0) {
            $finalpayment = 0;
        }

        \logInfo(json_encode($sameplan), "pay subscription sameplan");
        if ($companybalance >= $finalpayment) {
            $transactionId = generate_txnID();
            if ($sameplan == 1) {
                $endpayment = add_day_to_date($daysToAdd, $myoldplanend);
            } else {
                $endpayment = add_day_to_date($daysToAdd, $fdate);
            }

            $transaction = $this->createTransaction($auth, $finalpayment, $transactionId);

            $companywallet = company_wallet::where('company_id', $auth->company_id)->first();
            //echo "before addition $companywallet->balance ";
            if ($moneyleft > 0 && $sameplan != 1) {
                //$companywallet->balance     = $companywallet->balance + $moneyleft;
                //$transaction['description'] = $transaction['description'] . " and refund of $moneyleft from previous plan";
                $transaction['description'] = $transaction['description'];
                // echo "after addition $companywallet->balance $moneyleft <br>";

            }
            //echo "$moneyleft > 0 && $sameplan";
            $companywallet->subscription_balance = $companywallet->subscription_balance - $finalpayment;
            $balanceBefore                       = $companywallet->sms;
            $companywallet->sms                  += 200;
            // echo "after sub $companywallet->balance ";
            //die(" <br> plan compare $plan->id == $sub->plan_id");

            if ($companywallet->save()) {
                if ($hassub == 1) {
                    $sub->update([
                        'transaction_id' => $transaction->id,
                        'plan_id'        => $plan->id,
                        'quantity'       => $duration,
                        'start_date'     => $fdate,
                        'end_date'       => $endpayment,
                        'status'         => 1,
                    ]);
                } else {
                    sub::create([
                        'company_id'     => $auth->company_id,
                        'transaction_id' => $transaction->id,
                        'plan_id'        => $plan->id,
                        'quantity'       => $duration,
                        'start_date'     => $fdate,
                        'end_date'       => $endpayment,
                        'status'         => 1,
                    ]);
                }
                sublog::create([
                    'company_id'     => $auth->company_id,
                    'transaction_id' => $transaction->id,
                    'plan_id'        => $plan->id,
                    'quantity'       => $duration,
                    'start_date'     => $fdate,
                    'end_date'       => $endpayment,

                ]);

                $transaction->update([
                    "transaction_remarks_id" => 2,
                    "balance_after"          => $companywallet->subscription_balance,
                    "company_balance_after"  => $companywallet->subscription_balance,
                    "modified_by"            => $auth->id,
                ]);

                $bonusTransactionId = \generate_txnID();

                $transaction = transactions::create([
                    "balance_before"         => $balanceBefore,
                    "amount"                 => 200,
                    "balance_after"          => $companywallet->sms,
                    'company_id'             => $auth->company_id,
                    "description"            => "Subscription Bonus SMS unit",
                    "transaction_reference"  => $bonusTransactionId,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 67,
                    "company_balance_before" => $balanceBefore,
                    "company_amount"         => 200,
                    "company_balance_after"  => $companywallet->sms,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $auth->id,
                ]);

                return redirect()->route('company.subinvoicereview', [$company_name, $transactionId])->with('success', 'Subscription Successful!!!');
            }
        } else {
            return redirect()->back()->with('fail', "Insuffient Balance!");
        }
    }

}
