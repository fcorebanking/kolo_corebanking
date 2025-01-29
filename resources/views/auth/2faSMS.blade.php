@extends('layouts.app')

@section('content')
    <div class="layout-px-spacing" >
        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12 ">
                <div class="widget-content widget-content-area"
                    style=" margin-top: 120px; justify-content: center; align-items: center;">
                        <h3 class="mt-5"><b>Enter OTP</b></h3>
                        <div class="col-md-12">
                            <div class='form-group'>
                                <input class='form-control' name='otp' id='otp'>
                            </div>
                            <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
                            <button type='button'  class="btn btn-warning mt-3" onclick="resendOTPfactor()">Resend OTP</button>
                            <button type='button'  class="btn btn-danger mt-3" onclick="verifyOTPfactor()">Verify</button>
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@include('inc.scripts')
