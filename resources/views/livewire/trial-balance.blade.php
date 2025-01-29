<div class="widget-content widget-content-area">
    <h3>Trial Balance</h3>
    <form>
        @livewire('topsearchfield',['showDate' => true, 'showBranch' => true,'showTransactionType'=>false])
        <button type='reset' wire:click='resetForm' class='btn btn-danger m-5'>Reset</button>
    </form>
    <div class='row'>
        <div class='col-sm-6 col-md-12 col-lg-8'>
            <h3>Trial Balance</h3>
            <h4>{{ $from.' - '.$to}}</h4>
            <div class="table-responsive">
                <table class="table mb-4 contextual-table">
                    <thead>
                        <tr class="">
                            <th class="text-center">Code</th>
                            <th>Account</th>
                            <th>Debit</th>
                            <th>Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($records))

                            @foreach ($records as $recordsData)
                                @php
                                    $credit = isset($recordsData['data']['credit'][0]->total)?($recordsData['data']['credit'][0]->total + $recordsData['otherincome']):0;
                                    $debit = isset($recordsData['data']['debit'][0]->total)?($recordsData['data']['debit'][0]->total+ $recordsData['otherexpenses']):0;
                                    if($recordsData['type']=="debit") {$totalDebit +=  $debit;}
                                    if($recordsData['type']=="credit") {$totalcredit +=$credit;}

                                @endphp
                                <tr class="table-default">
                                    <td class='bg-primary'>{{$recordsData['code']}}</td>
                                    <td >{{$recordsData['name']}}</td>
                                    <td class='bg-secondary text-light'><a class="text-light" href="{{url('company/accounting/ledger-details/'.$recordsData['code'])}}">{{($recordsData['type']=="debit")?number_format(abs($debit  -$credit)):'' }}</a></td>
                                    <td class='bg-dark '><a class="text-light" href="{{url('company/accounting/ledger-details/'.$recordsData['code'])}}">{{($recordsData['type']=="credit")?number_format(abs($debit -$credit)):'' }}</a></td>
                                </tr>
                            @endforeach


                        @endif
                        @if ($OtherIncomeLedger)
                        @foreach ($OtherIncomeLedger as $OtherIncomeLedgerItem)
                        @php
                            $totalcredit +=$OtherIncomeLedgerItem->total;
                        @endphp
                        <tr class="table-default">
                            <td class='bg-primary'>FFF</td>
                            <td >{{$OtherIncomeLedgerItem->name}}</td>
                            <td  class='bg-secondary text-light'></td>
                            <td class='bg-dark text-light'>{{ number_format($OtherIncomeLedgerItem->total)}}</td>
                        </tr>
                        @endforeach
                    @endif
                    @if ($ExpensesReportLedger)
                                @foreach ($ExpensesReportLedger as $ExpensesReportLedgerItem)
                                @php
                                    $totalDebit +=$ExpensesReportLedgerItem->total;
                                @endphp
                                <tr class="table-default">
                                    <td class='bg-primary'>EEE</td>
                                    <td >{{$ExpensesReportLedgerItem->name}}</td>
                                    <td class='bg-secondary text-light'>{{ number_format($ExpensesReportLedgerItem->total)}}</td>
                                    <td class='bg-dark text-light'></td>
                                </tr>
                                @endforeach
                            @endif
                            <tr class="table-default">
                                <td class='bg-primary'>Total</td>
                                <td >--</td>
                                <td class='bg-success text-light'>{{number_format($totalDebit)}}</td>
                                <td class='bg-success text-light'>{{number_format($totalcredit)}}</td>
                            </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>





