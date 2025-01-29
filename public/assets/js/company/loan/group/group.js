function groupToggle(){
    $('.group-form').toggle();
    $('#group-form')[0].reset();
    $('#group_id').val('');
}
//groupToggle()
//view-with-group-loan-select
$('.group-select').select2({
    placeholder: 'Select group',
    ajax: {
        url: '/company/view-with-group-loan-select',
        dataType: 'json',
        delay: 100,
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
$('.staff').select2({
    placeholder: 'Select Staff',
    ajax: {
        url: '/userDataSelect?type=2',
        dataType: 'json',
        delay: 100,
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
$('.customerx').select2({
    placeholder: 'Select Customer',
    ajax: {
        url: '/userDataSelect',
        dataType: 'json',
        delay: 100,
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
$(document).ready(function() {
    $('#group-form').submit(function(event) {
        event.preventDefault(); // prevent the default form submission

        // get the form data
        var formData = $(this).serialize();

        // send the AJAX request
        ajax_operation('post', '/company/loan/group', formData,'grouploanBTN','Save');
     $('#html5-extension').DataTable().ajax.url('/company/loan/group/show').load();
     groupToggle();
    });
    $('#assign_customer_form').submit(function(event) {
        event.preventDefault(); // prevent the default form submission
        // get the form data
        var formData = $(this).serialize();
        // send the AJAX request
        ajax_operation('post', '/company/assign-group-loan', formData,'proceebtn','Process');
     $('#html5-extension').DataTable().ajax.url('/company/view-user-with-group-loan').load();
    });
});
    // listen for the edit button click event
    function editGroup(id){

        // send an AJAX request to fetch the item data
        $.ajax({
            url: "/company/loan/group/" + id + "/edit",
            type: "GET",
            dataType: "json",
            success: function(response) {
                // populate the form fields with the fetched data
                $('#group_id').val(response.id);
                $('#name').val(response.name);
                $('#leader').val(response.leader_info.uuid).change();
                $('#secretary').val(response.secretary_info.uuid).change();
                if(response.manager_info) $('#manager_id').val(response.manager_info.uuid).change();


                $('#max_member').val(response.max_member);
                $('#max_principal').val(response.max_principal);
                $('#max_interest').val(response.max_interest);
                $('.leader').html(response.leader_info.first_name+' '+response.leader_info.last_name)
                $('.secretary').html(response.secretary_info.first_name+' '+response.secretary_info.last_name)
                $('.group-form').show();

            },
            error: function(xhr, status, error) {
                // handle the error response
                alert("An error occurred while fetching the data: " + error);
            }
        });
    }
    function deleteGroup(id){
        ajax_operation('delete', '/company/loan/group/'+id+'/delete', {});
        $('#html5-extension').DataTable().ajax.url('/company/loan/group/show').load();
    }
function GroupDetails(id){
    //
    $('#GroupDetails').modal('show')
    $('.groupContent').html('Loading...');
    $.ajax({
        url: "/company/loan/group-destails/" + id ,
        type: "GET",
        dataType: "json",
        success: function(response) {
            // populate the form fields with the fetched data
            $('.groupContent').html('<table class="table table-hover non-hover" style="width:100%">');
            $('.groupContent').append('<tbody><tr><td class="btn btn-flat">Total Principal '+response.principal+'</td><td class="btn btn-flat">Total Principal Left '+response.principalLeft+'</td><td class="btn btn-flat" >Total Interest Left '+response.InterestLeft+'</td><td class="btn btn-flat" >Total Paid '+response.paid+'</td><td class="btn btn-flat" >Total Saving Balance '+response.savingBalance+'</td></tr>');
            $('.groupContent').append('</tbody></table>');
        },
        error: function(xhr, status, error) {
            // handle the error response
            alert("An error occurred while fetching the data: " + error);
        }
    });
}
