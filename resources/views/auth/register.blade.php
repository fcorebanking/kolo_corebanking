@extends('layouts.app')

@section('content')
    <style>
        .invalid-feedback {
            color: red;
        }
    </style>

    <div class="form-container">
        <div class="form-form">
            <div class="form-form-wrap">
                <div class="form-container">
                    <div class="form-content" style="padding-top:10px;padding-bottom:10px;">

                        <h1 class="">Get started with a <br /> mvp account</h1>
                        <p class="signup-link">Already have an account? <a href="{{ url('login') }}">Log in</a></p>
                        <form class="text-left" id='reg' action="{{ url('companyregister') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="form">
                                <div class='login-info'>
                                    <h3>Login Information</h3>
                                    <div class="custom-file-container" data-upload-id="myFirstImage">
                                        <label>Upload (Single File) <a href="javascript:void(0)"
                                                class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                                        <label class="custom-file-container__custom-file">
                                            <input type="file"
                                                class="custom-file-container__custom-file__custom-file-input" name='logo'
                                                accept="image/*">
                                            <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                                            <span class="custom-file-container__custom-file__custom-file-control"></span>
                                        </label>
                                        <div class="custom-file-container__image-preview"></div>
                                    </div>
                                    <div id="firstname-field" class="field-wrapper input">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <input id="firstname" value="{{ old('firstname') }}" required name="firstname"
                                            type="text" class="form-control @error('firstname') is-invalid @enderror"
                                            placeholder="First Name">
                                        <span class="validation-text"></span>

                                        @error('firstname')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div id="lastname-field" class="field-wrapper input">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <input id="lastname" name="lastname" type="text"
                                            class="form-control @error('lasttname') is-invalid @enderror"
                                            value="{{ old('lastname') }}" placeholder="Last Name">
                                        @error('lastname')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div id="username-field" class="field-wrapper input">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <input id="username" name="username" type="text"
                                            class="form-control  @error('username') is-invalid @enderror"
                                            value="{{ old('username') }}" placeholder="Username">
                                        @error('username')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div id="email-field" class="field-wrapper input">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-at-sign">
                                            <circle cx="12" cy="12" r="4"></circle>
                                            <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path>
                                        </svg>
                                        <input id="email" name="email" type="text"
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email') }}" placeholder="Email">
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div id="password-field" class="field-wrapper input mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-lock">
                                            <rect x="3" y="11" width="18" height="11" rx="2"
                                                ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                        <input id="password" name="password" type="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="Password">
                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div id="password2-field" class="field-wrapper input mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-lock">
                                            <rect x="3" y="11" width="18" height="11" rx="2"
                                                ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                        <input id="password2" type="password" class="form-control"
                                            placeholder="Comfirm Password">
                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>

                                    <div class="d-sm-flex justify-content-between">
                                        <div class="field-wrapper toggle-pass">
                                            <p class="d-inline-block">Show Password</p>
                                            <label class="switch s-primary">
                                                <input type="checkbox" id="toggle-password" class="d-none">
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                        <div class="field-wrapper">
                                            <button type="button" class="btn btn-primary" id="step1"
                                                value="">Next</button>
                                        </div>
                                    </div>
                                </div>
                                <div class='company-information'>
                                    <h3>Company Information</h3>
                                    <div id="companyname-field" class="field-wrapper input">

                                        <input id="companyname" name="companyname" type="text"
                                            class="form-control @error('companyname') is-invalid @enderror"
                                            value="{{ old('companyname') }}" placeholder="Company Name">
                                        @error('companyname')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div id="companyshort-field" class="field-wrapper input">
                                        <input id="companyshort" name="companyshort" type="text"
                                            class="form-control @error('companyshort') is-invalid @enderror"
                                            value="{{ old('companyshort') }}" placeholder="Company Short Name">
                                        @error('companyshort')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>

                                    <div id="companycountry-field" class="field-wrapper ">

                                        <select name="companycountry" id="companycountry"
                                            class="form-control @error('companycountry') is-invalid @enderror">
                                            <option selected="">Choose Country</option>
                                            @foreach ($country as $country2)
                                                <option value='{{ $country2->id }}'>{{ $country2->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('companycountry')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>

                                    <div id="companyphone-field" class="field-wrapper input">
                                        <input id="companyphone" name="companyphone" type="text"
                                            class="form-control  @error('companyphone') is-invalid @enderror"
                                            value="{{ old('companyphone') }}" placeholder="Company Phone">
                                        @error('companyphone')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div id="companycac_no-field" class="field-wrapper input">
                                        <input id="companycac_no" name="companycac_no" type="text"
                                            class="form-control  @error('companycac_no') is-invalid @enderror"
                                            value="{{ old('companycac_no') }}" placeholder="Company CAC No">
                                        @error('companycac_no')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div id="companylicense_no-field" class="field-wrapper input">
                                        <input id="companylicense_no" name="companylicense_no" type="text"
                                            class="form-control  @error('companylicense_no') is-invalid @enderror"
                                            value="{{ old('companylicense_no') }}" placeholder="Company License No">
                                        @error('companylicense_no')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div id="companylicense_certificate_image-field" class="field-wrapper input">
                                        <input id="companylicense_certificate_image"
                                            name="companylicense_certificate_image" type="file" class="form-control">
                                        @error('companylicense_certificate_image')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>

                                    <!-- <div class="custom-file-container" data-upload-id="myFirstImage">
                                            <label>Upload (Lincese Certificate) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                                            <label class="custom-file-container__custom-file" >
                                            <input type="file" class="custom-file-container__custom-file__custom-file-input" name="companylicense_certificate_image"  >
                                          
                                                <span class="custom-file-container__custom-file__custom-file-control"></span>
                                            </label>
                                            
                                        </div> -->



                                    <div id="companytype-field" class="field-wrapper input">


                                        <select name="companycooperativetype" id="companycooperativetype"
                                            class="form-control @error('companycooperativetype') is-invalid @enderror"
                                            value="{{ old('companycooperativetype') }}">
                                            <option selected="">Choose Cooperative Type</option>
                                            @foreach ($cooperative as $cooperative2)
                                                <option value='{{ $cooperative2->id }}'>{{ $cooperative2->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('companycooperativetype')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div id="companycurrency-field" class="field-wrapper input">


                                        <select name="companycurrency" id="companycurrency"
                                            class="form-control @error('companycurrency') is-invalid @enderror">
                                            <option selected="">Choose Currency</option>
                                            @foreach ($currency as $currency2)
                                                <option value='{{ $currency2->id }}'>{{ $currency2->code }}</option>
                                            @endforeach
                                        </select>
                                        @error('companycurrency')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>

                                    <div id="Companyaddress-field" class="field-wrapper input">

                                        <textarea name="companyaddress" id="companyaddress"
                                            class="form-control @error('companyaddress') is-invalid @enderror"> office Address
                                        </textarea>
                                        @error('companyaddress')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>
                                    <div class="field-wrapper terms_condition">
                                        <div class="n-chk new-checkbox checkbox-outline-primary">
                                            <label class="new-control new-checkbox checkbox-outline-primary">
                                                <input type="checkbox" class="new-control-input">
                                                <span class="new-control-indicator"></span><span>I agree to the <a
                                                        href="javascript:void(0);"> terms and conditions </a></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d-sm-flex justify-content-between">
                                        <div class="field-wrapper toggle-pass">

                                            <button type="button" class="btn btn-primary" id="backstep1"
                                                value="">Prev</button>

                                        </div>
                                        <div class="field-wrapper">
                                            <button type="submit" class="btn btn-primary" value="">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <p class="terms-conditions">© 2022 All Rights Reserved. <a
                                href="{{ url('/') }}">{{ env('APP_NAME') }}</a> <a
                                href="javascript:void(0);">Cookie
                                Preferences</a>, <a href="javascript:void(0);">Privacy</a>, and <a
                                href="javascript:void(0);">Terms</a>.</p>

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
