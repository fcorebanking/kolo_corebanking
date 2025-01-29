@extends('layouts.app') @section('content')

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

<div class='render_page'></div>
<!-- Modal -->
<div class="modal fade" id="fundWalletSummary" tabindex="-1" role="dialog" aria-labelledby="fundWalletSummaryLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="fundWalletSummaryLabel">Transaction Summary</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id='fund'>
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
                                        <span class="summaryValues" id="receipientAccount"> </span> <span
                                            class="summaryValues" id="receipientBankName"> </span>
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
                                    <p> {!! auth()->user()->company->currency->symbol !!} <span class="summaryValues"
                                            id="amountValue"></span> </p>
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
                                        <input type="hidden" id="transactionTypeId" name="transactionTypeId">
                                    </p>
                                </div>
                                <hr />

                                <div class="col-xl-12">
                                    <div class="form-group">
                                        <label for="static-mask1">Enter Transaction Pin</label>
                                        <input id="transactionPin" type="text" class="form-control" placeholder="">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" disabled id="completeTransactionButton"
                        onclick='companyWalletsTransfer()'>Continue</button>
                    <button class="btn" data-dismiss="modal" id="modalCancelButton"><i class="flaticon-cancel-12"></i>
                        Cancel
                        Transaction</button>
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


<div class="modal fade" id="exampleModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Create Company Virtual Card</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="fa fa-remove"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="createCardForm">
                    <div class="col-md-12">
                        @if(auth()->user()->company->currency_id == 1)
                        <div class="alert alert-info">Kindly note that naira virtual card fee is <b> ₦500</b> <br> and
                            dollar virtual card fee is <b>$5</b></div>
                        @endif
                        @if(auth()->user()->company->currency_id == 2)
                        <div class="alert alert-info">Kindly note that dollar virtual card fee is <b>$3</b></div>
                        @endif
                        <div class="form-group mb-4" id="tagCustomerDiv">
                            <label for="transferBankCode">Card Name</label>
                            <input type="text" class="form-control" id='cardCompanyName' readonly
                                aria-describedby="basic-addon3" value="{{auth()->user()->company->company_name}}"
                                placeholder="Card Name">

                            <div class="row">
                                <div class="col-lg-12">
                                    <span class="validation-text"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4" id="tagCustomerDiv">
                            <label for="companyCardAmount">Amount
                                @if(auth()->user()->company->currency_id != 1)(In USDollars) @else(In Naira)@endif
                            </label>
                            <input type="number" class="form-control" id='companyCardAmount'
                                aria-describedby="basic-addon3" placeholder="Enter Amount to Fund">

                            <div class="row">
                                <div class="col-lg-12">
                                    <span class="validation-text"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4" id="tagCustomerDiv">
                            <label for="transferBankCode">Card Currency</label>
                            <select required name='companyCardCurrency' id='companyCardCurrency' class='form-control'>
                                <option value="">::Select Card::</option>
                                @if(auth()->user()->company->currency_id == 1)
                                <option value="1">Naira Card</option>
                                @endif
                                <option value="3">Dollar Card</option>
                            </select>

                            <div class="row">
                                <div class="col-lg-12">
                                    <span class="validation-text"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn modalCancelButton" data-dismiss="modal"><i class="flaticon-cancel-12"></i>
                    Discard</button>
                <button type="button" id="createCompanyCardModalBtn" onclick="createCompanyCreate()"
                    class="btn btn-primary"> <i class="fa fa-send"></i>&nbsp;&nbsp;Create</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="fundWithdrawModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="fundWithdrawModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fundWithdrawModalLabel">Fund Virtual Card</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="fa fa-remove"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-md-12">

                    <div class="form-group mb-4" id="tagCustomerDiv">
                        <label for="fundWithdrawalAmount">Amount</label>
                        <input type="number" class="form-control" id='fundWithdrawalAmount'
                            aria-describedby="basic-addon3" placeholder="Enter Amount">
                        <input type="hidden" id='fundWithdrawType' aria-describedby="fund-withdraw-type">


                        <div class="row">
                            <div class="col-lg-12">
                                <span class="validation-text"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4" id="tagCustomerDiv">
                        <label for="transferBankCode">Description</label>
                        <textarea class="form-control" id="fundWithdrawalDescription" name="fundWithdrawalDescription"
                            rows="3"></textarea>


                        <div class="row">
                            <div class="col-lg-12">
                                <span class="validation-text"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn modalCancelButton" data-dismiss="modal"><i class="flaticon-cancel-12"></i>
                    Discard</button>
                <button type="button" id="fundWithdrawBtn" onclick="fundWithdrawalTransaction()"
                    class="btn btn-primary"><i class="fa fa-send"></i>&nbsp;&nbsp;Continue</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="blockCardModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="fundWithdrawModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="blockUnblockLabel">Fund Virtual Card</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="fa fa-remove"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-text" id="blockUnblockWarning"> </p>
            </div>

            <div class="modal-footer">
                <button class="btn modalCancelButton" data-dismiss="modal"><i class="flaticon-cancel-12"></i>
                    Discard</button>
                <button type="button" id="fundWithdrawBtn" onclick="fundWithdrawalTransaction()"
                    class="btn btn-primary"><i class="fa fa-send"></i>&nbsp;&nbsp;Continue</button>
            </div>
        </div>
    </div>
</div>

@endsection