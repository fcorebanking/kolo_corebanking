@extends('layouts.app')

@section('content')


<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <span id='infobait'>
                        <h4>Customers Card Requests</h4>
                    </span>
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>
                            <th>#</th>
                            <th>Name</th>
                            <th>Account No</th>
                            <th>BVN</th>
                            <th>Phone Number</th>
                            <th>Request Date</th>
                            <th>Status</th>
                        </thead>
                        <tbody>

                        </tbody>

                        <tfoot>
                            <th>#</th>
                            <th>Name</th>
                            <th>Account No</th>
                            <th>BVN</th>
                            <th>Phone Number</th>
                            <th>Request Date</th>
                            <th>Status</th>
                        </tfoot>
                    </table>


                    <!-- Modal -->


                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="exampleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Card</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>


                <form id='add-staff'>
                    <div class="col-sm-12 mt-4">
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="staff_id" name='id'>

                        <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                        <div class="row">
                            <div class="col-sm-6 col-md-6 mb-4">
                                <label for="c-first-name">First Name</label>
                                <div class="input-group ">
                                    <input type="text" class="form-control" id='c-first-name' name='firstname' aria-describedby="basic-addon3" placeholder="Daniel...">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-6 col-md-6 mb-4">
                                <label for="c-last-name">Last Name</label>
                                <div class="input-group ">
                                    <input type="text" class="form-control" id='c-last-name' name='lastname' aria-describedby="basic-addon3" placeholder="John...">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                        </div>

                        <div class="row">

                            <div class="col-sm-6 col-md-6 mb-4">

                                <label for="c-phone">BVN</label>
                                <div class="input-group">

                                    <input type="text" class="form-control" id='c-bvn' name='bvn' aria-describedby="basic-addon3" placeholder="Enter your BVN">
                                </div>
                                <div class='validation-text'></div>
                            </div>

                            <div class="col-sm-6 col-md-6 mb-4">

                                <label for="c-phone">Phone</label>
                                <div class="input-group">

                                    <input type="text" class="form-control" id='c-phone' name='phone' aria-describedby="basic-addon3" placeholder="2340 80...">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                        </div>

                        <div class="row">

                            <div class="col-sm-12 mb-4">
                                <label for="c-email">Email</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id='c-email' name='email' aria-describedby="basic-addon3" placeholder="John123@domain.com...">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                        </div>

                        <div class="row">

                            <div class="form-group mb-4 col-sm-12">
                                <label for="description">Address</label>
                                <textarea class="form-control" placeholder="Enter Address" id="c-address" name="address" rows="3"></textarea>
                                <span class="validation-text"></span>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                        <button type="button" id="requestCardBtn" onclick="createPhysicalCard()" class="btn btn-primary">Request Card</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="cardModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Card</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>


                <form id='add-card'>

                    <div class="modal-body">
                        <input type="hidden" id="staff_id" name='id'>

                        <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                        <div class="row">
                            <div class="col-sm-12 col-md-12 mb-4">
                                <label for="card_name">Card Name</label>
                                <div class="input-group ">
                                    <input type="text" readonly class="form-control" id='card_name' name='card_name' aria-describedby="basic-addon3" placeholder="Enter Card Name">
                                </div>
                                <div class='validation-text'></div>
                            </div>
                            <div class="col-sm-12 col-md-12 mb-4">
                                <label for="card_type">Card Type</label>
                                <div class="input-group ">
                                    <select name="card_type" id="card_type" class="form-control">
                                        <option selected="" value="">Choose Card Type</option>

                                    </select>
                                </div>
                                <div class='validation-text'></div>
                            </div>

                            <div class="col-sm-12 col-md-12 mb-4">
                                <label for="card_number">Card Number (PAN)</label>
                                <div class="input-group ">
                                    <input type="number" class="form-control" id='card_number' name='card_number' aria-describedby="basic-addon3" placeholder="Enter Card Number">
                                </div>
                                <div class='validation-text'></div>
                            </div>

                            <div class="col-sm-6 col-md-6 mb-4">
                                <label for="exp_date">Expiry Date</label>
                                <div class="input-group ">
                                    <input type="text" class="form-control" id='exp_date' name='exp_date' aria-describedby="basic-addon3" placeholder="Expiry Date">
                                </div>
                                <div class='validation-text'></div>
                            </div>

                            <div class="col-sm-6 col-md-6 mb-4">
                                <label for="cvv">CVV</label>
                                <div class="input-group ">
                                    <input type="text" class="form-control" id='cvv' name='cvv' aria-describedby="basic-addon3" placeholder="CVV">
                                </div>
                                <div class='validation-text'></div>
                            </div>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                        <button type="button" id="addCardBtn" onclick="addPhysicalCard()" class="btn btn-primary">Submit Card</button>
                    </div>
                </form>


            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="fundCardModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pre-Fund Card</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>


                <form id='add-card'>

                    <div class="modal-body">
                        <input type="hidden" id="staff_id" name='id'>

                        <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                        <div class="row">
                            <div class="col-sm-12 col-md-12 mb-4">
                                <label for="fund_amount">Amount</label>
                                <div class="input-group ">
                                    <input type="number" class="form-control" id='fund_amount' name='fund_amount' aria-describedby="basic-addon3" placeholder="Enter Amount to Fund">
                                </div>
                                <div class='validation-text'></div>
                            </div>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                        <button type="button" id="fundCardBtn" onclick="fundPhysicalCard()" class="btn btn-primary">Continue</button>
                    </div>
                </form>


            </div>
        </div>
    </div>




    @endsection