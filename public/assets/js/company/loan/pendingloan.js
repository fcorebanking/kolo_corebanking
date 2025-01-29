
function approvalStatus(){
    saveLoan();
    $('#html5-extension').DataTable().ajax.reload();
}
function viewRemitaSalary(phone,acc,bankCode){
    $('#remittar_bankCode').val(bankCode);
    $('#remittar_accountNo').val(acc);
    $('#remitaPhone').val(phone);

    $('#exampleModal-remita').modal('show');
}
function viewLoan(uuid){
    $('#exampleModal').modal('show');
        $.ajax({
            url:'/company/loan/view-loan-details/'+uuid,
            type: 'post',
            dataType: 'json',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success:function(data){
                // loan_type
                $('.sessions').html("<strong><p>Created By:"+data.created_by+"</p><p>Modified By: "+data.modified_by+"</p></strong>");
                $('#customer').val(data.user_id);
                $('#remark').val(data.remark);
                $('#loanID').val(uuid);
                $('#DurationPeriod').val(data.DurationPeriod).change();
                $('#loan_type').val(data.LoanType).change();
                $("#loanproducts option").filter(function() {
                    return $(this).text() == data.productText;
                }).prop("selected", true);
                $('#loanproducts').change();
                $("#branch option").filter(function() {
                    return $(this).text() == data.LoanBranch_id;
                }).prop("selected", true);
                $("#branch").change();
                $('#loanofficer').val(data.LoanOfficer_id);
                $('#loanFeeMethod').val(data.LoanFeeMethod).change();
                $('#loanFee').val(data.LoanFee);
                $('#managementFee').val(data.mrgtFee);
                $('#managementFeeMethod').val(data.mrgtFeeMethod).change();
                $('#auto_pay').val(data.auto_pay).change();

                $('#Duration').val(data.duration);
                $('#principal').val(data.principal);
                $('#keep_as_past_onMissed_onExtend').val(data.keep_as_past_onMissed_onExtend).change();
                $('#ExtendDuration').val(data.ExtendDuration);
                $('#missedFee').val(data.missedFee);
                if ($('#loan_type').val() == 2) {
                    selectPeriod();
                } else {
                    $("#freq option").filter(function() {
                        return $(this).text() == data.periodUid;
                    }).prop("selected", true);
                    $('#repaymentAMTDiv').hide();
                    $("#freq").removeAttr('readonly');
                }

            }
        });
    }
