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
     $logo = str_replace('public', 'storage',@$txn->company->logo);
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
                                            <img class="company-logo" src="{{asset($logo)}}" alt="{{@$txn->company->short_name}}">
                                            <h3 class="in-heading align-self-center">{{@$txn->company->company_name}}.</h3>
                                            <p class="inv-street-addr">{{@$txn->company->address}}</p>
                                            <span class='text-center'>Transaction Receipt</span>
                                            <table class='table'>
                                                <tr>
                                                    <td>Transaction Ref:</td>
                                                    <td>{{@$txnid}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Transaction Date:</td>
                                                    <td>{{@$txn->created_date}}</td>
                                                </tr>
                                                @if (@$txn->instrumentCode)
                                                    <tr>
                                                        <td>InstrumentCode:</td>
                                                        <td>{{@$txn->instrumentCode}}</td>
                                                    </tr>
                                                @endif
                                                <tr>
                                                    <td>Transaction Amount:</td>
                                                    <td>{{auth()->user()->company->currency->symbol}}{{@@$txn->company_amount}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Account Name:</td>
                                                    <td>{{ @$txn->company->createdby->first_name.' '.@$txn->company->createdby->last_name}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Account Number:</td>
                                                    <td>{{@$txn->beneficiary->virtual_accnum}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Account email:</td>
                                                    <td>{{@$txn->company->createdby->email }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Account Phone:</td>
                                                    <td>{{@$txn->company->createdby->phone }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Transaction Type:</td>
                                                    <td><span class='badge badge-info'>{{@$txn->transaction_type}}</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Transaction:</td>
                                                    <td><span class='badge {{(@$txn->creditDebitType->id == 1)?"badge-success":"badge-warning"}} '>{{@$txn->creditDebitType->type}}</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Status:</td>
                                                    <td>{{ @$txn->remark->transaction_remark}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Description:</td>
                                                    <td>{{ @$txn->description}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Account Address:</td>
                                                    <td>{{ @$txn->company->createdby->address}}</td>
                                                </tr>

                                            </table>


                                        </div>
                                    </div>

                                </div>

                                <div class="invoice-actions-btn">

                                    <div class="invoice-action-btn">

                                        <div class="row">

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
