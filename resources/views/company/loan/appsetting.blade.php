@extends('layouts.app')

@section('content')

<button type="button" style="margin:10px;" onclick='addLoanSettings()' class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#exampleModal">
    Loan Settings &nbsp<i class='fa fa-plus'></i>
</button>
<div class="widget-content widget-content-area br-6">
    <div class='alert alert-info'>This section set customer loan eligibility</div>
    <div class="table-responsive mb-4 mt-4">
        <span id='infobait'></span>
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
            <th>sn</th>
            <th>Product</th>
            <th>durationPeriod</th>
            <th>repayment</th>
            <th>duration</th>
            <th>maxamt</th>
            <th>minamt</th>
            <th>transactionMin</th>
            <th>transactionMax</th>
            <th>minWalletBalance</th>
            <th>maxWalletBalance</th>
            <th>No. Active</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
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
                <h5 class="modal-title" id="exampleModalLabel">App Loan Settings</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id='add-loan-settings'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <div class="modal-body">
                <input type="hidden" name='setting_id' id='setting_id' value='0'>
                    <div class='row'>
                        <div class='col-sm-6 col-md-6'>
                            <label>Loan Product</label>
                            <select name='loan_products' id='loan_products' class='form-control form-small' style='margin:5px' >
                                @forelse($product as $productData)
                                <option value='{{Crypt::encryptString($productData->id)}}'>{{$productData->product_name." ".$productData->interest_rate."%"}}</option>
                                @empty
                                @endforelse
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
                        <div class="col-sm-6 col-md-6">
                               
                                <label>Duration Period</label>
                                <select id='DurationPeriod' name='DurationPeriod' class='form-control'>
                                    <option value='weeks'>Weeks</option>
                                    <option value='months'>Months</option>
                                    <option value='years'>Years</option>
                                </select>
                        </div>
                        <div class="col-sm-6 col-md-6">
                                <label>Duration</label>
                                <input type="number" class="form-control" name='Duration' id='Duration' aria-describedby="basic-addon3"
                                    placeholder="100..." value='1'>
                        </div>
                        <div class='col-sm-6 col-md-6'>
                            
                            <label>Repayment Cycle</label>
                            <select name='period' id='freq' class='form-control'>
                                @forelse($period as $periodData)
                                    <option value='{{Crypt::encryptString($periodData->id)}}'>{{$periodData->name}}</option>
                                @empty
                                @endforelse
                            </select>
                        <span class='validation-text'></span>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <label>Min-Principal</label>
                                <input type="number" class="form-control" name='minamt' id='minamt' aria-describedby="basic-addon3"
                                    placeholder="100..." value='1'>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <label>Max-Principal</label>
                                <input type="number" class="form-control" name='maxamt' id='maxamt' aria-describedby="basic-addon3"
                                    placeholder="100..." value='1'>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            
                            <label>Min-Transaction</label>
                                <input type="number" class="form-control" name='transactionMin' id='transactionMin' aria-describedby="basic-addon3"
                                    placeholder="100..." value='1'>
                           
                        </div>
                        <div class="col-sm-6 col-md-6">
                           
                            <label>Max-Transaction</label>
                                <input type="number" class="form-control" name='transactionMax' id='transactionMax' aria-describedby="basic-addon3"
                                    placeholder="100..." value='0'>
                           
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <label>Min-Wallet Balance</label>
                                <input type="number" class="form-control" name='minWalletBalance' id='minWalletBalance' aria-describedby="basic-addon3"
                                    placeholder="100..." value='0'>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <label>Max-Wallet Balance</label>
                                <input type="number" class="form-control" name='maxWalletBalance' id='maxWalletBalance' aria-describedby="basic-addon3"
                                    placeholder="100..." value='0'>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <label>No. Active Loan</label>
                                <input type="number" class="form-control" name='NumOngoinAllowed' id='NumOngoinAllowed' aria-describedby="basic-addon3"
                                    placeholder="100..." value='0'>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <label>Remark</label>
                            <textarea class="form-control" name='remark' id='remark' ></textarea>
                        </div>
                    
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="button" onclick="saveSettings()" class="btn btn-primary submit" id="loanBTN">Save</button>
                    <button type="button" onclick="UpdatesaveSettings()" class="btn btn-primary update" id="updateloanBTN">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection