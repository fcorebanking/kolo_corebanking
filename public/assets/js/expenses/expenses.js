$("#product-form").on('submit', function() {
    var form = $(this);
    if ($('#type_id').val() == '0' || $('#type_id').val() == null) {
        ajax_operation('post', '/company/expenses/store', form.serialize());
    } else {
        ajax_operation('post', '/company/expenses/update', form.serialize());
    }
    $('#html5-extension').DataTable().ajax.url("/company/expenses/table").load();
    return false;
});
function reset(){
    $('#exampleModal').modal('show')

    $('#type_id').val(0);
    // $('#product-form')[0].reset();
}
function editfunc(val){
    $.ajax({
        url: "/company/expenses-type/edit/" + val,
        type: 'post',
        data: {},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('#exampleModal').modal('show')
            $('#name').val(data.name);
            $('#type_id').val(data.id);
        }
    });
}
function editfunction(val){
    $.ajax({
        url: "/company/expenses/edit/" + val,
        type: 'post',
        data: {},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            $('#exampleModal').modal('show')
            $('#expense_type_id').val(data.expense_type_id).change();
            $('#type_id').val(data.id);
            $('#amount').val(data.amount);
            $('#expenses_date').val(data.expenses_date);
            $('#ledger_dr').val(data.ledger_dr);

            $('#recurring_expense_period').val(data.recurring_expense_period).change();
            $('#description').text(data.description);
            if(data.is_recurring == 1){
                $("#is_recurring2").attr('checked', 'checked');
                $("#is_recurring").removeAttr('checked', 'checked');
            }else{
                $("#is_recurring2").removeAttr('checked', 'checked');

                $("#is_recurring").attr('checked', 'checked');
            }
        }
    });
}
function removeType(val){

}
function removeExpenses(val){
    ajax_operation('delete',  "/company/expenses/delete/" + val, {});
    $('#html5-extension').DataTable().ajax.url("/company/expenses/expenses-table").load();

}
// $("#add-expenses").on('submit', function() {
//     var form = $(this);
//     if ($('#type_id').val() == '0' || $('#type_id').val() == null) {
//         ajax_operation('post', '/company/expenses/store-expenses', form.serialize());
//     } else {
//         ajax_operation('post', '/company/expenses/update-expenses', form.serialize());
//     }
//     $('#html5-extension').DataTable().ajax.url("/company/expenses/expenses-table").load();
//     return false;
// });


$("#add-expenses").submit(function(e) {
    e.preventDefault();
    var formData = new FormData($(this)[0]);
    $("#btn-progress").html('Processing<i class="fa fa-spinner fa-spin" style="font-size:24px"></i>');
    if ($('#type_id').val() == 0 || $('#type_id').val() == '' || $('#type_id').val() == '0' ||  $('#type_id').val() == null) {
    $url = '/company/expenses/store-expenses';
    }else{
        $url = '/company/expenses/update-expenses';
    }
    $.ajax({
         type: "POST",
         url: $url,
         data: formData,
         async: false,
         dataType: 'json',
         cache: false,
         contentType: false,
         processData: false,
         headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
         success: function(data,textStatus, xhr) {
            if(data.status ==1){
                $('#html5-extension').DataTable().ajax.url("/company/expenses/expenses-table").load();
                swal("Good job!", data.message, "success");
            }else{
                swal("Failed!", data.message, "fail");
            }
        },
         complete: function(xhr, textStatus) {
            $("#btn-progress").html('Save');
                console.log(xhr.status);
                if(xhr.status == 401){
                            window.open("{{url('/login')}}","newwin","width=1200,height=600");
                }
        }
    });
    return false;
});

function searchExpenses(){
    $from = $('#from').val();
    $to = $('#to').val();
    $page = $('#page').val();
    if($from > $to){
        alert("From date should be lesser than to");
        return false;
    }
    $expense_type_idx = $('#expense_type_idx').val();
    $('#html5-extension').DataTable().ajax.url("/company/expenses/expenses-table?from="+$from+"&to="+$to+"&type="+$expense_type_idx+"&"+$page).load();
}

function approvePending($id){
    $.ajax({
        type: "POST",
        url: '/company/expenses/approve/'+$id,
        async: false,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data,textStatus, xhr) {
           if(data.status ==1){
               $('#html5-extension').DataTable().ajax.reload();
               swal("Done!", data.message, "success");
           }else{
               swal("Failed!", data.message, "fail");
           }
       },
        complete: function(xhr, textStatus) {
           $("#btn-progress").html('Save');
               console.log(xhr.status);
               if(xhr.status == 401){
               }
       }
   });
}
function rejectPending($id){
    $.ajax({
        type: "POST",
        url: '/company/expenses/reject/'+$id,
        async: false,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data,textStatus, xhr) {
           if(data.status ==1){
               $('#html5-extension').DataTable().ajax.reload();
               swal("Done!", data.message, "success");
           }else{
               swal("Failed!", data.message, "fail");
           }
       },
        complete: function(xhr, textStatus) {
           $("#btn-progress").html('Save');
               console.log(xhr.status);
               if(xhr.status == 401){
               }
       }
   });
}
