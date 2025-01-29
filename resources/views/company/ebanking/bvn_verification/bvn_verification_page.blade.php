@include('inc.styles')
<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h3>Customer Verification</h3>

                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
                    <div class="col-md-8">
                        <form id="bvn_verification">

                            @if ($message = Session::get('success'))
                            <div class="alert alert-success mb-4" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg></button>
                                <strong> {{$message}}.</button>
                            </div>
                            @endif

                            @if ($message = Session::get('error'))
                            <div class="alert alert-danger mb-4" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg></button>
                                <strong> {{$message}}.</button>
                            </div>
                            @endif

                            <div class="alert alert-info">Kindly Note the folloowing fees apply <br>
                                BVN Verification: <b>NGN{{$bvn_fee}}</b> <br>
                                NIN Verification: <b>NGN{{$nin_fee}}</b> <br>
                                Driver Licence Verification: <b>NGN{{$driver_licence_fee}}</b> <br>
                            </div>

                            <div id="vNINInstruction" style="display: none;" class="alert alert-info">To get your vNIN, Kindly dial the following USSD <br>
                                *346*3*your-NIN*1138183# <br>
                                SMS will be sent to your phone with your vNIN
                            </div>

                            <div class="row mb-4" style="">

                                <div class="form-group mb-4 col-sm-12">
                                    <label for="verificationType">Verification Type</label>
                                    <select class="form-control" name="verificationTyp" id="verificationTyp" required>
                                        <option value="">-- Select Verification Type --</option>
                                        @if($company_verification_type == 3 || $company_verification_type == 8 || $company_verification_type == 6 || $company_verification_type == 4)
                                        <option value="nin">vNIN</option>
                                        @endif
                                        @if($company_verification_type == 2 || $company_verification_type == 8 || $company_verification_type == 7 || $company_verification_type == 4)
                                        <option value="bvn">BVN</option>
                                        @endif
                                        @if($company_verification_type == 5 || $company_verification_type == 7 || $company_verification_type == 6 || $company_verification_type == 8)
                                        <option value="driver_license">Driver License</option>
                                        @endif
                                    </select>
                                    <span class="validation-text"></span>

                                </div>

                                <div class="form-group mb-4 col-sm-12" style="display: none;" id="validationNumberDiv">
                                    <label for="validationNumber" id="validationLabel">Enter BVN</label>

                                    <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                                    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                    <input type="text" class="form-control" id="validationNumber" placeholder="">
                                    <span class="validation-text"></span>

                                </div>

                                <div class="" style="margin: 0 auto;">
                                    <button type="button" style="padding: 10px 100px;" onclick="verifyBVN()" id="mySubmit" class=" btn btn-primary btn-block">
                                        Submit</button>

                                </div>
                            </div>



                        </form>
                    </div>

                </div>


            </div>
        </div>


        <div class="col-xl-12 col-lg-12 col-sm-12 mt-3" id="bvnDetailsDiv" style="display: none;">
            <div class="widget-content widget-content-area">

                <h3 id="titleHeader">BVN Details</h3>

                <div class="row" id="newVerificationDiv" style="flex-direction: row; justify-content: center; align-items: left;">
                    <div class="col-md-8" style="flex-direction: column; align-items: left;">

                        <img src="" id="bvn_image">

                        <div class="row mb-9 bg-grey mt-5 text-left" id="first_name_div">
                            <div class="col-md-6">
                                <h5><b>First Name</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="first_name"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="middle_name_div">
                            <div class="col-md-6">
                                <h5><b>Middle Name</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="middle_name"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 bg-grey text-left" id="last_name_div">
                            <div class="col-md-6">
                                <h5><b>Last Name</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="last_name"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="date_of_birth_div">
                            <div class="col-md-6">
                                <h5><b>Date of Birth</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="date_of_birth"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 bg-grey text-left" id="issued_date_div">
                            <div class="col-md-6">
                                <h5><b>Issued Date</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="issued_date"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="expiry_date_div">
                            <div class="col-md-6">
                                <h5><b>Expiry Date</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="expiry_date"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 bg-grey text-left" id="phone_number1_div">
                            <div class="col-md-6">
                                <h5><b>Phone Number 1</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="phone_number1"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="phone_number2_div">
                            <div class="col-md-6">
                                <h5><b>Phone Number 2</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="phone_number2"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 bg-grey text-left" id="gender_div">
                            <div class="col-md-6">
                                <h5><b>Gender</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="gender"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="email_div">
                            <div class="col-md-6">
                                <h5><b>Email</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="email"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 bg-grey text-left" id="lga_of_origin_div">
                            <div class="col-md-6">
                                <h5><b>LGA of Origin</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="lga_of_origin"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="lga_of_residence_div">
                            <div class="col-md-6">
                                <h5><b>LGA of Residence</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="lga_of_residence"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 bg-grey text-left" id="marital_status_div">
                            <div class="col-md-6">
                                <h5><b>Marital Status</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="marital_status"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="nin_div">
                            <div class="col-md-6">
                                <h5><b>NIN</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="nin"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="license_div">
                            <div class="col-md-6">
                                <h5><b>Driver License No</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="license_no"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 bg-grey text-left" id="name_on_card_div">
                            <div class="col-md-6">
                                <h5><b>Name on Card</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="name_on_card"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="residential_address_div">
                            <div class="col-md-6">
                                <h5><b>Residential Address</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="residential_address"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 bg-grey text-left" id="state_of_origin_div">
                            <div class="col-md-6">
                                <h5><b>State of Origin</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="state_of_origin"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 text-left" id="state_of_residence_div">
                            <div class="col-md-6">
                                <h5><b>State of Residence</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="state_of_residence"></h5>
                            </div>
                        </div>

                        <div class="row mb-9 bg-grey text-left" id="watch_listed_div">
                            <div class="col-md-6">
                                <h5><b>Watch List</b></h5>
                            </div>
                            <div class="col-md-6">
                                <h5 id="watch_listed"></h5>
                            </div>
                        </div>

                    </div>

                </div>


            </div>
        </div>

        <div id="printBtnDiv" class="" style="margin: 0 auto; display: none">
            <button type="button" style="padding: 10px 100px;" onclick="printDetails()" id="mySubmit" class=" btn btn-primary btn-block">
                Print</button>
        </div>

    </div>

    <script>
        $('#verificationTyp').on('change', function(e) {

            console.log(e.target.value);
            if (e.target.value == "nin" || e.target.value == "bvn") {

                $('#validationNumber').on('input', function(event) {
                    $('#mySubmit').prop('disabled', event.target.value.length < 9)
                })
            }

            if (e.target.value == "nin") {
                $('#validationNumberDiv').fadeIn();
                $('#validationLabel').text('Enter NIN');
                $('#validationNumber').prop('placeholder', "Enter NIN");
                $('#vNINInstruction').fadeIn();
            } else if (e.target.value == "bvn") {
                $('#validationLabel').text('Enter BVN');
                $('#validationNumberDiv').fadeIn();
                $('#vNINInstruction').fadeOut();
                $('#validationNumber').prop('placeholder', "Enter BVN");
            } else if (e.target.value == "driver_license") {
                $('#validationLabel').text('Driver Licence No');
                $('#validationNumberDiv').fadeIn();
                $('#vNINInstruction').fadeOut();
                $('#validationNumber').prop('placeholder', "Enter Driver License");
            } else {
                $('#validationNumberDiv').fadeOut();
                $('#vNINInstruction').fadeOut();
                _getValidationField[0].innerHTML = 'Verification type is required';
                _getValidationField[0].style.display = 'block';
                return false;

            }
            _getValidationField[0].style.display = 'none';
        });

        function printDetails() {

            var printContent = document.getElementById("bvnDetailsDiv").innerHTML;
            var originalContent = document.body.innerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
        }
    </script>

</div>
@include('inc.scripts')