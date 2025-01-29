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

    .rightSummaryDiv {
        display: table;
        overflow: hidden;
    }

    .summaryValues {
        color: #525252;
        font-weight: normal;
        font-size: medium;
    }

    .summaryLabels {
        color: #525252;
        font-weight: bold;
        font-size: medium;
    }

    .rightSummaryItem {
        display: table-cell;
        vertical-align: middle;
    }
</style>
{{-- <div class='render_page'></div> --}}
<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <span style="font-size: 20px; color: black">Wallet Balance:</span> <span id="companyWalletBalanceAgain" style="font-size: 20px;"> {{moneyFormat(Auth()->user()->company->wallet->balance)}}</span>
                <hr>

                <h3>Nigerian Bank Transfer</h3>

                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">

                    <div class="col-md-8">
                        <form id="bank_transfer_form">
                            <input type='hidden' class='companyWalletBalancex' value='{{auth()->user()->company->wallet->balance}}'>
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

                            <div class="form-group ">
                                <label for="transferBankCode">Select Bank</label>

                                <select class="form-control basic" id="transferBankCode" name="transferBankCode" onchange="verifyBankAccount()">

                                    <option value="">::Select Bank::</option>

                                    @foreach ($banks as $bank)
                                    <option value="{{$bank->bank_code}}">{{$bank->bank_name}}
                                    </option>
                                    @endforeach

                                </select>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>
                            </div>


                            <div class="form-group ">
                                <label for="amount">Account Number</label>
                                <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                <input type="number" name="transferAccountNumber" id="transferAccountNumber" maxlength="10" placeholder="Enter Account Number" class="form-control" oninput="verifyBankAccount()" required>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>

                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12" style="display: none;" id="myBankLoader">

                                <div class="d-flex mb-4" style="flex-direction: row; justify-content:flex-center; align-items: center;">
                                    <div class="spinner-border text-success loader-xs inline-block " style="height: 20px; width: 20px;"></div>&nbsp;
                                    <p style="margin-top: 10px;">Getting Account Details</p>

                                </div>
                            </div>



                            <div class="form-group mb-4">
                                <label for="amount">Account Name</label>
                                <input type="text" name="transferAccountName" id="transferAccountName" placeholder="Account Name" class="form-control" style="color: #555555;" readonly>
                                <input type="hidden" name="bankName" id="bankName">
                                <input type="hidden" name="requestReference" id="requestReference">
                                <input type="hidden" name="sessionID" id="sessionID">

                                <div class="row">
                                    <div class="col-lg-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 n-chk">
                                    <label class="new-control new-checkbox primary new-checkbox-rounded new-checkbox-text">
                                        <input type="checkbox" id="tagCustomerCheck" onchange="calc()" class="new-control-input">
                                        <span class="new-control-indicator"></span><span class="new-chk-content"> Tag a
                                            Customer</span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group mb-4" id="tagCustomerDiv" style="display: none;">
                                <label for="customerMain">Select Customer</label>
                                <select name='customerMain' id='customerMain' class='form-control form-small' style='margin:5px'>
                                </select>
                                <label for="chargeAMT">Charge</label>
                                <input id="chargeAMT" type="number" name="chargeAMT" placeholder="Enter Charge Amount" class="form-control" required>
                            </div>
                            <div class="form-group ">
                                <label for="transferAmount">Amount</label>
                                <input id="transferAmount" type="number" name="transferAmount" placeholder="Enter Amount" class="form-control" oninput="betterFunction(event)" required>
                                <input type="hidden" name="currentCompanyCurrentCode" id="currentCompanyCurrentCode" value="{{Auth()->user()->company->currency->code}}" readonly>
                                <input type="hidden" name="companyCurrencyCode" id="companyCurrencyCode" value="NGN" readonly>


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
                                <div class="col-sm-10">
                                    <div class="jumbotron jumbotron-fluid" style="padding-top: 8px; padding-bottom: 8px; margin-bottom: 10px">
                                        <div class="container">
                                            <h6><b>Exchange Rates</b></h6><br>
                                            <div>
                                                <p><span id="baseCurrency"></span> <span id="baseAmount"></span> =
                                                    <span id="exchangeCurrency"></span> <span id="exchangeAmount"></span> at
                                                    <span id="exchangeRate"></span> rate<br>
                                                    <b>Charge:</b> {{Auth()->user()->company->currency->code}} <span id="exchangeCharger"></span><br>
                                                    <b>Total:</b> {{Auth()->user()->company->currency->code}} <span id="finalAmountTxt"></span>
                                                </p>
                                            </div>
                                            <p style="color: red; font-size: 18px" id="balanceStatus"></p>
                                        </div>
                                    </div>
                                </div>
                                <input type='hidden' id='withdrawal_charge' name='withdrawal_charge'>
                                <label for="" class="col-sm-3 control-label"></label>
                            </div>

                            <div class="form-group mb-4">
                                <label for="amount">Description</label>
                                <textarea class="form-control" placeholder="Enter Description" id="transferDescription" name="transferDescription" rows="3"></textarea>
                            </div>

                            <span class="validation-text"></span>

                            <button type="button" class="btn btn-primary start" onclick="showBankTransferSummary()" id="initiateBankTransfer">
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
<!-- Modal -->
<div class="modal fade" id="fundWalletSummary" tabindex="-1" role="dialog" aria-labelledby="fundWalletSummaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="fundWalletSummaryLabel">Transaction Summary</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id='add-branch'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                <div class="modal-body">

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-md-6 text-semibold leftSummaryDiv bg-grey">
                                    <p class="summaryLabels">Type</p>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv bg-grey">
                                    <p class="summaryValues" id="transferType">Company Wallet Transfer</p>
                                </div>
                                <hr />

                                <div class="col-md-6 text-semibold leftSummaryDiv">
                                    <p class="summaryLabels" style=" float: left;">Receipient Account</p>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv">
                                    <p> <span class="summaryValues" id="receipientName"> </span> <br>
                                        <span class="summaryValues" id="receipientAccount"> </span> <span class="summaryValues" id="receipientBankName"> </span>
                                    </p>
                                </div>
                                <hr />

                                <div class="col-md-6 text-semibold leftSummaryDiv bg-grey">
                                    <p class="summaryLabels">Description</p>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv bg-grey">
                                    <p class="summaryValues" id="mydescription"></p>
                                </div>
                                <hr />

                                <div class="col-md-6 text-semibold leftSummaryDiv">
                                    <p class="summaryLabels">Amount</p>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv">
                                    <p> {!! auth()->user()->company->currency->symbol !!} <span class="summaryValues" id="amountValue"></span> </p>
                                </div>
                                <hr />

                                <div class="col-md-6 text-semibold leftSummaryDiv bg-grey">
                                    <p class="summaryLabels">Charge
                                        </h6>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv bg-grey">
                                    <p> {!! auth()->user()->company->currency->symbol !!}
                                        <span class="summaryValues" id="chargeValue">50</span>
                                    </p>
                                </div>
                                <hr />

                                <div class="col-md-6 text-semibold leftSummaryDiv">
                                    <p class="summaryLabels">Total</p>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv">
                                    <p> {!! auth()->user()->company->currency->symbol !!}
                                        <span class="summaryValues" id="modalfinalAmount">3,050</span>
                                        <input type="hidden" id="transactionId" name="transactionId">
                                        <input type="hidden" id="transactionTypeId" name="transactionTypeId" value="8">
                                    </p>
                                </div>
                                <hr />

                                <div class="col-xl-12">
                                    <div class="form-group">
                                        <label for="static-mask1">Enter Transaction Pin</label>
                                        <input id="transactionPin" type="password" class="form-control" placeholder="">
                                    </div>
                                    <span class="validation-text"></span>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" disabled id="completeTransactionButton" onclick='companyWalletsTransfer()'>Continue</button>
                    <button class="btn" data-dismiss="modal" id="modalCancelButton"><i class="flaticon-cancel-12"></i>
                        Cancel Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($message = Session::get('success'))
