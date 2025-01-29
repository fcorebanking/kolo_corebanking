@include('inc.styles')
<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h3>Business Transfer</h3>
                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
                    <div class="col-md-8">
                        <form id="fund_wallet">

                            <div class="alert alert-success mb-4" id="statusMessageDiv" style="display: none;"
                                role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
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


                            <div class="form-group ">
                                <label for="amount">Account Number</label>
                                <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                <input type="number" name="accountNumber" id="accountNumber" maxlength="10"
                                    placeholder="Enter Account Number" class="form-control"
                                    oninput="verifyWalletAccountNumber(this.value)" required>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row" style="display: none;" id="myLoader">

                                <div class="d-flex mb-4"
                                    style="flex-direction: row; justify-content:flex-center; align-items: center;">
                                    <div class="spinner-border text-success loader-xs inline-block "
                                        style="height: 20px; width: 20px;"></div>&nbsp;
                                    <p style="margin-top: 10px;">Getting Account Details</p>

                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="amount">Account Name</label>
                                <input type="text" name="accountName" id="accountName" placeholder="Account Name"
                                    class="form-control" style="color: #555555;" readonly>
                                <input type="hidden" name="currentCompanyCurrentCode" id="currentCompanyCurrentCode"
                                    value="{{Auth()->user()->company->currency->code}}" readonly>
                                <input type="hidden" name="companyCurrencyCode" id="companyCurrencyCode" readonly>
                                <input type="hidden" name="exchangeAmountValue" id="exchangeAmountValue" readonly>
                                <input type="hidden" name="finalAmount" id="finalAmount" readonly>
                                <input type="hidden" name="exchangeCharge" id="exchangeCharge" readonly>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group ">
                                <label for="amount">How much do you want to send in
                                    <b>{{auth()->user()->company->currency->code}}</b></label>
                                <input id="amount" type="number" oninput="betterFunction(event)" name="amount"
                                    placeholder="Enter Amount" class="form-control" required>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                            <div class="row">
                                <div class="col-sm-6">
                                    <p id="loaderText"></p>
                                </div>
                            </div>

                            <div class="form-group" id="exchangeDiv" style="display:none">
                                <label for="" class="col-sm-3 control-label" style="color:blue;"></label>
                                <div class="col-sm-6">
                                    <div class="jumbotron jumbotron-fluid"
                                        style="padding-top: 8px; padding-bottom: 8px; margin-bottom: 10px">
                                        <div class="container">
                                            <p><span id="baseCurrency"></span> <span id="baseAmount"></span> =
                                                <span id="exchangeCurrency"></span> <span id="exchangeAmount"></span> at
                                                <span id="exchangeRate"></span> rate<br>
                                                <b>Charge:</b> {{Auth()->user()->company->currency->code}} <span
                                                    id="exchangeCharger"></span><br>
                                                <b>Total:</b> {{Auth()->user()->company->currency->code}} <span
                                                    id="finalAmountTxt"></span>
                                            </p>
                                            <p style="color: red; font-size: 18px" id="balanceStatus"></p>
                                        </div>
                                    </div>
                                </div>
                                <label for="" class="col-sm-3 control-label"></label>
                            </div>

                            <div class="form-group mb-4">
                                <label for="description">Description</label>
                                <textarea class="form-control" placeholder="Enter Description" id="description"
                                    name="description" rows="3"></textarea>
                                <span class="validation-text"></span>
                            </div>


                            <button type="button" id="initiateCompanyTransactionBtn"
                                class="btn btn-primary btn-lg start" onclick="showTransactionSummary()">
                                <i class="fa fa-send"></i>&nbsp;&nbsp;Continue
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