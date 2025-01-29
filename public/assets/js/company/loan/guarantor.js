// $('#html5-extension').DataTable().ajax.url('/company/branch/ajax_view').load();

// $("#add-loan-guarantor").on('submit', function() {
//     var formData = $(this).serialize();
//     ajax_operation('post', '/company/loan/save-guarantor', formData, 'loanBTN', 'Save Guarantor');

//     $('#html5-extension').DataTable().ajax.url("/company/loan/view-guarantor").load();
//     return false;
// });

function newModal() {
    $('.add-g-card').toggle();
}
$('.add-g-card').toggle();

$('#customer').select2({
    placeholder: 'Select an Customer',
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
function editModal(id){
        $.ajax({
            url: '/company/loan/guarantor-edit/'+id,
            type: 'post',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                $('#editCustomer').val(data.user_id);
                $('#editfirst_name').val(data.first_name);
                $('#editlast_name').val(data.last_name);
                $('#editsalary').val(data.salary);
                $('#editstatus_id').val(data.status_id).change();
                $('#editbvn').val(data.bvn);
                $('#editemail').val(data.email);
                $('#editphone').val(data.phone);
                $('#editrelationship').val(data.relationship);
                $('#editaddress').val(data.address);
                $('#editoccupation').val(data.occupation);
                $('#editother_remarks').val(data.other_remarks);
                $('#editGloan').val(data.editid);
            }
        });
    $('#exampleModal').modal('show');
}
function deleteG(id){
    swal({
        title: 'Are you sure you want to delete this Record?',
        text: "Note: This is irreversible",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        padding: '2em'
    }).then(function(result) {
        if (result.value) {
          ajax_operation('delete', '/company/loan/delete-guarantor/'+id, {});
         $('#html5-extension').DataTable().ajax.url('/company/loan/view-guarantor').load();
        }
    });
    
}