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

                                                        <div class="col-sm-6 col-12 mr-auto">
                                                            <div class="d-flex">

                                                                <h3 class="in-heading align-self-center">.</h3>
                                                            </div>
                                                        </div>

                                                        <div class="col-sm-6 text-sm-right">
                                                                    </div>

                                                        <div class="col-sm-6 align-self-center mt-3">
                                                            <p class="inv-street-addr"></p>
                                                            <h3 class="inv-customer-name">{{auth()->user()->company->company_name}} Mobile Transaction</h3>


                                                        </div>
                                                        <div class="col-sm-6 align-self-center mt-3 text-sm-right">

                                                        </div>

                                                    </div>

                                                </div>
                                               <center>
                                                   <div class='container row'>

                                                        <div class='col-sm-6 col-md-3'>
                                                            <label for='from'>from</label>
                                                            <input type='date' class='form-control' id='from' ></div>
                                                        <div class='col-sm-6 col-md-3'>
                                                            <label for='to'>to</label>
                                                            <input type='date' class='form-control' id='to' ></div>
                                                            <div class='col-sm-6 col-md-3'>
                                                                <label for='to'>Type</label>
                                                                <select class='form-control' id='transactiontype'>
                                                                    <option value='pending-withdraw'>Pending Withdraw</option>
                                                                    <option value='approved-withdraw'>Approved Withdraw</option>
                                                                    <option value='agent-txn'>Agent Transaction</option>
                                                                    </select></div>

                                                        <div class='col-sm-6 col-md-3'>

                                                            <button onclick='searchrange_callover()' class='btn btn-primary custom_active' id='click-button'>Filter</button></div>

                                                    </div>
                                            </center>
                                                <div class="inv--detail-section inv--customer-detail-section">
                                                </div>
                                                <h5>Transaction Esusu</h5>
                                                <hr>
                                                <div class="inv--product-table-section">
                                                    <div class="table-responsive">
                                                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                                            <thead>
                                                                <th>#</th>
                                                                <th>Client</th>
                                                                <th>Account No</th>
                                                                <th>Description</th>
                                                                <th>Tran. Type</th>
                                                                <th>TXN No</th>
                                                                <th>Agent</th>
                                                                <th>Amount</th>
                                                                <th>Balance Before{{auth()->user()->company->currency->symbol}}</th>
                                                                <th>Balance After{{auth()->user()->company->currency->symbol}}</th>
                                                                <th>Session</th>
                                                                <th>Created At</th>

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
@endsection






