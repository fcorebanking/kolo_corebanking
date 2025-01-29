@extends('layouts.app')

@section('content')


<div class="layout-px-spacing">
    <div class='card' style='margin:10px;padding:10px;'>
    <div class='row'>
         <div class='col-sm-6 col-md-3'><button onclick='switch_view(1,"{{$accountid}}")' class='btn btn-warning'>View Active</button></div>
        <div class='col-sm-6 col-md-3'><button  onclick='switch_view(3,"{{$accountid}}")' class='btn btn-success'>View Complete</button></div>
        <div class='col-sm-6 col-md-3'><button  onclick='switch_view(2,"{{$accountid}}")' class='btn btn-danger'>View Terminated</button></div>
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
      
             
           <!-- Modal -->


</div>
<!-- Modal -->


@endsection 

