@extends('layouts.app')

@section('content')

<div style="min-height: 100vh;" class="col-md-12 table responsive card card-body">

    @if(Session::has('message'))
    <p class="alert alert-info">{{ Session::get('message') }}</p>
    @endif

    <h4>Customer Details</h4>
    <div class="container">
        @if($user->email_verified_at == null)
        <button onclick="userresetEmail('{{$user->uuid}}')" class="btn btn-warning float-right btn-lg " id='verify-email'>Verify Email</button>
        @endif
        <button onclick="userpswreset('{{$user->uuid}}')" class="btn btn-warning float-right btn-lg">Send Reset
            Password</button>
        <button onclick="userpinreset('{{$user->uuid}}')" class="btn btn-danger float-right btn-lg">Send Reset
            PIN</button>
        <button onclick="send_form()" class="btn btn-success float-right btn-lg">Update</button>
    </div>

    <form id="data" action="{{route('update_profilex')}}" method="post">

        @csrf
        <center>
            <h6>Bio Data</h6>
        </center>
        <hr>

        <div class="row">
            <input type="hidden" class="form-control" name="user_id" value="{{$user->uuid}}">

            <div class="col-md-6">

                <div class="form-group">
                    <label>Firstname</label>
                    <input type="text" class="form-control" name="first_name" value="{{$user->first_name}}">
                </div>

            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Lastname</label>
                    <input type="text" class="form-control" name="last_name" value="{{$user->last_name}}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-control" name="phone" value="{{$user->phone}}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" class="form-control" name="email" value="{{$user->email}}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" name="username" value="{{$user->username}}">
                </div>
            </div>
            {{--  --}}
            <div class="col-md-6">
                <div class="form-group">

                    <label for="country_id">Country</label>

                    <select name="country_id" id="country_id" class="form-control">
                        @foreach ($countries as $country)
                            <option value="{{$country->id}}" {{$country->id==$user->country_id?'selected':''}}>{{$country->name}}</option>
                        @endforeach

                    </select>

                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="business_name">Nick Name</label>
                    <input type="text" class="form-control" name="nike_name" value="{{$user->nike_name}}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="occupation">Occupation</label>
                    <input type="text" class="form-control" name="occupation" value="{{$user->occupation}}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="marital_status">Marital Status</label>
                    <select name='marital_status' class='form-control'>
                        <option value='single'  {{$user->marital_status=='single'?'selected':''}}>Single</option>
                        <option value='married' {{$user->marital_status=='married'?'selected':''}}>Married</option>
                        <option value='divorce' {{$user->marital_status=='divorce'?'selected':''}}>Divorce</option>
                        <option value='widow' {{$user->marital_status=='widow'?'selected':''}}>Widow</option>
                        <option value='widower' {{$user->marital_status=='widower'?'selected':''}}>Widower</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="alternative_phone">Alternative Phone</label>
                    <input type="text" class="form-control" name="alternative_phone" value="{{$user->alternative_phone}}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="state_of_origin">State of Origin</label>
                    <input type="text" class="form-control" name="state_of_origin" value="{{$user->state_of_origin}}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="business_name">Business Name</label>
                    <input type="text" class="form-control" name="business_name" value="{{$user->business_name}}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="home_address">Home Address</label>
                    <textarea class="form-control" name="home_address" >{{$user->home_address}}</textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">

                    <label for="whatsapp_status_id">Whatsapp Control</label>

                    <select name="whatsapp_status_id" id="whatsapp_status_id" class="form-control">
                        <option value="1" {{$user->whatsapp_status_id=='1'?'selected':''}}>Allow - Full access</option>
                        <option value="2" {{$user->whatsapp_status_id=='2'?'selected':''}}>Disable access</option>
                        <option value="3" {{$user->whatsapp_status_id=='3'?'selected':''}}>Check only balance </option>

                    </select>

                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">

                    <label for="ussd_status_id">USSD Control</label>

                    <select name="ussd_status_id" id="ussd_status_id" class="form-control">
                        <option value="1" {{$user->ussd_status_id=='1'?'selected':''}}>Allow - Full access</option>
                        <option value="2" {{$user->ussd_status_id=='2'?'selected':''}}>Disable access</option>
                        <option value="3" {{$user->ussd_status_id=='3'?'selected':''}}>Check only balance </option>

                    </select>

                </div>
            </div>
            <div class="col-md-6 row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="bvn">BVN</label>
                        <input type="text" class="form-control" id="bvn" name="bvn" value="{{$user->bvn}}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group text-center" style="display: flex; flex-direction: column; justify-content: center; height: 100%;">
                        <button type="button" onclick='verifyBVN("{{$user->uuid}}")' class="btn btn-primary">Verify</button>
                    </div>
                </div>
            </div>


            <div class="col-md-6 ">

                <div class="form-group">
                    <label>NIN</label>
                    <input type="text" class="form-control" name="nin" value="{{$user->nin}}">
                </div>
            </div>
            <div class="col-md-6 ">

                <div class="form-group">
                    <label>DOB</label>
                    <input type="date" class="form-control" name="dob" value="{{$user->dob}}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Gender </label>
                    <select name="sex" id="" class="form-control">
                        <option value="male" {{$user->sex=='male'?'selected':''}}>Male</option>
                        <option value="female" {{$user->sex=='female'?'selected':''}}>Female</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Change Status</label>
                    <select name="user_status" id="" class="form-control">
                        @if($status)
                        @foreach($status as $statusData)
                        <option value="{{$statusData->id}}" {{($statusData->
                            id==$user->status_id)?'selected':''}}>{{$statusData->name}}</option>
                        @endforeach
                        @endif

                    </select>
                </div>
            </div>
            <div class="col-md-6">
                @if(cando('root'))

                <div class="form-group">
                    <label for="">Account Tier</label>
                    <select name="account_tier" id='account_tier' class="form-control">
                        <option value="">Change Account Tier</option>

                        @php $count = 0; @endphp

                        @if(count($tiers))
                        @foreach($tiers as $tier)
                        @php $count++ @endphp
                        <option value="{{$tier->id}}" @if($user->kyc_level_id == $tier->id) selected @endif> Tier {{$count}}</option>
                        @endforeach

                        @endif


                    </select>
                </div>

                @else
                <input type='hidden' value='{{auth()->user()->branch_id}}' name="branch" id='branch'>
                @endif
            </div>
            <div class="col-md-12">
                <p class='alert alert-primary'> If you dont want to change customer's branch or account officer then
                    leave it blank!</p>
            </div>
            <div class="col-md-6">
                @if(cando('root|set-customer-branch-officer'))

                <div class="form-group">
                    <label for="">Branch ({{$user->branch->branch_name}})</label>
                    <select name="branch" id='branch' onchange="renderStaff()" class="form-control">
                        <option value="">Change Branch</option>

                        @if(count($branch))
                        @foreach($branch as $branchData)
                        <option value="{{Crypt::encryptString($branchData->id)}}">{{$branchData->branch_name}}</option>

                        @endforeach

                        @endif


                    </select>
                </div>

                @else
                <input type='hidden' value='{{auth()->user()->branch_id}}' name="branch" id='branch'>
                @endif
            </div>
            @if(cando('root|set-customer-branch-officer'))
            <div class="col-md-6">
                <div class="form-group">
                    <label for="">Account Officer({{isset($user->myOfficer->first_name) ? $user->myOfficer->first_name :
                        ""}} {{isset($user->myOfficer->last_name) ? $user->myOfficer->last_name : ""}})</label>
                    <select name="officer" id='officer' class="form-control">

                    </select>
                </div>
                <script>
                    function renderStaff() {
                        $branch = $('#branch').val();
                        $.ajax({
                            url: '/company/{{auth()->user()->company->short_name}}/branch/branch_staff/' + $branch,
                            type: 'GET',
                            dataType: 'json',
                            success: function(data) {
                                $('select[name="officer"]').empty();

                                data.forEach(function(staff) {

                                    $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
                                });
                            }
                        });
                    }
                </script>
            </div>
            @else
            <input type='hidden' value='{{auth()->user()->uuid}}' name="officer" id='officer'>
            @endif

            <div class="col-md-6">

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" class="form-control" name="address" value="{{$user->address}}">
                </div>
            </div>

            <div class="col-md-6">

                <div class="form-group">
                    <label for="">Product Type</label>
                    <select name="account_id" id="" disabled class="form-control">

                        @foreach ($account_types as $account_type)
                        <option value="{{$account_type->id}}" {{$account_type->id === $user->wb_account_details[0]->account_product_id ? 'selected' : ''}}>{{$account_type->savings_product}}
                        </option>
                        @endforeach

                    </select>
                </div>
            </div>




        </div>



        <center>
            <h3>Next of Kin Details</h3>
        </center>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Next of Kin name:</label>
                    <input type="text" class="form-control" name="nok_name" value="@if(isset($user->nok_details->nok_name)){{$user->nok_details->nok_name}} @endif">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Next of Kin phone:</label>
                    <input type="text" class="form-control" name="nok_phone" value="@if(isset($user->nok_details->nok_phone)){{$user->nok_details->nok_phone}} @endif">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Next of Kin address:</label>
                    <input type="text" class="form-control" name="nok_address" value="@if(isset($user->nok_details->nok_address)){{$user->nok_details->nok_address}} @endif">
                </div>

            </div>
        </div>

        <div class="col-md-12">

            <div class="row p-3">

                <div class="col-md-6">

                    <div class="form-group mx-auto text-center">
                        <?php $profile_pic = url(str_replace('public', 'storage', $user->profile_pic)); ?>
                        <img id="previewImg" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{$profile_pic}}">
                    </div>

                    <div class="form-group col-md-9">

                        <label>Upload Passport</label>
                        <input onchange="previewFile4(this.id);" type="file" name="passport" class="form-control-file" id="customFile">
                        <span class='profile_pic'></span>
                    </div>
                </div>

                <div class="col-md-6 p-3">
                    <?php $validid = url(str_replace('public', 'storage', $user->validID_file)); ?>


                    <div class="form-group mx-auto text-center">
                        <img id="previewImg2" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{$validid}}">
                    </div>

                    <div class="form-group col-md-9">

                        <label>Upload Valid ID (Optional)</label>
                        <input onchange="previewFile3(this.id);" type="file" name="valid_id" class="form-control-file" id="customFile2">
                        <span class='validID_file'></span>
                    </div>
                    @if($user->validID_file !=null)

                    <a href='{{$validid}}' target='_blank' class='btn btn-warning'>View Valid ID</a>
                    @endif
                </div>


                <div class="col-md-6 p-3">
                    <?php $otherID_file = url(str_replace('public', 'storage', $user->otherID_file)); ?>


                    <div class="form-group mx-auto text-center">
                        <img id="previewImg3" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{$otherID_file}}??{{config('app.url')}}placholder/default.png">
                    </div>

                    <div class="form-group col-md-9">

                        <label>Upload Other Documents (Optional)</label>
                        <input onchange="previewFile2(this.id);" type="file" name="other_doc" class="form-control-file" id="customFile3">
                        <span class='otherID_file'></span>
                    </div>

                    @if($user->otherID_file !=null)

                    <a href='{{$otherID_file}}' target='_blank' class='btn btn-warning'>View Other ID</a>
                    @endif
                </div>
                <?php $signature = url(str_replace('public', 'storage', $user->signature)); ?>

                <div class="col-md-6 p-3">


                    <div class="form-group mx-auto text-center">
                        <img id="previewImg4" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{$signature}}??{{config('app.url')}}placholder/default.png">
                    </div>

                    <div class="form-group col-md-9">

                        <label>Signature (Optional)</label>
                        <input onchange="previewFile1(this.id);" type="file" name="signature" class="form-control-file" id="customFile4">
                        <span class='signature'></span>
                    </div>

                    @if($user->signature !=null)

                    <a href='{{$signature}}' target='_blank' class='btn btn-warning'>View Signature</a>
                    @endif

                </div>

                <?php $utilityBillID_file = url(str_replace('public', 'storage', $user->utilityBillID_file)); ?>
                <div class="col-md-6 p-3">


                    <div class="form-group mx-auto text-center">
                        <img id="previewImg5" style="height: 150px; width: 150px; object-fit: cover; border-radius: 20px;" class="shadow" src="{{$utilityBillID_file}}??{{config('app.url')}}placholder/default.png">
                    </div>

                    <div class="form-group col-md-9">
                        <span class='utilityBillID_file'></span>
                        <label>Utility File(Optional)</label>
                        <input onchange="previewFile5(this.id);" type="file" name="utilityfile" class="form-control-file" id="customFile5">

                    </div>
                    @if($user->utilityBillID_file !=null)

                    <a href='{{$utilityBillID_file}}' target='_blank' class='btn btn-warning'>View Utility Bill File</a>
                    @endif

                </div>


            </div>

        </div>



