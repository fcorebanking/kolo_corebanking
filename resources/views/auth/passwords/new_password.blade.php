@extends('layouts.app')

@section('content')


<body class="form">

    <div class="form-container">
        <div class="form-form">
            <div class="form-form-wrap">
                <div class="form-container">

                    @if($status == 'successful')
                    <div class="form-content">

                        <h1 class="">Enter New Password</h1>
                        <p class="signup-link">Set a new password for you account</p>

                        <form id="password-form" class="text-left">

                            @csrf

                            <div class="form">

                                <div id="new-password-field" class="field-wrapper input">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="feather feather-user">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <input id="new_password" name="new_password" type="password"
                                        class="form-control @error('new_password') is-invalid @enderror"
                                        value="{{ old('new_password') }}" placeholder="New Password" required
                                        autocomplete="name" autofocus>
                                </div>

                                <div id="confirm-password-field" class="field-wrapper input">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="feather feather-user">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <input id="confirm_password" name="confirm_password" type="password"
                                        class="form-control @error('confirm_password') is-invalid @enderror"
                                        value="{{ old('confirm_password') }}" placeholder="Confirm Password" required
                                        autocomplete="name" autofocus>
                                    <input type="hidden" id="verification_hash" value="{{$verification_hash}}" />

                                    @error('username')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="d-sm-flex justify-content-between">
                                    <button type="button" id="submit-request" onclick="setNewPassword()"
                                        class="btn btn-primary" value="">Submit</button>

                                </div>

                            </div>
                        </form>

                        <p class="terms-conditions">© 2022 All Rights Reserved. <a href="/">{{env('APP_NAME')}}</a>
                            <a href="javascript:void(0);">Cookie Preferences</a>, <a
                                href="javascript:void(0);">Privacy</a>, and <a href="javascript:void(0);">Terms</a>.
                        </p>

                    </div>
                    @endif

                    @if($status == 'failed' || $status == 'expired')
                    <div class="form-content">

                        @if($status == 'failed' || $status == 'expired')
                        <svg width="150" height="150" version="1.1" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 130.2 130.2">
                            <circle class="path circle" fill="none" stroke="#D06079" stroke-width="6"
                                stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1" />
                            <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round"
                                stroke-miterlimit="10" x1="34.4" y1="37.9" x2="95.8" y2="92.3" />
                            <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round"
                                stroke-miterlimit="10" x1="95.8" y1="38" x2="34.4" y2="92.2" />
                        </svg>
                        @endif

                        <div class="row center" style="justify-content: center;">

                            <h3 class="mt-5">{{$title }} </h3>

                            <h5>{!! $description !!}</h5>
                        </div>

                        <p class="terms-conditions">© 2022 All Rights Reserved. <a href="/">{{env('APP_NAME')}}</a>
                            <a href="javascript:void(0);">Cookie Preferences</a>, <a
                                href="javascript:void(0);">Privacy</a>, and <a href="javascript:void(0);">Terms</a>.
                        </p>

                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="form-image">
            <div class="l-image">
            </div>
        </div>
    </div>
    @endsection

    <script>
        var _getValidationField = document.getElementsByClassName('validation-text');
        isSuccessmessage = false;

        function setNewPassword() {

            console.log("we are getting it hot");
            startLoadingButton('submit-request');

            var new_password = $('#new_password').val();
            var confirm_password = $('#confirm_password').val();
            var verification_hash = $('#verification_hash').val();

            var data = {
                new_password: new_password,
                confirm_password: confirm_password,
                verification_hash: verification_hash
            }

            $.ajax({
                url: '/password/set-new-password',
                type: 'post',
                data: data,
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {

                    stopLoadingButton('submit-request', 'Submit')

                    if (data.status == 1) {
                        console.log(data);
                        swal({
                            title: 'Success!',
                            text: data.message,
                            type: 'success',
                            padding: '2em',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                        }).then(function (result) {
                            if (result.value) {
                                window.location.replace(window.location.origin + '/login');
                            }
                        });
                    } else if (data.status == 2) {
                        swal({
                            title: 'Error!',
                            text: data.message,
                            type: 'error',
                            padding: '2em'
                        })
                    } else {
                        swal({
                            title: 'Error!',
                            text: data.message,
                            type: 'error',
                            padding: '2em',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                        }).then(function (result) {
                            if (result.value) {
                                window.location.replace(window.location.origin + '/login');
                            }
                        });
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