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

            <div class="row">

                <div class="col-xl-12">

                    <div class="invoice-container">
                        <div class="invoice-inbox">

                            <div id="ct" class="">

                                <div class="invoice-00001">
                                    <div class="content-section">

                                        <div class="inv--head-section inv--detail-section">

                                            <div class="row">

                                                <div class="col-sm-6 col-12 mr-auto">
                                                    <div class="d-flex">
                                                        <?php $logo = url(str_replace('public', 'storage', auth()->user()->company->logo)); ?>

                                                        <img class="company-logo" src="{{$logo}}" alt="{{auth()->user()->companyshort_name}}">
                                                        <h3 class="in-heading align-self-center">.</h3>
                                                    </div>
                                                </div>

                                                <div class="col-sm-6 text-sm-right">
                                                    <p class="inv-list-number"><span class="inv-title">User ID : </span> <span class="inv-number">#{{$user->uuid}}</span></p>
                                                </div>

                                                <div class="col-sm-6 align-self-center mt-3">
                                                    <p class="inv-street-addr"></p>
                                                    <p class="inv-customer-name">Company: {{$user->company->company_name}} </p>


                                                </div>
                                                <div class="col-sm-6 align-self-center mt-3 text-sm-right">
                                                    <p class="inv-created-date"><span class="inv-title">Started Date : {{$user->created_at}} </span> <span class="inv-date"></span></p>

                                                </div>

                                            </div>

                                        </div>

                                        <div class="inv--detail-section inv--customer-detail-section">

                                            <div class="row">

                                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-4 align-self-center">
                                                    <p class="inv-to">Customer Info</p>
                                                </div>

                                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-8 align-self-center order-sm-0 order-1 inv--payment-info">
                                                    <h6 class=" inv-title">Transaction Info:</h6>
                                                </div>

                                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-4">
                                                    <p class="inv-customer-name"> {{$user->first_name.' '.$user->last_name}}</p>
                                                    <p class="inv-email-phone">{{$user->phone}}</p>
                                                    <p class="inv-email-address">{{$user->email}}</p>
                                                    <p class="inv-street-addr"> {{$user->address}}</p>


                                                </div>

                                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-8 col-12 order-sm-0 order-1">
                                                    <div class="inv--payment-info">
                                                        <div class="totalinfo">
                                                            <hr>
                                                            <p class=" inv-subtitle btn btn-success"><span>Deposit: {{auth()->user()->company->currency->symbol}}</span> <span class="credit"></span></p>
                                                            <p class=" inv-subtitle btn btn-warning"><span>Withdraw: {{auth()->user()->company->currency->symbol}}</span> <span class="debit"></span></p>
                                                            <p onclick="viewinvestment()" class=" inv-subtitle btn btn-info"><span>Investment: {{auth()->user()->company->currency->symbol}}</span> <span class="investment"></span></p>
                                                            <p class=" inv-subtitle btn btn-primary"><span>Wallet Balance: {{auth()->user()->company->currency->symbol}}</span> <span class="wallet"></span></p>
                                                        </div>



                                                    </div>



                                                </div>

                                            </div>

                                        </div>
                                        <h5>Wallet Account List</h5>
                                        <hr>
                                        <input type='hidden' id='origininvestmenturl' value="{{url('company/'.Auth()->user()->company->short_name.'/view-user-investment/')}}">
                                        <input type='hidden' id='investmenturl' value="#">
                                        <input type='hidden' id='selectedaccountid' value="">
                                        <input type='hidden' id='virtual_account' value="">
                                        <div class='row'>
                                            <div class='col-sm-6 col-md-9'>
                                                <div class='row mt-n1 ' id='accountlist'>

                                                </div>
                                            </div>
                                            <div class='col-sm-6 col-md-3'><button onclick='pop_multiwallet()' class='btn btn-primary custom_active'>Add Wallet<i class='fa fa-plus'></i></button></div>
                                        </div>
                                        <input type='hidden' id='cus-phone' value='{{$user->phone}}'>
                                        <div class='row'>
                                            <div class='col-sm-6 col-md-3'><input type='date' class='form-control' id='from'></div>
                                            <div class='col-sm-6 col-md-3'><input type='date' class='form-control' id='to'></div>
                                            <div class='col-sm-6 col-md-3'><button onclick='searchrange()' class='btn btn-primary custom_active'>Search<i class='fa fa-search'></i></button></div>
                                            <div class='col-sm-6 col-md-3'><button onclick='searchdownload()' class='btn btn-primary custom_active'>Search and Download<i class='fa fa-download'></i></button></div>
                                        </div>
                                        <div class="inv--product-table-section">
                                            <div class="table-responsive">
                                                <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                                    <thead>

                                                        <th>SN</th>
                                                        <th>TXN Reference</th>
                                                        <th>Account</th>
                                                        {{-- <th>AccountNo</th> --}}

                                                        <th>Balance Before</th>
                                                        <th>Amount</th>
                                                        <th>Balance after</th>

                                                        <th>Withdrawal Charge</th>
                                                        <th>Transaction</th>
                                                        {{-- <th>TXN Type</th> --}}
                                                        {{-- <th>TXN Remark</th> --}}
                                                        <th>Description</th>

                                                        {{-- <th>Created By</th> --}}
                                                        {{-- <th>Modified By</th> --}}
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

                {{-- <div class="col-xl-2">

                                <div class="invoice-actions-btn">

                                    <div class="invoice-action-btn">

                                        <div class="row">
                                        <div class="col-xl-12 col-md-3 col-sm-6">
                                                <a href="javascript:void(0);" class="btn btn-success btn-send">Send</a>
                                            </div>
                                            <div class="col-xl-12 col-md-3 col-sm-6">
                                                <a href="javascript:void(0);" onclick='printpage()' class="btn btn-secondary btn-print  action-print">Print <i class='fa fa-print'></i></a>
                                            </div>


                                        </div>
                                    </div>

                                    </div>
                                </div> --}}


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
                <div class="alert alert-info">Kindly note : customer must have <b>Valid BVN</b>, <b>Phone number</b> and <b>Email</b> on there profile before virtual account can be linked</div>
                <input type='hidden' name='userid' id='userid' value='{{$uuid}}'>

                <div class="form-group">
                    <label for="">Replace Account Number </label>
                    <select name='replace_account' id='replace_account' class='form-control' required>
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="">Replace Currency</label>
                    <select name='currency_id' id='currency_id' class='form-control'>
                        @if ($currencies)
                        @foreach ($currencies as $currenciesdata)
                        <option value="{{$currenciesdata->id}}">{{$currenciesdata->name}}</option>
                        @endforeach
                        @endif
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>

                <div class="form-group ">
                    <label for="">Account Type </label>
                    <select name='saving_type' id='saving_type' class='form-control' required>
                        <option value="">::Select Account Type::</option>
                        <option value="virtual">Virtual Account</option>
                        <option value="internal">Internal Account</option>
                    </select>
                </div>

                <div class="form-group ">
                    <label for="">Savings Product Type </label>
                    <select name='savings_product_type' id='savings_product_type' class='form-control' required> </select>
                </div>

                <div class="form-group" id="bankSelectDiv">
                    <label for="">Bank</label>
                    <select name="account_bank" id="account_bank" class="form-control">
                        <option value="">::Select Bank::</option>

                        @if(count($banks) > 0)
                        @foreach($banks as $bank)
                        <option value='{{$bank->bank_code}}'>{{$bank->bank_name}}</option>
                        @endforeach

                        @endif
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-dismiss="modal">Close</button>
                <button type="button" id="addWalletBtn" class="btn btn-primary" onclick='addwallet()'>Add new</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="replaceBankModal" tabindex="-1" role="dialog" aria-labelledby="replaceBankModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Replace Bank</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">Kindly note: This customer bank name will be updated to <b>Beyond MFB</b> and cannot be undone</div>
                <input type='hidden' name='userid' id='userid' value='{{$uuid}}'>
                <input type='hidden' name='account_id' id='account_id'>

                <div class="form-group">
                    <label for="">Bank </label>
                    <select name='replacement_bank' id='replacement_bank' class='form-control' required>
                        <option value="no">Beyond MFB</option>
                    </select>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-dismiss="modal">Close</button>
                <button type="button" id="replaceBankBtn" class="btn btn-primary" onclick='replaceBank()'>Replace Bank</button>
            </div>
        </div>
    </div>
</div>

@endsection