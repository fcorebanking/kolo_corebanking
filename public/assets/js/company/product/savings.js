function btnadd() {
    $name = $('#name').val();
    $code = $('#code').val();
    $interest = $('#interest').val();
    $min_withdraw = $('#min_withdraw').val();
    $max_deposit = $('#max_balance').val();
    $main_charges = $('#maintenance_charges').val();
    $interestamt = $('#interestamt').val();
    $max_daily_transfer = $('#max_daily_transfer').val();
    $max_withdraw = $('#max_withdraw').val();
    $maintenace_duration = $('#maintenace_duration').val();

    var bank_transfer_charge_type = $('#bank_transfer_charge_type').val();

    setChargeType();

    var max_bank_transfer_charge_or_flat_fee = $('#max_bank_transfer_charge_or_flat_fee').val();
    var min_bank_transfer_charge_or_percentage = $('#min_bank_transfer_charge_or_percentage').val();


    $duration = $('#duration').val();
    $freq = $('#freq').val();
    $min_balance = $('#min_balance').val();
    $reg_fee = $('#reg_fee').val();
    $token = $('.token').val();
    $data_info = {
        registration_fee: $reg_fee,
        minimum_balance: $min_balance,
        duration: $duration,
        interestamt: $interestamt,
        freq: $freq,
        main_charges: $main_charges,
        min_withdraw: $min_withdraw,
        max_deposit: $max_deposit,
        name: $name,
        interest: $interest,
        _token: $token,
        max_withdraw: $max_withdraw,
        max_daily_transfer: $max_daily_transfer,
        bank_transfer_charge_type,
        max_bank_transfer_charge_or_flat_fee,
        min_bank_transfer_charge_or_percentage
    };
    ajax_operation('put', 'savings', $data_info);
    rendertable();

}

function update_btn() {
    var id = $('#edit_id').val();
    var status = $('#edit_freq').val();
    var name = $('#edit_name').val();
    var interest = $('#edit_interest').val();
    var interestamt = $('#editinterestamt').val();
    var editduration = $('#editduration').val();
    var editmaintenace_duration = $('#editmaintenace_duration').val();
    var min_withdraw = $('#edit_min_withdraw').val();
    var max_deposit = $('#edit_max_deposite').val();
    var main_charges = $('#edit_maintenance_charges').val();
    var min_balance = $('#edit_min_balance').val();
    var reg_fee = $('#edit_reg_fee').val();
    var max_daily_transfer = $('#edit_max_daily_transfer').val();
    var max_withdraw = $('#edit_max_withdraw').val();
    var token = $('.token').val();
    $code = $('#code').val();

    var max_bank_transfer_charge_or_flat_fee = $('#edit_max_bank_transfer_charge_or_flat_fee').val();
    var min_bank_transfer_charge_or_percentage = $('#edit_min_bank_transfer_charge_or_percentage').val();
    var bank_transfer_charge_type = $('#edit_bank_transfer_charge_type').val();

    var data_info = {
        code: $code,
        registration_fee: reg_fee,
        minimum_balance: min_balance,
        duration: editduration,
        interestamt: interestamt,
        id: id,
        max_daily_transfer: max_daily_transfer,
        max_withdraw: max_withdraw,
        freq: status,
        main_charges: main_charges,
        min_withdraw: min_withdraw,
        max_deposit: max_deposit,
        name: name,
        interest: interest,
        _token: token,
        maintenace_duration: editmaintenace_duration,
        max_bank_transfer_charge_or_flat_fee: max_bank_transfer_charge_or_flat_fee,
        min_bank_transfer_charge_or_percentage: min_bank_transfer_charge_or_percentage,
        bank_transfer_charge_type: bank_transfer_charge_type
    };

    var methodType = $('#operation_type').val() == "add" ? 'put' : 'post';

    ajax_operation(methodType, 'savings', data_info);
    rendertable();
}

function delete_record(val) {
    if (confirm("Are you sure, you want to delete this record?") == true) {
        ajax_operation('delete', 'savings/' + val, { id: val });
        rendertable();
    }
    return false;
}

function rendertable() {
    $send_method = 'GET';
    $pageUrl = 'fetch_savings';
    $data_info = {}
    ajax_fetch($send_method, $pageUrl, $data_info);


}
rendertable();

function edit_product(
    operationType,
    id = "",
    name = "",
    interest = "",
    freq = "",
    minwith = "",
    maxdep = "",
    maincharge = "",
    edit_max_withdraw = "",
    edit_max_daily_transfer = "",
    interestamt = "",
    duration = "",
    minimum_balance = "",
    registration_fee = "",
    maintenace_duration = "",
    bank_transfer_charge_type = "",
    min_bank_transfer_charge_or_percentage = "",
    max_bank_transfer_charge_or_flat_fee = "",
    code = ""
) {


    $('#accountProductForm')[0].reset();

    $('#exampleModalLabel').text(operationType == 'add' ? 'Add Product' : 'Edit Product');
    $('#productBtn').text(operationType == 'add' ? 'Add Product' : 'Edit Product');

    $('#operation_type').val(operationType);

    $('#editexampleModal').modal('show');

    if (operationType == 'add') return;

    $('#code').val(code);
    $('#edit_name').val(name);
    $('#edit_interest').val(interest);
    $('#edit_max_withdraw').val(edit_max_withdraw);
    $('#edit_max_daily_transfer').val(edit_max_daily_transfer);
    $('#edit_freq').val(freq).change();
    $('#editinterestamt').val(interestamt).change();
    $('#editmaintenace_duration').val(maintenace_duration).change();

    $('#edit_bank_transfer_charge_type').val(bank_transfer_charge_type).change();

    updateChargeType();

    $('#edit_max_bank_transfer_charge_or_flat_fee').val(max_bank_transfer_charge_or_flat_fee).change();
    $('#edit_min_bank_transfer_charge_or_percentage').val(min_bank_transfer_charge_or_percentage).change();

    $('#edit_min_withdraw').val(minwith);
    $('#editduration').val(duration);
    $('#edit_max_deposite').val(maxdep);
    $('#edit_maintenance_charges').val(maincharge).change();;

    $('#edit_min_balance').val(minimum_balance);
    $('#edit_reg_fee').val(registration_fee);

    $('#edit_id').val(id);
    $('select[name="edit_freq"]').val(freq).change();

}


function setChargeType() {
    var status = $('#bank_transfer_charge_type').val();

    if (status == '0') {
        $('#min_bank_transfer_charge_or_percentage_div').hide();
        $('#max_bank_transfer_charge_or_flat_fee_label').text('Flat Fee')
    } else {
        $('#min_bank_transfer_charge_or_percentage_div').show();
        $('#max_bank_transfer_charge_or_flat_fee_label').text('Charge Capped At:')
    }
}

function updateChargeType() {
    var status = $('#edit_bank_transfer_charge_type').val();

    if (status == '0') {
        $('#edit_min_bank_transfer_charge_or_percentage_div').hide();
        $('#edit_max_bank_transfer_charge_or_flat_fee_label').text('Flat Fee')
    } else {
        $('#edit_min_bank_transfer_charge_or_percentage_div').show();
        $('#edit_max_bank_transfer_charge_or_flat_fee_label').text('Charge Capped At:')
    }
}