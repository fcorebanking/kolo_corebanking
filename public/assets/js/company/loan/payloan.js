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

function swap1() {
    $('.transferpayment').hide();
    $('.tablepaymenthistory').show();
}

function swap2() {
    $('.transferpayment').show();
    $('.tablepaymenthistory').hide();
}
swap1();

function changewalletCash(val) {
    if (val == 'wallet') {
        $('.accountType').show();
    } else {
        $('.accountType').hide();
        $('#instrumentCode').val('')

    }
}

function payloan() {
    var formData = $('#pay-loan').serialize();
    ajax_operation('post', '/company/loan/pay-loan', formData, 'loanPayBTN', 'Pay <i class="fa fa-arrow-right"></i>');
    $loanID = $('#loanID').val();
    GeneraLoanRepayment($loanID);

}

function GeneraLoanRepayment(uuid) {
    const fromDate = $('#fromDate').val()
    const toDate = $('#toDate').val()
    if (fromDate == '' && toDate == '') {
        viewLoan(uuid);
    }

    $.ajax({
        url: '/company/loan/json-pay-loan/' + uuid + '?fromDate=' + fromDate + '&toDate=' + toDate,
        type: 'get',
        // data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $loanRepayment = data.repaymentTable;
            $paymentHistory = data.repayment;
            $type = data.type;
            $h = $i = 0;

            if (fromDate == '' && toDate == '') {

                $('#principalLeft').html(data.Pleft);
                $('#interestLeft').html(data.Ileft);
                $('#Loan-Balance').html(data.IPleft);
                $('#amountPaid').html('<b>Total Paid: ' + data.totalPaid + '<b>');

            }
            $('.PaymentHistory').empty();
            $('#loanRepayment').empty();
            $('#paidloanTable').empty();
            var paid = 0;
            $paymentHistory.forEach(function(repayHistory) {
                $method = '';
                if (repayHistory.method == 1) {
                    $method = 'Cash';
                } else if (repayHistory.method == 2) {
                    $method = 'Wallet';
                }
                $h++;
                //  paid += repayHistory.amount;
                $('.PaymentHistory').append('\
                <tr class="table-secondary">\
                    <th scope="col">' + $h + '</th>\
                    <th scope="col-5">' + repayHistory.paymentDate + '</th>\
                    <th scope="col">' + repayHistory.first_name + ' ' + repayHistory.last_name + '</th>\
                    <th scope="col">' + $method + '</th>\
                    <th scope="col-5">' + repayHistory.amount + '</th>\
                    <th scope="col-5">' + (repayHistory.balance + repayHistory.amount) + '</th>\
                    <th scope="col-5">' + repayHistory.balance + '</th>\
                    <th scope="col"><a  href="/company/loan/delete-payment/' + repayHistory.payment_ref + '"><i class="fa fa-trash btn btn-danger"></i></a></th>\
                    <th scope="col"><a style="margin:5px; padding:5px; font-size:20px;" href="/company/loan/loan-invoice-print/' + repayHistory.payment_ref + '"><i class="fa fa-print"></i></a><a style="margin:5px;  padding:5px; font-size:20px;" href="/company/loan/loan-invoice-pdf/' + repayHistory.payment_ref + '"><i class="fa fa-file-pdf-o"></i></a></th>\
                </tr>\
            ');
                $('#paidloanTable').append('\
                <tr class="table-secondary">\
                    <th scope="col">' + $h + '</th>\
                    <th scope="col-5">' + repayHistory.paymentDate + '</th>\
                    <th scope="col-5">' + repayHistory.amount + '</th>\
                    <th scope="col">' + $method + '</th>\
                </tr>\
            ');

                $('#loanRepayment').append('\
                <tr class="table-secondary">\
                    <th scope="col">#</th>\
                    <th scope="col-5">' + repayHistory.paymentDate + '</th>\
                    <th scope="col">' + $method + '</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col-5">' + repayHistory.amount + '</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                </tr>\
            ');
            });
            if (fromDate == '' && toDate == '') {
                $loanRepayment.forEach(function(repay) {
                    $i++;
                    if ($type == 1) {

                        $('#loanRepayment').append('\
                    <tr ' + repay.tag + '>\
                    <th scope="row">' + repay.sn + '</th>\
                    <td>' + repay.repaymentDate + '</td>\
                    <td>' + repay.description + '</td>\
                    <td>' + repay.principal_reduction + '</td>\
                    <td>' + repay.I + '</td>\
                    <td>' + repay.fee + '</td>\
                    <td>' + repay.payment_amount + '</td>\
                    <td>' + repay.principalRepayment + '</td>\
                    <td>--</td>\
                    <td>' + repay.loanBalance + '</td>\
                    <td>' + repay.loanBalanceRecurring + '</td>\
                    <td>' + repay.loan_balance + '</td>\
                    </tr>\
                    ');
                        $('.duecheck').html('Loan-Due Progress');
                        $('.interestcheck').html('Repayment');

                    } else if ($type == 2) {

                        $('#loanRepayment').append('\
                    <tr>\
                    <th scope="row">' + repay.sn + '</th>\
                    <td>' + repay.repaymentDate + '</td>\
                    <td>' + repay.description + '</td>\
                    <td>' + repay.principal_reduction + '</td>\
                    <td>' + repay.I + '</td>\
                    <td>' + repay.fee + '</td>\
                    <td>' + repay.payment_amount + '</td>\
                    <td>' + repay.IX + '</td>\
                    <td>--</td>\
                    <td>' + repay.loanBalance + '</td>\
                    <td>' + repay.loanBalanceRecurring + '</td>\
                    <td>' + repay.loan_balance + '</td>\
                    </tr>\
                    ');
                        $('.preduction').html('Principal Repayment');
                        $('.PIReducingBal').html('Total Repayment(PI)');
                        $('.IXTotal').html('Interest Increament');
                    }
                });
            }
        }
    });
}

