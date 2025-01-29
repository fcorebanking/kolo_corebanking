@extends('layouts.app')

@section('content')


<div class="layout-px-spacing">

    <div class="col-md-12 text-left mt-3">
        <h3>Transaction History</h3>

    </div>
    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <span id='infobait'></span>
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>

                            <th>SN</th>
                            <th>Trans. Reference</th>
                            <th>Balance Before</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                            <th>Description</th>
                            <th>Trans. Type</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>

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