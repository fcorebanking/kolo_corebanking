$("#registration_form_wizard").steps({
    headerTag: "h3",
    bodyTag: "section",
    transitionEffect: "slideLeft",
    autoFocus: true,
    cssClass: 'pill wizard',
    onFinished: function () {
        $("#public_customer_form").submit()
    },
    onStepChanging: function () {



        if ($("#registration_form_wizard").steps("getCurrentIndex") == 0) {

            var account_category = $('#account_category').val();

            if (account_category == '') {
                document.getElementById('account_category_error').innerHTML = 'Account Category is required';
                return false;
            } else {
                document.getElementById('account_category_error').innerHTML = '';
                return true
            }

        }


        if ($("#registration_form_wizard").steps("getCurrentIndex") == 1) {

            console.log('step2')

            var should_passx = 'yes';

            var first_name = $('#first_name').val();
            var last_name = $('#last_name').val();
            var BVN = $('#BVN').val();
            var email = $('#email').val();
            var phone = $('#phone').val();
            var address = $('#address').val();
            var username = $('#username').val();
            var dob = $('#dob').val();
            var branch = $('#branch').val();
            var password = $('#password').val();
            var transaction_pin = $('#transaction_pin').val();
            var gender = $('#gender').val();


            const values = [
                first_name,
                last_name,
                BVN,
                email,
                phone,
                address,
                username,
                dob,
                branch,
                password,
                transaction_pin,
                gender
            ];

            const fields = [
                'first_name',
                'last_name',
                'BVN',
                'email',
                'phone',
                'address',
                'username',
                'dob',
                'branch',
                'password',
                'transaction_pin',
                'gender',
                'privacy_btn'
            ];

            for (var index = 0; index < fields.length; index++) {

                try {


                    document.getElementById(fields[index] + '_error').innerHTML = '';

                    document.getElementById(fields[index] + '_error2').innerHTML = '';

                    document.getElementById(fields[index] + '_error3').innerHTML = '';

                } catch (error) {

                }

            }

            var letters = /[a-zA-Z]/g;

            var lettersx = /[a-zA-Z]/g;

            for (let index = 0; index < fields.length; index++) {

                console.log(fields[index]);

                if (values[index] == '') {

                    // alert(fields[index]+'_error');


                    document.getElementById(fields[index] + '_error').innerHTML = 'This field is required <br>';

                    should_passx = 'no';

                }

                if (fields[index] == 'BVN' && values[2].length < 3) {

                    // alert('BVN length is less than 11')


                    document.getElementById('BVN_error2').innerHTML = 'The Bank Verification Number must be at least 3 digits <br>';

                    should_passx = 'no';

                }

                if (fields[index] == 'email') {

                    var myEmail = $('#email').val();
                    var myUsername = $('#username').val();

                    var company_reference = $('#company_reference').val();

                    if (myEmail != "" || myUsername != "") {
                        $.ajax({
                            type: 'POST',
                            url: "/company/validate_details_public",
                            async: false,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            // enctype: 'multipart/form-data',
                            data: {

                                email: $('#email').val(),
                                username: $('#username').val(),
                                phone:$('#phone').val(),
                                company_reference: $('#company_reference').val()

                            },


                            success: (data) => {

                                // console.log(data.user.user)

                                if (data.email > 0) {


                                    document.getElementById('email_error').innerHTML = 'The email already exist <br>';

                                    should_passx = 'no';

                                    return should_passx;

                                }
                                if (data.username > 0) {


                                    document.getElementById('username_error').innerHTML = 'The username already exist <br>';

                                    should_passx = 'no';

                                    return should_passx;

                                }
                                if (data.phone > 0) {


                                    document.getElementById('phone_error').innerHTML = 'The phone already exist <br>';

                                    should_passx = 'no';

                                    return should_passx;

                                }


                            },
                            error: function (data) {


                            }


                        });
                    }
                }
                if (fields[index] == 'privacy_btn') {
                var privacyCheckbox = document.getElementById('privacy_btn');
                var isChecked = privacyCheckbox.checked;

                if (isChecked) {
                    // Checkbox is checked
                    console.log('Checkbox is checked');
                } else {
                    document.getElementById('privacy_btn_error').innerHTML = 'Accept terms and conditions <br>';

                        should_passx = 'no';

                    // Checkbox is not checked
                    console.log('Checkbox is not checked');
                }
            }
                if (fields[index] == 'BVN') {

                    // alert(values[2])


                    if (letters.test(values[2])) {
                        // alert(values[2])
                        // alert('bvn has letters')

                        document.getElementById('BVN_error3').innerHTML = 'The BVN must be digits <br>';

                        should_passx = 'no';

                    } else {

                        console.log(" bvn has no letters");

                    }

                }

                if (fields[index] == 'dob') {

                    if (values[index] == '') {

                        document.getElementById(fields[index] + '_error').innerHTML = 'Date of Birth is required';

                        should_passx = 'no';
                    } else {
                        console.log(" dob has no letters");
                    }
                }

                if (fields[index] == 'branch') {

                    if (values[index] == '') {

                        document.getElementById(fields[index] + '_error').innerHTML = 'Branch is required';

                        should_passx = 'no';
                    } else {
                        console.log(" dob has no letters");
                    }
                }

                if (fields[index] == 'gender') {

                    if (values[index] == '') {

                        document.getElementById(fields[index] + '_error').innerHTML = 'Gender is required';

                        should_passx = 'no';
                    } else {
                        console.log(" dob has no letters");
                    }
                }

                if (fields[index] == 'transaction_pin') {

                    if (values[index] == '') {

                        document.getElementById(fields[index] + '_error').innerHTML = 'Pin is required';

                        should_passx = 'no';
                    } else {
                        console.log(" dob has no letters");
                    }
                }

                if (fields[index] == 'password') {

                    if (values[index] == '') {

                        document.getElementById(fields[index] + '_error').innerHTML = 'Account password is required';

                        should_passx = 'no';
                    } else {
                        console.log(" dob has no letters");
                    }
                }
                // if (fields[index] == 'PIN') {

                //     // alert(values[4])

                //     // alert('This is the length of the password: ' +values[4].length)




                //     if (lettersx.test(values[4])) {
                //         // alert(values[4])

                //         // alert('pin has letters')

                //         document.getElementById('PIN_error3').innerHTML = 'The PIN must be digits <br>';

                //         should_passx = 'no';

                //     } else {

                //         console.log("pin has no letters");

                //     }




                // }




                // if (fields[index] == 'PIN' && values[4].length != 4) {

                //     // alert('This is the length of the password: ' +values[4].length)

                //     document.getElementById('PIN_error2').innerHTML = 'The PIN must be 4 characters <br>';

                //     should_passx = 'no';
                // }




            }



            if (should_passx == 'yes') {

                return true;

            } else {

                // alert(should_passx)






                return false;

            }








        }

        if ($("#registration_form_wizard").steps("getCurrentIndex") == 2) {

            console.log('step 2')
            return '!0';
        }

        if ($("#registration_form_wizard").steps("getCurrentIndex") == 3) {

            console.log('step 3')
            return '!0';
        }





    },
});
$("#circle-basic").steps({
    headerTag: "h3",
    bodyTag: "section",
    transitionEffect: "slideLeft",
    autoFocus: true,
    cssClass: 'circle wizard'
});
$("#example-vertical").steps({
    headerTag: "h3",
    bodyTag: "section",
    transitionEffect: "slideLeft",
    cssClass: 'circle wizard',
    titleTemplate: '<span class="number">#index#</span>',
    stepsOrientation: "vertical"
});
$("#pill-vertical").steps({
    headerTag: "h3",
    bodyTag: "section",
    transitionEffect: "slideLeft",
    cssClass: 'pills wizard',
    titleTemplate: '#title#',
    stepsOrientation: "vertical"
});



