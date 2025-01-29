@extends('layouts.app')

@section('content')

<button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" onclick='reset()'>
    Add Expenses Type &nbsp<i class='fa fa-plus'></i>
</button>
<div class="widget-content widget-content-area br-6">
    <div class="table-responsive mb-4 mt-4">
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">

            <thead>
                <tr>
                    <th>S/N</th>
                        <th>Name</th>
                        <th>Created By</th>
                        <th>Modified By</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Action</th>
                    </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Expenses Product</h5>
                <button type="button" class="close"  aria-label="Close" >

                </button>
            </div>
            <form id='product-form'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden"  id="type_id"  name="type_id" value="">
                <div class="modal-body">
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" class="form-control" id='name' name='name' aria-describedby="basic-addon3"
                            placeholder="product 1..">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" class="btn btn-primary" >Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection
