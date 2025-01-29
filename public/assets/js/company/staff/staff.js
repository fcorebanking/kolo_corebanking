var $_getValidationField = document.getElementsByClassName('validation-text');
$("#add-staff").on('submit', function() {

    $staff_fname = $('#c-first-name').val();
    $staff_lastname = $('#c-last-name').val();
    $staff_username = $('#c-username').val();
    $staff_email = $('#c-email').val();
    $staff_phone = $('#c-phone').val();
    $staff_branch = $('#c-branch').val();
    $staff_password = $('#c-password').val();
    $staff_password2 = $('#c-password2').val();
    $token = $('.token').val();
    $('.validation-text').html();


    if ($staff_fname == "") {
        $_getValidationField[0].innerHTML = 'First Name must be filled out';
        $_getValidationField[0].style.display = 'block';
        return false;
    } else {
        $_getValidationField[0].style.display = 'none';
    }
    if ($staff_lastname == "") {
        $_getValidationField[1].innerHTML = 'Last Name must be filled out';
        $_getValidationField[1].style.display = 'block';
        return false;
    } else {
        $_getValidationField[1].style.display = 'none';
    }
    if ($staff_username == "") {
        $_getValidationField[2].innerHTML = 'Username must be filled out';
        $_getValidationField[2].style.display = 'block';
        return false;
    } else {
        $_getValidationField[2].style.display = 'none';
    }
    if ($staff_email == "") {
        $_getValidationField[3].innerHTML = 'Email must be filled out';
        $_getValidationField[3].style.display = 'block';
        return false;
    } else {
        $_getValidationField[3].style.display = 'none';
    }
    if ($staff_phone == "") {
        $_getValidationField[4].innerHTML = 'Phone must be filled out';
        $_getValidationField[4].style.display = 'block';
        return false;
    } else {
        $_getValidationField[4].style.display = 'none';
    }
    if ($staff_branch == "") {
        $_getValidationField[5].innerHTML = 'Branch must be filled out';
        $_getValidationField[5].style.display = 'block';
        return false;
    } else {
        $_getValidationField[5].style.display = 'none';
    }
    if ($('#user_id').val() == '0') {
        if ($staff_password == "") {
            $_getValidationField[6].innerHTML = 'Password must be filled out';
            $_getValidationField[6].style.display = 'block';
            return false;
        } else {
            $_getValidationField[6].style.display = 'none';
        }

        if ($staff_password != $staff_password2) {
            $_getValidationField[7].innerHTML = 'Password Miss-Match';
            $_getValidationField[7].style.display = 'block';
            return false;
        }
    }
    var form = $(this);
    //  $data_info = {branch:$staff_branch,firstname:$staff_fname,lastname:$staff_lastname,email:$staff_email,username:$staff_username,phone:$staff_phone,password:$staff_password};
    if ($('#staff_id').val() == '0' || $('#staff_id').val() == null) {
        ajax_operation('put', 'staff/ajax_addstaff', form.serialize());
    } else {
        ajax_operation('post', 'staff/ajax_updatestaff', form.serialize());
    }
    $('#html5-extension').DataTable().ajax.url("/company/staff/ajax_view").load();
    return false;
});


function delete_staff(val, statusid) {
    if (confirm("Are you sure, you want to delete this record?") == true) {
        ajax_operation('delete', 'staff/ajax_recyclestaff', { id: val, status: statusid });
        $('#html5-extension').DataTable().ajax.url("/company/staff/ajax_view").load();
    }
    return false;
}

function send_staff_deposit() {
    $staff = $('#staff').val();
    $amount_staff = $('#amount_staff').val();
    $staff_credit_option = $('#staff_credit_option').val();
    ajax_operation('post', '/company/vault/send-staff-wallet', { amount: $amount_staff, staff: $staff, staff_credit_option: $staff_credit_option });
    $('#html5-extension').DataTable().ajax.url("/company/staff/ajax_view").load();

}

function send_staff_commission() {
    $staff = $('#staff').val();
    $amount_staff = $('#amount_staff').val();
    $staff_credit_option = $('#staff_credit_option').val();
    ajax_operation('post', '/company/vault/send-staff-comission', { amount: $amount_staff, staff: $staff, staff_credit_option: $staff_credit_option });
    $('#html5-extension').DataTable().ajax.url("/company/staff/ajax_view").load();

}

function addstaff() {
    $("#add-staff")[0].reset();
    $('#staff_id').val('0');
    $('#hidepsw').show();
    $('.reset_psw_pin').hide();
    $('#exampleModal').modal('show');
}

function edit_staff(id, roleid) {
    $("#add-staff")[0].reset();
    $('.reset_psw_pin').show();
    $.ajax({
        url: 'staff/staff_list/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('.reset_psw_pin').empty();
            data.forEach(function(staff) {
                $('#staff_id').val(staff.uuid);
                $('#c-first-name').val(staff.first_name);
                $('#c-last-name').val(staff.last_name);
                $('#c-username').val(staff.username);
                $('#c-email').val(staff.email);
                $('#c-phone').val(staff.phone);
                $('#c-branch').val(staff.branch_id).change();
                $('#c-status').val(staff.status_id).change();
                $userpswreset = "userpswreset('" + staff.uuid + "')";
                $userpinreset = "userpinreset('" + staff.uuid + "')";
                $('.reset_psw_pin').append('<button onclick="' + $userpswreset + '" class="btn btn-warning float-right btn-lg" type="button"><span id="srpassword">Send Reset Password</span></button>');
                $('.reset_psw_pin').append('<button onclick="' + $userpinreset + '" class="btn btn-danger float-right btn-lg" type="button"><span id="srpin">Send Reset Pin</span></button>');

            });

        }
    });
    //edit_role(roleid); for multiple checkbox rolee
    $('#permission').val(roleid).change(); //for select option
    $('#hidepsw').hide();
    $('#exampleModal').modal('show');
}

function edit_role(id) {

    $.ajax({
        url: 'role/get-permission/' + id,
        type: 'post',
        data: {},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('input:checkbox').prop('checked', false);
            data.forEach(function(item) {
                $('input[value="' + item.permission_id + '"]:checkbox').prop('checked', true);
            });
        }
    });

}
//renderstaff();