@extends('layouts.app')

@section('content')

<body>
    <div class="layout-px-spacing" style="flex: 1; height: 100%; background-color: #efefef;">

        <div class="row layout-top-spacing">

            @if($isReferenceValid)

            <div class="col-xl-12 col-lg-12 col-sm-12 ">
                <div class="widget-content widget-content-area" style=" margin-top: 120px;">

                    <h4>Payment link IFO</h4>
                    <h3 style="margin-bottom: 30px;"> {{$company_name}}</h3>

                    <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
                        <div class="col-md-8 col-lg-6 col-sm-11 col-11">
                            <form id="fund_wallet">

                                @if ($message = Session::get('success'))
                                <div class="alert alert-success mb-4" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    <strong> {{$message}}.</button>
                                </div>
                                @endif

                                @if ($message = Session::get('error'))
                                <div class="alert alert-danger mb-4" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    <strong> {{$message}}.</button>
                                </div>
                                @endif

                                <div class="form-group mb-4">
                                    <label for="amount">Fund Amount (Currency: {{$currency->code}})</label>
                                    <input id="amount" type="number" name="amount" placeholder="Enter Amount"
                                        class="form-control" required>
                                </div>
                                <span class="validation-text"></span>

                                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                                <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                <div class="form-group mb-4">
                                    <label for="exampleFormControlSelect1">Fund Method</label>
                                    <select class="form-control" name="fundMethod" id="fundMethod" required>
                                        <option value="">-- Select Fund Method --</option>
                                        <option value="3">Flutterwave</option>
                                        <option value="6">Bank Transfer</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <span class="validation-text"></span>
                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="phone">Phone Number</label>
                                    <input id="phone" type="number" name="phone" placeholder="Enter Phone"
                                        class="form-control" required>
                                </div>
                                <span class="validation-text"></span>
                                <div class="form-group mb-4">
                                    <button type="button" onclick="showTransactionSummary()" id="mySumit"
                                        class="mt-4 btn btn-primary">
                                        Submit</button>
                                </div>
                            </form>
                        </div>

                    </div>


                </div>
            </div>

            @endif

            @if(!$isReferenceValid)


            <div class="col-xl-12 col-lg-12 col-sm-12 ">
                <div class="widget-content widget-content-area"
                    style=" margin-top: 120px; justify-content: center; align-items: center;">

                    <div class="col-md-3" style="align-self: center; margin: auto;">
                        <img src="{{url('/wba_images/empty_image.svg')}}" height="auto" width="100%" />
                        <h3 class="mt-5"><b>Company Not Found</b></h3>
                        <h5>Kindly Enter a Valid Company reference to Continue</h5>
                    </div>


                </div>
            </div>

            @endif

        </div>

        @if($isReferenceValid)
        <!-- Modal -->
        <div class="modal fade" id="fundWalletSummary" tabindex="-1" role="dialog"
            aria-labelledby="fundWalletSummaryLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fundWalletSummaryLabel">Transaction Summary</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                        </button>
                    </div>
                    <form id='add-branch'>
                        <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="company_reference" id="company_reference"
                            value="{{$company_reference}}">

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
                                    <p class="mb-4">To make payment to <b>{{$company_name}}</b>, Kindly Transfer to the
                                        Account Below</p>
                                    <h5 style="color: #777777;">{{$bank_name}}</h5>
                                    <h4><span id="paragraph-copy1"><b>{{$virtual_account}}</b></span></h4>

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
        @endif


    </div>

    <script>
        var _getValidationField = document.getElementsByClassName('validation-text');
        isSuccessmessage = false;

        function showTransactionSummary() {

            var amount = $('#amount').val();
            var fundMethod = $('#fundMethod').val();
            var token = $('.token').val();
            $('.validation-text').html();

            console.log(fundMethod);

            if (fundMethod == 3) {

                if (amount == "") {
                    _getValidationField[0].innerHTML = 'Amount is required';
                    _getValidationField[0].style.display = 'block'; return false;
                } else {
                    _getValidationField[0].style.display = 'none';
                }
                if (fundMethod == "") {
                    _getValidationField[1].innerHTML = 'Please select the fund method';
                    _getValidationField[1].style.display = 'block'; return false;
                } else {
                    _getValidationField[1].style.display = 'none';
                }

                var fundMethodValue = '';
                if (fundMethod == '3') {
                    fundMethodValue = "Flutterwave"
                }

                console.log(fundMethodValue + " " + amount)


                $('#fundingMethodValue').text(fundMethodValue)
                $('#amountValue').text(amount)
                $('#chargeValue').text('0')
                $('#totalValue').text(amount)

                $('#fundWalletSummary').modal({ backdrop: 'static', keyboard: false, show: true })
            } else {
                $('#fundWalletBankTransferSummary').modal({ backdrop: 'static', keyboard: false, show: true })

            }
            // renderFundWallet();
        }

        function initiateTransacion() {
            var amount = $('#amount').val();
            var fundMethod = $('#fundMethod').val();
            var phone = $('#phone').val();
            var customerReference = $('#company_reference').val();

            data_info = { finalAmount: amount, transaction_type: 22, fund_method: fundMethod, phone: phone, company_reference: customerReference };


            $('#initiateFundWallet').prop("disabled", true);
            $('#initiateFundWallet').html(
                '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
            );

            console.log(data_info);

            $.ajax({
                url: '/transaction/initiate-payment-link',
                type: 'post',
                data: data_info,
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {

                    $('#initiateFundWallet').prop("disabled", false);
                    $('#initiateFundWallet').html('Continue');

                    if (data.status == 1) {
                        console.log(data);
                        window.location.href = data.link;
                    } else {
                        swal({
                            title: 'Error!',
                            text: data.message,
                            type: 'error',
                            padding: '2em'
                        })
                    }

                },
                error: function (jqXHR, status, error) {

                    $('.toast').addClass("bg-danger");
                    var msg = '';
                    if (jqXHR.status === 0) {
                        msg = 'Not connected\n Verify Network.';
                    } else if (jqXHR.status == 404) {
                        msg = 'Requested page not found. [404]';
                    } else if (jqXHR.status == 500) {
                        msg = 'Internal Server Error [500].';
                    } else {
                        msg = 'Uncaught Error.\n' + error;
                    }

                    $('.toast-msg').html(msg);
                },
                xhr: function () {
                    var xhr = $.ajaxSettings.xhr();
                    xhr.onprogress = function e() {
                        // For downloads
                        if (e.lengthComputable) {
                            console.log(e.loaded / e.total);
                            $(".toast-msg").html(e.loaded / e.total);
                        }
                    };
                    xhr.upload.onprogress = function (e) {
                        // For uploads
                        if (e.lengthComputable) {
                            console.log(e.loaded / e.total);
                        }
                    };
                    return xhr;
                }
            });

        }

        function btnupdate() {
            $staff_fname = $('#edit-first-name').val();
            $staff_lastname = $('#edit-last-name').val();
            $staff_username = $('#edit-username').val();
            $staff_email = $('#edit-email').val();
            $staff_phone = $('#edit-phone').val();
            $staff_branch = $('#edit-branch').val();
            $user_id = $('#user_id').val();

            $token = $('.token').val();
            $('.validation-text').html();


            if ($staff_fname == "") {
                $_getValidationField[6].innerHTML = 'First Name must be filled out';
                $_getValidationField[6].style.display = 'block'; return false;
            } else {
                $_getValidationField[6].style.display = 'none';
            }
            if ($staff_lastname == "") {
                $_getValidationField[7].innerHTML = 'Last Name must be filled out';
                $_getValidationField[7].style.display = 'block'; return false;
            } else {
                $_getValidationField[7].style.display = 'none';
            }
            if ($staff_username == "") {
                $_getValidationField[8].innerHTML = 'Username must be filled out';
                $_getValidationField[8].style.display = 'block'; return false;
            } else {
                $_getValidationField[8].style.display = 'none';
            }
            if ($staff_email == "") {
                $_getValidationField[9].innerHTML = 'Email must be filled out';
                $_getValidationField[9].style.display = 'block';
                return false;
            } else {
                $_getValidationField[9].style.display = 'none';
            }
            if ($staff_phone == "") {
                $_getValidationField[10].innerHTML = 'Phone must be filled out';
                $_getValidationField[10].style.display = 'block';
                return false;
            } else {
                $_getValidationField[10].style.display = 'none';
            }
            if ($staff_branch == "") {
                $_getValidationField[11].innerHTML = 'Branch must be filled out';
                $_getValidationField[11].style.display = 'block';
                return false;
            } else {
                $_getValidationField[11].style.display = 'none';
            }


            $data_info = { id: $user_id, branch: $staff_branch, firstname: $staff_fname, lastname: $staff_lastname, email: $staff_email, username: $staff_username, phone: $staff_phone };
            ajax_operation('post', 'staff/ajax_updatestaff', $data_info);
            renderFundWallet();
        }
    </script>
</body>

@endsection

@include('inc.scripts')