viewdata("/company/loan/pending-repayment-data", "get");
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
       $('#html5-extension').DataTable().ajax.url("/company/loan/pending-repayment-data?staff="+$officer).load();

   }
});
}
function renderStaff_pending() {
$officer = $('#officer').val();

$('#html5-extension').DataTable().ajax.url("/company/loan/pending-repayment-data?staff="+$officer).load();

}
$("#pending_approval_form").on('submit',function() {
$officer = $('#officer').val();
var formData = $(this).serialize();

$link ='/company/loan/post-pending-repayment';
var formData = $(this).serialize();
ajax_operation('post', $link, formData);
$('#html5-extension').DataTable().ajax.url("/company/loan/pending-repayment-data?staff="+$officer).load();
return false;
});

