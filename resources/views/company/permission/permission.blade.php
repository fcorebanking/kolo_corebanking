@extends('layouts.app')

@section('content')

<button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#exampleModal">
    Role &nbsp<i class='fa fa-plus'></i>
                                        </button>
<div class='render_page'></div>
           <!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                
                </button>
            </div>
            <form id='addrole'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">
                                     
                <div class="modal-body">
                
                    <label for="basic-url">Name</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" class="form-control"  name='role_name' id='role_name' aria-describedby="basic-addon3" placeholder="role name...">
                    </div>
                    <span class="validation-text"></span>
                    <label for="basic-url">Role Permission</label>
                    <div class='row'>
                    @if(count($permission) > 0)
                        @foreach($permission as $data)
                             <div class='col-sm-6 col-md-6'> <input type='checkbox' name='permission[]' value='{{$data->id}}'>&nbsp;&nbsp;{{removeMinusText($data->name)}} </div>
                        @endforeach
                   @endif
                   </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit"  class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="edit_exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Change Role Permission</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                
                </button>
            </div>
            <form id='updaterole'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">
            <input type = "hidden" class="id" id='id' name = "id" >
                              
                <div class="modal-body">
                
                    <label for="basic-url">Name</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon8">Name</span>
                        </div>
                        <input type="text" class="form-control"  name='edit_role_name' id='edit_role_name' aria-describedby="basic-addon3" placeholder="role name...">
                    </div>
                    <span class="validation-text"></span>
                    <h4>Role Permissions</h4>
                    <div class='row'>
                    @if(count($permission) > 0)
                        @foreach($permission as $data)
                             <div class='col-sm-6 col-md-6' style="align-items: left;"> <input type='checkbox' name='edit_permission[]' value='{{$data->id}}'>&nbsp;&nbsp;{{removeMinusText($data->name)}} </div>
                        @endforeach
                   @endif
                   </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit"  class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection