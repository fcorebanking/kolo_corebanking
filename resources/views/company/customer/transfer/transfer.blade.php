@extends('layouts.app')

@section('content')

<div class="layout-px-spacing">
    <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <form id='transfer_active_customer_form' class='deleteform'>
                    <div class="widget-content widget-content-area br-6">
                        <span id='infobait'> <h4>Customer Ownership Transfer</h4></span>
                        <fieldset>
                            <legend> Search and Process</legend>
                            <div class='row' >
                                <div class='col-md-3'>
                                    <label for='accountNo'>Company Account No</label>
                                    <input type='text' name='accountNo' id='accountNo' class='form-control' />

                                </div>
                                <div class='col-md-3 '>
                                    <label for='action'>Company Pin</label>
                                    <input type='password' maxlength="4"  name='pin' id='pin' class='form-control' />
                                </div>

                                <div class='col-md-3'>
                                    <br>
                                    <br>
                                    <button type='submit' class='btn btn-secondary' onclick='processTransfer()'>Process Transfer</button>
                                </div>
                                <div class='col-md-3'>
                                    <br>
                                    <br>

                                    <span ><b>Check All</b> </span><input type="checkbox" name="checkedAll" id="checkedAll" title='Checkall' />
                                </div>


                            </div>
                        </fieldset>

                    </div>
                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                    <div class="widget-content widget-content-area br-6">
                        <div class="table-responsive mb-4 mt-4">
                            <span id='infobait'></span>

                            <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                <thead>
                                <th>Select</th>
                                <th>FirstName</th>
                                <th>LastName</th>
                                <th>Phone</th>
                                <th>Account Number</th>
                                <th>Balance</th>
                                <th>Action</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                                <!-- Modal -->
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
<div class="modal fade" id="comfirmtransfer" tabindex="-1" role="dialog" aria-labelledby="addTaskModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-dismiss="modal"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <div class="compose-box">
                    <div class="compose-content" id="addTaskModalTitle">
                        <h5 class="">Customer Ownership Transfer</h5>
                        <form id='FinalTransfer' >
                            <div class="d-flex  mail-subject mb-4">
                                <div class="w-100">
                                    <div id="taskdescription" class="">
                                        <label>Enter OTP</label>
                                        <input type='hidden' class='form-control' name='company_ref' id='company_ref'/>
                                        <input type='password' maxlength="4" class='form-control' name='otp' id='otp'/>
                                    </div>
                                    <span class="validation-text"></span>
                                    <p>confirm the transfer of the following Customer to <span class='tocompany'></span></p>
                                    <p class='alert alert-warning'>Note:  be advise all selected customer will be transferred to <span class='tocompany'></span> and you will no longer have access to view or manage them .. likewise all virtual collection will be credited to the new management.</p>
                                    <div class='transferList'></div>
                                    <label></label>

                                </div>
                            </div>
                            <button type='submit' id='finaltransferBTN' class='btn btn-primary'>Proceed</button>

                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>

            </div>
        </div>
    </div>
</div>
@endsection

