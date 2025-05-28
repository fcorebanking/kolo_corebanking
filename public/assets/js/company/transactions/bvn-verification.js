var _getValidationField = document.getElementsByClassName('validation-text');
isSuccessmessage = false;

// $(document).ready(function ($) {

//     $('#verificationTyp').on('change', function (e) {

//         console.log("lsdkjfljsdfjskldj");

//     });
// })

function verifyBVN() {
    var verificationType = $('#verificationTyp').val();
    var validationNumber = $('#validationNumber').val();

    console.log(verificationType);

    if (verificationType == "") {
        _getValidationField[0].innerHTML = 'Verification type is required';
        _getValidationField[0].style.display = 'block'; return false;
    } else {
        _getValidationField[0].style.display = 'none';
    }
    if (validationNumber == "") {
        if (verificationType == "nin") {
            _getValidationField[1].innerHTML = 'NIN is required';
            _getValidationField[1].style.display = 'block'; return false;
        } else {
            _getValidationField[1].innerHTML = 'BVN is required';
            _getValidationField[1].style.display = 'block'; return false;
        }
    } else {
        _getValidationField[1].style.display = 'none';
    }

    hideAllDivs();
    startLoadingButton('mySubmit');

    $.ajax({
        url: 'verify-bvn?verification_type=' + verificationType + '&verification_number=' + validationNumber,
        type: 'get',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            stopLoadingButton('mySubmit', 'Submit')

            $('#mySumit').prop("disabled", false);
            $('#mySumit').html('Continue');

            console.log(data);

            if (data.status == 1) {
                console.log(data);

                $('#bvnDetailsDiv').show();
                // $('#printBtnDiv').show();

                if (verificationType == "bvn") {
                    console.log(data.data.firstName)
                    console.log(data.data.base64Image)
                    $('#titleHeader').html("BVN Details")

                    displayInfo('first_name_div', 'first_name', data.data.firstName)
                    displayInfo('middle_name_div', 'middle_name', data.data.middleName)
                    displayInfo('last_name_div', 'last_name', data.data.lastName)
                    displayInfo('date_of_birth_div', 'date_of_birth', data.data.dateOfBirth)
                    displayInfo('phone_number1_div', 'phone_number1', data.data.phoneNumber1)
                    displayInfo('phone_number2_div', 'phone_number2', data.data.phoneNumber2)
                    displayInfo('gender_div', 'gender', data.data.gender)
                    displayInfo('email_div', 'email', data.data.email)
                    displayInfo('lga_of_origin_div', 'lga_of_origin', data.data.lga_of_origin)
                    displayInfo('lga_of_residence_div', 'lga_of_residence', data.data.lgaOfResidence)
                    displayInfo('marital_status_div', 'marital_status', data.data.maritalStatus)
                    displayInfo('nin_div', 'nin', data.data.nin)
                    displayInfo('name_on_card_div', 'name_on_card', data.data.nameOnCard)
                    displayInfo('residential_address_div', 'residential_address', data.data.residentialAddress)
                    displayInfo('state_of_origin_div', 'state_of_origin', data.data.stateOfOrigin)
                    displayInfo('state_of_residence_div', 'state_of_residence', data.data.stateOfResidence)
                    displayInfo('watch_listed_div', 'watch_listed', data.data.watchListed)
                    $('#bvn_image').show();

                    //$('#bvn_image').attr("src", "data:image/png;base64," + data.data.base64Image);
                } else if (verificationType == "nin") {

                    displayInfo('titleHeader_div', 'titleHeader', "NIN Details")
                    displayInfo('first_name_div', 'first_name', data.data.firstName)
                    displayInfo('middle_name_div', 'middle_name', data.data.middleName)
                    displayInfo('last_name_div', 'last_name', data.data.surname)
                    displayInfo('date_of_birth_div', 'date_of_birth', data.data.dateOfBirth)
                    displayInfo('phone_number1_div', 'phone_number1', data.data.phone)
                    displayInfo('gender_div', 'gender', data.data.gender == "M" ? "Male" : "Female")
                    $('#bvn_image').show();
                    console.log(data.data.base64Image);
                    $('#bvn_image').attr("src", "data:image/png;base64," + data.data.photo);
                } else {

                    displayInfo('titleHeader_div', 'titleHeader', "Driver License Details")
                    displayInfo('first_name_div', 'first_name', data.data.firstName)
                    displayInfo('middle_name_div', 'middle_name', data.data.middleName)
                    displayInfo('last_name_div', 'last_name', data.data.lastName)
                    displayInfo('date_of_birth_div', 'date_of_birth', data.data.birthDate)
                    displayInfo('gender_div', 'gender', data.data.gender)
                    displayInfo('issued_date_div', 'issued_date', data.data.issuedDate)
                    displayInfo('expiry_date_div', 'expiry_date', data.data.expiryDate)
                    displayInfo('state_of_issue_div', 'state_of_issue', data.data.stateOfIssue)
                    displayInfo('license_no_div', 'license_no', data.data.licenseNo)
                    $('#bvn_image').hide();
                }


            } else {
                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }

        },
        error: function (jqXHR, status, error) {

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

            $('#mySumit').prop("disabled", false);
            $('#mySumit').html('Continue');

        },
        xhr: function () {
            var xhr = $.ajaxSettings.xhr();
            xhr.onprogress = function e() {
                // For downloads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                    $(".toast-msg").html(e.loaded / e.total);
                }
            };
            xhr.upload.onprogress = function (e) {
                // For uploads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                }
            };
            return xhr;
        }
    });

}

