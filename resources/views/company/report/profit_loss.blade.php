
@extends('layouts.app')
@section('content')
<div class='container-fluid'>
<div class="widget-content widget-content-area br-6">
    <div class='container' style='margin:10px;'>
    <h3>Profit And Loss</h3>
<form>
<div class='row'>
    <div class='col-md-6 col-sm-6 form-group'>
        <label>Search Form</label>
        <input type='date' id='from' class='form-control'>
    </div>
    <div class='col-md-6 col-sm-6 form-group'>
        <label>Search To</label>
        <input type='date' id='to' class='form-control'>
    </div>
    </div>
    <div class=' form-group'>
        <button type='button'  class='btn btn-info' id='click-button' onclick="FliterOfficerReport()">Search</button>
        <button type='reset' class='btn btn-warning'  >Reset</button>
    </div>
</form>
    <div class="table-responsive mb-4 mt-4">
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Branch</th>
                    <th>Loan Officer</th>
                    <th>No. of Clients</th>
                    <th>Client with Loan</th>
                    <th>Principal</th>
                    <th>Interest%</th>
                    <th>Total Outstanding</th>
                    <th>Disbursed</th>
                    <th>Disbursed Amt</th>
                    <th>Write OFF</th>
                    <th>Write Off Amt</th>
                    <th>Total Principal</th>
                    <th>Total Interest</th>
                    <th>Saving Balance</th>
                </tr>
            </thead>
            <tbody id='LoanRecTable'>
            </tbody>
        </table>
    </div>
</div>
</div>

@endsection
