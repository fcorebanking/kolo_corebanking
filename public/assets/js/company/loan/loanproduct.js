var $_getValidationField = document.getElementsByClassName('validation-text');
$("#add-loan-product").on('submit', function() {
    $name = $('#product_name').val();
    $ProdID = $('#product_id').val();
    $interest_rate = $('#interest_rate').val();
    $minPrincipal = $('#minPrincipal').val();
    $DefaultPrincipal = $('#DefaultPrincipal').val();
    $maxPrincipal = $('#maxPrincipal').val();
    $minInterest = $('#minInterest').val();
    $DefaultInterest = $('#DefaultInterest').val();
    $maxInterest = $('#maxInterest').val();
    $max_withdraw = $('#max_withdraw').val();
    $max_daily_transfer = $('#max_daily_transfer').val();
    $max_balance = $('#max_balance').val();
    $loan_interest_type = $('#loan_interest_type').val();
    $maintenance_charges = $('#maintenance_charges').val();
    $freq = $('#freq').val();
    $token = $('.token').val();
    $('.validation-text').html();
    if ($name == "") {
        $_getValidationField[0].innerHTML = 'Name must be filled out';
        $_getValidationField[0].style.display = 'block';
        return false;
    } else {
        $_getValidationField[0].style.display = 'none';
    }
    if ($interest_rate == "") {
        $_getValidationField[1].innerHTML = 'Interest Rate must be filled out';
        $_getValidationField[1].style.display = 'block';
        return false;
    } else {
        $_getValidationField[1].style.display = 'none';
    }

    var form = $(this);
    if($ProdID == null || $ProdID == 0){
        ajax_operation('put', '/company/loan/save-product', form.serialize(), 'loanBTN', 'Save Product');
    }else{
        ajax_operation('put', '/company/loan/update-product', form.serialize(), 'loanBTN', 'Save Product');
    }
    $('#html5-extension').DataTable().ajax.url("/company/loan/product-table").load();
    return false;
});

function newModal() {
    $('#add-loan-product')[0].reset();
    $('#product_id').val(null);

}

function edit(id) {
    $.ajax({
        url: '/company/loan/edit/' + id,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            console.log(data);
            if (data) {
                $('#loan_interest_type').val(data.product.loan_interest_type).change();
                $('#product_id').val(data.productID);
                $('#product_name').val(data.product.product_name);
                $('#interest_rate').val(data.product.interest_rate);
                $('#DefaultPrincipal').val(data.product.DefaultPrincipal);
                $('#minPrincipal').val(data.product.minPrincipal);
                $('#maxPrincipal').val(data.product.maxPrincipal);
                $('#DefaultInterest').val(data.product.DefaultInterest);
                $('#minInterest').val(data.product.minInterest);
                $('#maxInterest').val(data.product.maxInterest);
                $('#loanFeeMethod').val(data.product.loanFeeMethod).change();
                $('#loanFee').val(data.product.loanFee);
                $('#managementFee').val(data.product.managementFee);
                $('#managementFeeMethod').val(data.product.managementFeeMethod).change();
                $('#DefaultDuration').val(data.product.DefaultDuration);
                $('#minDuration').val(data.product.minDuration);
                $('#maxDuration').val(data.product.maxDuration);
                $('#freq').val(data.product.repayment_cycle_id).change();
                $('#approximate_status').val(data.product.approximate_status);
                $('#status_id').val(data.product.status_id);
                $('#keep_as_past_onMissed_onExtend').val(data.product.keep_as_past_onMissed_onExtend).change();
                $('#auto_pay').val(data.product.auto_pay).change();
                $('#missedFee').val(data.product.missedFee);
                // $('#minExtendDuration').val(data.product.minExtendDuration);
                // $('#maxExtendDuration').val(data.product.maxExtendDuration);
                $('#orderOfRepayment').val(data.product.orderOfRepayment).change();
                $('#exampleModal').modal('show');
            }
        }
    });
}