<script>
    var jobs = '{{ $message }}';
    console.log("We have a value now")
    showSuccessAlert(jobs);
</script>
@else
<script>
    console.log("We dont have a value anymore")
</script>
@endif


<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <svg> ... </svg>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-text">Mauris mi tellus, pharetra vel mattis sed, tempus ultrices eros. Phasellus egestas
                    sit amet velit sed luctus. Orci varius natoque penatibus et magnis dis parturient montes, nascetur
                    ridiculus mus. Suspendisse potenti. Vivamus ultrices sed urna ac pulvinar. Ut sit amet ullamcorper
                    mi. </p>
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                <button type="button" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editfundWalletSummary" tabindex="-1" role="dialog" aria-labelledby="fundWalletSummaryLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fundWalletSummaryLabel">Edit Branch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id='edit-branch'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" id="edit_id">



                <div class="modal-body">


                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" class="form-control" id='edit_name' aria-describedby="basic-addon3" placeholder="product 1..">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Interest</span>
                        </div>
                        <input type="text" class="form-control" id='edit_interest' aria-describedby="basic-addon3" placeholder="50...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Min. Balance</span>
                        </div>
                        <input type="text" class="form-control" id='edit_min_balance' aria-describedby="basic-addon3" placeholder="100...">
                    </div>
                    <div class="input-group mb-4">
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
                </div>


                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="button" class="btn btn-primary" onclick='update_btn()'>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection