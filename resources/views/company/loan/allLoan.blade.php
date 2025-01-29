@extends('layouts.app')

@section('content')

<div class="widget-content widget-content-area br-6">
    <div class='row'>
        <div class='form-group col-md-2 col-sm-6'>
            <label for="">View</label>
            <select class='form-control' id='view-select'>
                <option value='paginate'>Paginated</option>
                <option value='all'>Show All</option>
            </select>
        </div>
        <div class='form-group col-md-2 col-sm-6'>
            <label for="">From</label>
            <input type='date' id='from' class='form-control'>
        </div>
        <div class='form-group  col-md-2 col-sm-6'>
            <label for="">To</label>
            <input type='date' id='to' class='form-control'>
        </div>

        <div class='form-group  col-md-2 col-sm-6'>
            <label for="">Status</label>
            <select id='status'  class='form-control'>
                <option value=''>Enter Status</option>
                <option value='1'>Active</option>
                <option value='3'>Pending</option>
                <option value='4'>Rejected</option>
                <option value='2'>Disbursment</option>
                <option value='10'>Completed</option>
                <option value='8'>Liquidate</option>
                <option value='7'>Writtenoff</option>
            </select>
        </div>
        @include('company.report.addon.addBranchandStaff')
        @include('company.report.addon.addLoanProduct')
        <div class='form-group  col-md-2 col-sm-6'>

            <button type='button' class='btn btn-primary' onclick='searchLoan()'>Filter</button>
        </div>
    </div>
    <div class="table-responsive mb-4 mt-4">
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
                <th>Status</th>
                <th>Pament Status</th>
                <th>Action</th>
            </thead>
            <tbody>

            </tbody>
        </table>
            <!-- Modal -->
    </div>
</div>
<!-- Modal -->
@include('company.loan.inc.loandetails')

@endsection
