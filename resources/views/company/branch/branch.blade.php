@extends('layouts.app')

@section('content')


                                        <div class="layout-px-spacing">
                                            <div class="row layout-top-spacing">
                                                <button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#exampleModal">
                                                    Add Branch &nbsp<i class='fa fa-plus'></i></button>
                                                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                                                    <div class="widget-content widget-content-area br-6">
                                                        <div class="table-responsive mb-4 mt-4">
                                                            <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                                                <thead>
                                                                    <tr>
                                                                    <th>S/N</th>
                                                                    <th>Code</th>

                                                                        <th>Branch Manager</th>
                                                                        <th>Branch Name</th>
                                                                        <th>Currency</th>
                                                                        <th>Address</th>
                                                                        <th>Status</th>
                                                                        <th>Created By</th>
                                                                        <th>Modified By</th>
                                                                        <th>Updated At</th>
                                                                        <th>Created At</th>
                                                                            <th>Action</th>
                                                                    </tr>
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
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Branch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id='add-branch'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">

                <div class="modal-body">

                    <label for="basic-url">Branch Name</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" class="form-control" id='branch_name' aria-describedby="basic-addon3" placeholder="Abuja...">
                    </div>
                    <label for="basic-url">Branch Prefix</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Code</span>
                        </div>
                        <input type="number" class="form-control" id='code' aria-describedby="basic-addon3" placeholder="1111...">
                    </div>
                    <label for="basic-url">Currency</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Currency</span>
                        </div>
                        <select required name='currencies' id='currencies' class='form-control'>
                                @if(count($currency) > 0)
                                    @foreach($currency as $currenciesData)
                                      <option value='{{$currenciesData->id}}'>{{$currenciesData->name}} ({{$currenciesData->code}})</option>

                                    @endforeach

                                @endif
                        </select>
                    </div>
                    <label for="basic-url">Branch Address</label>
                    <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Address</span>
                    </div>
                    <textarea class="form-control" id='branch_address' aria-label="With textarea"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="button"  class="btn btn-primary" onclick='btnadd()'>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editexampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Branch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id='add-branch'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">
            <input type = "hidden"  id="edit_id" >

                <div class="modal-body">

                    <label for="basic-url">Branch Name</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Branch</span>
                        </div>
                        <input type="text" class="form-control" id='edit_branch_name'  aria-describedby="basic-addon3" placeholder="Abuja...">
                    </div>
                    <label for="basic-url">Branch Prefix</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Code</span>
                        </div>
                        <input type="number" class="form-control" id='code_edit' aria-describedby="basic-addon3" placeholder="1111...">
                    </div>
                    <label for="basic-url">Branch Manager</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Manager</span>
                        </div>
                        <select required name='manager' id='manager' class='form-control'>

                        </select>
                                 </div>
                    <label for="basic-url">Status</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Status</span>
                        </div>
                        <select required name='status' id='status' class='form-control'>
                                @if(count($status) > 0)
                                    @foreach($status as $status_data)
                                      <option value='{{$status_data->id}}'>{{$status_data->name}}</option>

                                    @endforeach

                                @endif
                        </select>
                                 </div>
                                 <label for="basic-url">Currency</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Currency</span>
                        </div>
                        <select required name='edit_currencies' id='edit_currencies' class='form-control'>
                                @if(count($currency) > 0)
                                    @foreach($currency as $currenciesData)
                                      <option value='{{$currenciesData->id}}'>{{$currenciesData->name}} ({{$currenciesData->code}})</option>

                                    @endforeach

                                @endif
                        </select>
                    </div>
                    <label for="basic-url">Branch Address</label>
                    <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Address</span>
                    </div>
                    <textarea class="form-control" id='edit_branch_address' aria-label="With textarea"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="button"  class="btn btn-primary" onclick='update_branch()'>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
