function viewLoan(uuid, useruuid) {
    $('#loanID').val(uuid);
    $('#customerx').val(useruuid);
    $('#exampleModal').modal('toggle');
}

function Disburse() {
    var formData = $('#loan-disturb').serialize();
    ajax_operation('post', '/company/loan/disburseLoan', formData, 'disburseBTN', 'Disburse');
    $('#html5-extension').DataTable().ajax.reload();
    $('#exampleModal').modal('toggle');
    $('#loan-disturb')[0].reset();
    swapDisburse(1);

}

function rendersave() {
    $val = $('#customerx').val();

    $.ajax({
        url: '/userDataSelect/' + $val,
        type: 'get',

        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('#savetype').empty();
            data.forEach(function(dataView) {
                $('#savetype').append('<option value="' + dataView.id + '">' + dataView.text + '</option>');
            })
        }
    });
}

function swapDisburse(val) {
    if (val == 2) {
        $('.savetype_s').show();
        $('.mobilemoney').hide();
        rendersave();
    } else if (val == 4) {
        $('.mobilemoney').show();
        $('.savetype_s').hide();
    } else {
        $('.savetype_s').hide();
        $('.mobilemoney').hide();

    }
}
swapDisburse(1);