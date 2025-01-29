@extends('layouts.app')
<style>
    @media only screen and (max-width: 600px) {

    }
    @media only screen and (min-width: 650px) {
        .vertical{
        max-width:50%;
        margin: 0 auto;
        }
    }

td{
    font-weight: 600;
    font-size: 12px;
    padding:10px;
}
.inv-street-addr{
    padding:5px;
}
.company-logo{
    width:100px;
}
.invoice-inbox,.content-section{
    margin: 0 auto;
    text-align: center;
}
tr,td{
    /* text-align: center; */
}.content-section{
    padding:10px;
}
</style>
<div id='printinvoice'>
@section('content')
@php
     $logo = str_replace('public', 'storage',@$txn->author->company->logo);
@endphp

    <div class="row invoice layout-top-spacing layout-spacing" >
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

            <div class="container">

                <div class='vertical fullScreen2'>


                        <div class="invoice-container">
                            <div class="invoice-inbox">


                                <div id="ct" class="">

                                    <div class="invoice-00001">
                                        <div class="content-section">
                                            <img class="company-logo" src="{{asset($logo)}}" alt="{{@$txn->author->company->short_name}}">
                                            <h3 class="in-heading align-self-center">{{@$txn->author->company->company_name}}.</h3>
                                            <p class="inv-street-addr">{{@$txn->author->company->address}}</p>
                                            <span class='text-center'>Transaction Receipt</span>
                                            <table class='table'>
                                                <tr>
                                                    <td>Transaction Date:</td>
                                                    <td>{{@$txn->created_date??@$txn->created_at}}</td>
                                                </tr>
                                                @if ($txn->instrumentCode)
                                                    <tr>
                                                        <td>InstrumentCode:</td>
                                                        <td>{{@$txn->instrumentCode}}</td>
                                                    </tr>
                                                @endif

                                                <tr>
                                                    <td>Transaction Ref:</td>
                                                    <td>{{@$txn->transaction_reference}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Transaction Amount:</td>
                                                    <td>{{auth()->user()->company->currency->symbol}}{{@$txn->amount}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Balance After:</td>
                                                    <td>{{auth()->user()->company->currency->symbol}}{{@$txn->balance_after}}</td>
                                                </tr>

                                                <tr>
                                                    <td>Account Name:</td>
                                                    <td>{{ $txn->beneficiary->user->first_name.' '.$txn->beneficiary->user->last_name}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Account Number:</td>
                                                    <td>{{@$txn->beneficiary->virtual_accnum}}</td>
                                                </tr>
                                                {{-- <tr>
                                                    <td>Account email:</td>
                                                    <td>{{@$txn->beneficiary->user->email }}</td>
                                                </tr> --}}

                                                {{-- <tr>
                                                    <td>Account Phone:</td>
                                                    <td>{{@$txn->beneficiary->user->phone }}</td>
                                                </tr> --}}
                                                <tr>
                                                    <td>Transaction Type:</td>
                                                    <td><span class='badge badge-info'>{{@$txn->transaction_type}}</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Transaction:</td>
                                                    <td><span class='badge {{($txn->creditDebitType->id == 1)?"badge-success":"badge-warning"}} '>{{@$txn->creditDebitType->type}}</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Status:</td>
                                                    <td>{{ $txn->remark->transaction_remark}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Description:</td>
                                                    <td>{{ $txn->description}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Account Address:</td>
                                                    <td>{{ $txn->beneficiary->user->address}}</td>
                                                </tr>

                                            </table>
                                            {{-- <div class="inv--head-section inv--detail-section">

                                                <div class="row">

                                                    <div class="col-sm-6 col-12 mr-auto">
                                                        <div class="d-flex">
                                                            <img class="company-logo" src="{{asset($txn->author->company->logo)}}" alt="{{@$txn->author->company->short_name}}">
                                                            <h3 class="in-heading align-self-center">{{@$txn->author->company->company_name}}.</h3>
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-6 text-sm-right">
                                                        <p class="inv-list-number"><span class="">TXN Id: </span> <span class="">#{{@$txn->transaction_reference}}</span></p>
                                                    </div>

                                                    <div class="col-sm-6 align-self-center mt-3">
                                                        <p class="inv-street-addr">{{@$txn->author->company->address}}</p>
                                                        <p class="inv-customer-name">By: {{@$txn->author->first_name.' '.$txn->author->last_name}}</p>


                                                    </div>
                                                    <div class="col-sm-6 align-self-center mt-3 text-sm-right">
                                                        <p class="inv-created-date"><span class="inv-title">TXNDate : </span> <span class="inv-date">{{@$txn->created_at}}</span></p>

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="inv--detail-section inv--customer-detail-section">

                                                <div class="row">

                                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-4 align-self-center">
                                                        <p class="inv-to">Invoice To</p>
                                                    </div>

                                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-8 align-self-center order-sm-0 order-1 inv--payment-info">
                                                        <h6 class=" inv-title">Payment Info:</h6>
                                                    </div>

                                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-4">
                                                        <p class="inv-customer-name">{{ $txn->beneficiary->user->first_name }}</p>
                                                        <p class="inv-street-addr">{{ $txn->beneficiary->user->address }}</p>
                                                        <p class="inv-email-address">{{ $txn->beneficiary->user->email }}</p>
                                                        <p class="inv-email-address">{{ $txn->beneficiary->user->phone }} </p>
                                                    </div>

                                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-8 col-12 order-sm-0 order-1">
                                                        <div class="inv--payment-info">

                                                            <p><span class=" inv-subtitle">Account Name:</span> <span>{{ $txn->beneficiary->user->first_name.' '.$txn->beneficiary->user->last_name}}</span></p>
                                                            <p><span class=" inv-subtitle">Account Number: </span> <span>{{@$txn->beneficiary->virtual_accnum}}</span></p>



                                                        </div>
                                                    </div>

                                                </div>

                                            </div>

                                            <div class="inv--product-table-section">
                                                <div class="table-responsive">
                                                    <table class="table">
                                                        <thead class="">
                                                            <tr>

                                                                <th class="text-right" scope="col">Status</th>
                                                                <th class="text-right" scope="col">Balance After</th>
                                                                <th class="text-right" scope="col">Remark</th>
                                                                <th class="text-right" scope="col">Description</th>

                                                                <th class="text-right" scope="col">Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>

                                                                <td><span class='badge {{($txn->creditDebitType->id == 1)?"badge-success":"badge-warning"}} '>{{@$txn->creditDebitType->type}}</span></td>
                                                                <td class="text-right">{{auth()->user()->company->currency->symbol}}{{ $txn->balance_after}}</td>
                                                                <td class="text-right">{{ $txn->remark->transaction_remark}}</td>
                                                                <td class="text-right">{{@$txn->description}}</td>
                                                                <td class="text-right">{{auth()->user()->company->currency->symbol}}{{@$txn->amount}}</td>

                                                            </tr>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="inv--total-amounts">

                                                <div class="row mt-4">
                                                    <div class="col-sm-5 col-12 order-sm-0 order-1">
                                                    </div>
                                                    <div class="col-sm-7 col-12 order-sm-1 order-0">
                                                        <div class="text-sm-right">
                                                            <div class="row">
                                                                <div class="col-sm-8 col-7">
                                                                    <p class="">Sub Total: </p>
                                                                </div>
                                                                <div class="col-sm-4 col-5">
                                                                    <p class="">{{@$txn->amount}}</p>
                                                                </div>
                                                                <div class="col-sm-8 col-7">
                                                                    <p class="">TXN Charge: </p>
                                                                </div>
                                                                <div class="col-sm-4 col-5">
                                                                    <p class="">{{auth()->user()->company->currency->symbol}}{{@$txn->withdrawal_charge?$txn->withdrawal_charge:0}}</p>
                                                                </div>
                                                                <div class="col-sm-8 col-7">
                                                                    <p class="">SMS Charge: </p>
                                                                </div>
                                                                <div class="col-sm-4 col-5">
                                                                    <p class="">{{auth()->user()->company->currency->symbol}}{{@$txn->sms_charge?$txn->sms_charge:0}}</p>
                                                                </div>


                                                                <div class="col-sm-8 col-7 grand-total-title">
                                                                    <h4 class="">Grand Total : </h4>
                                                                </div>
                                                                <div class="col-sm-4 col-5 grand-total-amount">
                                                                    <h4 class="">{{auth()->user()->company->currency->symbol}}{{@$txn->amount + $txn->sms_charge + $txn->withdrawal_charge}}</h4>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="inv--note">

                                                <div class="row mt-4">
                                                    <div class="col-sm-12 col-12 order-sm-0 order-1">
                                                        <p>Note: Thank you for doing Business with us.</p>
                                                    </div>
                                                </div>

                                            </div> --}}

                                        </div>
                                    </div>

                                </div>

                                <div class="invoice-actions-btn">

                                    <div class="invoice-action-btn">

                                        <div class="row">
                                        {{-- <div class="col-xl-12 col-md-3 col-sm-6">
                                                <a href="javascript:void(0);" class="btn btn-success btn-send">Send</a>
                                            </div> --}}
                                            <div class="col-xl-12 col-md-3 col-sm-6">
                                                <a href="javascript:void(0);" onclick='printpage()' class="btn btn-secondary btn-print  action-print">Print</a>
                                            </div>
                                            <div class="col-xl-12 col-md-3 col-sm-6">
                                                <a href="{{url('company/'.Auth()->user()->company->short_name.'/saving')}}" class="btn btn-primary btn-print  action-print">Go Back</a>
                                            </div>


                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>

            </div>
                {{-- <div class='vertical card'>
                    <div class="row justify-content-center text-center">
                        <div class="col-sm-6 col-12">
                            <div class="d-flex">
                                <img class="company-logo" src="{{asset($txn->author->company->logo)}}" alt="{{@$txn->author->company->short_name}}">
                                <h3 class="in-heading align-self-center">{{@$txn->author->company->company_name}}.</h3>
                            </div>
                        </div>
                        <div class="col-sm-6 col-12">
                            <div class="d-flex">
                                <img class="company-logo" src="{{asset($txn->author->company->logo)}}" alt="{{@$txn->author->company->short_name}}">
                                <h3 class="in-heading align-self-center">{{@$txn->author->company->company_name}}.</h3>
                            </div>
                        </div>
                    </div>
                </div> --}}

            </div>

        </div>
    </div>

<script type="text/javascript">
    function printpage() {
        //Get the print button and put it into a variable
        var printButton = document.getElementById("printinvoice");
        //Set the print button visibility to 'hidden'
        printButton.style.visibility = 'hidden';
        //Print the page content
        window.print()
        //Set the print button to 'visible' again
        //[Delete this line if you want it to stay hidden after printing]
        printButton.style.visibility = 'visible';
    }
    function btnswapexpand(){
        $('.fullScreen').show();
        $('.vertical').hide();
    }
    function btnswapvertical(){
        $('.fullScreen').hide();
        $('.vertical').show();
    }
</script>
@endsection
</div>
