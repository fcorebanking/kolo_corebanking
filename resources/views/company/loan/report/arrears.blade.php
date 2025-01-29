
@extends('layouts.app')
@section('content')
<div class='container-fluid'>

<div class="widget-content widget-content-area br-6">
    <div class='container-fluid' style='margin:10px;'>

    <h3>Arrear Report Search through Missed Loan</h3>
<form>
<div class='row'>
    <div class='col-md-6 col-sm-6 form-group'>
        <label>Loan Product</label>

        <select name='loan_product_id' class='form-control' id='loan_product_id'>
        <option value=''>Select by Product</option>
        @if ($product)
        @foreach ($product as $rec)
            <option value='{{Crypt::encryptString($rec->id)}}"'>{{$rec->product_name}}</option>

        @endforeach

        @endif
    </select></div>
    <div class='col-md-6 col-sm-6 form-group'>
        <label>Search By</label>
        <select name='searchType' class='form-control' id='searchType' onchange="swapSearchType()">
        <option  value='day'>Search by Day</option>
        <option value='date'>Search by Date</option>
    </select></div>
    <div class='col-md-6 col-sm-6 form-group startfrom' >
        <label>Start Form</label>
        <input type='date' id='startfrom' class='form-control'>
    </div>
        <div class='col-md-6 col-sm-6 form-group'>
        <label>Search Form</label>
        <input type='number' id='from' class='form-control'>
    </div>
    <div class='col-md-6 col-sm-6 form-group'>
        <label>Search To</label>
        <input type='number' id='to' class='form-control'>
    </div>
    @if(cando('select-cutomer-branch|root'))
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label for="">Branch</label>
                            <select name="branch" id='branch' onchange="renderStaff()" class="form-control">
                                <option value="">Select Branch</option>
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
                    <div class="col-md-6 col-sm-6">
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
    </div>
    <div class=' form-group'>

        <button type='button'  class='btn btn-info' id='click-button' onclick="FliterArrear()">Search</button>
        <button type='reset' class='btn btn-warning'  >Reset</button>
    </div>
</form>
<button id="export-btn" class='btn btn-success'>Export to Excel</button>

    <div class="table-responsive mb-4 mt-4">

        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Release Date</th>
                    <th>Loan Officer</th>
                    <th>Borrower</th>
                    <th>Phone</th>
                    <th>LoanID</th>
                    <th>Days in Arrears </th>
                    <th>Overdue Principal</th>
                    <th>Overdue Interest</th>
                    <th>Overdue Fees</th>
                    <th>Overdue Penalty</th>
                    <th title="Loans that are extended due to delay payment">Extension</th>
                    <th>Overdue Balance</th>
                    <th>Total Principal Outstanding</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id='LoanRecTable'>
            </tbody>
        </table>
    </div>
</div>

</div>
<script>

document.getElementById("export-btn").addEventListener("click", function(){
    exportTableToExcel("html5-extension", "table_data");
});

function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

    // Specify file name
    filename = filename?filename+'.xls':'excel_data.xls';

    // Create download link element
    downloadLink = document.createElement("a");

    document.body.appendChild(downloadLink);

    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;

        // Setting the file name
        downloadLink.download = filename;

        //triggering the function
        downloadLink.click();
    }
}

</script>
@endsection
