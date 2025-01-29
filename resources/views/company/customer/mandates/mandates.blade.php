@extends('layouts.app')

@section('content')
<div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12 mt-2">
                    <h4>Mandates</h4>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area text-center">
            <div class="row" style="flex: 1; justify-content:space-between; padding-right: 30px; padding-left: 30px;">
                <a id="mandateGuidelineBtn" class='text-info text-right'>How to Create Mandate</a>
                <a href='{{url("company/download-mandate-form")}}' class='text-info text-right'>Download Mandate Form</a>
            </div>
            <form id='submit_Rform' enctype="multipart/form-data">
                <div class="row pb-4">
                    <div class="form-group col-sm-12 col-md-6 offset-md-3">

                        <label for="customer">Customer</label>
                        <select name="customer" id="customer" onchange="getCustomerBalance()" class='form-control form-small' style='margin-top:5px; margin-bottom: 0;'>
                        </select>
                        <div style="display: none" id="myBankLoader">
                            <div class="d-flex" style="flex-direction: row; margin-left: 0; margin-top: -20px !important; justify-content:flex-start; align-items: center">
                                <div class="spinner-border text-success loader-xs inline-block " style="height: 20px; width: 20px;"></div>&nbsp;
                                <p style="margin-top: 10px;">Getting Customer Balance</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-6 offset-md-3" id="balanceDiv" style="display: none;">
                        <h4>Customer Balance: <span id="customer_balance"></span></h4>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4 offset-md-2">
                        <label for="payerName">Payer Name</label>
                        <input id="payerName" oninput="validity.valid||(value='');" type="text" name="payerName" placeholder="Enter Payer Name" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4">
                        <label for="payerAddress">Payer Address</label>
                        <input id="payerAddress" type="text" name="payerAddress" placeholder="Enter payerAddress" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4 offset-md-2">
                        <label for="emailAddress">Email Address</label>
                        <input id="emailAddress" oninput="validity.valid||(value='');" type="text" name="emailAddress" placeholder="Enter Address" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4">
                        <label for="phoneNumber">Phone Number</label>
                        <input id="phoneNumber" type="number" name="phoneNumber" placeholder="Enter Phone Number" class="form-control" required>
                    </div>


                    <div class="mb-4 col-sm-12 col-md-4 offset-md-2">
                        <label for="banks">Bank</label>
                        <select name="bank" id="bank" class='form-control form-small' style="width:100%">
                            <option>Select Bank</option>
                            @if($banks)
                            @foreach($banks as $bank)
                            <option value='{{$bank->bank_code}}'>{{($bank->bank_name)}}</option>
                            @endforeach
                            @endif
                        </select>

                        <span class="validation-text"></span>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4">
                        <label for="accountNumber">Account Number</label>
                        <input id="accountNumber" oninput="verifyBankAccount()" maxlength="10" type="number" name="accountNumber" placeholder="Account Number" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-8 offset-md-2">
                        <label for="accountName">Account Name</label>
                        <input id="accountName" type="text" name="accountName" placeholder="Account Name" readonly class="form-control" required>

                        <div class="col-lg-12" style="display: none;" id="myBankDetailsLoader">

                            <div class="d-flex mb-4" style="flex-direction: row; justify-content:flex-center; align-items: center;">
                                <div class="spinner-border text-success loader-xs inline-block " style="height: 20px; width: 20px;"></div>&nbsp;
                                <p style="margin-top: 10px;">Getting Account Details</p>

                            </div>
                        </div>
                        <span class="validation-text"></span>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4 offset-md-2">
                        <label for="narration">Narration </label>
                        <input id="narration" oninput="validity.valid||(value='');" type="text" name="narration" placeholder="Enter Narration" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4">
                        <label for="amount">Amount</label>
                        <input id="amount" type="number" name="amount" placeholder="Enter Amount" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4 offset-md-2">
                        <label for="variableAmounts">Variable Amount - Optional <span style="font-size: 12px;">(e.g 1000, 500, 200)</span></label>
                        <input id="variableAmounts" type="text" name="variableAmounts" placeholder="Variable Amount" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4">
                        <label for="startDate">Start Date</label>
                        <input id="startDate" type="date" name="startDate" placeholder="Enter Start Date" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4 offset-md-2">
                        <label for="endDate">End Date</label>
                        <input id="endDate" oninput="validity.valid||(value='');" type="date" name="endDate" placeholder="Enter Amount" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4">
                        <label for="firstPaymentDate">First Payout Date</label>
                        <input id="firstPaymentDate" type="date" name="firstPaymentDate" placeholder="Select First Payout Date" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-4 offset-md-2">
                        <label for="subscriberCode">Subsriber Code</label>
                        <input id="subscriberCode" type="text" name="subscriberCode" placeholder="Enter Subscriber Code" class="form-control" required>
                    </div>


                    <div class="form-group mb-4 col-sm-12 col-md-4">
                        <label for="mandateFile">Mandate Form <span style="color: red; font-size: 12px"> (PDF, JPEG, JPG, PNG)</span></label>

                        <div class="form-group mx-auto text-center">
                            <img id="previewImg2" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{config('app.url')}}placholder/default.png">
                        </div>

                        <div class="form-group col-md-9">
                            <label>Mandate Form</label>
                            <input onchange="previewFile3(this.id);" type="file" name="mandate_form" class="form-control-file" id="customFile2">
                        </div>
                    </div>

                    <div class="mb-4 col-sm-12 col-md-4  offset-md-2">
                        <label for="repeat">Repeat</label>
                        <select name="repeat" id="repeat" onchange="getMandateExplanation()" class='form-control form-small' style="width:100%">
                            <option value="">Select Repeat type</option>
                            <option value='D'>Daily</option>
                            <option value='W'>Weekly</option>
                            <option value='M'>Monthly</option>
                            <option value='Y'>Yearly</option>
                        </select>
                    </div>

                    <div class="mb-4 col-sm-12 col-md-4">
                        <label for="frequency">Frequency</label>
                        <select name="frequency" id="frequency" class='form-control form-small' style="width:100%">
                            <option>1</option>
                            <option>2</option>
                            <option>3</option>
                            <option>4</option>
                            <option>5</option>
                            <option>6</option>
                            <option>7</option>
                            <option>8</option>
                            <option>9</option>
                            <option>10</option>
                            <option>11</option>
                            <option>12</option>
                            <option>13</option>
                            <option>14</option>
                            <option>15</option>
                            <option>16</option>
                            <option>17</option>
                            <option>18</option>
                            <option>19</option>
                            <option>20</option>
                            <option>21</option>
                            <option>22</option>
                            <option>23</option>
                            <option>24</option>
                            <option>25</option>
                            <option>26</option>
                            <option>27</option>
                            <option>28</option>
                            <option>29</option>
                            <option>30</option>
                            <option>31</option>
                        </select>
                    </div>


                    <div class="form-group mb-4 col-sm-12 col-md-6 offset-md-3 mb-5 mt-2" id="mandateExplanationDiv" style="display: none;">
                        <div class="alert alert-info"><span id="mandateExplanation"></span></div>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-6 offset-md-3 mb-5 mt-2">
                        <button type='submit' id="mySumit" class="btn btn-block btn-primary" class='text-info'>Continue</button>
                    </div>

            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mandateGuidelineModal" tabindex="-1" role="dialog" aria-labelledby="mandateGuidelineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="mandateGuidelineModalLabel">Setting Up A Direct Debit Mandate</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id='add-branch'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                <div class="modal-body">
                    <p style="text-align: left; font-size: 16px">
                        In order to set up a Direct Debit Mandate a DDI (or Direct Debit Instruction) instruction form must be completed.
                    <ol style="text-align: left; font-size: 16px">
                        <li>Download the mandate form customized with your logo and business name.</li>
                        <br>
                        <li>The customer is expected to hand-fill the form, sign and submit to the company for upload.</li>
                        <br>
                        <li>The bank contacts him/her for confirmation, to prevent fraud a request. </li>
                        <br>
                        <li><b>Note:</b>Kindly select At least two (2) days from the application date as <b>START DATE</b> and at least Three (3) days from application date as <b>FIRST REPAYMENT DATE</b> </li>
                        <br>
                        <li>Variable amount should sum collection amount or leave it empty as it is optional.</li>
                        <br>

                        <li>Ensure the form is pdf or jpg, or png before you upload.</li>
                    </ol>

                    <a target="_blank" href='https://www.cbn.gov.ng/out/2013/bpsd/direct%20debit%20faq.pdf' class='text-info text-right'>Learn More</a>

                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal" id="modalCancelButton"><i class="flaticon-cancel-12"></i>
                        Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    function previewFile3(chooser) {
        console.log('hello');


        var file = $('#' + chooser).get(0).files[0];

        if (file) {
            var reader = new FileReader();

            reader.onload = function() {
                var previewer = chooser + '_preview';

                // $('#' + previewer).css('background-image', 'url("' + reader.result + '")');
                $("#previewImg2").attr("src", reader.result);

                // $("#bg-img").css("background-image", "url(" + reader.result + ")");
            }

            reader.readAsDataURL(file);
        }
    }
</script>

@endsection