//im not using this so it can be deleted
var $_getValidationField = document.getElementsByClassName('validation-text');

var chargeUsing = $('#incoming_transfer_charge_state').val();
var whoPays = $('#who_to_pay_incoming_transfer_charge').val();
var virtual_card_currency = $('#preferred_virtual_card_currency').val();

$('#additional_virtual_card_funding_fee_label').html(virtual_card_currency == 1 ? 'Additional Virtual Card Funding Fee (NGN)' : 'Additional Virtual Card Funding Fee (USD)');

$('#preferred_virtual_card_currency').change(function() {
    var virtual_card_currency = $('#preferred_virtual_card_currency').val();

    $('#additional_virtual_card_funding_fee_label').html(virtual_card_currency == 1 ? 'Additional Virtual Card Funding Fee (NGN)' : 'Additional Virtual Card Funding Fee (USD)');
})
if (whoPays == 0) {
    $('#incomingTransferChargeDiv').hide();
    $('#incomingTransferCapDiv').hide();
    $('#incomingTransferChargePercentageDiv').hide();
    $('#chargeIncomingUsingDiv').hide();

    $('#incoming_transfer_charge_amount').attr("required", false);
    $('#incoming_transfer_charge_percentage').attr("required", false);
    $('#incoming_transfer_charge_capped_at').attr("required", false);

} else {
    if (chargeUsing == 1) {
        $('#incomingTransferChargeDiv').hide();
        $('#incomingTransferCapDiv').show();
        $('#incomingTransferChargePercentageDiv').show();

        $('#incoming_transfer_charge_amount').attr("required", false);
        $('#incoming_transfer_charge_percentage').attr("required", true);
        $('#incoming_transfer_charge_capped_at').attr("required", true);

    } else {

        $('#incoming_transfer_charge_amount').attr("required", true);
        $('#incoming_transfer_charge_percentage').attr("required", false);
        $('#incoming_transfer_charge_capped_at').attr("required", false);

        $('#incomingTransferChargeDiv').show();
        $('#incomingTransferCapDiv').hide();
        $('#incomingTransferChargePercentageDiv').hide();
    }
}

$('#who_to_pay_incoming_transfer_charge').change(function() {

    if ($(this).val() == 0) {

        $('#incomingTransferChargeDiv').hide();
        $('#incomingTransferCapDiv').hide();
        $('#incomingTransferChargePercentageDiv').hide();
        $('#chargeIncomingUsingDiv').hide();

        $('#incoming_transfer_charge_amount').attr("required", false);
        $('#incoming_transfer_charge_percentage').attr("required", false);
        $('#incoming_transfer_charge_capped_at').attr("required", false);
    } else {
        var chargeUsing = $('#incoming_transfer_charge_state').val();

        if (chargeUsing == 1) {
            $('#incomingTransferChargeDiv').hide();
            $('#incomingTransferCapDiv').show();
            $('#chargeIncomingUsingDiv').show();

            $('#incomingTransferChargePercentageDiv').show();

            $('#incoming_transfer_charge_amount').attr("required", false);
            $('#incoming_transfer_charge_percentage').attr("required", true);
            $('#incoming_transfer_charge_capped_at').attr("required", true);

        } else {

            $('#incoming_transfer_charge_amount').attr("required", true);
            $('#incoming_transfer_charge_percentage').attr("required", false);
            $('#incoming_transfer_charge_capped_at').attr("required", false);

            $('#chargeIncomingUsingDiv').show();
            $('#incomingTransferChargeDiv').show();
            $('#incomingTransferCapDiv').hide();
            $('#incomingTransferChargePercentageDiv').hide();
        }
    }

})

$('#incoming_transfer_charge_state').change(function() {
    if ($(this).val() == 1) {
        $('#incomingTransferChargeDiv').hide();
        $('#incomingTransferCapDiv').show();
        $('#incomingTransferChargePercentageDiv').show();
    } else {
        $('#incomingTransferChargeDiv').show();
        $('#incomingTransferCapDiv').hide();
        $('#incomingTransferChargePercentageDiv').hide();
    }
});

