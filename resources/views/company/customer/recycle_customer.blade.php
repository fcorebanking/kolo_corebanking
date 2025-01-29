@extends('layouts.app')

@section('content')

<div class="layout-px-spacing">
    <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <form id='delete_active_customer_form' class='deleteform'>
                    <div class="widget-content widget-content-area br-6">
                        <span id='infobait'> <h4>Delete Customers</h4></span>
                        <fieldset>
                            <legend> Search and Process</legend>
                            <div class='row' >
                                <div class='col-md-3'>
                                    <label for='page'>Page</label>
                                    <select name='page' id='page' class='form-control'>
                                        <option value='active-account'>Active Account</option>
                                        <option value='recycle-account'>Recycle Account</option>
                                    </select>
                                </div>
                                <div class='col-md-3 '>
                                    <label for='action'>Action</label>

                                    <select name='action' id='action' class='form-control'>
                                        
                                    </select>
                                </div>
                                
                                <div class='col-md-3'>
                                    <br>
                                    <br>
                                    <button type='submit' class='btn btn-secondary'>Process</button>
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
                                <th>FirstName</th>
                                <th>LastName</th>
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

