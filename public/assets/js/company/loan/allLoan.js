$('#writeoffSection').hide();

function GeneraLoanRepayment(uuid){
    $.ajax({
        url: '/company/loan/json-pay-loan/'+uuid,
        type: 'get',
        // data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $loanRepayment = data.repaymentTable;
            $paymentHistory = data.repayment;
            $type = data.type;
            $h=$i =0;
            $('#principalLeft').html(data.Pleft);
            $('#interestLeft').html(data.Ileft);
            $('#Loan-Balance').html(data.IPleft);

            $('.PaymentHistory').empty();
            $('#loanRepayment').empty();
            $('#paidloanTable').empty();
            $('#amountPaid').html('<b>Total Paid: '+data.totalPaid+'<b>');

            var paid = 0;
            $paymentHistory.forEach(function(repayHistory) {
                $method= '';
                if(repayHistory.method==1){
                    $method ='Cash';
                }else if(repayHistory.method==2){
                    $method ='Wallet';
                }
                $h++;
                $('.PaymentHistory').append('\
                <tr class="table-secondary">\
                    <th scope="col">'+$h+'</th>\
                    <th scope="col-5">'+repayHistory.paymentDate+'</th>\
                    <th scope="col">'+repayHistory.first_name+' '+repayHistory.last_name+'</th>\
                    <th scope="col">'+$method+'</th>\
                    <th scope="col-5">'+repayHistory.amount+'</th>\
                    <th scope="col"><a  href="/company/loan/delete-payment/'+repayHistory.payment_ref+'"><i class="fa fa-trash btn btn-danger"></i></a></th>\
                    <th scope="col"><a style="margin:5px; padding:5px; font-size:20px;" href="/company/loan/loan-invoice-print/'+repayHistory.payment_ref+'"><i class="fa fa-print"></i></a><a style="margin:5px;  padding:5px; font-size:20px;" href="/company/loan/loan-invoice-pdf/'+repayHistory.payment_ref+'"><i class="fa fa-file-pdf-o"></i></a></th>\
                </tr>\
            ');
            $('#paidloanTable').append('\
                <tr class="table-secondary">\
                    <th scope="col">'+$h+'</th>\
                    <th scope="col-5">'+repayHistory.paymentDate+'</th>\
                    <th scope="col-5">'+repayHistory.amount+'</th>\
                    <th scope="col">'+$method+'</th>\
                </tr>\
            ');
                $('#loanRepayment').append('\
                <tr class="table-secondary">\
                    <th scope="col">#</th>\
                    <th scope="col-5">'+repayHistory.paymentDate+'</th>\
                    <th scope="col">'+$method+'</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col-5">'+repayHistory.amount+'</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                    <th scope="col">-</th>\
                </tr>\
            ');
            });
            $loanRepayment.forEach(function(repay) {
                $i++;
                if ($type == 1) {

                    $('#loanRepayment').append('\
                    <tr '+repay.tag+'>\
                    <th scope="row">' +repay.sn+ '</th>\
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
                    <th scope="row">' +repay.sn+ '</th>\
                    <td>' + repay.repaymentDate + '</td>\
                    <td>' + repay.description + '</td>\
                    <td>' + repay.principal_reduction + '</td>\
                    <td>' + repay.I + '</td>\
                    <td>' + repay.fee + '</td>\
                    <td>' + repay.payment_amount + '</td>\
                    <td>'+repay.IX+'</td>\
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
        });
}
function DeleteLoan(uuid){
    let text = "Are you sure you want to delete this loan? All transactions associated to this loan will all be deleted";
  if (confirm(text) == true) {
    ajax_operation('delete', '/company/loan/delete-loan/'+uuid, {});
    $('#html5-extension').DataTable().ajax.reload();
  } else {

  }

}
function searchLoan(){
    $from = $('#from').val();
    $to = $('#to').val();
    $status = $('#status').val();
    $paginate = $('#view-select').val();
    $loan_products = $('#loan_products').val();
    $officer = $('#officer').val();
    $branch = $('#branch').val();
    $('#html5-extension').DataTable().ajax.url('/company/loan/all-data?all=1' +'&from='+$from+'&to='+$to+'&paginate='+$paginate+'&status='+$status+'&officer='+$officer+'&branch='+$branch+'&loan_products='+$loan_products).load();
}
