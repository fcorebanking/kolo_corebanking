@extends('layouts.app')

@section('content')
<script src="{{asset('assets/js/libs/jquery-3.1.1.min.js')}}"></script>
<button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" onclick='addnew()'>
    Add Day &nbsp<i class='fa fa-plus'></i>
</button>
<div class='render_page'></div>
</button>
<div class="layout-px-spacing">
                
                <div class="row layout-top-spacing">
                
                    <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                        <div class="widget-content widget-content-area br-6">
                            <div class="table-responsive mb-4 mt-4">
                                <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                        <th>S/N</th>
                                            <th>Days</th>
                                            
                                            <th>Holiday</th>
                                            <th>Start-Time</th>
                                            <th>End-Time</th>
                                       
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Modified By</th>
                                            <th>Updated At</th>
                                            <th>Created At</th>
                                             <th>Action</th>
                                        </tr> 
                                    </thead>
                                    <tbody class='render_page'><?php $i =1; ?>
                                        @if(count($days) > 0)
                                            @foreach($days as $product_data)
                                              <tr>
                                                 <td>{{$i++}}</td>

                                                <td>{{($product_data->nday !=null)?checkdate_day($product_data->nday):'Holiday'}}</td>
                                                <td>{{$product_data->holiday}}</td>

                                                <td>{{$product_data->starttime}}</td>
                                                <td>{{$product_data->endtime}}</td>
                                              
                                               
                                                <td><span class="shadow-none badge badge-primary">{{$product_data->status_id?$product_data->status->name:'none'}}</span></td>
                                                <td>{{$product_data->author->first_name}}</td>
                                                <td>{{($product_data->modified_by!=null)?$product_data->editor->first_name : 'none'}}</td>
                                                <td>{{$product_data->updated_at}}</td>
                                                <td>
                                                {{$product_data->created_at}}
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                    <button class='btn btn-secondary' ><svg xmlns="http://www.w3.org/2000/svg" onclick="edit_product('')"  width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-2 edit"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg></button>
    
                                                  <form action='opening/delete/{{$product_data->id}}' method='post'> @method('delete') {{ csrf_field() }} <button type='submit' class='btn btn-secondary'><svg xmlns="http://www.w3.org/2000/svg"  width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle table-cancel"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg></button></form></td>

                                                       
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

</div>
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Opening - Closing time</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id='add-timing'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" class="id" name="id" value="0">

                <div class="modal-body" >
                    <div class='form-group'>
                    <select name='byday_date' id='byday_date' onchange="swapday()" class='form-control'>
                            <option value='day'>By Day Opening</option>
                            <option value='date'>Holiday</option>
                    </select>

                    </div>
                    <div class='date' style="display:none;">
                        <input type='date' name='holiday' id='holiday' class='form-control'>

                    </div>
                    <div class='day'>


                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <select name='snday' class='form-control'>
                            <option value='1'>Monday</option>
                            <option value='2'>Tuesday</option>
                            <option value='3'>Wednessday</option>
                            <option value='4'>Thursday</option>
                            <option value='5'>Friday</option>
                            <option value='6'>Sarturday</option>
                            <option value='7'>Sunday</option>

                        </select>
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Start Time</span>
                        </div>
                        <input type='time' name='start' id='start' class='form-control'>
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">End Time</span>
                        </div>
                        <input type='time' name='end' id='end' class='form-control'>
                    </div>
                    </div>
                   
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" class="btn btn-primary" >Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<!-- Modal -->


<script>
     $("#add-timing").on('submit',function() {
        ajax_operation('put','opening/save',$(this).serialize());
        ajax_fetch_openings();
        return false;
    });
    function swapday(){

        $state = $('#byday_date').val();
        if($state == 'day'){
            $('.day').show();
            $('.date').hide();

        }else{
            $('.day').hide();
            $('.date').show();

        }
    }
   
    swapday();
    
    
    function ajax_fetch_openings(){   
        $.ajax({
        url:'opening/get-table',
        type: 'get',
        data: {},
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success:function(da_ta){
        $data_page = da_ta;
        
        $(".render_page").html('');
        $("tbody").empty();
        $data_page.forEach(function(datas){
            
            
                $("tbody").append(datas);
        

        });
            
   
        },
        error: function(jqXHR, status, error) {
        
        
            $('.render_page').addClass( "bg-danger" );
        var msg = '';
        if (jqXHR.status === 0) {
            msg = 'Not connected\n Verify Network.';
        } else if (jqXHR.status == 404) {
            msg = 'Requested page not found. [404]';
        } else if (jqXHR.status == 500) {
            msg = 'Internal Server Error [500].';
        } else{
            msg = 'Uncaught Error.\n' +error;
        }
    
        $('.render_page').html(msg);
            },
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                xhr.onprogress = function e() {
                    // For downloads
                    if (e.lengthComputable) {
                        console.log(e.loaded / e.total);
                        $(".render_page").html(e.loaded / e.total);
                    }
                };
                xhr.upload.onprogress = function (e) {
                    // For uploads
                    if (e.lengthComputable) {
                        console.log(e.loaded / e.total);
                    }
                };
                return xhr;
            }
            }).done(function() {
        // $('.render_page').addClass( "bg-success" );
            });
             
  }
  ajax_fetch_openings();
  function edit_item(id){
    $('.id').val(id);
    $.ajax({
    url:'opening/edit/'+id,
    type: 'get',
    data: {},
    dataType: 'json',
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
    success:function(da_ta){
      
     
    $('#exampleModal').modal('show');
    if(da_ta.nday == null){
        $('#holiday').val(da_ta.holiday);
        
        $('#byday_date').val('date').change();
        $('.day').hide();
            $('.date').show();
    }else{
        $('#byday_date').val('day').change();
        $('#snday').val(da_ta.nday).change();
        $('.day').show();
            $('.date').hide();

            $('#start').val(da_ta.starttime);
            $('#end').val(da_ta.endtime);
    }
    
           
   
    }
  });
}
function addnew(){
    $('.id').val(0);
    $('#exampleModal').modal('show');
}
</script>

@endsection