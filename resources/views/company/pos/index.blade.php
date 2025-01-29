@extends('layouts.app')

@section('content')
<div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>POS Management</h4>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area text-center">
            <form id='submit_Rform'>
                <div class="row">
                    <div class="col-sm-6 col-md-6">
                        <label for="customer" class="col-form-label">Select Customer</label>
                        <select name='customer' id='customer' class='form-control form-small' style='margin:5px'>

                        </select>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <label for="pos_serial_number" class="col-form-label">POS Serial Number</label>
                        <input type="text" placeholder="POS Serial Number" class="form-control" name="pos_serial_number" id="pos_serial_number" />
                    </div>
                </div>

                <button type='submit' class="mr-2 btn btn-primary">Add Terminal</button>
            </form>


        </div>
    </div>
</div>

<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <span id='infobait'></span>
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>

                            <th>Name</th>
                            <th>Account Number</th>
                            <th>Terminal Code</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Created by</th>
                            <th>Action</th>

                        </thead>
                        <tbody>

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog rotateInDownLeft" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Credit POS</h5>

                </div>

                <form id='credit_pos_form'>
                    <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" id='customer_reference' name='customer_reference'>
                    <input type="hidden" id="transaction_type" name='transaction_type' />
                    <input type="hidden" id='terminal_serial' name='terminal_serial'>

                    <div class="modal-body">
                        <div id="debitCreditDiv" style="display: none;">
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Amount</span>
                                </div>
                                <input type="number" class="form-control" id='transaction_amount' name='transaction_amount' aria-describedby="basic-addon3" placeholder="amount">

                            </div>
                            <span class='validation-text'></span>
                        </div>
                        <div id="reassignDiv" style="display: none;">
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Account Number</span>
                                </div>
                                <input type="number" class="form-control" id='account_number' name='account_number' aria-describedby="basic-addon3" placeholder="Enter New User Account Number">

                            </div>
                            <span class='validation-text'></span>
                        </div>
                        <div id="deactivateActivateDiv" style="display: none;">
                            <div class=" mb-4">
                                <h5 id="deactivationActivationMessage"></h5>

                                <input type="hidden" name="pos_status" id="pos_status">

                            </div>
                            <span class='validation-text'></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                        <button type="submit" class="btn btn-primary" id='savingsbtn'>Continue</button>
                    </div>
                </form>


            </div>
        </div>

        <div class="modal fade" id="reassign_modal" tabindex="-1" role="dialog" aria-labelledby="reassignModalLabel" aria-hidden="true">
            <div class="modal-dialog rotateInDownLeft" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reassignModalLabel">Reassign POS</h5>

                    </div>

                    <form id='reassign_pos_form'>
                        <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" id='user_reference' name='user_reference'>
                        <input type="hidden" id='pos_serial_number' name='pos_serial_number'>

                        <div class="modal-body">
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">User Account Number</span>
                                </div>
                                <input type="number" class="form-control" id='account_number' name='account_number' aria-describedby="basic-addon3" placeholder="Enter user account number">

                            </div>
                            <span class='validation-text'></span>

                        </div>
                        <div class="modal-footer">
                            <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                            <button type="submit" class="btn btn-primary" id='reassignBtn'>Reassign</button>
                        </div>
                    </form>



                </div>
            </div>
        </div>

    </div>
    <!-- Modal -->


    @endsection