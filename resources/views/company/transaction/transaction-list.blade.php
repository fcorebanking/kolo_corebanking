@extends('layouts.app')

@section('content')


<div class="layout-px-spacing">
    <div class="col-md-12 text-left mt-3">
        <h3>Transaction History</h3>
    </div>

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6 row">
                <div class='form-group col-md-2 col-sm-6'>
                    <label for="">View</label>
                    <select class='form-control' id='view-select'>
                        <option value='paginate'>Paginated</option>
                        <option value='all'>Show All</option>
                    </select>
                </div>
                <div class='form-group col-md-2 col-sm-6'>
                    <label for="">From</label>
                    <input type='date' id='from' class='form-control'>
                </div>
                <div class='form-group  col-md-2 col-sm-6'>
                    <label for="">To</label>
                    <input type='date' id='to' class='form-control'>
                </div>
                <div class='form-group  col-md-2 col-sm-6'>
                    <label for="">Transaction Type</label>
                    <select id='transaction_type'  class='form-control'>
                        <option value=''>Enter Transaction Type</option>
                        @if ($transaction_type)
                            @foreach ($transaction_type as $transaction_typeData)
                                <option value='{{$transaction_typeData->id}}'>{{$transaction_typeData->transaction_type}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class='form-group  col-md-2 col-sm-6'>
                    <label for="">Status</label>
                    <select id='status'  class='form-control'>
                        <option value=''>Enter Status</option>
                        <option value='customer'>Customer</option>
                        <option value='company'>Company</option>
                    </select>
                </div>
                <div class='form-group  col-md-2 col-sm-6'>

                    <button type='button' class='btn btn-primary' onclick='searchTXN()'>Filter</button>
                </div>
                <div class="table-responsive mb-4 mt-4 col-md-12 col-sm-6">
                    <span id='infobait'></span>
                    <table id="html5-extension" class="table table-hover non-hover" align="left">
                        <thead>

                            <th>SN</th>
                            <th>AccountNo</th>
                            <th>Trans. Reference</th>
                            <th>Bal. Before</th>
                            <th>Amount</th>
                            <th>Bal. After</th>
                            <th>Company Bal. Before</th>
                            <th>Amount</th>
                            <th>Company Bal. After</th>
                            <th>Description</th>
                            <th>Trans. Type</th>
                            <th>Credit or Debit Type</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Action</th>

                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
