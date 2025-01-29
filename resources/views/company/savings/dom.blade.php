@extends('layouts.app')

@section('content')

<style>
    .center-m{
        margin: 0 auto;
        width:40%;
    }
</style>
<div class="layout-px-spacing">

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
<form id='dom-all' method='post'>
            <div class="widget-content widget-content-area br-6 text-center">

                <span class='center-m'>
                    <center>
                        <label>Check All <input type="checkbox" name="checkedAll" id="checkedAll" title="Checkall"> <button class='btn btn-success'>Unblock All</button></label>
                    </center>
                    </span>
                <div class="table-responsive mb-4 mt-4">
                    <span id='infobait'></span>
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>
                        <th>Select</th>
                        <th>FirstName</th>
                        <th>LastName</th>
                        <th>AccountNo</th>
                        <th>Phone</th>
                        <th>Account Type</th>
                        <th>Balance</th>
                        <th>Action</th>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </form>
        </div>
    </div>
</div>



@endsection

