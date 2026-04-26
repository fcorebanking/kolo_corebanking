@extends('layouts.app')

@section('content')
    <div class="layout-px-spacing">

        <div class="row" id="cancel-row">

            <div class="col-lg-12 layout-spacing layout-top-spacing">
                <div class="statbox widget box box-shadow">
                    <div class="widget-header">
                        <div class="row">

                            <div class="col-md-12 mt-4">
                                <h4 style="color: black;margin: 0 auto;"><span>Bal</span>: {!! Auth()->user()->company->currency->symbol !!}
                                    <span id="companyWalletBalance">
                                        {{ moneyFormat(Auth()->user()->company->wallet->subscription_balance) }}</span>
                                </h4>
                                <a class="btn btn-primary" target="_blank" href="{{ $paymentLink }}">Topup Wallet</a>

                                <h4 style="margin-top: 20px;font-weight:normal; margin-bottom:0px;padding-bottom:5px;">
                                    Company Account No;</h4>
                                <h3 style="margin-top: 0px;"><b>{{ auth()->user()->company->wallet->subscription_vaccount }}
                                        - Kuda MFB</b></h3>
                            </div>

                            <div class="col-xl-12 mt-3">
                                <h3><b>Subscription Manager</b></h3>
                            </div>
                        </div>
                    </div>
                    <div class="widget-content widget-content-area">

                        <div class="alert alert-info">
                            <h5>Exchange Rate:</h5>
                            <h4><b> 1 USD -> <span id="exchangeAmount"></span>
                                    {{ auth()->user()->company->currency->code }}</span></b></h4>
                        </div>

                        <div class="container">

                            <!-- Billing Cycle  -->
                            <div class="billing-cycle-radios mt-5">
                                <div class="radio billed-yearly-radio">
                                    <div class="d-flex justify-content-center">
                                        <span class="txt-monthly">Monthly</span>
                                        <label class="switch s-icons s-outline  s-outline-success">
                                            <input type="checkbox" id="radio-6">
                                            <span class="slider round"></span>
                                        </label>

                                        <span class="txt-yearly">Yearly </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Plans Container -->
                            <div class="pricing-plans-container mt-5 d-md-flex d-block">
                                <!-- Plan -->
                                @if ($plans)
                                    <div class='row'>
                                        @foreach ($plans as $plan)
                                            <div class='col-xl-4 col-md-4 col-sm-6'>

                                                <?php $amtmonth = $plan->daily_rates * 31; ?>
                                                <div
                                                    class="pricing-plan mb-5 @if ($plan->recommended) mt-md-0  recommended @endif">
                                                    @if ($plan->recommended)
                                                        <div class="recommended-badge">Most Popular</div>
                                                    @endif
                                                    <h3>{{ $plan->plan_name }} </h3>
                                                    <p class="margin-top-10">{{ $plan->desc }}</p>
                                                    <div class="pricing-plan-label billed-monthly-label">
                                                        <strong>${{ $amtmonth }}</strong>/ monthly
                                                    </div>
                                                    <div class="pricing-plan-label billed-yearly-label">
                                                        <strong>${{ $amtmonth * 12 }}</strong>/ yearly <br>
                                                        @if ($plan->discount_percent > 0.0)
                                                            <span
                                                                class="badge badge-pill badge-success">{{ $plan->discount_percent .
                                                                    '%
                                                                                                                                                                                Off' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="pricing-plan-features mb-4">
                                                        <strong>Plan Features</strong>
                                                        <ul>

                                                            <li>Max Customers : {{ $plan->max_customers }}
                                                            </li>
                                                            <li>Max Staff : {{ $plan->max_staff }}
                                                            </li>
                                                            <li>Max Branch : {{ $plan->max_branch }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <button class="btn btn-primary plan-btn"
                                                        @if ($plan->daily_rates > 0) onclick="showPlanDuration('{{ $plan->id }}','{{ $plan->daily_rates }}','{{ $plan->discount_percent }}')" @endif
                                                        class="button btn btn-dark btn-block
                                            margin-top-20">
                                                        @if (auth()->user()->company->sub)
                                                            @if (auth()->user()->company->sub->plan_id < $plan->id)
                                                                Upgrade
                                                            @elseif(auth()->user()->company->sub->plan_id == $plan->id)
                                                                Renew
                                                            @elseif(auth()->user()->company->sub->plan_id > $plan->id)
                                                                DownGrade
                                                            @else
                                                                Subscribe
                                                            @endif
                                                        @else
                                                            Subscribe
                                                        @endif
                                                    </button>
                                                </div>

                                            </div>
                                        @endforeach
                                    </div>
                                @endif



                            </div>



                        </div>

                    </div>
                </div>
            </div>


        </div>

    </div>
    <!-- Modal -->
    <div class="modal fade " id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">

                <form action='/company/plan/sub' method='post'>

                    {{ method_field('PUT') }}
                    {{ csrf_field() }}
                    <div class="modal-body">
                        <p class="modal-text">
                            Enter Quantity or multiple of the plan u want
                        </p>
                        <input type='hidden' id='initamount' class='form-control' required>
                        <input type='hidden' id='discount' class='form-control' required>
                        <h3> <span class='amountp'></span> => {{ auth()->user()->company->currency->symbol }}<span
                                id='exchangeAmountModal'></span></h3>
                        <p> <span class="badge badge-pill badge-success discount" onclick='discount()'></span></p>
                        <input type='hidden' name='id' id='planid' required>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon5">Month</span>
                            </div>
                            <input type='number' onkeyup='updateamt()' min='1' name='qrt' id='qrt'
                                value='1' class='form-control' required>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                        <button type="submit" id="payNowBtn" onclick="submitForm(this);"
                            class="btn btn-primary">Pay</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="{{ asset('assets/js/libs/jquery-3.1.1.min.js') }}"></script>
    <script>
        function submitForm(btn) {
            // disable the button
            btn.disabled = true;
            // submit the form    
            btn.form.submit();
        }

        function showPlanDuration(id, amount, discount) {
            $('#qrt').val(1);
            $('#planid').val(id);
            $('#initamount').val(amount * 31);
            $('#discount').val(discount);

            console.log(amount * 31);

            if (discount > 0) {
                $('.discount').show();
                $('.discount').html('Click for ' + discount + '% discount');
            } else {
                $('.discount').hide();
            }

            $newAmount = amount * 31;
            $('.amountp').html('$' + ($newAmount.toFixed(2)));

            var exchangeAmount = $('#exchangeAmount').text();

            console.log("Exchange amount is " + exchangeAmount);


            var newRate = exchangeAmount * amount * 31;


            $("#exchangeAmountModal").text(moneyFormat(newRate));

            $('#exampleModal').modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });

        };

        function discount() {
            $('#qrt').val(12);
            updateamt();
        }

        function updateamt() {


            var amt = Number($('#initamount').val());
            var duration = Number($('#qrt').val());
            var discount = $('#discount').val();
            var exchangeAmount = $('#exchangeAmount').text();

            console.log("duration is: " + duration);

            if (duration < 1) {
                $('#payNowBtn').prop('disabled', true);
            } else {
                $('#payNowBtn').prop('disabled', false);
            }

            if ($('#qrt').val() == "") {
                console.log("There's nothing here");
            }

            if (duration >= 12) {

                if (discount > 0) {
                    $topay = (discount / 100) * (amt * duration);

                } else {
                    $topay = amt * duration;
                }
            } else {
                $topay = amt * duration;
            }

            var newExchange = $topay * exchangeAmount;

            $('.amountp').html('$' + $topay.toFixed(2));
            $("#exchangeAmountModal").text(moneyFormat(newExchange));


        }

        $(document).ready(function() {

            console.log("making request");

            $('.plan-btn').prop('disabled', true);

            $.ajax({
                url: '/company/transaction/get-current-exchange-rates',
                type: 'post',
                data: {
                    countryCurrency: 'USD',
                    exchangeAmount: 1,
                    isInverted: true
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    console.log(data);
                    $('#exchangeAmount').text(data.data.amount);
                    $('.plan-btn').attr('disabled', false);
                },
                error: function(jqXHR, status, error) {

                    console.log(jqXHR);

                    console.log(status);

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

                    console.log(msg);

                },
                xhr: function() {
                    var xhr = $.ajaxSettings.xhr();
                    xhr.onprogress = function e() {
                        // For downloads
                        if (e.lengthComputable) {
                            console.log(e.loaded / e.total);
                            $(".toast-msg").html(e.loaded / e.total);
                        }
                    };
                    xhr.upload.onprogress = function(e) {
                        // For uploads
                        if (e.lengthComputable) {
                            console.log(e.loaded / e.total);
                        }
                    };
                    return xhr;
                }
            }).done(function() {
                $('.toast').addClass("bg-success");
            });

        });
    </script>
@endsection
