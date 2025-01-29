@extends('layouts.app')

@section('content')
<div class="widget-content widget-content-area br-6">
    <div class="card mb-4 mt-4">
        @if(cando('loan-delete-repayment|root'))
        <button type='button' class='btn btn-danger'  onclick='showReversalModule()'>Reverse</button>
        <legend style='padding:10px;' class='groupdate'>
            <div class='alert alert-warning'>Note by selecting the From and to date all; Repayment within those date will be reversed</div>
            <div class='row'>

            <div class='col-sm-6 col-md-3'>
                <div  class='form-group '>
                    <label for="">From</label>
                    <input type='date' id='rfrom' class='form-control'>
                </div>
            </div>
            <div class='col-sm-6 col-md-3'>
                <div  class='form-group '>
                    <label for="">To</label>
                    <input type='date' id='rto' class='form-control'>
                </div>
            </div>
            <div class='col-sm-6 col-md-3'>
                <div  class='form-group ' title='This will reverse only loan with this ID'>
                    <label for="">Input Loan ID (optional)</label>
                    <input type='text' id='loanid' class='form-control'>
                </div>
            </div>
            <div class='col-sm-6 col-md-3'>
                <button type='button' class='btn btn-danger'  onclick='reverserepayment()'>Reverse</button>

            </div>

            </div>
        </legend>
        @endif
        <div class='container-fluid' style='margin:10px;'>
            <h2>Check Loan Repayment Records</h2>
            <div class='row'>
                <div class='col-sm-6 col-md-2'>
                    <div  class='form-group '>
                        <label for="">Date Type</label>
                        <select id='date_type' class='form-control'>
                            <option value='paymentDate'>By Entered Date</option>
                            <option value='created_at'>By Actual Date</option>
                        </select>
                    </div>
                </div>
                <div  class='form-group col-md-2 col-sm-6'>
                    <label for="">From</label>
                    <input type='date' id='from' class='form-control'>
                </div>
                <div  class='form-group  col-md-2 col-sm-6'>
                    <label for="">To</label>
                    <input type='date' id='to' class='form-control'>
                </div>
                @if(cando('select-cutomer-branch|root'))
                <div class="col-md-2 col-sm-6">
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
                    <button type='button' class='btn btn-primary' id='click-button' onclick='searchreport()'>Filter</button>
                </div>
            </div>

            <div class='table-responsive'>

        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client Names</th>
                        <th>Account Number</th>
                        <th>Loan ID</th>
                        <th>Payment Ref</th>
                        <th>Interest Paid</th>
                        <th>Principal Paid</th>
                        <th>LoanFeee</th>
                        <th>Mgrt Feee</th>
                        <th>Amount Paid</th>
                        <th>Balance After</th>
                        <th>Remark</th>
                        <th>Branch</th>
                        <th>Approve By</th>
                        <th>Initiated By</th>
                        <th>PaymentDate</th>
                        <th>Method</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody id='dueLoanRecTable'>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>




<div class="modal" id="stringModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Repayment System Log Remark</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id='staff_deposit'>
                <p>below is how the payment was made</p>
                <div class='alert alert-info'>Note: I=Interest P=Principal</div>

                <div class="row justify-content-center m-5" style="">

                    <div class="col-md-8">
                        <div class='repayString'>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
