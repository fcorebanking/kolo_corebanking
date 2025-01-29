<div>

    <div class='row'>
        @if($showDate)
        <div class='col-sm-6 col-md-3 col-lg-3'>
            <label>From</label>
            <input type='date'  wire:model="from" class='form-control' placeholder="From...">
        </div>
        <div class='col-sm-6 col-md-3 col-lg-3'>
            <label>To</label>
            <input type='date'  wire:model="to" class='form-control' placeholder="To...">
        </div>
        @endif
        @if($showBranch)
        <div class='col-sm-6 col-md-3 col-lg-3'>
            @if(cando('select-cutomer-branch|root'))
                <div class="form-group">
                    <label for="branchID">Branch</label>
                    <select id='branchID' wire:model="branchID" class="form-control" >
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
        @endif
        @if($staff)

        <div class='col-sm-6 col-md-2'>

            <label for='officer'>Staff</label>

            <select wire:model="staff" class="form-control">
                @foreach (getStaff() as $mystaff)
                    <option value='{{$mystaff->id}}'>{{$mystaff->first_name.' '.$mystaff->last_name}}</option>
                @endforeach
            </select>

        </div>
        @endif

        @if($showTransactionType)
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
        @endif
        @if($showledgerAccount)
        <div class='col-sm-6 col-md-3 col-lg-3'>
        <div class='form-group'>
            <label for="ledger">Ledger Account</label>
            <select  wire:model="ledgerAccount" class='form-control' required>
                @if (ledgers())
                @foreach (ledgers() as $ledgerData)
                @if ($ledgerData->access ==1 ||  ($ledgerData->access ==2 && $ledgerData->branch_id !=null && $ledgerData->branch_id ==auth()->user()->branch_id))
                    <option value='{{$ledgerData->code}}'>{{$ledgerData->name.'-'.$ledgerData->code}}</option>
                @endif
                @endforeach
                @endif
            </select>
        </div>
    </div>
        @endif
    </div>
</div>

