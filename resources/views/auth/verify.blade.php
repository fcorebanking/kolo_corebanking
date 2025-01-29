@extends('layouts.app')

@section('content')

<body style="flex: 1; height: 100%; background-color: #efefef;">
    <div class="layout-px-spacing" >

        <div class="row layout-top-spacing">


            <div class="col-xl-12 col-lg-12 col-sm-12 ">
                <div class="widget-content widget-content-area"
                    style=" margin-top: 120px; justify-content: center; align-items: center;">

                    <div class="col-md-6" style="align-self: center; margin: auto;">
                        <img src="{{url('/wba_images/email.png')}}" height="140" width="140" />
                        <h3 class="mt-5"><b>Verifcation Email Sent</b></h3>
                        <h5>A verification Email has been sent to your mail. Kindly Check and Verify your account to
                            continue your Registration process</h5>

                        <div class="col-md-12">
                            <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                            <a href="{{ url('ma-resend-email') }}" id="resendBtn"  class="btn btn-primary mt-3">Resend
                                Email</a>
                            <a href="{{ route('logout') }}" class="btn btn-danger mt-3">Logout</a>
                        </div>
                    </div>


                </div>
            </div>

        </div>


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


            startLoadingButton('resendBtn');


            $.ajax({
                url: '/ma-resend-email',
                type: 'get',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {

                    stopLoadingButton('resendBtn', 'Resend Email')

                    if (data.status == 1) {
                        console.log(data);
                        swal({
                            title: 'Success!',
                            text: data.message,
                            type: 'success',
                            padding: '2em'
                        })
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



        function startLoadingButton(buttonId) {
            $('#' + buttonId).prop("disabled", true);
            $('#' + buttonId).html('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...');
        }

        function disableBtn(buttonId) {
            $('#' + buttonId).prop("disabled", true);
        }

        function enableBtn(buttonId) {
            $('#' + buttonId).prop("disabled", false);
        }

        function stopLoadingButton(buttonId, text) {
            $('#' + buttonId).prop("disabled", false);
            $('#' + buttonId).html(' ' + text + ' ');
        }

    </script>
</body>

@endsection

@include('inc.scripts')