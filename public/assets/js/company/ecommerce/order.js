function comfirm_to_delete(formtodelete){
    $('#selectedID').val(formtodelete);
    swal({
    title: 'Are you sure?',
    text: "You want to delete this record",
    type: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Proceed',
    padding: '2em'
    }).then(function(result) {
        if (result.value) {
            $id = $('#selectedID').val();
            ajax_operation('delete', '/company/ecommerce/order/delete/' + $id, {});
            $('#html5-extension').DataTable().ajax.reload();
        }
    });
    return false;
}
function comfirm_to_cancil(formtodelete){
    $('#selectedID').val(formtodelete);

    swal({
    title: 'Are you sure?',
    text: "You want to Cancil this Order",
    type: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Proceed',
    padding: '2em'
    }).then(function(result) {
        if (result.value) {
            $id = $('#selectedID').val();
            ajax_operation('post', '/company/ecommerce/order/cancil', {order_id:$id});
            $('#html5-extension').DataTable().ajax.reload();
        }
    });
    return false;
}
function comfirm_to_complete(formtodelete){
    $('#selectedID').val(formtodelete);
    swal({
    title: 'Are you sure?',
    text: "You want to Compete this Order",
    type: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Proceed',
    padding: '2em'
    }).then(function(result) {
        if (result.value) {
            $id = $('#selectedID').val();
            ajax_operation('post', '/company/ecommerce/order/complete/'+$id, {});
            $('#html5-extension').DataTable().ajax.reload();
        }
    });
    return false;
}
function showOrderdetails(id){
    $('#editexampleModal').modal('show');
    $.ajax({
    url: '/company/ecommerce/order/show/' + id,
    type: 'post',
    dataType: 'json',
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    success: function(data) {
        $('.imgcover').empty();
        $('.imgcover').append("<p><h3>Order Details</h3></p>");

        $('.imgcover').append("<p>Product: "+data.name+"</p>");
        $('.imgcover').append("<p>Amount: "+data.amount+"</p>");
        $('.imgcover').append("<p>Quantity: "+data.qrty+"</p>");
        $('.imgcover').append("<p>Total Amount: "+data.total_amount+"</p>");
        $('.imgcover').append("<p><h3>Contact</h3></p>");
        $('.imgcover').append("<p>Name: "+data.first_name+" "+data.last_name+"</p>");
        $('.imgcover').append("<p>Address: "+data.address+"</p>");
        $('.imgcover').append("<p>Contact: "+data.contact+"</p>");
    }
});
}
