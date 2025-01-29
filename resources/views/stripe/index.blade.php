<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top up USD wallet</title>
</head>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:weight@100;200;300;400;500;600;700;800&display=swap");
    .card{

    border: none;
    }
    .hide{
    display:none;
    }
    .card-header {
        padding: .5rem 1rem;
        margin-bottom: 0;
        background-color: rgba(0,0,0,.03);
        border-bottom: none;
    }

    .btn-light:focus {
        color: #212529;
        background-color: #e2e6ea;
        border-color: #dae0e5;
        box-shadow: 0 0 0 0.2rem rgba(216,217,219,.5);
    }

    .form-control{

    height: 50px;
    border: 2px solid #eee;
    border-radius: 6px;
    font-size: 14px;
    }

    .form-control:focus {
    color: #495057;
    background-color: #fff;
    border-color: #039be5;
    outline: 0;
    box-shadow: none;

    }

    .input{

    position: relative;
    }

    .input i{

    position: absolute;
    top: 16px;
    left: 11px;
    color: #989898;
    }

    .input input{

    text-indent: 25px;
    }

    .card-text{

    font-size: 13px;
    margin-left: 6px;
    }

    .certificate-text{

    font-size: 12px;
    }


    .billing{
    font-size: 11px;
    }

    .super-price{

    top: 0px;
    font-size: 22px;
    }

    .super-month{

    font-size: 11px;
    }


    .line{
    color: #bfbdbd;
    }

    .free-button{

    background: #1565c0;
    height: 52px;
    font-size: 15px;
    border-radius: 8px;
    }


    .payment-card-body{

    flex: 1 1 auto;
    padding: 24px 1rem !important;

    }
</style>

<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-6 p-3">
            @if (Session::has('success'))
                <div class="alert alert-success mb-2">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
                    <p class="mb-0">{{ Session::get('success') }}</p>
                </div>
            @endif
            <h3 class="panel-title display-td" >Payment Information</h3>
            <p class=" display-td" >Top up your USD wallet</p>
            <br/>
            <form role="form"  action="{{ route('stripe.post') }}" method="post" class="require-validation border p-3" data-cc-on-file="false" data-stripe-publishable-key="{{ env('STRIPE_PUBLIC') }}" id="payment-form">
                @csrf
                <input type="hidden" class="form-control" name="virtual_account" value='{{$_GET["virtual_account"]??null}}'>
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="text" class="form-control amount" size="20" name="amount" autocomplete='off' placeholder="">
                </div>
                <div class="mb-3">
                    <label for="card-name" class="form-label">Name on Card</label>
                    <input type="text" class="form-control card-name" size="4" id="card-name" placeholder="">
                </div>
                <div class="mb-3">
                    <label for="card-number" class="form-label">Card Number</label>
                    <input type="text" class="form-control card-number" size="20" id="card-number" autocomplete='off' placeholder="">
                </div>
                <div class="row p-3">
                    <div class="p-1 col-xs-12 col-md-4 mb-3">
                        <label for="card-cvc" class="form-label">CVC</label>
                        <input type="text" class="form-control card-cvc" size="4" id="card-cvc" autocomplete='off' placeholder="ex. 311">
                    </div>
                    <div class="p-1 col-xs-12 col-md-4 mb-3">
                        <label for="card-expiry-month" class="form-label">Expiration Month</label>
                        <input type="text" class="form-control card-expiry-month" size="2" id="card-expiry-month" placeholder="MM">
                    </div>
                    <div class="p-1 col-xs-12 col-md-4 mb-3">
                        <label for="card-expiry-year" class="form-label">Expiration Year</label>
                        <input type="text" class="form-control card-expiry-year" size="4" id="card-expiry-year" placeholder="YYYY">
                    </div>
                </div>
                <div class='form-row row'>
                    <div class='col-md-12 error form-group hide'>
                        <div class='alert-danger alert'>Please correct the errors and try again.</div>
                    </div>
                </div>
                <div class="row p-3">
                    <div class="col-xs-12">
                        <ul>
                            <li>Added Service Charge 3.3%</li>
                        </ul>
                        <button class="btn btn-primary btn-lg" type="submit" style="width:100%;">Top up</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script type="text/javascript" src="https://js.stripe.com/v2/"></script>

<script type="text/javascript">
$(function() {
    var $form         = $(".require-validation");
  $('form.require-validation').bind('submit', function(e) {
  $('.btn-primary').html('Loading')
    var $form         = $(".require-validation"),
        inputSelector = ['input[type=email]', 'input[type=password]',
                         'input[type=text]', 'input[type=file]',
                         'textarea'].join(', '),
        $inputs       = $form.find('.required').find(inputSelector),
        $errorMessage = $form.find('div.error'),
        valid         = true;
        $errorMessage.addClass('hide');

        $('.has-error').removeClass('has-error');
    $inputs.each(function(i, el) {
      var $input = $(el);
      if ($input.val() === '') {
        $input.parent().addClass('has-error');
        $errorMessage.removeClass('hide');
        e.preventDefault();
      }
    });

    if (!$form.data('cc-on-file')) {
      e.preventDefault();
      Stripe.setPublishableKey($form.data('stripe-publishable-key'));
      Stripe.createToken({
        number: $('.card-number').val(),
        cvc: $('.card-cvc').val(),
        exp_month: $('.card-expiry-month').val(),
        exp_year: $('.card-expiry-year').val()
      }, stripeResponseHandler);
    }

  });

  function stripeResponseHandler(status, response) {
        if (response.error) {
            $('.error')
                .removeClass('hide')
                .find('.alert')
                .text(response.error.message);
                $('.btn-primary').html('Top Up')
        } else {
            // token contains id, last4, and card type
            var token = response['id'];
            // insert the token into the form so it gets submitted to the server
            $form.find('input[type=text]').empty();
            $form.append("<input type='hidden' name='stripeToken' value='" + token + "'/>");
            $form.get(0).submit();
            $('.btn-primary').html('Top Up')

        }
    }

});
</script>
