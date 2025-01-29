@extends('layouts.app')

@section('content')


<div class="layout-px-spacing">
    <div class='card' style='margin:10px;padding:10px;'>
    <div class='row'>
         <div class='col-sm-6 col-md-3'><button onclick='switch_view(1)' class='btn btn-warning'>View Active</button></div>
        <div class='col-sm-6 col-md-3'><button  onclick='switch_view(3)' class='btn btn-success'>View Complete</button></div>
        <div class='col-sm-6 col-md-3'><button  onclick='switch_view(2)' class='btn btn-danger'>View Terminated</button></div>
    </div>
    </div>


    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <span id='infobait'></span>
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>

                        <th>Name</th>
                        <th>Account No</th>
                        <th>Account</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Interest%</th>
                        <th>Duration</th>
                        <th>Balance</th>
                        <th>Expected Balance</th>
                        <th>No.Days</th>
                        <th>Begin</th>
                        <th>Maturity</th>


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
<!-- Modal -->

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Investment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form  method='post' action='update-investment' enctype="multipart/form-data" >
                    @method('PUT')
                    {{ csrf_field() }}
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" id="uuid" name="investment_id" >

                <div class="modal-body">
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Investment Product</span>
                        </div>
                        <select  name='investmentproduct'  id='investmentproduct' class='form-control' onchange='calculateinvestment()'>

                        </select>

                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Enter Amount</span>
                        </div>
                        <input type="number"  class="form-control" name='amount' id='amount' aria-describedby="basic-addon3"
                            placeholder="50...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Start Date</span>
                        </div>
                        <input type="date" class="form-control" name='investment_date' id='investment_date' aria-describedby="basic-addon3">
                    </div>

                    <div class="alert alert-warning"><b>Please Note:</b> if the amount entered is higher than the previous amount, customer will be debitted from main wallet. If the amount is leass than the previous value then the main wallet will be creditted with the extracash remaining else no debit\credit will be done</div>

                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" class="btn btn-primary">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