$('#withdraw_charge_state').change(function() {
    if ($(this).val() == 1) {
        $('#minWithdrawalDiv').show();
        $('#min_withdraw_label').html('Percentage Charge');
        $("#min_withdraw_charge").attr("placeholder", "Enter Percentage Charge");
        $("#min_withdraw_charge").attr("max", 10);
        $("#min_withdraw_charge").attr("maxlength", 3);
        $('#max_withdraw_label').html('Withdrawal Charge Capped at');
    } else {
        $('#minWithdrawalDiv').hide();
        $('#min_withdraw_label').html('Percentage Charge');
        $("#min_withdraw_charge").attr("placeholder", "Enter Percentage Charge");
        $('#max_withdraw_label').html('Flat Charge Amount');
        $("#max_withdraw_charge").attr("placeholder", "Enter Flat Charge");
    }
});

if ($('#bank_transfer_charge_state').val() == 1) {
    switchBanktransferChargeType('percent');
} else {
    switchBanktransferChargeType('flat');
}

function switchBanktransferChargeType(chargeType) {
    if (chargeType == 'percent') {
        $('#minTransferDiv').show();
        $('#min_withdraw_label').html('Percentage Charge');
        $("#min_bank_transfer_charge_or_percentage").attr("placeholder", "Enter Percentage Charge");
        $("#min_bank_transfer_charge_or_percentage").attr("max", 10);
        $("#min_bank_transfer_charge_or_percentage").attr("maxlength", 3);
        $('#max_bank_transfer_label').html('Transfer Charge Capped at');
    } else {
        $('#minTransferDiv').hide();
        $('#min_withdraw_label').html('Percentage Charge');
        $("#min_bank_transfer_charge_or_percentage").attr("placeholder", "Enter Percentage Charge");
        $('#max_bank_transfer_label').html('Flat Charge Amount');
        $("#max_bank_transfer_charge").attr("placeholder", "Enter Flat Charge");
    }
}

$('#bank_transfer_charge_state').change(function() {
    switchBanktransferChargeType($(this).val() == 1 ? 'percent' : 'flat');
});

$('#min_withdraw_charge').on('keypress', function(e) {
    if ($('#withdraw_charge_state').val() == 1)
        if (e.target.value.length == 3) return false;
})

$('#min_bank_transfer_charge_or_percentage').on('keypress', function(e) {
    if ($('#bank_transfer_charge_state').val() == 1)
        if (e.target.value.length == 3) return false;
})

function onSendNotficationChange() {

    if ($('#send_notification').val() == 1) {
        $('#webhookUrlDiv').show();
    } else {
        $('#webhookUrlDiv').hide();
    }

}

$("#general-info").on('submit', function() {

    $companyname = $('#company_name').val();
    $company_short_name = $('#company_short_name').val();
    $license_no = $('#license_no').val();
    $cac_no = $('#cac_no').val();
    $companycurrency = $('#companycurrency').val();
    $companycountry = $('#companycountry').val();
    $companyaddress = $('#companyaddress').val();

    $('.validation-text').html();


    if ($companyname == "") {
        $_getValidationField[0].innerHTML = 'Company Name must be filled out';
        $_getValidationField[0].style.display = 'block';
        return false;
    } else {
        $_getValidationField[0].style.display = 'none';
    }
    if ($company_short_name == "") {
        $_getValidationField[1].innerHTML = 'Enter Company Short Name';
        $_getValidationField[1].style.display = 'block';
        return false;
    } else {
        $_getValidationField[1].style.display = 'none';
    }
    if ($license_no == "") {
        $_getValidationField[2].innerHTML = 'Enter Company License No';
        $_getValidationField[2].style.display = 'block';
        return false;
    } else {
        $_getValidationField[2].style.display = 'none';
    }
    if ($cac_no == "") {
        $_getValidationField[3].innerHTML = 'Enter Company CAC No';
        $_getValidationField[3].style.display = 'block';
        return false;
    } else {
        $_getValidationField[3].style.display = 'none';
    }
    if ($companycurrency == "") {
        $_getValidationField[4].innerHTML = 'Enter Company Currency';
        $_getValidationField[4].style.display = 'block';
        return false;
    } else {
        $_getValidationField[4].style.display = 'none';
    }
    if ($companycountry == "") {
        $_getValidationField[5].innerHTML = 'Enter Company Country';
        $_getValidationField[5].style.display = 'block';
        return false;
    } else {
        $_getValidationField[5].style.display = 'none';
    }
    if ($companyaddress == "") {
        $_getValidationField[6].innerHTML = 'Enter Company Address';
        $_getValidationField[6].style.display = 'block';
        return false;
    } else {
        $_getValidationField[6].style.display = 'none';
    }
    var form = $(this);
    ajax_operation('post', 'save', form.serialize());
    $.blockUI({
        message: $('.blockui-growl-message'),
        fadeIn: 700,
        fadeOut: 700,
        timeout: 3000, //unblock after 3 seconds
        showOverlay: false,
        centerY: false,
        css: {
            width: '250px',
            backgroundColor: 'transparent',
            top: '12px',
            left: 'auto',
            right: '15px',
            border: 0,
            opacity: .95,
            zIndex: 1200,
        }
    });
    return false;
});

