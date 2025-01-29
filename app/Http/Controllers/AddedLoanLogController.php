<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddedLoanLogRequest;
use App\Http\Requests\UpdateAddedLoanLogRequest;
use App\Models\AddedLoanLog;
use App\Models\loan_period;
use App\Models\loan;

class AddedLoanLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    public function extendLoan(){
        $loan = loan::where('id',23)->first();
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
            // while(date("Y-m-d") > date("Y-m-d", strtotime($loan->end_date . "+ $extend_Duration ".$loanRepaymentPeriod['key']))){
            //     if($loan->keep_as_past_onMissed_onExtend == 3 && $loan->ExtendDuration > 0){
            //         $extend_Duration += $loan->ExtendDuration;
            //     }else{
            //         $extend_Duration++;
            //     }
            // }
            $interest = $principal * ($rate/100 ) ;
            $dayMissed = dateDiffInDays($loan->end_date, date("Y-m-d"));
            $missedPayable = ($interest/30) * $dayMissed;
            $loan->loanBalance +=$missedPayable;
            $loan->InterestLeft +=$missedPayable;
            // $loan->end_date = date("Y-m-d", strtotime($loan->end_date . "+ $extend_Duration ".$loanRepaymentPeriod['key']));
            $loan->end_date = date("Y-m-d");
            // $loan->duration += $extend_Duration;
            $eachrepaymentAddition = ($interest/$loan->duration);
            $loan->EachRepayment += $missedPayable;
            $loan->save();
            $AddedLoanLog->end_date = $loan->end_date;
            $AddedLoanLog->loan_id = $loan->id;
            $AddedLoanLog->amount = $missedPayable;
            $AddedLoanLog->balance_before = $loan->loanBalance-$missedPayable;
            $AddedLoanLog->balance_after = $loan->loanBalance;
            $AddedLoanLog->save();
            echo "$interest - $dayMissed == $missedPayable ";
        }else{
            echo "nothing to run ($loan->end_date) && ($loanBalance > 0)";
        }
        echo "done";
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAddedLoanLogRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAddedLoanLogRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AddedLoanLog  $addedLoanLog
     * @return \Illuminate\Http\Response
     */
    public function show(AddedLoanLog $addedLoanLog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AddedLoanLog  $addedLoanLog
     * @return \Illuminate\Http\Response
     */
    public function edit(AddedLoanLog $addedLoanLog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAddedLoanLogRequest  $request
     * @param  \App\Models\AddedLoanLog  $addedLoanLog
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAddedLoanLogRequest $request, AddedLoanLog $addedLoanLog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AddedLoanLog  $addedLoanLog
     * @return \Illuminate\Http\Response
     */
    public function destroy(AddedLoanLog $addedLoanLog)
    {
        //
    }
}
