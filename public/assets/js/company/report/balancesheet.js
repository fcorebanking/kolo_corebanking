let currency ='';
function balancesheet(){
    $.ajax({
        url: '/company/accounting/balance-sheet',
        type: 'get',
        data: {from:$('#from').val(),to:$('#to').val()},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            if (data.status == 1) {
                currency = data.currency;
                $('#loanpaid').html(data.currency+formatToCurrency(data.loanpaid));
                $('#PrincipalLeft').html(data.currency+formatToCurrency(data.PrincipalLeft));
                $('#luquidatebalance').html(data.currency+formatToCurrency(data.luquidatebalance));
                $('#luquidate').html(data.currency+formatToCurrency(data.liquidateprincipal));
                $('#principal').html(data.currency+formatToCurrency(data.activePrincipal));
                $('#offloan').html(data.currency+formatToCurrency(data.writtenoffprincipal));
                $('#totaloutstanding').html(data.currency+formatToCurrency(data.active));
                $('#pastloan').html(data.currency+formatToCurrency(data.missedloan));
                $('#currentloan').html(data.currency+formatToCurrency(data.active));
                $('#interest').html(data.currency+formatToCurrency(data.InterestLeft));
                $('#completeloan').html(data.currency+formatToCurrency(data.completedloan));
                $('#penalty').html(data.currency+formatToCurrency(data.penaltyPaid));
                // $('#penalty').html(data.currency+formatToCurrency(data.penaltyPaid));
                $transaction_vault = data.vault;
                $('#vault').empty();
                let totalcharge = 0;
                totalcharge =data.penaltyPaid;
                let savings = data.user;
                $totalsavings =0;
                $('#savings').val(0);
                $('#expensesval').val(0);
                $('#esusuval').val(data.esusu.wallet);
                $('#esusuwithdrawable_val').val(data.esusu.withdrawable);

                $('#esusu_overdraft').val(data.esusu.overdraft);
                $('#esusu_overdraft_view').html(data.currency+formatToCurrency(data.esusu.overdraft));

                $('#esusu_accum_charge').val(data.esusu.acccharge);
                $('#esusu_accum_charge_view').html(data.currency+formatToCurrency(data.esusu.acccharge));

                $('#investmentval').val(data.investment);
                $('#other_income_value').val(0);
                $('#totalchargehide').val(0);
                $('#totalcharge').html('0');
                $('#expensesval').val(data.expenses);
                $('#other_income').html(data.currency+formatToCurrency(data.otherIncome));
                $('#other_income_value').val(data.otherIncome);
                $('#savings_product').empty();
                savings.forEach(function (saving) {
                    $('#savings_product').append('\
                    <tr>\
                        <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+saving.savings_product+'</b></td>\
                        <td style="text-align:right"><div>'+data.currency+formatToCurrency(saving.balance)+'</div></td>\
                        <td ></td>\
                    </tr>\
                    ');
                    $totalsavings += saving.balance;
                    $('#savings').val($totalsavings);
                });
                $('#vault').empty();
                $transaction_vault.forEach(function (vault) {
                    let nameValut = vault.vault;
                    nameValut = nameValut.replace(/_/gi,' ');
                    $('#vault').append('\
                    <tr>\
                        <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+nameValut+'</b></td>\
                        <td style="text-align:right"><div>'+data.currency+formatToCurrency(vault.value)+'</div></td>\
                        <td ></td>\
                    </tr>\
                    ');

                    totalcharge += vault.value;
                    $('#totalcharge').html(data.currency+formatToCurrency(totalcharge));
                    $('#totalchargehide').val(totalcharge);
                });
                $('#loannet').html(data.currency+formatToCurrency(Number(data.loanpaid)+Number(data.active)));
                $('#currentasset1').html(data.currency+formatToCurrency(Number(data.esusu.acccharge)+Number(data.esusu.overdraft)+Number(data.loanpaid)+Number(data.active) + totalcharge + Number(data.otherIncome)));
                update_balance_sheet();
            } else {
                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }

        },
        error: function (jqXHR, status, error) {

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
        },
        xhr: function () {
            var xhr = $.ajaxSettings.xhr();
            xhr.onprogress = function e() {
                // For downloads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                    $(".toast-msg").html(e.loaded / e.total);
                }
            };
            xhr.upload.onprogress = function (e) {
                // For uploads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                }
            };
            return xhr;
        }
    });
}
balancesheet();
function update_balance_sheet()
{
       $('.load').html('<div class="loader"></div>');
    var total;

 total =  $('#totalchargehidebk').val();

    var liability =Number($('#esusuwithdrawable_val').val())+ Number($('#esusuval').val())+ Number($('#savings').val()) + Number($('#expensesval').val()) + Number($('#wages_payable').val()) + Number($('#income_taxes_payable').val());

    $('#TotalLiabilities').html(currency+formatToCurrency(liability));
     var equity = Number($('#retained_net_surplus').val()) + Number($('#fund_capital').val()) + Number($('#net_surplus').val());
    $('#TotalEquity').html(currency+formatToCurrency(equity));
    $('#TotalLiabilitiesEquity').html(currency+formatToCurrency(equity + liability));
    //     $('.load').html('');
}
