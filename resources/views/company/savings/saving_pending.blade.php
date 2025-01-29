@extends('layouts.app')

@section('content')

<div class='container-fluid'>
<div class="row layout-top-spacing ">

    <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
        <form id='pending_approval_form'>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="">Branch</label>
                        <select name="branch" id='branch' onchange="renderStaff()" class="form-control" required>
                             <option value="null">Select Branch</option>

                            @if(count($branch))
                            @foreach($branch as $branchData)
                            <option value="{{Crypt::encryptString($branchData->id)}}">{{$branchData->branch_name}}</option>

                            @endforeach

                            @endif


                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="">Account Officer</label>
                        <select name="officer" id='officer' onchange="renderStaff_pending()" class="form-control" required>
                        </select>
                    </div>

                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="">Action</label>
                        <select name='action' class='form-control' required>
                            <option value=''>Choose action</option>
                            <option value='1'>Approve</option>
                            <option value='2'>Delete</option>
                        </select>
                    </div>

                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <br>
                        <button type='submit' class='btn btn-primary'>Submit</button>
                    </div>
                </div>
            </div>
        <div class="widget-content widget-content-area br-6">
            <div class="table-responsive mb-4 mt-4">
                <span id='infobait'></span>
                <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                    <thead>
                    <th>Name</th>
                    <th>Wallet</th>
                    <th>Amount</th>
                    <th>Charge</th>
                    <th>Transaction</th>
                    <th>Remark</th>
                    <th>Created By</th>
                    <th>View</th>
                    <th>Select</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
    </div>
</div>
</div>
<!-- Button trigger modal -->


  <!-- Modal -->
  <div class="modal fade" id="exampleModalPending" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabeltitle">Details</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type='hidden' id='pendingid'>
          <div class='table-responsive'>

            <table class='table table-striped"' id='account'>
                <thead><tr><th>Name</th><th>A/C Number</th><th>Account Type</th><th>Balance</th></tr></thead>
                <tbody id='bodyTable'>

                </tbody>
            </table>
          </div>
          <h2>Withdrawal Details</h2>
          <div class='row'>
          <div class='col-sm-6 col-md-8'>
            <h5>Customer Information</h5>
            <div class='customerinfo'>u</div>
          </div>
          <div class='col-sm-6 col-md-4'>
            <h5>Photo</h5>
            <div class='customerinfoPhoto'>-</div>
          </div>
          </div>
          <div class='row'>
            <div class='col-sm-6 col-md-8'>
          <h5>Teller Information</h5>
          <div class='tellerinfo'></div>

        </div>
        <div class='col-sm-6 col-md-4'>
            <h5>Teller Photo</h5>
            <div class='tellerinfoPhoto'>-</div>
          </div>
        </div>
        <div class='beneficiary'></div>
        <h3>#Transaction Amount</h3>
        <h4 class='amount'></h4>
        <button class='btn btn-lg btn-success' onclick='rejectPending(1)'>Approve</button>
        <button class='btn btn-lg btn-danger' onclick='rejectPending(2)'>Reject</button>
    </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-info text-dark" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
@endsection
