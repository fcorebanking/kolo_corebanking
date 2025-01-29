@extends('layouts.app')

@section('content')

<button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal" onclick="edit_product('add')" id="addProductBtn">
    Add Product &nbsp<i class='fa fa-plus'></i>
</button>
<div class='render_page'></div>
<!-- Modal -->

<div class="modal fade" id="editexampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Product</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <form id='accountProductForm'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" id="edit_id">
                <input type="hidden" id="operation_type">

                <div class="modal-body">

                    <div class="row">


                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Name</span>
                            </div>
                            <input type="text" class="form-control" id='edit_name' aria-describedby="basic-addon3" placeholder="product 1..">
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Account Prefix</span>
                            </div>
                            <input type="number" class="form-control" id='code' aria-describedby="basic-addon3" placeholder="1111..">
                        </div>
                        <p class='alert alert-info'>Interest is credited to customer account after set Duration time if the customer have upto that Min Amount Interest in thier balance and have not make withdrawal after the duration date</p>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Interest</span>
                            </div>
                            <input type="number" class="form-control" id='edit_interest' aria-describedby="basic-addon3" placeholder="50...">
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Min Amount For Interest</span>
                            </div>
                            <input type="text" class="form-control" id='editinterestamt' aria-describedby="basic-addon3" placeholder="50...">
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Min. Withdraw</span>
                            </div>
                            <input type="text" class="form-control" id='edit_min_withdraw' aria-describedby="basic-addon3" placeholder="100...">
                        </div>
                        {{-- new added --}}
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Max. Withdraw</span>
                            </div>
                            <input type="text" class="form-control" id='edit_max_withdraw' aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Max. Daily Transfer</span>
                            </div>
                            <input type="text" class="form-control" id='edit_max_daily_transfer' aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Max. Deposit</span>
                            </div>
                            <input type="text" class="form-control" id='edit_max_deposite' aria-describedby="basic-addon3" placeholder="100...">
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Maintenance Charge</span>
                            </div>

                            <input type="text" class="form-control" id='edit_maintenance_charges' aria-describedby="basic-addon3" placeholder="100...">
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Stamp Duty</span>
                            </div>
                            <input type="text" class="form-control" id='stamp_duty' aria-describedby="basic-addon3" placeholder="100...">
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Interest Duration</span>
                            </div>
                            <input type="text" class="form-control" id='editduration' aria-describedby="basic-addon3" placeholder="30...">
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Maintenance Duration</span>
                            </div>
                            <select class="form-control" id='editmaintenace_duration' aria-describedby="basic-addon3" placeholder="30...">
                                <option value='30'>Monthly</option>
                                <option value='90'>Quarterly</option>
                                <option value='365'>Yearly</option>
                                <option value='0'>No Charge</option>
                            </select>
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Frequency</span>
                            </div>
                            <select required name='edit_freq' id='edit_freq' class='form-control'>
                                @if(count($status) > 0)
                                @foreach($status as $status_data)
                                <option value='{{$status_data->id}}'>{{$status_data->frequency}}</option>

                                @endforeach

                                @endif
                            </select>
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Bank Transfer Charge Type</span>
                            </div>
                            <select required name='edit_bank_transfer_charge_type' onchange="updateChargeType()" id='edit_bank_transfer_charge_type' class='form-control'>
                                <option value='0'>Charge Flat Fee</option>
                                <option value='1'>Charge By Percentage</option>
                            </select>
                        </div>

                        <div class="input-group mb-4 col-lx-6 col-md-12" id="edit_min_bank_transfer_charge_or_percentage_div" style="display: none;">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="edit_min_bank_transfer_charge_or_percentage_label">Percentage Charge (%)</span>
                            </div>
                            <input type="text" class="form-control" id='edit_min_bank_transfer_charge_or_percentage' aria-describedby="basic-addon3" placeholder="50...">
                        </div>

                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="edit_max_bank_transfer_charge_or_flat_fee_label">Flat Fee</span>
                            </div>
                            <input type="text" class="form-control" id='edit_max_bank_transfer_charge_or_flat_fee' aria-describedby="basic-addon3" placeholder="50...">
                        </div>

                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Minimum Balance</span>
                            </div>
                            <input type="text" class="form-control" id='edit_min_balance' aria-describedby="basic-addon3" placeholder="50...">
                        </div>
                        <div class="input-group mb-4 col-lx-6 col-md-12">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Registration Fee</span>
                            </div>
                            <input type="text" class="form-control" id='edit_reg_fee' aria-describedby="basic-addon3" placeholder="50...">
                        </div>
                    </div>
                </div>


                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="button" class="btn btn-primary" id="productBtn" onclick='update_btn()'>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
