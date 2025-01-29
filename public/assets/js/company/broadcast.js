
function fetch_user(){
    $audience = $('#audience').val();
    $link = '/userDataSelect';
    if($audience == '1'){
        $link = '/userDataSelect';
    }else if($audience == '2'){
        $link = '/staffDataSelect';
    }

    $('#user').select2({
        placeholder: 'Select User',
        ajax: {
            url: $link,
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
}
// new SimpleMDE({
//     element: document.getElementById("message"),
//     spellChecker: false,
//     autosave: {
//         enabled: true,
//         unique_id: "message",
//     },
// });
// var simplemde = new SimpleMDE({ element: $("#message")[0] });
// var limit = 10;

function swapSMSEmail($option){

    if($option == 'email'){
        $('#sms-txt-area').hide();
        $('#basic').show();

    }else{
        $('#sms-txt-area').show();
        $('#basic').hide();
    }
}swapSMSEmail('all');
function broadcast() {
    $audience = $('#audience').val();
    $user = $('#user').val();
    $option = $('#option').val();
    $subject= $('#subject').val();
    if($option == 'email'){
        $message =$('#editor-container').html();
        $character_count = $message.length;
    }else{
        $message =$('#txtarea-text').val();
        $character_count = $message.length;
    if (($character_count > 300)) {
        $takeout = $character_count - 300;

        alert("Maximum Character length for option including SMS is 300, takeout "+$takeout+" Character");
        $('.count-msg').html("<div style='color:red;'>Maximum Character length for option including SMS is 300, takeout "+$takeout+" Character</div>");
        return false;
    }
    }
    if (($character_count == 0) ) {

        alert("Enter message");
        return false;
    }
    swal({
        title: 'Are you sure, you want to send this broadcast message?',
        text: "Note: you will be charge for SMS.",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        padding: '2em'
    }).then(function(result) {
        if (result.value) {
            ajax_operation('post', '/company/send-broadcast', {audience:$audience,user:$user,option:$option,message :$message,subject:$subject },'broadcastBTN','Send');
        }
    });
       return false;
    }
