<div class="widget-content widget-content-area">
    <h3>View Account Ledger - {{@$ledgerName}}</h3>
    <form>
        @livewire('topsearchfield',['showDate' => true, 'showBranch' => true,'showTransactionType'=>false,'showledgerAccount'=>true])

        <button type='reset' wire:click='resetForm' class='btn btn-danger m-5 block-page'>Reset</button>
    </form>
    <div class='row'>
        <div class='col-sm-6 col-md-12 col-lg-12'>
            <h3>View Account Ledger</h3>
            <h4>{{ $from.' - '.$to}}</h4>
            <div class="table-responsive">
                <table class="table mb-4 contextual-table">
                    <thead>
                        <tr class="">
                            <th class="text-center">View</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Branch</th>
                            <th>Description</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($records))
                            @foreach ($records as $recordsData)
                            @php
                            $totalcredit +=($recordsData->credit_debit_type_id == 1)?$recordsData->amount:0;
                            $totalDebit +=($recordsData->credit_debit_type_id == 2)?$recordsData->amount:0;
                            $columnTotal = ($recordsData->credit_debit_type_id == 2)?$columnTotal+$recordsData->amount:$columnTotal-$recordsData->amount
                            @endphp
                            <tr class="">
                                <td >TXN</td>
                                <td>{{$recordsData->created_date}}</td>
                                <td>{{$recordsData->transaction_type}}</td>
                                <td>{{$recordsData->branch_name}}</td>
                                <td>{{$recordsData->description}}</td>
                                <td class='bg-info text-light'>{{($recordsData->credit_debit_type_id == 1)?$recordsData->amount:'0'}}</td>
                                <td class='bg-warning text-light'>{{($recordsData->credit_debit_type_id == 2)?$recordsData->amount:'0'}}</td>
                                <td>{{abs($columnTotal)}}</td>
                            </tr>
                            @endforeach
                        @endif
                        @if (isset($expenses))
                        @foreach ($expenses as $recordsData)
                        @php
                            $columnTotal += $recordsData->amount;
                        @endphp
                            <tr class="">
                                <td >TXN</td>
                                <td>{{$recordsData->created_at}}</td>
                                <td>{{$recordsData->expenseType->name}}</td>
                                <td>{{$recordsData->author->first_name.' '.$recordsData->author->last_name}}</td>
                                <td>{{$recordsData->description}}</td>
                                <td class='bg-warning text-light'>{{$recordsData->amount}}</td>
                                <td class='bg-info text-light'></td>
                                <td>{{abs($columnTotal)}}</td>
                            </tr>
                        @endforeach
                        @endif
                        @if (isset($otherincome))
                        @foreach ($otherincome as $recordsData)
                        @php
                            $columnTotal -= $recordsData->amount;
                        @endphp
                            <tr class="">
                                <td >TXN</td>
                                <td>{{$recordsData->created_at}}</td>
                                <td>{{$recordsData->otherIncomeType->name}}</td>
                                <td>{{$recordsData->author->first_name.' '.$recordsData->author->last_name}}</td>
                                <td>{{$recordsData->description}}</td>
                                <td class='bg-warning text-light'>{{$recordsData->amount}}</td>
                                <td class='bg-info text-light'></td>
                                <td>{{abs($columnTotal)}}</td>
                            </tr>
                        @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="">
                            <th class="text-center"></th>
                            <th>-</th>
                            <th>-</th>
                            <th></th>
                            <th>Closing Balance</th>
                            <th>{{$totalcredit}}</th>
                            <th>{{$totalDebit}}</th>
                            <th>{{abs( $totalDebit-$totalcredit)}}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
