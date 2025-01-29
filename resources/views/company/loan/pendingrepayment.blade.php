@extends('layouts.app')

@section('content')

<div class='container-fluid'>
<div class="row layout-top-spacing ">

    <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
        <form id='pending_approval_form'>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="">Branch</label>
                        <select name="branch" id='branch' onchange="renderStaff()" class="form-control" required>
                             <option value="null">Select Branch</option>

                            @if(count($branch))
                            @foreach($branch as $branchData)
                            <option value="{{Crypt::encryptString($branchData->id)}}">{{$branchData->branch_name}}</option>

                            @endforeach

                            @endif


                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="">Account Officer</label>
                        <select name="officer" id='officer' onchange="renderStaff_pending()" class="form-control" required>
                        </select>
                    </div>

                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="">Action</label>
                        <select name='action' class='form-control' required>
                            <option value=''>Choose action</option>
                            <option value='1'>Approve</option>
                            <option value='2'>Delete</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <br>
                        <button type='submit' class='btn btn-primary'>Submit</button>
                    </div>
                </div>
            </div>
        <div class="widget-content widget-content-area br-6">
            <div class="table-responsive mb-4 mt-4">
                <span id='infobait'></span>
                <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                    <thead>
                    <th>Sn</th>
                    <th>LoanID</th>
                    <th>Name</th>
                    <th>Account</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>created By</th>
                    <th>PaymentDate</th>
                    <th>Created At</th>
                    <th>Select</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
    </div>

</div>
</div>
@endsection
