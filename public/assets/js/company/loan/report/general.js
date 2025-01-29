function FliterMissedloan() {
    // btn-primary
    disableBtn('click-button');
    startLoadingButton('click-button');
    $from = $('#duefrom').val();
    $to = $('#dueto').val();
    $officer = $('#officer').val();
    $branch = $('#branch').val();
    $.ajax({
        url: '/company/loan/missed-loan-data',
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
                console.log(tablerender);

                $('#dueLoanRecTable').append('\
                <tr>\
                <th scope="row">' + tablerender.sn + '</th>\
                <td>' + tablerender.name + '</td>\
                <td>' + tablerender.phone + '</td>\
                <td>' + tablerender.accountOff + '</td>\
                <td>' + tablerender.branch + '</td>\
                <td>' + tablerender.phone + '</td>\
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
                <td>' + tablerender.loanstatus + '</td>\
                </tr>\
                ');
            });
        }
    })
}

function FliterDisburseloan() {
    disableBtn('click-button');
    startLoadingButton('click-button');
    $from = $('#duefrom').val();
    $to = $('#dueto').val();
    $officer = $('#officer').val();
    $branch = $('#branch').val();
    $searchType = $('#searchType').val()
    $.ajax({
        url: '/company/loan/disburse-loan-data',
        type: 'post',
        data: { from: $from, to: $to, branch: $branch, officer: $officer, searchType: $searchType },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            stopLoadingButton('click-button', 'Filter');
            enableBtn('click-button');
            $report = data.message;
            let totalDisbursedAmount = 0
            $('#dueLoanRecTable').empty();
            $report.forEach(function(tablerender) {
                totalDisbursedAmount += tablerender.principal

                $('#dueLoanRecTable').append('\
                <tr>\
                <th scope="row">' + tablerender.sn + '</th>\
                <td>' + tablerender.name + '</td>\
                <td>' + tablerender.phone + '</td>\
                <td>' + tablerender.accountOff + '</td>\
                <td>' + tablerender.ref + '</td>\
                <td>' + tablerender.loanFee + '%</td>\
                <td>' + tablerender.loanInterest + '%</td>\
                <td>' + tablerender.principal + '</td>\
                <td>' + tablerender.graceDate + '</td>\
                <td>' + tablerender.repaymentAmt + '</td>\
                <td>' + tablerender.branch + '</td>\
                <td>' + tablerender.officer + '</td>\
                <td>' + tablerender.accountBalance + '</td>\
                <td>' + tablerender.loanstatus + '</td>\
                <td>' + tablerender.created_by + '</td>\
                <td>' + tablerender.approved + '</td>\
                </tr>\
                ');
            });
            $('#dueLoanRecTable').append('\
            <tr>\
            <th scope="row">#</th>\
            <td></td>\
            <td>Total</td>\
            <td></td>\
            <td></td>\
            <td></td>\
            <td></td>\
            <td class="bg-success text-light"> ' + totalDisbursedAmount + ' </td>\
            <td></td>\
            <td></td>\
            <td></td>\
            <td></td>\
            <td></td>\
            <td></td>\
            <td></td>\
            <td></td>\
            </tr>\
            ');
        }
    })
}


function FliterArrear() {
    disableBtn('click-button');
    startLoadingButton('click-button');
    $from = $('#from').val();
    $to = $('#to').val();
    $officer = $('#officer').val();
    $branch = $('#branch').val();
    $loan_product_id = $('#loan_product_id').val();
    $searchType = $('#searchType').val();
    $startfrom = $('#startfrom').val();
    $.ajax({
        url: '/company/loan/loan-arrears-report',
        type: 'post',
        data: { from: $from, to: $to, branch: $branch, officer: $officer, loan_product_id: $loan_product_id, searchType: $searchType, startfrom: $startfrom },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            stopLoadingButton('click-button', 'Filter');
            enableBtn('click-button');
            $report = data.message;
            $('#LoanRecTable').empty();
            if (data.status == 1) {
                $report.forEach(function(tablerender) {
                    console.log(tablerender);

                    $('#LoanRecTable').append('\
                <tr>\
                <th scope="row">' + tablerender.sn + '</th>\
                <td>' + tablerender.start_date + '</td>\
                <td>' + tablerender.loan_officer + '</td>\
                <td>' + tablerender.borrower + '</td>\
                <td>' + tablerender.phone + '</td>\
                <td>' + tablerender.loanref + '</td>\
                <td>' + tablerender.days_in_arrears + '</td>\
                <td>' + tablerender.overdue_P + '</td>\
                <td>' + tablerender.overdue_I + '</td>\
                <td>' + tablerender.overdue_Fee + '</td>\
                <td>' + tablerender.overdue_Penalty + '</td>\
                <td>' + tablerender.extended + '</td>\
                <td>' + tablerender.overdue_Balance + '</td>\
                <td>' + tablerender.totalPoutstanding + '</td>\
                <td>' + tablerender.status + '</td>\
                </tr>\
                ');
                });
            } else {
                $('#LoanRecTable').html(data.message);
            }
        }
    })
}

function swapSearchType() {
    $searchType = $('#searchType').val();
    if ($searchType == 'day') {
        $('#from').removeAttr('type');
        $('#to').removeAttr('type');
        $('#from').attr('type', 'number');
        $('#to').attr('type', 'number');
        $('.startfrom').show();
        $('#startfrom').show();
    } else {
        $('.startfrom').hide();
        $('#startfrom').hide();
        $('#from').removeAttr('type');
        $('#to').removeAttr('type');
        $('#from').attr('type', 'date');
        $('#to').attr('type', 'date');
    }
}

function FliterOfficerReport() {
    disableBtn('click-button');
    startLoadingButton('click-button');
    $from = $('#from').val();
    $to = $('#to').val();
    $officer = $('#officer').val();
    $branch = $('#branch').val();
    $.ajax({
        url: '/company/loan/loan-officer-performance',
        type: 'get',
        data: { from: $from, to: $to, branch: $branch, officer: $officer },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            stopLoadingButton('click-button', 'Filter');
            enableBtn('click-button');
            $report = data.message;
            $('#LoanRecTable').empty();
            if (data.status == 1) {
                $report.forEach(function(tablerender) {
                    // console.log(tablerender);
                    $('#LoanRecTable').append('\
                <tr>\
                <th scope="row">' + tablerender.sn + '</th>\
                <td>' + tablerender.branch + '</td>\
                <td>' + tablerender.loan_officer + '</td>\
                <td><a href="#" onclick=showModal("client","' + tablerender.uuid + '")>' + tablerender.no_client + '</a></td>\
                <td><a href="#" onclick=showModal("loan-client","' + tablerender.uuid + '")>' + tablerender.no_client_loan + '</a></td>\
                <td><a href="#" onclick=showModal("loans","' + tablerender.uuid + '")>' + tablerender.totalLoans + '</a></td>\
                <td>' + tablerender.principal + '</td>\
                <td>' + tablerender.interest + '</td>\
                <td>' + tablerender.outstanding + '</td>\
                <td>' + tablerender.totalDisbursed + '</td>\
                <td>' + tablerender.totalDisbursedAMT + '</td>\
                <td>' + tablerender.writingOffLoan + '</td>\
                <td>' + tablerender.writingOffLoanAmt + '</td>\
                <td>' + tablerender.totalP + '</td>\
                <td>' + tablerender.totalI + '</td>\
                <td>' + tablerender.totalSaving + '</td>\
                </tr>\
                ');
                });
            } else {
                $('#LoanRecTable').html(data.message);
            }
        }
    })
}

function showModal($url, $officer) {
    $('#exampleModalOfficerPerformance').modal('show');

    $('#client-table').empty();
    $('#loans').empty();
    $('#loan-client').empty();

    $('.loans').hide();
    $('.loan-client').hide();
    $('.client').hide();

    if ($url == 'client') {
        $('.client').show();
        $('#client-table').html('Loading');

    }
    if ($url == 'loan-client') {
        $('#loan-client').html('Loading');
        $('.loan-client').show();

    }
    if ($url == 'loans') {
        $('#loans').html('Loading');
        $('.loans').show();
    }

    $.ajax({
        url: ($url == 'client') ? '/company/report/officer-saving-client/' + $officer : '/company/report/loan-officer-client-loan/' + $officer,
        type: 'get',
        data: { clientURL: $url },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('#client-table').html('');
            $('#loans').html('');
            $('#loan-client').html('');
            if ($url == 'client') {
                var sn = 1;
                data.forEach(function(tablerender) {
                    var vaccount = '';
                    var balance = 0;
                    tablerender.wb_account_details.forEach(function(tablerenderRes) {
                        balance += tablerenderRes.user_wallet.balance;
                        vaccount += '<span class="badge badge-primary">' + tablerenderRes.virtual_accnum + ' - ' + tablerenderRes.user_wallet.balance + '</span>'
                    });
                    $('#client-table').append('\
                    <tr>\
                    <th>' + sn++ + '</th>\
                    <th scope="row">' + tablerender.first_name + ' ' + tablerender.last_name + '</th>\
                    <td>' + vaccount + '</td>\
                    <td>' + balance + '</td></tr>\
                    ')
                });
            }
            if ($url == 'loan-client') {
                console.log(data)

                var sn = 1;
                data.forEach(function(tablerender) {

                    $('#loan-client').append('\
                    <tr>\
                    <th>' + sn++ + '</th>\
                    <th scope="row">' + tablerender.user.first_name + ' ' + tablerender.user.last_name + '</th>\
                    <td>' + tablerender.user.virtual_account + '</td>\
                    <td>' + tablerender.user.phone + '</td>\
                    <td>' + tablerender.principal + '</td>\
                    <td>' + tablerender.principalLeft + '</td>\
                    <td>' + tablerender.InterestLeft + '</td>\
                    <td>' + tablerender.loanBalance + '</td></tr>\
                    ')
                });

            }
            if ($url == 'loans') {
                var sn = 1;
                var p = 0;
                var pl = 0;
                var lb = 0;
                var il = 0;
                data.forEach(function(tablerender) {
                    p += tablerender.principal
                    pl += tablerender.principalLeft
                    il += tablerender.InterestLeft
                    lb += tablerender.loanBalance
                    $('#loans').append('\
                    <tr>\
                    <th>' + sn++ + '</th>\
                    <th scope="row">' + tablerender.user.first_name + ' ' + tablerender.user.last_name + '</th>\
                    <td>' + tablerender.user.virtual_account + '</td>\
                    <td>' + tablerender.user.phone + '</td>\
                    <td>' + tablerender.principal + '</td>\
                    <td>' + tablerender.principalLeft + '</td>\
                    <td>' + tablerender.InterestLeft + '</td>\
                    <td>' + tablerender.loanBalance + '</td></tr>\
                    ')
                });
                $('#loans').append('\
                    <tr>\
                    <th>' + sn++ + '</th>\
                    <th scope="row">-</th>\
                    <td>-</td>\
                    <td>-</td>\
                    <td>' + p + '</td>\
                    <td>' + pl + '</td>\
                    <td>' + il + '</td>\
                    <td>' + lb + '</td></tr>\
                    ')
            }

        }
    })
}