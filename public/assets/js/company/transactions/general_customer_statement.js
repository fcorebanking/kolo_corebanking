function searchCustomer() {
    $from = $('#from').val();
    $to = $('#to').val();
    $option = $('#option').val();
    $username = $('#officer').val();
    $branch = $('#branch').val();
    $product = $('#productx').val();
    $links = '/userDataSource/all-customer?all-customer=1' + '&from=' + $from + '&to=' + $to + '&option=' + $option + '&branch=' + $branch + '&officer=' + $username + '&product=' + $product;
    $('#html5-extension').DataTable().ajax.url($links).load();
    $('#html5-extension').DataTable().reload();
}
$('#username').select2({
    placeholder: 'Select staff',
    ajax: {
        url: '/staffDataSelect',
        dataType: 'json',
        delay: 250,
        data: function (data) {
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
$('#type').select2({
    placeholder: 'Select TXN Type',
    ajax: {
        url: '/fetch_txn_type',
        dataType: 'json',
        delay: 250,
        data: function (data) {
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

function pop_multiwallet() {
    view_wallet();
    $('#multiplemodal').modal('show');
}


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
function searchrange() {
    $to = $('#to').val();
    $from = $('#from').val();
    $branch = $('#branch').val();
    $staff = $('select[name="officer"]').val();
    $links = '/company/fetch-reversal-statement';
    $('#html5-extension').DataTable().ajax.url($links + '?from=' + $from + '&to=' + $to + '&staff=' + $staff + '&branch=' + $branch).load();
}
function searchrange_callover() {
    disableBtn('click-button');
    startLoadingButton('click-button');
    $to = $('#to').val();
    $from = $('#from').val();
    $type = $('#type').val();
    $username = $('#officer').val();
    $branch = $('#branch').val();
    $initiator = $('#initiator').val();
    $links = '/company/callover-report-data';
    $('#html5-extension').DataTable().ajax.url($links + '?from=' + $from + '&to=' + $to + '&branch=' + $branch + '&type=' + $type + '&officer=' + $username + '&initiator=' + $initiator).load();
    stopLoadingButton('click-button', 'Filter');
    enableBtn('click-button');
}
function searchrange_endday() {
    $to = $('#to').val();
    $from = $('#from').val();
    $links = '/company/end-of-day-datatable';
    $('#html5-extension').DataTable().ajax.url($links + '?from=' + $from + '&to=' + $to).load();
}
function reverse($url) {
    swal({
        title: 'Warning!!!',
        text: "You want to reverse this transaction?",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        padding: '2em'
    }).then(function (result) {
        if (result.value) {
            ajax_operation('get', '/company/reverse-statement/' + $url, {});
            $links = '/company/fetch-reversal-statement';
            $('#html5-extension').DataTable().ajax.url($links).load();
        }
    });

}
function activate_account(id) {
    swal({
        title: 'Warning!!!',
        text: "Are you sure to activate this account?",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        padding: '2em'
    }).then(function (result) {
        if (result.value) {
            $links = '/company/pending-entry/' + id;
            ajax_operation('post', $links, {});
            $('#html5-extension').DataTable().ajax.url('/userDataSource/pending-entry').load();
        }
    });
}
function reject_account(id) {
    swal({
        title: 'Are you sure you want to reject this account?',
        text: "note: Rejected accounts can be viewed in the recycle section before total delete",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        padding: '2em'
    }).then(function (result) {
        if (result.value) {
            $links = '/company/pending-entry-reject/' + id;
            ajax_operation('post', $links, {});
            $('#html5-extension').DataTable().ajax.url('/userDataSource/pending-entry').load();
        }
    });
}
