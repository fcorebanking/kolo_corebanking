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
                                                            <h3 class="inv-customer-name">{{auth()->user()->company->company_name}} End oF Day</h3>
                                                            
                                                            
                                                        </div>
                                                        <div class="col-sm-6 align-self-center mt-3 text-sm-right">
                                                            
                                                        </div>
                                                    
                                                    </div>
                                                    
                                                </div>
                                               <center>
                                                   <div class='row'>
                                                  
                                                        <div class='col-sm-6 col-md-4'>
                                                            <label for='from'>from</label>
                                                            <input type='date' class='form-control' id='from' ></div>
                                                        <div class='col-sm-6 col-md-4'>
                                                            <label for='to'>to</label>
                                                            <input type='date' class='form-control' id='to' ></div>
                                           
                                                        <div class='col-sm-6 col-md-3'>
                                                           
                                                            <button onclick='searchrange_endday()' class='btn btn-primary custom_active'><i class='fa fa-search'></i></button></div>
                                                       
                                                    </div>
                                            </center> 
                                                <div class="inv--detail-section inv--customer-detail-section">

                                                    
                                                    
                                                </div>
                                                <h5>Transaction End of Day</h5> 
                                                <hr>
                                                
                                                <div class="inv--product-table-section">
                                                    <div class="table-responsive">
                                                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                                            <thead>
                                                               
                                                                <th>Transaction Type</th>
                                                                <th>Amount ({{auth()->user()->company->currency->symbol}})</th>
                                                               
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






