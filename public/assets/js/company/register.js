var $_getValidationField = document.getElementsByClassName('validation-text');
document.getElementById("backstep1").onclick = function () {
    initform();
};
document.getElementById("step1").onclick = function () {


    if (!validateStepOneInput("all")) {
        return;
    } else {

        var firstname = $('#firstname').val();
        var lastname = $('#lastname').val();
        var username = $('#username').val();
        var email = $('#email').val();
        var password = $('#password').val();
        var password2 = $('#password2').val();
        var phone = $('#countryphone').val();

        phone = phone.startsWith("+") ? "" + phone.substring(1) : phone;

        var phoneNumberPrefix = $('#phoneNumberPrefix').text()

        var value = $("#companycountry option:selected");

        if (value.text().includes("Nigeria") || value.text().includes("Ghana") || value.text().includes("Zambia")) {
            phone = phone.startsWith("0") ? "" + phone.substring(1) : phone;
        }

        console.log(phone);
        console.log(phoneNumberPrefix);

        phone = phone.startsWith(phoneNumberPrefix.substring(1)) ? phone.substring(phoneNumberPrefix.trim().length - 1, phone.length) : phone;

        phone = phoneNumberPrefix.substring(1) + "" + phone;

        $('#formatted_phone').val(phone);

        console.log(phone);

        var data_info = {
            firstname: firstname,
            lastname: lastname,
            username: username,
            email: email,
            phone: phone,
            password: password,
            password2: password2
        }

        startLoadingButton('step1');

        console.log("we are here already");
        $.ajax({
            type: 'post',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: 'validate-email-phone',
            data: data_info,
            dataType: 'json',
            cache: false,
            success: (data) => {

                stopLoadingButton('step1', "Next");

                if (data.status == 1) {
                    $('.company-information').fadeIn("slow");
                    $('.login-info').fadeOut("slow");

                    var value = $("#companycountry option:selected");

                    if (value.text().includes("Nigeria")) {
                        $('#bvn-field').show();
                        console.log("we are showing bvn");
                    } else {
                        console.log("we are hiding bvn");
                        $('#bvn-field').hide();
                    }

                } else if (data.status == 0) {

                    data.data.forEach(function (errorMessage) {

                        if (errorMessage.includes("email")) {
                            $_getValidationField[3].innerHTML = errorMessage;
                            $_getValidationField[3].style.display = 'block';
                        }
                        if (errorMessage.includes("username")) {
                            $_getValidationField[2].innerHTML = errorMessage;
                            $_getValidationField[2].style.display = 'block';
                        }
                        if (errorMessage.includes("phone")) {
                            $_getValidationField[5].innerHTML = errorMessage;
                            $_getValidationField[5].style.display = 'block';
                        }
                    });

                }
            },
            error: function (jqXHR, status, error) {

                stopLoadingButton('step1', "Next");

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
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    msg,
                    $status
                );
            }
        }).done(function () {
            // $('.toast').addClass("bg-success");
        });
    }
};

$('#termsCheck').on('change', function () {
    var checked = this.checked
    console.log("checked value is: " + checked);
    if (checked) {
        $('#submitBtn').prop("disabled", false);
    } else
        $('#submitBtn').prop("disabled", true);
})


$('#firstname').on('input', function (e) {
    validateStepOneInput("firstName");
})

$('#lastname').on('input', function (e) {
    validateStepOneInput("lastName");
})
$('#username').on('input', function () {
    validateStepOneInput("username");
})
$('#email').on('input', function () {
    validateStepOneInput("email");
})
$('#bvn').on('input', function () {
    validateStepTwoInput("bvn");
})
$('#password').on('input', function () {
    validateStepOneInput("password");
})
$('#password2').on('input', function () {
    validateStepOneInput("password2");
})


