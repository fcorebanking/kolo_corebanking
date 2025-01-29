var _getValidationField = document.getElementsByClassName('validation-text');
isSuccessmessage = false;
let timer;
let counter = 0;
let newValue;
let newMobileMoneyValue;
let selectedPhonePrefix = '';

let statusCode;
let statusMessage;
$(document).ready(function ($) {

    $(".basic").select2({
        tags: true,
    });

    $('#transactionPin').inputmask({
        mask: "9999"
    });

    $('#transactionPin').on('focus keyup', function (event) {
        event.preventDefault();
        console.log("lskjdflskjd");
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

    $(".js-data-example-ajax").select2(
        {
            ajax: {
                url: 'https://api.github.com/search/repositories',
                dataType: 'json',
                type: "GET",
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    var res = data.items.map(function (item) {
                        return { id: item.id, text: item.name };
                    });
                    return {
                        results: res
                    };
                }
            },

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


function showMobileMoneyTransactionSummary() {

    var mobileMoneyCountry = $('#mobileMoneyCountry').val();
    var mobileMoneyPaymentMethod = $('#mobileMoneyPaymentMethod').val();
    var mobileMoneyPhone = $('#mobileMoneyPhone').val();
    var recipientName = $('#recipientName').val();
    var mobileMoneyAmount = $('#mobileMoneyAmount').val();
    var mobileMoneyFinalAmount = $('#mobileMoneyFinalAmountTxt').text();
    var exchangeCharge = $('#mobileMoneyExchangeCharger').val();
    var description = $('#mobileMoneyDescription').val();
    var token = $('.token').val();
    $('.validation-text').html();

    if (mobileMoneyCountry == "") {
        _getValidationField[0].innerHTML = 'Country is required';
        _getValidationField[0].style.display = 'block'; return false;
    } else {
        _getValidationField[0].style.display = 'none';
    }
    if (mobileMoneyPaymentMethod == "") {
        _getValidationField[1].innerHTML = 'Payment Method is required';
        _getValidationField[1].style.display = 'block'; return false;
    } else {
        _getValidationField[1].style.display = 'none';
    }

    if (mobileMoneyPhone == "") {
        _getValidationField[2].innerHTML = 'Recipient Phone Number is required';
        _getValidationField[2].style.display = 'block'; return false;
    } else {
        _getValidationField[2].style.display = 'none';
    }

    if (recipientName == "") {
        _getValidationField[3].innerHTML = 'Recipient Name is required';
        _getValidationField[3].style.display = 'block'; return false;
    } else {
        _getValidationField[3].style.display = 'none';
    }

    if (mobileMoneyAmount == "") {
        _getValidationField[4].innerHTML = 'Amount is required';
        _getValidationField[4].style.display = 'block'; return false;
    } else {
        _getValidationField[4].style.display = 'none';
    }

    description = description == "" ? "Mobile Money Transfer" : description;

    data_info = {
        finalAmount: mobileMoneyFinalAmount,
        exchangeCharge: exchangeCharge,
        accountNumber: mobileMoneyPhone,
        accountName: recipientName,
        description: description,
        transaction_type: 19,
    };

    console.log(data_info);

    $('#initiateMobileMoneyTransfer').prop("disabled", true);
    $('#initiateMobileMoneyTransfer').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    $.ajax({
        url: 'initiate-transaction',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            $('#initiateMobileMoneyTransfer').prop("disabled", false);
            $('#initiateMobileMoneyTransfer').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');


            if (data.status == 1) {

                $('#transferType').text("Mobile Money Transfer");
                $('#receipientName').text(recipientName);
                $('#receipientAccount').text(selectedPhonePrefix + "" + mobileMoneyPhone);
                $('#amountValue').text(mobileMoneyFinalAmount);
                $('#chargeValue').text(data.data.charge);
                $('#totalValue').text(data.data.total);
                $('#transactionId').val(data.data.transactionId);
                $('#transactionTypeId').val(data.data.transactionTypeId);
                $('#mydescription').text(description);

                $('#fundWalletSummary').modal({ backdrop: 'static', keyboard: false, show: true })
                console.log(data);
            } else {

                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })

            }

            $('#initiateCompanyTransactionBtn').prop("disabled", false);
            $('#initiateCompanyTransactionBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

        },
        error: function (jqXHR, status, error) {


            $('#initiateMobileMoneyTransfer').prop("disabled", false);
            $('#initiateMobileMoneyTransfer').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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

            $('#initiateMobileMoneyTransfer').prop("disabled", false);
            $('#initiateMobileMoneyTransfer').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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
        $('.toast').addClass("bg-success");
    });

    // renderFundWallet();
}

function showBankTransferSummary() {

    var transferBankCode = $('#transferBankCode').val();
    var transferAccountNumber = $('#transferAccountNumber').val();
    var transferAccountName = $('#transferAccountName').val();
    var transferAmount = $('#transferAmount').val();
    var description = $('#transferDescription').val();
    var bankName = $('#bankName').val();
    var token = $('.token').val();
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
        transferAmount: transferAmount,
        description: description,
        transaction_type: 8,
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

            $('#initiateBankTransfer').prop("disabled", false);
            $('#initiateBankTransfer').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

            if (data.status == 1) {

                $('#transferType').text("Wallet to Bank Transfer");
                $('#receipientName').text(transferAccountName);
                $('#receipientAccount').text(transferAccountNumber);
                $('#receipientBankName').text("(" + bankName + ")");
                $('#amountValue').text(data.data.transferAmount);
                $('#chargeValue').text(data.data.charge);
                $('#modalfinalAmount').text(data.data.total);
                $('#transactionId').val(data.data.transactionId);
                $('#transactionTypeId').val(data.data.transactionTypeId);
                $('#mydescription').text(description);

                $('#btnFetch').prop("disabled", false);
                $('#btnFetch').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

                $('#fundWalletSummary').modal({ backdrop: 'static', keyboard: false, show: true })
                // console.log(data);
            }
        },
        error: function (jqXHR, status, error) {

            console.log(status);
            console.log(error);
            console.log(jqXHR.status);


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
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');

    // renderFundWallet();
}

function initiateTransacion() {
    var amount = $('#amount').val();
    var fundMethod = $('#fundMethod').val();

    data_info = { amount: amount, transaction_type: 1, fund_method: fundMethod };

    $.ajax({
        url: 'initiate-transaction',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { "X-CSRFToken": '{{csrf_token}}' },
        success: function (data) {

            console.log(data);
            window.location.href = data.link;

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
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');

}

function companyWalletsTransfer() {

    var transactionTypeId = $('#transactionTypeId').val();
    var amount = $('#modalfinalAmount').text();
    var transactionId = $('#transactionId').val();
    var destinationAccount = $('#receipientAccount').text();
    var transactionPin = $('#transactionPin').val();

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

        destinationAccount = destinationAccount.startsWith("0") ? "" + destinationAccount : destinationAccount;

        data_info = {
            amount: amount,
            transactionId: transactionId,
            destinationAccount: destinationAccount,
            description: description,
            bankCode: transferBankCode,
            transactionPin: transactionPin,
            receipientName: receipientName,
        };
    } else if (transactionTypeId == 19) {
        url = "mobile-money-transfer"
        var receipientName = $('#receipientName').text();

        var countryCurrency = $('#mobileMoneyCountry').val();
        var myAmount = $('#mobileMoneyExchangeAmount').text();
        var destinationAccount = $('#receipientAccount').text();
        var transferBankCode = $('#mobileMoneyPaymentMethod').val();

        destinationAccount = destinationAccount.startsWith("+") ? "" + destinationAccount.substring(1) : destinationAccount.substring(1);

        data_info = {
            amount: amount,
            exchangeAmount: myAmount,
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

            setModalButtonLoading(false);
            window.scroll({
                top: 0,
                left: 0,
                behavior: 'smooth'
            });

            if (data.status == 1) {
                if (data.status == 1) {
                    $('#companyWalletBalance').text(data.data.balance);
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
                swal({
                    title: 'Error',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })

                _getValidationField[5].innerHTML = data.message;
                _getValidationField[5].style.display = 'block'; return false;
            } else if (data.status == 3) {
                swal({
                    title: 'Error',
                    text: data.data.message,
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

function getCountryPaymentMethod() {

    var mobileMoneyPaymentMethod = $('#mobileMoneyPaymentMethod').val();
    var mobileMoneyCountry = $('#mobileMoneyCountry').val();
    selectedTextarea = $('#mobileMoneyAmount')[0];

    if (mobileMoneyCountry != '') $('#networkProvidersDiv').fadeIn(200);
    else $('#networkProvidersDiv').fadeOut(200);

    _getValidationField[0].style.display = 'none';


    // $('#mobileMoneyAmount').attr("placeholder", );
    // $('#mobileMoneyAmountLabel').html("Amount (" + mobileMoneyCountry + ")");

    getGhanaMobileMoney(mobileMoneyCountry);

    switch (mobileMoneyCountry) {
        case "GHS":
            selectedPhonePrefix = "+233"
            $('')
            break;
        case "KES":
            selectedPhonePrefix = "+254"
            break;
        case "RWF":
            selectedPhonePrefix = "+250"
            break;
        case "TZS":
            selectedPhonePrefix = "+255"
            break;
        case "UGX":
            selectedPhonePrefix = "+256"
            break;
        case "ZMW":
            selectedPhonePrefix = "+260"
            break;
        case "ETB":
            selectedPhonePrefix = "+251"
            break;
        case "XAF":
            selectedPhonePrefix = "+236"
            break;
        default:
            break;
    }

    // console.log(selectedPhonePrefix);

    $('#phoneNumberPrefix').text(selectedPhonePrefix)

    newMobileMoneyValue = $('#mobileMoneyAmount').val();

    if (newMobileMoneyValue != "") {
        $('#initiateMobileMoneyTransfer').attr("disabled", "disabled");
        $('#initiateMobileMoneyTransfer').css("opacity", ".5");

        verifyMobileMoneyAmount();
    }


}

function getGhanaMobileMoney(currencyType) {

    var obj = document.getElementById("mobileMoneyPaymentMethod");
    obj.replaceChildren();
    var years = [];

    if (currencyType == "GHS") {
        years = [
            { myvalue: "MTN", text: "Ghana MTN Money Transfer" },
            { myvalue: "TIGO", text: "Ghana TIGO Money Transfer" },
            { myvalue: "VODAFONE", text: "Ghana Vodafone Money Transfer" },
            { myvalue: "AIRTEL", text: "Ghana Airtel Money Transfer" },
        ];
    } else if (currencyType == "ETB") {
        years = [
            { myvalue: "ETBAMOLE", text: "Mobile Money" },
        ];

    } else if (currencyType == "XAF") {
        years = [
            { myvalue: "FMM", text: "Mobile Money" },
        ];

    } else {
        years = [
            { myvalue: "MPS", text: "Mobile Money" },
        ];
    }

    years.forEach((element, index, array) => {
        console.log(element.text);
        opt = document.createElement("option");
        opt.value = element.myvalue;
        opt.text = element.text;
        obj.appendChild(opt);
        obj.child
    });
}

function clearAllFields() {

    document.getElementById("bank_transfer_form").reset();

    $('#statusMessageDiv').hide();
    $('#statusMessage').val('');
    $('#networkProvidersDiv').hide();
    $('#phoneNumberPrefix').val('');
    $('#mobileMoneyLoaderText').text("");
    $('#mobileMoneyExchangeDiv').hide();
    $('#mobileMoneyBalanceStatus').text("");

    $(document).ready(function ($) {

        $("#transactionPin").val('');
    });

    var _getValidationField = document.getElementsByClassName('validation-text');

    var i, n = _getValidationField.length;
    for (i = 0; i < n; ++i) {
        _getValidationField[i].style.display = "none";
    }
}

function renderFundWallet(hasMessage, messageType, message) {
    $send_method = 'POST';
    $pageUrl = 'mobile-money-transfer-page';
    $data_info = { hasMessage: hasMessage, messageType: messageType, message: message }
    ajax_fetch($send_method, $pageUrl, $data_info);
}

function showSuccessAlert(message) {

    console.log(message + "sldjflksjdflksj");

    if (message != '')
        renderFundWallet(true, 'success', message);
    else {

    }
}

const debouncina = function (fn, d) {
    if (timer) {
        clearTimeout(timer);
    }

    timer = setTimeout(fn, d);
}

const mymobileMoneyAmount = (e) => {
    newValue = e.target.value;
    $('#initiateMobileMoneyTransfer').attr("disabled", "disabled");
    $('#initiateMobileMoneyTransfer').css("opacity", ".5");
    debouncina(verifyMobileMoneyAmount, 1000);
}


const verifyMobileMoneyAmount = () => {

    var countryCurrency = $('#mobileMoneyCountry').val();
    var currentCompanyCurrentCode = $('#mobileMOneyCurrentCompanyCurrency').val();

    newMobileMoneyValue = $('#mobileMoneyAmount').val();

    if (newMobileMoneyValue > 0) {
        console.log(countryCurrency + " " + currentCompanyCurrentCode);

        if (countryCurrency == currentCompanyCurrentCode) {

            var companyWalletBalance = $('#companyWalletBalance').text();
            companyWalletBalance = companyWalletBalance.replace(/\,/g, '');
            companyWalletBalance = Number(companyWalletBalance);

            console.log(companyWalletBalance + " " + newMobileMoneyValue);

            if (companyWalletBalance < newMobileMoneyValue) {
                console.log("Insufficient Balance");
                _getValidationField[2].innerHTML = 'Insufficient Balance';
                _getValidationField[2].style.display = 'block'; return false;
            } else {
                _getValidationField[2].style.display = 'none';
            }

            $('#initiateMobileMoneyTransfer').css("opacity", "");
            $("#initiateMobileMoneyTransfer").removeAttr("disabled");

            $('#baseAmount').html(newMobileMoneyValue + " " + currentCompanyCurrentCode);
            $('#baseCurrency').html(currentCompanyCurrentCode);
            $('#exchangeAmount').html(newMobileMoneyValue);
            $('#exchangeCurrency').html(countryCurrency);
            $('#finalAmount').val(newMobileMoneyValue);
            $('#exchangeAmountValue').val(newMobileMoneyValue);
            $('#amt_chg').val(0);
            $('#exchangeCharger').html(0 + " ");
            $('#chargeValue').html(0);

            $('#finalAmountTxt').html(newMobileMoneyValue);
            $('#modalfinalAmount').html(newMobileMoneyValue);

        } else {
            $('#mobileMoneyLoaderText').html('Fetching exchange rates...');

            $.ajax({
                type: "post",
                url: "/company/transaction/get-current-exchange-rates",
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data:
                {
                    'sourceCurrency': currentCompanyCurrentCode,
                    'countryCurrency': countryCurrency,
                    'exchangeAmount': newMobileMoneyValue
                },
                beforeSend: function () {
                    $('#initiateMobileMoneyTransfer').attr("disabled", "disabled");
                    $('#initiateMobileMoneyTransfer').css("opacity", ".5");
                },
                cache: false,
                success: function (data) {
                    $('#mobileMoneyLoaderText').html('');

                    if (data.status == 1) {

                        console.log(data.data.charge);

                        currentExchangeRate = data.data.rate;
                        currentExchangeCharge = data.data.charge;
                        newExchangeAmount = data.data.finalAmount;
                        availableBalance = data.data.availableBalance;

                        $('#mobileMoneyBaseAmount').html(newMobileMoneyValue);
                        $('#mobileMoneyBaseCurrency').html(currentCompanyCurrentCode);
                        $('#mobileMoneyExchangeRate').html(currentCompanyCurrentCode + " " + data.data.rate);
                        $('#mobileMoneyExchangeAmount').html(data.data.amount);
                        $('#mobileMoneyExchangeCurrency').html(countryCurrency);
                        $('#mobileMoneyFinalAmount').val(newMobileMoneyValue);
                        $('#mobileMoneyExchangeAmountValue').val(data.data.amount);
                        $('#mobileMoneyExchangeCharger').html(data.data.charge + " ");
                        $('#mobileMoneyChargeValue').html(data.data.charge);

                        $('#mobileMoneyFinalAmountTxt').html(data.data.final_amount);
                        $('#modalfinalAmount').html(data.data.final_amount);

                        console.log(newMobileMoneyValue + " " + availableBalance);

                        if (newMobileMoneyValue > availableBalance) {
                            console.log("Insufficient balance");
                            $('#mobileMoneyBalanceStatus').html("Insufficient Balance");
                            $('#initiateMobileMoneyTransfer').attr("disabled", "disabled");
                            $('#initiateMobileMoneyTransfer').css("opacity", ".5");
                        } else {
                            console.log("Sufficient Balance");
                            $('#mobileMoneyBalanceStatus').html("");
                            $('#initiateMobileMoneyTransfer').css("opacity", "");
                            $("#initiateMobileMoneyTransfer").removeAttr("disabled");
                        }

                        $('#mobileMoneyExchangeDiv').fadeIn(200);
                    } else { }
                }
            });

            console.log('my different currency');
        }
    }
}

renderFundWallet(false, '', '');
