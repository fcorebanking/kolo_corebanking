@extends('layouts.app')

@section('content')

<div class="widget-content widget-content-area br-6">
    <div class="table-responsive mb-4 mt-4">
        <span id='infobait'></span>
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
            <th>sn</th>
            <th>Name</th>
            <th>A/C Number</th>
            <th>ref</th>
            <th>Interest Rate(%)</th>
            <th>Principal</th>
            <th>product</th>
            <th>Status</th>

            <th>action</th>
            </thead>
            <tbody>

            </tbody>
        </table>
            <!-- Modal -->
    </div>
</div>
<!-- Modal -->
<div class="modal fade bd-example-modal-lg" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content" style='padding:5px;margin:5px; width:100%;'>
        <form id='add-loan-book' class='card add-loan-book'>
            <div id='bookingform'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" class="token" id='loanID' name="loanID" value="">
                <input type="hidden" name="guarantorOption" value="x">
                <input type="hidden" id='customer' name="customer[]" >
                <div class="modal-body">
                    <div class='row'>
                        <div class='col-sm-6 col-md-6'>
                        <label>Loan Product</label>
                        <select name='loan_products' id='loanproducts' class='form-control form-small select2' style='margin:5px' >
                            @foreach($product as $productData)
                            <option >Select Product</option>
                            <option value="{{Crypt::encryptString($productData->id)}}">{{$productData->product_name.'-'.$productData->interest_rate}}%</option>
                            @endforeach
                        </select>
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
                            <input type='hidden' id='loanofficer'>
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
                                                        $loanofficer = $('#loanofficer').val();
                                                            if($loanofficer == staff.uuid){
                                                                $('select[name="officer"]').append('<option value="' + staff.uuid + '" selected>' + staff.first_name + ' ' + staff.last_name + '</option>');
                                                            }else{
                                                                $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>');
                                                            }
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
                    </div>
                    </div>
                    <div class='row'>
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

                    <div class="col-sm-6 col-md-6">

                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Duration</span>
                        </div>
                        <input type="text" class="form-control" name='Duration' id='Duration' aria-describedby="basic-addon3"
                            placeholder="100..." value='0'>
                    </div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Duration Period</span>
                            </div>
                            <select id='DurationPeriod' name='DurationPeriod' class='form-control'>
                                <option value='days'>Days</option>
                                <option value='weeks'>Weeks</option>
                                <option value='months'>Months</option>
                                <option value='years'>Years</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Principal</span>
                            </div>
                            <input type="text" class="form-control" name='principal' id='principal' aria-describedby="basic-addon3"
                                placeholder="100..." value='0'>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6" id='repaymentAMTDiv'>

                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Repayment Amount</span>
                            </div>
                            <input type="text" class="form-control" name='RepaymentAmt' id='RepaymentAmt' aria-describedby="basic-addon3"
                                placeholder="100..." value='0'>
                        </div>
                    </div>

                    <div class="col-sm-6 col-md-6">
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Set Missed Loan</span>
                        </div>

                            <select class='form-control'  name='keep_as_past_onMissed_onExtend' id='keep_as_past_onMissed_onExtend' >
                                <option value='1'>Extend Untill Fully Paid</option>
                                <option value='2'>Extend Missed Loan with penalty fee</option>
                                <option value='3'>Extend by set duration</option>
                            </select>
                    </div>
                    </div>

                    <div class="col-sm-6 col-md-6">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Extend Duration</span>
                            </div>
                            <input type="text" class="form-control" name='ExtendDuration' id='ExtendDuration' aria-describedby="basic-addon3"
                                placeholder="100..." value='0'>
                        </div>
                    </div>

                    <div class="col-sm-6 col-md-6">
                    <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Missed-Fee</span>
                            </div>
                            <input type="text" class="form-control" name='missedFee' id='missedFee' aria-describedby="basic-addon3"
                                placeholder="100..." value='0'>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <label>Payment Method</label>
                            <select  class="form-control" name='auto_pay' id='auto_pay' >
                                <option value='1'>Auto Debit System Debit User-Wallet</option>
                                <option value='2'>Manual -- I'll Credit Loan</option>
                            </select>
                    </div>
                    <div class="col-sm-12 col-md-12 sessions">

                    </div>
                    <div class="col-sm-6 col-md-12">
                        <label>Remarks</label>
                        <textarea name='remark' id='remark' class='form-control'></textarea>
                    </div>
                </div>
                <input type="hidden" class="form-control" name='actions' id='actions' aria-describedby="basic-addon3" value='1'>
                </div>
                <div class="modal-footer">
                    <button type="button"  class="btn" data-dismiss="modal" ><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="button" class="btn btn-primary" id="loanNextBTN" onclick='nextProcess()'>Proceed</button>
                </div>
            </div>
            <div id='bookingform2'>

                @include('company.loan.inc.bookingSummary')
                <div class="modal-footer">
                    <button type="button"  class="btn" onclick='prevForm()' ><i class="flaticon-cancel-12"></i> Prev</button>

                    <button type="button" class="btn btn-primary" id='SubmitLoanBTN' onclick='approvalStatus()'>Update</button>
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>

@endsection