// registration


$(".success_page").hide();
$(".fail_page").hide();
$(".public_customer_form").on('submit', function () {

    swal({
        title: 'Please wait!',
        text: 'Creating customer account',
        allowOutsideClick: false,
        allowEscapeKey: false,
        padding: '2em',
        onOpen: function () {
            swal.showLoading()
        }
    })

    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/company/book-external-loan',
        data: new FormData(this),
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: (data) => {

            swal.close()

            $('.toast').removeClass("bg-success");
            $('.toast').removeClass("bg-danger");
            $(".toast-msg").html(data.message);
            if (data.status == 1) {
                data.status = 'bg-success';
                $(".success_page").show();
                $('#createAccountDiv').hide();
                $(".fail_page").hide();
                $(".accoutno").html(data.account.account_number);
                $(".bank").html(data.account.bank);
                $(".loanMessage").html(data.loan.message);
                // $(".password").html(data.password);
                $(".msg").html(data.desc);
                $("#examplez")[0].reset();
            } else if (data.status == 0) {
                data.status = 'bg-danger';
                $(".fail_page").show();
                $(".success_page").hide();
                $(".msg").html(data.message);
            }
            // $("html, body").animate({ scrollTop: $(document).height() }, 1000);


            $('.toast').addClass(data.status);
            $('.token').val(data.token);
        },
        error: function (jqXHR, status, error) {
            swal.close()

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
        }
    }).done(function () {
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');
    return false;
});



