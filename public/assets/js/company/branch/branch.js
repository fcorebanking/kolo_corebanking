function btnadd() {
    $branchname = $('#branch_name').val();
    $branchaddress = $('#branch_address').val();
    $currenciesID = $('#currencies').val();
    $code = $('#code').val();
    $token = $('.token').val();
    $data_info = { currency_id: $currenciesID, branch_name: $branchname, address: $branchaddress, _token: $token, code: $code };
    ajax_operation('put', 'branch/ajax_addbranch', $data_info);
    renderptbranch();
    // renderptvariablepage();
}

function update_branch() {
    $branchid = $('#edit_id').val();
    $status = $('#status').val();
    $branchname = $('#edit_branch_name').val();
    $branchmanager = $('#manager').val();
    $branchaddress = $('#edit_branch_address').val();
    $currenciesID = $('#edit_currencies').val();
    $code = $('#code_edit').val();

    $token = $('.token').val();
    $data_info = { code: $code, currency_id: $currenciesID, manager: $branchmanager, status: $status, id: $branchid, branch_name: $branchname, address: $branchaddress, _token: $token };
    ajax_operation('post', 'branch/ajax_updatebranch', $data_info);
    renderptbranch();
}

function delete_branch(val) {

    if (confirm("Are you sure, you want to delete this record?") == true) {
        ajax_operation('delete', 'branch/ajax_deletebranch/' + val, { id: val });
        renderptbranch();
    }
    return false;
}

function renderptbranch() {
    $('#html5-extension').DataTable().ajax.url('/company/branch/ajax_view').load();
}


function edit_branch(id, branchmanager) {
    $('#editexampleModal').modal('show');
    $('#edit_id').val(id);
    $.ajax({
        url: '/company/branch-record/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            data.forEach(function(branchdta) {
                $('#edit_branch_name').val(branchdta.branch_name);
                $('#status').val(branchdta.status_id).change();
                $('#edit_branch_address').val(branchdta.address);
                $('#edit_currencies').val(branchdta.currency_id);
                $('#code_edit').val(branchdta.code);

            });
        }
    });
    $.ajax({
        url: 'branch/branch_staff/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {

            $('select[name="manager"]').empty();
            $('select[name="manager"]').append('<option >Select Manager</option>')
            data.forEach(function(staff) {
                if (staff.id == branchmanager) {
                    $('select[name="manager"]').append('<option value="' + staff.uuid + '" selected>' + staff.first_name + '</option>')

                } else {
                    $('select[name="manager"]').append('<option value="' + staff.uuid + '" >' + staff.first_name + '</option>')
                }
            });
        }
    }).done(function() {
        $('select[name="manager"]').val(branchmanager).change();
    });



}