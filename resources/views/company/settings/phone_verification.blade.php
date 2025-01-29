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
        font-weight: bold;
        font-size: medium;
    }

    .rightSummaryItem {
        display: table-cell;
        vertical-align: middle;
    }
</style>

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
            <form id='add-branch'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                <div class="modal-body">

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-md-6 text-semibold leftSummaryDiv bg-grey">
                                    <h6 style="color: #aaaaaa;">Type</h6>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv bg-grey">
                                    <p class="summaryValues">Wallet Funding</p>
                                </div>
                                <hr />

                                <div class="col-md-6 text-semibold leftSummaryDiv">
                                    <h6 style="color: #aaaaaa;">Funding Method</h6>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv">
                                    <p class="summaryValues" id="fundingMethodValue">Card</p>
                                </div>
                                <hr />

                                <div class="col-md-6 text-semibold leftSummaryDiv bg-grey">
                                    <h6 style="color: #aaaaaa;">Amount</h6>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv bg-grey">
                                    <p class="summaryValues" id="amountValue">3,000</p>
                                </div>
                                <hr />

                                <div class="col-md-6 text-semibold leftSummaryDiv">
                                    <h6 style="color: #aaaaaa;">Charge</h6>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv">
                                    <p class="summaryValues" id="chargeValue">50</p>
                                </div>
                                <hr />

                                <div class="col-md-6 text-semibold leftSummaryDiv bg-grey">
                                    <h6 style="color: #aaaaaa;">Total</h6>
                                </div>
                                <div class="col-md-6 text-right leftSummaryDiv bg-grey">
                                    <p class="summaryValues" id="totalValue">3,050</p>
                                </div>
                                <hr />

                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="initiateFundWallet"
                        onclick='initiateTransacion()'>Continue</button>
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="fundWalletBankTransferSummary" tabindex="-1" role="dialog"
    aria-labelledby="fundWalletBankTransferSummary" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="fundWalletSummaryLabel">Payment Via Bank Transfer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="jumbotron" style="padding: 20px;">
                        <div class="clipboard copy-txt">
                            <p class="mb-4">To make payment to <b></b>, Kindly Transfer to the Account Below</p>
                            <h5 style="color: #777777;"></h5>
                            <h4><span id="paragraph-copy1"><b></b></span></h4>

                            <a class="mb-1 btn btn-primary" href="javascript:;" data-clipboard-action="copy"
                                data-clipboard-target="#paragraph-copy1"><i class="fa fa-clipboard"></i> Copy
                                Account Number</a>
                            <a class="mb-1 btn btn-default" data-dismiss="modal"><i class="fa fa-remove"></i>
                                Close</a>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@if ($message = Session::get('success'))
<script>
    var jobs = '{{ $message }}';
    console.log("We have a value now")
    showSuccessAlert('success', jobs);
</script>
@endif

@if ($message = Session::get('error'))
<script>
    var jobs = '{{ $message }}';
    console.log("We dont have a value anymore")
    showSuccessAlert('error', jobs);
</script>
@endif

<div class='render_page'></div>


<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
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

<div class="modal fade" id="editfundWalletSummary" tabindex="-1" role="dialog" aria-labelledby="fundWalletSummaryLabel"
    aria-hidden="true">
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
                        <input type="text" class="form-control" id='edit_name' aria-describedby="basic-addon3"
                            placeholder="product 1..">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Interest</span>
                        </div>
                        <input type="text" class="form-control" id='edit_interest' aria-describedby="basic-addon3"
                            placeholder="50...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Min. Balance</span>
                        </div>
                        <input type="text" class="form-control" id='edit_min_balance' aria-describedby="basic-addon3"
                            placeholder="100...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Frequency</span>
                        </div>

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

@include('inc.scripts')
