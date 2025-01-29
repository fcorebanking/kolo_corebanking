@extends('layouts.app')

@section('content')

<button type="button" style="margin:10px;" onclick='newModal()' class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#exampleModal">
    Loan Product &nbsp<i class='fa fa-plus'></i>
</button>
<div class="widget-content widget-content-area br-6">
    <div class="table-responsive mb-4 mt-4">
        <span id='infobait'></span>
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
            <th>sn</th>
            <th>Name</th>
            <th>Interest Rate(%)</th>
            <th>Default Principal</th>
            <th>LoanFee(%)</th>
            <th>management Fee(%)</th>
            <th>missed Fee(%)</th>
            <th>action</th>
            </thead>
            <tbody>

            </tbody>
        </table>
            <!-- Modal -->
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Loan Product</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id='add-loan-product'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <div class="modal-body">
                <input type="hidden" name='product_id' id='product_id' value=''>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" class="form-control" name='product_name' id='product_name' aria-describedby="basic-addon3"
                            placeholder="product 1.."  required>
                    </div>
                    <span class='validation-text'></span>
                    <div class='row'>
                        <div class='col-sm-6 col-md-6'>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Interest Rate(%)</span>
                        </div>
                        <input type="text" class="form-control" name='interest_rate' id='interest_rate' aria-describedby="basic-addon3"
                            placeholder="50..."  required>
                    </div>
                    <span class='validation-text'></span>
                        </div>
                        <div class='col-sm-6 col-md-6'>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Repayment Method</span>
                                </div>
                                    <select id='loan_interest_type' name='loan_interest_type' class="form-control" >
                                        <option value='1'>Interest by Duration for each repayment</option>
                                        <option value='2'>Charge total Interest by duration</option>
                                    </select>
                            </div>
                            <span class='validation-text'></span>
                            </div>
                    <div class='col-sm-6 col-md-6'>

                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Default Principal</span>
                        </div>
                        <input type="text" class="form-control" id='DefaultPrincipal' name='DefaultPrincipal' aria-describedby="basic-addon3"
                            placeholder="100..." value='0'>
                    </div>
                    <span class='validation-text'></span>
                </div>

                        <div class='col-sm-6 col-md-6'>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Min. Principal</span>
                                </div>
                                <input type="text" class="form-control" name='minPrincipal' id='minPrincipal' aria-describedby="basic-addon3"
                                    placeholder="100..." value='0'  required>
                            </div>
                            <span class='validation-text'></span>
                        </div>
                        <div class='col-sm-6 col-md-6'>
                            <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Max. Principal</span>
                            </div>
                            <input type="text" class="form-control" id='maxPrincipal' name='maxPrincipal' aria-describedby="basic-addon3"
                                placeholder="100..." value='0'  required>
                        </div>
                        <span class='validation-text'></span>
                        </div>

                    <div class='col-sm-6 col-md-6'>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">loanFeeMethod</span>
                        </div>
                            <select id='loanFeeMethod' name='loanFeeMethod' class="form-control" >
                                <option value='0'>No Charge</option>
                                <option value='1'>Charge from Principal</option>
                                <option value='2'>Charge from Interest</option>
                                <option value='3'>Charge from Principal + Interest</option>
                            </select>
                    </div>
                    <span class='validation-text'></span>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">loanFee(%)</span>
                        </div>
                        <input type="text" class="form-control" name='loanFee' id='loanFee' aria-describedby="basic-addon3"
                            placeholder="100..." value='0'  required>
                    </div>
                    <span class='validation-text'></span>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                        <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Repayment Order</span>
                        </div>
                            <select id='orderOfRepayment' name='orderOfRepayment' class="form-control" >
                                <option value='1'>Charge from Principal + Interest (Default)</option>
                                <option value='2'>Charge from Principal First</option>
                                <option value='3'>Charge from Interest First</option>
                            </select>
                        </div>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">ManagementFee(%)</span>
                        </div>
                        <input type="text" class="form-control" name='managementFee' id='managementFee' aria-describedby="basic-addon3"
                            placeholder="100..." value='0'  required>
                    </div>
                    <span class='validation-text'></span>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                    <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">managementFeeMethod</span>
                    </div>
                        <select id='managementFeeMethod' name='managementFeeMethod' class="form-control" >

                            <option value='0'>No Charge</option>
                            <option value='1'>Charge from Principal</option>
                            <option value='2'>Charge from Interest</option>
                            <option value='3'>Charge from Principal + Interest</option>
                        </select>
                    </div>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">DefaultDuration</span>
                            </div>
                            <input type="text" class="form-control" name='DefaultDuration' id='DefaultDuration' aria-describedby="basic-addon3"
                                placeholder="100..." value='0'  required>
                        </div>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                        <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">minDuration</span>
                                </div>
                                <input type="text" class="form-control" name='minDuration' id='minDuration' aria-describedby="basic-addon3"
                                    placeholder="100..." value='0' required>
                            </div>
                        </div>
                        <div class='col-sm-6 col-md-6'>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">maxDuration</span>
                                </div>
                                <input type="text" class="form-control" name='maxDuration' id='maxDuration' aria-describedby="basic-addon3"
                                    placeholder="100..." value='0'  required>
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
                    <div class='col-sm-6 col-md-6'>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Decimal Point</span>
                            </div>
                            <input type="text" class="form-control" name='approximate_status' id='approximate_status' aria-describedby="basic-addon3"
                                placeholder="100..." value='0'  required>
                        </div>
                        <span class='validation-text'></span>
                    </div>
                    <div class="col-sm-6 col-md-6">
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Status</span>
                        </div>
                            <select class='form-control'  name='status_id' id='status_id' >
                                <option value='1'>Active</option>
                                <option value='2'>Disable</option>
                                <option value='3'>Disable product Loan</option>
                            </select>
                    </div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Auto Pay</span>
                            </div>
                                <select  class="form-control" name='auto_pay' id='auto_pay' >
                                    <option value='1'>Yes</option>
                                    <option value='2'>No</option>
                                </select>
                        </div>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                        <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Penalty Charge Method</span>
                        </div>
                            <select id='penaltyChargeMethod' name='penaltyChargeMethod' class="form-control" >
                                <option value='1'>Charge from Principal</option>
                                    <option value='2'>Charge from Interest</option>
                                    <option value='3'>Charge from Principal + Interest</option>
                                    <option value='4'>Charge from MissedFee</option>
                                    <option value='5'>Charge from Principal Left</option>
                                    <option value='6'>Charge from Interest Left</option>
                                    <option value='7'>Charge from Loan Balance</option>
                                    <option value='8'>Charge from Missed Loan Principal</option>
                                    <option value='9'>Charge from Missed Loan Interest</option>
                                    <option value='10'>Charge from Missed Loan Balance</option>
                            </select>
                        </div>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                        <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Penalty Charge Period</span>
                        </div>
                            <select id='penaltyChargePeriod' name='penaltyChargePeriod' class="form-control" >
                                <option value='1'>Charge Daily</option>
                                    <option value='2'>Charge According to Repayment</option>
                            </select>
                        </div>
                    </div>
                        <div class="col-sm-6 col-md-6">
                        <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Missed Fee (%)</span>
                        </div>
                        <input type="text" class="form-control" name='missedFee' id='missedFee' aria-describedby="basic-addon3"
                            placeholder="100..." value='0' required>
                    </div>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Penalty/Extension</span>
                                </div>
                                    <select class='form-control'  name='keep_as_past_onMissed_onExtend' id='keep_as_past_onMissed_onExtend' >
                                        <option value='0'>No Extension</option>
                                        <option value='1'>Extend by Interest Untill Fully Paid</option>
                                        {{-- <option value='2'>Extend by Interest Missed Loan with penalty fee</option> --}}
                                        {{-- <option value='1'>Extend by Interest</option> --}}
                                    </select>
                            </div>
                            </div>
                        <div class="col-sm-6 col-md-6">
                        <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            {{-- <span class="input-group-text">minExtendDuration</span> --}}
                        </div>
                        <input type="hidden" class="form-control" name='minExtendDuration' id='minExtendDuration' aria-describedby="basic-addon3"
                            placeholder="100..." value='0'  required>
                    </div>
                        </div>
                        <div class="col-sm-6 col-md-6">
                        <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            {{-- <span class="input-group-text">maxExtendDuration</span> --}}
                        </div>
                        <input type="hidden" class="form-control" name='maxExtendDuration' id='maxExtendDuration' aria-describedby="basic-addon3"
                            placeholder="100..." value='0'  required>
                    </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" class="btn btn-primary" id="loanBTN">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
