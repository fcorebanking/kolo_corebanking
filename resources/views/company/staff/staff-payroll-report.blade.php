@extends('layouts.app')

@section('content')
<style>
    .px-md-5 {
        margin: 5px;
    }

    .custom_active {
        margin: 5px;
    }
</style>
<div class="row invoice layout-top-spacing layout-spacing" id='printinvoice'>
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="doc-container">
            <div class="invoice-container">
                <div class="invoice-inbox">

                    <div id="ct" class="col-md-12">

                        <div class="widget-content widget-content-area br-6">
                            <div class="content-section">

                                <div class="inv--head-section inv--detail-section">

                                    <div class="row">

                                        <div class="col-sm-12 align-self-center mt-3">
                                            <p class="inv-street-addr"></p>
                                            <h3 class="inv-customer-name">{{auth()->user()->company->company_name}}
                                                Payroll Summary</h3>

                                        </div>


                                    </div>

                                </div>
                                <div class='row'>

                                    <div class='col-sm-6 col-md-2'>
                                        <label for='from'>from</label>
                                        <input type='date' class='form-control' id='from'>
                                    </div>
                                    <div class='col-sm-6 col-md-2'>
                                        <label for='to'>to</label>
                                        <input type='date' class='form-control' id='to'>
                                    </div>
                                    <div class='col-sm-6 col-md-2'>
                                        <label for='branch'>Branch</label>
                                        <select id='branch' class='form-control'>
                                            <option value='0'>All</option>
                                            @foreach($branch as $branchdata)
                                            <option value='{{$branchdata->id}}'>{{$branchdata->branch_name}}
                                            </option>

                                            @endforeach

                                        </select>
                                    </div>

                                    <div class='col-sm-6 col-md-2'>
                                        <label for='username'>Username</label>

                                        <select id='username' class='form-control form-small' style='margin:5px'>

                                        </select>
                                    </div>
                                    <div class='col-sm-6 col-md-2'>


                                        <button onclick='searchrange_callover()' class='btn btn-primary custom_active'><i class='fa fa-search'></i></button>
                                    </div>

                                </div>
                                <div class="inv--detail-section inv--customer-detail-section">



                                </div>
                                <hr>

                                <div class="inv--product-table-section">
                                    <div class="table-responsive">
                                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                            <thead>
                                                <th>#</th>
                                                <th>Pay Date</th>
                                                <th>Payment Reference</th>
                                                <th>Staff</th>
                                                <th>Amount Paid</th>
                                                <th>Payment Via</th>
                                                <th>Payment Mode</th>
                                                <th>Status</th>
                                                <!-- <th>Pay Slip</th> -->
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
                <input type='hidden' name='userid' id='userid'>
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

@endsection