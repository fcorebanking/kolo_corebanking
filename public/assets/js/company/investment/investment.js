
$(document).ready(function() {
    $btn = "investment";
     $('#html5-extension').DataTable({
         "serverSide": true,
         "ajax": {
             url: "/userDataSource/"+$btn,
             method: "get"
         },
         "columnDefs" : [{
             'targets': [4],
             'orderable': false
         }],

         dom: '<"row"<"col-md-12"<"row"<"col-md-6"B><"col-md-6"f> > ><"col-md-12"rt> <"col-md-12"<"row"<"col-md-5"i><"col-md-7"p>>> >',
         buttons: {
             buttons: [{
                 extend: 'copy',
                 className: 'btn'
             }, {
                 extend: 'csv',
                 className: 'btn'
             }, {
                 extend: 'excel',
                 className: 'btn'
             }, {
                 extend: 'print',
                 className: 'btn'
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
 });

function invest($uid){
    $('.verify_Restriction').hide();
    $("#submit_form")[0].reset();
    $('#formtype').val('investment');
    $('#uuid').val($uid);
    $('#exampleModalLabel').html('Select Investment');
    $("#exampleModal").modal('show');
    fetchproduct($uid);
    fetchinvestmentproduct();
}

var $_getValidationField = document.getElementsByClassName('validation-text');
$("#submit_form").on('submit',function() {
    $amount= $('#amount').val();

    $valid = 0;
    if ($amount == "") {
        $_getValidationField[0].innerHTML = ' Enter Amount';
        $_getValidationField[0].style.display = 'block';
        $valid++;
      } else {
        $_getValidationField[0].style.display = 'none';
      }

      if($valid > 0){
        return false;
      }
      var formData = $(this).serialize();

      disableBtn('btn-investment');
    startLoadingButton('btn-investment');
      $.ajax({
        url:'/company/makeinvestment/',
        type: 'put',
        data: formData,
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success:function(data){
            if(data.status == 1){
                window.location.replace(window.location.origin +'/'+ data.redirectlink);
            }else{
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                  )
            }
            stopLoadingButton('btn-investment', 'Book');
            enableBtn('btn-investment');
        }
    });

    return false;
});
function functionfetchinfo(){
    $searchval =  $('#uuid').val();
    $('.verify_Restriction').toggle();
    ViewCustomerStatus($searchval);
}
