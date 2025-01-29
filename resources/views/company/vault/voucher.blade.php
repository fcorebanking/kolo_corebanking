@extends('layouts.app')

@section('content')

<div class="layout-px-spacing">
    <div>
        <h3>Submit Vault balance: {{ auth()->user()->staff_wallet->balance}}</h3>
        <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
            <div class="col-md-8">
                <div >
                    <div class="form-group ">
                        <label for="wallet">Amount</label>
                        <input type='number' id='amount' class='form-control form-small' style='margin:5px'>
                    </div>
                    <button class='btn btn-primary'  onclick='send_voucher()'>Send</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            @if(cando('root'))
            <div class="widget-content widget-content-area br-6 walletview vault-card">

            </div>
            @endif
            <div class="widget-content widget-content-area br-6 logview">
                <div class="table-responsive mb-4 mt-4">
                    <span id='infobait'>
                           <h3>Transaction History</h3></span>
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>
                        <th>SN</th>
                        <th>Name</th>
                        <th>Acnumber</th>
                        <th>Balance</th>
                        <th>Branch</th>
                        <th>Vault</th>
                        <th>Ref</th>
                        <th>Amount</th>
                        <th>Status</th>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>

                </div>
            </div>
        </div>

    </div>
    @include('company.vault.inc')

</div>


@endsection