function filterLoanDate() {

    disableBtn('click-button');
    startLoadingButton('click-button');
    $from = $('#duefrom').val();
    $to = $('#dueto').val();
    $officer = $('#officer').val();
    $branch = $('#branch').val();
    // from:$from,to:$to,branch:$branch,officer:$officer
    $('#html5-extension').DataTable().ajax.url("/company/loan/due-loan?from=" + $from + "&to=" + $to + "&branch=" + $branch + "&officer=" + $officer).load();
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

function viewLoan(uuid) {
    $('#loanID').val(uuid);
    $.ajax({
        url: '/company/loan/view-Loan-Details-data/' + uuid,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('#remark').html(data.remark);
            $('.ref').html('Loan Ref: ' + data.ref);
            $('#borrower').html('Borrower: ' + data.user_id);
            $('#principal').html('Principal: ' + data.principal);
            $('#loan_balance').html('Loan Balance: ' + data.loanBalance);
            $('#borrowerAccount').html('Account Number: ' + data.borrowerAccount);
            $('#Loantype').html((data.Loantype == 1) ? 'Loan Type: ' + data.Loantype : 'Loan Type: ' + data.Loantype);
            $('#LoanBranch_id').html('Branch: ' + data.LoanBranch_id);
            $('#LoanOfficer_id').html('Officer: ' + data.LoanOfficer_id);
            $('#remark').html(data.remark);
            $('#duration').html('Duration: ' + data.duration + '/' + data.DurationPeriod);
            $('#loan_product_id').html('Product: ' + data.loan_product_id);
            $('#rate').html('Rate: ' + data.rate + '%');
            $('.sessions').html('<li>Author: ' + data.created_by + '</li><li> Editor: ' + data.modified_by + '</li>');
            $('#repaymentcycle').html(data.period_id);
            $('#start_at').html('Start Date: ' + data.start_at);
            $('#grace_at').html('Grace Date: ' + data.grace_at);
            $('#LoanFeeMethod').html(data.LoanFeeMethod + '/' + data.LoanFee + '%');
            $('#mrgtFeeMethod').html(data.mrgtFeeMethod + '/' + data.mrgtFee + '%');
            $('#auto_pay').html(data.auto_pay);
            $('#on_guarantor').html(data.on_guarantor);
            $('#keep_as_past_onMissed_onExtend').html(data.keep_as_past_onMissed_onExtend);
            $('#ExtendDuration').html(data.ExtendDuration);
            $('#missedFee').html(data.missedFee + '% Missed Fee');
            $('.sessions').html("<strong><p>Created By:" + data.created_by + "</p><p>Approved By: " + data.modified_by + "</p></strong>");
            $('.guarantorCard').empty();
            $guarantor = data.pickguarantor;
            $guarantor.forEach(function(guarantor) {
                console.log(guarantor);
                $avatar = guarantor.passport;
                $signature = guarantor.signature;
                $avatar = ($avatar) ? $avatar.replace('public', 'storage') : null;
                $signature = ($signature) ? $signature.replace('public', 'storage') : null;
                $avtarURL = ($avatar) ? window.location.origin + '/' + $avatar : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                $signatureURL = ($avatar) ? window.location.origin + '/' + $signature : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                $('.guarantorCard').append('\
                <div class="col-md-4 col-sm-6">\
                <div class="card" style="width: 10rem;">\
                <img class="card-img-top" src="' + $avtarURL + '" alt="' + guarantor.first_name + '">\
                <div class="card-body"><h5 class="card-title">' + guarantor.owner + '</h5>\
                <p class="card-text">' + guarantor.first_name + ' ' + guarantor.last_name + '</p>\
                <p class="card-text">Salary: ' + guarantor.salary + '</p>\
                <p class="card-text">Relationship: ' + guarantor.relationship + '</p>\
                <p class="card-text">Phone: ' + guarantor.phone + '</p>\
                <p class="card-text">occupation: ' + guarantor.occupation + '</p>\
                <p class="card-text">BVN: ' + guarantor.bvn + '</p>\
                <p class="card-text">Remark: ' + guarantor.other_remarks + '</p>\
                <p class="card-text">Address: ' + guarantor.address + '</p>\
                <a target="_blank" href="' + $signature + '" class="btn btn-warning"> Signature</a>\
                </ul></p>\
                </div></div></div>');
            });
            $('#viewdetails').show();
            $('#rollOver').hide();
        }
    });
}

