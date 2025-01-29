<div class='form-group'>
    <label for="ledger_dr">Cash/Bank (Debit)</label>
    <select name='ledger_dr' id='ledger_dr' class='form-control' required>
        @if (ledgers())
        @foreach (ledgers() as $ledgerData)
        @if ($ledgerData->access ==1 ||  ($ledgerData->access ==2 && $ledgerData->branch_id !=null && $ledgerData->branch_id ==auth()->user()->branch_id))
            <option value='{{$ledgerData->id}}'>{{$ledgerData->name.'-'.$ledgerData->code}}</option>
        @endif
        @endforeach
        @endif
    </select>
</div>
