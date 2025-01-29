function switch_view(status, uuid) {
    if (status == 1) {
        $('#html5-extension').DataTable().ajax.url('/company/view-investment/active/' + uuid).load();
    } else if (status == 2) {
        $('#html5-extension').DataTable().ajax.url('/company/view-investment/terminated/' + uuid).load();
    } else {
        $('#html5-extension').DataTable().ajax.url('/company/view-investment/complete/' + uuid).load();
    }
    $('#html5-extension').DataTable().ajax.url($page).load();
}

function invest($uid, $pen) {

    $('#exampleModalLabel').html('Select Investment');

    swal({
        title: 'Are you sure?',
        text: "Do you want to terminate this Investment? please note " + $pen + "% wil be decucted from your interest!!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        padding: '2em'
    }).then(function(result) {
        if (result.value) {
            $.ajax({
                url: '/company/investmentstatus/' + $uid,
                type: 'post',

                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(data) {
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

        }
    })



}

var $_getValidationField = document.getElementsByClassName('validation-text');
$("#submit_form").on('submit', function() {
    $amount = $('#amount').val();

    $valid = 0;
    if ($amount == "") {
        $_getValidationField[0].innerHTML = ' Enter Amount';
        $_getValidationField[0].style.display = 'block';
        $valid++;
    } else {
        $_getValidationField[0].style.display = 'none';
    }

    if ($valid > 0) {
        return false;
    }
    var formData = $(this).serialize();


    $.ajax({
        url: '/company/makeinvestment/',
        type: 'put',
        data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {

                window.location.replace(window.location.origin + '/' + data.redirectlink);
            } else {
                alert(data.message);
            }

        }
    });

    return false;
});