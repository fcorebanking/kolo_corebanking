var _getValidationField = document.getElementsByClassName('validation-text');


ocument.getElementById('deposit_vault').style.display = 'none';
document.getElementById('staff_deposit').style.display = 'none';
document.getElementById('branch_deposit').style.display = 'none';
document.getElementById('create_vault').style.display = 'none';

function transfer_vault() {
    document.getElementById('transfer_vault').style.display = 'block';
    document.getElementById('deposit_vault').style.display = 'none';
    document.getElementById('staff_deposit').style.display = 'none';
    document.getElementById('branch_deposit').style.display = 'none';
    document.getElementById('create_vault').style.display = 'none';
}

function deposit_vault() {
    document.getElementById('transfer_vault').style.display = 'none';
    document.getElementById('deposit_vault').style.display = 'block';
    document.getElementById('staff_deposit').style.display = 'none';
    document.getElementById('branch_deposit').style.display = 'none';
    document.getElementById('create_vault').style.display = 'none';

}

function staff_deposit() {
    document.getElementById('transfer_vault').style.display = 'none';
    document.getElementById('deposit_vault').style.display = 'none';
    document.getElementById('staff_deposit').style.display = 'block';
    document.getElementById('branch_deposit').style.display = 'none';
    document.getElementById('create_vault').style.display = 'none';

}

function branch_deposit() {
    document.getElementById('transfer_vault').style.display = 'none';
    document.getElementById('deposit_vault').style.display = 'none';
    document.getElementById('staff_deposit').style.display = 'none';
    document.getElementById('branch_deposit').style.display = 'block';
    document.getElementById('create_vault').style.display = 'none';

}

function view_vault() {
    document.getElementById('transfer_vault').style.display = 'none';
    document.getElementById('deposit_vault').style.display = 'none';
    document.getElementById('staff_deposit').style.display = 'none';
    document.getElementById('branch_deposit').style.display = 'none';
    document.getElementById('create_vault').style.display = 'block';
}


function send_voucher() {
    $amount = $('#amount').val();
    ajax_operation('post', '/company/vault/send-voucher', { amount: $amount });
    $('#html5-extension').DataTable().ajax.url("/company/vault-ajax?page=voucher").load();
    get_vaults();
}

function approveVoucherModal(vaultID, amount) {
    $('.voucherModel').modal('show')
    $('#selectedtxn').val(vaultID)
    $('.vaultamountdisplay').html(amount)

    viewVault(null)

}

function approveVoucher() {

    if ($('#selectedaction').val() === null) {
        alert('select action')
        return false;
    }
    let selectedvaultid = null;
    if ($('#selectedaction').val() === 'branchvault') {
        selectedvaultid = $('#branchvaultvalue').val()
    } else if ($('#selectedaction').val() === 'staffvault') {
        selectedvaultid = $('#staffvaultvalue').val()
    } else if ($('#selectedaction').val() === 'othervault') {
        selectedvaultid = $('#othervaultvalue').val()
    } else {
        selectedvaultid = null
    }
    const data_val = {
        selectedaction: $('#selectedaction').val(),
        selectedvaultid,

    }
    ajax_operation('post', '/company/vault/approve-voucher/' + $('#selectedtxn').val(), data_val);
    $('#html5-extension').DataTable().ajax.url("/company/vault-ajax?page=voucher").load();
    get_vaults();
}

function viewVault(view = null) {
    if (view == null) {
        document.getElementById('branchvault').style.display = 'none';
        document.getElementById('othervault').style.display = 'none';
        document.getElementById('staffvault').style.display = 'none';
        $('#selectedaction').val(null)
        $('.approveVoucherbtn').hide();

    }
    if (view == 'branchvault') {

        document.getElementById('branchvault').style.display = 'block';
        document.getElementById('othervault').style.display = 'none';
        document.getElementById('staffvault').style.display = 'none';
        $('#selectedaction').val('branchvault')
        $('.approveVoucherbtn').show();

    }
    if (view == 'othervault') {
        document.getElementById('othervault').style.display = 'block';
        document.getElementById('branchvault').style.display = 'none';
        document.getElementById('staffvault').style.display = 'none';
        $('#selectedaction').val('othervault')
        $('.approveVoucherbtn').show();

    }
    if (view == 'staffvault') {
        document.getElementById('staffvault').style.display = 'block';
        document.getElementById('branchvault').style.display = 'none';
        document.getElementById('othervault').style.display = 'none';
        $('#selectedaction').val('staffvault')
        $('.approveVoucherbtn').show();

    }
}


