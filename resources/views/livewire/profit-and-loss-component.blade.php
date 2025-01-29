<div class="widget-content widget-content-area">
    <h3>Profit/Loss</h3>
    <form>
        @livewire('topsearchfield',['showDate' => true, 'showBranch' => true,'showTransactionType'=>false,'staff'=>false])
        <div class='col-sm-6 col-md-2'>

            <label for='officer'>Staff</label>

            <select wire:model="officer" class="form-control">
                @foreach (getStaff() as $mystaff)
                    <option value='{{$mystaff->id}}'>{{$mystaff->first_name.' '.$mystaff->last_name}}</option>
                @endforeach
            </select>

        </div>
        <button type='reset' wire:click='calculate' class='btn btn-info m-5 block-page'>Search</button>
        <button type='reset' wire:click='resetForm' class='btn btn-danger m-5 block-page'>Reset</button>
    </form>
    <div class='row'>
        <div class='col-sm-6 col-md-12 col-lg-8'>
            <h3>Profit/Loss </h3>
            <h4>{{ $from.' - '.$to}}</h4>
            @if ($loading)
            <div id="loading-spinner" class="spinner-border text-primary" role="status">
                Loading Content...<span class="sr-only">Loading...</span>
            </div>
            @endif
        @if($viewLoanRepayment)
            <button type='button' wire:click='goBack' class='btn btn-warning m-5 block-page'>Back</button>

            <h5>Loan Cash Payment</h5>
            <table class='table'>
                <thead>
                    <tr>
                        <td>Full Name</td>
                        <td>Officer</td>
                        <td>Loan Ref</td>
                        <td>Transaction amount</td>
                        <td>Interest Paid</td>
                        <td>Principal Paid</td>
                        <td>Method</td>
                        <td>Created By</td>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalI =0;
                        $totalP =0;
                        $totalLoanPaidamount =0;
                    @endphp
                    @if ($viewLoanRepayment)
                        @foreach ($viewLoanRepayment as $viewSelectedTransactionTypeDetail)
                        @php
                            $totalLoanPaidamount += $viewSelectedTransactionTypeDetail->amount;
                            $totalI += $viewSelectedTransactionTypeDetail->I;
                            $totalP += $viewSelectedTransactionTypeDetail->P;
                        @endphp
                            <tr>
                                <td>{{$viewSelectedTransactionTypeDetail->loan->user->first_name}} {{$viewSelectedTransactionTypeDetail->loan->user->last_name}}</td>
                                <td>{{$viewSelectedTransactionTypeDetail->loan->user->myOfficer->first_name}} {{$viewSelectedTransactionTypeDetail->loan->user->myOfficer->last_name}}</td>
                                <td>{{$viewSelectedTransactionTypeDetail->loan->ref}}</td>
                                <td>{{$viewSelectedTransactionTypeDetail->amount}}</td>
                                <td>{{$viewSelectedTransactionTypeDetail->I}}</td>
                                <td>{{$viewSelectedTransactionTypeDetail->P}}</td>
                                <td>{{$viewSelectedTransactionTypeDetail->method==1?'Cash':'Wallet'}}</td>
                                <td>{{$viewSelectedTransactionTypeDetail->author->first_name}} {{$viewSelectedTransactionTypeDetail->author->last_name}}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td></td>
                        <td></td>
                        <td>{{$totalLoanPaidamount}}</td>
                        <td>{{$totalI}}</td>
                        <td>{{$totalP}}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            @endif
            @if ($viewSelectedTransactionTypeDetails)
        <button type='button' wire:click='goBack' class='btn btn-warning m-5 block-page'>Back</button>

            <div class='card'>
                <div class="table-responsive">
                    @php
                        $totalAmt =0;
                        $wcharges =0;
                    @endphp
                    <table class='table'>
                        <thead>
                            <tr>
                                <td>Name</td>
                                <td>Account Officer</td>
                                <td>Transaction amount</td>
                                <td>Withdrawal Charges</td>
                                <td>Credit Debit Type</td>
                                <td>Transaction Type</td>
                                <td>CreatedBy</td>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($viewSelectedTransactionTypeDetails)
                                @foreach ($viewSelectedTransactionTypeDetails as $viewSelectedTransactionTypeDetail)
                                @php
                                    $totalAmt += $viewSelectedTransactionTypeDetail->amount;
                                    $wcharges +=$viewSelectedTransactionTypeDetail->withdrawal_charge;
                                @endphp
                                    <tr>
                                        <td>{{$viewSelectedTransactionTypeDetail->first_name.' '.$viewSelectedTransactionTypeDetail->last_name}}</td>
                                        <td>{{$viewSelectedTransactionTypeDetail->officer_first_name.' '.$viewSelectedTransactionTypeDetail->officer_last_name}}</td>
                                        <td>{{$viewSelectedTransactionTypeDetail->amount}}</td>
                                        <td>{{$viewSelectedTransactionTypeDetail->withdrawal_charge}}</td>
                                        <td>{{$viewSelectedTransactionTypeDetail->creditDebitType}}</td>
                                        <td>{{$viewSelectedTransactionTypeDetail->transaction_type}}</td>
                                        <td>{{$viewSelectedTransactionTypeDetail->session_first_name.' '.$viewSelectedTransactionTypeDetail->session_last_name}}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td>-</td>
                                <td>{{$totalAmt}}</td>
                                <td>{{$wcharges}}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif
            @if(!$viewLoanRepayment && !$viewSelectedTransactionTypeDetails)
            <div class="table-responsive">
                 <table class="table mb-4 contextual-table">
                    <thead>
                        <tr class="">
                            <th class="text-center"></th>
                            <th><b>{{$currentDate}}</b></th>
                            <tr class="">
                                <th class="text-center text-success">Revenu</th>
                                <th></th>
                            </tr>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($revenue))
                            @foreach ($revenue as $revenueData)
                            @if(isset($revenueData->total))
                                @php
                                    $totaldeposit +=$revenueData->total;
                                @endphp
                                <tr class="table-default">
                                    <td >{{$revenueData->transaction_type}}</td>
                                    <td ><button class='btn btn-info' wire:click='loadTransactionTypeRecord({{$revenueData->transaction_type_id}})' >{{number_format($revenueData->total) }}</button></td>
                                </tr>
                            @endif
                            @endforeach
                        @endif

                        <tr class="table-default">
                            <td >Cash Loan Repayment</td>
                            @php
                                    $totaldeposit +=$totalCashLoanRepaymentInterest;
                                @endphp
                            <td><button class='btn btn-warning' wire:click='loadTransactionTypeRecordLoanRepayment()'> {{number_format($totalCashLoanRepaymentInterest) }}</button></td>
                        </tr>
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

                            <tr class="">
                                <th class="text-center text-danger">Expenses</th>
                                <th></th>
                            </tr>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($loss))
                            @foreach ($loss as $lossData)
                            @if (isset($lossData->total))
                                @php
                                    $totalwithdraw +=$lossData->total;
                                @endphp
                                <tr class="table-default">
                                    <td >{{$lossData->transaction_type}}</td>
                                    <td>{{number_format($lossData->total) }}</td>
                                </tr>
                            @endif
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
                                <th @if(($totaldeposit - $totalwithdraw) > 0) class="text-center text-primary" @else class="text-center text-danger" @endif ><small>Balance at:</small> <br><b >{{number_format($totaldeposit - $totalwithdraw)}}</b></th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endif

        </div>
    </div>
</div>
