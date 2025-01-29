@include('inc.styles')
<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h3>International Bank Transfer</h3>

                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">

                    <div class="col-md-8">
                        <form id="bank_transfer_form">

                            <div class="alert alert-success mb-4" id="statusMessageDiv" style="display: none;" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg></button>
                                <strong>
                                    <span id="statusMessage"></span>
                                </strong>
                                </button>

                            </div>

                            @if ($message = Session::get('success'))
                            <div class="alert alert-success mb-4" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg></button>
                                <strong> {{$message}}.</button>
                            </div>
                            @endif

                            <div class="form-group">
                                <label for="mobileMoneyPaymentMethod">Select Country</label>

                                <select class="form-control basic" id="mobileMoneyCountry" name="mobileMoneyCountry" onchange="getCountryPaymentMethod()">

                                    <option value="">::Select Country::</option>
                                    <option value="GHS">Ghana</option>
                                    <option value="KES">Kenya</option>
                                    <option value="RWF">Rwanda</option>
                                    <option value="TZS">Tanzania</option>
                                    <option value="UGX">Uganda</option>
                                    <option value="ZMW">Zambia</option>
                                    <option value="XAF">Central Africa Republic</option>
                                    <option value="ETB">Ethiopia</option>

                                </select>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="networkProvidersDiv">
                                <label for="mobileMoneyPaymentMethod">Select Network Provider</label>

                                <select class="form-control basic" id="mobileMoneyPaymentMethod" name="mobileMoneyPaymentMethod" required>
                                </select>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>
                            </div>


                            <div class="form-group ">
                                <label for="mobileMoneyPhone">Phone Number</label>
                                <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="phoneNumberPrefix"></span>
                                    </div>
                                    <input type="number" name="mobileMoneyPhone" id="mobileMoneyPhone" maxlength="10" placeholder="Enter Phone Number" class="form-control" oninput="verifyBankAccount()" required>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>

                            </div>


                            <div class="form-group mb-4">
                                <label for="amount">Recipient Name</label>
                                <input type="text" name="recipientName" id="recipientName" placeholder="Recipient Name" class="form-control" style="color: #555555;">
                                <!-- <input type="hidden" name="bankName" id="bankName"> -->

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group ">
                                <label for="mobileMoneyAmount" id="mobileMoneyAmountLabel">Amount</label>
                                <input id="mobileMoneyAmount" type="number" oninput="mymobileMoneyAmount(event)" name="mobileMoneyAmount" placeholder="Enter Amount" class="form-control" required>
                                <input type="hidden" name="mobileMOneyCurrentCompanyCurrency" id="mobileMOneyCurrentCompanyCurrency" value="{{Auth()->user()->company->currency->code}}" readonly>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-sm-6">
                                    <p id="mobileMoneyLoaderText"></p>
                                </div>
                            </div>

                            <div class="form-group" id="mobileMoneyExchangeDiv" style="display:none">
                                <label for="" class="col-sm-3 control-label" style="color:blue;"></label>
                                <div class="col-sm-6">
                                    <div class="jumbotron jumbotron-fluid" style="padding-top: 8px; padding-bottom: 8px; margin-bottom: 10px">
                                        <div class="container">
                                            <h6><b>Exchange Rates</b></h6>
                                            <p><span id="mobileMoneyBaseCurrency"></span> <span id="mobileMoneyBaseAmount"></span> =
                                                <span id="mobileMoneyExchangeCurrency"></span> <span id="mobileMoneyExchangeAmount"></span> at
                                                <span id="mobileMoneyExchangeRate"></span> rate<br>
                                                <b>Charge:</b> {{Auth()->user()->company->currency->code}} <span id="mobileMoneyExchangeCharger"></span><br>
                                                <b>Total:</b> {{Auth()->user()->company->currency->code}} <span id="mobileMoneyFinalAmountTxt"></span>
                                            </p>
                                            <p style="color: red; font-size: 18px" id="mobileMoneyBalanceStatus">
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <label for="" class="col-sm-3 control-label"></label>
                            </div>


                            <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                            <div class="form-group mb-4">
                                <label for="mobileMoneyDescription">Description</label>
                                <textarea class="form-control" placeholder="Enter Description" id="mobileMoneyDescription" name="mobileMoneyDescription" rows="3"></textarea>
                            </div>

                            <button type="button" class="btn btn-primary start" onclick="showMobileMoneyTransactionSummary()" id="initiateMobileMoneyTransfer">
                                <i class="icon-upload icon-large"></i>
                                <span>Continue</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>


        </div>
    </div>

</div>

</div>
@include('inc.scripts')