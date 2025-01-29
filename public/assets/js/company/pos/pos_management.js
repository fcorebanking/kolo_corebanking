$('#customer').select2({
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

$("#submit_Rform").on('submit', function () {
    var formData = $(this).serialize();

    $.ajax({
        url: 'add',
        type: 'post',
        data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $status = '';
            $complete = '';
            if (data.status) {
                $status = 'success';
                $complete = 'Done!';
                $('#html5-extension').DataTable().ajax.reload();
            } else {
                $status = 'error';
                $complete = 'Error!';
            }
            swal(
                $complete,
                data.message,
                $status
            )
        }
    });

    return false;
});


function creditPOS($uid, $userRef) {

    $('#exampleModal').modal('show');

    hideShowDivs('credit');

    $('#exampleModalLabel').text("Credit POS");
    $('#transaction_type').val('credit');
    $('#customer_reference').val($userRef);
    $('#terminal_serial').val($uid);
}

function debitPOS($uid, $userRef) {

    $('#exampleModal').modal('show');

    hideShowDivs('debit')

    $('#transaction_type').val('debit');
    $('#exampleModalLabel').text("Debit POS");
    $('#customer_reference').val($userRef);
    $('#terminal_serial').val($uid);

}

function hideShowDivs(currentDiv) {

    $('#debitCreditDiv').css("display", currentDiv == 'debit' || currentDiv == 'credit' ? "block" : "none");
    $('#reassignDiv').css("display", currentDiv == 'reassign' ? "block" : "none");
    $('#deactivateActivateDiv').css("display", currentDiv == 'deactivateActivate' ? "block" : "none");

}

function reassign($uid, $userRef) {

    $('#exampleModal').modal('show');
    $('#exampleModalLabel').text("Reassign POS");
    $('#transaction_type').val('reassign');

    hideShowDivs('reassign')

    $('#customer_reference').val($userRef);
    $('#terminal_serial').val($uid);
}

function deactivateActivate($uid, $userRef, currentStatus) {

    console.log("Deactivate clicked");

    $('#exampleModal').modal('show');
    $('#exampleModalLabel').text((currentStatus == 1) ? "Deactivate POS" : "Activate POS")
    $('#deactivationActivationMessage').html(
        currentStatus == 1 ? "Deactivating this POS means that it will no longer be able to process transactions. Are you sure you want to continue?" :
            "Are you sure you want to Activate this POS?")
    $('#transaction_type').val('deactivate_activate');

    $('#pos_status').val(currentStatus)

    hideShowDivs('deactivateActivate')

    $('#customer_reference').val($userRef);
    $('#terminal_serial').val($uid);
}

$("#credit_pos_form").on('submit', function (e) {
    var formData = $(this).serialize();
    e.preventDefault();

    var url = 'credit';

    var trans_type = $('#transaction_type').val();

    if (trans_type == 'debit') {
        url = 'debit';
    } else if (trans_type == 'reassign') {
        url = 'reassign';
    } else if (trans_type == "deactivate_activate") {
        url = 'deactivate-activate';
    }

    $.ajax({
        url: url,
        type: 'post',
        data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {

            $('#exampleModal').modal('hide');

            $status = '';
            $complete = '';
            if (data.status) {
                $("#credit_pos_form").trigger("reset");
                $status = 'success';
                $complete = 'Done!';
                $('#html5-extension').DataTable().ajax.reload();
            } else {
                $status = 'error';
                $complete = 'Error!';
            }
            swal(
                $complete,
                data.message,
                $status
            )
        }
    });
});

function deletefunc($uid, $userRef) {

    swal({
        title: 'Are you sure?',
        text: "You want to Remove Customer Restriction!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        padding: '2em'
    }).then(function (result) {
        if (result.value) {
            $.ajax({
                url: '/removerestriction',
                type: 'post',
                data: { restriction_id: $uid, user_reference: $userRef },
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {
                    $status = '';
                    $complete = '';
                    if (data.status) {
                        $status = 'success';
                        $complete = 'Done!';

                    } else {
                        $status = 'error';
                        $complete = 'Error!';
                    }
                    swal(
                        $complete,
                        data.message,
                        $status
                    );


                }
            });
            $('#html5-extension').DataTable().ajax.reload();
        }
    })



}

$('#html5-extension').DataTable({
    "serverSide": true,
    "ajax": {
        url: "all-pos",
        method: "get"
    },
    "columnDefs": [{

        'orderable': false
    }],

    dom: '<"row"<"col-md-12"<"row"<"col-md-6"B><"col-md-6"f> > ><"col-md-12"rt> <"col-md-12"<"row"<"col-md-5"i><"col-md-7"p>>> >',
    buttons: {
        buttons: [{
            extend: 'copy',
            className: 'btn btn-primary'
        }, {
            extend: 'csv',
            className: 'btn btn-primary'
        }, {
            extend: 'excel',
            className: 'btn btn-primary'
        }, {
            extend: 'print',
            className: 'btn btn-primary'
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