$('#customerx').select2({
    placeholder: 'Select an Customer',
    ajax: {
        url: '/userDataSelect',
        dataType: 'json',
        delay: 100,
        data: function (data) {
            console.log('esusu ' + JSON.stringify(data, null, 2));
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


//esusu/product-list
$('#esusu_product').select2({
    placeholder: 'Select Esusu Product',
    ajax: {
        url: '/company/esusu/product-list',
        dataType: 'json',
        delay: 100,
        data: function (data) {
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

function select_tool() {
    $val = $('#customerx').val();
    $('#savetype').select2({
        placeholder: 'Select an Account Wallet',
        ajax: {
            url: '/userDataSelect/' + $val,
            dataType: 'json',
            delay: 100,
            data: function (data) {
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

}

function pop_modal($id) {
    $('#productid').val($id);
    $('#esusu_type').val('');
    $('#min_rate').val(0);
    $('#max_rate').val(0);
    $('#circle_charge').val(0);
    $('#duration').val(30);
    $('#overdraft_charge').val(0);

}

function btnadd() {
    $esusu_type = $('#esusu_type').val();
    $min_rate = $('#min_rate').val();
    $max_rate = $('#max_rate').val();
    $circle_charge = $('#circle_charge').val();
    $duration = $('#duration').val();
    $overdraft_charge = $('#overdraft_charge').val();
    $productid = $('#productid').val();
    const status = $('#status').val();
    $data_info = { status, productid: $productid, overdraft_charge: $overdraft_charge, esusu_type: $esusu_type, min_rate: $min_rate, max_rate: $max_rate, circle_charge: $circle_charge, duration: $duration };

    ajax_operation('put', '/company/esusu/product', $data_info);

    $('#html5-extension').DataTable().ajax.url('/company/esusu/product').load();
}

function edit_product($id) {
    $.ajax({
        url: '/company/esusu/edit/' + $id,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            pop_modal(data.id);
            $('#esusu_type').val(data.esusu_type);
            $('#min_rate').val(data.min_rate);
            $('#max_rate').val(data.max_rate);
            $('#circle_charge').val(data.circle_charge);
            $('#duration').val(data.duration);
            $('#overdraft_charge').val(data.overdraft_charge);
            $('#productid').val(data.id);
            $('#status').val(data.status).change();

        }
    });
}
//

//
function delete_product($id) {
    swal({
        title: 'Are you sure you want to delete this product?',
        text: "Note: Products with running Esusu Cannot be deleted but can be disabled",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        padding: '2em'
    }).then(function (result) {
        if (result.value) {
            ajax_operation('delete', '/company/esusu/delete-product/' + $id, {});
            $('#html5-extension').DataTable().ajax.url('/company/esusu/product').load();
        }
    });

}

function rendersave() {
    $val = $('#customerx').val();
    $('#savetype').select2({
        placeholder: 'Select Account Type',
        ajax: {
            url: '/userDataSelect/' + $val,
            dataType: 'json',
            delay: 250,
            data: function (data) {
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
}
// viewdata("/userDataSource/card_request", "get");
function btncreate_esusu() {
    $savetype = $('#savetype').val();
    $esusu_product = $('#esusu_product').val();
    $rate = $('#rate').val();
    $start_date = $('#start_date').val();

    $data_info = { savetype: $savetype, esusu_product: $esusu_product, rate: $rate, start_date: $start_date };

    ajax_operation('post', '/company/esusu/create-esusu-wallet', $data_info, 'esusu-create', 'Create Account');


    $('#html5-extension').DataTable().ajax.url('/company/esusu/view-data').load();
}


function pop_esusu_txn(esusuid, txn) {
    $('#esusu_id').val(esusuid);
    if (txn == 1) {
        $('.deposit-esusu-form').show();
        $('.withdraw-esusu-form').hide();
        $('.overdraft-esusu-form').hide();
        $('.reset-esusu-form').hide();

    } else if (txn == 2) {
        $('.withdraw-esusu-form').show();
        $('.deposit-esusu-form').hide();
        $('.overdraft-esusu-form').hide();
        $('.reset-esusu-form').hide();

    } else if (txn == 3) {
        $('.withdraw-esusu-form').hide();
        $('.deposit-esusu-form').hide();
        $('.overdraft-esusu-form').show();
        $('.reset-esusu-form').hide();
    } else if (txn == 4) {
        $('.withdraw-esusu-form').hide();
        $('.deposit-esusu-form').hide();
        $('.overdraft-esusu-form').hide();
        $('.reset-esusu-form').show();

    }

}

function perform_deposit() {
    $esusuid = $('#esusu_id').val();
    $number = $('#number').val();
    $method = $('#method').val();
    $ledger_cr = $('#ledger_cr').val();
    $ledger_dr = $('#ledger_dr').val();
    $data_info = { esusuid: $esusuid, number: $number, ledger_cr: $ledger_cr, ledger_dr: $ledger_dr, method: $method };
    ajax_operation('post', '/company/esusu/deposit-esusu-wallet', $data_info, 'esusu-deposit', 'Topup');
    $('#html5-extension').DataTable().ajax.url('/company/esusu/view-data').load();
}

function perform_withdraw() {
    $esusuid = $('#esusu_id').val();
    $amount = $('#withdraw_amount').val();

    $withdraw_to_wallet = 0;
    $wallet_to_main = 0;
    $wallet_to_main = $("input[name='wallet_to_main']:checked").val()

    if ($('#wallet_to_main').is(":checked")) {
        // alert();
        // $wallet_to_main = $('#wallet_to_main').val();
    }
    if ($('#withdraw_to_wallet').is(":checked")) {
        $withdraw_to_wallet = 1;
    }
    $ledger_cr = $('#ledger_cr').val();
    $ledger_dr = $('#ledger_dr').val();
    $data_info = { esusuid: $esusuid, amount: $amount, wallet_to_main: $wallet_to_main, withdraw_to_wallet: $withdraw_to_wallet, ledger_cr: $ledger_cr, ledger_dr: $ledger_dr };
    ajax_operation('post', '/company/esusu/withdraw-esusu-wallet', $data_info, 'esusu-debit', 'Withdraw');
    $('#html5-extension').DataTable().ajax.url('/company/esusu/view-data').load();

}

function perform_overdraft() {
    $esusuid = $('#esusu_id').val();
    $number = $('#overdraft_amount').val();
    $ledger_cr = $('#ledger_cr').val();
    $ledger_dr = $('#ledger_dr').val();

    $data_info = { esusuid: $esusuid, amount: $number, ledger_cr: $ledger_cr, ledger_dr: $ledger_dr };
    ajax_operation('post', '/company/esusu/overdraft-esusu-wallet', $data_info, 'esusu-overdraft', 'Apply for Overdraft');
    $('#html5-extension').DataTable().ajax.url('/company/esusu/view-data').load();

}

function perform_reset() {
    $esusuid = $('#esusu_id').val();
    $reset_rate = $('#reset_rate').val();
    $reset_date = $('#reset_date').val();
    $data_info = { esusuid: $esusuid, reset_rate: $reset_rate, reset_date: $reset_date };
    ajax_operation('post', '/company/esusu/reset-esusu-wallet', $data_info);
    $('#html5-extension').DataTable().ajax.url('/company/esusu/view-data').load();

}
$("#pending_approval_form").on('submit', function () {

    var formData = $(this).serialize();

    $link = '/company/esusu/post-pending-esusu';
    var formData = $(this).serialize();
    ajax_operation('post', $link, formData);
    $('#html5-extension').DataTable().ajax.url('/company/esusu/display-pending-esusu').load();
    return false;
});

function renderStaff() {
    $branch = $('#branch').val();
    $.ajax({
        url: '/company/{{auth()->user()->company->short_name}}/branch/branch_staff/' + $branch,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            $('select[name="officer"]').empty();

            data.forEach(function (staff) {

                $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
            });
            $officer = $('#officer').val();
            $('#html5-extension').DataTable().ajax.url("/company/esusu/display-pending-esusu?staff=" + $officer).load();

        }
    });
}

function renderStaff_pending() {
    $officer = $('#officer').val();
    $('#html5-extension').DataTable().ajax.url("/company/esusu/display-pending-esusu?staff=" + $officer).load();
}

function searchByData(uid) {
    $datepicker = $('#datepicker').val();
    $datepicker2 = $('#datepicker2').val();
    $('.statementtext').html('Statement From ' + $datepicker + ' To ' + $datepicker2);
    $('#html5-extension').DataTable().ajax.url("/company/view-esusu/statement?esusu=" + uid + "&from=" + $datepicker + "&to=" + $datepicker2).load();
}