$('#companyname').on('input', function () {
    validateStepTwoInput("companyname");
})
$('#companyshort').on('input', function () {
    validateStepTwoInput("companyshort");
})
$('#countryphone').on('input', function () {
    validateStepOneInput("countryphone");
})
$('#companycac_no').on('input', function () {
    validateStepTwoInput("companycac_no");
})
$('#companylicense_no').on('input', function () {
    validateStepTwoInput("companylicense_no");
})
$('#companycooperativetype').on('change', function () {
    validateStepTwoInput("companycooperativetype");
})
$('#companycurrency').on('change', function () {
    validateStepTwoInput("companycurrency");
})
$('#companycountry').on('change', function (e) {

    var value = $("#companycountry option:selected");
    console.log(e.target.value);

    $('#countryphone').prop('disabled', e.target.value == "");

    if (e.target.value != "") {

        var splitted = value.text().split('+');
        var first = splitted[1].trim();

        $('#phoneNumberPrefix').text("+" + first.slice(0, first.length - 1))

    }

    validateStepOneInput("companycountry");


})
$('#companyaddress').on('input', function () {
    validateStepTwoInput("companyaddress");
})


function validateStepOneInput(type) {
    isValidated = true;
    if (type == 'firstName' || type == "all") {
        if ($('#firstname').val() == "") {
            $_getValidationField[0].innerHTML = 'First Name must be filled out';
            $_getValidationField[0].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[0].style.display = 'none';
        }
    }

    if (type == 'lastName' || type == "all") {

        if ($('#lastname').val() == "") {
            $_getValidationField[1].innerHTML = 'Last Name must be filled out';
            $_getValidationField[1].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[1].style.display = 'none';
        }
    }

    if (type == 'username' || type == "all") {

        if ($('#username').val() == "") {
            $_getValidationField[2].innerHTML = 'Username must be filled out';
            $_getValidationField[2].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[2].style.display = 'none';
        }
    }

    if (type == 'email' || type == "all") {

        if ($('#email').val() == "") {
            $_getValidationField[3].innerHTML = 'Email must be filled out';
            $_getValidationField[3].style.display = 'block';
            isValidated = false;
        } else if (!isEmail($('#email').val())) {
            $_getValidationField[3].innerHTML = 'Invalid Email Address';
            $_getValidationField[3].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[3].style.display = 'none';
        }
    }

    if (type == 'companycountry' || type == "all") {

        if ($('#companycountry').val() == "") {
            $_getValidationField[4].innerHTML = 'Country must be selected';
            $_getValidationField[4].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[4].style.display = 'none';
        }
    }

    if (type == 'countryphone' || type == "all") {

        if ($('#countryphone').val() == "") {
            $_getValidationField[5].innerHTML = 'Phone number must be filled out';
            $_getValidationField[5].style.display = 'block';
            isValidated = false;
        } else {
            if ($('#countryphone').val().length < 6) {
                $_getValidationField[5].innerHTML = 'Enter a valid phone number';
                $_getValidationField[5].style.display = 'block';
                isValidated = false;
            } else {
                $_getValidationField[5].style.display = 'none';
            }
        }
    }

    if (type == 'password' || type == 'password2' || type == "all") {

        if ($('#password').val() == "") {
            $_getValidationField[6].innerHTML = 'Paswords must be filled out';
            $_getValidationField[6].style.display = 'block';
            isValidated = false;
        } else if ($('#password').val() != "" && $('#password2').val() != "" && $('#password').val() != $('#password2').val()) {
            $_getValidationField[6].innerHTML = 'Paswwords do not match';
            $_getValidationField[6].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[6].style.display = 'none';
        }
    }

    if (type == 'password2' || type == "all") {

        console.log(type);

        if ($('#password2').val() == "") {
            $_getValidationField[7].innerHTML = 'Confirm Paswword must be filled out';
            $_getValidationField[7].style.display = 'block';
            isValidated = false;
        } else if ($('#password2').val() != $('#password').val() && (type == 'password' || type == 'password2' || type == "all")) {
            $_getValidationField[7].innerHTML = 'Paswords do not match';
            $_getValidationField[7].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[7].style.display = 'none';
        }
    }

    return isValidated;
}

