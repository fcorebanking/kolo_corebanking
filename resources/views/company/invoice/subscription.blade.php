@extends('layouts.app')

@section('content')
<div class="row invoice layout-top-spacing layout-spacing" id='printinvoice'>
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                        
                        <div class="doc-container">

                            <div class="row">

                                <div class="col-xl-9">

                                    <div class="invoice-container">
                                        <div class="invoice-inbox">
                                            
                                            <div id="ct" class="">
                                                
                                                <div class="invoice-00001">
                                                    <div class="content-section">
    
                                                        <div class="inv--head-section inv--detail-section">
                                                        
                                                            <div class="row">
                                                            
                                                                <div class="col-sm-6 col-12 mr-auto">
                                                                    <div class="d-flex">
                                                                        <img class="company-logo" src="{{asset($sub->company->logo)}}" alt="{{$sub->company->short_name}}">
                                                                        <h3 class="in-heading align-self-center">{{$sub->company->company_name}}.</h3>
                                                                    </div>
                                                                </div>

                                                                <div class="col-sm-6 text-sm-right">
                                                                    <p class="inv-list-number"><span class="inv-title">Invoice : </span> <span class="inv-number">#{{$sub->TXNlog->transaction_reference}}</span></p>
                                                                </div>

                                                                <div class="col-sm-6 align-self-center mt-3">
                                                                    <p class="inv-street-addr">{{$sub->company->address}}</p>
                                                                   
                                                                   
                                                                </div>
                                                                <div class="col-sm-6 align-self-center mt-3 text-sm-right">
                                                                    <p class="inv-created-date"><span class="inv-title">Invoice Date : </span> <span class="inv-date">{{$sub->created_at}}</span></p>
                                                                   
                                                                </div>
                                                            
                                                            </div>
                                                            
                                                        </div>
    
                                                        <div class="inv--detail-section inv--customer-detail-section">

                                                            <div class="row">
    
                                                                <div class="col-xl-8 col-lg-7 col-md-6 col-sm-4 align-self-center">
                                                                    <p class="inv-to">Invoice To</p>
                                                                </div>

                                                                <div class="col-xl-4 col-lg-5 col-md-6 col-sm-8 align-self-center order-sm-0 order-1 inv--payment-info">
                                                                    <h6 class=" inv-title">Payment Info:</h6>
                                                                </div>
                                                                
                                                                <div class="col-xl-8 col-lg-7 col-md-6 col-sm-4">
                                                                    <p class="inv-customer-name">Core Banking</p>
                                                                    <!--<p class="inv-email-address">01-8880311 </p>-->
                                                                </div>
                                                                
                                                                <div class="col-xl-4 col-lg-5 col-md-6 col-sm-8 col-12 order-sm-0 order-1">
                                                                    <div class="inv--payment-info">
                                                                        <p><span class=" inv-subtitle">Bank Name:</span> <span>PROVIDUS BANK</span></p>
                                                                        <p><span class=" inv-subtitle">Account Name:</span> <span>Core Banking</span></p>
                                                                        <p><span class=" inv-subtitle">Account Number: </span> <span>**********</span></p>
                                                                     
                                                                        <p><span class=" inv-subtitle">Country:</span> <span>Nigeria</span></p>

                                                                    </div>
                                                                </div>

                                                            </div>
                                                            
                                                        </div>

                                                        <div class="inv--product-table-section">
                                                            <div class="table-responsive">
                                                                <table class="table">
                                                                    <thead class="">
                                                                        <tr>
                                                                            <th scope="col">S.No</th>
                                                                            <th scope="col">Items</th>
                                                                            <th class="text-right" scope="col">Qty</th>
                                                                            <th class="text-right" scope="col">Price</th>
                                                                            <th class="text-right" scope="col">Amount</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1</td>
                                                                            <td>Core Subsciption</td>
                                                                            <td class="text-right">{{$sub->quantity}}</td>
                                                                            <td class="text-right">${{$sub->plan->daily_rates * 31}}</td>
                                                                            <td class="text-right">{{auth()->user()->company->currency->symbol}} {{$sub->TXNlog->amount}}</td>
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
                                                                                <p class="">{{auth()->user()->company->currency->symbol}}{{$sub->TXNlog->amount -50}}</p>
                                                                            </div>
                                                                            <div class="col-sm-8 col-7">
                                                                                <p class="">Tax Amount: </p>
                                                                            </div>
                                                                            <div class="col-sm-4 col-5">
                                                                                <p class="">{{auth()->user()->company->currency->symbol}}0</p>
                                                                            </div>
                                                                            <div class="col-sm-8 col-7">
                                                                                <p class=" discount-rate">Discount : <span class="discount-percentage">{{($sub->quantity >=12)?$sub->plan->discount_percent : 0}}%</span> </p>
                                                                            </div>
                                                                            <div class="col-sm-4 col-5">
                                                                                <p class="">{{auth()->user()->company->currency->symbol}}{{($sub->plan->discount_percent > 0 && $sub->quantity >=12)? ($sub->plan->discount_percent/100)*(($sub->plan->daily_rates * 31)*$sub->quantity) :0}}</p>
                                                                            </div>
                                                                            <div class="col-sm-8 col-7 grand-total-title">
                                                                                <h4 class="">Grand Total : </h4>
                                                                            </div>
                                                                            <div class="col-sm-4 col-5 grand-total-amount">
                                                                                <h4 class="">{{auth()->user()->company->currency->symbol}}{{$sub->TXNlog->amount}}</h4>
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

                                                        </div>
    
                                                    </div>
                                                </div> 
                                                
                                            </div>
    
    
                                        </div>
    
                                    </div>

                                </div>

                                <div class="col-xl-3">

                                    <div class="invoice-actions-btn">

                                        <div class="invoice-action-btn">

                                            <div class="row">
                                            <div class="col-xl-12 col-md-3 col-sm-6">
                                                    <a href="javascript:void(0);" class="btn btn-success btn-send">Send</a>
                                                </div>
                                                <div class="col-xl-12 col-md-3 col-sm-6">
                                                    <a href="javascript:void(0);" onclick='printpage()' class="btn btn-secondary btn-print  action-print">Print</a>
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
</script>
@endsection