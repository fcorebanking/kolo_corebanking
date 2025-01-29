<div class="modal fade bd-example-modal-lg" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">

        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">Loan Details</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>
        <form id='ongoingloan' class='card'>
            <div id='bookingform'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" class="token" id='loanID' name="loanID" value="">
                <input type="hidden" class="token" id='UserId' name="UserId" value="">

                <div class="modal-body">
                    <div class='row' id='viewdetails'>
                        <div class="col-sm-12 col-md-12 sessions">
                        </div>
                        <div class="col-sm-12 col-md-12 row">
                            <hr>

                        <div class="col-sm-6 col-md-6">
                            <p id='borrower'></p>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <p id='borrowerAccount'></p>
                        </div>
                        </div>

                        <div class="col-sm-12 col-md-12 row">
                            <hr>
                        <div class="col-sm-6 col-md-6">
                            <p id='principal'></p>
                        </div>
                        <div class="col-sm-6 col-md-6">

                            <p id='loan_balance'></p>
                        </div>

                        </div>

                        <div class="col-sm-12 col-md-12 row">
                            <hr>
                        <div class="col-sm-6 col-md-6">
                            <p id='Loantype'></p>
                        </div>
                        <div class="col-sm-6 col-md-6">

                            <p id='duration'></p>
                        </div>

                        </div>
                        <div class="col-sm-12 col-md-12 row">
                            <hr>
                        <div class="col-sm-6 col-md-6">
                            <p id='LoanBranch_id'></p>
                        </div>
                        <div class="col-sm-6 col-md-6">

                            <p id='LoanOfficer_id'></p>
                        </div>

                        </div>
                        <div class="col-sm-12 col-md-12 row">
                            <hr>
                        <div class="col-sm-6 col-md-6">
                            <p id='loan_product_id'></p>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <p id='rate'></p>
                        </div>
                        </div>
                        <div class="col-sm-12 col-md-12 row">
                            <hr>
                        <div class="col-sm-6 col-md-6">
                            <p id='repaymentlabel'>Repayment Cycle: </p>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <p id='repaymentcycle'></p>
                        </div>
                        </div>
                        <div class="col-sm-12 col-md-12 row">
                            <hr>
                        <div class="col-sm-6 col-md-6">
                            <p id='start_at'></p>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <p id='grace_at'></p>
                        </div>
                        </div>

                        <div class="col-sm-12 col-md-12 row">
                            <hr>

                        <div class="col-sm-6 col-md-12">
                            <p id='LoanFeeMethod'></p>
                        </div>
                        </div>
                        <div class="col-sm-12 col-md-12 row">
                            <hr>

                        <div class="col-sm-6 col-md-12">
                            <p id='mrgtFeeMethod'></p>
                        </div>
                        </div>

                        <div class="col-sm-12 col-md-12 row">
                            <hr>
                        <div class="col-sm-6 col-md-6">
                            <p id='keep_as_past_onMissed_onExtend'></p>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <p id='ExtendDuration'></p>
                        </div>
                        <div class="col-sm-6 col-md-12">
                            <p id='auto_pay'></p>
                        </div>

                        <div class="col-sm-6 col-md-12">
                            <p id='missedFee'></p>
                        </div>
                        </div>
                        <div class='col-sm-12 col-md-12  guarantorTitle'>
                            <h3>Loan Guarantor</h3>
                            <p id='on_guarantor'></p>
                        <div class="row guarantorCard"></div>
                        </div>
                        <div class="col-sm-12 col-md-12">
                            <hr>
                            <p id='remark'></p>
                        </div>
                    </div>
                    <div class='row' id='rollOver'>
                        <div class='col-sm-6 col-md-5 loanID'>
                        </div>
                        <div class='col-sm-6 col-md-2'>
                            <i class="fa fa-arrow-right"></i><i class="fa fa-arrow-right"></i>
                        </div>
                        <div class='col-sm-6 col-md-5'>
                            <select name='destinationLoan' id='destinationLoan' class='form-control'>
                            </select>
                        </div>
                        <div class='col-sm-12 col-md-12'>
                            <button type="button"  class="btn btn-primary" id='rollLoan' onclick='rollLoanee()'>Roll <i class="fa fa-arrow-right"></i></button>
                        </div>
                    </div>
                    @if($page_name == 'loan_all')
                        @include('company.loan.inc.paymentHistory')
                    @endif
                    <div class='row' id='writeoffSection'>
                        <div class='col-sm-12 col-md-12'>
                            <p class='alert alert-info'>Loan written off are marked as complete but there loan balance remain</p>
                            <p class='alert alert-warning'>Note: if you select (Write Off All Loan) loa will be mark as complete, if you select (Write Off Principal Loan) principal will be deducted from what is being owed, if you select (Write Off Interest Loan) Interest will be deducted from what is owed and if you select both, interest and principal left will be deducted base on the amount entered</p>
                                @if(cando('root|loan-writeoff'))
                                <p class='alert alert-warning'>Amount only work for option 2-4 the first option overrid amount</p>
                                <div class='row'>
                                    <div class='col-sm-6 col-md-6 col-lg-6'><label>Write Off Amount</label><input type='number' id='writeoffamount' name='writeoffamount' class='form-control'/></div>
                                    <div class='col-sm-6 col-md-6 col-lg-6'>
                                        <label>Write Off Option</label>
                                        <select class='form-control' id='writeoffoptions' name='writeoffoptions'>
                                            <option value='1'>Write Off All Loan</option>
                                            <option value='2'>Write Off Principal Loan</option>
                                            <option value='3'>Write Off Interest Loan</option>
                                            <option value='3'>Write Off from both</option>
                                        </select>
                                    </div>
                                    <div class='col-sm-6 col-md-6 col-lg-6 g-5 '><span class='m-5'><button type="button"  class="btn btn-danger" id='writeoff' onclick='writeloanoff()'>Write Loan Off <i class="fa fa-arrow-right"></i></button></span></div>
                                </div>


                                @endif
                                @if(cando('root|loan-liquidate'))
                                <div class='m-5'>

                                    <button type="button"  class="btn btn-danger" id='liquedateID' onclick='liquidate()'>Liquidate <i class="fa fa-arrow-right"></i></button>
                                </div>
                                @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button"  class="btn btn-flat" data-dismiss="modal" ><i class="flaticon-cancel-12"></i> Discard</button>
                </div>
            </div>

        </form>
        </div>
    </div>
    </div>

