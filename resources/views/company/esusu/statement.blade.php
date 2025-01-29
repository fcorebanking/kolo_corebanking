@extends('layouts.app')

@section('content')
<div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area text-center">
            <h2>Esusu Statement</h2>
            <div class="row layout-top-spacing">
                <div class="col-xl-6 col-lg-6 col-sm-6">
                    <ul style='list-style-type: none; text-align:left; padding: 0;'>
                        <li><b>Account Name: {{$esusu->wb_account_details->user->first_name}} {{$esusu->wb_account_details->user->last_name}}</b></li>
                        <li><b>Account Number: {{$esusu->wb_account_details->virtual_accnum}}</b></li>
                        <li><b>Phone Number: {{$esusu->wb_account_details->user->phone}}</b></li>
                        <li><b>Account Type: {{$esusu->wb_account_details->accountType->savings_product}}</b></li>
                    </ul>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>From</label>
                            <input class="form-control" type="date" id="datepicker" placeholder="select date" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>To</label>
                            <input class="form-control" type="date" id="datepicker2" placeholder="select date" autocomplete="off">
                        </div>
                    </div>
                        <div class="col-sm-4">
                        <div class="form-group">
                            <br>
                            <button class="btn btn-info" id="search" onclick="searchByData('{{$_GET['uid']}}')">Search</button>
                        </div>
                    </div>
                </div>
                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing"><b> <div class='statementtext text-center'></div></b></div>
                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                    <div class="table-responsive mb-4 mt-4">
                        <span id='infobait'></span>
                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                            <caption class='statementtext'></caption>
                            <thead>
                                <th>#</th>
                                <th>Created At</th>
                                <th>Description</th>
                                <th>Tran. Type</th>
                                <th>TXN No</th>
                                <th>Product</th>
                                <th>Acct Officer</th>
                                <th>Withdrawal{{auth()->user()->company->currency->symbol}}</th>
                                <th>Deposit{{auth()->user()->company->currency->symbol}}</th>
                                <th>Session</th>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="esusu_transaction" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Esusu Transaction</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <input type="hidden" class="form-control" id='esusu_id' name='esusu_id' aria-describedby="basic-addon3"
                     value='0'>
        <div class='deposit-esusu-form'>
            <label>No. Days</label>
            <div class="input-group ">
                <div class="input-group-prepend">
                    <span class="input-group-text">Number</span>
                </div>
                <input type="number" class="form-control" id='number' name='number' aria-describedby="basic-addon3"
                    placeholder="100..." value='0'>
            </div>
        </div>
        <div class='withdraw-esusu-form'>

            <br>
            <div class="n-chk">
            <label>Enter Amount</label>
                <div class="input-group ">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Amount</span>
                    </div>
                    <input type="number" class="form-control" id='withdraw_amount' name='withdraw_amount' aria-describedby="basic-addon3"
                        placeholder="100..." value='0'>
                </div>
            </div>
            <div class="n-chk">
                <label class="new-control new-checkbox checkbox-primary">
                  <input type="checkbox" class="new-control-input" id='withdraw_to_wallet' checked>
                  <span class="new-control-indicator"></span>Esusu Withdrawable to Esusu Wallet
                </label>
            </div>
            <div class="n-chk">
                <label class="new-control new-checkbox checkbox-primary">
                  <input type="checkbox" class="new-control-input" id='wallet_to_main' checked>
                  <span class="new-control-indicator"></span>Esusu Wallet to Main Balance
                </label>
            </div>

        </div>
        <div class='overdraft-esusu-form'>
            <label>Amount</label>
                <div class="input-group ">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Amount</span>
                    </div>
                    <input type="number" class="form-control" id='overdraft_amount' name='overdraft_amount' aria-describedby="basic-addon3"
                        placeholder="100..." value='0'>
                </div>
        </div>
        <div class='reset-esusu-form'>
            <label>Rate</label>
            <div class="input-group ">
                <div class="input-group-prepend">
                    <span class="input-group-text">Rate</span>
                </div>
                <input type="number" class="form-control" id='reset_rate' name='reset_rate' aria-describedby="basic-addon3"
                    placeholder="100..." value='0'>
            </div>
            <label>Commence date</label>
            <div class="input-group ">
                <div class="input-group-prepend">
                    <span class="input-group-text">Date</span>
                </div>
                <input type="date" class="form-control" id='reset_date' name='reset_date' aria-describedby="basic-addon3"
                   >
            </div>
        </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
          <div class='reset-esusu-form'>
            <button type="button" class="btn btn-primary" onclick="perform_reset()">Reset Esusu</button>

        </div>
          <div class='overdraft-esusu-form'>
            <button type="button" class="btn btn-primary" onclick="perform_overdraft()">Apply for Overdraft</button>

        </div>
          <div class='deposit-esusu-form'>
          <button type="button" class="btn btn-primary" onclick="perform_deposit()">Topup</button>
        </div>
        <div class='withdraw-esusu-form'>
            <button type="button" class="btn btn-primary" onclick="perform_withdraw()">Withdraw</button>
          </div>
    </div>
      </div>
    </div>
  </div>
@endsection






