@extends('layouts.app')

@section('content')

<style>
    .leftSummaryDiv {
        display: flex;
        justify-content: center;
        align-content: left;
        flex-direction: column;
    }

    .bg-grey {
        background-color: #eeeeee;
    }

    .row-style {
        padding: 10 5;
    }

    .rightSummaryDiv {
        display: table;
        overflow: hidden;
    }

    .summaryValues {
        color: #525252;
        font-weight: bold;
        font-size: medium;
    }

    .rightSummaryItem {
        display: table-cell;
        vertical-align: middle;
    }
</style>

<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h3>Loan Decision / Verification</h3>

                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
                    <div class="col-md-8">


                        <form id="bvn_verification">

                            @if ($message = Session::get('success'))
                            <div class="alert alert-success mb-4" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg></button>
                                <strong> {{$message}}.</button>
                            </div>
                            @endif

                            @if ($message = Session::get('error'))
                            <div class="alert alert-danger mb-4" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg></button>
                                <strong> {{$message}}.</button>
                            </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="alert alert-info">Kindly Note that charges apply as stated Below <br>

                                        <b>Karma Loan Check: NGN50</b><br>
                                        <b>CRC Credit Bureau: NGN100</b><br>
                                        <b>EcoSystem Verification: NGN50</b><br>
                                        <b>First Central Search NGN150</b><br>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info">
                                        <h6>Why Lookup</h6><br>
                                        Perform credit check<br>
                                        See credit status of clients on the go to
                                        Improve risk management with our 
                                        Incorporated multiple credit database against chronic defaulters.
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4" style="">
                                <div class="form-group mb-4 col-sm-12">
                                    <label for="verificationType">Verification Type</label>
                                    <select class="form-control" name="verificationTyp" id="verificationTyp" required>
                                        <option value="">Select Verification Type</option>
                                        @if($customerCredit)
                                        @foreach($customerCredit as $customerCreditData)
                                        <option value="{{encrypt_decrypt('encrypt', $customerCreditData->id) }}"
                                            dataValue="{{$customerCreditData->callCost}}">{{$customerCreditData->name}}
                                        </option>
                                        @endforeach
                                        @endif
                                    </select>
                                    <span class="validation-text"></span>
                                </div>

                                <div class="form-group mb-4 col-sm-12" style="display: none;" id="validationNumberDiv">
                                    <label for="validationNumber" id="validationLabel">Enter BVN Or Phone number</label>

                                    <input type="text"
                                        oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                        class="form-control" maxlength="30" id="validationNumber" placeholder="">
                                    <span class="validation-text"></span>
                                </div>

                                <div class="" style="margin: 0 auto;">
                                    <button type="button" style="padding: 10px 100px;" disabled onclick="verify()"
                                        id="mySubmit" class=" btn btn-primary btn-block">
                                        Submit</button>
                                </div>
                            </div>

                            <div class="col-xl-12 col-lg-12 col-sm-12 mt-3" id="bvnDetailsDiv" style="display: none;">
                                <div class="widget-content widget-content-area">

                                    <h3 id="titleHeader">Karma Loan Checker</h3>

                                    <div class="row"
                                        style="flex-direction: row; justify-content: center; align-items: left;">
                                        <div class="col-md-12" style="flex-direction: column; align-items: left;">

                                            <div class="row bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>BVN: </b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="bvn_number"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Loan Amount</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="loan_amount"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Reason</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="reason"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Date of Default</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="date_of_default"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Karma Type</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="karma_type">08052144563</h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Reporting Company</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="reporting_company"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Company Email</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="company_email"></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12 col-lg-12 col-sm-12 mt-3" id="ecoSystemDiv" style="display: none;">
                                <div class="widget-content widget-content-area">

                                    <h3 id="titleHeader">Eco System Checker</h3>

                                    <div class="row"
                                        style="flex-direction: row; justify-content: center; align-items: left;">
                                        <div class="col-md-12" style="flex-direction: column; align-items: left;">

                                            <div class="row bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>BVN: </b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="ecosystem_bvn"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>First Name</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="first_name"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Last Name</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="last_name"></h5>
                                                    <h5 id="lending_lenders"></h5>
                                                    </dlending_lenders>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Lending Lenders</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="lending_lenders"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>BVN Phone Number</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="bvn_phone_number"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Date of Birth</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="date_of_birth"></h5>
                                                </div>
                                            </div>

                                            <div class="row bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Age: </b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="age"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Email</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="email"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Gender</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="gender"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>No of Loans</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="number_of_loan"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Loan Amount</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="ecosystem_loan_amount"></span></h5>

                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Min. Loan Amount</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="min_loan_amount"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Max. Loan Amount</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="max_loan_amount"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Average Loan Amount</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="average_loan_amount"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>No. of Settled Loans</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 span id="number_of_settled_loans"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Settled Loan Amount</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="settled_loan_amount"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Settled Loan Amount Paid</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="settled_loan_amount_paid"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>No. of Running Loans</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5><span id="running_loans"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Running Loan Amount</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="running_loan_amount"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>No. of Past Due Loan</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="number_of_past_due_amount"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Past Due Loan Amount</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="past_due_loan_amount"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>No. of Delayed Paid Loans</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5><span id="delayed_paid_loans"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Delayed Paid Loan Amount</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="delayed_paid_loan_amount"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Delayed Paid Loans Trials</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="delayed_paid_loans_trials"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Delayed Paid Loans Avg.</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>&#8358<span id="delayed_paid_loans_average"></span></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Number of Loan Request</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="loan_requests"></h5>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12 col-lg-12 col-sm-12 mt-3" id="crcSearchDiv" style="display: none;">
                                <div class="widget-content widget-content-area">

                                    <h3 id="titleHeader">CRC Search</h3>

                                    <div class="row"
                                        style="flex-direction: row; justify-content: center; align-items: left;">
                                        <div class="col-md-12" style="flex-direction: column; align-items: left;">

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>First Name</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="nano_profile_first_name"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Last Name</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="nano_profile_last_name"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Gender</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="nano_profile_gender"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Date of Birth</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="nano_profile_date_of_birth"></h5>
                                                </div>
                                            </div>

                                            <div class="row bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Has Credit Facility (MF Credit) </b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="mf_credit_has_credit_facilities"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>No of Delinq Credit Facilities (MF Credit)</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="mf_credit_number_of_delinqcredit_facilities"></h5>
                                                </div>
                                            </div>

                                            <div class="row bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Has Credit Facility (MG Credit) </b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="mg_has_credit_facilities"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>No of Delinq Credit Facilities (MG Credit)</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="mg_credit_number_of_delinqcredit_facilities"></h5>
                                                </div>
                                            </div>

                                            <div class="row bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Has Credit Facility (Credit) </b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="credit_has_credit_facilities"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>No of Delinq Credit Facilities (Credit)</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="credit_number_of_delinqcredit_facilities"></h5>
                                                </div>
                                            </div>

                                            <div class="row bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Report Header Mail: </b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="report_header_email"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Product Name:</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="report_header_product_name"></h5>
                                                </div>
                                            </div>

                                            <div class="row mb-9 bg-grey text-left row-style">
                                                <div class="col-md-6">
                                                    <h5><b>Report Date</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 id="credit_last_reported_date"></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12 col-lg-12 col-sm-12 mt-3" id="firstCentralDiv" style="display: none;">
                                <div class="widget-content widget-content-area">

                                    <h3 id="titleHeader">First Central</h3>

                                    <fieldset class="col-md-12">
                                        <legend id="header">Personal Details:</legend>

                                        <div class="row"
                                            style="flex-direction: row; justify-content: center; align-items: left;">
                                            <div class="col-md-12" style="flex-direction: column; align-items: left;">

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Consumer Id</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="consumer_id"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Reference No</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="reference_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Nationality</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="nationality"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>National ID No.</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="national_id_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Passport No</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="passport_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Drivers License No</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="driversLicense_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>BVN</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="bank_verification_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Pencom ID No.</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="pencom_id_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Other ID No.</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="otherid_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Date of Birth</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="birth_date"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Dependants</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="dependants"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Gender</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="first_central_gender"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Marital Status</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="marital_status"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Residential Address1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="residential_address1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Residential Address2</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="residential_address2"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Residential_Address3</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="residential_address3"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Residential_Address4</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="residential_address4"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Postal Address1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="postal_address1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Postal Address2</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="postal_address2"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Postal Address3</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="postal_address3"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Postal Address4</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="postal_address4"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Home Telephone No.</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="home_telephone_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Work Telephone No.</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="Work_telephone_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Cellular No.</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="cellular_no"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Email Address</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="email_address"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Employer Detail</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="employer_detail"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Property Owned Type</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="property_owned_type"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Surname</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="surname"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>First Name</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="fc_first_name"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Other Names</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="other_names"></h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>

                                    <fieldset class="col-md-12" style="margin-top: 30px;">
                                        <legend id="header">Credit Account Summary:</legend>

                                        <div class="row"
                                            style="flex-direction: row; justify-content: center; align-items: left;">
                                            <div class="col-md-12" style="flex-direction: column; align-items: left;">

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Monthly Installment</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_monthly_installment"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Outstanding Debt</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_outstanding_debt"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Account Arrear</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_account_arrear"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Amount Arrear</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="amount_arrear"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Accounts</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_accounts"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Accounts1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_accounts1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Ac counting Good Condition</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_accountin_god_condition"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Ac counting Good Condition</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_accountin_good_condition"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Number Of Judgement</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_numberof_judgement"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Judgement Amount</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_judgement_amount"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Last Judgement Date</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="last_judgement_date"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Numberof Dishonoured</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_numberof_dishonoured"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Dishonoured Amount</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_dishonoured_amount"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Last Bounced Cheques Date</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="last_bounced_cheques_date"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Monthly Instalment1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_monthly_instalment1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Outstanding Debt1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_outstandingdebt1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Account Arrear1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_account_arrear1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Amount Arrear1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="amount_arrear1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Ac counting Good Condition1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_accountin_god_condition1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Accounting Bad Condition</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_accountin_bad_condition"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Number Of Judgement1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_number_of_judgement1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total Judgement Amount1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_judgement_amount1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Last Judgement Date1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="last_judgement_date1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Total_Number Of Dishonoured1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_numberof_dishonoured1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>total_dishonoured_amount1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="total_dishonoured_amount1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Last Bounced Cheques Date1</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="last_bounced_cheques_date1"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>Rating</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="rating"></h5>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </fieldset>

                                    <fieldset class="col-md-12" style="margin-top: 30px;">
                                        <legend id="header">Account Ratings:</legend>

                                        <div class="row"
                                            style="flex-direction: row; justify-content: center; align-items: left;">
                                            <div class="col-md-12" style="flex-direction: column; align-items: left;">

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Home Loan Accounts Good</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_home_loan_accounts_good"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Home Loan Accounts Bad</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_home_loan_accounts_bad"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Auto Loan Accounts Good</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_auto_loan_acounts_good"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Auto Loan Accounts Bad</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_auto_loan_accounts_bad"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Study Loan Accounts Good</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_studyLoanAccounts_good"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Study Loan Accounts Bad</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_studyLoanAccounts_bad"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Personal Loan Accounts Good</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_personal_loan_accounts_good"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Personal Loan Accounts Bad</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_personal_loan_accounts_bad"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of CreditCard Accounts Good</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_credit_card_accounts_good"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Credit Card Accounts Bad</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_credit_card_accounts_bad"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Retail Accounts Good</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_retail_accounts_good"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Retail Accounts Bad</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_retail_accounts_bad"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Joint Loan Accounts Good</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_joint_loan_ccounts_good"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Joint Loan Accounts Bad</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_joint_loan_accounts_bad"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Telecom Accounts Good</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_telecom_ccounts_good"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Telecom Accounts Bad</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_telecom_accounts_bad"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Other Accounts Good</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_other_ccounts_good"></h5>
                                                    </div>
                                                </div>

                                                <div class="row mb-9 bg-grey text-left row-style">
                                                    <div class="col-md-6">
                                                        <h5><b>No. Of Other Accounts Bad</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5 id="no_of_other_accounts_bad"></h5>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $('#verificationTyp').on('change', function (e) {


            if (e.target.value != null) {
                // $optionval =$(this).find('dataValue:selected');
                // $('#cost').html($optionval);

                // consol.log(option)
                $("#verificationTyp option:selected").attr("dataValue");
                $('#cost').html($("#verificationTyp option:selected").attr("dataValue"));

                $('#validationLabel').text('Enter BVN');
                $('#validationNumberDiv').fadeIn();
                $('#validationNumber').prop('placeholder', "Enter BVN");

                $('#validationNumber').on('input', function (event) {
                    $('#mySubmit').prop('disabled', event.target.value.length < 9)
                })
            } else {
                $('#validationNumberDiv').fadeOut();
                _getValidationField[0].innerHTML = 'Verification type is required';
                _getValidationField[0].style.display = 'block'; return false;

            }
            _getValidationField[0].style.display = 'none';
        });
        $('.code-section-container').hide();


        function verify() {
            disableBtn('mySubmit')
            startLoadingButton('mySubmit')
            $('#bvnDetailsDiv').hide();
            $('#ecoSystemDiv').hide();
            $('#crcSearchDiv').hide();

            var uuid = $('#verificationTyp').val();
            var validationNumber = $('#validationNumber').val();
            $.ajax({
                url: '/company/loan/loan-bvn-check-verify/' + uuid + '/' + validationNumber,
                type: 'get',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {
                    stopLoadingButton('mySubmit', 'Verify');
                    enableBtn('mySubmit');

                    if (data.status) {

                        document.getElementById("bvn_verification").reset();

                        if (data.data.type == "karma") {

                            $('#bvn_number').html(data.data.bvn)
                            $('#loan_amount').html(formatMoney(data.data.loan_amount))
                            $('#reason').html(data.data.reason)
                            $('#date_of_default').html(data.data.default_date)
                            $('#karma_type').html(data.data.identification_type)
                            $('#reporting_company').html(data.data.reporting_company_name)
                            $('#company_email').html(data.data.reporting_company_email)
                            $('#bvnDetailsDiv').show();

                        } else if (data.data.type == "ecosystem") {

                            console.log(data.data);

                            $('#ecosystem_bvn').html(data.data.bvn);
                            $('#first_name').html(data.data.first_name);
                            $('#last_name').html(data.data.last_name);
                            $('#lending_lenders').html(data.data.lending_lenders);
                            $('#bvn_phone_number').html(data.data.bvn_phone_number);
                            $('#date_of_birth').html(data.data.date_of_birth);
                            $('#age').html(data.data.age);
                            $('#email').html(data.data.email);
                            $('#gender').html(data.data.gender);
                            $('#number_of_loan').html(data.data.loans);
                            $('#ecosystem_loan_amount').html(data.data.loan_amount);
                            $('#min_loan_amount').html(data.data.loan_amount_minimum);
                            $('#max_loan_amount').html(data.data.loan_amount_maximum);
                            $('#average_loan_amount').html(data.data.loan_amount_average);
                            $('#number_of_settled_loans').html(data.data.settled_loans);
                            $('#settled_loan_amount').html(data.data.settled_loan_amount);
                            $('#settled_loan_amount_paid').html(data.data.settled_loan_amount_paid);
                            $('#running_loans').html(data.data.running_loans);
                            $('#running_loan_amount').html(data.data.running_loan_amount);
                            $('#number_of_past_due_amount').html(data.data.past_due_loans);
                            $('#past_due_loan_amount').html(data.data.past_due_loan_amount);
                            $('#delayed_paid_loans').html(data.data.delayed_paid_loans);
                            $('#delayed_paid_loan_amount').html(data.data.delayed_paid_loan_amount);
                            $('#delayed_paid_loans_trials').html(data.data.delayed_paid_loans_trials);
                            $('#delayed_paid_loans_average').html(data.data.delayed_paid_loans_avg);
                            $('#loan_requests').html(data.data.loan_requests);
                            $('#ecoSystemDiv').show();
                        } else if (data.data.type == "crc_search") {
                            $('#nano_profile_first_name').html(data.data.nano_profile_first_name);
                            $('#nano_profile_last_name').html(data.data.nano_profile_last_name);
                            $('#nano_profile_gender').html(data.data.nano_profile_gender);
                            $('#nano_profile_date_of_birth').html(data.data.nano_profile_date_of_birth);
                            $('#mf_credit_has_credit_facilities').html(data.data.mf_credit_has_credit_facilities);
                            $('#mf_credit_number_of_delinqcredit_facilities').html(data.data.mf_credit_number_of_delinqcredit_facilities);
                            $('#mg_has_credit_facilities').html(data.data.mg_has_credit_facilities);
                            $('#mg_credit_number_of_delinqcredit_facilities').html(data.data.mg_credit_number_of_delinqcredit_facilities);
                            $('#credit_has_credit_facilities').html(data.data.credit_has_credit_facilities);
                            $('#credit_number_of_delinqcredit_facilities').html(data.data.credit_number_of_delinqcredit_facilities);
                            $('#report_header_email').html(data.data.report_header_email);
                            $('#report_header_product_name').html(data.data.report_header_product_name);
                            $('#credit_last_reported_date').html(data.data.credit_last_reported_date);

                            $('#crcSearchDiv').show();
                        } else if (data.data.type == "first_central") {

                            $('#header').html(data.data.personal_details_summary.header)
                            $('#consumer_id').html(data.data.personal_details_summary.consumer_id)
                            $('#reference_no').html(data.data.personal_details_summary.reference_no)
                            $('#nationality').html(data.data.personal_details_summary.nationality)
                            $('#national_id_no').html(data.data.personal_details_summary.national_id_no)
                            $('#passport_no').html(data.data.personal_details_summary.passport_no)
                            $('#driversLicense_no').html(data.data.personal_details_summary.driversLicense_no)
                            $('#bank_verification_no').html(data.data.personal_details_summary.bank_verification_no)
                            $('#pencom_id_no').html(data.data.personal_details_summary.pencom_id_no)
                            $('#otherid_no').html(data.data.personal_details_summary.otherid_no)
                            $('#birth_date').html(data.data.personal_details_summary.birth_date)
                            $('#dependants').html(data.data.personal_details_summary.dependants)
                            $('#first_central_gender').html(data.data.personal_details_summary.first_central_gender)
                            $('#marital_status').html(data.data.personal_details_summary.marital_status)
                            $('#residential_address1').html(data.data.personal_details_summary.residential_address1)
                            $('#residential_address2').html(data.data.personal_details_summary.residential_address2)
                            $('#residential_address3').html(data.data.personal_details_summary.residential_address3)
                            $('#residential_address4').html(data.data.personal_details_summary.residential_address4)
                            $('#postal_address1').html(data.data.personal_details_summary.postal_address1)
                            $('#postal_address2').html(data.data.personal_details_summary.postal_address2)
                            $('#postal_address3').html(data.data.personal_details_summary.postal_address3)
                            $('#postal_address4').html(data.data.personal_details_summary.postal_address4)
                            $('#home_telephone_no').html(data.data.personal_details_summary.home_telephone_no)
                            $('#Work_telephone_no').html(data.data.personal_details_summary.Work_telephone_no)
                            $('#cellular_no').html(data.data.personal_details_summary.cellular_no)
                            $('#email_address').html(data.data.personal_details_summary.email_address)
                            $('#employer_detail').html(data.data.personal_details_summary.employer_detail)
                            $('#property_owned_type').html(data.data.personal_details_summary.property_owned_type)
                            $('#surname').html(data.data.personal_details_summary.surname)
                            $('#fc_first_name').html(data.data.personal_details_summary.first_name)
                            $('#other_names').html(data.data.personal_details_summary.other_names)


                            $('#total_monthly_installment').html(data.data.credit_account_summary.total_monthly_installment);
                            $('#total_outstanding_debt').html(data.data.credit_account_summary.total_outstanding_debt);
                            $('#total_account_arrear').html(data.data.credit_account_summary.total_account_arrear);
                            $('#amount_arrear').html(data.data.credit_account_summary.amount_arrear);
                            $('#total_accounts').html(data.data.credit_account_summary.total_accounts);
                            $('#total_accounts1').html(data.data.credit_account_summary.total_accounts1);
                            $('#total_accountin_god_condition').html(data.data.credit_account_summary.total_accountin_god_condition);
                            $('#total_accountin_good_condition').html(data.data.credit_account_summary.total_accountin_good_condition);
                            $('#total_numberof_judgement').html(data.data.credit_account_summary.total_numberof_judgement);
                            $('#total_judgement_amount').html(data.data.credit_account_summary.total_judgement_amount);
                            $('#last_judgement_date').html(data.data.credit_account_summary.last_judgement_date);
                            $('#total_numberof_dishonoured').html(data.data.credit_account_summary.total_numberof_dishonoured);
                            $('#total_dishonoured_amount').html(data.data.credit_account_summary.total_dishonoured_amount);
                            $('#last_bounced_cheques_date').html(data.data.credit_account_summary.last_bounced_cheques_date);
                            $('#total_monthly_instalment1').html(data.data.credit_account_summary.total_monthly_instalment1);
                            $('#total_outstandingdebt1').html(data.data.credit_account_summary.total_outstandingdebt1);
                            $('#total_account_arrear1').html(data.data.credit_account_summary.total_account_arrear1);
                            $('#amount_arrear1').html(data.data.credit_account_summary.amount_arrear1);
                            $('#total_accountin_god_condition1').html(data.data.credit_account_summary.total_accountin_god_condition1);
                            $('#total_accountin_bad_condition').html(data.data.credit_account_summary.total_accountin_bad_condition);
                            $('#total_number_of_judgement1').html(data.data.credit_account_summary.total_number_of_judgement1);
                            $('#total_judgement_amount1').html(data.data.credit_account_summary.total_judgement_amount1);
                            $('#last_judgement_date1').html(data.data.credit_account_summary.last_judgement_date1);
                            $('#total_numberof_dishonoured1').html(data.data.credit_account_summary.total_numberof_dishonoured1);
                            $('#total_dishonoured_amount1').html(data.data.credit_account_summary.total_dishonoured_amount1);
                            $('#last_bounced_cheques_date1').html(data.data.credit_account_summary.last_bounced_cheques_date1);
                            $('#rating').html(data.data.credit_account_summary.rating);

                            $('#no_of_home_loan_accounts_good').html(data.data.account_rating.no_of_home_loan_accounts_good);
                            $('#no_of_home_loan_accounts_bad').html(data.data.account_rating.no_of_home_loan_accounts_bad);
                            $('#no_of_auto_loan_acounts_good').html(data.data.account_rating.no_of_auto_loan_acounts_good);
                            $('#no_of_auto_loan_accounts_bad').html(data.data.account_rating.no_of_auto_loan_accounts_bad);
                            $('#no_of_studyLoanAccounts_good').html(data.data.account_rating.no_of_studyLoanAccounts_good);
                            $('#no_of_studyLoanAccounts_bad').html(data.data.account_rating.no_of_studyLoanAccounts_bad);
                            $('#no_of_personal_loan_accounts_good').html(data.data.account_rating.no_of_personal_loan_accounts_good);
                            $('#no_of_personal_loan_accounts_bad').html(data.data.account_rating.no_of_personal_loan_accounts_bad);
                            $('#no_of_credit_card_accounts_good').html(data.data.account_rating.no_of_credit_card_accounts_good);
                            $('#no_of_credit_card_accounts_bad').html(data.data.account_rating.no_of_credit_card_accounts_bad);
                            $('#no_of_retail_accounts_good').html(data.data.account_rating.no_of_retail_accounts_good);
                            $('#no_of_retail_accounts_bad').html(data.data.account_rating.no_of_retail_accounts_bad);
                            $('#no_of_joint_loan_ccounts_good').html(data.data.account_rating.no_of_joint_loan_ccounts_good);
                            $('#no_of_joint_loan_accounts_bad').html(data.data.account_rating.no_of_joint_loan_accounts_bad);
                            $('#no_of_telecom_ccounts_good').html(data.data.account_rating.no_of_telecom_ccounts_good);
                            $('#no_of_telecom_accounts_bad').html(data.data.account_rating.no_of_telecom_accounts_bad);
                            $('#no_of_other_ccounts_good').html(data.data.account_rating.no_of_other_ccounts_good);
                            $('#no_of_other_accounts_bad').html(data.data.account_rating.no_of_other_accounts_bad);

                            $('#firstCentralDiv').show();
                        }
                    } else {
                        swal({
                            title: 'Error!',
                            text: data.message,
                            type: 'error',
                            padding: '2em'
                        })
                    }
                }
            });

        }

    </script>

</div>


@endsection

@include('inc.scripts')