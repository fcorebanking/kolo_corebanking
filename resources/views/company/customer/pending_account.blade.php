@extends('layouts.app')

@section('content')


<div class="layout-px-spacing">
                
    <div class="row layout-top-spacing">
    
        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <span id='infobait'></span>
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>        
                        <th>FirstName</th>
                        <th>LastName</th>
                        <th>AccountNo</th>
                        <th>Phone</th>
                        <th>Account Type</th>
                        <th>Balance</th>   
                        <th>Branch</th>   
                        <th>Created By</th> 
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


  <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" >
              <div class="user-profile layout-spacing">
                    <div class="widget-content widget-content-area" id='view_profile'>
                        
                    </div>
                </div>
         
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
           
        </div>
      </div>
    </div>
  </div>

@endsection 



