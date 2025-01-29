
@extends('layouts.app')
@section('content')
<div class='container-fluid'>

<div class="widget-content widget-content-area br-6">
    <div class='container-fluid' style='margin:10px;'>

    <h3>Officer Performance</h3>
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
                    <th>Total Loan</th>
                    <th>Active Principal</th>
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

</div>
<!-- Modal -->
<div class="modal fade" id="exampleModalOfficerPerformance" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Report</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class='table-responsive'>
                <table class='table client'>
                    <thead>
                        <th>SN</th>
                        <th>Name</th>
                        <th>Wallet</th>
                        <th>Balance</th>
                    </thead>
                    <tbody id='client-table'>

                    </tbody>
                </table>
                <table class='table loan-client'>
                    <thead>
                        <th>SN</th>
                        <th>Name</th>
                        <th>Account No</th>
                        <th>Phone</th>
                        <th>Principal</th>
                        <th>Principal Left</th>
                        <th>Interest Left</th>
                        <th>LoanBalance</th>
                    </thead>
                    <tbody id='loan-client'>

                    </tbody>
                </table>
                <table class='table loans'>
                    <thead>
                        <th>SN</th>
                        <th>Name</th>
                        <th>Account No</th>
                        <th>Phone</th>
                        <th>Principal</th>
                        <th>Principal Left</th>
                        <th>Interest Left</th>
                        <th>LoanBalance</th>
                    </thead>
                    <tbody id='loans'>

                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Save changes</button>
        </div>
      </div>
    </div>
  </div>

@endsection
