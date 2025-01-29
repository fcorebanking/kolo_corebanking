@extends('layouts.app')

@section('content')
<div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-header">                                
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>Restrict Customer</h4>
                </div>
            </div>
        </div>                            
        <div class="widget-content widget-content-area text-center">
        <form id='submit_Rform'>
            <div class="row">
                <div class="col-sm-6 col-md-6">
                    <select name='customer' id='customer' class='form-control form-small' style='margin:5px'>
                    
                    </select>
                </div>
                <div class="col-sm-6 col-md-6">
                    <select name='permission' id='permission' class='form-control basic' style='margin:5px'>
                    @if($permission)
                    @foreach($permission as $datarec)
                    <option value='{{$datarec->id}}'>{{($datarec->title)}}</option>

                    @endforeach
                    @endif
                    </select>
                </div>
                <div class="col-sm-12 col-md-12">
                    <textarea name='remark' id='remark' class='form-control' style='margin:5px'></textarea>
                </div>
            </div>
            
            <button type='submit' class="mr-2 btn btn-primary">Set</button>
            </form>


        </div>
    </div>
</div>  

<div class="layout-px-spacing">
                
    <div class="row layout-top-spacing">
    
        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <span id='infobait'></span>
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>
        
                        <th>Name</th>
                        <th>Phone</th>
                        <th>AccountNo</th>
                        <th>Restriction</th>
                        
                        <th>Remark</th>
                        <th>By</th>
            
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
<!-- Modal -->


@endsection 

