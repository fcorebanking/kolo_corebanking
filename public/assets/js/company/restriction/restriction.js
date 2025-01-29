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
        url: '/restrictUser',
        type: 'put',
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
        url: "/company/all-restriction",
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