var $_getValidationField = document.getElementsByClassName('validation-text');

$('#employment_type').on('change', function () {
    $_getValidationField[0].style.display = 'none';
})
$('#basic_salary').on('input', function (e) {
    $_getValidationField[1].style.display = 'none';
    calculateSalary();
})
$('#date_of_payment').on('input', function () {
    $_getValidationField[2].style.display = 'none';
})
$('#house_allowance').on('input', function () {
    $_getValidationField[3].style.display = 'none';
    calculateSalary();
})
$('#medical_allowance').on('input', function () {
    $_getValidationField[4].style.display = 'none';
    calculateSalary();
})
$('#special_allowance').on('input', function () {
    $_getValidationField[5].style.display = 'none';
    calculateSalary();
})
$('#provident_fund_contribution').on('input', function () {
    $_getValidationField[6].style.display = 'none';
    calculateSalary();
})
$('#overtime').on('input', function () {
    $_getValidationField[7].style.display = 'none';
    calculateSalary();
})
$('#transport_allowance').on('input', function () {
    $_getValidationField[8].style.display = 'none';
    calculateSalary();
})
$('#paid_leave').on('input', function () {
    $_getValidationField[9].style.display = 'none';
    calculateSalary();
})
$('#other_allowances').on('input', function () {
    $_getValidationField[10].style.display = 'none';
    calculateSalary();
})
$('#tax_deductions').on('input', function () {
    $_getValidationField[11].style.display = 'none';
    calculateSalary();
})
$('#provident_fund_deduction').on('input', function () {
    $_getValidationField[12].style.display = 'none';
    calculateSalary();
})
$('#pension_deduction').on('input', function () {
    $_getValidationField[13].style.display = 'none';
    calculateSalary();
})
$('#unpaid_leave').on('input', function () {
    $_getValidationField[14].style.display = 'none';
    calculateSalary();
})
$('#loan_deduction').on('input', function () {
    $_getValidationField[15].style.display = 'none';
    calculateSalary();
})
$('#other_deductions').on('input', function () {
    $_getValidationField[16].style.display = 'none';
    calculateSalary();
})
$('#gross_salary').on('input', function () {
    $_getValidationField[17].style.display = 'none';
    calculateSalary();
})
$('#total_deductions').on('input', function () {
    $_getValidationField[18].style.display = 'none';
    calculateSalary();
})
$('#net_salary').on('input', function () {
    $_getValidationField[19].style.display = 'none';
    calculateSalary();
})
$('#bank').on('change', function (e) {
    $_getValidationField[20].style.display = 'none';
    verifyBankAccount();
})
$('#account_number').on('input', function () {
    $_getValidationField[21].style.display = 'none';
    verifyBankAccount();
})
$('#account_name').on('input', function () {
    $_getValidationField[22].style.display = 'none';

})


$('#paymentMethod').on('change', function () {
    if ($(this).val() == 0) {
        $('#bankDiv').hide();
        $('#accountNumberDiv').hide();
        $('#accountNameDiv').hide();
        $('#walletAccountDiv').show();
        $('#walletAccountNameDiv').show();
    } else {
        $('#bankDiv').show();
        $('#accountNumberDiv').show();
        $('#accountNameDiv').show();
        $('#walletAccountDiv').hide();
        $('#walletAccountNameDiv').hide();
    }
})


