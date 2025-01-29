$('#customer').select2({
    placeholder: 'Select Customer',
    ajax: {
        url: '/userDataSelect',
        dataType: 'json',
        delay: 250,
        data: function(data) {
            return {
                searchTerm: data.term // search term
            };
        },
        processResults: function(response) {
            return {
                results: response
            };
        },
        cache: true
    }
});
$('#beneficiary').select2({
    placeholder: 'Select Beneficiary',
    ajax: {
        url: '/userDataSelect',
        dataType: 'json',
        delay: 250,
        data: function(data) {
            return {
                searchTerm: data.term // search term
            };
        },
        processResults: function(response) {
            return {
                results: response
            };
        },
        cache: true
    }
});
function rendersave1(){
    var val = $('#customer').val();
   
$('#savetype1').select2({
    placeholder: 'Select Account Type',
    ajax: {
        url: '/userDataSelect/'+val,
        dataType: 'json',
        delay: 250,
        data: function(data) {
            return {
                searchTerm: data.term // search term
            };
        },
        processResults: function(response) {
            return {
                results: response
            };
        },
        cache: true
    }
});
}
function rendersave2(){
    var val = $('#beneficiary').val();
    $('#savetype2').select2({
        placeholder: 'Account Type',
        ajax: {
            url: '/userDataSelect/'+val,
            dataType: 'json',
            delay: 250,
            data: function(data) {
                return {
                    searchTerm: data.term // search term
                };
            },
            processResults: function(response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });
    }
//

//
    $("#submit_form").on('submit',function() {
        swal({
            title: 'Are you sure you want to perform this transfer?',
            text: "Note: you will be charge for SMS",
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Proceed',
            padding: '2em'
        }).then(function(result) {
            if (result.value) {
                disableBtn('transfer-wallet')
                startLoadingButton('transfer-wallet')
            var formData = $("#submit_form").serialize();  
            $.ajax({
              url:'/company/transaction/make-wallet-to-wallet-transfer',
              type: 'post',
              data: formData,
              dataType: 'json',
              headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
              success:function(data){
               
                  if(data.status){
                      $status = 'success';
                      $complete = 'Redirecting...';
                      swal(
                          $complete,
                          data.message,
                          $status
                        );
                     window.location.replace(window.location.origin +'/'+ data.redirectlink);
                  }else{
              
                      $status = 'error';
                      $complete = 'Error!';
                      swal(
                          $complete,
                          data.message,
                          $status
                        );
                  }
                  stopLoadingButton('transfer-wallet', 'submit');
                    enableBtn('transfer-wallet');
              }
          });
        }
        });
       
    
        return false;
    });