function rejectVoucher(vaultID) {
    ajax_operation('post', '/company/vault/reject-voucher/' + vaultID, { voucherIDID: vaultID });
    $('#html5-extension').DataTable().ajax.url("/company/vault-ajax?page=voucher").load();
}

function send_transfer() {
    $source_wallet = $('#source_wallet').val();
    $destination = $('#destination').val();
    $amount = $('#amount').val();
    ajax_operation('post', '/company/vault/send-transfer', { amount: $amount, source_wallet: $source_wallet, destination_wallet: $destination });
    $('#html5-extension').DataTable().ajax.url("/company/vault-ajax").load();
    get_vaults();
}

function send_deposit() {
    $wallet = $('#wallet').val();
    $amount_deposit = $('#amount_deposit').val();
    ajax_operation('post', '/company/vault/send-wallet', { amount: $amount_deposit, wallet: $wallet });
    $('#html5-extension').DataTable().ajax.url("/company/vault-ajax").load();
    get_vaults();
}

function send_staff_deposit() {
    $staff = $('#staff').val();
    $amount_staff = $('#amount_staff').val();
    $staff_credit_option = $('#staff_credit_option').val();
    ajax_operation('post', '/company/vault/send-staff-wallet', { amount: $amount_staff, staff: $staff, staff_credit_option: $staff_credit_option });
    $('#html5-extension').DataTable().ajax.url("/company/vault-ajax").load();
    get_vaults();
}

function send_branch_deposit() {
    $branch = $('#branch').val();
    $amount_branch = $('#amount_branch').val();
    ajax_operation('post', '/company/vault/send-branch-wallet', { amount: $amount_branch, branch: $branch });
    $('#html5-extension').DataTable().ajax.url("/company/vault-ajax").load();
    get_vaults();
}

function create_vault() {
    $new_vault = $('#new_vault').val();
    ajax_operation('post', '/company/vault/create-vault', { new_vault: $new_vault });
    $('#html5-extension').DataTable().ajax.url("/company/vault-ajax").load();
    get_vaults();
}

function verifyBankAccount() {

    var transferBankCode = $('#transferBankCode').val();
    var transferAccountNumber = $('#transferAccountNumber').val();

    if (transferBankCode != null) _getValidationField[4].style.display = 'none';

    $('#transferAccountName').val("");

    console.log(transferBankCode + " " + transferAccountNumber);
    if (transferAccountNumber.length == 10 && transferBankCode != "") {
        console.log("Verifying account number " + transferAccountNumber);

        $("#myBankLoader").show();

        $('#transferAccountName').val("");
        _getValidationField[5].style.display = 'none';


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

                console.log(data.status, "Response status");

                if (data.status == 1) {

                    var newAccountNumber = $('#transferAccountNumber').val();

                    if (newAccountNumber.length == 10) {
                        $('#transferAccountName').val(data.data.account_name);
                        $('#bankName').val(data.data.bank_name);
                        $('#requestReference').val(data.data.request_reference);
                        $('#sessionID').val(data.data.session_id);
                        _getValidationField[5].style.display = 'none';
                    } else {
                        $('#transferAccountName').val("");
                        $('#bankName').val("");
                        _getValidationField[5].innerHTML = 'Invalid Account Number';
                        _getValidationField[5].style.display = 'block';
                    }
                } else {
                    console.log(data);
                    _getValidationField[5].innerHTML = 'Invalid Account Number';
                    _getValidationField[5].style.display = 'block';
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

        // $('.toast').toast('show');
    } else {
        $('#accountName').val('');
        $("#myBankLoader").hide();
    }
}