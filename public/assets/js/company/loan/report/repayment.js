function searchreport(){
    $from = $('#from').val();
    $to = $('#to').val();
    $date_type = $('#date_type').val();
    if($from > $to){
        alert("From date should be lesser than to");
        return false;
    }
    if($date_type.length == 0){
        alert("Select Date Type");
        return false;
    }
    $staff= $('#officer').val();
    $branch= $('#branch').val();
    $('#html5-extension').DataTable().ajax.url("/company/loan/loan-repayment-report?from="+$from+"&to="+$to+"&branch="+$branch+"&officer="+$staff+"&date_type="+$date_type).load();
}
function remarkText($string){
    $('#stringModal').modal('show');
    $('.repayString').html($string);
}
function reverserepayment(){
    $from = $('#rfrom').val();
    $to = $('#rto').val();
    $ref = $('#loanid').val();

    if($from > $to){
        alert("From date should be lesser than to");
        return false;
    }
    ajax_operation('post', '/company/loan/reverse-all', {from:$from,to:$to,loanid:$ref});

}
function showReversalModule(){
    $('.groupdate').toggle();
}showReversalModule();
