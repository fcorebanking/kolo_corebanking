@extends('layouts.app')

@section('content')

<button type="button" onclick='addstaff()' style="margin:10px;" class="btn btn-primary mb-2 mr-2">
    Add Staff &nbsp<i class='fa fa-plus'></i>
</button>
<div class="row layout-top-spacing">

    <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
        <div class="widget-content widget-content-area br-6">
            <div class="table-responsive mb-4 mt-4">
                <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                    <thead>
                        <tr>
                        <th>S/N</th>
                            <th>Name</th>
                            <th>AccountNO</th>
                            <th>Balance</th>
                            <th>Comission</th>
                            <th>Settlement Balance</th>
                            <th>Settlement Account</th>

                            <th>Branch</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Created By</th>
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
<!-- Modal -->
<div class="modal" id="exampleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Company Staff</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>


            <form id='add-staff'>
                <div class="modal-body">
                    <input type="hidden" id="staff_id" name='id'>

                    <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                    <hr>
                    <div class='row'>
                        <div class='col-md-12'>
                            <div class='reset_psw_pin'> </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <label for="c-first-name">First Name</label>
                            <div class="input-group mb-4">

                                <input type="text" class="form-control" id='c-first-name' name='firstname' aria-describedby="basic-addon3" placeholder="Daniel...">
                            </div>
                            <div class='validation-text'></div>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <label for="c-last-name">Last Name</label>
                            <div class="input-group mb-4">

                                <input type="text" class="form-control" id='c-last-name' name='lastname' aria-describedby="basic-addon3" placeholder="John...">
                            </div>
                            <div class='validation-text'></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <label for="c-username">Username</label>
                            <div class="input-group mb-4">

                                <input type="text" class="form-control" id='c-username' name='username' aria-describedby="basic-addon3" placeholder="John123...">
                            </div>
                            <div class='validation-text'></div>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <label for="c-email">Email</label>
                            <div class="input-group mb-4">

                                <input type="text" class="form-control" id='c-email' name='email' aria-describedby="basic-addon3" placeholder="John123@domain.com...">
                            </div>
                            <div class='validation-text'></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 col-md-6">

                            <label for="c-phone">Phone</label>
                            <div class="input-group mb-4">

                                <input type="text" class="form-control" id='c-phone' name='phone' aria-describedby="basic-addon3" placeholder="2340 80...">
                            </div>
                            <div class='validation-text'></div>
                        </div>

                        <div class="col-sm-6 col-md-6">

                            <label for="c-branch">Branch</label>
                            <div class="input-group mb-4">


                                <select id="c-branch" name='branch' class="form-control" required>
                                    <option selected="" value="">Choose branch</option>
                                    @foreach($branch as $branchs)
                                    <option value='{{Crypt::encryptString($branchs->id)}}'>{{$branchs->branch_name}}</option>

                                    @endforeach
                                </select>
                            </div>
                            <div class='validation-text'></div>

                        </div>
                    </div>

                    <div class='row'>
                        @if(count($roles) > 0)


                        <div class='col-sm-6 col-md-6'>
                            <label for="basic-url">Set Role</label>
                            <select name='permission' id='permission' class='form-control'>
                                @foreach($roles as $data)
                                <option value='{{$data->id}}'> {{$data->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-sm-6 col-md-6">

                            <label for="c-status">Status</label>
                            <div class="input-group mb-4">
                                <select id="c-status" name='status' class="form-control" required>

                                    @foreach($status as $statusdata)
                                        <option value='{{$statusdata->id}}'>{{$statusdata->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class='validation-text'></div>

                        </div>
                    </div>
                    <div class="row" id='hidepsw'>
                        <div class="col-sm-6 col-md-6">
                            <label for="c-password">New Password</label>
                            <div class="input-group mb-4">

                                <input type="password" class="form-control" id='c-password' name='password' aria-describedby="basic-addon3" placeholder="***...">
                            </div>
                            <div class='validation-text'></div>
                        </div>
                        <div class="col-sm-6 col-md-6">

                            <label for="c-password">Comfirm Password</label>
                            <div class="input-group mb-4">

                                <input type="password" class="form-control" id='c-password2' name='c-password2' aria-describedby="basic-addon3" placeholder="***...">
                            </div>
                            <div class='validation-text'></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="modal" id="VaultModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id='staff_deposit'>
                <h3 class='title'></h3>

                <div class="row justify-content-center m-5" style="">

                    <div class="col-md-8">
                        <div >
                            <div class="form-group ">
                                <input type='hidden' id='staff'>
                            </div>
                            <div class="form-group ">
                                <label for="staff_credit_option">Credit Option</label>
                                <select id='staff_credit_option' class='form-control' >
                                    <option value='credit'>Credit</option>
                                    <option value='debit'>Debit</option>
                                </select>
                            </div>
                            <div class="form-group ">
                                <label for="amount">Amount</label>
                                <input type='text' id='amount_staff' class='form-control form-small' style='margin:5px'>
                            </div>

                            <button class='btn btn-primary fundingBTNb' >Process</button>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function popvault(id){
        $('.title').html('Fund Company Staff Wallet')
        $('.modal-title').html('Staff Vault')
        $('#VaultModal').modal('show');
        $('#staff').val(id);
        $('.fundingBTNb').attr('onclick','send_staff_deposit()')
    }
    function popcomission(id){
        $('.title').html('Fund Company Staff Comission')
        $('.modal-title').html('Staff Comission')

        $('#VaultModal').modal('show');
        $('#staff').val(id);
        $('.fundingBTNb').attr('onclick','send_staff_commission()')

    }

</script>
@endsection