function generateAPIKey() {

    var companyReference = $('#company_reference').val();
    var baseUrl = $('#base_url').val();
    console.log("HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH");
    console.log(baseUrl);

    data_info = {
        company_reference: companyReference
    }

    $.ajax({
        url: baseUrl + '/company/settings/generate-api-key',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {

            if (data.status) {

                $('#api_key').val(data.data.api_key);

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em'
                })

                console.log(data);
            }
        },
        error: function(jqXHR, status, error) {
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
        xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            xhr.onprogress = function e() {
                // For downloads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                    $(".toast-msg").html(e.loaded / e.total);
                }
            };
            xhr.upload.onprogress = function(e) {
                // For uploads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                }
            };
            return xhr;
        }
    });
}

function copyApiKey() {

    $('.toast').addClass("bg-success");
    $('.toast-msg').html("Api Key Copied Successfully");
    $('.toast').toast('show');

}

function copyCompanyReference() {

    $('.toast').addClass("bg-success");
    $('.toast-msg').html("Company Reference Copied Successfully");
    $('.toast').toast('show');

}

function saveDeveloperSettings() {

    var companyReference = $('#company_reference').val();
    var baseUrl = $('#base_url').val();
    var apiKey = $('#api_key').val();
    var send_notification = $('#send_notification').val();
    var webhook_url = $('#webhook_url').val();


    if (send_notification == 1 && apiKey == "") {
        $_getValidationField[7].innerHTML = 'Api Key is required';
        $_getValidationField[7].style.display = 'block';
        return false;
    } else {
        $_getValidationField[7].style.display = 'none';
    }

    if (send_notification == 1 && webhook_url == "") {
        $_getValidationField[8].innerHTML = 'Webhook url is required';
        $_getValidationField[8].style.display = 'block';
        return false;
    } else {
        $_getValidationField[8].style.display = 'none';
    }

    data_info = {
        company_reference: companyReference,
        send_notification: send_notification,
        api_key: apiKey,
        webhook_url: webhook_url
    }

    console.log(data_info);

    $.ajax({
        url: baseUrl + '/company/settings/save-developer-settings',
        type: 'post',
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {

            if (data.status) {

                swal({
                    title: 'Success',
                    text: data.message,
                    type: 'success',
                    padding: '2em'
                })

                console.log(data);
            } else {
                swal({
                    title: 'Error',
                    text: data.message,
                    type: 'error',
                    padding: '2em'
                })
            }
        },
        error: function(jqXHR, status, error) {
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
        xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            xhr.onprogress = function e() {
                // For downloads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                    $(".toast-msg").html(e.loaded / e.total);
                }
            };
            xhr.upload.onprogress = function(e) {
                // For uploads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                }
            };
            return xhr;
        }
    });
}