function enterMessage() {
    $('#addMessageModal').modal()
}

function sendMessage() {
    $("#commentForm").submit()
}
$("#commentForm").on('submit', function() {
    disableBtn('submitmsg')
    startLoadingButton('submitmsg')
    let loanID = $('#loanID').val()
    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/company/loan/comments/' + loanID,
        data: new FormData(this),
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: (data) => {
            stopLoadingButton('submitmsg', 'Sending <i class="fa fa-arrow-right"></i>');
            enableBtn('submitmsg');
            getComments();
        },
        error: function(jqXHR, status, error) {

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
        }
    });
    return false;
});

function getComments() {
    let loanID = $('#loanID').val()
    if (loanID) {
        $.ajax({
            type: 'get',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/company/loan/comments/' + loanID,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: (data) => {
                $('.message-box-scroll').html('');
                data.map((record) => {
                    attachment = record.attachment;
                    let attach = '';
                    if (attachment.length > 0) {
                        record.attachment.map((attachFile) => {
                            let publicConvert = (attachFile.attachment).replace('public', 'storage')
                            attach += '  <div class="attachments">\
                                    <span class=""><a href="/' + publicConvert + '">Attachment</a></span>\
                                </div>';
                        });
                    }
                    $('.message-box-scroll').append('<div class="mail-item draft">\
                    <div class="animated animatedFadeInUp fadeInUp" id="mailHeadingOne">\
                        <div class="mb-0">\
                            <div class="mail-item-heading personal collapsed"  data-toggle="collapse" role="navigation" data-target="#mailCollapseOne" aria-expanded="false">\
                                <div class="mail-item-inner">\
                                    <div class="d-flex">\
                                        <div class="n-chk text-center">\
                                            <label class="new-control new-checkbox checkbox-primary">\
                                                <span onclick="deleteComment(' + record.id + ')"><i class="fa fa-trash"></i></span>\
                                            </label>\
                                        </div>\
                                        <div class="f-body" data-mailfrom="{{auth()->user()->email}}" data-mailto="' + record.user.email + '" data-mailcc="">\
                                            <div class="meta-mail-time">\
                                                <p class="user-email" data-mailTo="' + record.user.email + '">' + record.user.first_name + ' ' + record.user.last_name + '</p>\
                                            </div>\
                                            <div class="meta-title-tag">\
                                                <p class="mail-content-excerpt" data-maildescription="" onclick="viewMessageModal(' + record.id + ')"><span class="mail-title" data-mailTitle="Web Design News"> Message: ' + record.message + '</span> .</p>\
                                                <div class="tags">\
                                                    <span class="g-dot-primary"></span>\
                                                    <span class="g-dot-warning"></span>\
                                                    <span class="g-dot-success"></span>\
                                                    <span class="g-dot-danger"></span>\
                                                </div>\
                                                <p class="meta-time align-self-center">' + record.created_at + '</p>\
                                            </div>\
                                        </div>\
                                    </div>\
                                </div>' + attach + '\
                            </div>\
                        </div>\
                    </div>\
                </div>');
                })
            },
            error: function(jqXHR, status, error) {
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
            }
        });
    }
}
getComments();

function deleteComment(id) {
    var confirmText = "Are you sure you want to delete this Comment?";
    if (confirm(confirmText)) {
        ajax_operation('delete', '/company/loan/comments/' + id, {});
        getComments();
    }
}

function viewMessageModal(id) {
    $('#viewMessageModal').modal()

    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/company/loan/comments-edit/' + id,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: (data) => {
            $('.load-content').html(data.message);
            $('.remark_author').html('By: ' + data.user.first_name + ' ' + data.user.last_name)
            attachment = data.attachment;
            let attach = '';
            if (attachment.length > 0) {
                data.attachment.map((attachFile) => {
                    let publicConvert = (attachFile.attachment).replace('public', 'storage')
                    attach += '  <div class="attachments">\
                                <span class=""><a href="/' + publicConvert + '">Attachment</a></span>\
                            </div>';
                });
            }
            $('.load-content').append(attach);
        }
    });
}
//viewMessageModal
$(document).ready(function() {
    // Add a click event listener to the print button
    $("#print-button").on("click", function() {
        // Clone the target element and create a new window
        var $printContent = $(".print-target").clone();
        var $printWindow = window.open('', '_blank');
        $printWindow.document.write('<html><head><title>Print Preview</title></head><body>');
        $printWindow.document.write($printContent.html());
        $printWindow.document.write('</body></html>');
        $printWindow.document.close();
        // Initiate the print process
        $printWindow.focus();
        $printWindow.print();
        //   $printWindow.close();
    });
});

function ExtendLoanAMT() {
    $extend_amount = $('#extend_amount').val()
    $extendby = $('#extendby').val()
    $loanID = $('#loanID').val()
    ajax_operation('post', '/company/loan/extend-loan', { amount: $extend_amount, extendby: $extendby, loanid: $loanID }, 'extendLoanPayBTN', 'send');
}