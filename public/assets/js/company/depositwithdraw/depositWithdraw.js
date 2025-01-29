$('.bankdetails').hide();

$(document).ready(function() {
    $debitcredit = "debitcredit";
    $('#html5-extension').DataTable({
        "serverSide": true,
        "ajax": {
            url: "/userDataSource/" + $debitcredit,
            method: "get"
        },
        "columnDefs": [{
            'targets': [5],
            'orderable': false
        }],


        dom: '<"row"<"col-md-12"<"row"<"col-md-6"B><"col-md-6"f> > ><"col-md-12"rt> <"col-md-12"<"row"<"col-md-5"i><"col-md-7"p>>> >',
        buttons: {
            buttons: [{
                extend: 'copy',
                className: 'btn'
            }, {
                extend: 'csv',
                className: 'btn'
            }, {
                extend: 'excel',
                className: 'btn'
            }, {
                extend: 'print',
                className: 'btn'
            }]
        },
        "oLanguage": {
            "oPaginate": {
                "sPrevious": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
                "sNext": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>'
            },
            "sInfo": "Showing page _PAGE_ of _PAGES_",
            "sSearch": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
            "sSearchPlaceholder": "Search...",
            "sLengthMenu": "Results :  _MENU_",
        },
        "stripeClasses": [],
        "lengthMenu": [7, 10, 20, 50],
        "pageLength": 7
    });
});

function credit($uid) {
    $('.verify_Restriction').hide();
    $("#submit_form")[0].reset();
    $('#wcharge').hide();
    $('#formtype').val('credit');
    $('#uuid').val($uid);
    $('#exampleModalLabel').html('Credit');
    $("#exampleModal").modal('show');
    $("#transaction_type option[value='7']").remove();
    $("#transaction_type option[value='93']").remove();
    $("#transaction_type option[value='30']").remove();
    $("#transaction_type option[value='81']").remove();
    $("#transaction_type").empty();
    $("#transaction_type").append("<option value='1'>Cash Deposit</option>");
    $("#transaction_type").append("<option value='94'>Local Bank Transfer Deposit</option>");
    $("#transaction_type").append("<option value='78'>Cheque Payment</option>");
    $("#transaction_type").append("<option value='39'>Withdrawal Charges</option>");
    $("#transaction_type").append("<option value='30'>Registration Charges</option>");
    $('.bankdetails').hide();

    fetchproduct($uid)
}

function debit($uid) {
    $('.verify_Restriction').hide();
    $("#submit_form")[0].reset();
    $('#formtype').val('debit');
    $('#uuid').val($uid);
    $('#wcharge').show();
    $('#exampleModalLabel').html('Debit');
    $("#exampleModal").modal('show');
    $("#transaction_type").empty();
    $("#transaction_type").append("<option value='7'>Cash Withdrawal</option>");
    $("#transaction_type").append("<option value='93'>Local Wallet to Bank Transfer</option>");
    $("#transaction_type").append("<option value='30'>Customer Registration Fee</option>");
    $("#transaction_type").append("<option value='81'>Cheque Withdrawal</option>");
    $("#transaction_type").append("<option value='39'>Withdrawal Charges</option>");
    $("#transaction_type").append("<option value='30'>Registration Charges</option>");
    $("#transaction_type option[value='1']").remove();
    $("#transaction_type option[value='94']").remove();
    $("#transaction_type option[value='78']").remove();
    $('.bankdetails').hide();
    fetchproduct($uid)
}
var $_getValidationField = document.getElementsByClassName('validation-text');
$("#submit_form").on('submit', function() {
    $amount = $('#amount').val();
    $total = $('#total-input').val();
    if ($total > 0 && $amount != $total) {
        $status = 'error';
        $complete = 'Error!';
        swal(
            $complete,
            "Your total amount must be equal to input denominator!",
            $status
        );
        return false;
    }
    $initiator = $('#initiator').val();
    $account_type = $('#account_type').val();
    $desc = $('#desc').val();
    $valid = 0;
    if ($amount == "") {
        $valid++;
    } else {}

    if ($account_type == "") {
        $valid++;
    } else {}
    if ($desc == "") {
        $valid++;
    } else {}
    if ($valid > 0) {
        return false;
    }
    var formData = $(this).serialize();
    $url = $("#formtype").val();
    if ($url == "credit") {
        $link = '/company/sendCreditTXN';
    } else if ($url == "debit") {
        $link = '/company/sendDebitTXN';
    }
    disableBtn('savingsbtn')
    startLoadingButton('savingsbtn')
    $.ajax({
        url: $link,
        type: 'post',
        data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {
                $status = 'success';
                $complete = 'Redirecting...';
                swal(
                    $complete,
                    data.message,
                    $status
                );
                if (data.redirectlink) {
                    window.location.href = window.location.origin + '/' + data.redirectlink;
                }
            } else {
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            }
            stopLoadingButton('savingsbtn', 'submit');
            enableBtn('savingsbtn');

        }
    });
    return false;

});

function functionfetchinfo() {
    $searchval = $('#uuid').val();
    $('.verify_Restriction').toggle();
    ViewCustomerStatus($searchval);
}

function onselectType() {
    if ($('#transaction_type').val() == '93')
        $('.bankdetails').show();
    else
        $('.bankdetails').hide();

}


function verifyBankAccount() {
    $('#savingsbtn').attr("disabled", "disabled");
    $('#savingsbtn').css("opacity", ".5");
    var transferBankCode = $('#bank_id').find('option:selected').attr('code');
    var transferAccountNumber = $('#accountNo').val();
    // alert(transferAccountNumber +' - '+transferBankCode);
    $('#transferAccountName').val(transferAccountNumber + ' - ' + transferBankCode);
    if (transferAccountNumber.length == 10 && transferBankCode != "") {
        console.log("Verifying account number " + transferAccountNumber);
        $("#myBankLoader").show();
        $('#transferAccountName').val("loading...");
        data_info = {
            "bankCode": transferBankCode,
            "accountNumber": transferAccountNumber,
        }
        $.ajax({
            url: '/company/wba/transaction/verify-bank-account',
            type: 'post',
            data: data_info,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                console.log(data);
                if (data.status == 1) {
                    var newAccountNumber = $('#accountNo').val();
                    if (newAccountNumber.length == 10) {
                        $('#transferAccountName').val(data.data.account_name);
                    } else {
                        $('#transferAccountName').val("");
                    }
                } else {
                    console.log(data);
                    $('#transferAccountName').val("");
                }
                $("#myBankLoader").hide();
                $('#savingsbtn').css("opacity", "");
                $("#savingsbtn").removeAttr("disabled");
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
    } else {
        $('#accountName').val('');
        $("#myBankLoader").hide();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for each quantity input
    $('.db_data').hide()

    function updateAmount(rowId) {
        const quantityInput = document.getElementById(`${rowId}-quantity`);
        const amountInput = document.getElementById(`${rowId}`);
        let rate = 0;
        if (rowId === 'ten-thousand') {
            rate = 10000
        } else if (rowId === 'five-thousand') {
            rate = 5000
        } else if (rowId === 'two-thousand') {
            rate = 2000
        } else if (rowId === 'one-thousand') {
            rate = 1000
        } else if (rowId === 'five-hundred-note') {
            rate = 500
        } else if (rowId === 'five-hundred-coin') {
            rate = 500
        } else if (rowId === 'one-hundred') {
            rate = 100
        } else if (rowId === 'fifthy') {
            rate = 50
        } else if (rowId === 'twenty-five') {
            rate = 25
        } else if (rowId === 'ten') {
            rate = 10
        } else if (rowId === 'five') {
            rate = 5
        } else if (rowId === 'two') {
            rate = 2
        } else if (rowId === 'one') {
            rate = 1
        }
        quantityInput.addEventListener('input', function() {
            const quantity = parseInt(this.value) || 0;
            const amount = rate * quantity; // Replace 10000 with the appropriate amount for each row
            amountInput.value = amount;
            sumInput();
        });
    }

    function sumInput() {
        const tenthousand = Number($('#ten-thousand').val())
        const fivethousand = Number($('#five-thousand').val())
        const twothousand = Number($('#two-thousand').val())
        const onethousand = Number($('#one-thousand').val())
        const fivehundrednote = Number($('#five-hundred-note').val())
        const onehundred = Number($('#one-hundred').val())
        const fivehundredcoin = Number($('#five-hundred-coin').val())
        const fifthy = Number($('#fifthy').val())
        const twothundred = Number($('#twenty-five').val())
        const ten = Number($('#ten').val())
        const five = Number($('#five').val())
        const two = Number($('#two').val())
        const one = Number($('#one').val())
        const total = document.getElementById(`total-input`);
        const coin = fivehundredcoin / 100;
        total.value = (tenthousand + fivethousand + twothousand + onethousand + fivehundrednote + onehundred + fifthy + twothundred + ten + five + two + one + coin)
    }
    // Add event listeners for each row
    updateAmount('ten-thousand');
    updateAmount('five-thousand');
    updateAmount('two-thousand');
    updateAmount('one-thousand');
    updateAmount('five-hundred-note');
    updateAmount('five-hundred-coin');
    updateAmount('one-hundred');
    updateAmount('fifthy');
    updateAmount('twenty-five');
    updateAmount('five');
    updateAmount('ten');
    updateAmount('two');
    updateAmount('one');
    // Add similar calls for other rows

});