function hideAllDivs() {
    $('#first_name_div').hide();
    $('#middle_name_div').hide();
    $('#last_name_div').hide();
    $('#date_of_birth_div').hide();
    $('#issued_date_div').hide();
    $('#expiry_date_div').hide();
    $('#phone_number1_div').hide();
    $('#phone_number2_div').hide();
    $('#gender_div').hide();
    $('#email_div').hide();
    $('#lga_of_origin_div').hide();
    $('#lga_of_residence_div').hide();
    $('#marital_status_div').hide();
    $('#nin_div').hide();
    $('#license_div').hide();
    $('#name_on_card_div').hide();
    $('#residential_address_div').hide();
    $('#state_of_origin_div').hide();
    $('#state_of_residence_div').hide();
    $('#watch_listed_div').hide();
}

function displayInfo(divId, textId, value) {
    $('#' + divId).show();
    $('#' + textId).text(value);
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
        $_getValidationField[6].style.display = 'block'; return false;
    } else {
        $_getValidationField[6].style.display = 'none';
    }
    if ($staff_lastname == "") {
        $_getValidationField[7].innerHTML = 'Last Name must be filled out';
        $_getValidationField[7].style.display = 'block'; return false;
    } else {
        $_getValidationField[7].style.display = 'none';
    }
    if ($staff_username == "") {
        $_getValidationField[8].innerHTML = 'Username must be filled out';
        $_getValidationField[8].style.display = 'block'; return false;
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


    $data_info = { id: $user_id, branch: $staff_branch, firstname: $staff_fname, lastname: $staff_lastname, email: $staff_email, username: $staff_username, phone: $staff_phone };
    ajax_operation('post', 'staff/ajax_updatestaff', $data_info);
    renderFundWallet();
}

function renderFundWallet(hasMessage, messageType, message) {
    $send_method = 'POST';
    $pageUrl = 'bvn-verification-page';
    $data_info = { hasMessage: hasMessage, messageType: messageType, message: message }
    ajax_fetch($send_method, $pageUrl, $data_info);
}

function showSuccessAlert(messageType, message) {

    console.log(message + "sldjflksjdflksj");

    if (message != '')
        renderFundWallet(true, messageType, message);
}

function edit_staff(id) {
    $.ajax({
        url: 'staff/staff_list/' + id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {

            data.forEach(function (staff) {
                $('#user_id').val(staff.id);
                $('#edit-first-name').val(staff.first_name);
                $('#edit-last-name').val(staff.last_name);
                $('#edit-username').val(staff.username);
                $('#edit-email').val(staff.email);
                $('#edit-phone').val(staff.phone);
                $('#edit-branch').val(staff.branch_id);
            });
            $('#editexampleModal').modal('show');
        }
    })
}
renderFundWallet(false, '', '');