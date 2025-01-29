@extends('layouts.app')

@section('content')


<div class="layout-px-spacing">
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <h3>List of Accounts</h3>
                    <div class='container'>
                        <div class='row layout-spacing ' style='margin-top:2rem;'>
                            @if ($branch)
                                @foreach ($branch as $branchData)
                                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                                        <div class="widget widget-card-five" onclick="selectBranch({{$branchData->currency_id}})">
                                            <div class="widget-content">
                                                <div class="account-box">

                                                    <div class="info-box">
                                                        <div class="icon">
                                                            <span>
                                                                <img  src="{{asset('assets/dashboard/wallet.png')}}" alt="money-bag"  width="50">
                                                            </span>
                                                        </div>

                                                        <div class="balance-info">
                                                            <h6>{{$branchData->branch_name}}</h6>
                                                            <p class="acc-amount">{{$branchData->currency->name}}</p>
                                                        </div>
                                                    </div>

                                                    <div class="card-bottom-section">
                                                        <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                                                        <a href="javascript:void(0);" class="">Tab to view</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class="table-responsive" style="overflow-x: scroll">
                            <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                <thead>
                                    <tr class="">
                                        <th class="text-center">#</th>
                                        <th>Name</th>
                                        <th>Account Number</th>
                                        <th>Currency</th>
                                        <th>Created by</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>


                    </div>
            </div>
        </div>
    </div>
</div>
<script>
    function selectBranch(id){
        $('#html5-extension').DataTable().ajax.url('/company/view-accounts-data?currency_id=' + id).load();
    }
</script>
@endsection
