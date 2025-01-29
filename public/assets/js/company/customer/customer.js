
function delete_customer(var_ra){
  ajax_operation('post', '/company/customer/trash-single-account/'+var_ra, {});
  $action = $('#page').val();
 $('#html5-extension').DataTable().ajax.url('/userDataSource/'+$action).load();

}
function permanent_delete(var_ra){
  swal({
    title: 'Are you sure you want to delete this User?',
    text: "Note: This is irreversible",
    type: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Proceed',
    padding: '2em'
}).then(function(result) {
    if (result.value) {
      ajax_operation('post', '/company/customer/delete-single-account/'+var_ra, {});
      $action = $('#page').val();
     $('#html5-extension').DataTable().ajax.url('/userDataSource/'+$action).load();
    }
});

}
function restore(var_ra){
  ajax_operation('post', '/company/customer/restore-single-account/'+var_ra, {});
  $action = $('#page').val();
 $('#html5-extension').DataTable().ajax.url('/userDataSource/'+$action).load();
}

$('#action').append("<option value='trash'>Move to Trash</option>");
$("#page").change(function(){
  if(this.value == 'recycle-account'){
     $('#action').empty();
    $('#action').append("<option value='delete'>Permanently Delete</option><option value='restore'>Restore Account</option>");


  }else{
     $('#action').empty();
    $('#action').append("<option value='trash'>Move to Trash</option>");

  }
    $('#html5-extension').DataTable().ajax.url('/userDataSource/'+this.value).load();

  });

  $("#delete_active_customer_form").on('submit',function() {
    swal({
      title: 'Are you sure?',
      text: "Note: make sure to double check before changing user status",
      type: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Proceed',
      padding: '2em'
  }).then(function(result) {
      if (result.value) {

      $link ='/company/customer/delete-restore-account-batch';
      var formData = $("#delete_active_customer_form").serialize();
      ajax_operation('post', $link, formData);
      $action = $('#page').val();
    $('#html5-extension').DataTable().ajax.url('/userDataSource/'+$action).load();
      }
  });

    return false;
  });
    $("#transfer_active_customer_form").on("submit", function(e){
        e.preventDefault();
        $('#comfirmtransfer').modal('show')
        $('.transferList').html('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...')
        $('.tocompany').html('')
        $('#otp').hide();
        $.ajax({
            url:'/company/transfer-customer',
            dataType: "json",
            type: "post",
            data: new FormData(this),
            contentType: false,
            cache: false,
            processData:false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success:function(data){
                if(data.status){
                    $('#otp').show();
                    let customerList = data.customers;
                    $('.tocompany').html(data.company)
                    $('.transferList').html('')
                    $('#company_ref').val(data.company_ref);
                    customerList.map(record=>{
                        $('.transferList').append('<p> '+record.first_name+' '+record.last_name+' </p>  ')
                    $('.transferList').append('&nbsp;')

                    })
                }else{
                    $('.transferList').html("<div class='aler alert-danger'>"+data.message+"</div>")
                }
            }
        });
    return false;
    });
    $("#FinalTransfer").on("submit", function(e){
        startLoadingButton('finaltransferBTN');
        disableBtn('finaltransferBTN')
        e.preventDefault();
        $('.transferList').html('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...')
        var formData = new FormData($("form#FinalTransfer")[0]);
        $("input:checkbox[type=checkbox]:checked").each(function(){
            formData.append("customers[]", $(this).val())
        });
        formData.append("accountNo", $('#accountNo').val())

        //
        $.ajax({
            url:'/company/final-transfer-customer',
            dataType: "json",
            type: "post",
            // data: new FormData(this),
            data:formData,
            contentType: false,
            cache: false,
            processData:false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success:function(data){
                if(data.status){
                    $('.transferList').html("<div class='aler alert-success'>"+data.message+"</div>")
                    $('#html5-extension').DataTable().ajax.url('/userDataSource/transfer_customer').load();
                }else{
                    $('.transferList').html("<div class='aler alert-danger'>"+data.message+"</div>")
                }
                enableBtn('finaltransferBTN')
                stopLoadingButton('finaltransferBTN','Proceed');
            }
        });
        return false;
    });
