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

    // var f1 = flatpickr(document.getElementById('basicFlatpickr'));

    $('#exp_date').inputmask("99/99");
    $('#cvv').inputmask("999");
    $('#basicFlatpickr').flatpickr(); //got error $(...).flatpickr is not a function


    $('#fund_amount').on('input', function (e) {
        var fundAmount = e.target.value;

        console.log(fundAmount);

        if (fundAmount < 50) {
            $('#fundCardBtn').prop('disabled', true);
            _getValidationField[11].innerHTML = 'Minimum fund amount is NGN50';
            _getValidationField[11].style.display = 'block';
        } else {
            _getValidationField[11].style.display = 'none';
            $('#fundCardBtn').prop('disabled', false);
        }
    })

    $(document).ready(function () {
        $(window).keydown(function (event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
    });

    var f3 = flatpickr(document.getElementById('basicDateFrom'));

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

    $(".js-data-example-ajax").select2({
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

function createCompanyCard() {

    $('#exampleModal').modal({
        backdrop: 'static',
        keyboard: false,
        show: true
    })
}

function showfundWithdrawModal(fundOrWithdraw) { // value 1 = Fund, 2, Withdraw
    $('#fundWithdrawModalLabel').text(fundOrWithdraw == 1 ? "Fund Virtual Card" : "Withdraw From Virtual Card")
    $('#fundWithdrawType').val(fundOrWithdraw)

    var newRef = $('#cardReference').val();
    console.log(newRef);

    $('#fundWithdrawModal').modal({
        backdrop: 'static',
        keyboard: false,
        show: true
    })
}

function showfundBlockCardModal(blockOrUnblock) { // value 1 = Fund, 2, Withdraw
    $('#blockUnblockLabel').text(blockOrUnblock == 1 ? "Block Card" : "Unblock Card")
    $('#blockUnblockWarning').html(blockOrUnblock == 1 ? "Are you sure you want to block this card?" : "Are you sure you want to unblock this Card");

    var newRef = $('#cardReference').val();
    console.log(newRef);

    $('#blockCardModal').modal({
        backdrop: 'static',
        keyboard: false,
        show: true
    })
}

function createCompanyCreate() {

    var cardCompanyName = $('#cardCompanyName').val();
    var cardCurrency = $('#companyCardCurrency').val();
    var amount = $('#companyCardAmount').val();
    var token = $('.token').val();
    $('.validation-text').html();

    if (cardCompanyName == "") {
        _getValidationField[0].innerHTML = 'Company Name is required';
        _getValidationField[0].style.display = 'block';
        return false;
    } else {
        _getValidationField[0].style.display = 'none';
    }

    if (amount == "") {
        _getValidationField[1].innerHTML = 'Amount is Required';
        _getValidationField[1].style.display = 'block';
        return false;
    } else {
        _getValidationField[1].style.display = 'none';
    }

    data_info = {
        cardCompanyName: cardCompanyName,
        cardCurrency: cardCurrency,
        companyCardCategory: 1,
        amount: amount
    };

    console.log(data_info);

    $('#createCompanyCardModalBtn').prop("disabled", true);
    $('#createCompanyCardModalBtn').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    $.ajax({
        url: 'create-company-card',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('#exampleModal').modal('hide');

            document.getElementById("createCardForm").reset();


            if (data.status == 1) {
                console.log(data);

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em',
                    allowOutsideClick: false,

                }).then(function (result) {
                    if (result.value) {
                        window.location.reload();

                    }
                })
            } else if (data.status == 3) {

                swal({
                    title: 'Error!',
                    text: data.data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }

            $('#createCompanyCardModalBtn').prop("disabled", false);
            $('#createCompanyCardModalBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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


            swal({
                title: 'Error!',
                text: msg,
                type: 'error',
                padding: '2em'
            })

            $('#createCompanyCardModalBtn').prop("disabled", false);
            $('#createCompanyCardModalBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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

    // renderCardManagement();
}

function fundWithdrawalTransaction() {

    var fundWithdrawAmount = $('#fundWithdrawalAmount').val();
    var fundWithdrawType = $('#fundWithdrawType').val();
    var cardReference = $('#cardReference').val();
    var description = $('#fundWithdrawalDescription').val();
    var token = $('.token').val();
    $('.validation-text').html();

    console.log(cardReference + "reference");

    if (fundWithdrawAmount == "") {
        _getValidationField[3].innerHTML = 'Amount is required';
        _getValidationField[3].style.display = 'block';
        return false;
    } else {
        _getValidationField[3].style.display = 'none';
    }

    data_info = {
        amount: fundWithdrawAmount,
        description: description,
        cardReference: cardReference,
        fundWithdrawType: fundWithdrawType,
    };

    console.log(data_info);

    $('.modalCancelButton').prop("disabled", true);
    $('#fundWithdrawBtn').prop("disabled", true);
    $('#fundWithdrawBtn').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    $.ajax({
        url: 'fund-withdraw-virtual-card',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('#fundWithdrawModal').modal('hide');
            clearVirtualCardModal();

            console.log(data.status + "sta");
            if (data.status == 1) {


                clearVirtualCardModal();
                $('#cardAvailableBalance').text(data.data.cardBalance)
                $('#companyWalletBalance').text(data.data.walletBalance)

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em'
                })
            } else if (data.status == 2) {

                _getValidationField[3].innerHTML = data.message;
                _getValidationField[3].style.display = 'block';
                return;

            } else if (data.status == 3) {

                swal({
                    title: 'Error!',
                    text: data.data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }

            $('.modalCancelButton').prop("disabled", false);
            $('#fundWithdrawBtn').prop("disabled", false);
            $('#fundWithdrawBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

        },
        error: function (jqXHR, status, error) {
            clearVirtualCardModal();

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


            swal({
                title: 'Error!',
                text: msg,
                type: 'error',
                padding: '2em'
            })

            $('.modalCancelButton').prop("disabled", false);
            $('#fundWithdrawBtn').prop("disabled", false);
            $('#fundWithdrawBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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

    // renderCardManagement();
}


function setCardCategory(cardType) {
    $('#cardCategory').val(cardType)
}

function setModalButtonLoading(isLoading) {

    $('.modalCancelButton').prop("disabled", isLoading);
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

function clearVirtualCardModal() {

    $('#fundWithdrawalDescription').val('');
    $('#fundWithdrawalAmount').val('');

}

function renderCardManagement(hasMessage, messageType, message) {
    console.log("Page loaded");
    $send_method = 'POST';
    $pageUrl = 'company-card-management-page';
    $data_info = { hasMessage: hasMessage, messageType: messageType, message: message }
    ajax_fetch($send_method, $pageUrl, $data_info);
}

function showSuccessAlert(message) {

    console.log(message + "sldjflksjdflksj");

    if (message != '')
        renderCardManagement(true, 'success', message);
    else {

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
    $('#initiateCompanyTransactionBtn').attr("disabled", "disabled");
    $('#initiateCompanyTransactionBtn').css("opacity", ".5");
    debouncina(verifyAmount, 1000);
}

const verifyAmount = () => {

    var countryCurrency = $('#companyCurrencyCode').val();
    var currentCompanyCurrentCode = $('#currentCompanyCurrentCode').val();

    newValue = $('#amount').val();

    if (newValue > 0) {
        console.log(countryCurrency + " " + currentCompanyCurrentCode);

        if (countryCurrency == currentCompanyCurrentCode) {

            var companyWalletBalance = $('#companyWalletBalance').text();
            companyWalletBalance = companyWalletBalance.replace(/\,/g, '');
            companyWalletBalance = Number(companyWalletBalance);

            console.log(companyWalletBalance + " " + newValue);

            if (companyWalletBalance < newValue) {
                console.log("Insufficient Balance");
                _getValidationField[2].innerHTML = 'Insufficient Balance';
                _getValidationField[2].style.display = 'block';
                return false;
            } else {
                _getValidationField[2].style.display = 'none';
            }

            $('#initiateCompanyTransactionBtn').css("opacity", "");
            $("#initiateCompanyTransactionBtn").removeAttr("disabled");

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
                url: "get-current-exchange-rates",
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: {
                    'sourceCurrency': currentCompanyCurrentCode,
                    'countryCurrency': countryCurrency,
                    'exchangeAmount': newValue
                },
                beforeSend: function () {
                    $('#initiateCompanyTransactionBtn').attr("disabled", "disabled");
                    $('#initiateCompanyTransactionBtn').css("opacity", ".5");
                },
                cache: false,
                success: function (data) {
                    $('#loaderText').html('');

                    if (data.status == 1) {

                        console.log(data.data.charge);

                        currentExchangeRate = data.data.rate;
                        currentExchangeCharge = data.data.charge;
                        newExchangeAmount = data.data.finalAmount;
                        availableBalance = data.data.availableBalance;

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

                        $('#finalAmountTxt').html(newValue);
                        $('#modalfinalAmount').html(newValue);

                        if (newValue > availableBalance) {
                            $('#balanceStatus').html("Insufficient Balance");
                            $('#initiateCompanyTransactionBtn').attr("disabled", "disabled");
                            $('#initiateCompanyTransactionBtn').css("opacity", ".5");
                        } else {
                            $('#balanceStatus').html("");
                            $('#initiateCompanyTransactionBtn').css("opacity", "");
                            $("#initiateCompanyTransactionBtn").removeAttr("disabled");
                        }

                        $('#exchangeDiv').fadeIn(200);
                    } else { }
                }
            });

            console.log('different currency');
        }
    }

}


function show_customer_details(id) {
    $("#add-staff")[0].reset();
    $.ajax({
        url: 'customer_list/' + id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {

            if (data.status == 0) {
                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                console.log(data.data);

                $('#staff_id').val(data.data.uuid);
                $('#c-first-name').val(data.data.first_name);
                $('#c-last-name').val(data.data.last_name);
                $('#c-username').val(data.data.username);
                $('#c-email').val(data.data.email);
                $('#c-phone').val(data.data.phone);
                $('#c-address').val(data.data.address);
                $('#c-bvn').val(data.data.bvn);

                $('#hidepsw').hide();
                $('#exampleModal').modal('show');
            }
        }
    });
    //edit_role(roleid); for multiple checkbox rolee

}


function createPhysicalCard() {

    var firstName = $('#c-first-name').val();
    var lastName = $('#c-last-name').val();
    var email = $('#c-email').val();
    var phone = $('#c-phone').val();
    var address = $('#c-address').val();
    var bvn = $('#c-bvn').val();
    var customer_reference = $('#staff_id').val();
    var cardNumber = $('#c-cardnumber').val();
    var cardType = $('#c-card_type').val();

    $('.validation-text').html();

    if (firstName == "") {
        _getValidationField[0].innerHTML = 'First Name is required';
        _getValidationField[0].style.display = 'block';
    } else {
        _getValidationField[0].style.display = 'none';
    }

    if (lastName == "") {
        _getValidationField[1].innerHTML = 'Last is Required';
        _getValidationField[1].style.display = 'block';
    } else {
        _getValidationField[1].style.display = 'none';
    }

    if (bvn == "") {
        _getValidationField[2].innerHTML = 'BVN is Required';
        _getValidationField[2].style.display = 'block';
    } else {
        _getValidationField[2].style.display = 'none';
    }

    if (phone == "") {
        _getValidationField[3].innerHTML = 'Phone is Required';
        _getValidationField[3].style.display = 'block';
    } else {
        _getValidationField[3].style.display = 'none';
    }

    if (email == "") {
        _getValidationField[4].innerHTML = 'Email is Required';
        _getValidationField[4].style.display = 'block';
    } else {
        _getValidationField[4].style.display = 'none';
    }

    if (address == "") {
        _getValidationField[5].innerHTML = 'Address is Required';
        _getValidationField[5].style.display = 'block';
    } else {
        _getValidationField[5].style.display = 'none';
    }

    if (cardNumber == "") {
        _getValidationField[5].innerHTML = 'Card Number is Required';
        _getValidationField[5].style.display = 'block';
    } else {
        _getValidationField[5].style.display = 'none';
    }

    if (cardType == "") {
        _getValidationField[6].innerHTML = 'Card Type is Required';
        _getValidationField[6].style.display = 'block';
    } else {
        _getValidationField[6].style.display = 'none';
    }

    if (firstName == "" || lastName == "" || bvn == "" || email == "" || phone == "" || address == "" || cardNumber == "" || cardType == "") return false


    data_info = {

        first_name: firstName,
        last_name: lastName,
        phone: phone,
        address: address,
        email: email,
        bvn: bvn,
        customer_reference: customer_reference,
        card_number: cardNumber,
        card_type: cardType

    };

    console.log(data_info);

    $('#requestCardBtn').prop("disabled", true);
    $('#requestCardBtn').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    $.ajax({
        url: 'create-physical-card',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('#exampleModal').modal('hide');

            document.getElementById("add-staff").reset();


            if (data.status == 1) {
                console.log(data);

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em',
                    allowOutsideClick: false,

                }).then(function (result) {
                    if (result.value) {
                        window.location.reload();

                    }
                })
            } else if (data.status == 3) {

                swal({
                    title: 'Error!',
                    text: data.data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }

            $('#requestCardBtn').prop("disabled", false);
            $('#requestCardBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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


            swal({
                title: 'Error!',
                text: msg,
                type: 'error',
                padding: '2em'
            })

            $('#requestCardBtn').prop("disabled", false);
            $('#requestCardBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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

    // renderCardManagement();
}

function addPhysicalCard() {

    var card_name = $('#card_name').val();
    var card_type = $('#card_type').val();
    var card_number = $('#card_number').val();
    var exp_date = $('#exp_date').val();
    var cvv = $('#cvv').val();

    var customer_reference = $('#addMyCard').data('id');

    $('.validation-text').html();

    if (card_name == "") {
        _getValidationField[5].innerHTML = 'Card Name is required';
        _getValidationField[5].style.display = 'block';
    } else {
        _getValidationField[5].style.display = 'none';
    }

    if (card_type == "") {
        console.log("Card type missging");
        _getValidationField[6].innerHTML = 'Card Type is Required';
        _getValidationField[6].style.display = 'block';
    } else {
        _getValidationField[6].style.display = 'none';
    }

    if (card_number == "") {
        _getValidationField[7].innerHTML = 'Card Number is Required';
        _getValidationField[7].style.display = 'block';
    } else {
        _getValidationField[7].style.display = 'none';
    }

    if (exp_date == "") {
        _getValidationField[8].innerHTML = 'Expiry Date is Required';
        _getValidationField[8].style.display = 'block';
    } else {
        _getValidationField[8].style.display = 'none';
    }

    if (cvv == "") {
        _getValidationField[9].innerHTML = 'CVV is Required';
        _getValidationField[9].style.display = 'block';
    } else {
        _getValidationField[9].style.display = 'none';
    }

    if (card_name == "" || card_type == "" || exp_date == "" || card_number == "" || cvv == "") return false

    data_info = {

        card_name: card_name,
        card_type: card_type,
        card_number: card_number,
        exp_date: exp_date,
        cvv: cvv,
        customer_reference: customer_reference,

    };

    console.log(data_info);

    $('#addCardBtn').prop("disabled", true);
    $('#addCardBtn').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    $.ajax({
        url: 'add-physical-card',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('#exampleModal').modal('hide');

            document.getElementById("add-staff").reset();


            if (data.status == 1) {
                console.log(data);

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em',
                    allowOutsideClick: false,

                }).then(function (result) {
                    if (result.value) {
                        window.location.reload();

                    }
                })
            } else if (data.status == 3) {

                swal({
                    title: 'Error!',
                    text: data.data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }

            $('#addCardBtn').prop("disabled", false);
            $('#addCardBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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


            swal({
                title: 'Error!',
                text: msg,
                type: 'error',
                padding: '2em'
            })

            $('#addCardBtn').prop("disabled", false);
            $('#addCardBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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

    // renderCardManagement();
}

function showFundCardModal(userReference) {

    $('#staff_id').val(userReference)

    $.ajax({
        url: 'customer_accounts/' + userReference,
        type: 'GET',
        dataType: 'json',
        success: function (data) {

            console.log(data);

            if (data.status == 0) {
                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                console.log(data.data);
                var obj = document.getElementById("account_id");
                obj.replaceChildren();

                data.data.accounts.forEach((element, index, array) => {
                    console.log(element.text);
                    opt = document.createElement("option");
                    opt.value = element.id;
                    opt.text = element.virtual_accnum + " - NGN " + element.user_wallet.balance.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');;
                    obj.appendChild(opt);
                    obj.child
                });

                $('#fundCardModal').modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                })

            }
        }
    });


}

function fundPhysicalCard() {

    var fund_amount = $('#fund_amount').val();

    var customer_reference = $('#staff_id').val();
    var account_id = $('#account_id').val();

    $('.validation-text').html();

    if (fund_amount == "") {
        _getValidationField[10].innerHTML = 'Amount is required';
        _getValidationField[10].style.display = 'block';
    } else {
        _getValidationField[10].style.display = 'none';
    }

    if (fund_amount == "") return false

    data_info = {
        fund_amount: fund_amount,
        customer_reference: customer_reference,
        account_id: account_id,
    };

    console.log(data_info);

    $('#fundCardBtn').prop("disabled", true);
    $('#fundCardBtn').html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    $.ajax({
        url: 'fund-physical-card',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('#exampleModal').modal('hide');

            document.getElementById("add-staff").reset();


            if (data.status == 1) {
                console.log(data);

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em',
                    allowOutsideClick: false,

                }).then(function (result) {
                    if (result.value) {
                        window.location.reload();

                    }
                })
            } else if (data.status == 3) {

                swal({
                    title: 'Error!',
                    text: data.data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }

            $('#fundCardBtn').prop("disabled", false);
            $('#fundCardBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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


            swal({
                title: 'Error!',
                text: msg,
                type: 'error',
                padding: '2em'
            })

            $('#fundCardBtn').prop("disabled", false);
            $('#fundCardBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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

    // renderCardManagement();
}

function getPhysicalCardBalance(userid) {

    // console.log(userId + " " + cardNumber + " " + cvv + " " + expiryDate);

    console.log("We are going places " + userid);


    $('#getMyCardBalance-' + userid).prop("disabled", true);
    $('#getMyCardBalance-' + userid).html(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...'
    );

    var data_info = {
        user_id: userid
    }

    $.ajax({
        url: 'get-physical-card-balance',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            console.log(data);

            $('.modalCancelButton').prop("disabled", false);
            $('#getMyCardBalance-' + userid).prop("disabled", false);
            $('#getMyCardBalance-' + userid).html('Continue');

            if (data.status == 1) {
                $('#cardBalanceModal').modal('show');

                $('#cardName').text(data.data.card.card_name);
                $('#cardNumber').text(data.data.card.card_number);
                $('#expiryDate').text(data.data.card.exp_date);
                $('#modalCvv').text(data.data.card.cvv);
                $('#modalfinalAmount').text(data.data.available_balance);

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


            $('.modalCancelButton').prop("disabled", false);
            $('#getMyCardBalance-' + userid).prop("disabled", false);
            $('#getMyCardBalance-' + userid).html('Continue');


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


            swal({
                title: 'Error!',
                text: msg,
                type: 'error',
                padding: '2em'
            })

            $('#fundCardBtn').prop("disabled", false);
            $('#fundCardBtn').html('<i class="fa fa-send"></i>&nbsp;&nbsp; Continue');

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


    // renderCardManagement();
}


function show_customer_details(id) {

    // first_name
    // last_name
    // phone
    // address
    // email
    // bvn
    // customer_reference

    $("#add-staff")[0].reset();
    $.ajax({
        url: 'customer_list/' + id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {

            if (data.status == 0) {
                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                console.log(data.data.address);

                $('#staff_id').val(data.data.uuid);
                $('#c-first-name').val(data.data.first_name);
                $('#c-last-name').val(data.data.last_name);
                $('#c-email').val(data.data.email);
                $('#c-phone').val(data.data.phone);
                $('#c-address').val(data.data.address);
                $('#c-bvn').val(data.data.bvn);

                $('#hidepsw').hide();
                $('#exampleModal').modal('show');
            }
        }
    });
    //edit_role(roleid); for multiple checkbox rolee

}

function show_card_customer_name(id) {

    console.log(id);

    // first_name
    // last_name
    // phone
    // address
    // email
    // bvn
    // customer_reference

    $("#add-staff")[0].reset();
    $.ajax({
        url: 'customer_list/' + id + '?add_card=true',
        type: 'GET',
        dataType: 'json',
        success: function (data) {

            if (data.status != 1) {
                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                console.log(data.data.address);

                $('#staff_id').val(data.data.uuid);
                $('#card_name').val(data.data.first_name + " " + data.data.last_name);

                $('#hidepsw').hide();
                $('#cardModal').modal('show');
            }
        }
    });
    //edit_role(roleid); for multiple checkbox rolee

}

function add_card_details(id) {

    $("#add-card")[0].reset();
    $.ajax({
        url: 'customer_list/' + id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {

            if (data.status == 0) {
                swal({
                    title: 'Error!',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            } else {

                console.log(data.data.address);

                $('#staff_id').val(data.data.uuid);
                $('#c-first-name').val(data.data.first_name);
                $('#c-last-name').val(data.data.last_name);
                $('#c-email').val(data.data.email);
                $('#c-phone').val(data.data.phone);
                $('#c-address').val(data.data.address);
                $('#c-bvn').val(data.data.bvn);

                $('#hidepsw').hide();
                $('#exampleModal').modal('show');
            }
        }
    });
    //edit_role(roleid); for multiple checkbox rolee

}


