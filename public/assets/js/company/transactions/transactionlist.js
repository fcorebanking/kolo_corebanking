function searchTXN() {
    $from = $('#from').val();
    $to = $('#to').val();
    $status = $('#status').val();
    $transaction_type = $('#transaction_type').val();
    $view_select=$('#view-select').val();
    $('#html5-extension').DataTable().ajax.url("/company/transaction/list?from="+$from+"&to="+$to+"&paginate="+$view_select+"&transaction_type="+$transaction_type+"&status="+$status).load();
    }
