@extends('layouts.app')

@section('content')
<div class="widget-content widget-content-area">
    <h3>Set Ledger Settings</h3>
    <div class='container'>
        <div class="row">
            <div class="col-md-12">
                {{-- @if ($this->responseStatus == 1)
                    <div class="alert alert-success">
                        {{ $this->response }}
                    </div>
                    @endif
                @if ($this->responseStatus == 2)
                    <div class="alert alert-danger">
                        {{ $this->response }}
                    </div>
                @endif --}}
            </div>
        </div>
        <div class='m-5'>

        <div class="row">
            <div class="col-sm-6 col-md-6">
                <div class='form-group'>
                <label for="page">Page</label>
                <select wire:model="page" class="form-control" wire:key="page" wire:ignore>
                    <option value="">Select Page</option>
                    @foreach (transactionType() as $transactionType)
                        <option value="{{ $transactionType->id }}">{{ $transactionType->transaction_type }}</option>
                    @endforeach
                </select>

                @error('page') <span class="text-danger error">{{ $message }}</span>@enderror
            </div>
        </div>
            <div class="col-sm-6 col-md-6">
                <div class='form-group'>
                    <label for="ledger_cr">Credit Account</label>
                    <select name='ledger_cr' wire:model="ledger_cr" id='ledger_cr' class='form-control' wire:key="ledger_cr">
                        @if (ledgers())
                        <option value="">Select Credit Ledger</option>

                        @foreach (ledgers() as $ledgerData)
                        @if ($ledgerData->access ==1 ||  ($ledgerData->access ==2 && $ledgerData->branch_id !=null && $ledgerData->branch_id ==auth()->user()->branch_id))
                            <option value='{{$ledgerData->id}}'>{{$ledgerData->name.'-'.$ledgerData->code.'-'.$ledgerData->category}}</option>
                        @endif
                        @endforeach
                        @endif
                    </select>
                @error('ledger_cr') <span class="text-danger error">{{ $message }}</span>@enderror

                </div>
            </div>
            <div class="col-sm-6 col-md-6">
                <div class='form-group'>
                    <label for="ledger_dr">Debit Account</label>
                    <select name='ledger_dr' wire:model="ledger_dr" id='ledger_dr' class='form-control' wire:key="ledger_dr">
                        @if (ledgers())
                        <option value="">Select Debit Ledger</option>

                        @foreach (ledgers() as $ledgerData)
                        @if ($ledgerData->access ==1 ||  ($ledgerData->access ==2 && $ledgerData->branch_id !=null && $ledgerData->branch_id ==auth()->user()->branch_id))
                            <option value='{{$ledgerData->id}}'>{{$ledgerData->name.'-'.$ledgerData->code.'-'.$ledgerData->category}}</option>
                        @endif
                        @endforeach
                        @endif
                    </select>
                @error('ledger_dr') <span class="text-danger error">{{ $message }}</span>@enderror

                </div>
            </div>
        </div>
        <button type="submit" wire:click='store' class="btn btn-primary mt-3 block-page">Submit</button>
    </div>
        <div class='table-responsive'>
            <table class='table'>
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Ledger CR</th>
                        <th>Ledger DR</th>
                        <th>Created By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                    @forelse ($records as $record)
                        <tr>
                            <td>{{ @$record->page->transaction_type }}</td>
                            <td>{{ @$record->ledgerCR->name }}</td>
                            <td>{{ @$record->ledgerDR->name }}</td>
                            <td>{{ @$record->author->first_name.' '.$record->author->last_name }}</td>
                            <td>
                                    <button class='btn btn-danger block-page' wire:click="delete({{ $record->id }})">Delete</button>
                            </td>
                        </tr>
                        @empty
                    @endforelse
                </tbody>
            </table>
        </div>
</div>
</div>
@endsection
