$('#customerx').select2({
    placeholder: 'Select Staff',
    ajax: {
        url: '/userDataSelectStaff',
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


function filterLoanDate() {

    disableBtn('click-button');
    startLoadingButton('click-button');
    $from = $('#duefrom').val();
    $to = $('#dueto').val();
    $officer = $('#officer').val();
    $branch = $('#branch').val();
    $('#html5-extension').DataTable().ajax.url("/company/loan/due-loan-collection?from=" + $from + "&to=" + $to + "&branch=" + $branch + "&officer=" + $officer).load();
    stopLoadingButton('click-button', 'Filter');
    enableBtn('click-button');
    return false;
    $.ajax({
        url: '/company/loan/due-loan',
        type: 'post',
        data: { from: $from, to: $to, branch: $branch, officer: $officer },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            stopLoadingButton('click-button', 'Filter');
            enableBtn('click-button');
            $report = data.message;
            $('#dueLoanRecTable').empty();
            $report.forEach(function(tablerender) {
                $('#dueLoanRecTable').append('\
                <tr>\
                <th scope="row">' + tablerender.sn + '</th>\
                <td>' + tablerender.name + '</td>\
                <td>' + tablerender.phone + '</td>\
                <td>' + tablerender.accountOff + '</td>\
                <td>' + tablerender.ref + '</td>\
                <td>' + tablerender.loanFee + '%</td>\
                <td>' + tablerender.loanInterest + '%</td>\
                <td>' + tablerender.repaymentDate + '</td>\
                <td>' + tablerender.repaymentAmt + '</td>\
                <td>' + tablerender.amountpaid + '</td>\
                <td>' + tablerender.totalDue + '</td>\
                <td>' + tablerender.maturityDate + '</td>\
                <td>' + tablerender.branch + '</td>\
                <td>' + tablerender.officer + '</td>\
                <td>' + tablerender.accountBalance + '</td>\
                <td>' + tablerender.totalpaid + '</td>\
                <td>' + tablerender.loanstatus + '</td>\
                <td>' + tablerender.outstandingpay + '</td>\
                </tr>\
                ');
            });
        }
    })
}