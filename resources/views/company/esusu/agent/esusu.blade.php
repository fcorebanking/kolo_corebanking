@extends('layouts.app')

@section('content')
    <div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-content widget-content-area text-center">
                <div class="row ">
                    <div class="col-sm-12 col-md-12 col-12 order-sm-0 order-1">
                        <button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal"
                            data-target="#exampleModal" onclick='pop_modal(0)'>
                            Add Esusu Customer &nbsp<i class='fa fa-plus'></i>
                        </button>
                        <div class="tab-content" id="v-right-pills-tabContent">
                            <div class="tab-pane fade show active" id="v-right-pills-home" role="tabpanel"
                                aria-labelledby="v-right-pills-home-tab">
                                <h4 class="mb-4">Active Esusu</h4>
                                <div class="layout-px-spacing">
                                    <div class='table-responsive'>

                                        <div class="row layout-top-spacing">

                                            <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                                                <div class=" mb-4 mt-4">
                                                    <span id='infobait'></span>
                                                    <table id="html5-extension" class="table table-hover non-hover"
                                                        style="width:100%">
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
                            </div>
                        </div>
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
                    <h5 class="modal-title" id="exampleModalLabel">Esusu New Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form id='add-esusu-cstomer'>
                    <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" id="productid">
                    <div class="modal-body">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Name</span>
                            </div>
                            <select name='customerx' id='customerx' class='form-control form-small' style='margin:5px'>
                            </select>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Start Date</span>
                            </div>
                            <input type="date" class="form-control" name="start_date" id="start_date"
                                placeholder="Start Date" min='1930-01-01'>
                            <script>
                                document.getElementById("myDate").max = "2014-01-01";
                            </script>

                            <span id="dob_error" class="text-danger font-weight-bold"></span>
                        </div>

                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Min. Rate</span>
                            </div>
                            <input type="text" class="form-control" id='min_rate' name='min_rate'
                                aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        {{-- new added --}}



                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                        <button type="button" class="btn btn-primary" onclick='btnadd()'>Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
