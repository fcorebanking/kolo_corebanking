function addFunct() {
    $('#editid').val('')
    $('#exampleModal').modal('show');
}

function editFunct(editId, name, start_date, end_date, minimun, maximal, status, circle_out_charge, withdraw_out_charge, t_c, agent_commission, description) {
    $('#exampleModal').modal('show');
    $('#name').val(name)
    $('#editid').val(editId)
    $('#start_date').val(start_date)
    $('#end_date').val(end_date)
    $('#minimun').val(minimun)
    $('#maximal').val(maximal)
    $('#status').val(status).change()
    $('#circle_out_charge').val(circle_out_charge)
    $('#withdraw_out_charge').val(withdraw_out_charge)

    $('#t_c').val(t_c)
    $('#agent_commission').val(agent_commission)
    $('#description').val(description)

}

$('#customerx').select2({
    placeholder: 'Select an Customer',
    ajax: {
        url: '/userDataSelect',
        dataType: 'json',
        delay: 100,
        data: function(data) {
            return {
                searchTerm: data.term // search term
            };
        },
        processResults: function(response) {
            return {
                results: response
            };
        },
        cache: true
    }
});
//esusu/product-list
// /
// $('#staff_select').select2({
//     placeholder: 'Select Staff',
//     ajax: {
//         url: '/company/staff/staff_list',
//         dataType: 'json',
//         delay: 100,
//         data: function(data) {
//             return {
//                 searchTerm: data.term // search term
//             };
//         },
//         processResults: function(response) {
//             return {
//                 results: response
//             };
//         },
//         cache: true
//     }
// });
$('#esusu_product').select2({
    placeholder: 'Select Foodstash Product',
    ajax: {
        url: '/company/esusu/schedule/product-list',
        dataType: 'json',
        delay: 100,
        data: function(data) {
            return {
                searchTerm: data.term // search term
            };
        },
        processResults: function(response) {
            return {
                results: response
            };
        },
        cache: true
    }
});
$('#foodstash_product').select2({
    placeholder: 'Select Foodstash Product',
    ajax: {
        url: '/company/esusu/schedule/product-list',
        dataType: 'json',
        delay: 100,
        data: function(data) {
            return {
                searchTerm: data.term // search term
            };
        },
        processResults: function(response) {
            return {
                results: response
            };
        },
        cache: true
    }
});

function btncreate_esusu() {
    $account = $('#customerx').val();
    $esusu_product = $('#esusu_product').val();
    $rate = $('#rate').val();
    $start_date = $('#start_date').val();


    // const t_c = $('#t_c').val()
    // const agent_commission = $('#agent_commission').val()
    // const description = $('#description').val()

    $data_info = { account: $account, esusu_product: $esusu_product, rate: $rate, start_date: $start_date };
    ajax_operation('post', '/company/esusu/schedule/operation/store', $data_info, 'esusu-create', 'Create Account');
}

function pop_esusu_txn(esusuid, txn) {
    $('#esusu_id').val(esusuid);
    if (txn == 1) {
        $('.deposit-esusu-form').show();
        $('.withdraw-esusu-form').hide();
        $('.overdraft-esusu-form').hide();
        $('.reset-esusu-form').hide();
        $('.staff').hide();


    } else if (txn == 2) {
        $('.withdraw-esusu-form').show();
        $('.deposit-esusu-form').hide();
        $('.overdraft-esusu-form').hide();
        $('.reset-esusu-form').hide();
        $('.staff').hide();

    } else if (txn == 3) {
        $('.withdraw-esusu-form').hide();
        $('.deposit-esusu-form').hide();
        $('.overdraft-esusu-form').show();
        $('.reset-esusu-form').hide();
        $('.staff').hide();

    } else if (txn == 4) {
        $('.withdraw-esusu-form').hide();
        $('.deposit-esusu-form').hide();
        $('.overdraft-esusu-form').hide();
        $('.reset-esusu-form').show();
        $('.staff').hide();


    } else if (txn == 5) {
        $('.withdraw-esusu-form').hide();
        $('.deposit-esusu-form').hide();
        $('.overdraft-esusu-form').hide();
        $('.reset-esusu-form').hide();
        $('.staff').show();

    }

}

function perform_deposit() {
    $esusuid = $('#esusu_id').val();
    $number = $('#number').val();
    method = $('#method').val();
    $ledger_cr = $('#ledger_cr').val();
    $ledger_dr = $('#ledger_dr').val();
    $data_info = { esusuid: $esusuid, number: $number, method };
    ajax_operation('post', '/company/esusu/schedule/operation/topup', $data_info, 'esusu-deposit', 'Topup');
    $('#html5-extension').DataTable().ajax.url('/company/esusu/schedule/operation/table').load();
}

function perform_withdraw() {
    $esusuid = $('#esusu_id').val();
    $amount = $('#withdraw_amount').val();

    $data_info = { esusuid: $esusuid, amount: $amount };
    ajax_operation('post', '/company/esusu/schedule/operation/withdraw', $data_info, 'esusu-debit', 'Withdraw');
    $('#html5-extension').DataTable().ajax.url('/company/esusu/schedule/operation/table').load();

}

function perform_reset() {
    $esusuid = $('#esusu_id').val();
    $reset_rate = $('#reset_rate').val();
    $reset_date = $('#reset_date').val();
    $productid = $('#productid').val();
    $data_info = { product_id: $productid, esusuid: $esusuid, reset_rate: $reset_rate, reset_date: $reset_date };
    ajax_operation('post', '/company/esusu/schedule/operation/reset', $data_info);
    $('#html5-extension').DataTable().ajax.url('/company/esusu/schedule/operation/table').load();

}

function search() {
    const foodstash_product = $('#foodstash_product').val();
    const status = $('#status').val();
    $('#html5-extension').DataTable().ajax.url(`/company/esusu/schedule/operation/table?product=${foodstash_product}&status=${status}`).load();
}

function changeAgent() {
    const agent = $('#staff_select').val();
    const esusuid = $('#esusu_id').val();

    ajax_operation('post', '/company/esusu/schedule/operation/agent', { agent, esusuid });
}