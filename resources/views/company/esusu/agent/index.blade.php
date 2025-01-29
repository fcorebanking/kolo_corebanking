@extends('layouts.app')

@section('content')
<div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area text-center">
            <h2>Esusu Mobile App</h2>
            <div class="row layout-top-spacing">
                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                    <div ><button onclick='renderPage("esusu-mobile","1")' class='btn btn-primary'>Active Agents</button><button onclick='renderPage("esusu-mobile","3")' class='btn btn-secondary'>Agents Disabled</button><button onclick='renderPage("esusu-mobile","2")' class='btn btn-warning'>Agents Request</button></div>

                    <div class="table-responsive mb-4 mt-4">
                        <span > <h4 id='infobait'>Customers Agent</h4></span>
                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                            <thead >
                            <th>#</th>
                            <th>FirstName</th>
                            <th>LastName</th>
                            <th>Email</th>
                            <th>Account Number</th>
                            <th>Balance</th>
                            <th>Action</th>
                            </thead>

                            <tbody>

                            </tbody>
                        </table>
                            <!-- Modal -->
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






