var _getValidationField = document.getElementsByClassName('validation-text');
isSuccessmessage = false;

var myDataBundles = [];

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
            $('#buyAirtimBtn').prop("disabled", false);
        }
    });
    $('#transactionPin').on('keyup', function (event) {
        event.preventDefault();
        if (!$(this).inputmask("isComplete")) {
            $('#buyAirtimBtn').prop("disabled", true);
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


function showTransactionSummary() {

    var networkProvider = $('#networkProvider').val();
    var mobilePhone = $('#mobilePhone').val();
    var dataBundle = $('#dataBundle').val();
    var dataAmount = $('#dataAmount').val();
    var token = $('.token').val();
    $('.validation-text').html();

    console.log(mobilePhone);

    if (networkProvider == "") {
        _getValidationField[0].innerHTML = 'Please Select a Network operator';
        _getValidationField[0].style.display = 'block'; return false;
    } else {
        _getValidationField[0].style.display = 'none';
    }
    if (dataBundle == "") {
        _getValidationField[1].innerHTML = 'Data Bundle is required';
        _getValidationField[1].style.display = 'block'; return false;
    } else {
        _getValidationField[1].style.display = 'none';
    }
    if (mobilePhone == "") {
        _getValidationField[2].innerHTML = 'Mobile Number is required';
        _getValidationField[2].style.display = 'block'; return false;
    } else {
        _getValidationField[2].style.display = 'none';
    }
    if (dataAmount == "") {
        _getValidationField[3].innerHTML = 'Amount is required';
        _getValidationField[3].style.display = 'block'; return false;
    } else {
        _getValidationField[3].style.display = 'none';
    }

    $('#amountValue').text(dataAmount)
    $('#chargeValue').text('0')
    $('#totalValue').text(dataAmount)


    data_info = { amount: dataAmount, finalAmount: dataAmount, transaction_type: 12, description: "Airtime Purchase" };


    $('#mySumit').prop("disabled", true);
    $('#mySumit').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );


    $.ajax({
        url: 'initiate-transaction',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            $('#mySumit').prop("disabled", false);
            $('#mySumit').html('Continue');

            console.log(data);

            if (data.status == 1 || data.status == true) {

                $('#chargeValue').text(data.data.charge);
                $('#totalValue').text(data.data.total);
                $('#transactionId').val(data.data.transactionId);
                $('#transactionTypeId').val(data.data.transactionTypeId);

                $('#airtimeTransactionSummary').modal({ backdrop: 'static', keyboard: false, show: true })
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
            } else if (jqXHR.status == 400 || jqXHR.status == 403) {
                msg = jqXHR.responseJSON.message;
                swal({
                    title: 'Error!',
                    text: msg,
                    type: 'error',
                    padding: '2em'
                })
            } else {
                msg = 'Uncaught Error.\n' + error;
            }

            $('#mySumit').prop("disabled", false);
            $('#mySumit').html('Continue');

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

    // renderFundWallet();
}

function verifydataAmount() {
    var amount = $('#dataAmount').val();
    var companyWalletBalance = $('#companyWalletBalance').text();
    companyWalletBalance = companyWalletBalance.replace(/\,/g, '');
    companyWalletBalance = Number(companyWalletBalance);


    $('#mySumit').attr("disabled", "disabled");
    $('#mySumit').css("opacity", ".5");

    if (amount < 0) {
        _getValidationField[2].innerHTML = 'Invalid Airtime Amount';
        _getValidationField[2].style.display = 'block'; return false;
    }

    console.log(`${amount} ${companyWalletBalance}`);

    if (amount > companyWalletBalance) {
        _getValidationField[2].innerHTML = 'Insufficient Wallet Balance';
        _getValidationField[2].style.display = 'block'; return false;

    }
    _getValidationField[2].style.display = 'none';

    $('#mySumit').css("opacity", "");
    $('#mySumit').prop("disabled", false);

}


function airtimPurchaseTransaction() {

    var transactionTypeId = $('#transactionTypeId').val();
    var amount = $('#totalValue').text();
    var transactionId = $('#transactionId').val();
    var dataBundle = $('#dataBundle').val();
    var networkProviderId = $('#networkProvider').val();
    var mobileNumber = $('#mobilePhone').val();
    var transactionPin = $('#transactionPin').val();

    let url;


    setModalButtonLoading(true)

    data_info = {
        amount: amount,
        transactionTypeId: transactionTypeId,
        transactionId: transactionId,
        transactionPin: transactionPin,
        dataBundle: dataBundle,
        mobileNumber: mobileNumber,
        networkProviderId: networkProviderId,
    };

    console.log(data_info);

    $.ajax({
        url: 'buy-data-transaction',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            $('#transactionPin').val('')

            setModalButtonLoading(false);
            window.scroll({
                top: 0,
                left: 0,
                behavior: 'smooth'
            });

            if (data.status == 1) {
                $('#companyWalletBalance').text(data.data.balance);
                data.status = 'bg-success'

                $('#airtimeTransactionSummary').modal('hide');
                document.getElementById("buyAirtimeForm").reset();

                _getValidationField[3].style.display = 'none';

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em'
                })

                console.log(data);
            } else if (data.status == 2) {
                swal({
                    title: 'Error',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })

                _getValidationField[3].innerHTML = data.message;
                _getValidationField[3].style.display = 'block'; return false;
            } else if (data.status == 3 || data.status == 0) {

                $('#airtimeTransactionSummary').modal('hide');
                document.getElementById("buyAirtimeForm").reset();

                _getValidationField[3].style.display = 'none';

                swal({
                    title: 'Error',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }
        },
        error: function (jqXHR, status, error) {
            console.log(error);
            console.log(status);
            console.log(jqXHR.responseJSON);

            $('#transactionPin').val('')


            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connected\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else if (jqXHR.status == 400) {
                msg = jqXHR.responseJSON.message;
            }
            else {
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
    $('#buyAirtimBtn').prop("disabled", isLoading);
    $('#buyAirtimBtn').html(isLoading ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...' :
        'Continue');
}


function getNetworkProviders() {

    $.ajax({
        url: 'get-data-network-operators',
        type: 'get',
        data: {},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            console.log(data.data.length + " array size");

            var sel = $("#networkProvider");
            sel.empty();

            sel.append('<option value="">::Select Network Provider::</option>')
            for (var i = 0; i < data.data.length; i++) {
                console.log(data.data[i].id + " name: " + data.data[i].name);
                sel.append('<option value="' + data.data[i].id + '">' + data.data[i].name + '</option>');
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

function getSelectedBundle() {

    var selectedBundle = $('#dataBundle').val();
    console.log(selectedBundle);

    if (selectedBundle == "") {
        $("#dataAmount").val("")
        return;
    }

    var mydat = myDataBundles.find(value => value.code == selectedBundle);
    console.log(mydat.amount);
    $("#dataAmount").val(mydat.amount);
}

function getDataBundles() {

    var network = $('#networkProvider').val();

    $('#networkType').html(network)

    console.log(network);

    if (network == "") {
        $('#dataBundleDiv').hide();
        return;
    }
    _getValidationField[0].style.display = 'none';

    $.ajax({
        url: 'get-data-bundles?network=' + network,
        type: 'get',
        data: {},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            console.log(data.data.length + " array size");

            $('#dataBundleDiv').show();

            var sel = $("#dataBundle");
            sel.empty();

            myDataBundles = [];
            sel.append('<option value="">::Select Network Provider::</option>')
            for (var i = 0; i < data.data.length; i++) {
                console.log(data.data[i].variation_code + " name: " + data.data[i].name);
                myDataBundles.push({ code: data.data[i].variation_code, amount: data.data[i].variation_amount });
                sel.append('<option value="' + data.data[i].variation_code + '">' + data.data[i].name + '</option>');
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


function renderFundWallet(hasMessage, messageType, message) {
    $send_method = 'POST';
    $pageUrl = 'buy-data-page';
    $data_info = {
        hasMessage: hasMessage, messageType: messageType, message: message, 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    }
    ajax_fetch($send_method, $pageUrl, $data_info);

    console.log('going there');

}

renderFundWallet(false, '', '');

getNetworkProviders();
