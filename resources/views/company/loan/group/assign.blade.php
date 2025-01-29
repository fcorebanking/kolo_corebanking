@extends('layouts.app')

@section('content')

<div class="layout-px-spacing">
    <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <form id='assign_customer_form' class='deleteform'>
                    <div class="widget-content widget-content-area br-6">
                        <span id='infobait'> <h4>Assign Customer Group</h4></span>

                        <fieldset>
                            <div class='row' >
                                <div class='col-md-3 '>
                                    <label for='action'>Action</label>
                                    <select name='action' id='action' class='form-control'>
                                        <option value='assign'>Assign</option>
                                        <option value='remove'>Remove</option>
                                    </select>
                                </div>
                                <div class='col-md-3'>
                                    <label for='group_id'>Group</label>
                                    <select name='group_id' id='group_id' class='form-control group-select'>
                                    </select>
                                </div>
                                <div class='col-md-3'>
                                    <br>
                                    <br>
                                    <button type='submit' class='btn btn-secondary' id='proceebtn'>Process</button>
                                </div>
                                <div class='col-md-3'>
                                    <br>
                                    <br>
                                    <span ><b>Check All</b> </span><input type="checkbox" name="checkedAll" id="checkedAll" title='Checkall' />
                                </div>


                            </div>
                        </fieldset>

                    </div>
                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                    <div class="widget-content widget-content-area br-6">
                        <div class="table-responsive mb-4 mt-4">
                            <span id='infobait'></span>
                            <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                <thead>
                                <th>Select</th>
                                <th>name</th>
                                <th>Group</th>
                                <th>Phone</th>
                                <th>Account Number</th>
                                <th>Balance</th>
                                <th>Action</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                                <!-- Modal -->
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

