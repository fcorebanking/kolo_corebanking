<div class="widget-content widget-content-area">
    <h3>Edit Ledger Account</h3>
    <div class='container'>
        <a href='/company/accounting/view-ledger-account' class='btn btn-info m-5'>View Ledger Accounts</a>

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
        <div class="row">
            <div class="col-sm-6 col-md-6"><div class='form-group'>
                <input type='code' wire:model="code" name='code' class='form-control' placeholder="Code" >
                @error('code') <span class="text-danger error">{{ $message }}</span>@enderror
            </div></div>
            <div class="col-sm-6 col-md-6"><div class='form-group'>
                <input type='name' wire:model="name" name='name' class='form-control' placeholder="Name" >
                @error('name') <span class="text-danger error">{{ $message }}</span>@enderror
            </div></div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <select id='access' wire:model="access"  class="form-control">
                        <option value="">Select Branch Access</option>
                        <option value="1" @if($access==1) selected @endif>All Branch</option>
                        {{-- <option value="2" @if($access==2) selected @endif>Selected Branch</option> --}}
                    </select>
                    @error('access') <span class="text-danger error">{{ $message }}</span>@enderror
                </div>
            </div>
                <div class="col-sm-6 col-md-6">

                    <div class="form-group">
                        <select id='branchID' wire:model="branchID" class="form-control">
                            <option value="">Select Branch(Optional)</option>
                            @if(count(getbranchlist()))
                                @foreach(getbranchlist() as $branchData)
                                    <option value="{{$branchData->id}}" @if($branchID==$branchData->id) selected @endif>{{$branchData->branch_name}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                </div>
                <div class="col-sm-6 col-md-6">
                    <div class="form-group">
                        <select id='category' wire:model="category" class="form-control">
                            <option value="">Select Ledger Account Category</option>
                            <option value="Drawings"  @if($category=='Drawings') selected @endif>Drawings (Debit-Increase)(Credit-Descrease)</option>
                            <option value="Expenses" @if($category=='Expenses') selected @endif>Expenses (Debit-Increase)(Credit-Descrease)</option>
                            <option value="Asset" @if($category=='Asset') selected @endif>Asset (Debit-Increase)(Credit-Descrease)</option>
                            <option value="Liability" @if($category=='Liability') selected @endif>Liability (Credit-Increase)(Debit-Descrease)</option>
                            <option value="Equity" @if($category=='Equity') selected @endif>Equity (Credit-Increase)(Debit-Descrease)</option>
                            <option value="Revenue" @if($category=='Revenue') selected @endif>Revenue (Credit-Increase)(Debit-Descrease)</option>
                        </select>
                        @error('category') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                </div>
        </div>
        <button type="submit" wire:click='store' class="btn btn-primary mt-3 block-page">Submit</button>

</div>
</div>
