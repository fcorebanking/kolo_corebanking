function AllbalanceInfo($uid) {

    $.ajax({
        url: 'all-balance-info/' + $uid,
        type: 'post',

        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            console.log(data);
            $('.credit').html(data.credit);
            $('.debit').html(data.debit);
            $('.investment').html(data.investment);
            $('.wallet').html(data.balance);

        }
    });

}

$('#replace_account').on('change', function (e) {
    if (e.target.value == "yes") $('#addWalletBtn').text("Replace Account")
    else $('#addWalletBtn').text("Add New")
})

function pop_multiwallet() {
    view_wallet();
    $('#multiplemodal').modal('show');
}

$('#saving_type').on('change', function (e) {

    if (e.target.value == 'virtual')
        $('#bankSelectDiv').show();
    else
        $('#bankSelectDiv').hide();

})
$('#bankSelectDiv').hide();

function printpage() {
    //Get the print button and put it into a variable
    var printButton = document.getElementById("printinvoice");
    //Set the print button visibility to 'hidden'
    printButton.style.visibility = 'hidden';
    //Print the page content
    window.print()
    //Set the print button to 'visible' again
    //[Delete this line if you want it to stay hidden after printing]
    printButton.style.visibility = 'visible';
}

function view_wallet() {
    $.ajax({
        url: '/company/get-savings-type',
        type: 'GET',
        dataType: 'json',
        success: function (data) {

            // $("#saving_type").append("<option value='virtual'>Virtual Account</option>")

            data.forEach(function (account) {
                $("#savings_product_type").append("<option value='" + account.id + "'>" + account.savings_product + "</option>")

            });

        }
    });
}

function view_accounts($id) {
    $.ajax({
        url: '/company/get-my-savings-products/' + $id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {

            $("#accountlist").empty();

            data.forEach(function (account) {

                $("#accountlist").append("<div class='col-sm-6 col-md-4 px-md-5'><button class='btn  custom_active' onclick='selected_account(" + account.id + ")'>" + account.savings_product + " - " + account.bank_name + " (" + account.accno + ")</button ><br><button class='btn btn-info' onclick='showRreplaceBankModal(" + account.id + ")'>Replace Bank</button></div> ")

            });
            $("#accountlist").append("<div class='col-sm-6 col-md-4 px-md-5'><button class='btn  custom_active' onclick='displayall_account_info()'> Display All</button></div>")

            //
            activereset();
        }

    });
}


function showRreplaceBankModal(accountId) {
    console.log(accountId);
    $("#account_id").val(accountId);
    $('#replaceBankModal').modal('show');
}


function replaceBank() {
    var accountId = $('#account_id').val();
    var uid = $('#userid').val();

    startLoadingButton("replaceBankBtn");

    $.ajax({
        url: '/company/replace-bank/' + accountId,
        type: 'GET',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        dataType: 'json',
        success: function (data) {

            stopLoadingButton("replaceBankBtn", "Replace Bank")

            if (data.status == 1) {
                $status = 'success';
                $complete = 'Done';
                swal(
                    $complete,
                    data.message,
                    $status
                )


                $('#replaceBankModal').modal('hide');

                view_accounts($uid);
            } else {

                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            }

        }
    });
}

function addwallet() {
    var productid = $('#saving_type').val();
    var uid = $('#userid').val();
    var bankid = $('#account_bank').val();
    var replace_account = $('#replace_account').val();
    var currency_id = $('#currency_id').val();
    var savings_product_type = $('#savings_product_type').val();

    startLoadingButton("addWalletBtn");

    $.ajax({
        url: '/company/save-savings-type',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        dataType: 'json',
        data: { saving_type: productid, savings_product_type: savings_product_type, uuid: uid, bankcode: bankid, replace_account, currency_id },
        success: function (data) {
            let replaceVar = $('#replace_account').val() == 'yes' ? "Replace Account" : "Add New"

            stopLoadingButton("addWalletBtn", replaceVar)

            if (data.status == 1) {
                $status = 'success';
                $complete = 'Done';
                swal(
                    $complete,
                    data.message,
                    $status
                )
                view_accounts($uid);
            } else {

                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            }

        }
    });
}

function selected_account(id) {

    $('#selectedaccountid').val(id)
    $('#html5-extension').DataTable().ajax.url('/company/fetch-statement-single/' + id).load();
    $investaccturl = $('#origininvestmenturl').val();
    $('#investmenturl').val($investaccturl + '/' + id);
    $.ajax({
        url: 'account-balance-info/' + id,
        type: 'post',

        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            console.log(data);
            $('.credit').html(data.credit);
            $('.debit').html(data.debit);
            $('.investment').html(data.investment);
            $('.wallet').html(data.balance);
            $('#virtual_account').val(data.virtual_account);

        }
    });


}

function viewinvestment() {
    if ($('#investmenturl').val() != '#') {
        window.location = $('#investmenturl').val();
    } else {
        alert("Select Account from Account List");
    }
}

function activereset() {
    $(".custom_active").click(function () {

        $(".custom_active").removeClass("btn-info");
        $(this).addClass("btn-info");
    });
}
setTimeout(activereset, 3000);

function searchrange() {
    $to = $('#to').val();
    $from = $('#from').val();
    //userid
    var id = $('#userid').val();
    $links = '/company/fetch-statement/';
    if ($('#selectedaccountid').val() != '') {
        id = $('#selectedaccountid').val();
        $links = '/company/fetch-statement-single/';
    }
    $('#html5-extension').DataTable().ajax.url($links + id + '?from=' + $from + '&to=' + $to).load();

}

function displayall_account_info() {
    var id = $('#userid').val();

    AllbalanceInfo(id);

    view_accounts(id);

    $('#html5-extension').DataTable().ajax.url("/company/fetch-statement/" + id).load();

    $('#selectedaccountid').empty();
    $('#investmenturl').val('#');

}

function searchdownload() {
    $multAccount = $('#virtual_account').val();
    if ($multAccount.length == 0) {
        alert('Select a wallet')
        return false;
    }
    $to = $('#to').val();
    $from = $('#from').val();
    window.location = "http://safe.wbalite.com/vas/api/statement/" + $multAccount + "/" + $from + "/" + $to + ""
}