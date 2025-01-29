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
            <h5 class="modal-title" id="exampleModalLongTitle">Rejected Loan</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <form id='loan-reject' class='card add-loan-book'>
            <div id='bookingform'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" class="token" id='loanID' name="loanID" value="">

                <div class="modal-body">
        <div class='row'>
            <div class="col-sm-12 col-md-12 sessions">

            </div>
                <div class="col-sm-12 col-md-12">
                <label>Remarks</label>
                <textarea name='remark' id='remark' class='form-control'></textarea>
            </div>

            <div class='col-md-12 col-sm-12'>
                <br>
                <br>
                <div class="form-check form-check-inline">
                    <input class="btn-check" type="radio" name="actions" id="inlineRadio1" value="3" required autocomplete="off">
                    <label class="btn btn-outline-primary" for="inlineRadio1">Pending</label>
                  </div>

                  <div class="form-check form-check-inline">
                    <input class="btn-check" type="radio" name="actions" id="inlineRadio3" value="5" autocomplete="off">
                    <label class="btn btn-outline-primary" for="inlineRadio3">Delete</label>
                  </div>
            </div>
        </div>
                </div>
                <div class="modal-footer">
                    <button type="button"  class="btn btn-flat" data-dismiss="modal" ><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="button" class="btn btn-primary" id='updateLoanStatus' onclick='PerforAction()'>Submit</button>

                </div>
            </div>

        </form>
      </div>
    </div>
  </div>

@endsection
