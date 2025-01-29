function loanrollover(uuid, loanID) {
    $('#rollOver').show();
    $('#viewdetails').hide();
    $('#loanID').val(loanID);

    $('#UserId').val(uuid);
    $('#exampleModal').modal('show');
    $.ajax({
        url: '/company/loan/load-user-loan/' + uuid + '/' + loanID,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('#destinationLoan').empty();
            $('.loanID').html(data.detail.ref + '<br> (' + (Number(data.detail.loanBalance)) + ')');
            $loandata = data.loan;
            $loandata.forEach(function(dataView) {
                $('#destinationLoan').append('<option value="' + dataView.ref + '">' + dataView.ref + '</option>');
            });
        }
    });
}

function rollLoanee() {
    var formData = $('#ongoingloan').serialize();
    ajax_operation('post', '/company/loan/roll-loan-over', formData, 'rollLoan', 'Roll <i class="fa fa-arrow-right"></i>');
    $('#html5-extension').DataTable().ajax.reload();
    $('#exampleModal').modal('toggle');
    $('#ongoingloan')[0].reset();
}
$('#writeoffSection').hide();

function loanWrite(uuid) {
    viewLoan(uuid);
    $('#writeoffSection').show();
}

function writeloanoff() {
    var formData = $('#ongoingloan').serialize();
    formData.amount = $('#writeoffamount').val()
    formData.option = $('#writeoffoptions').val()
    if ($('#writeoffoptions').val() > 1 && $('#writeoffamount').val() < 1) {
        alert('Enter valide amount');
        return false
    }
    ajax_operation('post', '/company/loan/writeoff-loan-over', formData, 'writeoff', 'Write Loan Off <i class="fa fa-arrow-right"></i>');
    $('#html5-extension').DataTable().ajax.reload();
    $('#exampleModal').modal('toggle');
    $('#ongoingloan')[0].reset();
}

function liquidate() {
    // alert("hello");
    var formData = $('#ongoingloan').serialize();
    ajax_operation('post', '/company/loan/liquidate-loan', formData, 'liquedateID', 'Liquidate <i class="fa fa-arrow-right"></i>');
    $('#html5-extension').DataTable().ajax.reload();
    $('#exampleModal').modal('toggle');
    $('#ongoingloan')[0].reset();
}

function showTotalPrincipalLeft() {
    var dataTable = $('#html5-extension').DataTable();
    // Set the new URL
    var newUrl = '/company/loan/ongoing-data?showTotal=true&paginate=no';
    dataTable.ajax.url(newUrl).load();
}
// &