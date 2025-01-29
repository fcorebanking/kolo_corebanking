// function
// $('#esusu-table').hide();
// document.getElementById( 'esusu-table' ).style.display = 'none';
// document.getElementById( 'esusu-table' ).style.display = 'none';

function renderPage($page,$status) {
    $('#html5-extension').DataTable().ajax.url("/userDataSource/"+$page+"?is_agent="+$status).load();
}
function approved($id){
    // document.getElementById( 'esusu-table' ).style.display = 'none';
    ajax_operation('post', '/company/mobile/esusu/approve-agent/'+$id, {});
    $('#html5-extension').DataTable().ajax.reload();
}
function disable($id){
    // document.getElementById( 'esusu-table' ).style.display = 'none';
    ajax_operation('post', '/company/mobile/esusu/disabled-agent/'+$id, {});
    $('#html5-extension').DataTable().ajax.reload();
}
function searchrange_callover() {
    $from = $('#from').val();
    $to = $('#to').val();
    $type = $('#transactiontype').val();
    $('#html5-extension').DataTable().ajax.url("/company/mobile-customer-transaction?from="+$from+"&to="+$to+"&type="+$type).load();
}
