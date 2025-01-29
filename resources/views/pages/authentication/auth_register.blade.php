@extends('layouts.app')

@section('content')
<style>
    .invalid-feedback {
        color: red;
    }
</style>

<body style="margin-top: 0;">
    <div class="form-container">
        <div class="form-form">
            <div class="form-form-wrap" style="width: 100% !important;">
                <div class="form-container" style="width: 100% !important; padding-right: 0; padding-left: 0;">
                    <div class="form-content" style="padding-top:10px; padding-bottom:10px;">

                        <h3 class=""><b>Create Company Account</b></h3>
                        <p class="signup-link">Already have an account? <a href="{{url('login')}}">Log in</a></p>


                        <!--    </p>-->
                        <!--</div>-->
                        <form class="text-left" id='reg-form' action="{{ url('companyregister') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="form">
                                <div class='login-info'>
                                    <h3>Business Onboarding</h3>
                                    <div class="custom-file-container" data-upload-id="myFirstImage">
                                        <label>Upload Logo <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                                        <label class="custom-file-container__custom-file">
                                            <input type="file" class="custom-file-container__custom-file__custom-file-input" name='logo' accept="image/*">
                                            <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                                            <span class="custom-file-container__custom-file__custom-file-control"></span>
                                        </label>
                                        <div class="custom-file-container__image-preview"></div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="firstname">First Name</label>
                                        <input type="text" name="firstname" id="firstname" placeholder="First Name" class="form-control" style="color: #555555;">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="lastname">Last Name</label>
                                        <input type="text" name="lastname" id="lastname" placeholder="Last Name" class="form-control" style="color: #555555;">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="username">Username</label>
                                        <input type="text" name="username" id="username" placeholder="Username" class="form-control" style="color: #555555;">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="form-group mb-4">
                                        <label for="email">Email</label>
                                        <input type="text" name="email" id="email" placeholder="Email" class="form-control" style="color: #555555;">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="companycountry-field" class="field-wrapper mb-4">
                                        <label for="companycountry">Country</label>

                                        <select name="companycountry" id="companycountry" class="form-control @error('companycountry') is-invalid @enderror">
                                            <option value="">Choose Country</option>
                                            @foreach($country as $country2)
                                            <option value='{{$country2->id}}'>{{$country2->name}}({{$country2->phone_code}})
                                            </option>

                                            @endforeach
                                        </select>

                                        <span class="validation-text"></span>
                                    </div>

                                    <div class="form-group mb-4" id="phone_number_div">
                                        <label for="countryphone">Phone Number</label>
                                        <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" id="phoneNumberPrefix"></span>
                                            </div>
                                            <input type="number" name="countryphone" disabled id="countryphone" maxlength="10" placeholder="Enter Valid Phone Number" class="form-control" required>
                                            <input type="hidden" id="formatted_phone" name="formatted_phone" />
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4" id="phone_number-field">
                                        <label for="password">Password</label>

                                        <input type="password" name="password" id="password" maxlength="10" placeholder="Enter Password" class="form-control" required>
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-4" id="phone_number-field">
                                        <label for="password2">Confirm Password</label>

                                        <input type="password" name="password2" id="password2" maxlength="10" placeholder="Confirm Password" class="form-control" required>
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-sm-flex justify-content-between">
                                        <div class="field-wrapper toggle-pass">
                                            <p class="d-inline-block">Show Password</p>
                                            <label class="switch s-primary">
                                                <input type="checkbox" id="toggle-password" class="d-none">
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                        <div class="">
                                            <button type="button" class="btn btn-primary" id="step1">Next</button>
                                        </div>
                                    </div>
                                </div>
                                <div class='company-information'>
                                    <h3 id="companyInfoTitle">Company Information</h3>

                                    <div class="form-group mb-4">
                                        <label for="email">Company Name</label>
                                        <input type="text" name="companyname" id="companyname" placeholder="Company Name" class="form-control" style="color: #555555;">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="form-group mb-4">
                                        <label for="email">SMS Sender ID</label>
                                        <input type="text" name="companyshort" maxlength="11" id="companyshort" placeholder="SMS Sender ID" class="form-control" style="color: #555555;">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="form-group mb-4">
                                        <label for="companycac_no">Company CAC Number</label>
                                        <input type="text" name="companycac_no" id="companycac_no" placeholder="Company CAC Number" class="form-control" style="color: #555555;">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="companylicense_no">Company License Number</label>
                                        <input type="text" name="companylicense_no" id="companylicense_no" placeholder="Company License Number" class="form-control" style="color: #555555;">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="companytype-field" class="field-wrapper input">
                                        <label for="companylicense_no">Company type</label>
                                        <select name="companycooperativetype" id="companycooperativetype" class="form-control @error('companycooperativetype') is-invalid @enderror" value="{{ old('companycooperativetype') }}">
                                            <option value="">Choose Company Type</option>
                                            @foreach($cooperative as $cooperative2)
                                            <option value='{{$cooperative2->id}}'>{{$cooperative2->name}}</option>

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
                                        <label for="companylicense_no">Currrency</label>

                                        <select name="companycurrency" id="companycurrency" class="form-control @error('companycurrency') is-invalid @enderror">
                                            <option value="">Choose Currency</option>
                                            @foreach($currency as $currency2)
                                            <option value='{{$currency2->id}}'>{{$currency2->code}}</option>

                                            @endforeach
                                        </select>
                                        @error('companycurrency')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>

                                    <div class="form-group mb-4" id="bvn-field" style="display: none">
                                        <label for="bvn">BVN</label>
                                        <input type="number" name="bvn" id="bvn" placeholder="Enter BVN" class="form-control" style="color: #555555;">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>


                                    <div id="Companyaddress-field" class="field-wrapper input">
                                        <label for="companylicense_no">Company Address</label>

                                        <textarea name="companyaddress" id="companyaddress" class="form-control @error('companyaddress') is-invalid @enderror" placeholder="Office Address"></textarea>
                                        @error('companyaddress')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                        <span class="validation-text"></span>
                                    </div>


                                    <div class="form-group mb-4" id="referer-phone-field">
                                        <label for="referer_phone">Referer Username (Optional)</label>
                                        <input type="text" name="referer_phone" value="{{$referer}}" @if($referer !='' ) readonly @endif id="referer_phone" placeholder="Enter Referer Username" class="form-control" style="color: #555555;">
                                    </div>

                                    <div class="field-wrapper terms_condition">
                                        <div class="n-chk new-checkbox checkbox-outline-primary">
                                            <label class="new-control new-checkbox checkbox-outline-primary">
                                                <input type="checkbox" id="termsCheck" class="new-control-input">
                                                <span class="new-control-indicator"></span><span>I agree to the <a href="javascript:void(0);"> terms and conditions </a></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row mt-5 justify-content-between" style="width: 100%;">

                                        <button type="button" class="btn btn-primary float-left" id="backstep1" value="">Prev</button>

                                        <button type="submit" id="submitBtn" disabled class="btn btn-primary float-right" value="">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <p class="terms-conditions">© 2022 All Rights Reserved. <a href="{{ url('/') }}">{{env('APP_NAME')}}</a> <a href="javascript:void(0);">Cookie
                                Preferences</a>, <a href="javascript:void(0);">Privacy</a>, and <a href="javascript:void(0);">Terms</a>.</p>

                    </div>
                </div>
            </div>
        </div>
        <div class="form-image">
            <div class="l-image">
            </div>
        </div>
    </div>

    <script src="{{asset('assets/js/app.js')}}"></script>
</body>

@endsection