<div class="widget-content widget-content-area">
    <h3>General Ledger Summary</h3>
    <form>
        @livewire('topsearchfield',['showDate' => true, 'showBranch' => true,'showTransactionType'=>false])
        <button type='reset' wire:click='resetForm' class='btn btn-danger m-5 block-page'>Reset</button>
    </form>
    <div class='row'>
        <div class='col-sm-6 col-md-12 col-lg-8'>
            <h3>General Ledger Summary</h3>
            <h4>{{ $from.' - '.$to}}</h4>
            <div class="table-responsive">
                <table class="table mb-4 contextual-table">
                    <thead>
                        <tr class="">
                            <th class="text-center">Code</th>
                            <th>Account</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($records))

                            @foreach ($records as $recordsData)
                                @php
                                    $credit = isset($recordsData['data']['credit'][0]->total)?($recordsData['data']['credit'][0]->total + $recordsData['otherincome']):0;
                                    $debit = isset($recordsData['data']['debit'][0]->total)?($recordsData['data']['debit'][0]->total+ $recordsData['otherexpenses']):0;
                                    $totalDebit +=$debit;
                                    $totalcredit +=$credit;
                                @endphp
                                @if (!$credit ==0 && !$debit  ==0)

                                <tr class="table-default">
                                    <td class='bg-primary'>{{$recordsData['code']}}</td>
                                    <td >{{$recordsData['name']}}</td>
                                    <td class='bg-danger text-light'>{{ number_format($debit)}}</td>
                                    <td class='bg-info text-light'>{{ number_format($credit)}}</td>
                                    <td class='bg-success text-light'><a class="text-light" href="{{url('company/accounting/ledger-details/'.$recordsData['code'])}}">{{number_format(abs($debit - $credit)) }}</a></td>
                                </tr>
                                @endif

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
                                    <td class='bg-danger text-light'></td>
                                    <td class='bg-info text-light'>{{ abs(number_format($OtherIncomeLedgerItem->total))}}</td>
                                    <td class='bg-success text-light'><a class="text-light" href="{{url('company/accounting/ledger-details/'.$OtherIncomeLedgerItem->id)}}">{{abs(number_format(abs($OtherIncomeLedgerItem->total))) }}</a></td>
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
                                    <td class='bg-danger text-light'>{{ number_format($ExpensesReportLedgerItem->total)}}</td>
                                    <td class='bg-info text-light'></td>
                                    <td class='bg-success text-light'><a class="text-light" href="{{url('company/accounting/ledger-details/'.$ExpensesReportLedgerItem->id)}}">{{number_format(abs($ExpensesReportLedgerItem->total)) }}</a></td>
                                </tr>
                                @endforeach
                            @endif

                        <tr class="table-dark">
                            <td class=''></td>
                            <td >Total</td>
                            <td class='bg-danger text-light'>{{ number_format($totalDebit)}}</td>
                            <td class='bg-info text-light'>{{ number_format($totalcredit)}}</td>
                            <td class='bg-success text-light'>-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
