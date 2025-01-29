<div class="widget-content widget-content-area">
    <h3>Manage Ledger Account</h3>
    <div>
        <div class='container'>

            <div class="row">
                <div class="col-md-12">
                    @if ($this->responseStatus == 1)
                        <div class="alert alert-success">
                            {{ $this->response }}
                        </div>
                        @endif
                    @if ($this->responseStatus == 2)
                        <div class="alert alert-danger">
                            {{ $this->response }}
                        </div>
                    @endif
                </div>
            </div>
            <button wire:click='resync' class='btn btn-danger m-5 block-page'>Reset To Default settings </button>
            <button wire:click='resyncTransaction' class='btn btn-secondary m-5 block-page'>Sync Transaction With Settings </button>

            <div class='table-responsive'>
                <table class='table'>
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
@php
    $i=1;
@endphp
                        @forelse ($records as $record)
                            <tr>
                                <td>{{ $i }}</td>
                                <td>{{ $record->name }}</td>
                                <td>{{ $record->code }}</td>
                                <td>{{ $record->type }}</td>
                                <td>{{ $record->category }}</td>

                                <td>{{ $record->author->first_name.' '.$record->author->last_name }}</td>
                                <td>
                                    {{-- <button class='btn btn-warning' wire:click="edit({{ $record->code }})">Edit</button> --}}

                                    @if ($record->deleteable == 0)
                                        <button class='btn btn' disabled>Locked</button>
                                    @else
                                        <button class='btn btn-danger block-page' wire:click="delete({{ $record->code }})">Delete</button>
                                    @endif
                                    <a class='btn btn-warning block-page' href="{{'/company/accounting/edit-ledger-account/'.$record->code}}">Edit</a>

                                </td>
                            </tr>
                            @php
                                $i++;
                            @endphp
                            @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
            <a href='/company/accounting/add-ledger-account' class='btn btn-info text-right'>Add Ledger Account</a>

</div>
</div>

