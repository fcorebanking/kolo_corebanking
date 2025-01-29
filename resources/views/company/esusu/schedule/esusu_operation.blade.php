@extends('layouts.app')

@section('content')
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area text-center">
        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>Food Stash</h4>
                </div>
            </div>
        </div>
            <ul class="nav nav-pills mb-3 mt-3 justify-content-end" id="v-right-pills-tab" role="tablist" aria-orientation="vertical">
                <li class="nav-item">
                    <a class="nav-link active"  id="v-right-pills-home-tab" data-toggle="pill" href="#v-right-pills-home" role="tab" aria-controls="v-right-pills-home" aria-selected="true"><b>Running Food Stash </b></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="v-right-pills-profile-tab" data-toggle="pill" href="#v-right-pills-profile" role="tab" aria-controls="v-right-pills-profile" aria-selected="false"><b>Create Food Stash</b></a>
                </li>
            </ul>
        <div class="row ">
            <div class="col-sm-12 col-md-12 col-12 order-sm-0 order-1">
                <div class="tab-content" id="v-right-pills-tabContent">
                <div class="tab-pane fade show active" id="v-right-pills-home" role="tabpanel" aria-labelledby="v-right-pills-home-tab">
                    <h4 class="mb-4">Food Stash Esusu</h4>
                    <div class="layout-px-spacing">

                        <div class="row layout-top-spacing">

                            <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                                <div class="table-responsive mb-4 mt-4">
                                    <span id='infobait'></span>
                                    <div class="row">
                                    <div class="col-sm-6 col-md-4">
                                        <label>Food Stash Type</label>
                                        <select name='foodstash_product' onchange="search()" id='foodstash_product' class='form-control form-small foodstash_product' style='margin:5px' ></select>
                                    </div>
                                        <div class="col-sm-6 col-md-4">
                                        <label>Status</label>
                                        <select name='status' onchange="search()" id='status' class='form-control form-small status' style='margin:5px' >
                                            <option value=''>All</option>
                                            <option value='complete'>Completed</option>
                                            <option value='active'>Active</option>
                                        </select>
                                    </div>
                                </div>
                                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                        <thead>
                                        <th>Esusu</th>
                                        <th>Customer</th>
                                        <th>Account Number</th>
                                        <th>Rate</th>
                                        <th>Commencement Day</th>
                                        {{-- <th>Prev. Card No</th>
                                        <th>Today. Card No.</th>
                                        <th>Today Cash</th> --}}
                                        <th>Wallet</th>
                                        <th>EndDate</th>
                                        <th>Status</th>
                                        <th>CreatedBy</th>
                                        <th></th>
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
                                        <select name='customer' id='customerx'  class='form-control form-small' style='margin:5px'>

                                        </select>
                                    </div>

                                    <div class="col-sm-6 col-md-6">
                                        <label>Food Stash Type</label>
                                        <select name='esusu_product' id='esusu_product' class='form-control form-small esusu_product' style='margin:5px' >

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
{{-- </div> --}}

<!-- Modal -->
<div class="modal fade" id="esusu_transaction" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Food Stash Transaction</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <input type="hidden" class="form-control" id='esusu_id' name='esusu_id' aria-describedby="basic-addon3"
                     value='0'>
        <div class='deposit-esusu-form'>
            <div class="col-sm-6 col-md-6">
                <label>Method</label>
                <select name='method' id='method' class='form-control form-small method' style='margin:5px' >
                    <option value='cash'>Cash</option>
                    <option value='wallet'>Wallet</option>
                </select>
            </div>
            <label>No. Days</label>
            <div class="input-group ">
                <div class="input-group-prepend">
                    <span class="input-group-text">Number</span>
                </div>
                <input type="number" class="form-control" id='number' name='number' aria-describedby="basic-addon3"
                    placeholder="100..." value='0'>
            </div>

        </div>

        <div class='staff'>
            <label>Pick Set as Agent</label>
            <select  id='staff_select' class='form-control form-small ' style='margin:5px' >
                @foreach ($staff as $staffData)
                    <option value="{{$staffData->id}}">{{$staffData->first_name}} {{$staffData->last_name}}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-primary"  onclick="changeAgent()">Submit</button>
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
            <div class='alert alert-warning'>Note: Once reset the running esusu will be overriding.</div>
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
            <label>Esusu Type</label>
            <select name='productid' id='productid' class='form-control form-small select2' style='margin:5px' >
                @if ($product)
                    @foreach ($product as $productdata)
                        <option value="{{$productdata->id}}">{{$productdata->name}}</option>
                    @endforeach
                @endif
            </select>
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
        @if (cando('root|reset-foodstash'))

        <div class='withdraw-esusu-form'>
            <button type="button" class="btn btn-primary"   id='esusu-debit' onclick="perform_withdraw()">Withdraw</button>
          </div>
          @else
          <div class='alert alert-danger'>You do not have permission to override this data.</div>

          @endif
    </div>
      </div>
    </div>
  </div>
@endsection






