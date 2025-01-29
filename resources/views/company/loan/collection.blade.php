@extends('layouts.app')

@section('content')
<div class='container-fluid' style='margin:10px;'>
    <h2>Collection Sheet</h2>
    <div class='row'>
        <div  class='form-group col-md-3 col-sm-6'>
            <label for="">From</label>
            <input type='date' id='duefrom' class='form-control'>
        </div>
        <div  class='form-group  col-md-3 col-sm-6'>
            <label for="">To</label>
            <input type='date' id='dueto' class='form-control'>
        </div>
        @if(cando('select-cutomer-branch|root'))
        <div class="col-md-2 col-sm-6">
            <div class="form-group">
                <label for="">Branch</label>
                <select name="branch" id='branch' onchange="renderStaff()" class="form-control">
                    <option value="">Select All Branch</option>
                    @if(count($branch))
                    @foreach($branch as $branchData)
                        <option value="{{Crypt::encryptString($branchData->id)}}">
                        {{$branchData->branch_name}}</option>
                    @endforeach
                    @endif
                </select>
                <span id="branch_error" class="text-danger font-weight-bold"></span>
            </div>
        </div>
        @else
        <input type='hidden' value='{{Crypt::encryptString(auth()->user()->branch_id)}}'
            name="branch" id='branch'>
        @endif

        @if(cando('select-cutomer-branch|root'))
        <div class="col-md-2 col-sm-6">
            <div class="form-group">
                <label for="">Account Officer</label>
                <select name="officer" id='officer' class="form-control">

                </select>
            </div>
            <script>
                function renderStaff() {
                    $branch = $('#branch').val();
                    $.ajax({
                        url: '/company/{{auth()->user()->company->short_name}}/branch/branch_staff/' + $branch,
                        type: 'GET',
                        dataType: 'json',
                        success: function (data) {
                            $('select[name="officer"]').empty();
                            $('select[name="officer"]').append('<option value="">All</option>')
                            data.forEach(function (staff) {
                                $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
                            });
                        }
                    });
                }
            </script>
        </div>
        @else
        <input type='hidden' value='{{auth()->user()->uuid}}' name="officer" id='officer'>
        @endif
        <div  class='form-group  col-md-2 col-sm-6'>
            <button type='button' class='btn btn-primary' id='click-button' onclick='filterLoanDate()'>Search</button>
        </div>
    </div>

    <div class='table-responsive'>
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
                <tr>

                    <th>#</th>
                    <th>Client ID</th>
                    <th>Client Names</th>
                    <th>Collection Check</th>
                    <th>Saving Balance</th>
                    <th>Amount of Loan</th>
                    <th>Cost of Service</th>
                    <th>Principal + Gross</th>
                    <th>Date Repay Start</th>
                    <th>Amount Paid so Far</th>
                    <th>Expected Repay</th>
                    <th>Overdue Amount</th>
                    <th>Actual Repay</th>
                    <th>Outstanding Loan Balance</th>
                    <th>Loan Officer</th>
                </tr>
            </thead>
            <tbody id='dueLoanRecTable'>
            </tbody>
        </table>
    </div>
</div>
@endsection
