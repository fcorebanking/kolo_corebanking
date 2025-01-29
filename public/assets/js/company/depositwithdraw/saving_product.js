viewdata("/company/saving/view-pending", "get");
function renderStaff() {
$branch = $('#branch').val();
$.ajax({
   url: '/company/companyname/branch/branch_staff/' + $branch,
   type: 'GET',
   dataType: 'json',
   success: function(data) {
       $('select[name="officer"]').empty();

       data.forEach(function(staff) {

           $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
       });
       $officer = $('#officer').val();
       $('#html5-extension').DataTable().ajax.url("/company/saving/view-pending?staff="+$officer).load();

   }
});
}
function renderStaff_pending() {
$officer = $('#officer').val();

$('#html5-extension').DataTable().ajax.url("/company/saving/view-pending?staff="+$officer).load();

}
$("#pending_approval_form").on('submit',function() {
$officer = $('#officer').val();
var formData = $(this).serialize();
$link ='/company/saving/post-pending-saving';
var formData = $(this).serialize();
ajax_operation('post', $link, formData);
$('#html5-extension').DataTable().ajax.url("/company/saving/view-pending?staff="+$officer).load();
return false;
});

function viewPendingDetails($id){
    $('#exampleModalPending').modal('show');
    $.ajax({
        url:'/company/saving/view-pending-saving-details/'+$id,
        type: 'post',
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success:function(data){
            let account = data.account;
            let details = data.data;
            $('#bodyTable').empty();
            $('.customerinfo').empty();
            $('.amount').html(details.amount);
            $('#pendingid').val($id);
            $('.customerinfoPhoto').html("<img src='"+data.customerlogo+"' style='width:100%'>");
            $('.tellerinfoPhoto').html("<img src='"+data.tellerLogo+"' style='width:100%'>");
            $('.customerinfo').append("<ul class='text-left'>");
            $('.customerinfo').append("<li>Customer Name: "+details.user.first_name+" "+details.user.last_name+"</li>");
            $('.customerinfo').append("<li>Transaction AccountNo: "+details.account_type+"</li>");
            $('.customerinfo').append("<li>Phone: "+details.user.phone+"</li>");
            $('.customerinfo').append("<li>Email: "+details.user.email+"</li>");
            $('.customerinfo').append("</ul>");
            $('.tellerinfo').empty();
            $('.tellerinfo').append("<ul class='text-left'>");
            $('.tellerinfo').append("<li>Teller Name: "+details.teller.first_name+" "+details.teller.last_name+"</li>");
            $('.tellerinfo').append("<li>Phone: "+details.teller.phone+"</li>");
            $('.tellerinfo').append("<li>Email: "+details.teller.email+"</li>");
            $('.tellerinfo').append("<li>Balance: "+data.teller.staff_wallet.balance+"</li>");
            $('.tellerinfo').append("</ul>");
            // beneficiary
            $('.beneficiary').html('');
            if(data.beneficiary != false){
                $('.beneficiary').html('<h2>Beneficiary Account</h2><p>Beneficiary Name: '+data.beneficiary.name+'</p><p>Beneficiary Account: '+data.beneficiary.accountNo+'</p><p>Beneficiary Bank: '+data.beneficiary.bank.bank_name+'</p>');
            }
            account.forEach(function(savings) {
                // console.log(details.user.first_name);
                $('#bodyTable').append("<tr><td>"+details.user.first_name+" "+details.user.last_name+"</td><td>"+savings.virtual_accnum+"</td><td>"+savings.account_type.savings_product+"</td><td>"+savings.user_wallet.balance+"</td></tr>");
            });
            // details.forEach(function(detail) {
            // });
        }
    });
}
function rejectPending($action){
    $officer = $('#officer').val();
    $pendingid =$('#pendingid').val();
    ajax_operation('post', '/company/saving/single-approval', {action:$action,pendingid:$pendingid});
    $('#html5-extension').DataTable().ajax.url("/company/saving/view-pending?staff="+$officer).load();
}