function verifyBankAccount() {

    var bank = $('#bank').val();
    var accountNumber = $('#account_number').val();

    if (accountNumber.length == 10 && bank.length > 0) {

        data_info = {
            "bankCode": bank,
            "accountNumber": accountNumber,
        }

        console.log(data_info);

        $.ajax({
            url: '/transaction/verify-bank-account',
            type: 'post',
            data: data_info,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {

                console.log(data);

                if (data.status == 1) {

                    var newAccountNumber = $('#account_number').val();

                    if (newAccountNumber == accountNumber) {
                        $('#account_name').val(data.data.account_name);
                        // $('#bankName').val(data.data.bankName);
                        $_getValidationField[25].style.display = 'none';
                    } else {
                        $('#account_name').val("");
                        $('#bankName').val("");
                        $_getValidationField[25].innerHTML = 'Invalid Account Number';
                        $_getValidationField[25].style.display = 'block';
                    }
                } else {
                    console.log(data);
                    $('#account_name').val("");

                    $_getValidationField[25].innerHTML = 'Invalid Account Number';
                    $_getValidationField[25].style.display = 'block';
                }

                // $("#myBankLoader").hide();
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

    } else {
        $('#account_name').val('');
    }
}

function verifyWalletAccount() {

    var accountNumber = $('#wallet_account_number').val();

    if (accountNumber.length > 0) $_getValidationField[21].style.display = 'none';


    if (accountNumber.length == 10 && bank.length > 0) {

        data_info = {
            "account_number": accountNumber,
        }

        console.log(data_info);

        $.ajax({
            url: '/transaction/verify-wallet',
            type: 'post',
            data: data_info,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {

                console.log(data);

                if (data.status == 1) {

                    var newAccountNumber = $('#wallet_account_number').val();

                    if (newAccountNumber == accountNumber) {
                        $('#wallet_account_name').val(data.data.account_name);
                        // $('#bankName').val(data.data.bankName);
                        $_getValidationField[22].style.display = 'none';
                    } else {
                        $('#wallet_account_name').val("");
                        $_getValidationField[22].innerHTML = 'Invalid Account Number';
                        $_getValidationField[22].style.display = 'block';
                    }
                } else {
                    console.log(data);
                    $('#account_name').val("");

                    $_getValidationField[22].innerHTML = 'Invalid Account Number';
                    $_getValidationField[22].style.display = 'block';
                }

                // $("#myBankLoader").hide();
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

    } else {
        $('#wallet_account_name').val('');
    }
}


function calculateSalary() {

    console.log("We are live now");
    var totalDeductions = 0;
    var netSalary = 0;
    var grossSalary = 0;


    grossSalary += ($('#basic_salary').val() * 1) + ($('#house_allowance').val() * 1) + ($('#medical_allowance').val() * 1)
    grossSalary += ($('#special_allowance').val() * 1);
    grossSalary += ($('#provident_fund_contribution').val() * 1);
    grossSalary += ($('#overtime').val() * 1);
    grossSalary += ($('#transport_allowance').val() * 1);
    grossSalary += ($('#paid_leave').val() * 1);
    grossSalary += ($('#other_allowances').val() * 1);

    totalDeductions += ($('#tax_deductions').val() * 1) + ($('#provident_fund_deduction').val() * 1);
    totalDeductions += ($('#other_deductions').val() * 1);
    totalDeductions += ($('#pension_deduction').val() * 1);
    totalDeductions += ($('#unpaid_leave').val() * 1);
    totalDeductions += ($('#loan_deduction').val() * 1);

    netSalary = grossSalary - totalDeductions;

    $('#net_salary').val(netSalary);
    $('#gross_salary').val(grossSalary);
    $('#total_deductions').val(totalDeductions);
}

$("#add-staff").on('submit', function (e) {

    e.preventDefault();


    var employment_type = $('#employment_type').val();
    var basic_salary = $('#basic_salary').val();
    var date_of_payment = $('#date_of_payment').val();
    var house_allowance = $('#house_allowance').val();
    var medical_allowance = $('#medical_allowance').val();
    var special_allowance = $('#special_allowance').val();
    var provident_fund_contribution = $('#provident_fund_contribution').val();
    var overtime = $('#overtime').val();
    var transport_allowance = $('#transport_allowance').val();
    var paid_leave = $('#paid_leave').val();
    var other_allowances = $('#other_allowances').val();
    var tax_deductions = $('#tax_deductions').val();
    var provident_fund_deduction = $('#provident_fund_deduction').val();
    var pension_deduction = $('#pension_deduction').val();
    var unpaid_leave = $('#unpaid_leave').val();
    var loan_deduction = $('#loan_deduction').val();
    var other_deductions = $('#other_deductions').val();
    var gross_salary = $('#gross_salary').val();
    var total_deductions = $('#total_deductions').val();
    var net_salary = $('#net_salary').val();
    var bank = $('#bank').val();
    var account_number = $('#account_number').val();
    var account_name = $('#account_name').val();

    var paymentMethod = $('#paymentMethod').val();
    var wallet_account_number = $('#wallet_account_number').val();
    var wallet_account_name = $('#wallet_account_name').val();


    $token = $('.token').val();
    $('.validation-text').html();

    var isFormValidated = true;

    console.log("our length is " + basic_salary + "ending");

    if (employment_type == "") {
        $_getValidationField[0].innerHTML = "Employment Type is required";
        $_getValidationField[0].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[0].style.display = 'none';
    }
    if (basic_salary == "") {
        $_getValidationField[1].innerHTML = "Basic Salary is required";
        $_getValidationField[1].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[1].style.display = 'none';
    }
    if (date_of_payment == "") {
        $_getValidationField[2].innerHTML = "Date of Payment is required";
        $_getValidationField[2].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[2].style.display = 'none';
    }
    if (house_allowance == "") {
        $_getValidationField[3].innerHTML = "House Allowance is required";
        $_getValidationField[3].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[3].style.display = 'none';
    }
    if (medical_allowance == "") {
        $_getValidationField[4].innerHTML = "Medical Allowance is required";
        $_getValidationField[4].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[4].style.display = 'none';
    }
    if (special_allowance == "") {
        $_getValidationField[5].innerHTML = "Special Allowance is required";
        $_getValidationField[5].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[5].style.display = 'none';
    }
    if (provident_fund_contribution == "") {
        $_getValidationField[6].innerHTML = "Provident Fund is required";
        $_getValidationField[6].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[6].style.display = 'none';
    }
    if (overtime == "") {
        $_getValidationField[7].innerHTML = "Overtime is required";
        $_getValidationField[7].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[7].style.display = 'none';
    }
    if (transport_allowance == "") {
        $_getValidationField[8].innerHTML = "Transport Allowance is required";
        $_getValidationField[8].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[8].style.display = 'none';
    }
    if (paid_leave == "") {
        $_getValidationField[9].innerHTML = "Paid leave is required";
        $_getValidationField[9].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[9].style.display = 'none';
    }
    if (other_allowances == "") {
        $_getValidationField[10].innerHTML = "Other Allowances is required";
        $_getValidationField[10].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[10].style.display = 'none';
    }
    if (tax_deductions == "") {
        $_getValidationField[11].innerHTML = "Tax Deduction is required";
        $_getValidationField[11].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[11].style.display = 'none';
    }
    if (provident_fund_deduction == "") {
        $_getValidationField[12].innerHTML = "Provident fund Deduction is required";
        $_getValidationField[12].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[12].style.display = 'none';
    }
    if (pension_deduction == "") {
        $_getValidationField[13].innerHTML = "Pension Deduction is required";
        $_getValidationField[13].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[13].style.display = 'none';
    }
    if (unpaid_leave == "") {
        $_getValidationField[14].innerHTML = "Unpaid leave is required";
        $_getValidationField[14].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[14].style.display = 'none';
    }
    if (loan_deduction == "") {
        $_getValidationField[15].innerHTML = "Loan Deduction is required";
        $_getValidationField[15].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[15].style.display = 'none';
    }
    if (other_deductions == "") {
        $_getValidationField[16].innerHTML = "Other deduction is required";
        $_getValidationField[16].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[16].style.display = 'none';
    }
    if (gross_salary == "") {
        $_getValidationField[17].innerHTML = "Gross Salary is required";
        $_getValidationField[17].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[17].style.display = 'none';
    }
    if (total_deductions == "") {
        $_getValidationField[18].innerHTML = "Total Deduction is required";
        $_getValidationField[18].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[18].style.display = 'none';
    }
    if (net_salary == "") {
        $_getValidationField[19].innerHTML = "Net Salary is required";
        $_getValidationField[19].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[19].style.display = 'none';
    }

    console.log("kjhgfghjkjhgfgjkjhgjjhgfghjjhgg");
    if (paymentMethod == "") {
        $_getValidationField[20].innerHTML = "Payment Method is required";
        $_getValidationField[20].style.display = 'block';
        isFormValidated = false;
    } else {
        $_getValidationField[20].style.display = 'none';

        if (paymentMethod == "0") {

            if (wallet_account_number == "") {
                $_getValidationField[21].innerHTML = "Wallet Account is required";
                $_getValidationField[21].style.display = 'block';
                isFormValidated = false;
            } else {
                $_getValidationField[21].style.display = 'none';
            }

            if (wallet_account_name == "") {
                $_getValidationField[22].innerHTML = "Wallet Account name is required";
                $_getValidationField[22].style.display = 'block';
                isFormValidated = false;
            } else {
                $_getValidationField[22].style.display = 'none';
            }
        } else {
            if (bank == "") {
                $_getValidationField[23].innerHTML = "Bank is required";
                $_getValidationField[23].style.display = 'block';
                isFormValidated = false;
            } else {
                $_getValidationField[23].style.display = 'none';
            }

            if (account_number == "") {
                $_getValidationField[24].innerHTML = "Account Number is required";
                $_getValidationField[24].style.display = 'block';
                isFormValidated = false;
            } else {
                $_getValidationField[24].style.display = 'none';
            }
            if (account_name == "") {
                $_getValidationField[25].innerHTML = "Account Name is required";
                $_getValidationField[25].style.display = 'block';
                isFormValidated = false;
            } else {
                $_getValidationField[25].style.display = 'none';
            }
        }
    }


    if (!isFormValidated) {
        return false;
    }

    var form = $(this);

    console.log(form.serialize());

    $('#savePayrollBtn').prop("disabled", true);
    $('#savePayrollBtn').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    $.ajax({
        url: 'staff-payroll/setup-payroll',
        type: 'post',
        data: form.serialize(),
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('#exampleModal').modal('hide');

            document.getElementById("add-staff").reset();

            if (data.status == 1) {
                console.log(data);

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em',
                    allowOutsideClick: false,

                }).then(function (result) {
                    if (result.value) {
                        renderstaff();

                    }
                });
                $('#exampleModal').modal("hide");

            } else if (data.status == 3) {

                swal({
                    title: 'Error!',
                    text: data.data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }

            $('#savePayrollBtn').prop("disabled", false);
            $('#savePayrollBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Save');

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


            swal({
                title: 'Error!',
                text: msg,
                type: 'error',
                padding: '2em'
            })


            $('#savePayrollBtn').prop("disabled", false);
            $('#savePayrollBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Save');

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
        $('.toast').addClass("bg-success");
    });

    return false;
});


function delete_staff(val, statusid) {
    if (confirm("Are you sure, you want to delete this record?") == true) {
        ajax_operation('delete', 'staff/ajax_recyclestaff', { id: val, status: statusid });
        renderstaff();
    }
    return false;
}

function renderstaff() {
    $cur_path = window.location.href;
    $send_method = 'GET';
    $pageUrl = $cur_path + '/ajax_payroll_view';
    $data_info = {}
    ajax_fetch($send_method, $pageUrl, $data_info);
}

function add_payroll(fullname, userid) {
    console.log("fullname is: " + fullname + " userid is: " + userid);
    $("#add-staff")[0].reset();
    $('#staff_id').val(userid);
    $('#staff_title').text(fullname);

    $('#exampleModal').modal({ backdrop: 'static', keyboard: false, show: true })
}

function edit_payroll(userid) {
    console.log("userid is: " + userid);
    $("#add-staff")[0].reset();
    $('#staff_id').val(userid);
    $('#staff_title').text(fullname);

    $.ajax({
        url: 'staff/staff_list/' + id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {

            data.forEach(function (staff) {
                console.log(staff);
                $('#staff_id').val(staff.uuid);
                $('#c-first-name').val(staff.first_name);
                $('#c-last-name').val(staff.last_name);
                $('#c-username').val(staff.username);
                $('#c-email').val(staff.email);
                $('#c-phone').val(staff.phone);
                $('#c-branch').val(staff.branch_id).change();
            });

        }
    });

    $('#exampleModal').modal({ backdrop: 'static', keyboard: false, show: true })
}

function edit_payroll(id, roleid) {
    $("#add-staff")[0].reset();
    $.ajax({
        url: 'staff-payroll/get-staff-payroll-details/' + id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            console.log(data);

            if (data.status == 1) {

                $('#staff_id').val(data.data.staff_id);
                $('#employment_type').val(data.data.employment_type_id);
                $('#basic_salary').val(data.data.basic_salary);
                $('#day_of_payment').val(data.data.day_of_payment).change();
                $('#house_allowance').val(data.data.house_allowance);
                $('#medical_allowance').val(data.data.medical_allowance);
                $('#special_allowance').val(data.data.special_allowance);
                $('#provident_fund_contribution').val(data.data.provident_fund_allowance);
                $('#other_allowances').val(data.data.other_allowances);
                $('#tax_deductions').val(data.data.tax_deductions);
                $('#provident_fund_deduction').val(data.data.provident_fund_deduction);
                $('#pension_deduction').val(data.data.pension_deduction);
                $('#other_deductions').val(data.data.other_deductions);
                $('#gross_salary').val(data.data.gross_salary);
                $('#total_deductions').val(data.data.total_deduction);
                $('#net_salary').val(data.data.net_salary);
                if (data.data.payment_method == 1) {
                    $('#bank').val(data.data.bank.bank_code);
                    $('#account_number').val(data.data.account_number);
                    $('#account_name').val(data.data.account_name);

                    $('#bankDiv').show();
                    $('#accountNumberDiv').show();
                    $('#accountNameDiv').show();
                    $('#walletAccountDiv').hide();
                    $('#walletAccountNameDiv').hide();

                } else {
                    $('#wallet_account_number').val(data.data.wallet_account_number);
                    $('#wallet_account_name').val(data.data.wallet_account_name);

                    
                    $('#bankDiv').hide();
                    $('#accountNumberDiv').hide();
                    $('#accountNameDiv').hide();
                    $('#walletAccountDiv').show();
                    $('#walletAccountNameDiv').show();
                }
                $('#payment_mode').val(data.data.payment_mode);
                $('#overtime').val(data.data.overtime_allowance);
                $('#transport_allowance').val(data.data.transport_allowance);
                $('#paid_leave').val(data.data.paid_leave);
                $('#unpaid_leave').val(data.data.unpaid_leave_deduction);
                $('#loan_deduction').val(data.data.loan_deduction);
                $('#paymentMethod').val(data.data.payment_method).change();
            } else {
                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }
        }
    });
    //edit_role(roleid); for multiple checkbox rolee
    $('#permission').val(roleid).change(); //for select option
    $('#hidepsw').hide();
    $('#exampleModal').modal('show');
}

function edit_role(id) {

    $.ajax({
        url: 'role/get-permission/' + id,
        type: 'post',
        data: {},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('input:checkbox').prop('checked', false);
            data.forEach(function (item) {
                //  

                $('input[value="' + item.permission_id + '"]:checkbox').prop('checked', true);
            });
        }
    });

}
renderstaff();