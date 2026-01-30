@extends('layouts.app')

@section('content')
    <style>
        .count-numbers {
            font-size: 5px;
        }
    </style>
    @php
        $auth = Auth()->user();
    @endphp

    <div class="layout-px-spacing">

        <div class="row layout-top-spacing">

            <div class="col-xl-12 col-lg-12 col-sm-12">
                <!--  <div class="widget-content widget-content-area">

                                <h3 style="margin-bottom: 30px;"></h3>

                                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
                                    <div class="col-md-6">
                                        <div class="clipboard">
                                            <form class="form-horizontal">
                                                <label for="input-copy"><strong>Customer Registration Link</strong></label>
                                                <input type="text" class="form-control mb-4" readonly id="input-copy"
                                                    value="{{ $registrationLink }}">

                                                <a class="mb-1 btn btn-primary" href="javascript:;" data-clipboard-action="copy"
                                                    data-clipboard-target="#input-copy"><i class="fa fa-clipboard"></i> Copy Link</a>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
            </div>

        </div>
        <style>
            .widget.widget-card-five .account-box .info-box {
                position: relative;
                /* Add the following property to hide the overflow */
                overflow: hidden;
            }

            .card-img {
                overflow: clip;
                text-align: left;
                position: relative;
                top: 0px;
                left: -2px;
                width: 50px;

            }

            .widget.widget-card-five .account-box .info-box .icon:before {
                content: "";
                background: #faf4ea;
                position: absolute;
                top: -29px;
                left: -34px;
                height: 100%;
                width: 50%;
                border-radius: 50%;
                overflow: hidden;
            }

            .widget.widget-card-five .account-box .info-box .balance-info {
                text-align: right;
            }

            .account-box {
                /* padding:2rem; */
            }

            .widget.widget-card-five .account-box .info-box {
                display: flex;
                justify-content: space-between;
            }

            .widget {
                /* position: relative; */
                padding: 10px;
                /* border-radius: 8px; */
                /* border: none; */
                /* background: #fff; */
            }

            .account-box {
                min-height: 17vh;
            }
        </style>
        @if (cando('root'))
            <button class='btn btn-primary' onclick='addwalletscript()'>Add Wallet</button>
            <div class="row " style='margin-top:2rem;'>
                @foreach ($wallet as $walletData)
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                        <div class="widget widget-card-five">
                            <div class="widget-content">

                                <div class="account-box">

                                    <div class="info-box">
                                        <div class="icon">
                                            <span>
                                                <img class='card-img' src="{{ asset('assets/dashboard/wallet.png') }}"
                                                    alt="money-bag">
                                            </span>
                                        </div>
                                        <div class="balance-info">
                                            <h6>{{ $walletData->currency_code }} Balance</h6>
                                            <p>{{ $walletData->balance }}</p>
                                        </div>
                                    </div>

                                    <div class="card-bottom-section">
                                        <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" class="feather feather-trending-up">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                    <polyline points="17 6 23 6 23 12"></polyline>
                                                </svg></span></div>
                                        @if ($walletData->currency_code != 'USD')
                                            <a href="#" onclick='topupCurrency("{{ $walletData->currency_code }}")'
                                                class="btn btn-warning">Top up {{ $walletData->currency_code }}</a>
                                        @else
                                            <a href="/admin-stripe-payment" class="btn btn-warning">Top up
                                                {{ $walletData->currency_code }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row " style='margin-top:2rem;'>
                @foreach ($closingBalance as $closingBalanceData)
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                        <div class="widget widget-card-five">
                            <div class="widget-content">

                                <div class="account-box">

                                    <div class="info-box">
                                        <div class="icon">
                                            <span>
                                                <img class='card-img' src="{{ asset('assets/dashboard/wallet.png') }}"
                                                    alt="money-bag">
                                            </span>
                                        </div>
                                        <div class="balance-info">
                                            <h6>Till Balance</h6>
                                            <p>{{ $closingBalanceData->currency }}{{ number_format($closingBalanceData->total) }}
                                            </p>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="modal displayTopup" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title topuptitle">Top Up Currency</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action='/admin-company-wallet-topup' method='post' id='walletform'>
                            {{ csrf_field() }}
                            <div class="modal-body">
                                <div class="addwallet">
                                    <div class='form-group'>
                                        <select class='form-control' name='currency'>
                                            <option value='ZMW'>ZMW</option>
                                            <option value='USD'>USD</option>
                                            <option value='XAF'>XAF</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="payments">
                                    <input type='hidden' name='currencytoTopup' id='currencytoTopup'>
                                    <div class='form-group'>
                                        <input type='number' name='amount' class='form-control' id='amounttoTopUp'
                                            placeholder='Enter amount to top up'>
                                    </div>
                                    <div class='form-group'>

                                        <input type='text' name='phone' class='form-control' id='phone'
                                            placeholder='Enter phone number to receive from'>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Proceed</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                function topupCurrency(currency) {
                    $('.payments').show();
                    $('.addwallet').hide();
                    $('.topuptitle').html(`Topup ${currency}`);
                    $('#currencytoTopup').val(currency);
                    $('#amounttoTopUp').val(0);
                    $('.displayTopup').modal('show');
                    $("#walletform").attr("action", '/admin-company-wallet-topup');
                }

                function addwalletscript() {
                    $('.payments').hide();
                    $('.addwallet').show();
                    $('.displayTopup').modal('show');
                    $('.topuptitle').html(`Add wallet`);

                    $("#walletform").attr("action", '/admin-company-wallet-add');

                }
            </script>

            <div class='row layout-spacing ' style='margin-top:2rem;'>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">

                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img class='card-img' src="{{ asset('assets/dashboard/wallet.png') }}"
                                                alt="money-bag">
                                        </span>
                                    </div>

                                    <div class="balance-info">
                                        <h6>Settlement Balance</h6>
                                        <p class="acc-amount"><i class="fa fa-spinner fa-spin"
                                                style="font-size:20px"></i></p>
                                    </div>
                                </div>

                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-trending-up">
                                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                <polyline points="17 6 23 6 23 12"></polyline>
                                            </svg></span></div>
                                    <a href="javascript:void(0);"
                                        class="">{{ $auth->company->wallet->bank->bank_name }} |
                                        {{ $auth->company->wallet->vaccount }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img class='card-img' src="{{ asset('assets/dashboard/whatsapp.png') }}"
                                                alt="money-bag">
                                        </span>
                                    </div>

                                    <div class="balance-info">
                                        <h6>Whatsapp</h6>
                                        <p class='whatsapp'><i class="fa fa-spinner fa-spin" style="font-size:20px"></i>
                                        </p>
                                    </div>
                                </div>

                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-trending-up">
                                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                <polyline points="17 6 23 6 23 12"></polyline>
                                            </svg></span></div>
                                    <a href='#' class="" data-toggle="modal" data-target="#exampleModal"
                                        data-whatever="@mdo">Topup Whatsapp</a>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img class='card-img' src="{{ asset('assets/dashboard/ussd.png') }}"
                                                alt="money-bag">
                                        </span>
                                    </div>

                                    <div class="balance-info">
                                        <h6>Dollar Vault</h6>
                                        <p class='ussd'><i class="fa fa-spinner fa-spin" style="font-size:20px"></i>
                                        </p>
                                    </div>
                                </div>

                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-trending-up">
                                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                <polyline points="17 6 23 6 23 12"></polyline>
                                            </svg></span></div>
                                    <a href='#' class="" data-toggle="modal" data-target="#exampleModal"
                                        data-whatever="@mdo">Topup Dollar Vault</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img class='card-img' src="{{ asset('assets/dashboard/sms.png') }}"
                                                alt="money-bag">
                                        </span>
                                    </div>

                                    <div class="balance-info">
                                        <h6>SMS Balance</h6>
                                        <p class='sms'><i class="fa fa-spinner fa-spin" style="font-size:20px"></i>
                                        </p>
                                    </div>
                                </div>
                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-trending-up">
                                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                <polyline points="17 6 23 6 23 12"></polyline>
                                            </svg></span></div>
                                    <a href='#' class="" data-toggle="modal" data-target="#exampleModal"
                                        data-whatever="@mdo">Topup SMS</a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img class='card-img' src="{{ asset('assets/dashboard/wallet.png') }}"
                                                alt="money-bag">
                                        </span>
                                    </div>

                                    <div class="balance-info">
                                        <h6>Naira Card Balance</h6>
                                        <p class='card-balance'><i class="fa fa-spinner fa-spin"
                                                style="font-size:20px"></i></p>
                                    </div>
                                </div>
                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-trending-up">
                                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                <polyline points="17 6 23 6 23 12"></polyline>
                                            </svg></span></div>
                                    <a href='#' class="" data-toggle="modal" data-target="#exampleModal"
                                        data-whatever="@mdo">Topup Naira Card</a>
                                </div>



                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img class='card-img' src="{{ asset('assets/dashboard/wallet.png') }}"
                                                alt="money-bag">
                                        </span>
                                    </div>

                                    <div class="balance-info">
                                        <h6>Ghana (GHS)</h6>
                                        <p class='card-balance'><i class="fa fa-spinner fa-spin"
                                                style="font-size:20px"></i></p>
                                    </div>
                                </div>
                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-trending-up">
                                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                <polyline points="17 6 23 6 23 12"></polyline>
                                            </svg></span></div>
                                    <a href='#' class="" data-toggle="modal" data-target="#exampleModal"
                                        data-whatever="@mdo"></a>
                                </div>



                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img class='card-img' src="{{ asset('assets/dashboard/wallet.png') }}"
                                                alt="money-bag">
                                        </span>
                                    </div>

                                    <div class="balance-info">
                                        <h6>UK (GBP)</h6>
                                        <p class='card-balance'><i class="fa fa-spinner fa-spin"
                                                style="font-size:20px"></i></p>
                                    </div>
                                </div>
                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-trending-up">
                                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                <polyline points="17 6 23 6 23 12"></polyline>
                                            </svg></span></div>
                                    <a href='#' class="" data-toggle="modal" data-target="#exampleModal"
                                        data-whatever="@mdo"></a>
                                </div>



                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <button class="btn btn-primary starterbtn" style="height: 50px; margin-top: 10px" onclick="starter()">Initiate
            Dashboard</button>

        <div class='all-dashboard'>
            @if (cando('advance-dashboard|root'))
                <div class="row" id="loadDashboardButtonDiv" style="height: 100px; justify-content: center;">
                    <button class="btn btn-primary" id="loadDashboardBtn" style="height: 50px; margin-top: 10px"
                        onclick="load_data_full()"> Load Full Dashboard</button>
                </div>
            @endif
            <span class='loadingdash'></span>
            <div class="row layout-top-spacing">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">

                    @if (!cando('advance-dashboard|root'))
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-md-3">
                                <div class="card-counter success">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers today-deposit-total"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Today's Credit</span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card-counter danger">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers today-withdraw-total"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Today's Debit</span>
                                </div>
                            </div>

                            {{-- <div class="col-md-3">
            <div class="card-counter info">
              <i class="fa fa-dollar"></i>
              <span style="font-size:20px" class="count-numbers system-balance"><i class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
              <span class="count-name">System Balance</span>
            </div>
          </div> --}}

                            <div class="col-md-3">
                                <div class="card-counter primary">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers vault-balance"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">vault Balance</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card-counter info">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers my-commission"
                                        id='staff_comission'>{{ $auth->staff_wallet->commission }}</span>
                                    <span class="count-name">Comission</span>
                                </div>
                            </div>

                        </div>
                        <script>
                            function formatToCurrency(number, decPlaces = 2, decSep = '.', thouSep = ',') {

                                var value = (number).toLocaleString(
                                    undefined, // leave undefined to use the visitor's browser
                                    // locale or a string like 'en-US' to override it.
                                    {
                                        minimumFractionDigits: 2
                                    }
                                );

                                return value;
                            }
                            let commison = "{{ $auth->company->currency->symbol }}" + formatToCurrency(Number(
                                "{{ $auth->staff_wallet->commission }}"));
                            document.getElementById('staff_comission').innerHTML = commison;
                            //   $('.staff_comission').html()
                        </script>
                    @endif

                    @if (cando('advance-dashboard|root'))
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card-counter primary">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers deposit-total"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Total Deposit</span>
                                </div>
                            </div>
                            @if (cando('root'))
                                <div class="col-md-3">
                                    <div class="card-counter primary">
                                        <i class="fa fa-dollar"></i>
                                        <span style="font-size:20px" class="count-numbers system-balance"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">System Balance</span>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-3">
                                <div class="card-counter danger">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers withdraw-total"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Total Withdraw</span>
                                </div>
                            </div>

                            @if (cando('root'))
                                <div class="col-md-3">
                                    <div class="card-counter info">
                                        <i class="fa fa-dollar"></i>
                                        <span style="font-size:20px" class="count-numbers total-branches"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Total Branches</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="row" id="fulDashboardDiv" style="display: none; margin-bottom: 10px;">

                            <div class="col-md-3">
                                <div class="card-counter info">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers deposit-charge"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Deposit Charge</span>
                                </div>
                            </div>

                            @if (cando('root'))
                                <div class="col-md-3">
                                    <div class="card-counter warning">
                                        <i class="fa fa-dollar"></i>
                                        <span style="font-size:20px" class="count-numbers withdraw-charge"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Withdrawal Charge</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter warning">
                                        <i class="fa fa-dollar"></i>
                                        <span style="font-size:20px" class="count-numbers ewithdraw-charge"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">E-Withdrawal Charge</span>
                                    </div>
                                </div>




                                <div class="col-md-3">
                                    <div class="card-counter secondary">
                                        <i class="fa fa-envelope"></i>
                                        <span style="font-size:20px" class="count-numbers unpaid-sms"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Unpaid SMS</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card-counter success">
                                        <i class="fa fa-envelope"></i>
                                        <span style="font-size:20px" class="count-numbers sms-charge"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">SMS Charge</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card-counter info">
                                        <i class="fa fa-users"></i>
                                        <span style="font-size:20px" class="count-numbers reg-charge"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Entry Fee</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card-counter warning">
                                        <i class="fa fa-cash"></i>
                                        <span style="font-size:20px" class="count-numbers staff_comission">0</span>
                                        <span class="count-name">Staff Comission</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter warning">
                                        <i class="fa fa-cash"></i>
                                        <span style="font-size:20px" class="count-numbers staff_balance">0</span>
                                        <span class="count-name">Staff Balance</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter warning">
                                        <i class="fa fa-cash"></i>
                                        <span style="font-size:20px" class="count-numbers expenses">0</span>
                                        <span class="count-name">Total Expenses</span>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-3">
                                <div class="card-counter danger">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers dom_account"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Dom Account</span>
                                </div>
                            </div>
                            @if (cando('root'))
                                <div class="col-md-3">
                                    <div class="card-counter success">
                                        <i class="fa fa-dollar"></i>
                                        <span style="font-size:20px" class="count-numbers total_investment"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Total Investment</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter secondary">
                                        <i class="fa fa-dollar"></i>
                                        <span style="font-size:20px" class="count-numbers complete_investment"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Complete Investment</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter primary">
                                        <i class="fa fa-dollar"></i>
                                        <span style="font-size:20px" class="count-numbers active_investment"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Active Investment</span>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-3">
                                <div class="card-counter primary">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers esusu_count"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Total Active Esusu</span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card-counter primary">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers esusu_wallet"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Esusu Wallet </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card-counter primary">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers esusu_withdrawable"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Esusu Withdrawable</span>
                                </div>
                            </div>
                            {{-- <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span style="font-size:20px" class="count-numbers esusu_pending_charge"><i class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
            <span class="count-name">Esusu Pending Charges</span>
          </div>
        </div> --}}

                            <div class="col-md-3">
                                <div class="card-counter success">
                                    <i class="fa fa-user"></i>
                                    <span style="font-size:20px" class="count-numbers pending_entry"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Pending Entry</span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card-counter success">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers auth_wallet"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">My Wallet</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card-counter primary">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers today-deposit-total"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Today Credit</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card-counter bg-dark p-2" style="--bs-bg-opacity: .5;">
                                    <i class="fa fa-dollar"></i>
                                    <span style="font-size:20px" class="count-numbers today-withdraw-total"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Today Debit</span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card-counter bg-success  p-2" style="--bs-bg-opacity: .5;">
                                    <i class="fa fa-user"></i>
                                    <span style="font-size:20px" class="count-numbers link_cards"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Linked Card</span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card-counter bg-warning p-2" style="--bs-bg-opacity: .5;">
                                    <i class="fa fa-user"></i>
                                    <span style="font-size:20px" class="count-numbers pending_card"><i
                                            class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                    <span class="count-name">Pending Card</span>
                                </div>
                            </div>
                            {{-- <div class="col-md-3">
            <div class="card-counter warning">
              <i class="fa fa-user"></i>
              <span style="font-size:20px" class="count-numbers staff_comission">0</span>
              <span class="count-name">Staff Comission</span>
            </div>
          </div> --}}
                            @if (cando('root'))
                                <div class="col-md-3">
                                    <div class="card-counter info">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers completeCount"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Complete Loan</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-primary">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers pendingCount"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Pending Loan</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-danger">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers disbursmentCount"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Pending Disbused</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card-counter bg-secondary">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers writeOff"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Written Off Loan</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-danger">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers loanActiveCount"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Active Loan</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-warning">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers InitPamount"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Total Principal</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-light">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers TInterestLeft"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Interest Left</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-dark">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers TprincipalLeft"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Principal Left</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-secondary p-2" style="--bs-bg-opacity: .5;">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers TloanBalance"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Loan Balance</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter info">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers paidamount"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Total Loan Paid</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-success p-2" style="--bs-bg-opacity: .5;">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers Ppaidamount"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Loan P Paid</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-primary p-2" style="--bs-bg-opacity: .5;">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers Ipaidamount"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Loan I Paid</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-info p-2" style="--bs-bg-opacity: .5;">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers loanFeePaid"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Loan Fee</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card-counter bg-dark p-2" style="--bs-bg-opacity: .5;">
                                        <i class='fas fa-coins'></i>
                                        <span style="font-size:20px" class="count-numbers mgrtFeePaid"><i
                                                class="fa fa-spinner fa-spin" style="font-size:20px"></i></span>
                                        <span class="count-name">Loan Mgrt Fee</span>
                                    </div>
                                </div>
                            @endif

                        </div>
                    @endif


                    @if (cando('advance-dashboard|root'))
                        <div class='row savingsdata' style="margin-bottom: 20px;">

                        </div>
                    @endif

                    <div class="row">
                        <div class="col-xl-8 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                            <div class="widget widget-chart-one">
                                <div class="widget-heading">
                                    <h5 class="">Transaction</h5>
                                    <ul class="tabs tab-pills">
                                        <li><a href="javascript:void(0);" id="tb_1" class="tabmenu">Monthly</a></li>
                                    </ul>
                                </div>

                                <div class="widget-content">
                                    <div class="tabs tab-content">
                                        <div id="content_1" class="tabcontent">
                                            <div id="revenueMonthly"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if (cando('root'))
                            <div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                                <div class="widget widget-chart-two">
                                    <div class="widget-heading">
                                        <h5 class="">Users</h5>
                                    </div>
                                    <div class="widget-content">
                                        <div id="chart-2" class=""></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if (cando('root'))
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="widget widget-table-one">
                                        <div class="widget-heading">
                                            <h5 class="">Branch</h5>
                                        </div>

                                        <div class="widget-content branch_data"><i class="fa fa-spinner fa-spin"
                                                style="font-size:20px"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">

                                    <div class="widget widget-account-invoice-one">

                                        <div class="widget-heading">
                                            <h5 class="">Company Wallet Info</h5>
                                        </div>

                                        <div class="widget-content">
                                            <div class="invoice-box">

                                                <div class="acc-total-info">
                                                    <h5>Balance</h5>
                                                    <p class="acc-amount"></p>
                                                    <p class="">{{ $auth->company->wallet->bank->bank_name }}</p>
                                                    <p class="">{{ $auth->company->wallet->vaccount }}</p>
                                                    <b>CGate USSD Code</b>
                                                    <p><span class="badge badge-primary "><a class='text-light'
                                                                href="tel:*bank_ussd*000*621+{{ padAccountCode($auth->company->id) }}+AMOUNT#">
                                                                *bank_ussd*000*621+{{ padAccountCode($auth->company->id) }}+AMOUNT#</a></span>
                                                    </p>
                                                </div>

                                                <div class="inv-detail">
                                                    <div class="info-detail-1">
                                                        <p>SMS</p>
                                                        <p class='sms'><i class="fa fa-spinner fa-spin"
                                                                style="font-size:20px"></i></p>
                                                    </div>
                                                    <div class="info-detail-2">
                                                        <p>USSD</p>
                                                        <p class='ussd'><i class="fa fa-spinner fa-spin"
                                                                style="font-size:20px"></i></p>
                                                    </div>
                                                    <div class="info-detail-3 info-sub">
                                                        <div class="info-detail">
                                                            <p>Whatsapp</p>
                                                            <p class='whatsapp'><i class="fa fa-spinner fa-spin"
                                                                    style="font-size:20px"></i></p>
                                                        </div>

                                                    </div>
                                                </div>

                                                <div class="inv-action">

                                                    <button type="button" class="btn btn-primary" data-toggle="modal"
                                                        data-target="#exampleModal" data-whatever="@mdo">Topup</button>
                                                    <a href="{{ url('company/' . $auth->company->short_name . '/transaction/fund-wallet') }}"
                                                        class="btn btn-info"> Fund Wallet</a>

                                                    @if ($auth->company->ussd_string == null)
                                                        <button type="button" class="btn btn-warning gen_ussd_btn"
                                                            onclick="generate_ussd_string({{ config('app.ussd_generate_charge') }})">Generate
                                                            USSD Code
                                                        </button>
                                                    @else
                                                        <a href="tel:{{ config('app.ussd_string') }}*{{ $auth->company->ussd_string }}#"
                                                            class="badge badge-warning ussd_btn">
                                                            {{ config('app.ussd_string') }}*{{ $auth->company->ussd_string }}#</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            {{-- <div class="widget widget-activity-four">

            <div class="widget-heading">
              <h5 class="">Recent Activities</h5>
            </div>

            <div class="widget-content">

              <div class="mt-container mx-auto">
                <div class="timeline-line">

                  <div class="item-timeline timeline-primary">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p><span>Updated</span> Server Logs</p>
                      <span class="badge badge-danger">Pending</span>
                      <p class="t-time">Just Now</p>
                    </div>
                  </div>

                  <div class="item-timeline timeline-success">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Send Mail to <a href="javascript:void(0);">HR</a> and <a href="javascript:void(0);">Admin</a></p>
                      <span class="badge badge-success">Completed</span>
                      <p class="t-time">2 min ago</p>
                    </div>
                  </div>

                  <div class="item-timeline  timeline-danger">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Backup <span>Files EOD</span></p>
                      <span class="badge badge-danger">Pending</span>
                      <p class="t-time">14:00</p>
                    </div>
                  </div>

                  <div class="item-timeline  timeline-dark">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Collect documents from <a href="javascript:void(0);">Sara</a></p>
                      <span class="badge badge-success">Completed</span>
                      <p class="t-time">16:00</p>
                    </div>
                  </div>

                  <div class="item-timeline  timeline-warning">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Conference call with <a href="javascript:void(0);">Marketing Manager</a>.</p>
                      <span class="badge badge-primary">In progress</span>
                      <p class="t-time">17:00</p>
                    </div>
                  </div>

                  <div class="item-timeline  timeline-secondary">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Rebooted Server</p>
                      <span class="badge badge-success">Completed</span>
                      <p class="t-time">17:00</p>
                    </div>
                  </div>

                  <div class="item-timeline  timeline-warning">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Send contract details to Freelancer</p>
                      <span class="badge badge-danger">Pending</span>
                      <p class="t-time">18:00</p>
                    </div>
                  </div>

                  <div class="item-timeline  timeline-dark">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Kelly want to increase the time of the project.</p>
                      <span class="badge badge-primary">In Progress</span>
                      <p class="t-time">19:00</p>
                    </div>
                  </div>

                  <div class="item-timeline  timeline-success">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Server down for maintanence</p>
                      <span class="badge badge-success">Completed</span>
                      <p class="t-time">19:00</p>
                    </div>
                  </div>

                  <div class="item-timeline  timeline-secondary">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Malicious link detected</p>
                      <span class="badge badge-warning">Block</span>
                      <p class="t-time">20:00</p>
                    </div>
                  </div>

                  <div class="item-timeline  timeline-warning">
                    <div class="t-dot" data-original-title="" title="">
                    </div>
                    <div class="t-text">
                      <p>Rebooted Server</p>
                      <span class="badge badge-success">Completed</span>
                      <p class="t-time">23:00</p>
                    </div>
                  </div>

                </div>
              </div>

              <div class="tm-action-btn">
                <button class="btn">View All <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" class="feather feather-chevron-down">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg></button>
              </div>
            </div>
          </div> --}}

                        </div>
                    @endif

                </div>


                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-table-one">
                        <div class="widget-heading">
                            <h5 class="">End of Day Transaction</h5>
                        </div>

                        <div class="widget-content transaction_type"><i class="fa fa-spinner fa-spin"
                                style="font-size:20px"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget-one">
                        <div class="widget-content">
                            <div class="w-numeric-value">
                                <div class="w-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="feather feather-globe">
                                        <circle cx="12" cy="12" r="10" />
                                        <line x1="2" y1="12" x2="22" y2="12" />
                                        <path
                                            d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                    </svg>
                                </div>
                                <div class="w-content">
                                    <span class="w-value total_daily"></span>
                                    <span class="w-numeric-title">Daily Transaction Volume</span>
                                </div>
                            </div>
                            <div class="w-chart">
                                <div id="total-orders"><i class="fa fa-spinner fa-spin" style="font-size:20px"></i></div>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-table-two">

                        <div class="widget-heading">
                            <h5 class="">Recent Transaction</h5>
                        </div>

                        <div class="widget-content">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="th-content">Customer</div>
                                            </th>
                                            <th>
                                                <div class="th-content">Account No.</div>
                                            </th>
                                            <th>
                                                <div class="th-content">Transaction Type</div>
                                            </th>
                                            <th>
                                                <div class="th-content">TXNID</div>
                                            </th>
                                            <th>
                                                <div class="th-content th-heading">Amount</div>
                                            </th>
                                            <th>
                                                <div class="th-content">Status</div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id='recent_txt'>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-table-three">

                        <div class="widget-heading">
                            <h5 class="">Top Transactions</h5>
                        </div>

                        <div class="widget-content">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="th-content">Customer</div>
                                            </th>
                                            <th>
                                                <div class="th-content">Account No.</div>
                                            </th>
                                            <th>
                                                <div class="th-content">Transaction Type</div>
                                            </th>
                                            <th>
                                                <div class="th-content">TXNID</div>
                                            </th>
                                            <th>
                                                <div class="th-content th-heading">Amount</div>
                                            </th>
                                            <th>
                                                <div class="th-content">Status</div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id='largest_txn'>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Wallet Funding</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class='alert alert-info'>
                        Note: To receive payment via USSD code into your wallet account use <p><span
                                class="badge badge-primary "><a class='text-light'
                                    href="tel:*bank_ussd*000*621+{{ padAccountCode($auth->company->id) }}+AMOUNT#">
                                    *bank_ussd*000*621+{{ padAccountCode($auth->company->id) }}+AMOUNT#</a></span></p>
                    </div>
                    <form>
                        <div class="form-group">
                            <label for="wallet" class="col-form-label">Select wallet to Fund:</label>

                            <select id='wallet' class="form-control">
                                <option value='sms'>SMS</option>
                                <option value='ussd'>Dollar Vault</option>
                                <option value='whatsapp'>WhatsApp</option>
                                <option value='card_balance'>Naira Card</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount" class="col-form-label">Amount:</label>
                            <input type='number' id='amount' class='form-control'>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick='fund_wallet()'>Fund</button>
                </div>
            </div>
        </div>
    </div>

@endsection
