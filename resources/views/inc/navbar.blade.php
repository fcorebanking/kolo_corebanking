@if ($page_name != 'coming_soon' && $page_name != 'contact_us' && $page_name != 'error404' && $page_name != 'error500'
&& $page_name != 'error503' && $page_name != 'faq' && $page_name != 'helpdesk' && $page_name != 'maintenence' &&
$page_name != 'privacy' && $page_name != 'auth_boxed' && $page_name != 'auth_default' && $page_menu
!='payment_link_menu')
@if(auth()->check())
@php
    $Navbarnoti = notificationBar();
    $alertData =alertData();
    $auth = auth()->user();

@endphp

<!--  BEGIN NAVBAR  -->
<div class="header-container fixed-top">
    <header class="header navbar navbar-expand-sm">

        <ul class="navbar-nav theme-brand flex-row  text-center">
            <li class="nav-item theme-logo d-none d-sm-none d-md-block d-lg-block">
                <a href="javascript:void(0);" class="sidebarCollapse">
                    <?php  $logo = url(str_replace('public','storage',@$auth->company->logo));?>
                    <img src="{{$logo}}" class="navbar-logo" id="companyLogo" alt="logo" onerror="loadDefaultLogo2()">
                    {{-- onerror="loadDefaultLogo()"  --}}
                </a>
            </li>
            <li class="nav-item theme-text">
                <a href="javascript:void(0);" class="nav-link sidebarCollapse">{{ Str::upper(@$auth->company->short_name)}} </a>
            </li>
            <li class="nav-item toggle-sidebar">
                <a href="javascript:void(0);" class="sidebarCollapse" data-placement="bottom"><svg
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="feather feather-list">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3" y2="6"></line>
                        <line x1="3" y1="12" x2="3" y2="12"></line>
                        <line x1="3" y1="18" x2="3" y2="18"></line>
                    </svg></a>
            </li>
        </ul>

        @if ($category_name != 'starter_kits')
        @if(cando('root'))
        <ul class="navbar-item flex-row navbar-dropdown">
        <li>
            <a class="dropdown-toggle btn btn-warning" href="#" role="button" id="appSection" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false"><span class='timmerx'></span></a>
            </li>
        </ul>

        <ul class="navbar-item flex-row search-ul d-none d-sm-none d-lg-block d-md-block">
            <li class="nav-item align-self-center search-animated">

                <h5 style="color: white;"><span>Bal</span>: {!! @$auth->company->currency->symbol !!}
                    <span id="companyWalletBalance"> {{moneyFormat(@$auth->company->wallet->balance)}}</span>
                </h5>

            </li>
        </ul>

        @else
        <ul class="navbar-item flex-row navbar-dropdown">
            <span class='timmerx' style='display:none'></span>
        </ul>
        <ul class="navbar-item flex-row search-ul d-none d-sm-none d-lg-block d-md-block">
            <li class="nav-item align-self-center search-animated">
            </li>
        </ul>
        @endif
        @endif
        <ul class="navbar-item flex-row navbar-dropdown {{ ($category_name === 'starter_kits') ? 'ml-auto' : '' }}">
            @if ($category_name != 'starter_kits')

            <li class="nav-item dropdown notification-dropdown">
                <a href="javascript:void(0);" class="nav-link dropdown-toggle" id="notificationDropdown"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="feather feather-bell">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="badge badge-success" >{{($Navbarnoti['pending_User'] + $Navbarnoti['pending_loan'] + ($alertData['total']))}}</span>
                </a>
                <div class="dropdown-menu position-absolute animated fadeInUp" aria-labelledby="notificationDropdown">
                        @if ($Navbarnoti['pending_User'] >0)
                                <a href="{{url('company/'.@$auth->company->short_name.'/pending-entry')}}">You have {{$Navbarnoti['pending_User']}} pending customer <br></a>
                        @endif
                        <hr>
                        @if ($Navbarnoti['pending_loan'] > 0)
                                <a href="{{url('company/'.@$auth->company->short_name.'/loan/pending')}}">You have {{$Navbarnoti['pending_loan']}} pending Loan</a>
                        @endif
                        @if ($alertData['voucher'][0] >0)
                        <a href="{{url('company/'.@$auth->company->short_name.'/transfer-voucher')}}">You have {{$alertData['voucher'][0]}} pending Voucher <br></a>
                        <hr>
                @endif
                @if ($alertData['pending_transfer'][0] >0)
                        <a href="{{url('company/'.@$auth->company->short_name.'/transaction/pending-transaction')}}">You have {{$alertData['pending_transfer'][0]}} pending Transfer <br></a>
                        <hr>

                @endif
                @if ($alertData['pendingTXN'][0] >0)
                        <a href="{{url('company/'.@$auth->company->short_name.'/saving/pending')}}">You have {{$alertData['pendingTXN'][0]}} pending Transaction <br></a>
                @endif
                </div>
            </li>
            @endif
            <li class="nav-item dropdown user-profile-dropdown  order-lg-0 order-1">
                <a href="javascript:void(0);" class="nav-link dropdown-toggle user" id="userProfileDropdown"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="feather feather-settings">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                        </path>
                    </svg>
                </a>
                <div class="dropdown-menu position-absolute animated fadeInUp" aria-labelledby="userProfileDropdown">

                    <div class="dropdown-item">
                    <a href="{{ url('/company/'.@$auth->company->short_name.'/staff-profile') }}" >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="feather feather-users">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>Account Settings
                    </a>
                        <a href="{{ route('logout') }}" onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();"><svg
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="feather feather-log-out">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg> Sign Out</a>
                    </div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </header>
</div>
<!--  END NAVBAR  -->

@if($auth->phone_verification_status_id == 1)
@if($auth->company->status_id == 1)
@if($page_menu != 'subscrption')
@if(isset($auth->company->sub->end_date))
<script>

    // Set the date we're counting down to
    var countDownDate = new Date("{{@$auth->company->sub->end_date}}").getTime();

    // Update the count down every 1 second
    var x = setInterval(function () {

        // Get today's date and time
        var now = new Date().getTime();

        // Find the distance between now and the count down date
        var distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Output the result in an element with id="demo"
        // document.getElementById("timmerx").innerHTML = days + "d " + hours + "h "
        //     + minutes + "m ";
        if ('{{$page_name}}' !== 'ledgerAccountSetting'){
            $('.timmerx').html(days + "d " + hours + "h "
                + minutes + "m ")
        }


        // If the count down is over, write some text
        if (distance < 0) {
            window.location = '/company/' + "plan";
            clearInterval(x);
            $('.timmerx').html("EXPIRED")
            // document.getElementById("timmerx").innerHTML = "EXPIRED";
        }
    }, 1000);
</script>

@endif
@endif
@endif
@endif
@endif
@endif
