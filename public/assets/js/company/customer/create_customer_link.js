$(".success_page").hide();
$(".fail_page").hide();
$(".public_customer_form").on('submit', function () {

    swal({
        title: 'Please wait!',
        text: 'Creating customer account',
        allowOutsideClick: false,
        allowEscapeKey: false,
        padding: '2em',
        onOpen: function () {
            swal.showLoading()
        }
    })

    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/company/process-customer-registration',
        data: new FormData(this),
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: (data) => {

            swal.close()

            $('.toast').removeClass("bg-success");
            $('.toast').removeClass("bg-danger");
            $(".toast-msg").html(data.message);
            if (data.status == 1) {
                data.status = 'bg-success';
                $(".success_page").show();
                $('#createAccountDiv').hide();
                $(".fail_page").hide();
                $(".accoutno").html(data.account_number);
                $(".bank").html(data.bank);
                // $(".password").html(data.password);
                $(".msg").html(data.desc);
                $("#examplez")[0].reset();
            } else if (data.status == 0) {
                data.status = 'bg-danger';
                $(".fail_page").show();
                $(".success_page").hide();
                $(".msg").html(data.message);
            }
            // $("html, body").animate({ scrollTop: $(document).height() }, 1000);


            $('.toast').addClass(data.status);
            $('.token').val(data.token);
        },
        error: function (jqXHR, status, error) {
            swal.close()

            $('.toast').addClass("bg-danger");
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connected\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else {
                msg = 'Uncaught Error.\n' + error;
            }

            $('.toast-msg').html(msg);
        }
    }).done(function () {
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');
    return false;
});
