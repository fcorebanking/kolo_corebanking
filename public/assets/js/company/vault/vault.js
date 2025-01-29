document.getElementById('deposit_vault').style.display = 'none';
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