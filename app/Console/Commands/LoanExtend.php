<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AddedLoanLog;
use App\Models\loan_period;
use App\Models\loan;
ini_set('memory_limit', '500000M');

class LoanExtend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LoanExtend:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extend Loan if missed and finished';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $loanData = loan::where('loan_status_id', 1)
        ->where(function ($query) {
            $query->where('end_date', '<', now())
                  ->orWhereNull('end_date');
        })
        ->pluck('id');
        if($loanData){

            foreach($loanData  as $loanDataCheck){
                $this->extendloan($loanDataCheck);
            }
        }
        return 0;
    }
    public function extendloan($id){
        $loan = loan::where('id',$id)->first();
        try{
        if($loan){
        $product = $loan->LoanProduct;
        $extend = $product->keep_as_past_onMissed_onExtend;
        if($extend != 1 ){
            return false;
        }
        $graceDate = $loan->grace_at;
        $duration = $loan->duration;
        $rate = $loan->rate;
        $principal = $loan->principal;
        $loanBalance = $loan->loanBalance;
        $DurationPeriod = $loan->DurationPeriod;
        $period =  loan_period::where('id',$loan->period_id)->first();
        $loanRepaymentPeriod = convertLY($period->name);
        if($loan->end_date == null){
            $alltime = calculatePeriodByPeriod($period->name,$DurationPeriod) * $duration;
            $newdate = date("Y-m-d", strtotime($graceDate . "+ $alltime ".$loanRepaymentPeriod['key']));
            $loan->end_date = $newdate;
            $loan->save();
        }
        if((date("Y-m-d") > $loan->end_date) && ($loanBalance > 0)){
            $AddedLoanLog =new AddedLoanLog;
            $interest = $principal * ($rate/100 ) ;
            $dayMissed = dateDiffInDays($loan->end_date, date("Y-m-d"));
            $missedPayable = ($interest/30) * $dayMissed;
            $loan->loanBalance +=$missedPayable;
            $loan->InterestLeft +=$missedPayable;
            $loan->end_date = date("Y-m-d");
            $eachrepaymentAddition = ($interest/$loan->duration);
            $loan->save();
            $AddedLoanLog->end_date = $loan->end_date;
            $AddedLoanLog->loan_id = $loan->id;
            $AddedLoanLog->amount = $missedPayable;
            $AddedLoanLog->balance_before = $loan->loanBalance-$missedPayable;
            $AddedLoanLog->balance_after = $loan->loanBalance;
            $AddedLoanLog->save();
            \logInfo("Extended Loan complete",  "Loan ID:  $loan->id Extended");

        }
    }
}catch(Exception $e){
    \logInfo("Extended Loan Error: Line ". $e->getLine(),  $e->getMessage());

}
return 0;

    }
}
