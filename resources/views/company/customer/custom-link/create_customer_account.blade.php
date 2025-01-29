@extends('layouts.app')

@section('content')

<body>
    <div class="layout-px-spacing" style="flex: 1; height: 100%; background-color: #efefef;">

        <div class="row layout-top-spacing">

            @if($isReferenceValid)

            <style>
                #personal_account:hover {
                    cursor: pointer;
                    border: 2px solid green;
                }

                #cooperate_account:hover {
                    cursor: pointer;
                    border: 2px solid green;
                }

                .activated {
                    border: 2px solid green;
                }
            </style>


            <div class="col-md-10">

                @if(Session::has('account_creation_msg'))
                <p class="alert alert-info shadow">{{ Session::get('account_creation_msg') }}</p>
                @endif


                @if ($errors->any())
                @foreach ($errors->all() as $error)
                <p class="alert alert-warning shadow">{{$error}}</p>
                @endforeach
                @endif

            </div>

            <div class="p-3"></div>

            <div class="col-lg-9 layout-spacing card" id="createAccountDiv" style="margin: 0 auto;">
                <div class="statbox widget box box-shadow">
                    <div class="widget-header">
                        <div class="row mt-5">
                            <?php $logo = str_replace('public', 'storage', $company_logo); ?>
                            <img style="margin: 0 auto;" src="{{url($logo)}}" width="120" />
                            <div class="col-xl-12 col-md-12 col-sm-12 col-12 py-5">
                                <h4>Welcome to {{$company_name}}</h4>
                                <p>Kindly fill the form below to create an Account</p>
                                <div class="alert alert-info">Having issues with your Registration? Contact us via the following channels<br>
                                    <b>Email:</b> {{$supportEmail}}, <b>Phone:</b> {{$supportPhone}}
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="widget-content widget-content-area">
                        <form action="{{route('customer-registration-public')}}" class="public_customer_form" id="public_customer_form" enctype="multipart/form-data" method="post">
                            @csrf
                            <div id="registration_form_wizard">

                                <h3>Category</h3>
                                <section>

                                    <div class="container">
                                        <div class="p-3"></div>
                                        <div class="row" style="margin-bottom: 30px;">
                                            <div class="col-md-6 p-1">
                                                <div id="personal_account" onclick="selectx(this.id)" class="card py-5 shadow ">
                                                    <div class="card-body">
                                                        <h4 class="text-center">Personal Account</h4>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 p-1">
                                                <div id="coperate_account" onclick="selectx(this.id)" class="card py-5 shadow">
                                                    <div class="card-body">
                                                        <h4 class="text-center">Corporate Account</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <span id="account_category_error" class="text-danger font-weight-bold" style="margin-top: 20px;"></span>

                                        <input id="account_category" type="hidden" name="account_category">
                                    </div>



                                </section>
                                <h3>Biodata</h3>
                                <section>

                                    <div class="row">


                                        <div class="col-md-6">

                                            <div class="form-group">
                                                <label for="">Account Type</label>
                                                <select onchange="loadBanks()" name="account_type" id="account_type" class="form-control">

                                                    @if($company->currency->code == "NGN")
                                                    <option value="virtual">Virtual Account</option>
                                                    @endif

                                                    @if($company->setting->internal_account_creation == 1)
                                                    <option value="internal">Internal Account</option>
                                                    @endif

                                                </select>
                                            </div>
                                        </div>

                                        <div id="loadBankSelector" class="col-md-6 d-none">

                                            <div class="form-group ">
                                                <label for="">Select Bank</label>
                                                <select name="account_bank" id="account_bank" class="form-control">
                                                    @foreach ($banks as $bank)
                                                    <option value="{{$bank->bank_code}}" @if($selectedbank) @if($selectedbank->bank_code == $bank->bank_code) {{"selected"}} else {{"selected"}} "" @endif @endif>{{$bank->bank_name}}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">

                                            <div class="form-group">
                                                <label for="">Product Type</label>
                                                <select name="account_id" id="" class="form-control">

                                                    @foreach ($account_types as $account_type)
                                                    <option value="{{$account_type->id}}">
                                                        {{$account_type->savings_product}}
                                                    </option>
                                                    @endforeach

                                                </select>
                                            </div>
                                        </div>
                                        <div id="company_name" class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Company Name</label>
                                                <input type="text" class="form-control  " name="company_name" value="" placeholder="Company Name">

                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div id="reg_number" class="form-group">
                                                <label for="">Reg Number</label>
                                                <input type="hidden" name="company_reference" id="company_reference" value="{{$company_reference}}">
                                                <input type="text" class="form-control  " name="reg_number" value="" placeholder="Reg Number">

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div id="tin_number" class="form-group">
                                                <label for="">TIN Number (Optional)</label>
                                                <input type="text" class="form-control  " name="tin_Number" value="" placeholder="TIN Number">

                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Firstname</label>
                                                <input id="first_name" type="text" class="form-control  " name="first_name" value="" placeholder="Firstname">
                                                <span id="first_name_error" class="text-danger font-weight-bold"></span>
                                                @error('first_name')
                                                <div class="text-danger">
                                                    {{$message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Lastname</label>
                                                <input type="text" class="form-control" name="last_name" id="last_name" placeholder="Lastname">
                                                <span id="last_name_error" class="text-danger font-weight-bold"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Phone</label>
                                                <input type="tel" class="form-control" name="phone" id="phone" placeholder="234-8067-89**">
                                                <span id="phone_error" class="text-danger font-weight-bold"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Address</label>
                                                <input type="text" class="form-control" name="address" id="address" placeholder="Address">
                                                <span id="address_error" class="text-danger font-weight-bold"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Gender</label>
                                                <select name="gender" id="gender" class="form-control">
                                                    <option value="">Select Gender</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Not Say">Rather Not Say</option>
                                                </select>
                                                <span id="gender_error" class="text-danger font-weight-bold"></span>

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Branch</label>
                                                <select name="branch" id='branch' onchange="renderStaff()" class="form-control">
                                                    <option value="">Select Branch</option>

                                                    @if(count($branch))
                                                    @foreach($branch as $branchData)
                                                    <option value="{{Crypt::encryptString($branchData->id)}}">
                                                        {{$branchData->branch_name}}
                                                    </option>

                                                    @endforeach

                                                    @endif

                                                </select>
                                                <span id="branch_error" class="text-danger font-weight-bold"></span>

                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Email</label>
                                                <input type="text" class="form-control" name="email" id="email" placeholder="Email">
                                                <span id="email_error" class="text-danger font-weight-bold"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label>BVN</label>
                                            <input type="text" minlength="11" maxlength="11" style="letter-spacing: 0.5em;" class="form-control text-center" name="bvn" id="BVN" placeholder="BVN">
                                            <span id="BVN_error" class="text-danger font-weight-bold"></span>
                                            <span id="BVN_error2" class="text-danger font-weight-bold"></span>
                                            <span id="BVN_error3" class="text-danger font-weight-bold"></span>
                                            <div class='bvnmsg'></div>
                                        </div>
                                    </div>


                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="">Country</label>
                                            <select name="country" id='country' class="form-control">
                                                @foreach ($countries as $country)
                                                <option value="{{$country->id}}">{{$country->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="">National Identification Number (NIN) - Optional </label>
                                            <input type="text" class="form-control" name="national_id" id="national_id" placeholder="National Identity">
                                            <span id="email_error" class="text-danger font-weight-bold"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Username</label>
                                                <input type="text" class="form-control" name="username" id="username" placeholder="Username">
                                                <span id="username_error" class="text-danger font-weight-bold"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Password</label>
                                            <input type="password" class="form-control" name="password" id="password" placeholder="Enter Password">
                                            <span id="password_error" class="text-danger font-weight-bold"></span>

                                            <div class='passwordmsg'></div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Date Of Birth</label>
                                                <input type="date" class="form-control" name="dob" id="dob" placeholder="DOB" min='1930-01-01' max="2004-01-01">
                                                <script>
                                                    document.getElementById("myDate").max = "2014-01-01";
                                                </script>
                                                <span id="dob_error" class="text-danger font-weight-bold"></span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label>Transaction Pin (4 Digits)</label>
                                            <input type="number" minlength="4" maxlength="4" style="letter-spacing: 0.5em;" class="form-control text-center" name="transaction_pin" id="transaction_pin" placeholder="Transaction Pin">
                                            <span id="transaction_pin_error" class="text-danger font-weight-bold"></span>
                                            <div class='pinmsg'></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Referral : (Optional)</label>
                                                <input type="text" class="form-control" name="referral" id="referral" placeholder="Referral" value="{{@$request->referral}}" <?php if ($request->referral) echo "readonly"; ?>>
                                                <span id="referral_error" class="text-danger font-weight-bold"></span>
                                            </div>
                                        </div>
                                    </div>

                                </section>

                                <h3>Next of Kin Details</h3>
                                <section>

                                    <div class="row">

                                        <div class="col-md-6 mx-auto">

                                            <div class="form-group">
                                                <label for="">Next of Kin (Name)</label>
                                                <input type="text" class="form-control" name="nok_name" placeholder="Next of Kin Name">
                                            </div>

                                            <div class="form-group">
                                                <label>Next of Kin (Phone)</label>
                                                <input type="tel" class="form-control" name="nok_phone" placeholder="234-8067-89**">
                                            </div>

                                            <div class="form-group">
                                                <label>Next of Kin (Address)</label>
                                                <input type="text" class="form-control" name="nok_address" placeholder="Next of Kin Address">
                                            </div>
                                        </div>
                                    </div>





                                </section>
                                <h3>Documents Upload</h3>
                                <section>

                                    <div class="row p-3">
                                        <div class='col-sm-12 alert alert-danger'>Please note that Document upload is optional</div>
                                        <div class="col-md-6">

                                            <div class="form-group mx-auto text-center">
                                                <img id="previewImg" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{config('app.url')}}placholder/default.png">
                                            </div>

                                            <div class="form-group col-md-9">
                                                <label>Upload Passport</label>
                                                <input onchange="previewFile4(this.id);" type="file" name="passport" class="form-control-file" id="customFile">

                                            </div>
                                        </div>


                                        <div class="col-md-6 p-3">


                                            <div class="form-group mx-auto text-center">
                                                <img id="previewImg2" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{config('app.url')}}placholder/default.png">
                                            </div>

                                            <div class="form-group col-md-9">
                                                <label>Upload Valid ID</label>
                                                <input onchange="previewFile3(this.id);" type="file" name="valid_id" class="form-control-file" id="customFile2">

                                            </div>

                                        </div>

                                    </div>


                                </section>


                            </div>


                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-12 table responsive card card-body fail_page">
                <div class="alert alert-danger msg"></div>
            </div>
            <div style="min-height: 100vh;" class="col-md-12 table responsive card card-body success_page">
                <div class="p-5"></div>
                <h4 class="text-center msg">Account Created Successfully</h4>
                <h6 class="text-center accoutno">Account Number: </h6>
                <h6 class="text-center bank">with love </h6>
                <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

                <lottie-player src="https://assets7.lottiefiles.com/packages/lf20_ya4ycrti.json" background="transparent" speed="1" style="max-width: 400px; max-height: 400px; " class="mx-auto text-center" autoplay></lottie-player>

                <div class="row mt-7" style="margin: 0 auto; ">
                    <button class="btn btn-primary" onclick="location.reload()">Create Another Account</button>
                </div>

            </div>
            <script>
                function previewFile4(chooser) {
                    console.log('hello');


                    var file = $('#' + chooser).get(0).files[0];

                    if (file) {
                        var reader = new FileReader();

                        reader.onload = function() {
                            var previewer = chooser + '_preview';

                            // $('#' + previewer).css('background-image', 'url("' + reader.result + '")');
                            $("#previewImg").attr("src", reader.result);

                            // $("#bg-img").css("background-image", "url(" + reader.result + ")");
                        }

                        reader.readAsDataURL(file);
                    }
                }

                function previewFile3(chooser) {
                    console.log('hello');


                    var file = $('#' + chooser).get(0).files[0];

                    if (file) {
                        var reader = new FileReader();

                        reader.onload = function() {
                            var previewer = chooser + '_preview';

                            // $('#' + previewer).css('background-image', 'url("' + reader.result + '")');
                            $("#previewImg2").attr("src", reader.result);

                            // $("#bg-img").css("background-image", "url(" + reader.result + ")");
                        }

                        reader.readAsDataURL(file);
                    }
                }

                function previewFile2(chooser) {
                    console.log('hello');


                    var file = $('#' + chooser).get(0).files[0];

                    if (file) {
                        var reader = new FileReader();

                        reader.onload = function() {
                            var previewer = chooser + '_preview';

                            // $('#' + previewer).css('background-image', 'url("' + reader.result + '")');
                            $("#previewImg3").attr("src", reader.result);

                            // $("#bg-img").css("background-image", "url(" + reader.result + ")");
                        }

                        reader.readAsDataURL(file);
                    }
                }

                function previewFile1(chooser) {
                    console.log('hello');


                    var file = $('#' + chooser).get(0).files[0];

                    if (file) {
                        var reader = new FileReader();

                        reader.onload = function() {
                            var previewer = chooser + '_preview';

                            // $('#' + previewer).css('background-image', 'url("' + reader.result + '")');
                            $("#previewImg4").attr("src", reader.result);

                            // $("#bg-img").css("background-image", "url(" + reader.result + ")");
                        }

                        reader.readAsDataURL(file);
                    }
                }

                function selectx(id) {

                    if (id == 'personal_account') {


                        document.getElementById('personal_account').classList.add("activated");
                        document.getElementById('coperate_account').classList.remove("activated");

                        document.getElementById('company_name').classList.add('d-none');
                        document.getElementById('reg_number').classList.add('d-none');
                        document.getElementById('tin_number').classList.add('d-none');

                        document.getElementById('account_category').value = "personal";

                        document.getElementById('customer_bvn').classList.remove('d-none');

                        document.getElementById('company_bvn').classList.add('d-none');

                    }
                    if (id == 'coperate_account') {



                        document.getElementById('coperate_account').classList.add("activated");
                        document.getElementById('personal_account').classList.remove("activated");

                        document.getElementById('company_name').classList.remove('d-none');
                        document.getElementById('reg_number').classList.remove('d-none');
                        document.getElementById('tin_number').classList.remove('d-none');

                        document.getElementById('account_category').value = "coperate";

                    }
                }

                function loadBanks() {

                    // alert('loadbanks');

                    document.getElementById('loadBankSelector').classList.remove("d-none");

                    if (document.getElementById('account_type').value == "internal") {

                        document.getElementById('loadBankSelector').classList.add("d-none");
                    }

                }

                loadBanks();
            </script>

            @endif
            @if(isset($errortype))
            @if($errortype)
            <div class="col-xl-12 col-lg-12 col-sm-12 ">
                <div class="widget-content widget-content-area" style=" margin-top: 120px; justify-content: center; align-items: center;">
                    <div class="col-md-3" style="align-self: center; margin: auto;">
                        <img src="{{url('/wba_images/empty_image.svg')}}" height="auto" width="100%" />
                        <h3 class="mt-5"><b>Account Number does not exist!</b></h3>
                        <h5>Invalid Referral link</h5>
                    </div>
                </div>
            </div>

            @endif
            @else
            @if(!$isReferenceValid)
            <div class="col-xl-12 col-lg-12 col-sm-12 ">
                <div class="widget-content widget-content-area" style=" margin-top: 120px; justify-content: center; align-items: center;">

                    <div class="col-md-3" style="align-self: center; margin: auto;">
                        <img src="{{url('/wba_images/empty_image.svg')}}" height="auto" width="100%" />
                        <h3 class="mt-5"><b>Company Not Found</b></h3>
                        <h5>Kindly Enter a Valid Company reference to Continue</h5>
                    </div>


                </div>
            </div>

            @endif
            @endif


        </div>

        @if($isReferenceValid)
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
                        <input type="hidden" name="company_reference" id="company_reference" value="{{$company_reference}}">

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
                            <button type="button" class="btn btn-primary" id="initiateFundWallet" onclick='initiateTransacion()'>Continue</button>
                            <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Cancel</button>
                        </div>
                    </form>
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
                    _getValidationField[0].style.display = 'block';
                    return false;
                } else {
                    _getValidationField[0].style.display = 'none';
                }
                if (fundMethod == "") {
                    _getValidationField[1].innerHTML = 'Please select the fund method';
                    _getValidationField[1].style.display = 'block';
                    return false;
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

                $('#fundWalletSummary').modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                })
            } else {
                $('#fundWalletBankTransferSummary').modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                })

            }
            // renderFundWallet();
        }

        function initiateTransacion() {
            var amount = $('#amount').val();
            var fundMethod = $('#fundMethod').val();
            var phone = $('#phone').val();
            var customerReference = $('#company_reference').val();

            data_info = {
                finalAmount: amount,
                transaction_type: 22,
                fund_method: fundMethod,
                phone: phone,
                company_reference: customerReference
            };


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
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {

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
                error: function(jqXHR, status, error) {

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
                xhr: function() {
                    var xhr = $.ajaxSettings.xhr();
                    xhr.onprogress = function e() {
                        // For downloads
                        if (e.lengthComputable) {
                            console.log(e.loaded / e.total);
                            $(".toast-msg").html(e.loaded / e.total);
                        }
                    };
                    xhr.upload.onprogress = function(e) {
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
                $_getValidationField[6].style.display = 'block';
                return false;
            } else {
                $_getValidationField[6].style.display = 'none';
            }
            if ($staff_lastname == "") {
                $_getValidationField[7].innerHTML = 'Last Name must be filled out';
                $_getValidationField[7].style.display = 'block';
                return false;
            } else {
                $_getValidationField[7].style.display = 'none';
            }
            if ($staff_username == "") {
                $_getValidationField[8].innerHTML = 'Username must be filled out';
                $_getValidationField[8].style.display = 'block';
                return false;
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


            $data_info = {
                id: $user_id,
                branch: $staff_branch,
                firstname: $staff_fname,
                lastname: $staff_lastname,
                email: $staff_email,
                username: $staff_username,
                phone: $staff_phone
            };
            ajax_operation('post', 'staff/ajax_updatestaff', $data_info);
            renderFundWallet();
        }


        function loadBanks() {

            document.getElementById('loadBankSelector').classList.remove("d-none");

            if (document.getElementById('account_type').value == "internal") {
                document.getElementById('loadBankSelector').classList.add("d-none");
            }
        }
    </script>
</body>

@endsection

@include('inc.scripts')