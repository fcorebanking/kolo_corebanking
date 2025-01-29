@extends('layouts.app')
@section('content')
<div class="widget-content widget-content-area br-6">
    @isset($groupData)
        <h3>{{$groupData->name}} Group</h3>
    @endisset
    <div class="table-responsive mb-4 mt-4">
        <button class='btn btn-info' onclick='showTotalPrincipalLeft()'>Click to view Total Principal Left</button>
        <span id='infobait'></span>
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
            <th>sn</th>
            <th>Name</th>
            <th>A/C Number</th>
            <th>Loan Ref</th>
            <th>Rate</th>
            <th>Principal</th>
            <th>Loan Balance</th>
            <th>StartDate</th>
            <th>GraceDate</th>
            <th>Maturity</th>
            <th>Principal Left</th>
            <th>Status</th>
            <th>Pament Status</th>
            <th>Action</th>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<!-- Modal -->
@include('company.loan.inc.loandetails')
@endsection