//im using this
$(".general-info").on('submit', function() {
    $.blockUI({
        message: $('.blockui-growl-message'),
        fadeIn: 700,
        fadeOut: 700,
        timeout: 3000, //unblock after 3 seconds
        showOverlay: false,
        centerY: false,
        css: {
            width: '250px',
            backgroundColor: 'transparent',
            top: '12px',
            left: 'auto',
            right: '15px',
            border: 0,
            opacity: .95,
            zIndex: 1200,
        }
    });
    $(".toast-msg").html('');
    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: 'save',
        data: new FormData(this),
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: (data) => {
            $('.toast').removeClass("bg-success");
            $('.toast').removeClass("bg-danger");

            if (data.status == 1) {
                data.status = 'bg-success'

                data.status = 'bg-success';
                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            } else if (data.status == 0) {
                data.status = 'bg-danger';
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            }
            $('.toast').addClass(data.status);
            $('.token').val(data.token);
            $(".toast-msg").html(data.message);
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
    }).done(function() {
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');
    return false;
});

function save_sms_settings() {
    $.blockUI({
        message: $('.blockui-growl-message'),
        fadeIn: 700,
        fadeOut: 700,
        timeout: 3000, //unblock after 3 seconds
        showOverlay: false,
        centerY: false,
        css: {
            width: '250px',
            backgroundColor: 'transparent',
            top: '12px',
            left: 'auto',
            right: '15px',
            border: 0,
            opacity: .95,
            zIndex: 1200,
        }
    });

    $smsphone = $('#smsphone').val();
    $smsname = $('#smsname').val();
    $smsmessage = $('#smsmessage').val();
    $smsapiurl = $('#smsapiurl').val();
    $current_smsid = $('#current_smsid').val();
    $request_type = $('#request_type').val();
    $sender_variable = $('#sender_variable').val();

    $content_type = $('#content_type').val();
    $authorization = $('#authorization').val();
    $.ajax({
        url: '/company/settings/ajax-update-sms',
        type: 'post',
        data: { authorization: $authorization, content_type: $content_type, current_smsid: $current_smsid, request_type: $request_type, smsapiurl: $smsapiurl, smsmessage: $smsmessage, smsname: $smsname, smsphone: $smsphone, sender_variable: $sender_variable },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('.toast').removeClass("bg-success");
            $('.toast').removeClass("bg-danger");
            $(".toast-msg").html(data.message);
            if (data.status == 1) {
                data.status = 'bg-success';
                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
                viewallowedsms();
            } else if (data.status == 0) {
                data.status = 'bg-danger';
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            }
            $('.toast').addClass(data.status);
            $('.token').val(data.token);

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
            $status = 'error';
            $complete = 'Error!';
            swal(
                $complete,
                msg,
                $status
            );
        },
        xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            xhr.onprogress = function e() {
                // For downloads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                    $(".toast-msg").html(e.loaded / e.total);
                }
            };
            xhr.upload.onprogress = function(e) {
                // For uploads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                }
            };
            return xhr;
        }
    }).done(function() {
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');
}

function viewallowedsms() {
    $('.allowedsms').empty();
    $.ajax({
        url: '/company/settings/allowed/sms',
        type: 'GET',
        dataType: 'json',
        success: function(data) {

            var apidata = data.api;
            var activesms = data.activesms;
            $('#allsmsstate').prop('checked', data.allsms);
            apidata.forEach(function(allowedsms) {
                console.log(allowedsms);
                $active = '';
                if (activesms == allowedsms.id) {
                    $active = 'checked';
                }

                $('.allowedsms').append('<div class="col-sm-6 col-md-3"><label class="btn btn-dark" for="option' + allowedsms.id + '">' + allowedsms.name + ' <input onchange="changesms(' + allowedsms.id + ')" type="radio" class="btn-check" name="options" id="option' + allowedsms.id + '" autocomplete="off" value="' + allowedsms.id + '" ' + $active + ' /></label></div>');

            });

        }
    });

}
viewallowedsms();

function changesms(val) {

    ajax_operation('post', 'ajax-change-sms', { id: val });
}

$('#allsmsstate').change(function() {
    $url = '';
    if ($('#allsmsstate').is(':checked')) {
        $url = 1;
    } else {
        $url = 0;
    }
    $.ajax({
        url: 'update_all-sms-state/' + $url,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {

            if (data.status) {
                if (data.message == true && $('#allsmsstate').is(':checked') == true) {
                    $('#allsmsstate').prop('checked', true);
                } else if (data.message == false && $('#allsmsstate').is(':checked') == false) {
                    $('#allsmsstate').prop('checked', false);
                } else {
                    $('#allsmsstate').prop('checked', false);
                }
            } else {
                // if ($('#allsmsstate').is(':checked')) {

                //     $('#allsmsstate').prop('checked', 0);
                // } else {

                //     $('#allsmsstate').prop('checked', 1);
                // }
            }

        }
    });
});

function saveDomain() {
    // alert()
    $domain = $('#domain_name').val();
    $domainType = $('#domain_type').val();
    ajax_operation('post', '/company/setting/save-domain', { subdomain: $domain, domain_type: $domainType }, 'saveDomain', 'Save');
}

function settlementForm() {
    $.blockUI({
        message: $('.blockui-growl-message'),
        fadeIn: 700,
        fadeOut: 700,
        timeout: 3000, //unblock after 3 seconds
        showOverlay: false,
        centerY: false,
        css: {
            width: '250px',
            backgroundColor: 'transparent',
            top: '12px',
            left: 'auto',
            right: '15px',
            border: 0,
            opacity: .95,
            zIndex: 1200,
        }
    });
    $(".toast-msg").html('');
    let minimum_balance_threshold = $('#minimum_balance_threshold').val();
    let maximum_balance = $('#maximum_balance').val()
    let accountNumber = $('#accountNumber').val()
    let bank = $('#bank').val()
    let pin = $('#pin').val()
    var accountName = $('#accountName').val();
    let object = { accountName: accountName, pin: pin, minimum_balance_threshold: minimum_balance_threshold, maximum_balance: maximum_balance, accountNumber: accountNumber, bank: bank }
    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/settings/settlement',
        data: object,
        dataType: 'json',
        success: (data) => {
            $('.toast').removeClass("bg-success");
            $('.toast').removeClass("bg-danger");

            if (data.status == 1) {
                data.status = 'bg-success'

                data.status = 'bg-success';
                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            } else if (data.status == 0) {
                data.status = 'bg-danger';
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            }
            $('.toast').addClass(data.status);
            $('.token').val(data.token);
            $(".toast-msg").html(data.message);
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
    }).done(function() {
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');
    return false;
};
onSendNotficationChange();

function verifyBankAccount() {
    $('.btnsettlement').attr("disabled", "disabled");
    $('.btnsettlement').css("opacity", ".5");
    var transferBankCode = $('#bank').find('option:selected').attr('code');
    var transferAccountNumber = $('#accountNumber').val();


    if (transferAccountNumber.length == 10 && transferBankCode != "") {
        $("#myBankLoader").show();
        $("#myBankLoader").html("Loading...");

        $('#transferAccountName').val("loading...");
        data_info = {
            "bankCode": transferBankCode,
            "accountNumber": transferAccountNumber,
        }
        $('#accountName').val('loading...')

        $.ajax({
            url: '/company/wba/transaction/verify-bank-account',
            type: 'post',
            data: data_info,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                if (data.status == 1) {
                    $("#myBankLoader").html(data.data.account_name);
                    $('.btnsettlement').css("opacity", "");
                    $('.btnsettlement').removeAttr("disabled");
                    $('#accountName').val(data.data.account_name)
                } else {
                    $("#myBankLoader").html(data.message);
                }

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
            },
            xhr: function() {
                var xhr = $.ajaxSettings.xhr();
                xhr.onprogress = function e() {
                    // For downloads
                    if (e.lengthComputable) {
                        console.log(e.loaded / e.total);
                        $(".toast-msg").html(e.loaded / e.total);
                    }
                };
                xhr.upload.onprogress = function(e) {
                    // For uploads
                    if (e.lengthComputable) {
                        console.log(e.loaded / e.total);
                    }
                };
                return xhr;
            }
        }).done(function() {
            // $('.toast').addClass("bg-success");
        });
    }
}

function settingForm() {
    $.blockUI({
        message: $('.blockui-growl-message'),
        fadeIn: 700,
        fadeOut: 700,
        timeout: 3000, //unblock after 3 seconds
        showOverlay: false,
        centerY: false,
        css: {
            width: '250px',
            backgroundColor: 'transparent',
            top: '12px',
            left: 'auto',
            right: '15px',
            border: 0,
            opacity: .95,
            zIndex: 1200,
        }
    });
    $(".toast-msg").html('');
    let authfactorsetup = $('#authfactorsetup').val();
    let object = { authfactorsetup: authfactorsetup }
    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/settings/security',
        data: object,
        dataType: 'json',
        success: (data) => {
            $('.toast').removeClass("bg-success");
            $('.toast').removeClass("bg-danger");

            if (data.status == 1) {
                data.status = 'bg-success'

                data.status = 'bg-success';
                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            } else if (data.status == 0) {
                data.status = 'bg-danger';
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            }
            $('.toast').addClass(data.status);
            $('.token').val(data.token);
            $(".toast-msg").html(data.message);
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
    }).done(function() {
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');
    return false;
};
// /settings/security
function saveRemitta() {
    $apikey = $('#apikey').val()
    $apitoken = $('#apitoken').val()
    $machant = $('#machant').val()

    if ($('#defaultCheck').prop('checked')) {
        // Checkbox is checked
        $defaultCheck = 1
    } else {
        // Checkbox is not checked
        $defaultCheck = 0
    }
    ajax_operation('post', '/settings/remitta', { apikey: $apikey, apitoken: $apitoken, machant: $machant, defaultCheck: $defaultCheck });
}
// $('#activateRemitta').on('change', function() {
//     if ($(this).is(':checked')) {
//       // The checkbox is checked
//       console.log('The checkbox is checked');
//     $resp = ajax_operation('post', '/settings/remitta-activate', {status:1},'activateRemitta','activatingremitta');
//     showRemittaStatus(1)
//     } else {
//       // The checkbox is not checked
//       console.log('The checkbox is not checked');
//     ajax_operation('post', '/settings/remitta-activate', {status:0},'activateRemitta','activatingremitta');
//     showRemittaStatus(0)
//     }
//   });
function getRemittaSettings() {
    $.ajax({
        type: 'get',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/settings/remitta',
        dataType: 'json',
        success: (data) => {
            $('#apikey').val(data.apiKey)
            $('#apitoken').val(data.apiToken)
            $('#machant').val(data.machant)
            $('#defaultCheck').val(data.ownership).prop('checked', data.ownership);
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
    }).done(function() {
        $('.toast').addClass("bg-success");
    });

}

function showRemittaStatus(status) {
    if (status == 1) {
        $('.remittastatus').show()
        getRemittaSettings();
    } else {
        $('.remittastatus').hide()
    }
}

function saveTransactionSettings() {
    const view_vault_on_txn = $('#view_vault_on_txn').val()
    const hide_charge_on_statement = $('#hide_charge_on_statement').val()
    const view_instrument_number = $('#view_instrument_number').val()
    const customer_can_transfer = $('#customer_can_transfer').val()

    ajax_operation('post', '/company/settings/transaction', {
        'view_vault_on_txn': view_vault_on_txn,
        'hide_charge_on_statement': hide_charge_on_statement,
        'view_instrument_number': view_instrument_number,
        'customer_can_transfer': customer_can_transfer
    });
}

function saveEmail() {
    ajax_operation('post', '/settings/update-settings-mail', {
        'mail_host_port': $('#mail_host_port').val(),
        'mail_host': $('#mail_host').val(),
        'username_password': $('#username_password').val(),
        'mail_from': $('#mail_from').val(),
        'username_email': $('#username_email').val(),
        'mail_encryption': $('#mail_encryption').val(),

    });
}

function saveReferal() {
    ajax_operation('post', '/settings/update-settings-referral', {
        'direct_comission': $('#direct_comission').val(),
        'credit_method': $('#credit_method').val(),
        'indirect_comission': $('#indirect_comission').val(),
    });
}

function sendTest() {
    ajax_operation('post', '/settings/email_test', {
        'test_message': $('#test_message').val(),
        'test_to_email': $('#test_to_email').val(),
        'test_subject': $('#test_subject').val(),
    }, 'testMail', 'Send Again');
}

function returnAllBranchAndStaff() {
    $.ajax({
        type: 'get',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/company/returnAllBranchAndStaff',
        dataType: 'json',
        success: (data) => {
            data.branch.map((branchData) => {
                $('#sourceBranch').append("<option value='" + branchData.id + "'>" + branchData.branch_name + "</option>")
            })
            data.branch.map((branchData) => {
                $('#destinationBranch').append("<option value='" + branchData.id + "'>" + branchData.branch_name + "</option>")
            })
            data.staff.map((staff) => {
                $('#sourceStaff').append("<option value='" + staff.uuid + "'>" + staff.username + "</option>")
            })
            data.staff.map((staff) => {
                $('#destinationStaff').append("<option value='" + staff.uuid + "'>" + staff.username + "</option>")
            })
        },
    })
}
returnAllBranchAndStaff();

function transferStaff() {
    ajax_operation('post', '/settings/email_test', {
        'test_message': $('#test_message').val(),
        'test_to_email': $('#test_to_email').val(),
    }, 'testMail', 'Send Again');
}

function transferBranch() {

}
$('#companyemail_active').change(function() {
    $.ajax({
        url: '/company/settings/change-custom-email-state',
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {

            if (data.status) {
                if (data.message == 1) {
                    $('#companyemail_active').prop('checked', true);
                } else if (data.message == false) {
                    $('#companyemail_active').prop('checked', false);
                } else {
                    $('#companyemail_active').prop('checked', false);
                }
            }

        }
    });
});