@extends('layouts.app')

@section('content')
<style>
   span{
        font-size: 9px;
    }
</style>
<div class="layout-px-spacing">

  <div class="row layout-top-spacing">

    <div class="col-xl-12 col-lg-12 col-sm-12">
      <div class="widget-content widget-content-area">

        <h3 style="margin-bottom: 30px;"></h3>

        <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
          <div class="col-md-6">
            <div class="clipboard">
              <form class="form-horizontal">
                <label for="input-copy"><strong>Customer Registration Link2</strong></label>
                <input type="text" class="form-control mb-4" readonly id="input-copy" value="{{$registrationLink}}">

                <a class="mb-1 btn btn-primary" href="javascript:;" data-clipboard-action="copy"
                  data-clipboard-target="#input-copy"><i class="fa fa-clipboard"></i> Copy Link</a>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="row layout-top-spacing">

    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">

    @if(!cando('advance-dashboard|root'))
      <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-3">
          <div class="card-counter success">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers deposit-total" style='font-size:10px;'>0</span>
            <span class="count-name">Today's Credit</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter danger">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers withdraw-total">0</span>
            <span class="count-name">Today's Debit</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter info">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers system-balance">0</span>
            <span class="count-name">Total Customers</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter warning">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers system-balance">0</span>
            <span class="count-name">Pending Entry</span>
          </div>
        </div>

      </div>
      @endif

      @if(cando('advance-dashboard|root'))
      <div class="row">
        <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers deposit-total">0</span>
            <span class="count-name">Total Deposit</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers system-balance">0</span>
            <span class="count-name">System Balance</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter danger">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers withdraw-total">0</span>
            <span class="count-name">Total Withdraw</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter info">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers total-branches">0</span>
            <span class="count-name">Total Branches</span>
          </div>
        </div>

      </div>

      <div class="row" id="fulDashboardDiv" style="display: none; margin-bottom: 10px;">

        <div class="col-md-3">
          <div class="card-counter info">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers charge-deposit">0</span>
            <span class="count-name">Deposit Charge</span>
          </div>
        </div>


        <div class="col-md-3">
          <div class="card-counter warning">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers withdraw-charge">0</span>
            <span class="count-name">Withdrawal Charge</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers system-balance">0</span>
            <span class="count-name">System Balance</span>
          </div>
        </div>


        <div class="col-md-3">
          <div class="card-counter secondary">
            <i class="fa fa-envelope"></i>
            <span class="count-numbers unpaid-sms">0</span>
            <span class="count-name">Unpaid SMS</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter success">
            <i class="fa fa-envelope"></i>
            <span class="count-numbers sms-charge">0</span>
            <span class="count-name">SMS Charge</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter info">
            <i class="fa fa-users"></i>
            <span class="count-numbers reg-charge">0</span>
            <span class="count-name">Entry Fee</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter danger">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers dom_account">0</span>
            <span class="count-name">Dom Account</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter success">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers total_investment">0</span>
            <span class="count-name">Total Investment</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter secondary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers complete_investment">0</span>
            <span class="count-name">Complete Investment</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers active_investment">0</span>
            <span class="count-name">Active Investment</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers esusu_count">0</span>
            <span class="count-name">Total Active Esusu</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers esusu_wallet">0</span>
            <span class="count-name">Esusu Wallet </span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers esusu_withdrawable">0</span>
            <span class="count-name">Esusu Withdrawable</span>
          </div>
        </div>
        {{-- <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers esusu_pending_charge">0</span>
            <span class="count-name">Esusu Pending Charges</span>
          </div>
        </div> --}}

        <div class="col-md-3">
          <div class="card-counter success">
            <i class="fa fa-user"></i>
            <span class="count-numbers pending_entry">0</span>
            <span class="count-name">Pending Entry</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter success">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers auth_wallet">0</span>
            <span class="count-name">My Wallet</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter primary">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers today_deposit">0</span>
            <span class="count-name">Today Credit</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-dark p-2" style="--bs-bg-opacity: .5;">
            <i class="fa fa-dollar"></i>
            <span class="count-numbers today_withdraw">0</span>
            <span class="count-name">Today Debit</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter bg-info p-2" style="--bs-bg-opacity: .5;">
            <i class="fa fa-user"></i>
            <span class="count-numbers staff_customer">0</span>
            <span class="count-name">Total Customer</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter bg-success  p-2" style="--bs-bg-opacity: .5;">
            <i class="fa fa-user"></i>
            <span class="count-numbers link_cards"></span>
            <span class="count-name">Linked Card</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter bg-warning p-2" style="--bs-bg-opacity: .5;">
            <i class="fa fa-user"></i>
            <span class="count-numbers pending_card"></span>
            <span class="count-name">Pending Card</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter warning">
            <i class="fa fa-cash"></i>
            <span class="count-numbers staff_comission">0</span>
            <span class="count-name">Staff Comission</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter warning">
            <i class="fa fa-cash"></i>
            <span class="count-numbers staff_balance">0</span>
            <span class="count-name">Staff Balance</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter warning">
            <i class="fa fa-cash"></i>
            <span class="count-numbers expenses">0</span>
            <span class="count-name">Total Expenses</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter info">
            <i class='fas fa-coins'></i>
            <span class="count-numbers completeCount">0</span>
            <span class="count-name">Complete Loan</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-primary">
            <i class='fas fa-coins'></i>
            <span class="count-numbers pendingCount">0</span>
            <span class="count-name">Pending Loan</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-success">
            <i class='fas fa-coins'></i>
            <span class="count-numbers disbursmentCount">0</span>
            <span class="count-name">Disbused Loan</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter bg-secondary">
            <i class='fas fa-coins'></i>
            <span class="count-numbers writeOff">0</span>
            <span class="count-name">Written Off Loan</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-danger">
            <i class='fas fa-coins'></i>
            <span class="count-numbers loanActiveCount">0</span>
            <span class="count-name">Active Loan</span>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-counter bg-warning">
            <i class='fas fa-coins'></i>
            <span class="count-numbers InitPamount">0</span>
            <span class="count-name">Total Principal</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-light">
            <i class='fas fa-coins'></i>
            <span class="count-numbers TInterestLeft">0</span>
            <span class="count-name">Interest Left</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-dark">
            <i class='fas fa-coins'></i>
            <span class="count-numbers TprincipalLeft">0</span>
            <span class="count-name">Principal Left</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-secondary p-2" style="--bs-bg-opacity: .5;">
            <i class='fas fa-coins'></i>
            <span class="count-numbers TloanBalance">0</span>
            <span class="count-name">Loan Balance</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter info">
            <i class='fas fa-coins'></i>
            <span class="count-numbers paidamount">0</span>
            <span class="count-name">Total Loan Paid</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-success p-2" style="--bs-bg-opacity: .5;">
            <i class='fas fa-coins'></i>
            <span class="count-numbers Ppaidamount">0</span>
            <span class="count-name">Loan P Paid</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-primary p-2" style="--bs-bg-opacity: .5;">
            <i class='fas fa-coins'></i>
            <span class="count-numbers Ipaidamount">0</span>
            <span class="count-name">Loan I Paid</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-info p-2" style="--bs-bg-opacity: .5;">
            <i class='fas fa-coins'></i>
            <span class="count-numbers loanFeePaid">0</span>
            <span class="count-name">Loan Fee</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-counter bg-dark p-2" style="--bs-bg-opacity: .5;">
            <i class='fas fa-coins'></i>
            <span class="count-numbers mgrtFeePaid">0</span>
            <span class="count-name">Loan Mgrt Fee</span>
          </div>
        </div>
      </div>

      @endif


      @if(cando('advance-dashboard|root'))
      <div class='row savingsdata' style="margin-bottom: 20px;">

      </div>
      @endif


      @if(cando('advance-dashboard|root'))
      <div class="row" id="loadDashboardButtonDiv" style="height: 100px; justify-content: center;">
        <button class="btn btn-primary" id="loadDashboardBtn" style="height: 50px; margin-top: 10px"
          onclick="load_data_full()"> Load Full
          Dashboard</button>
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
        @if(cando('root'))
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

      @if(cando('root'))
      <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">

        <div class="row">
          <div class="col-lg-6">
            <div class="widget widget-table-one">
              <div class="widget-heading">
                <h5 class="">Branch</h5>
              </div>

              <div class="widget-content branch_data">
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
                    <p class="">{{Auth()->user()->company->wallet->bank->bank_name}}</p>
                    <p class="">{{Auth()->user()->company->wallet->vaccount}}</p>

                  </div>

                  <div class="inv-detail">
                    <div class="info-detail-1">
                      <p>SMS</p>
                      <p class='sms'></p>
                    </div>
                    <div class="info-detail-2">
                      <p>USSD</p>
                      <p class='ussd'></p>
                    </div>
                    <div class="info-detail-3 info-sub">
                      <div class="info-detail">
                        <p>Whatsapp</p>
                        <p class='whatsapp'></p>
                      </div>

                    </div>
                  </div>

                  <div class="inv-action">

                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal"
                      data-whatever="@mdo">Topup</button>
                    <a href="{{url('company/'.Auth()->user()->company->short_name.'/transaction/fund-wallet')}}"
                      class="btn btn-info"> Fund Wallet</a>

                    @if(auth()->user()->company->ussd_string ==null)
                    <button type="button" class="btn btn-warning gen_ussd_btn"
                      onclick="generate_ussd_string({{config('app.ussd_generate_charge')}})">Generate USSD Code
                    </button>
                    @else

                    <a href="tel:{{config('app.ussd_string')}}*{{auth()->user()->company->ussd_string}}#"
                      class="badge badge-warning ussd_btn">
                      {{config('app.ussd_string')}}*{{auth()->user()->company->ussd_string}}#</a>
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

        <div class="widget-content transaction_type">
        </div>
      </div>
    </div>
    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
      <div class="widget-one">
        <div class="widget-content">
          <div class="w-numeric-value">
            <div class="w-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="feather feather-globe">
                <circle cx="12" cy="12" r="10" />
                <line x1="2" y1="12" x2="22" y2="12" />
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
              </svg>
            </div>
            <div class="w-content">
              <span class="w-value total_daily"></span>
              <span class="w-numeric-title">Daily Transaction Volume</span>
            </div>
          </div>
          <div class="w-chart">
            <div id="total-orders"></div>
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
            <table class="table ">
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
        <form>
          <div class="form-group">
            <label for="wallet" class="col-form-label">Select wallet to Fund:</label>

            <select id='wallet' class="form-control">
              <option value='sms'>SMS</option>
              <option value='ussd'>USSD</option>
              <option value='whatsapp'>WhatsApp</option>
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
