var _getValidationField = document.getElementsByClassName('validation-text');
isSuccessmessage = false;

$('#customer').select2({
    placeholder: 'Select a Customer',
    ajax: {
        url: '/userDataSelect',
        dataType: 'json',
        delay: 250,
        data: function (data) {
            console.log(data);
            return {
                searchTerm: data.term // search term
            };
        },
        processResults: function (response) {
            return {
                results: response
            };
        },
        cache: true
    }
});

$('#mandateGuidelineBtn').click(function () {
    $('#mandateGuidelineModal').modal({ backdrop: 'static', keyboard: false, show: true })

});

function getCustomerBalance() {
    console.log();

    var customer_id = $('#customer').val()
    $('#customer_balance').html('');
    $('#balanceDiv').hide();
    $("#myBankLoader").show();

    $.ajax({
        url: 'get-customer-profile/' + customer_id,
        type: 'get',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            console.log(data.data);

            if (data.status == 1) {
                var value = (data.data.balance).toLocaleString(
                    undefined,
                    { minimumFractionDigits: 2 }
                );
                $('#balanceDiv').show();
                $('#customer_balance').html("₦" + value);
                $('#payerName').val(data.data.name);
                $('#payerAddress').val(data.data.address);
                $('#emailAddress').val(data.data.email);
                $('#phoneNumber').val(data.data.phone);

            } else {

                $('#customer_balance').html(data.data.balance);
                $('#balanceDiv').hide();
            }

            $("#myBankLoader").hide();
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
    }).done(function () {
        // $('.toast').addClass("bg-success");
    });

}

function getMandateExplanation() {
    let repeatType = $('#repeat').val();
    let frequency = $('#frequency').val();

    if (repeatType != "") {
        $('#mandateExplanationDiv').show();

        switch (repeatType) {
            case "M":
                repeatType = frequency == 1 ? "Monthly" : "Months";
                break;
            case "D":
                repeatType = frequency == 1 ? "Daily" : "Days";
                break;
            case "W":
                repeatType = frequency == 1 ? "Weekly" : "Weeks";
                break;
            case "Y":
                repeatType = frequency == 1 ? "Yearly" : "Years";
                break;

            default:
                break;
        }
        frequency = frequency == 1 ? "" : "in " + frequency;

        mandateExplanation = "This mandate will happen Once " + frequency + " " + repeatType;
        $('#mandateExplanation').text(mandateExplanation)
    } else {
        $('#mandateExplanationDiv').hide();
    }
}

$('#repeat').on('change', getMandateExplanation)
$('#frequency').on('change', getMandateExplanation)

$("#submit_Rform").on('submit', function () {
    var formData = $(this).serialize();

    startLoadingButton("mySumit");

    $.ajax({
        url: 'process-verify-and-reverse-transaction',
        type: 'post',
        data: new FormData(this),
        dataType: 'json',
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            stopLoadingButton("mySumit", "Submit");
            $status = '';
            $complete = '';
            if (data.status) {
                document.getElementById("submit_Rform").reset();

                $status = 'success';
                $complete = 'Done!';
                $('#html5-extension').DataTable().ajax.reload();
            } else {
                $status = 'error';
                $complete = 'Error!';
            }

            swal(
                $complete,
                data.message,
                $status
            )
        }
    });

    return false;
});

function showTransactionSummary() {

    var amount = $('#amount').val();
    var fundMethod = $('#fundMethod').val();
    var token = $('.token').val();
    $('.validation-text').html();

    console.log(fundMethod);

    if (fundMethod == 3) {

        if (amount == "") {
            _getValidationField[0].innerHTML = 'Amount is required';
            _getValidationField[0].style.display = 'block'; return false;
        } else {
            _getValidationField[0].style.display = 'none';
        }
        if (fundMethod == "") {
            _getValidationField[1].innerHTML = 'Please select the fund method';
            _getValidationField[1].style.display = 'block'; return false;
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

        $('#fundWalletSummary').modal({ backdrop: 'static', keyboard: false, show: true })
    } else {
        $('#fundWalletBankTransferSummary').modal({ backdrop: 'static', keyboard: false, show: true })

    }
    // renderFundWallet();
}


function verifyBankAccount() {

    var transferBankCode = $('#bank').val();
    var transferAccountNumber = $('#accountNumber').val();

    if (transferBankCode != null) _getValidationField[0].style.display = 'none';

    $('#accountName').val("");

    console.log(transferBankCode + " " + transferAccountNumber);
    if (transferAccountNumber.length == 10 && transferBankCode != "") {
        console.log("Verifying account number " + transferAccountNumber);

        $("#myBankDetailsLoader").show();

        $('#accountName').val("");
        _getValidationField[1].style.display = 'none';


        data_info = {
            "bankCode": transferBankCode,
            "accountNumber": transferAccountNumber,
        }

        $.ajax({
            url: 'verify-bank-account',
            type: 'post',
            data: data_info,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {

                console.log(data);

                if (data.status == 1) {

                    var newAccountNumber = $('#accountNumber').val();

                    if (newAccountNumber.length == 10) {
                        $('#accountName').val(data.data.account_name);
                        // $('#bankName').val(data.data.bank_name);
                        // $('#requestReference').val(data.data.request_reference);
                        // $('#sessionID').val(data.data.session_id);
                        _getValidationField[1].style.display = 'none';
                    } else {
                        $('#accountName').val("");
                        // $('#bankName').val("");
                        _getValidationField[1].innerHTML = 'Invalid Account Number';
                        _getValidationField[1].style.display = 'block';
                    }
                } else {
                    console.log(data);
                    _getValidationField[1].innerHTML = 'Invalid Account Number';
                    _getValidationField[1].style.display = 'block';
                }

                $("#myBankDetailsLoader").hide();
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
        }).done(function () {
            // $('.toast').addClass("bg-success");
        });

        // $('.toast').toast('show');
    } else {
        $('#accountName').val('');
        $("#myBankDetailsLoader").hide();
    }
}


function initiateTransacion() {
    var amount = $('#amount').val();
    var fundMethod = $('#fundMethod').val();

    data_info = { finalAmount: amount, transaction_type: fundMethod, fund_method: fundMethod };


    $('#initiateFundWallet').prop("disabled", true);
    $('#initiateFundWallet').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    console.log(data_info);

    $.ajax({
        url: 'initiate-transaction',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

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
    $pageUrl = 'customer-overdraft-page';
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

