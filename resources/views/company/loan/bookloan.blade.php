@extends('layouts.app')

@section('content')
<div class="widget-content widget-content-area br-6">
    <div class="card mb-4 mt-4">
    <form id='add-loan-book' enctype="multipart/form-data">
        <div id='bookingform'>
            <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
            <div class="modal-body">
                <div class='row'>
                    <div class="col-sm-6 col-md-12">
                    <label>Loan RefID</label>
                    <input type='text' id='loanref' name='loanref' value='{{$refLoan}}' class='form-control'>
                    </div>
                </div>
                <div class='row'>
                    <div class="col-sm-6 col-md-12">
                    <label>Borrowers</label>
                        <select  id='customer' class="js-example-basic-multiple form-control" name="customer[]" style='margin:5px'></select>
                    <span class='validation-text'></span>
                    </div>
                </div>
                <div class='row'>

                    @if(cando('select-cutomer-branch|root'))
                        <div class="col-sm-6 col-md-6">
                            <div class="form-group">
                                <label for="">Branch</label>
                                <select name="branch" id='branch' onchange="renderStaff()" class="form-control">
                                    <option value="null">Select Branch</option>
                                    @if(count($branch))
                                    @foreach($branch as $branchData)
                                    <option value="{{Crypt::encryptString($branchData->id)}}">{{$branchData->branch_name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        @else
                        <input type='hidden' value='{{Crypt::encryptString(auth()->user()->branch_id)}}' name="branch" id='branch'>
                        @endif

                        @if(cando('select-cutomer-branch|root'))
                        <div class="col-sm-6 col-md-6">
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

                                                data.forEach(function (staff) {

                                                    $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
                                                });
                                            }
                                        });
                                    }
                                </script>
                            @else
                            <input type='hidden' value='{{auth()->user()->uuid}}' name="officer" id='officer'>
                            @endif
                        </div>


                </div>
                <div class='row'>
                    <div class='col-sm-6 col-md-6'>
                    <label>Loan Product</label>
                    <select name='loan_products' id='loan_products' class='form-control form-small' style='margin:5px' ></select>
                    <span class='validation-text'></span>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                    <label>Loan Type</label>
                    <select name='loan_type' id='loan_type' class='form-control form-small' style='margin:5px'>
                        <option value='1'>Flat</option>
                        <option value='2'>Reducing</option>

                    </select>
                    <span class='validation-text'></span>
                    </div>
                </div>
                <div class='row'>
                    <div class="col-sm-6 col-md-6">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Principal</span>
                            </div>
                            <input type="text" class="form-control" name='principal' id='principal' aria-describedby="basic-addon3"
                                placeholder="100..." value='0'>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Duration</span>
                        </div>
                        <input type="number" class="form-control" name='Duration' id='Duration' aria-describedby="basic-addon3"
                            placeholder="100..." value='0'>
                    </div>
                    </div>

                        <div class="col-sm-6 col-md-6">
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Duration Period</span>
                                </div>
                                <select id='DurationPeriod' name='DurationPeriod' class='form-control'>
                                    {{-- <option value='days'>Days</option> --}}
                                    <option value='weeks'>Weeks</option>
                                    <option value='months'>Months</option>
                                    <option value='years'>Years</option>
                                </select>
                            </div>
                        </div>
                        <div class='col-sm-6 col-md-6'>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Repayment Cycle</span>
                                </div>
                                <select name='period' id='freq' class='form-control'>
                                    @forelse($period as $periodData)
                                        <option value='{{Crypt::encryptString($periodData->id)}}'>{{$periodData->name}}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <span class='validation-text'></span>
                            </div>
                            <div class="col-sm-6 col-md-6" id='repaymentAMTDiv'>

                                <div class="input-group mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Repayment Amount</span>
                                    </div>
                                    <input type="text" class="form-control" name='RepaymentAmt' id='Duration' aria-describedby="basic-addon3"
                                        placeholder="100..." value='0'>
                                </div>
                            </div>
                    <div class='col-sm-6 col-md-6'>
                        <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">loanFeeMethod</span>
                        </div>
                        <select id='loanFeeMethod' name='loanFeeMethod' class="form-control" >
                            <option value='1'>No Charge</option>
                            <option value='2'>Charge from Principal</option>
                            <option value='3'>Charge from Interest</option>
                            <option value='4'>Charge from Principal + Interest</option>
                        </select>
                </div>
                <span class='validation-text'></span>
                </div>
                <div class='col-sm-6 col-md-6'>

                {{-- new added --}}

                <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">loanFee(%)</span>
                    </div>
                    <input type="text" class="form-control" name='loanFee' id='loanFee' aria-describedby="basic-addon3"
                        placeholder="100..." value='0'>
                </div>
                <span class='validation-text'></span>
            </div>
            </div>
            <div class='row'>
                <div class='col-sm-6 col-md-6'>
                <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">ManagementFee(%)</span>
                    </div>
                    <input type="text" class="form-control" name='managementFee' id='managementFee' aria-describedby="basic-addon3"
                        placeholder="100..." value='0'>
                </div>
                <span class='validation-text'></span>
                </div>
                <div class='col-sm-6 col-md-6'>

                <div class="input-group mb-4">
                <div class="input-group-prepend">
                    <span class="input-group-text">managementFeeMethod</span>
                </div>
                    <select id='managementFeeMethod' name='managementFeeMethod' class="form-control" >

                        <option value='1'>No Charge</option>
                        <option value='2'>Charge from Principal</option>
                        <option value='3'>Charge from Interest</option>
                        <option value='4'>Charge from Principal + Interest</option>
                    </select>
                </div>
                <input type="hidden" class="form-control" name='keep_as_past_onMissed_onExtend' value='0' aria-describedby="basic-addon3" >
                <input type="hidden" class="form-control" name='ExtendDuration' value='0' aria-describedby="basic-addon3" >

                </div>
                <div class="col-sm-6 col-md-6">
                    <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Missed-Fee(%)</span>
                            </div>
                            <input type="text" class="form-control" name='missedFee' id='missedFee' aria-describedby="basic-addon3"
                                placeholder="100..." value='0'>
                        </div>
                </div>

                <div class='row'>

                    <div class="col-sm-6 col-md-6">

                        <label>Guarantor Option</label>

                        <select  class="form-control" name='guarantorOption' id='guarantorOption' >
                            <option value='2'>No Guarantor</option>
                            <option value='1'>Use Guarantor</option>
                        </select>

                    </div>
                    <div class="col-sm-6 col-md-6 guarantorsBox">
                        <label>Guarantors Add<a href="{{url('company/'.Auth()->user()->company->short_name.'/loan/guarantor')}}" target="_blank"><i class='fa fa-plus'></i></a></label>
                        <select  id='guarantor' class="js-example-basic-multiple form-control" name="guarantor[]" multiple="multiple" ></select>
                    </div>
                    <div class="col-sm-6 col-md-6">

                        <label>Payment Method</label>

                            <select  class="form-control" name='auto_pay' id='auto_pay' >
                                <option value='1'>Auto Debit System Debit User-Wallet</option>
                                <option value='2'>Manual -- I'll Credit Loan</option>
                            </select>

                    </div>
                    <div class="col-sm-6 col-md-12">

                        <label>Remarks</label>
                        <textarea name='remark' class='form-control'></textarea>
                    </div>
                </div>
            </div>


            </div>
            <div class="modal-footer m-5">
                <button type="reset"  class="btn" ><i class="flaticon-cancel-12"></i> Discard</button>
                <button type="button" class="btn btn-primary" id="loanNextBTN">Process</button>
            </div>
        </div>
        <div id='bookingform2'>

            @include('company.loan.inc.bookingSummary')
            <p><a href='{{url("assets/agreement/WBL-LOAN-AGREEMENT.docx")}}' class='text-info'>Read user agreement</a></p>

            <div class="modal-footer m-5">
                <button type="button"  class="btn" onclick='prevForm()'><i class="flaticon-cancel-12"></i> Prev</button>
                <button type="submit" class="btn btn-primary" id='SubmitLoanBTN'>Submit</button>
            </div>
        </div>
    </form>
    </div>
</div>

@endsection