</div>

<button class="d-none" id="real" type="submit">Submit</button>

</form>

<script>
    function verifyBVN(uuid){
        const bvn =$('#bvn').val();
        var form_data = new FormData(); // Creating object of FormData class
        form_data.append("bvn", bvn)
        form_data.append("uuid", uuid)
        $.ajax({
                url: '/company/verify-customer-info/bvn-info',
                type: 'POST',
                data: form_data,
                headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(data) {
                    var status;
                    if (data.status) {
                        data.status = 'bg-success'
                        status = 'success';
                        $complete = 'Success';
                        swal(
                            $complete,
                            data.message,
                            status
                        )
                    } else {
                        data.status = 'bg-danger';
                        status = 'error';
                        $complete = 'Error!';
                        swal(
                            $complete,
                            data.message,
                            status
                        )
                    }
                },
                cache: false,
                contentType: false,
                processData: false
            });
    }
    function previewFile5(chooser) {

        var file = $('#' + chooser).get(0).files[0];

        if (file) {
            var reader = new FileReader();

            reader.onload = function() {
                var previewer = chooser + '_preview';

                // $('#' + previewer).css('background-image', 'url("' + reader.result + '")');
                $("#previewImg5").attr("src", reader.result);

                // $("#bg-img").css("background-image", "url(" + reader.result + ")");
            }

            reader.readAsDataURL(file);
            var file_data = $("#customFile5").prop("files")[0]; // Getting the properties of file from file field
            dofileupload(file_data, '{{$user->uuid}}', 'utilityBillID_file');
        }
    }

    function previewFile4(chooser) {

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
            var file_data = $("#customFile").prop("files")[0]; // Getting the properties of file from file field
            dofileupload(file_data, '{{$user->uuid}}', 'profile_pic');
        }
    }

    function previewFile3(chooser) {


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
            var file_data = $("#customFile2").prop("files")[0]; // Getting the properties of file from file field
            dofileupload(file_data, '{{$user->uuid}}', 'validID_file');
        }
    }

    function previewFile2(chooser) {

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
            var file_data = $("#customFile3").prop("files")[0]; // Getting the properties of file from file field
            dofileupload(file_data, '{{$user->uuid}}', 'otherID_file');
        }
    }

    function previewFile1(chooser) {

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
            var file_data = $("#customFile4").prop("files")[0]; // Getting the properties of file from file field
            dofileupload(file_data, '{{$user->uuid}}', 'signature');
        }
    }


    function update_profile() {

        alert('sending');

        $("form#data").submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: '/update_profilex',
                type: 'POST',
                data: formData,
                success: function(data) {
                    alert(data)
                },
                cache: false,
                contentType: false,
                processData: false
            });
        });

    }

    function send_form() {

        document.getElementById('real').click()

    }

    function dofileupload(file_data, userid, imgname) {
        //$(document).on("change", "#avatar", function() {   // If you want to upload without a submit button


        var form_data = new FormData(); // Creating object of FormData class
        form_data.append("file", file_data) // Appending parameter named file with properties of file_field to form_data
        form_data.append("user_id", userid) // Adding extra parameters to form_data
        form_data.append("imgname", imgname)
        $.ajax({
            url: "updateimgprofile", // Upload Script
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: form_data, // Setting the data attribute of ajax with file_data
            type: 'post',
            success: function(data) {
                $('.' + imgname).removeClass("alert alert-danger");
                $('.' + imgname).removeClass("alert alert-success");
                if (data.status == 1) {
                    $('.' + imgname).addClass("alert alert-success");
                } else {
                    $('.' + imgname).addClass("alert alert-danger");
                }
                $('.' + imgname).html(data.message);
            }
        });

    }
</script>

</div>
@endsection
