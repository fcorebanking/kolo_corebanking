@extends('layouts.app')

@section('content')
<style>
.px-md-5{
    margin:5px;
}
.custom_active{
    margin:5px;
}

</style>
<div class="row invoice layout-top-spacing layout-spacing" id='printinvoice'>
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                        <div class="doc-container">
                            <div class="invoice-container">
                                <div class="invoice-inbox">

                                    <div id="ct" class="">

                                        <div class="invoice-00001">
                                            <div class="content-section">

                                                <div class="inv--head-section inv--detail-section">

                                                    <div class="row">



                                                        <div class="col-sm-6 text-sm-right">
                                                                    </div>

                                                        <div class="col-sm-6 align-self-center mt-3">
                                                            <p class="inv-street-addr"></p>
                                                            <h3 class="inv-customer-name">{{auth()->user()->company->company_name}} </h3>


                                                        </div>
                                                        <div class="col-sm-6 align-self-center mt-3 text-sm-right">

                                                        </div>
                                                    </div>

                                                </div>
                                               <center>
                                                   <div class='row'>
                                                   <div class='col-sm-6 col-md-1'></div>

                                                            <div class='col-sm-6 col-md-2'><label for='to'>To</label><input type='date' class='form-control' id='from' ></div>
                                                        <div class='col-sm-6 col-md-2'><label for='from'>From</label><input type='date' class='form-control' id='to' ></div>
                                                        <div class='col-sm-6 col-md-2'>
                                                            <label for='branch'>Branch</label>
                                                            <select id='branch' class='form-control' onchange="renderStaff()">
                                                                <option value=''>All</option>
                                                                @foreach($branch as $branchdata)
                                                                <option value='{{Crypt::encryptString($branchdata->id)}}'>{{$branchdata->branch_name}}</option>

                                                                @endforeach

                                                            </select>
                                                        </div>
                                                        @if(cando('root'))
                                                        <div class='col-sm-6 col-md-2'>
                                                            <label for='officer'>Staff</label>
                                                            <select name="officer" id='officer' class="form-control">
                                                            </select>
                                                        </div>
                                                        @else
                                                            <input type='hidden' id='officer'>
                                                        @endif
                                                        <div class='col-sm-6 col-md-3'><button onclick='searchrange()' class='btn btn-primary custom_active'>Search <i class='fa fa-search'></i></button></div>
                                                    </div>
                                            </center>
                                                <div class="inv--detail-section inv--customer-detail-section">
                                                </div>
                                                <h5>Transaction History </h5>
                                                <hr>

                                                <div class="inv--product-table-section">
                                                    <div class="table-responsive">
                                                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                                            <thead>
                                                            <th>SN</th>
                                                            <th>Name</th>
                                                            <th>TXN Reference</th>
                                                            <th>Account</th>
                                                            <th>AccountNo</th>
                                                            <th>Balance Before</th>
                                                            <th>Amount</th>
                                                            <th>Balance after</th>
                                                            <th>Withdrawal Charge</th>
                                                            <th>Transaction</th>
                                                            <th>TXN Type</th>
                                                            <th>TXN Remark</th>
                                                            <th>Description</th>
                                                            <th>Created By</th>
                                                            <th>Modified By</th>
                                                            <th>Created At</th>
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


                                </div>

                            </div>
                        </div>

                    </div>
                </div>



  <div class="modal fade" id="multiplemodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add Wallet</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <input type='hidden' name='userid' id='userid' >
        <select name='saving_type' id='saving_type' class='form-control'>

        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick='addwallet()'>Add new</button>
      </div>
    </div>
  </div>
</div>
<script>
    function renderStaff() {
        $branch = $('#branch').val();
        if(!$branch.length > 0 ){
            return false;
        }
        $.ajax({
            url: '/company/{{auth()->user()->company->short_name}}/branch/branch_staff/' + $branch,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                $('select[name="officer"]').empty();
                $('select[name="officer"]').append('<option value="">All</option>')
                data.forEach(function (staff) {

                    $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
                });
            }
        });
    }
</script>
@endsection
