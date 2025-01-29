
var _getValidationField = document.getElementsByClassName('validation-text');
isSuccessmessage = false;
let timer;
let counter = 0;
let newValue;
let newMobileMoneyValue;
let selectedPhonePrefix = '';

let statusCode;
let statusMessage;


(function(){
    $.ajaxSettings.beforeSend=function(xhr){
        xhr.setRequestHeader('X-Requested-With', {toString: function(){ return ''; }});
    };
})(jQuery);
$('#customerMain').select2({
    placeholder: 'Select a Customer',
    ajax: {
        url: '/userDataSelect',
        dataType: 'json',
        delay: 250,
        data: function (data) {
            console.log(data);
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

$(document).ready(function ($) {

    $(".basic").select2({
        tags: true,
    });

    $('#transactionPin').inputmask({
        mask: "9999"
    });

    $('#transactionPin').on('focus keyup', function (event) {
        event.preventDefault();
        if ($('#transactionPin').val().length == 3) {
            console.log("lskjdflskjd");
        }
        if ($(this).inputmask("isComplete")) {
            $('#completeTransactionButton').prop("disabled", false);
        }
    });
    $('#transactionPin').on('keyup', function (event) {
        event.preventDefault();
        if (!$(this).inputmask("isComplete")) {
            $('#completeTransactionButton').prop("disabled", true);
        }
    });
});

function getRepo() {
    var customer = document.getElementById("selectCustomer");

    console.log(customer);
}

function calc() {
    if (document.getElementById('tagCustomerCheck').checked) {
        console.log("Customer Taged");

        $('#tagCustomerDiv').fadeIn(300)
    } else {
        console.log("No customer is tagged");
        $('#tagCustomerDiv').fadeOut(300)


    }
}


function companyWalletsTransfer() {

    var transactionTypeId = $('#transactionTypeId').val();
    var amount = $('#modalfinalAmount').text();
    var transactionId = $('#transactionId').val();
    var destinationAccount = $('#receipientAccount').text();
    var transactionPin = $('#transactionPin').val();
    var withcharge = $('#chargeAMT').val();

    let url;


    setModalButtonLoading(true)

    if (transactionTypeId == 5) {

        var description = $('#mydescription').text();
        var exchangeAmount = $('#exchangeAmount').text();

        url = "company-wallet-transfer"

        console.log("new Trans id is: " + transactionId);

        data_info = {
            amount: amount,
            transactionId: transactionId,
            destinationAccount: destinationAccount,
            description: description,
            exchangeAmount: exchangeAmount,
            transactionPin: transactionPin,
        };
    } else if (transactionTypeId == 8) {
        url = "transfer-to-bank-account"
        var receipientName = $('#receipientName').text();

        var destinationAccount = $('#receipientAccount').text();
        var transferBankCode = $('#transferBankCode').val();

        var countryCurrency = $('#companyCurrencyCode').val();
        var currentCompanyCurrentCode = $('#currentCompanyCurrentCode').val();

        destinationAccount = destinationAccount.startsWith("0") ? "" + destinationAccount : destinationAccount;

        var requestReference = $('#requestReference').val();
        var accountName = $('#transferAccountName').val();
        var sessionID = $('#sessionID').val();
        var mydescription = $('#transferDescription').val();

        // destinationAccount = destinationAccount.startsWith("+") ? "" + destinationAccount.substring(1) : destinationAccount.substring(1);

        var accountNumber = $('#transferAccountNumber').val();

        data_info = {
            amount: amount,
            transactionId: transactionId,
            destinationAccount: accountNumber,
            exchangeCurrency: countryCurrency,
            description: mydescription,
            bankCode: transferBankCode,
            transactionPin: transactionPin,
            receipientName: receipientName,
            requestReference: requestReference,
            sessionID: sessionID,
            accountName: accountName,
            chargeAmount:withcharge,
        };

    } else if (transactionTypeId == 19) {
        url = "mobile-money-transfer"
        var receipientName = $('#receipientName').text();

        var countryCurrency = $('#mobileMoneyCountry').val();

        var destinationAccount = $('#receipientAccount').text();
        var transferBankCode = $('#transferBankCode').val();

        destinationAccount = destinationAccount.startsWith("+") ? "" + destinationAccount.substring(1) : destinationAccount.substring(1);

        data_info = {
            amount: amount,
            transactionId: transactionId,
            destinationAccount: destinationAccount,
            exchangeCurrency: countryCurrency,
            description: description,
            bankCode: transferBankCode,
            transactionPin: transactionPin,
            receipientName: receipientName,
        };
    }

    console.log(data_info);

    $.ajax({
        url: url,
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('.toast').removeClass("bg-success");
            $('.toast').removeClass("bg-danger");

            console.log(data);

            setModalButtonLoading(false);
            window.scroll({
                top: 0,
                left: 0,
                behavior: 'smooth'
            });

            if (data.status == 1) {
                if (data.status == 1) {
                    $('#companyWalletBalance').text(data.data.balance);
                    $('#companyWalletBalanceAgain').text(data.data.balance);
                    data.status = 'bg-success'
                }

                $('#fundWalletSummary').modal('hide');

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em'
                })

                clearAllFields();
                console.log(data);
            } else if (data.status == 2) {

                $('#transactionPin').val('');

                _getValidationField[5].innerHTML = data.message;
                _getValidationField[5].style.display = 'block'; return false;

            } else {

                $('#fundWalletSummary').modal('hide');
                $('#transactionPin').val('');

                swal({
                    title: 'Error',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }
        },
        error: function (jqXHR, status, error) {

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

            setModalButtonLoading(false);
            $('#fundWalletSummary').modal('hide');
            swal({
                title: 'Error',
                text: msg,
                type: 'error',
                padding: '2em'
            })
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

function showBankTransferSummary() {

    var transferBankCode = $('#transferBankCode').val();
    var transferAccountNumber = $('#transferAccountNumber').val();
    var transferAccountName = $('#transferAccountName').val();
    var transferAmount = $('#transferAmount').val();
    var description = $('#transferDescription').val();
    var bankName = $('#bankName').val();
    var token = $('.token').val();
    var customer = $('#customerMain').val();
    var withdrawal_charge = $('#chargeAMT').val();
    $('.validation-text').html();

    if (transferBankCode == "") {
        _getValidationField[0].innerHTML = 'Bank is required';
        _getValidationField[0].style.display = 'block'; return false;
    } else {
        _getValidationField[0].style.display = 'none';
    }
    if (transferAccountNumber == "") {
        _getValidationField[1].innerHTML = 'Account Number is required';
        _getValidationField[1].style.display = 'block'; return false;
    } else {
        _getValidationField[1].style.display = 'none';
    }

    if (transferAccountName == "") {
        _getValidationField[2].innerHTML = 'Account name is required';
        _getValidationField[2].style.display = 'block'; return false;
    } else {
        _getValidationField[2].style.display = 'none';
    }

    if (transferAmount == "") {
        _getValidationField[3].innerHTML = 'Amount is required';
        _getValidationField[3].style.display = 'block'; return false;
    } else {
        _getValidationField[3].style.display = 'none';
    }


    description = description == "" ? "Wallet to Bank Transfer" : description;

    data_info = {
        transferBankCode: transferBankCode,
        transferAccountNumber: transferAccountNumber,
        transferAccountName: transferAccountName,
        finalAmount: transferAmount,
        description: description,
        transaction_type: 8,
        customer:customer,
        withdrawal_charge:withdrawal_charge
    };


    $('#initiateBankTransfer').prop("disabled", true);
    $('#initiateBankTransfer').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    console.log(data_info);

    $.ajax({
        url: 'initiate-transaction',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            $('#transactionPin').val('');

            $('#initiateBankTransfer').prop("disabled", false);
            $('#initiateBankTransfer').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

            if (data.status == 1) {

                var countryCurrency = $('#companyCurrencyCode').val();
                var currentCompanyCurrentCode = $('#currentCompanyCurrentCode').val();
                if (countryCurrency == currentCompanyCurrentCode) {
                    $('#chargeValue').text(data.data.charge);
                    $('#modalfinalAmount').text(data.data.total);
                }
                $('#transferType').text("Wallet to Bank Transfer");
                $('#receipientName').text(transferAccountName);
                $('#receipientAccount').text(transferAccountNumber);
                $('#receipientBankName').text("(" + bankName + ")");
                $('#amountValue').text(data.data.transferAmount);
                $('#transactionId').val(data.data.transactionId);
                $('#transactionTypeId').val(data.data.transactionTypeId);
                $('#mydescription').text(description);

                if(data.data.userAccess){
                    $('#fundWalletSummary').modal({ backdrop: 'static', keyboard: false, show: true });
                }else{
                    swal({
                        title: 'Success!',
                        text: 'Transaction in Pending',
                        type: 'success',
                        padding: '2em'
                    })
                }


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

            console.log(status);
            console.log(error);
            console.log(jqXHR.status);

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

            swal({
                title: 'Error!',
                text: data.message,
                type: 'error',
                padding: '2em'
            })
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
    }).done(function () {
    });

    // renderFundWallet();
}

function setModalButtonLoading(isLoading) {

    $('#modalCancelButton').prop("disabled", isLoading);
    $('#completeTransactionButton').prop("disabled", isLoading);
    $('#completeTransactionButton').html(isLoading ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...' :
        'Continue');
}

var supcat = $("#mobileMoneyCountry");
var cat = $("#mobileMoneyPaymentMethod");

// duplicate the code for the processor -> motherboard
$('#mobileMoneyCountry').on("change", function () {

    var _rel = $(this).val();
    console.log(_rel);
    $cat.find("option").attr("style", "");
    $cat.val("");
    if (!_rel) return $cat.prop("disabled", true);
    $cat.find("[rel~='" + _rel + "']").show();
    $cat.prop("disabled", false);
});


function clearAllFields() {
    $('#receipientName').text('');
    $('#receipientAccount').text('');
    $('#accountName').val('');
    $('#amount').val('');
    $('#description').val('');
    $('#accountNumber').val('');
    $('#amountValue').text('');
    $('#chargeValue').text('');
    $('#totalValue').text('');
    $('#transactionId').val('');
    $('#transactionTypeId').val('');
    $('#transactionPin').val('');
    $('#mydescription').text('');

    $('#baseAmount').html('');
    $('#baseCurrency').html('');
    $('#exchangeAmount').html('');
    $('#exchangeCurrency').html('');
    $('#finalAmount').val('');
    $('#exchangeAmountValue').val('');
    $('#amt_chg').val('');
    $('#exchangeCharger').html('');

    $('#finalAmountTxt').html('');
    $('#modalfinalAmount').html('');
    $('#exchangeDiv').fadeOut(200);

    // $('#transferBankCode').val('');
    $('#transferAccountNumber').val('');
    $('#transferAccountName').val('');
    $('#transferAmount').val('');
    $('#transferDescription').val('');
    $('#bankName').val('');

    $('#transferType').text("Company Wallet Transfer");
    $('#receipientBankName').text('');

    var _getValidationField = document.getElementsByClassName('validation-text');

    var i, n = _getValidationField.length;
    for (i = 0; i < n; ++i) {
        _getValidationField[i].style.display = "none";
    }
}

function renderFundWallet(hasMessage, messageType, message) {
    // $send_method = 'POST';
    // $pageUrl = 'nigerian-bank-transfer-page';
    // $data_info = { hasMessage: hasMessage, messageType: messageType, message: message }
    // ajax_fetch($send_method, $pageUrl, $data_info);
}

function showSuccessAlert(message) {

    console.log(message + "sldjflksjdflksj");

    if (message != '')
        renderFundWallet(true, 'success', message);
    else {

    }
}

function verifyBankAccount() {

    var transferBankCode = $('#transferBankCode').val();
    var transferAccountNumber = $('#transferAccountNumber').val();

    if (transferBankCode != null) _getValidationField[4].style.display = 'none';

    $('#transferAccountName').val("");

    console.log(transferBankCode + " " + transferAccountNumber);
    if (transferAccountNumber.length == 10 && transferBankCode != "") {
        console.log("Verifying account number " + transferAccountNumber);

        $("#myBankLoader").show();

        $('#transferAccountName').val("");
        _getValidationField[5].style.display = 'none';


        data_info = {
            "bankCode": transferBankCode,
            "accountNumber": transferAccountNumber,
        }

        $.ajax({
            url: 'verify-bank-account',
            type: 'post',
            data: data_info,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {

                console.log(data.status, "Response status");

                if (data.status == 1) {

                    var newAccountNumber = $('#transferAccountNumber').val();

                    if (newAccountNumber.length == 10) {
                        $('#transferAccountName').val(data.data.account_name);
                        $('#bankName').val(data.data.bank_name);
                        $('#requestReference').val(data.data.request_reference);
                        $('#sessionID').val(data.data.session_id);
                        _getValidationField[5].style.display = 'none';
                    } else {
                        $('#transferAccountName').val("");
                        $('#bankName').val("");
                        _getValidationField[5].innerHTML = 'Invalid Account Number';
                        _getValidationField[5].style.display = 'block';
                    }
                } else {
                    console.log(data);
                    _getValidationField[5].innerHTML = 'Invalid Account Number';
                    _getValidationField[5].style.display = 'block';
                }

                $("#myBankLoader").hide();
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
        }).done(function () {
            // $('.toast').addClass("bg-success");
        });

        // $('.toast').toast('show');
    } else {
        $('#accountName').val('');
        $("#myBankLoader").hide();
    }
}


const debouncina = function (fn, d) {
    if (timer) {
        clearTimeout(timer);
    }

    timer = setTimeout(fn, d);
}

const betterFunction = (e) => {
    newValue = e.target.value;
    $('#initiateBankTransfer').attr("disabled", "disabled");
    $('#initiateBankTransfer').css("opacity", ".5");
    debouncina(verifyAmount, 1000);
}

const verifyAmount = () => {

    var countryCurrency = $('#companyCurrencyCode').val();
    var currentCompanyCurrentCode = $('#currentCompanyCurrentCode').val();

    newValue = $('#transferAmount').val();

    if (newValue > 0) {
        console.log(countryCurrency + " " + currentCompanyCurrentCode);

        if (countryCurrency == currentCompanyCurrentCode) {

            var companyWalletBalance = $('.companyWalletBalancex').val();
            companyWalletBalance = companyWalletBalance.replace(/\,/g, '');
            companyWalletBalance = Number(companyWalletBalance);

            console.log(companyWalletBalance + " " + newValue);

            if (companyWalletBalance < newValue) {
                console.log("Insufficient Balance");
                _getValidationField[3].innerHTML = 'Insufficient Balance';
                _getValidationField[3].style.display = 'block'; return false;
            } else {
                _getValidationField[3].style.display = 'none';
            }
             $('#initiateBankTransfer').css("opacity", "");
             $("#initiateBankTransfer").removeAttr("disabled");

            $('#baseAmount').html(newValue + " " + currentCompanyCurrentCode);
            $('#baseCurrency').html(currentCompanyCurrentCode);
            $('#exchangeAmount').html(newValue);
            $('#exchangeCurrency').html(countryCurrency);
            $('#finalAmount').val(newValue);
            $('#exchangeAmountValue').val(newValue);
            $('#amt_chg').val(0);
            $('#exchangeCharger').html(0 + " ");
            $('#chargeValue').html(0);

            $('#finalAmountTxt').html(newValue);
            $('#modalfinalAmount').html(newValue);

        } else {
            $('#loaderText').html('Fetching exchange rates...');

            $.ajax({
                type: "post",
                url: "/company/transaction/get-current-exchange-rates",
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data:
                {
                    'sourceCurrency': currentCompanyCurrentCode,
                    'countryCurrency': countryCurrency,
                    'exchangeAmount': newValue
                },
                beforeSend: function () {
                    $('#initiateBankTransfer').attr("disabled", "disabled");
                    $('#initiateBankTransfer').css("opacity", ".5");
                },
                cache: false,
                success: function (data) {
                    $('#loaderText').html('');

                    if (data.status == 1) {

                        console.log(data.data.charge);

                        currentExchangeRate = data.data.rate;
                        currentExchangeCharge = data.data.charge;
                        newExchangeAmount = data.data.final_amount;
                        availableBalance = data.data.available_balance;

                        $('#baseAmount').html(newValue);
                        $('#baseCurrency').html(currentCompanyCurrentCode);
                        $('#exchangeRate').html(currentCompanyCurrentCode + " " + data.data.rate);
                        $('#exchangeAmount').html(data.data.amount);
                        $('#exchangeCurrency').html(countryCurrency);
                        $('#finalAmount').val(newValue);
                        $('#exchangeAmountValue').val(data.data.amount);
                        $('#amt_chg').val(data.data.charge);
                        $('#exchangeCharger').html(data.data.charge + " ");
                        $('#chargeValue').html(data.data.charge);
                        $('#withdrawal_charge').val(data.data.charge);
                        $('#finalAmountTxt').html(data.data.final_amount);
                        $('#modalfinalAmount').html(data.data.final_amount);
                        if (data.data.final_amount > availableBalance) {
                            $('#balanceStatus').html("Insufficient Balance");
                            $('#initiateBankTransfer').attr("disabled", "disabled");
                            $('#initiateBankTransfer').css("opacity", ".5");
                        } else {
                            $('#balanceStatus').html("");
                            $('#initiateBankTransfer').css("opacity", "");
                            $("#initiateBankTransfer").removeAttr("disabled");
                        }

                        $('#exchangeDiv').fadeIn(200);
                    } else { }
                }
            });
            $('#initiateBankTransfer').css("opacity", "");
            $("#initiateBankTransfer").removeAttr("disabled");
            console.log('different currency');
        }
    }

    // if (value.length == 10) {
    //     console.log("Verifying account number " + value);

    //     $("#myLoader").toggle();

    //     $('#accountName').val("");
    //     _getValidationField[0].style.display = 'none';


    //     data_info = {
    //         "accountNumber": value,
    //     }

    //     $.ajax({
    //         url: 'verify-wallet-account',
    //         type: 'post',
    //         data: data_info,
    //         dataType: 'json',
    //         headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    //         success: function (data) {

    //             if (data.status == 1) {
    //                 $('#accountName').val(data.data.accountName);
    //                 $('#companyCurrentCode').val(data.data.countryCurrency);
    //                 _getValidationField[0].style.display = 'none';
    //             } else {
    //                 console.log(data);
    //                 _getValidationField[0].innerHTML = 'Invalid Account Number';
    //                 _getValidationField[0].style.display = 'block';
    //             }

    //             $("#myLoader").toggle();
    //         },
    //         error: function (jqXHR, status, error) {

    //             $('.toast').addClass("bg-danger");
    //             var msg = '';
    //             if (jqXHR.status === 0) {
    //                 msg = 'Not connected\n Verify Network.';
    //             } else if (jqXHR.status == 404) {
    //                 msg = 'Requested page not found. [404]';
    //             } else if (jqXHR.status == 500) {
    //                 msg = 'Internal Server Error [500].';
    //             } else {
    //                 msg = 'Uncaught Error.\n' + error;
    //             }

    //             $('.toast-msg').html(msg);
    //         },
    //         xhr: function () {
    //             var xhr = $.ajaxSettings.xhr();
    //             xhr.onprogress = function e() {
    //                 // For downloads
    //                 if (e.lengthComputable) {
    //                     console.log(e.loaded / e.total);
    //                     $(".toast-msg").html(e.loaded / e.total);
    //                 }
    //             };
    //             xhr.upload.onprogress = function (e) {
    //                 // For uploads
    //                 if (e.lengthComputable) {
    //                     console.log(e.loaded / e.total);
    //                 }
    //             };
    //             return xhr;
    //         }
    //     }).done(function () {
    //         $('.toast').addClass("bg-success");
    //     });
    //     $(".toast").toast({
    //         delay: 5000
    //     });
    //     $('.toast').toast('show');
    // } else {
    //     $('#accountName').val('');
    // }
}

renderFundWallet(false, '', '');

// renderTable
$("#pending_approval_form").on('submit',function() {
    $officer = $('#officer').val();
    var formData = $(this).serialize();
    $link ='/company/transaction/approve-reject-pending-txn';
    var formData = $(this).serialize();
    ajax_operation('post', $link, formData);
    $('#html5-extension').DataTable().ajax.url("/company/transaction/pending-transaction?staff="+$officer).load();
    return false;
    });
function viewPendingDetails($id){
    $('.cusIMG').hide();
    $('.modal-body').hide();
    $('#exampleModalPending').modal('show');
    $.ajax({
        url:'/company/transaction/more-details/'+$id,
        type: 'get',
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success:function(data){
            let details = data.data;
            $('.modal-body').show();
            $('#bodyTable').empty();
            $('.customerinfo').empty();
            $('.amount').html(details.amount);
            $('#pendingid').val($id);
            $('.customerinfoPhoto').html("<img src='"+data.customerlogo+"' style='width:100%'>");
            $('.tellerinfoPhoto').html("<img src='"+data.tellerLogo+"' style='width:100%'>");
            if(data.userData != 0){
                $('.cusIMG').show();
                $('.customerinfo').append("<ul class='text-left'>");
                $('.customerinfo').append("<li>Customer Name: "+data.userData.first_name+" "+data.userData.last_name+"</li>");
                $('.customerinfo').append("<li>Phone: "+data.userData.phone+"</li>");
                $('.customerinfo').append("<li>Email: "+data.userData.email+"</li>");
                $('.customerinfo').append("</ul>");
            }

            $('.tellerinfo').empty();
            $('.tellerinfo').append("<ul class='text-left'>");
            $('.tellerinfo').append("<li>Teller Name: "+data.teller.first_name+" "+data.teller.last_name+"</li>");
            $('.tellerinfo').append("<li>Phone: "+data.teller.phone+"</li>");
            $('.tellerinfo').append("<li>Email: "+data.teller.email+"</li>");
            $('.tellerinfo').append("<li>Balance: "+data.teller.staff_wallet.balance+"</li>");
            $('.tellerinfo').append("</ul>");
            // beneficiary
            $('.beneficiary').html('');
            if(data.beneficiary != false){
                $('.beneficiary').html('<h2>Beneficiary Account</h2><p>Beneficiary Account: '+data.beneficiary.accountNo+'</p><p>Beneficiary Bank: '+data.beneficiary.bank.bank_name+'</p><p>name: '+data.beneficiary.name+'</p>');
            }
            $('.txnref').html("Transaction Reference: "+details.transaction_reference);
            $('.txnType').html("Transaction Type: "+details.type.transaction_type);
            //transaction_type
            // account.forEach(function(savings) {
            //     // console.log(details.user.first_name);
            //     $('#bodyTable').append("<tr><td>"+details.user.first_name+" "+details.user.last_name+"</td><td>"+savings.virtual_accnum+"</td><td>"+savings.account_type.savings_product+"</td><td>"+savings.user_wallet.balance+"</td></tr>");
            // });
            // details.forEach(function(detail) {
            // });
        }
    });
}
function rejectApprovePending($action){
    $officer = $('#officer').val();
    $pendingid =$('#pendingid').val();
    $transactionPin = $('#transactionPin').val();
    $btnTXT = ($action==1)?'Approve':'Reject';
    $btnID = ($action==1)?'approvebtn':'rejectbtn';
    ajax_operation('post', '/company/transaction/approve-reject-pending-txn', {action:$action,pendingid:[$pendingid],transactionPin:$transactionPin},$btnID,$btnTXT);
    $('#html5-extension').DataTable().ajax.url("/company/saving/view-pending?staff="+$officer).load();
}
function renderStaff() {
    $branch = $('#branch').val();
    $.ajax({
       url: '/company/companyname/branch/branch_staff/' + $branch,
       type: 'GET',
       dataType: 'json',
       success: function(data) {
           $('select[name="officer"]').empty();

           data.forEach(function(staff) {

               $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
           });
           $officer = $('#officer').val();
           $('#html5-extension').DataTable().ajax.url("/company/transaction/pending-transaction?staff="+$officer).load();

       }
    });
    }
    function renderStaff_pending() {
    $officer = $('#officer').val();
    $('#html5-extension').DataTable().ajax.url("/company/transaction/pending-transaction?staff="+$officer).load();
    }
