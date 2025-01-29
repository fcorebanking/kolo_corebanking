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
                        <form action="" class="public_customer_form" id="public_customer_form" enctype="multipart/form-data" method="post">
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
                                    </div>
                                    <p>By clicking Start Application, I consent to {{$company_name}} obtaining information from relevant third parties as may be necessary, on my employment details, salary payment, loans and other related data, to make a decision on my loan application. I also consent to the loan amounts being deducted from my salary at source before credit to my account and any outstanding loans being recovered automatically from any other accounts linked to me in the case of default. </p>
                                    <p><input type="checkbox" name="privacy_btn" id="privacy_btn" class="" name="privacy_btn" value="privacy_btn" required></p>
                                    <p id='privacy_btn_error'></p>

                                </section>


                                <h3>Loan</h3>
                                <section>

                                    <div class="row p-3">
                                        <div class=" col-sm-6  col-md-6">
                                            <label for="accountNumber">Salary Account Number<span class="red">*</span></label>
                                            <input type="number" id="bankaccountNo" name="bank_Account_number" class="form-control" placeholder="Enter Account Number" onkeyup="verifyACC()">
                                            <div class="form-group bankmsg"></div>
                                            </div>
                                            {{-- allbank --}}
                                        <div class="col-sm-6  col-md-6">
                                            <label for="bank">Salary Bank<span class="red">*</span></label>
                                            <select name='bank' id='bank' class="form-control" >
                                                @foreach ( $allbank as $bank)
                                                    <option value='{{$bank->short_code}}'>{{$bank->bank_name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                            <div class="col-sm-6  col-md-6">
                                            <div class="form-group">
                                                <label>Principal Amount</label>
                                                <input  type="number" name="amount" class="form-control" >
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
                <p class='loanMessage'></p>
                <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

                <lottie-player src="https://assets7.lottiefiles.com/packages/lf20_ya4ycrti.json" background="transparent" speed="1" style="max-width: 400px; max-height: 400px; " class="mx-auto text-center" autoplay></lottie-player>


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

        </div>



    </div>

</body>

@endsection

@include('inc.scripts')