function validateStepTwoInput(type) {
    isValidated = true;
    if (type == 'companyname' || type == "all") {
        if ($('#companyname').val() == "") {
            $_getValidationField[8].innerHTML = 'Company Name must be filled out';
            $_getValidationField[8].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[8].style.display = 'none';
        }
    }

    if (type == 'companyshort' || type == "all") {

        if ($('#companyshort').val() == "") {
            $_getValidationField[9].innerHTML = 'Company Short must be filled out';
            $_getValidationField[9].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[9].style.display = 'none';
        }
    }

    if (type == 'companycac_no' || type == "all") {

        if ($('#companycac_no').val() == "") {
            $_getValidationField[10].innerHTML = 'CAC Number must be filled out';
            $_getValidationField[10].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[10].style.display = 'none';
        }
    }

    if (type == 'companylicense_no' || type == "all") {

        if ($('#companylicense_no').val() == "") {
            $_getValidationField[11].innerHTML = 'Cooperative License must be selected';
            $_getValidationField[11].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[11].style.display = 'none';
        }
    }

    if (type == 'companycooperativetype' || type == "all") {

        if ($('#companycooperativetype').val() == "") {
            $_getValidationField[12].innerHTML = 'Cooperative type must be selected';
            $_getValidationField[12].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[12].style.display = 'none';
        }
    }


    if (type == 'companycurrency' || type == "all") {

        if ($('#companycurrency').val() == "") {
            $_getValidationField[13].innerHTML = 'Currency must be selected';
            $_getValidationField[13].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[13].style.display = 'none';
        }
    }


    if (type == 'bvn' || type == "all") {

        var value = $("#companycountry option:selected");

        if (value.text().includes("Nigeria")) {

            if ($('#bvn').val() == "") {
                $_getValidationField[14].innerHTML = 'BVN must be filled out';
                $_getValidationField[14].style.display = 'block';
                isValidated = false;
            } else {
                $_getValidationField[14].style.display = 'none';
            }
        }
    }

    if (type == 'companyaddress' || type == "all") {
        if ($('#companyaddress').val() == "") {
            $_getValidationField[15].innerHTML = 'Address must be filled out';
            $_getValidationField[15].style.display = 'block';
            isValidated = false;
        } else {
            $_getValidationField[15].style.display = 'none';
        }
    }

    return isValidated;
}


function initform() {
    $('.company-information').fadeOut("slow");
    $('.login-info').fadeIn("slow");
}
initform()
$("#reg-form").on("submit", function (e) {
    e.preventDefault();

    if (!validateStepTwoInput("all")) {

        return;
    }

    startLoadingButton('submitBtn');

    $(".toast-msg").html('');
    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: 'companyregister',
        data: new FormData(this),
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: (data) => {
            stopLoadingButton('submitBtn', "Submit");

            if (data.status == 1) {
                mystatus = 'success';
                complete = 'Account Created Successfully';
                swal({
                    title: complete,
                    text: data.message,
                    icon: mystatus,
                    type: 'success'
                }).then(function () {
                    myGreeting();
                });
                // const myTimeout = setTimeout(myGreeting, 3000);
            } else if (data.status == 0 || data.status == 2) {
                // data.status = 'bg-danger';
                $status = 'error';
                $complete = 'Error!';

                console.log(data);

                if (data.data != null || data.data != undefined) {

                    data.data.forEach(function (errorMessage) {

                        if (errorMessage.includes("Company")) {
                            $_getValidationField[8].innerHTML = errorMessage;
                            $_getValidationField[8].style.display = 'block';

                            $('html, body').animate({
                                scrollTop: $("#companyInfoTitle").offset().top
                            }, 500);
                        }
                        if (errorMessage.includes("BVN")) {
                            $_getValidationField[14].innerHTML = errorMessage;
                            $_getValidationField[14].style.display = 'block';
                        }
                    });
                }
                if (data.status != 2)
                    swal(
                        $complete,
                        data.message,
                        $status
                    );
            }
            $('.token').val(data.token);
        },
        error: function (jqXHR, status, error) {
            stopLoadingButton('submitBtn', "Submit");

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

            $status = 'error';
            $complete = 'Error!';
            swal(
                $complete,
                msg,
                $status
            );
        }
    }).done(function () {
        // $('.toast').addClass("bg-success");
    });

});


function anotherStop() {
    stopLoadingButton('submitBtn', "Submit");
}

function isEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
}


function myGreeting() {
    window.location.replace(window.location.origin + '/login');
}
