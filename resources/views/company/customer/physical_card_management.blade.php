@extends('layouts.app')

@section('content')
    <div class="layout-px-spacing">

        <div class="row layout-top-spacing">

            <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                <div class="widget-content widget-content-area br-6">
                    <div class="table-responsive mb-4 mt-4">
                        <span id='infobait'>
                            <h4>Customers Physical Cards</h4>
                        </span>
                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                            <thead>
                                <!-- <th>#</th> -->
                                <th>FirstName</th>
                                <th>LastName</th>
                                <th>Email</th>
                                <th>Account Number</th>
                                <th>Balance</th>
                                <th></th>
                            </thead>
                            <tbody>

                            </tbody>
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
                            <div class="alert alert-info">Noted: {{ $card_charge->new_physical_card_charge }} naira will be
                                deducted from this user as Card Transaction fees</div>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="staff_id" name='id'>

                            <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-4">
                                    <label for="c-first-name">First Name</label>
                                    <div class="input-group ">
                                        <input type="text" class="form-control" readonly id='c-first-name'
                                            name='firstname' aria-describedby="basic-addon3" placeholder="Daniel...">
                                    </div>
                                    <div class='validation-text'></div>
                                </div>
                                <div class="col-sm-6 col-md-6 mb-4">
                                    <label for="c-last-name">Last Name</label>
                                    <div class="input-group ">
                                        <input type="text" class="form-control" readonly id='c-last-name' name='lastname'
                                            aria-describedby="basic-addon3" placeholder="John...">
                                    </div>
                                    <div class='validation-text'></div>
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-sm-6 col-md-6 mb-4">

                                    <label for="c-phone">BVN</label>
                                    <div class="input-group">

                                        <input type="text" class="form-control" id='c-bvn' name='bvn'
                                            aria-describedby="basic-addon3" placeholder="Enter your BVN">
                                    </div>
                                    <div class='validation-text'></div>
                                </div>

                                <div class="col-sm-6 col-md-6 mb-4">

                                    <label for="c-phone">Phone</label>
                                    <div class="input-group">

                                        <input type="text" class="form-control" id='c-phone' name='phone'
                                            aria-describedby="basic-addon3" placeholder="2340 80...">
                                    </div>
                                    <div class='validation-text'></div>
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-sm-12 mb-4">
                                    <label for="c-email">Email</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id='c-email' name='email'
                                            aria-describedby="basic-addon3" placeholder="John123@domain.com...">
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

                            <div class="row">

                                <div class="form-group mb-4 col-sm-12">
                                    <label for="description">Card Number</label>
                                    <textarea class="form-control" placeholder="Enter Card Number" id="c-cardnmber" name="card" rows="1"></textarea>
                                    <span class="validation-text"></span>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i>
                                Discard</button>
                            <button type="button" id="requestCardBtn" onclick="createPhysicalCard()"
                                class="btn btn-primary">Request Card</button>
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
                                        <input type="text" readonly class="form-control" id='card_name'
                                            name='card_name' aria-describedby="basic-addon3"
                                            placeholder="Enter Card Name">
                                    </div>
                                    <div class='validation-text'></div>
                                </div>
                                <div class="col-sm-12 col-md-12 mb-4">
                                    <label for="card_type">Card Type</label>
                                    <div class="input-group ">
                                        <select name="card_type" id="card_type" class="form-control">
                                            <option selected="" value="">Choose Card Type</option>
                                            @foreach ($card_types as $card_type)
                                                <option value='{{ $card_type->id }}'>{{ $card_type->card_type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class='validation-text'></div>
                                </div>

                                <div class="col-sm-12 col-md-12 mb-4">
                                    <label for="card_number">Card Number (PAN)</label>
                                    <div class="input-group ">
                                        <input type="number" class="form-control" id='card_number' name='card_number'
                                            aria-describedby="basic-addon3" placeholder="Enter Card Number">
                                    </div>
                                    <div class='validation-text'></div>
                                </div>

                                <div class="col-sm-6 col-md-6 mb-4">
                                    <label for="exp_date">Expiry Date</label>
                                    <div class="input-group ">
                                        <input type="text" class="form-control" id='exp_date' name='exp_date'
                                            aria-describedby="basic-addon3" placeholder="Expiry Date">
                                    </div>
                                    <div class='validation-text'></div>
                                </div>

                                <div class="col-sm-6 col-md-6 mb-4">
                                    <label for="cvv">CVV</label>
                                    <div class="input-group ">
                                        <input type="text" class="form-control" id='cvv' name='cvv'
                                            aria-describedby="basic-addon3" placeholder="CVV">
                                    </div>
                                    <div class='validation-text'></div>
                                </div>

                            </div>

                        </div>
                        <div class="modal-footer">
                            <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i>
                                Discard</button>
                            <button type="button" id="addCardBtn" onclick="addPhysicalCard()"
                                class="btn btn-primary">Submit
                                Card</button>
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

                            <div class="alert alert-info"><b>Please note:</b> Funding will be rejected if card pin is still
                                default. kindly visit the nearet ATM gallery to change card pin to be able to prefund
                                successfully.
                            </div>

                            <div class="row">

                                <div class="col-sm-12 col-md-12 mb-4">
                                    <label for="card_type">Select Account</label>
                                    <div class="input-group ">
                                        <select name="account_id" id="account_id" class="form-control">
                                            <option selected="" value="">Choose Account Type</option>
                                        </select>
                                    </div>
                                    <div class='validation-text'></div>
                                </div>


                                <div class="col-sm-12 col-md-12 mb-4">
                                    <label for="fund_amount">Amount</label>
                                    <div class="input-group ">
                                        <input type="number" class="form-control" id='fund_amount' name='fund_amount'
                                            aria-describedby="basic-addon3" placeholder="Enter Amount to Fund">
                                    </div>
                                    <div class='validation-text'></div>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i>
                                Discard</button>
                            <button type="button" id="fundCardBtn" onclick="fundPhysicalCard()"
                                class="btn btn-primary">Continue</button>
                        </div>
                    </form>


                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal" id="cardBalanceModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Card Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>


                    <div class="modal-body">

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-md-6 text-semibold leftSummaryDiv bg-grey">
                                        <p class="summaryLabels">Card Name</p>
                                    </div>
                                    <div class="col-md-6 text-right leftSummaryDiv bg-grey">
                                        <p class="summaryValues" id="cardName"></p>
                                    </div>
                                    <hr />

                                    <div class="col-md-6 text-semibold leftSummaryDiv">
                                        <p class="summaryLabels" style=" float: left;">Card Number</p>
                                    </div>
                                    <div class="col-md-6 text-right leftSummaryDiv">
                                        <p><span class="summaryValues" id="cardNumber"></span> </p>
                                    </div>
                                    <hr />

                                    <div class="col-md-6 text-semibold leftSummaryDiv bg-grey">
                                        <p class="summaryLabels">Expiry Data</p>
                                    </div>
                                    <div class="col-md-6 text-right leftSummaryDiv bg-grey">
                                        <p><span class="summaryValues" id="expiryDate"></span> </p>
                                    </div>
                                    <hr />

                                    <div class="col-md-6 text-semibold leftSummaryDiv">
                                        <p class="summaryLabels">CVV
                                            </h6>
                                    </div>
                                    <div class="col-md-6 text-right leftSummaryDiv">
                                        <p> <span class="summaryValues" id="modalCvv">50</span></p>
                                    </div>
                                    <hr />

                                    <div class="col-md-6 text-semibold leftSummaryDiv">
                                        <p class="summaryLabels">Available Balance</p>
                                    </div>
                                    <div class="col-md-6 text-right leftSummaryDiv">
                                        <p> {!! auth()->user()->company->currency->symbol !!}
                                            <span class="summaryValues" id="modalfinalAmount">3,050</span>
                                        </p>
                                    </div>
                                    <hr />

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Close</button>
                    </div>


                </div>
            </div>
        </div>
    @endsection
