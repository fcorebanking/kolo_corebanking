@extends('layouts.app')

@section('content')

    <body class="form">

        <div class="form-container">
            <div class="form-form">
                <div class="form-form-wrap">
                    <div class="form-container">
                        <div class="form-content">


                            <h1 class="">Password Recovery</h1>
                            <p class="signup-link">Enter your Company acocunt number and instructions will be sent to your
                                email</p>

                            @if ($message = Session::get('success'))
                                <div class="alert alert-success mb-4" role="alert"> <button type="button" class="close"
                                        data-dismiss="alert" aria-label="Close"> <svg xmlns="http://www.w3.org/2000/svg"
                                            width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    {{ $message }}
                                </div>
                            @endif
                            @if ($message = Session::get('fail'))
                                <div class="alert alert-danger mb-4" role="alert"> <button type="button" class="close"
                                        data-dismiss="alert" aria-label="Close"> <svg xmlns="http://www.w3.org/2000/svg"
                                            width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    {{ $message }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('submit-reset-request') }}" class="text-left">

                                @csrf

                                <div class="form">

                                    <div id="username-field" class="field-wrapper input">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <input id="account_number" name="account_number" type="text"
                                            class="form-control @error('account_number') is-invalid @enderror"
                                            value="{{ old('account_number') }}" placeholder="Enter Company Account Number"
                                            autocomplete="name" autofocus><br><br>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <input id="sername" name="username" type="text"
                                            class="form-control @error('username') is-invalid @enderror"
                                            value="{{ old('username') }}" placeholder="Enter Company Username"
                                            autocomplete="name" autofocus>

                                        <p class="signup-link" style="float: right; margin-top: 10px ">Have an Account? <a
                                                href="{{ url('login') }}">Login</a></p>


                                        @error('username')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>


                                    <div class="d-sm-flex justify-content-between" style="margin-top: 20px;">
                                        <div class="field-wrapper">
                                            <button type="submit" class="btn btn-primary" value="">Reset
                                                Password</button>
                                        </div>

                                    </div>

                                </div>
                            </form>

                            <p class="terms-conditions">© 2022 All Rights Reserved. <a
                                    href="/">{{ env('APP_NAME') }}</a>
                                <a href="javascript:void(0);">Cookie Preferences</a>, <a
                                    href="javascript:void(0);">Privacy</a>, and <a href="javascript:void(0);">Terms</a>.
                            </p>

                        </div>
                    </div>
                </div>
            </div>
            <div class="form-image">
                <div class="l-image">
                </div>
            </div>
        </div>
    @endsection
