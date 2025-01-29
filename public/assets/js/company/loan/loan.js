var bookingform = document.getElementById('bookingform')
var bookingform2 = document.getElementById('bookingform2')
bookingform2.style.display='none'
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
//

$('#loan_products').select2({
    placeholder: 'Select an Product',
    ajax: {
        url: '/company/loan/product-list',
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
$('#guarantor').select2({
    placeholder: 'Select a Guarantor',
    ajax: {
        url: '/company/loan/guarantor-list',
        dataType: 'json',
        delay: 250,
        data: function(data) {
            return {
                searchTerm: data.term, // search term
                moredata: $('#customer').val()
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

$("#guarantorOption").change(function() {
    if (this.value == 1) {
        $('.guarantorsBox').show();
        console.log($('#customer').val());
    } else {
        $('.guarantorsBox').hide();
    }
});
$('.guarantorsBox').hide();

$('#customer').change(function() {
    console.log(this.value);
});
$('#repaymentAMTDiv').hide();
$('#loan_type').on('change', function() {
    if (this.value == 2) {
        selectPeriod();
    } else {
        $('#repaymentAMTDiv').hide();
        $("#freq").removeAttr('readonly');
        //  $("#DurationPeriod").removeAttr('readonly');
    }
});

function selectPeriod() {
    // $('#repaymentAMTDiv').show();
    $("#freq option").filter(function() {
        return $(this).text() == 'Monthly';
    }).prop("selected", true);
    // $("#DurationPeriod").attr('readonly', 'readonly');
    $('#DurationPeriod').val('years').change();
    $("#freq").attr('readonly', 'readonly');
    // $("#freq").attr('disabled', 'disabled');

}

function nextProcess(){
    var formData = $('#add-loan-book').serialize();
    disableBtn('loanNextBTN');
    startLoadingButton('loanNextBTN');
    $.ajax({
        url: '/company/loan/process-loan',
        type: 'post',
        data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {

                $borrower = data.borrower;
                $loanRepayment = data.repayData;
                $guarantor = data.guarantor;
                $('.borrowerCard').empty();
                $('.guarantorCard').empty();

                $('.loanrecord').html('\
                    <div class="col-md-12 col-sm-12">'+data.loanFeeMethod+'</div>\
                    <div class="col-md-12 col-sm-12"> '+data.managementFeeMethod+'</div>\
                    <div class="col-md-12 col-sm-12">Extension: '+data.ExtendDuration+'</div>\
                ');
                if($guarantor.length == 0){
                    $('.gmsg').html('<p> No Guarantor </p>');
                }
                $g=0;
                $guarantor.forEach(function(guarantor) {
                    $avatar = guarantor.passport;
                    $signature = guarantor.signature;
                    $status='';
                    if(guarantor.status_id== 1){
                        $status ="<span class='badge badge-success'>Verified</span>";
                    }else if(guarantor.status_id == 2){
                        $status ="<span class='badge badge-warning'>Pending</span>";
                    }else if(guarantor.status_id == 3){
                        $status ="<span class='badge badge-warning'> Enquiry</span>";
                    } else{
                        $status ="<span class='badge badge-danger'>Declined</span>";

                    }
                    $avatar = ($avatar) ? $avatar.replace('public', 'storage') : null;
                    $signature = ($signature) ? $signature.replace('public', 'storage') : null;
                    $avtarURL = ($avatar) ? window.location.origin+'/' + $avatar : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                    $signatureURL = ($avatar) ? window.location.origin+'/' + $signature : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                    $g++;
                    $('.guarantorCard').append('<tr><td>'+$g+'</td>\
                    <td><img class="card-img-top" src="' + $avtarURL + '" alt="' + guarantor.first_name + '"></td>\
                    <td>' + guarantor.owner+ '</td>\
                    <td>' + guarantor.first_name + ' ' + guarantor.last_name + '</td>\
                    <td>'+guarantor.salary+'</td>\
                    <td>'+guarantor.relationship+'</td>\
                    <td>'+guarantor.phone+'</td>\
                    <td>'+$status+'</td>\
                    <td>'+guarantor.occupation+'</td>\
                    <td>'+guarantor.bvn+'</td>\
                    <td>'+guarantor.address+'</td>\
                    <td>'+guarantor.other_remarks+'</td>\
                    <td><a target="_blank" href="'+$signature+'" class="btn btn-warning"> Signature</a></td>\
                    </tr>\
                    ');
                });
                $sn=0;
                $borrower.forEach(function(borrowers) {
                    $avatar = borrowers.profile_pic;
                    $avatar = ($avatar) ? $avatar.replace('public', 'storage') : null;
                    $sn++;
                    $avtarURL = ($avatar) ? window.location.origin+'/' + $avatar : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                    $('.borrowerCard').append('<tr><td>'+$sn+'</td><td><img class="card-img-top" src="' + $avtarURL + '" alt="' + borrowers.first_name + '"  style="width:30px;"></td><td>' + borrowers.first_name + ' ' + borrowers.last_name + '</td><td>' + borrowers.virtual_account + '</td></tr>');
                });
                $('#flatTbody').empty();
                $('#repaymentTbody').empty();
                $loanRepayment.forEach(function(repay) {
                    if ($('#loan_type').val() == 1) {
                        $('.flat-table').show();
                        $('.repayment-table').hide();
                        $('#flatTbody').append('\
                        <tr>\
                        <td>' + repay.principal + '</td>\
                        <td>' + repay.repayI + '</td>\
                        <td>' + repay.I + '</td>\
                        <td>' + repay.PI + '</td>\
                        <td>' + repay.principal_return + '</td>\
                        <td>' + repay.duration + '</td>\
                        </tr>\
                        ');
                    } else if ($('#loan_type').val() == 2) {
                        $('.flat-table').hide();
                        $('.repayment-table').show();

                        $('#repaymentTbody').append('\
                        <tr>\
                        <td>' + repay.loan_balance + '</td>\
                        <td>' + repay.payment_amount + '</td>\
                        <td>' + repay.rate + '</td>\
                        <td>' + repay.principal_reduction + '</td>\
                        <td>' + repay.I + '</td>\
                        <td>' + repay.duration + '</td>\
                        </tr>\
                        ');
                    }
                });
                NextForm();
            } else {

                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            }
            stopLoadingButton('loanNextBTN', 'Process');
            enableBtn('loanNextBTN');
            stopLoadingButton('SubmitLoanBTN', 'Submit');
            enableBtn('SubmitLoanBTN');
        }
    });
    return false;
}
$('#loanNextBTN').on('click', function() {
    nextProcess();
});
$('#add-loan-book').on('submit', function() {
    saveLoan();
    return false;
});
function saveLoan(){
    var formData = $('#add-loan-book').serialize();
    disableBtn('SubmitLoanBTN');
    startLoadingButton('SubmitLoanBTN');
    $.ajax({
        url: '/company/loan/PostBookLoan',
        type: 'post',
        data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {
                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
                resetLoan();
            } else {
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            }
            stopLoadingButton('SubmitLoanBTN', 'Submit');
            enableBtn('SubmitLoanBTN');
        }
    });
    return false;
}
function prevForm() {
    bookingform2.style.display='none'
    bookingform.style.display='block'
}
function NextForm() {
    bookingform.style.display='none'
    bookingform2.style.display='block'
}
function resetLoan(){
    $('#exampleModal').modal('toggle');
    prevForm();
    $('#add-loan-book')[0].reset();
}

