<?php
namespace App\Jobs;

use App\Models\BranchWallet;
use App\Models\Investmentbooking;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvestmentCron
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
            \logInfo("Investment Cron Job is Working currently", "Cron Job Started");

            $allinvestment = Investmentbooking::where('status', 1)->get();
            $complete      = 3;
            $running       = 1;
            $return        = '';
            if ($allinvestment) {
                foreach ($allinvestment as $runninginvestment) {
                    if ($runninginvestment->plan) {
                        if ($runninginvestment->plan->duration > 0) {
                            if ($runninginvestment->duration > 0) {
                                $lastupdate = date_format($runninginvestment->updated_at, "Y-m-d");
                                if ($lastupdate < date("Y-m-d")) {
                                    $plan_interest = $runninginvestment->plan->interest_rate;
                                    $investamount  = $runninginvestment->amount;
                                    $cash_precent  = ($plan_interest / 100) * $investamount;
                                    $date_started  = $runninginvestment->start_date;
                                    $today         = date("Y-m-d");
                                    $end_date      = $runninginvestment->end_date;
                                    if ($end_date > $today) {
                                        $days_so_far = diff_btw_date($date_started, $today);
                                        $status      = $running;
                                    } else {
                                        $days_so_far = diff_btw_date($date_started, $end_date);
                                        $status      = $complete;
                                    }
                                    $duration          = $runninginvestment->duration;
                                    $expected_cash     = $investamount + $cash_precent;
                                    $amt_per_day       = $expected_cash / $duration;
                                    $amt_earned        = $days_so_far * $amt_per_day;
                                    $wallet            = $days_so_far * ($cash_precent / $duration);
                                    $investment_update = Investmentbooking::findorfail($runninginvestment->id);
                                    $balance           = $amt_earned;
                                    $investment_update->update([
                                        'wallet'           => $wallet,
                                        'balance'          => $balance,
                                        'expected_balance' => $expected_cash,
                                        'status'           => $status,
                                    ]);
                                    if ($status == $complete) {
                                        echo "invest complete";
                                        $transactionId   = generate_txnID();
                                        $userAccount     = wb_account_details::find($runninginvestment->wb_accountdetails_id);
                                        $balanceBefore   = $userAccount->user_wallet->balance;
                                        $balanceAfter    = $balanceBefore + $expected_cash;
                                        $session         = User::where('company_id', $userAccount->user->company_id)->first()->id;
                                        $amount          = $expected_cash;
                                        $ledger_cr       = null;
                                        $ledger_dr       = null;
                                        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(21, $userAccount->user->company_id);
                                        if ($ledger_Settings) {
                                            $ledger_cr = $ledger_Settings->ledger_cr;
                                            $ledger_dr = $ledger_Settings->ledger_dr;
                                        }
                                        $data = [
                                            "balance_before"         => $balanceBefore,
                                            "amount"                 => $expected_cash,
                                            "description"            => "Return of Investment ",
                                            "account_id"             => $runninginvestment->wb_accountdetails_id,
                                            "branch_id"              => $userAccount->user->branch_id,
                                            "company_id"             => $userAccount->user->company_id,
                                            "transaction_reference"  => $transactionId,
                                            "transaction_type_id"    => 21,
                                            "credit_debit_type_id"   => 1,
                                            "transaction_remarks_id" => 2, //success 2
                                            "balance_after"          => $balanceAfter,
                                            "created_date"           => date("Y-m-d"),
                                            "created_by"             => $session,
                                            "ledger_cr"              => $ledger_cr,
                                            "ledger_dr"              => $ledger_dr,
                                        ];
                                        $transaction = transactions::create($data);
                                        $investment_update->update([
                                            'wallet' => 0,
                                        ]);

                                        $BranchWallet = BranchWallet::where('branch_id', $userAccount->user->branch_id)->first();
                                        $BranchWallet->balance += $amount;
                                        $BranchWallet->save();
                                        user_wallet::where('wb_account_detail_id', $runninginvestment->wb_accountdetails_id)->update(['balance' => $balanceAfter]);
                                        $return = response(json_encode(['status' => 1, 'message' => "success"]));
                                    } else {
                                        $return = response(json_encode(['status' => 1, 'message' => "$days_so_far *$amt_per_day"]));

                                    }
                                } else {
                                    $return = response(json_encode(['status' => 1, 'message' => "Already Updated"]));

                                }
                            }
                        }
                    }
                }
            }

        } catch (Exception $e) {

            Log::error("InvestmentCron Job has error: " . $e->getMessage());
            throw $e;
        }

    }

}
