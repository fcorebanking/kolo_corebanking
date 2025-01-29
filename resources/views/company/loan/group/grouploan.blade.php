@extends('layouts.app')

@section('content')

<button type="button" style="margin:10px;" onclick='groupToggle()' class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#exampleModal">
    Create Group &nbsp<i class='fa fa-plus'></i>
</button>

<div class='card m-5 group-form p-5'>
    <h3>Create Loan Group</h3>
    <form id='group-form'>
        <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
        <div class="modal-body">
            <input type="hidden" name='group_id' id='group_id' value=''>
            <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" name='name' id='name' aria-describedby="basic-addon3"
                    placeholder="Name..."  required>
            </div>
            <div class="form-group">
                <label>Manager</label>
                <span class='manager_id'></span>
                <select name="manager_id" id="manager_id" class="form-control staff"></select>
            </div>
            <div class="form-group">
                <label>Leader</label>
                <span class='leader'></span>
                <select name="leader" id="leader" class="form-control customerx"></select>
            </div>
            <div class="form-group">
                <label>Secretary</label>
                <span class='secretary'></span>
                <select name="secretary" id="secretary" class="form-control customerx"></select>
            </div>
            <div class="form-group">
                <label>Max Member</label>
                <input type="number" class="form-control" name='max_member' id='max_member' aria-describedby="basic-addon3"
                    placeholder="Max Member..."  required>
            </div>
            <div class="form-group">
                <label>Max Principal</label>
                <input type="number" class="form-control" name='max_principal' id='max_principal' aria-describedby="basic-addon3"
                    placeholder="Max Principal..."  required>
            </div>
            <div class="form-group">
                <label>Max Interest (%)</label>
                <input type="number" class="form-control" name='max_interest' id='max_interest' aria-describedby="basic-addon3"
                    placeholder="Max Interest..."  required>
            </div>
            </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
            <button type="submit" class="btn btn-primary" id="grouploanBTN">Save</button>
        </div>
    </form>

</div>

<div class="widget-content widget-content-area br-6">
    <div class="table-responsive mb-4 mt-4">
        <span id='infobait'></span>
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
            <th>sn</th>
            <th>Name</th>
            <th>Leader</th>
            <th>Secratery</th>
            <th>Max-Member</th>
            <th>Max-Principal</th>
            <th>Max-Interest</th>
            <th>Total User</th>
            <th>Manager</th>
            <th>action</th>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>



<div class="modal fade bd-example-modal-lg" id="GroupDetails" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">

        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">Group Details</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>
                <div class="modal-body groupContent">

                </div>
                <div class="modal-footer">
                    <button type="button"  class="btn btn-flat" data-dismiss="modal" ><i class="flaticon-cancel-12"></i> Discard</button>
                </div>
        </div>
    </div>
    </div>


@endsection
