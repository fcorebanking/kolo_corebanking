function viewLoan(uuid){
    $('#loanID').val(uuid);
    $.ajax({
        url:'/company/loan/view-loan-details/'+uuid,
        type: 'post',
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success:function(data){
            // loan_type
            $('#remark').val(data.remark);
            $('.sessions').html("<strong><p>Created By:"+data.created_by+"</p><p>Modified By: "+data.modified_by+"</p></strong>");
        }
    });
    $('#exampleModal').modal('toggle');
}
function PerforAction(){
    var formData = $('#loan-reject').serialize();
    ajax_operation('post', '/company/loan/rejectedAction', formData,'updateLoanStatus','Submit');
    $('#html5-extension').DataTable().ajax.reload();
    $('#exampleModal').modal('toggle');
    $('#loan-reject')[0].reset();
}