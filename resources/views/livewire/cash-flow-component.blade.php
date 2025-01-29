<div class="widget-content widget-content-area">
    <h3>Cash Flow</h3>
    <form>
    <div class='row'>
        <div class='col-sm-6 col-md-3 col-lg-3'>
            <label>From</label>
            <input type='date'  wire:model="from" class='form-control' placeholder="From...">
        </div>
        <div class='col-sm-6 col-md-3 col-lg-3'>
            <label>To</label>
            <input type='date'  wire:model="to" class='form-control' placeholder="To...">
        </div>
        <div class='col-sm-6 col-md-3 col-lg-3'>
            @if(cando('select-cutomer-branch|root'))
                <div class="form-group">
                    <label for="branchID">Branch</label>
                    <select id='branchID' wire:model="branchID" class="form-control">
                        <option value="">Select Branch</option>
                        @if(count(getbranchlist()))
                            @foreach(getbranchlist() as $branchData)
                                <option value="{{$branchData->id}}">{{$branchData->branch_name}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            @endif
        </div>
        <div class='col-sm-6 col-md-3 col-lg-3'>
            <div class='form-group'>
                <label for="transactionType">Transaction Type</label>
                <select id='transactionType' wire:model="transactionType" class='form-control'>
                    <option value=''>Enter Transaction Type</option>
                    @if (transactionType())
                        @foreach (transactionType() as $transaction_typeData)
                            <option value='{{$transaction_typeData->id}}'>{{$transaction_typeData->transaction_type}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
    </div>
    <button type='reset' class='btn btn-danger m-5 block-page'>Reset</button>
    </form>
    <div class='row'>
        <div class='col-sm-6 col-md-12 col-lg-8'>
            <h3>Cash Flow Statement</h3>
            <div class="table-responsive">
                <table class="table mb-4 contextual-table">
                    <thead>
                        <tr class="">
                            <th class="text-center"></th>
                            <th><small>{{$currentDate}}</small></th>
                            <tr class="">
                                <th class="text-center text-success">#Receipts</th>
                                <th></th>
                            </tr>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($depositTXN)

                            @foreach ($depositTXN as $depositTXNData)
                                @php
                                    $totaldeposit +=$depositTXNData->total;
                                @endphp
                                <tr class="table-default">
                                    <td >{{$depositTXNData->transaction_type}}</td>
                                    <td>{{number_format($depositTXNData->total) }}</td>
                                </tr>
                            @endforeach

                        @endif
                        <tr class="table-default">
                            <td >OtherIncome</td>
                            <td>{{number_format($otherIncome) }}</td>
                        </tr>
                        <tr class="table-success">
                            <td class="text-center">Total Receipts(A)</td>
                            <td>{{number_format($totaldeposit)}}</td>
                        </tr>
                    </tbody>
                </table>
                <table class="table mb-4 contextual-table">
                    <thead>
                        <tr class="">
                            <th class="text-center"></th>
                            <th><small>{{$currentDate}}</small></th>
                            <tr class="">
                                <th class="text-center text-danger">#Payments</th>
                                <th></th>
                            </tr>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($withdrawTXN)
                            @foreach ($withdrawTXN as $withdrawTXNData)
                            @php
                                $totalwithdraw +=$withdrawTXNData->total;
                            @endphp
                                <tr class="table-default">
                                    <td >{{$withdrawTXNData->transaction_type}}</td>
                                    <td>{{number_format($withdrawTXNData->total) }}</td>
                                </tr>
                            @endforeach

                        @endif
                        <tr class="table-default">
                            <td >Expenses</td>
                            <td>{{number_format($expenses) }}</td>
                        </tr>
                        <tr class="table-danger">
                            <td class="text-center">Total Payments (B)</td>
                            <td>{{number_format($totalwithdraw)}}</td>
                        </tr>
                    </tbody>
                </table>
                <table class="table mb-4 contextual-table">
                    <thead>
                        <tr class="table-default">
                                <th class="text-center text-primary">#Balance (A-B)</th>
                                <th><small>Balance at {{$currentDate}}</small> <br><b >{{number_format($totaldeposit - $totalwithdraw)}}</b></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
