@extends('layouts.app')
<style>
    .picture{
        margin:10px;
        max-width: 200px;
        min-height: 200px;
        background-color: grey;
    }
</style>
@section('content')
<div class="container-fluid print-target">
@include('company.loan.inc.loanDetails2')
<div class='text-left'>
<p>
    We are delighted to inform you that your loan application has been approved by our
Management. In accordance with your application, we will grant you a sum of {{auth()->user()->company->currency->symbol}} {{$loanData->principal}}-
with interest rate of {{$loanData->rate}}%.</p><p>
According to our terms and conditions, it is mandatory for you to pay the first
installment of your loan amounting to {{auth()->user()->company->currency->symbol}}{{$loanData->EachRepayment}}- within {{$loanData->duration}} {{$loanData->DurationPeriod}} we Disburse the
principal amount into your bank account or your savings account.</p>
<p>
There after, you are supposed to make your {{$loanData->PeriodID->name}} repayment.
Kindly go through the enclosed literature discussing the details of our terms and
conditions and amortization rules. In case you fail to abide by these rules, We will be
authorized to take legal action.</p>

@include('company.loan.inc.repaymentTable')

<p class='m-3'>Your Signature ..........................</p>
<p class=''>Management Signature ..........................</p>
</div>
</div>
<button class='btn btn-success' id="print-button">Print Offer</button>

@endsection
