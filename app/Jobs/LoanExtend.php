<?php
namespace App\Jobs;

use App\Models\AddedLoanLog;
use App\Models\loan;
use App\Models\loan_period;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoanExtend
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
    /* public function handle()
    {
        try {
            $loanData = loan::where('loan_status_id', 1)
                ->where(function ($query) {
                    $query->where('end_date', '<', now())
                        ->orWhereNull('end_date');
                })
                ->pluck('id');
            if ($loanData) {

                foreach ($loanData as $loanDataCheck) {
                    $this->extendloan($loanDataCheck);
                }
            }
        } catch (Exception $e) {

            Log::error("LoanExtend Job has error: " . $e->getMessage());
            throw $e;
        }

    }*/

    public function handle()
    {
        try {
            foreach ($this->loanIdStream() as $loanId) {
                $this->extendloan($loanId);
            }

        } catch (\Exception $e) {
            \Log::error("LoanExtend Job has error: " . $e->getMessage());
            throw $e;
        }
    }

/**
 * Stream loan IDs using a generator (no large memory usage)
 */
    private function loanIdStream(): \Generator
    {
        $query = loan::where('loan_status_id', 1)
            ->where(function ($query) {
                $query->where('end_date', '<', now())
                    ->orWhereNull('end_date');
            })
            ->select('id'); // only fetch what we need

        foreach ($query->cursor() as $loan) {
            yield $loan->id;
        }
    }

    public function extendloan($id)
    {
        $loan = loan::where('id', $id)->first();
        try {
            if ($loan) {
                $product = $loan->LoanProduct;
                $extend  = $product->keep_as_past_onMissed_onExtend;
                if ($extend != 1) {
                    return false;
                }
                $graceDate           = $loan->grace_at;
                $duration            = $loan->duration;
                $rate                = $loan->rate;
                $principal           = $loan->principal;
                $loanBalance         = $loan->loanBalance;
                $DurationPeriod      = $loan->DurationPeriod;
                $period              = loan_period::where('id', $loan->period_id)->first();
                $loanRepaymentPeriod = convertLY($period->name);
                if ($loan->end_date == null) {
                    $alltime        = calculatePeriodByPeriod($period->name, $DurationPeriod) * $duration;
                    $newdate        = date("Y-m-d", strtotime($graceDate . "+ $alltime " . $loanRepaymentPeriod['key']));
                    $loan->end_date = $newdate;
                    $loan->save();
                }
                if ((date("Y-m-d") > $loan->end_date) && ($loanBalance > 0)) {
                    $AddedLoanLog  = new AddedLoanLog;
                    $interest      = $principal * ($rate / 100);
                    $dayMissed     = dateDiffInDays($loan->end_date, date("Y-m-d"));
                    $missedPayable = ($interest / 30) * $dayMissed;
                    $loan->loanBalance += $missedPayable;
                    $loan->InterestLeft += $missedPayable;
                    $loan->end_date        = date("Y-m-d");
                    $eachrepaymentAddition = ($interest / $loan->duration);
                    $loan->save();
                    $AddedLoanLog->end_date       = $loan->end_date;
                    $AddedLoanLog->loan_id        = $loan->id;
                    $AddedLoanLog->amount         = $missedPayable;
                    $AddedLoanLog->balance_before = $loan->loanBalance - $missedPayable;
                    $AddedLoanLog->balance_after  = $loan->loanBalance;
                    $AddedLoanLog->save();
                    \logInfo("Extended Loan complete", "Loan ID:  $loan->id Extended");

                }
            }
        } catch (Exception $e) {
            \logInfo("Extended Loan Error: Line " . $e->getLine(), $e->getMessage());

        }
    }
}
