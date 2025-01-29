@extends('layouts.app')

@section('content')


<style>
    .validation-text {
        margin-top: 0;
    }
</style>
<div class="col-md-12 py-1" style="margin-top: 10px;">
    <h4 style="text-align: left;">Staff Payroll</h4>
</div>

<div class='render_page'></div>
<!-- Modal -->
<div class="modal" id="exampleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Staff Payroll for <span id="staff_title"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>


            <form id="add-staff">
                <div class="modal-body">
                    <input type="hidden" id="staff_id" name='staff_id'>

                    <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                    <fieldset class="col-md-12">
                        <legend>
                            <h5 style="float: left;">Salary Details</h5>
                            <hr style="margin-top: 30px;">
                        </legend>

                        <div class='row'>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label for="employment_type">Employment Type</label>
                                    <select class="form-control" name='employment_type' id="employment_type">

                                        <option value="">--Select Employment Type</option>
                                        @foreach($employment_types as $datarecords)
                                        <option value='{{$datarecords->id}}'>
                                            {{$datarecords->employment_type}}
                                        </option>
                                        @endforeach

                                    </select>
                                    <div class='validation-text'></div>
                                </div>

                            </div>

                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="form-group">
                                    <label for="basic_salary">Basic Salary</label>
                                    <input type="number" class="form-control" value="0" name="basic_salary"
                                        id="basic_salary" placeholder="Enter Basic Salary">

                                    <div class='validation-text'></div>
                                </div>

                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label for="day_of_payment">Day of Payment</label>
                                    <select class="form-control" id="day_of_payment" name="day_of_payment" required>
                                        <option value="">Select Day of payment</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                        <option value="7">7</option>
                                        <option value="8">8</option>
                                        <option value="9">9</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                        <option value="24">24</option>
                                        <option value="25">25</option>
                                        <option value="26">26</option>
                                        <option value="27">27</option>
                                        <option value="28">28</option>
                                        <option value="29">29</option>
                                        <option value="30">30</option>
                                        <option value="31">31</option>
                                    </select>

                                    <div class='validation-text'></div>
                                </div>

                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="col-md-12">
                        <legend>
                            <h5 style="float: left;">Allowances</h5>
                            <hr style="margin-top: 30px;">
                        </legend>
                        <div class="row">
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="house_allowance">House Allowance</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="house_allowance"
                                        name='house_allowance' value="0" aria-describedby="basic-addon3"
                                        placeholder="Enter House Allowance">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="medical_allowance">Medical Allowance</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="medical_allowance"
                                        name='medical_allowance' value="0" aria-describedby="basic-addon3"
                                        placeholder="Medical Allowance">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="special_allowance">Special Allowance</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="special_allowance"
                                        name='special_allowance' value="0" aria-describedby="basic-addon3"
                                        placeholder="Special Allowance">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="provident_fund_contribution">Provident Fund</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="provident_fund_contribution"
                                        name='provident_fund_contribution' value="0" aria-describedby="basic-addon3"
                                        placeholder="Provident Fund">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="overtime">Overtime</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="overtime" name='overtime' value="0"
                                        aria-describedby="basic-addon3" placeholder="Overtime Allowance">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="transport_allowance">Transport Allowance</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="transport_allowance"
                                        name='transport_allowance' value="0" aria-describedby="basic-addon3"
                                        placeholder="Transport Allowance">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="paid_leave">Paid Leave</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="paid_leave" name='paid_leave'
                                        value="0" aria-describedby="basic-addon3" placeholder="Paid Leave">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="other_allowances">Other Allowances</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="other_allowances"
                                        name='other_allowances' value="0" aria-describedby="basic-addon3"
                                        placeholder="Other Allowances">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                        </div>

                    </fieldset>

                    <fieldset class="col-md-12">
                        <legend>
                            <h5 style="float: left;">Deductions</h5>
                            <hr style="margin-top: 30px;">
                        </legend>
                        <div class="row">
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="tax_deductions">Tax Deductions</label>
                                <div class="input-group">
                                    <input type="number" value="0" class="form-control" id="tax_deductions"
                                        name='tax_deductions' aria-describedby="basic-addon3"
                                        placeholder="Tax Deductions">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="provident_fund_deduction">Provident Fund Deduction</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="provident_fund_deduction"
                                        name='provident_fund_deduction' value="0" aria-describedby="basic-addon3"
                                        placeholder="Provident Fund Deductions">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="pension_deduction">Pension Deductions</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="pension_deduction"
                                        name='pension_deduction' value="0" aria-describedby="basic-addon3"
                                        placeholder="Pension deduction">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="unpaid_leave">Unpaid Leave</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="unpaid_leave" name='unpaid_leave'
                                        value="0" aria-describedby="basic-addon3" placeholder="Unpaid Leave">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="loan_deduction">Loan Deduction</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="loan_deduction" name='loan_deduction'
                                        value="0" aria-describedby="basic-addon3" placeholder="Loan deduction">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="other_deductions">Other Deductions</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="other_deductions"
                                        name='other_deductions' value="0" aria-describedby="basic-addon3"
                                        placeholder="Other deductions">
                                </div>
                                <div class='validation-text'></div>
                            </div>

                        </div>

                    </fieldset>

                    <fieldset class="col-md-12">
                        <legend>
                            <h5 style="float: left;">Total Salary Details</h5>
                            <hr style="margin-top: 30px;">
                        </legend>
                        <div class="row">
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="gross_salary">Gross Salary</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="gross_salary" name='gross_salary'
                                        aria-describedby="basic-addon3" value="0" readonly>
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="total_deductions">Total Deductions</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="total_deductions"
                                        name='total_deductions' aria-describedby="basic-addon3" readonly value="0">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="net_salary">Net Salary</label>
                                <div class="input-group">

                                    <input type="number" class="form-control" id="net_salary" name='net_salary'
                                        aria-describedby="basic-addon3" value="0" readonly>
                                </div>
                                <div class='validation-text'></div>
                            </div>
                        </div>

                    </fieldset>

                    <fieldset class="col-md-12">
                        <legend>
                            <h5 style="float: left;">Account Details</h5>
                            <hr style="margin-top: 30px;">
                        </legend>
                        <div class="row">
                            <div class="col-md-12 alert alert-info">
                                <p style="text-align: left; color: #000000">
                                    Do you want us to pay this staff every month automatically ?... If (Yes),
                                    kindly select either Pay to wallet or Pay to Bank account.
                                <p style="text-align: left; color: #000000"> 1. Pay to wallet required the staff to be registered as a
                                    customer where payment
                                    will be
                                    made.
                                    note: company wallet will not be debit for this Option.</p>
                                <p style="text-align: left; color: #000000">
                                    3 Pay into Bank account
                                    Please note: your scompany wallet will be debited and staff bank account linked
                                    credited/
                                    Fee N30 flat apply.</p>
                                </p>

                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="bank">Payment method</label>
                                <div class="input-group">
                                    <select class="form-control" name='paymentMethod' id="paymentMethod">

                                        <option value="">Select Payment Method</option>
                                        <option value="0">Wallet</option>
                                        <option value="1">Bank Transfer</option>

                                    </select>
                                </div>
                                <div class='validation-text'></div>
                            </div>

                            <div class="col-sm-6 col-lg-4 mb-4" id="walletAccountDiv" style="display: none;">
                                <label for="wallet_account_number">Wallet Account Number</label>
                                <div class="input-group">

                                    <input type="text" class="form-control" maxlength="10" id="wallet_account_number"
                                        oninput="verifyWalletAccount()" name='wallet_account_number'
                                        aria-describedby="basic-addon3">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4" id="walletAccountNameDiv" style="display: none;">
                                <label for="wallet_account_name">Account Name</label>
                                <div class="input-group">

                                    <input type="text" class="form-control" id="wallet_account_name"
                                        name='wallet_account_name' aria-describedby="basic-addon3" readonly>
                                </div>
                                <div class='validation-text'></div>
                            </div>


                            <div class="col-sm-6 col-lg-4 mb-4" id="bankDiv" style="display: none;">
                                <label for="bank">Bank</label>
                                <div class="input-group">
                                    <select class="form-control" name='bank' id="bank">

                                        <option value="">--Select Bank--</option>
                                        @foreach($banks as $datarecords)
                                        <option value='{{$datarecords->bank_code}}'>
                                            {{$datarecords->bank_name}}
                                        </option>
                                        @endforeach

                                    </select>
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4" id="accountNumberDiv" style="display: none;">
                                <label for="total_deductions">Account Number</label>
                                <div class="input-group">

                                    <input type="text" class="form-control" maxlength="10" id="account_number"
                                        oninput="verifyBankAccount()" name='account_number'
                                        aria-describedby="basic-addon3">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-lg-4 mb-4" id="accountNameDiv" style="display: none;">
                                <label for="net_salary">Account Name</label>
                                <div class="input-group">

                                    <input type="text" class="form-control" id="account_name" name='account_name'
                                        aria-describedby="basic-addon3" readonly>
                                </div>
                                <div class='validation-text'></div>
                            </div>

                            <div class="col-sm-6 col-lg-4 mb-4">
                                <label for="payment_mode">Staff Payment Mode</label>
                                <div class="input-group">
                                    <select class="form-control" name='payment_mode' id="payment_mode">

                                        <option value='0'>Automatic payment</option>
                                        <option value='1'>Manual payment</option>
                                    </select>
                                </div>
                                <div class='validation-text'></div>
                            </div>
                        </div>

                    </fieldset>

                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" id="savePayrollBtn" class="btn btn-primary">Save</button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection