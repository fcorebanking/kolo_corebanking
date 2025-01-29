function saveSettings(){
    var formData = $('#add-loan-settings').serialize();
    disableBtn('loanNextBTN');
    startLoadingButton('loanNextBTN');
    $url = '/company/loan/app-setting';
    $.ajax({
        url: $url,
        type: 'post',
        data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {
                $status = 'success';
                $complete = 'Success';
                swal(
                    $complete,
                    data.message,
                    $status
                );
                $('#html5-extension').DataTable().ajax.reload();

            } else {
                $status = 'error';
                $complete = "Error!";
                $message = data.message;
                swal(
                    $complete,
                    $message,
                    $status
                );
            }
            stopLoadingButton('loanNextBTN', 'Process');
            enableBtn('loanNextBTN');

        }
    });
    return false;
}
function UpdatesaveSettings(){
    var formData = $('#add-loan-settings').serialize();
    disableBtn('loanNextBTN');
    startLoadingButton('loanNextBTN');
    $url = '/company/loan/update-app-setting';
    $.ajax({
        url: $url,
        type: 'post',
        data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {
                $status = 'success';
                $complete = 'Success';
                swal(
                    $complete,
                    data.message,
                    $status
                );
                $('#html5-extension').DataTable().ajax.reload();

            } else {
                $status = 'error';
                $complete = "Error!";
                $message = data.message;
                swal(
                    $complete,
                    $message,
                    $status
                );
            }
            stopLoadingButton('loanNextBTN', 'Process');
            enableBtn('loanNextBTN');

        }
    });
    return false;
}
function permanent_delete(id){
    ajax_operation('delete', '/company/loan/delete-app-setting/'+id, {});
    $('#html5-extension').DataTable().ajax.reload();

}
function addLoanSettings(){
    $('#exampleModal').modal('show');
    $('#add-loan-settings')[0].reset();
    $('#setting_id').val(0);
    $('#updateloanBTN').hide();
    $('#loanBTN').show();

    // $('#add-loan-settings').trigger("reset");

}
function editSettings($id){
    $('#loanBTN').hide();
    $('#updateloanBTN').show();

    $.ajax({
        url: '/company/loan/edit-app-setting/'+$id,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(res) {
            var data= res.data;
            if (res.status == 1) {
                $('#loan_type').val(data.loan_type).change();
                $('#DurationPeriod').val(data.durationPeriod).change();
                $('#Duration').val(data.duration);
                $('#setting_id').val(data.ref);
                $('#minamt').val(data.minamt);
                $('#maxamt').val(data.maxamt);
                $('#NumOngoinAllowed').val(data.NumOngoinAllowed);
                $('#remark').val(data.remark);
                
                $("#loan_products option").filter(function() {
                    return $(this).text() == data.loan_product_id;
                }).prop("selected", true);
                $("#freq option").filter(function() {
                    return $(this).text() == data.repayment;
                }).prop("selected", true);
                $('#transactionMin').val(data.transactionMin);
                $('#transactionMax').val(data.transactionMax);
                $('#minWalletBalance').val(data.minWalletBalance);
                $('#maxWalletBalance').val(data.maxWalletBalance);
                $('#exampleModal').modal('show');
            } else {
                $status = 'error';
                $complete = res.message;
                $message = res.message;
                swal(
                    $complete,
                    $message,
                    $status
                );
            }
            stopLoadingButton('loanNextBTN', 'Process');
            enableBtn('loanNextBTN');

        }
    });
}