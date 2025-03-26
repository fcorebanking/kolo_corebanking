@extends('layouts.app')

@section('content')
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


    <div class="col-md-12">
        <h4 class="">Create account</h4>

        @if (Session::has('account_creation_msg'))
            <p class="alert alert-info shadow">{{ Session::get('account_creation_msg') }}</p>
        @endif


        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <p class="alert alert-warning shadow">{{ $error }}</p>
            @endforeach
        @endif


    </div>

    <div class="p-3"></div>

    <div class="col-lg-12 layout-spacing" id="createAccountDiv">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12 py-5">
                        <h4>Create Customer Account</h4>
                    </div>
                </div>
            </div>

            <div class="widget-content widget-content-area">
                <form action="{{ route('create_customer_accountx') }}" class="examplez" id="examplez"
                    enctype="multipart/form-data" method="post">
                    @csrf
                    <div id="example-basic">

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
                                <span id="account_category_error" class="text-danger font-weight-bold"
                                    style="margin-top: 20px;"></span>

                                <input id="account_category" type="hidden" name="account_category">
                            </div>



                        </section>
                        <h3>Biodata</h3>
                        <section>

                            <div class="row">


                                <div class="col-md-6">

                                    <div class="form-group">
                                        <label for="">Account Type</label>
                                        <select onchange="loadBanks()" name="account_type" id="account_type"
                                            class="form-control">

                                            @if (auth()->user()->company->currency->code == 'NGN')
                                                <option value="virtual">Virtual Account</option>
                                            @endif

                                            @if (auth()->user()->company->setting->internal_account_creation == 1)
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
                                                <option value="{{ $bank->bank_code }}"
                                                    @if ($selectedbank) @if ($selectedbank->bank_code == $bank->bank_code) {{ 'selected' }} else {{ 'selected' }} "" @endif
                                                    @endif>{{ $bank->bank_name }}
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
                                                <option value="{{ $account_type->id }}">{{ $account_type->savings_product }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>
                                </div>
                                <div id="company_name" class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Company Name</label>
                                        <input type="text" class="form-control  " name="company_name" value=""
                                            placeholder="Company Name">

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div id="reg_number" class="form-group">
                                        <label for="">Reg Number</label>
                                        <input onchange="check_cac()" type="text" class="form-control  "
                                            name="reg_number" value="" placeholder="Reg Number">

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div id="tin_number" class="form-group">
                                        <label for="">TIN Number (Optional)</label>
                                        <input type="text" class="form-control  " name="tin_Number" value=""
                                            placeholder="TIN Number">

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Firstname</label>
                                        <input id="first_name" type="text" class="form-control  " name="first_name"
                                            value="" placeholder="Firstname">
                                        <span id="first_name_error" class="text-danger font-weight-bold"></span>
                                        @error('first_name')
                                            <div class="text-danger">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Lastname</label>
                                        <input type="text" class="form-control" name="last_name" id="last_name"
                                            placeholder="Lastname">
                                        <span id="last_name_error" class="text-danger font-weight-bold"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Phone</label>
                                        <input type="tel" class="form-control" name="phone" id="phone"
                                            placeholder="0806789**">
                                        <span id="phone_error" class="text-danger font-weight-bold"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Address</label>
                                        <input type="text" class="form-control" name="address" id="address"
                                            placeholder="Address">
                                        <span id="address_error" class="text-danger font-weight-bold"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Gender</label>
                                        <select name="gender" id="" class="form-control">
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Not Say">Rather Not Say</option>
                                        </select>
                                    </div>
                                </div>
                                @if (cando('select-cutomer-branch|root'))
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="">Branch</label>
                                            <select name="branch" id='branch' onchange="renderStaff()"
                                                class="form-control">
                                                <option value="">Select Branch</option>

                                                @if (count($branch))
                                                    @foreach ($branch as $branchData)
                                                        <option value="{{ Crypt::encryptString($branchData->id) }}">
                                                            {{ $branchData->branch_name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <span id="branch_error" class="text-danger font-weight-bold"></span>

                                        </div>
                                    </div>
                                @else
                                    <input type='hidden' value='{{ Crypt::encryptString(auth()->user()->branch_id) }}'
                                        name="branch" id='branch'>
                                @endif


                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Country</label>
                                        <select name="country" id='country' class="form-control">

                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">National Identification Number (NIN) - Optional</label>
                                        <input type="text" class="form-control" name="national_id" id="national_id"
                                            placeholder="National Identity">
                                        <span id="email_error" class="text-danger font-weight-bold"></span>
                                    </div>
                                </div>

                                @if (cando('select-cutomer-branch|root'))
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="">Account Officer</label>
                                            <select name="officer" id='officer' class="form-control"></select>
                                        </div>
                                        <script>
                                            function renderStaff() {
                                                $branch = $('#branch').val();
                                                $.ajax({
                                                    url: '/company/{{ auth()->user()->company->short_name }}/branch/branch_staff/' + $branch,
                                                    type: 'GET',
                                                    dataType: 'json',
                                                    success: function(data) {
                                                        $('select[name="officer"]').empty();

                                                        data.forEach(function(staff) {

                                                            $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff
                                                                .first_name + ' ' + staff.last_name + ' - ' + staff.username +
                                                                '</option>')
                                                        });
                                                    }
                                                });
                                            }
                                        </script>
                                    </div>
                                @else
                                    <input type='hidden' value='{{ auth()->user()->uuid }}' name="officer"
                                        id='officer'>
                                @endif


                                <div class="col-md-6">

                                    <div class="form-group">
                                        <label for="">Email</label>
                                        <input type="text" class="form-control" name="email" id="email"
                                            placeholder="Email">
                                        <span id="email_error" class="text-danger font-weight-bold"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Username (Optional)</label>
                                        <input type="text" class="form-control" name="username" id="username"
                                            placeholder="Username">
                                        <span id="username_error" class="text-danger font-weight-bold"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">

                                    <div id="customer_bvn" class="form-group d-none">

                                    </div>
                                    <div id="company_bvn" class="form-group ">

                                    </div>
                                    <label>Bank Verification Number</label>
                                    <input type="text" minlength="11" maxlength="11" style="letter-spacing: 0.2em;"
                                        class="form-control text-center" name="bvn" id="BVN"
                                        placeholder="Bank Verification Number">
                                    <span id="BVN_error" class="text-danger font-weight-bold"></span>
                                    <span id="BVN_error2" class="text-danger font-weight-bold"></span>
                                    <span id="BVN_error3" class="text-danger font-weight-bold"></span>
                                    <div class='bvnmsg'></div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="push_wmn" id="inlineRadio1"
                                            value="1">
                                        <label class="form-check-label" for="inlineRadio1">Push WMN (ON)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="push_wmn" id="inlineRadio2"
                                            value="0" checked>
                                        <label class="form-check-label" for="inlineRadio2">Push WMN (OFF)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="sms" id="inlineRadio1"
                                            value="1">
                                        <label class="form-check-label" for="inlineRadio1">SMS ON</label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="sms" id="inlineRadio2"
                                            value="0" checked>
                                        <label class="form-check-label" for="inlineRadio2">SMS OFF</label>
                                    </div>

                                    <div class="p-3"></div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">DOB</label>
                                        <input type="date" class="form-control" name="dob" id="dob"
                                            placeholder="DOB" min='1930-01-01'>
                                        <script>
                                            document.getElementById("myDate").max = "2014-01-01";
                                        </script>

                                        <span id="dob_error" class="text-danger font-weight-bold"></span>

                                    </div>

                                    <div class=" p-1">
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Registration Date</label>
                                        <input type="date" class="form-control" name="registration_date"
                                            placeholder="Registration Date" min='1930-01-01'>

                                    </div>

                                    <div class=" p-1">
                                    </div>
                                </div>

                        </section>

                        <h3>Next of Kin Details</h3>
                        <section>

                            <div class="row">

                                <div class="col-md-6 mx-auto">
                                    <div class='alert alert-info'>Please note that Next of Kin is optional</div>
                                    <div class="form-group">
                                        <label for="">Next of Kin (Name)</label>
                                        <input type="text" class="form-control" name="nok_name"
                                            placeholder="Next of Kin Name">
                                    </div>

                                    <div class="form-group">
                                        <label>Next of Kin (Phone)</label>
                                        <input type="tel" class="form-control" name="nok_phone"
                                            placeholder="234-8067-89**">
                                    </div>

                                    <div class="form-group">
                                        <label>Next of Kin (Address)</label>
                                        <input type="text" class="form-control" name="nok_address"
                                            placeholder="Next of Kin Address">
                                    </div>
                                </div>
                            </div>

                        </section>
                        <h3>Documents Upload</h3>
                        <section>

                            <div class="col-md-12 table responsive card card-body fail_page">
                                <div class="alert alert-danger msg"></div>
                            </div>

                            <div class='col-sm-12 alert alert-info'>Please note that Document upload is optional</div>

                            <div class="row p-3">

                                <div class="col-md-6">

                                    <div class="form-group mx-auto text-center">
                                        <img id="previewImg"
                                            style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;"
                                            class="shadow" src="{{ config('app.url') }}placholder/default.png">
                                    </div>

                                    <div class="form-group col-md-9">
                                        <label>Upload Passport</label>
                                        <input onchange="previewFile4(this.id);" type="file" name="passport"
                                            class="form-control-file" id="customFile" accept=".jpg,.png,.jpeg">

                                    </div>
                                </div>


                                <div class="col-md-6 p-3">


                                    <div class="form-group mx-auto text-center">
                                        <img id="previewImg2"
                                            style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;"
                                            class="shadow" src="{{ config('app.url') }}placholder/default.png">
                                    </div>

                                    <div class="form-group col-md-9">
                                        <label>Upload Valid ID</label>
                                        <input onchange="previewFile3(this.id);" type="file" name="valid_id"
                                            class="form-control-file" id="customFile2" accept=".jpg,.png,.jpeg">

                                    </div>

                                </div>




                                <!-- <div class="col-md-6 p-3">


                                            <div class="form-group mx-auto text-center">
                                                <img id="previewImg3" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{ config('app.url') }}placholder/default.png" >
                                            </div>

                                            <div class="form-group col-md-9">
                                                <label >Upload Other Documents (Optional)</label>
                                                <input onchange="previewFile2(this.id);" type="file" name="other_doc" class="form-control-file" id="customFile3">

                                            </div>


                                        </div>


                                        <div class="col-md-6 p-3">


                                            <div class="form-group mx-auto text-center">
                                                <img id="previewImg4" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{ config('app.url') }}placholder/default.png" >
                                            </div>

                                            <div class="form-group col-md-9">
                                                <label >Signature (Optional)</label>
                                                <input onchange="previewFile1(this.id);" type="file" name="signature" class="form-control-file" id="customFile4">

                                            </div>



                                        </div> -->


                            </div>


                        </section>


                    </div>


                </form>
            </div>
        </div>
    </div>

    <div class="col-md-12 table responsive card card-body fail_page">
    </div>
    <div style="min-height: 100vh;" class="col-md-12 table responsive card card-body success_page">
        <div class="p-5"></div>
        <h4 class="text-center msg">Account Created Successfully</h4>
        <h6 class="text-center accoutno">Account Number: </h6>
        <h6 class="text-center bank">smiles </h6>
        <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

        <lottie-player src="https://assets7.lottiefiles.com/packages/lf20_ya4ycrti.json" background="transparent"
            speed="1" style="max-width: 400px; max-height: 400px; " class="mx-auto text-center"
            autoplay></lottie-player>

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

            document.getElementById('loadBankSelector').classList.remove("d-none");

            if (document.getElementById('account_type').value == "internal") {
                document.getElementById('loadBankSelector').classList.add("d-none");
            }
        }

        function check_cac() {
            $reg_number = $('#reg_number').val();
            console.log($reg_number);
            $.ajax({
                url: '/check_cac_data',
                type: 'post',
                data: {
                    reg_number: $reg_number
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    console.log(data);



                }
            });
        }
    </script>
@endsection
