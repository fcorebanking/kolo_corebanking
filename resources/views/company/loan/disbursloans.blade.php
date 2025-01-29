@extends('layouts.app')

@section('content')

<div class="widget-content widget-content-area br-6">
    <div class="table-responsive mb-4 mt-4">
        <span id='infobait'></span>
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
            <th>sn</th>
            <th>Name</th>
            <th>A/C Number</th>
            <th>ref</th>
            <th>Interest Rate(%)</th>
            <th>Principal</th>
            <th>product</th>
            <th>Status</th>

            <th>action</th>
            </thead>
            <tbody>

            </tbody>
        </table>
            <!-- Modal -->
    </div>
</div>
<!-- Modal -->
<div class="modal fade bd-example-modal-lg" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">

      <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">Disburse Loan</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <form id='loan-disturb' class='card add-loan-book'>
            <div id='bookingform'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" class="token" id='loanID' name="loanID" value="">
                <input type="hidden" class="token" id='customerx' name="customerx" value="">

                <div class="modal-body">

                <div class='row'>
                    <div class='col-sm-12 col-md-12'>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Loan StartDate</span>
                        </div>
                        <input type="date" class="form-control" name='startDate' id='startDate' aria-describedby="basic-addon3" >
                    </div>

                    </div>
                    <div class='col-sm-12 col-md-12'>

                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Repay Start Date</span>
                            </div>
                            <input type="date" class="form-control" name='graceDate' id='graceDate' aria-describedby="basic-addon3" >
                        </div>
                    </div>
                </div>
                    <div class='row'>

                        <div class='col-sm-6 col-md-4'>
                            <label>Disburse Type</label>
                        </div>
                        <div class='col-sm-6 col-md-8'>

                            <select name='disbursementCash' onchange='swapDisburse(this.value)' class='form-control'>
                                <option value='1'>Disburse by cash </option>
                                <option value='2'>To disburse to customer wallet</option>
                                <option value='4'>Disburse via mobile Money</option>
                                <option value='3' disabled>Disburse to Bank (Coming Soon)</option>
                            </select>
                        </div>
                    </div>
                    <br>


                    <div class='savetype_s row'>

                            <div class='col-sm-6 col-md-4'>
                                <label>Account Type</label>
                            </div>
                            <div class='col-sm-6 col-md-8'>
                                <select name='savetype' id='savetype' class='form-control'></select>
                            </div>

                    </div>
                    <div class='mobilemoney'>
                    <div class="form-group">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Currency</span>
                            </div>
                        <select name='currency_id' id='currency_id' class='form-control'>
                            @if ($currencies)
                                @foreach ($currencies as $currenciesdata)
                                    <option value="{{$currenciesdata->id}}">{{$currenciesdata->name}}</option>
                                @endforeach
                            @endif
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </div>
                </div>
                    <div class="form-group">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Mobile Number </span>
                            </div>
                        <input type='text' name='mobile' id='mobile' class='form-control'/>
                    </div>
                </div>
                <div class='alert alert-warning'>Note: This is only available for Zambia and Cameroon</div>
                </div>

                </div>
                <div class="modal-footer">
                    <button type="button"  class="btn" data-dismiss="modal" ><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="button" class="btn btn-primary" id="disburseBTN" onclick='Disburse()'>Disburse</button>
                </div>
            </div>

        </form>
      </div>
    </div>
  </div>

@endsection
