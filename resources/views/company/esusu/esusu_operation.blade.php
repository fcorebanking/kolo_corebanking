@extends('layouts.app')

@section('content')
<div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area text-center">

        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>Esusu Customer</h4>
                </div>
            </div>
        </div>

            <ul class="nav nav-pills mb-3 mt-3 justify-content-end" id="v-right-pills-tab" role="tablist" aria-orientation="vertical">
                <li class="nav-item">
                    <a class="nav-link active"  id="v-right-pills-home-tab" data-toggle="pill" href="#v-right-pills-home" role="tab" aria-controls="v-right-pills-home" aria-selected="true"><b>Running Esusu </b></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="v-right-pills-profile-tab" data-toggle="pill" href="#v-right-pills-profile" role="tab" aria-controls="v-right-pills-profile" aria-selected="false"><b>Create Esusu</b></a>
                </li>

            </ul>
        <div class="row ">
            <div class="col-sm-12 col-md-12 col-12 order-sm-0 order-1">
                <div class="tab-content" id="v-right-pills-tabContent">
                <div class="tab-pane fade show active" id="v-right-pills-home" role="tabpanel" aria-labelledby="v-right-pills-home-tab">
                    <h4 class="mb-4">Active Esusu</h4>
                    <div class="layout-px-spacing">

                        <div class="row layout-top-spacing">

                            <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                                <div class="table-responsive mb-4 mt-4">
                                    <span id='infobait'></span>
                                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                        <thead>

                                        <th>Esusu</th>
                                        <th>Customer</th>
                                        <th>Account Number</th>
                                        <th>Rate</th>
                                        <th>Commencement Day</th>
                                        <th>Current-Date</th>
                                        <th>Prev. Card No</th>
                                        <th>Today. Card No.</th>
                                        <th>Today Cash</th>
                                        <th>Circle Out</th>
                                        <th>Withdrawable</th>
                                        <th>Wallet</th>
                                        <th>O/D Amount</th>
                                        <th>Accumulated Charge</th>
                                        <th>Day Missed</th>
                                        <th>Action</th>


                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="v-right-pills-profile" role="tabpanel" aria-labelledby="v-right-pills-profile-tab">
                        <div class="media">
                               <div class="media-body">
                                <div class="row">
                                    <div class="col-sm-6 col-md-6">
                                        <label>Account</label>
                                        <select name='customer' id='customerx' onchange='select_tool()' class='form-control form-small' style='margin:5px'>

                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-md-6">
                                        <label>Account Wallet</label>
                                        <select name='savetype' id='savetype' class='form-control form-small' style='margin:5px' >

                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-md-6">
                                        <label>Esusu Type</label>
                                        <select name='esusu_product' id='esusu_product' class='form-control form-small' style='margin:5px' >

                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-md-6">
                                        <label>Rate</label>
                                    <div class="input-group mb-4">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rate</span>
                                        </div>
                                        <input type="text" class="form-control" id='rate' name='rate' aria-describedby="basic-addon3"
                                            placeholder="100..." value='0'>
                                    </div>
                                    </div>
                                    <div class="col-sm-6 col-md-6">
                                        <label>Start Date</label>
                                        <div class="input-group mb-4">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Start Date</span>
                                            </div>
                                            <input type="date" class="form-control" id='start_date' name='start_date' aria-describedby="basic-addon3">
                                        </div>
                                    </div>

                                </div>
                                <button type='button' onclick='btncreate_esusu()' class="mr-2 btn btn-primary" id='esusu-create'>Create Account</button>

                            </div>
                        </div>
                </div>

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
            <label>Payment Method</label>
            <select class="form-control" id='method' >
                <option value='cash'>Cash</option>
                <option value='wallet'>Wallet</option>
            </select>


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
            <div class='row'>
                <div class='col-md-12'>
            <div class="n-chk">
                <label class="new-control new-checkbox checkbox-primary">
                  <input type="checkbox" class="new-control-input" id='withdraw_to_wallet' checked>
                  <span class="new-control-indicator"></span>Move Withdrawable to Esusu Wallet
                </label>
            </div>
        </div>
        <div class='col-md-12'>

            <div class="n-chk">
                <label class="new-control new-checkbox checkbox-primary">
                  <input type="radio" name='wallet_to_main' value='1' class="new-control-input" id='wallet_to_main' checked>
                  <span class="new-control-indicator"></span>Dont Debit Esusu Wallet
                </label>
            </div>
        </div>
        <div class='col-md-12'>

            <div class="n-chk">
                <label class="new-control new-checkbox checkbox-primary">
                  <input type="radio" name='wallet_to_main' value='2' class="new-control-input" id='wallet_to_main2' >
                  <span class="new-control-indicator"></span>Debit Esusu Wallet Only
                </label>
            </div><br>
        </div>
        <div class='col-md-12'>

            <div class="n-chk">
                <label class="new-control new-checkbox checkbox-primary">
                  <input type="radio" name='wallet_to_main' value='3' class="new-control-input" id='wallet_to_main3' >
                  <span class="new-control-indicator" ></span>Credit Saving Wallet from Esusu Wallet
                </label>
            </div><br>
        </div>
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
            <button type="button" class="btn btn-primary" id='esusu-reset' onclick="perform_reset()">Reset Esusu</button>

        </div>
          <div class='overdraft-esusu-form'>
            <button type="button" class="btn btn-primary" id='esusu-overdraft' onclick="perform_overdraft()">Apply for Overdraft</button>

        </div>
          <div class='deposit-esusu-form'>
          <button type="button" class="btn btn-primary" id='esusu-deposit' onclick="perform_deposit()">Topup</button>
        </div>
        <div class='withdraw-esusu-form'>
            <button type="button" class="btn btn-primary"   id='esusu-debit' onclick="perform_withdraw()">Withdraw</button>
          </div>
    </div>
      </div>
    </div>
  </div>
@endsection






