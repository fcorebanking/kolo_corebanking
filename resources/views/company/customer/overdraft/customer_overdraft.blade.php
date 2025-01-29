@extends('layouts.app')

@section('content')
<div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12 mt-2">
                    <h4>Customer Overdraft</h4>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area text-center">

            <form id='submit_Rform'>
                <div class="row pb-4">
                    <div class="form-group col-sm-12 col-md-6 offset-md-3">


                        <div class="alert alert-info mb-4" role="alert" style="text-align: left;">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg></button>
                            <p> Overdraft means that the bank allows customers to borrow a set amount of money,
                                allowing
                                his savings account go negative.</p>

                            <p>There is interest on the loan, and there is typically a fee per overdraft This is
                                subject to the banks T & C</p>

                            <p>List of account with negative balance can be found on report.</p>
                        </div>

                        <label for="customer">Customer</label>
                        <select name='customer' id='customer' onchange="getCustomerBalance()"
                            class='form-control form-small' style='margin-top:5px; margin-bottom: 0;'>
                        </select>
                        <div style="display: none" id="myBankLoader">
                            <div class="d-flex"
                                style="flex-direction: row; margin-left: 0; margin-top: -20px !important; justify-content:flex-start; align-items: center">
                                <div class="spinner-border text-success loader-xs inline-block "
                                    style="height: 20px; width: 20px;"></div>&nbsp;
                                <p style="margin-top: 10px;">Getting Customer Balance</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-6 offset-md-3" id="balanceDiv" style="display: none;">
                        <h4>Customer Balance: <span id="customer_balance"></span></h4>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-6 offset-md-3">
                        <label for="amount">Overdraft Amount</label>
                        <input id="amount" oninput="validity.valid||(value='');" type="number" name="amount" placeholder="Enter Amount" class="form-control"
                            required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-6 offset-md-3">
                        <label for="interest">Interest (%)</label>
                        <input id="interest" type="number" name="interest" placeholder="Enter interest"
                            class="form-control" required>
                    </div>

                    <div class="mb-4 col-sm-12 col-md-6 offset-md-3">
                        <label for="frequency">Frequency</label>
                        <select name='frequency' id='frequency' class='form-control form-small' style="width:100%">
                            @if($frequencies)
                            @foreach($frequencies as $frequency)
                            <option value='{{$frequency->id}}'>{{($frequency->frequency)}}</option>
                            @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-6 offset-md-3 mb-5 mt-2">
                        <button type='submit' id="mySumit" class="btn btn-block btn-primary">Continue</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection