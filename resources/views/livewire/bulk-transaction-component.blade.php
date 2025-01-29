<div class="statbox widget box box-shadow">
    <div class="widget-header">
        <div class="row">
            <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                <h4>Upload CSV file for bulk upload</h4>
            </div>
        </div>
    </div>
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
    <form wire:submit.prevent="import">
    <div class="widget-content widget-content-area">
        <p>please adhen to the following instruction before uploading your transaction</p>
        <p>Use this particular format for your upload</p>
        <p><a href="{{url('assets/transaction/transaction_format.csv')}}" class='btn btn-info'>Download Transaction Format</a></p>
        <div class="custom-file-container" data-upload-id="myFirstImage">
            <label>Upload (Single File) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear CSV">x</a></label>
            <label class="custom-file-container__custom-file" >
                <input type="file" wire:model="file" class="custom-file-container__custom-file__custom-file-input" accept=".csv">
                <span class="custom-file-container__custom-file__custom-file-control"></span>
            </label>
            <div class="custom-file-container__image-preview"></div>
        </div>
        @error('file') <span class="text-danger">{{ $message }}</span> @enderror

    </div>
    <div class="widget-content widget-content-area">

        <p class='alert alert-danger'>Note: 'Type' tell the system what transaction to perform either debit or credit, so be sure before proceeding with transaction</p>
        <div class='form-group'>
            <label>Type</label>
            <select wire:model="type" class='form-control' wire:change="typeChanged">
                <option value="">Select Debit|Credit</option>
                <option value="Debit">Debit</option>
                <option value="Credit">Credit</option>
            </select>
            @error('type') <span class="text-danger error">{{ $message }}</span>@enderror
        </div>
        <div class='form-group'>
            <label> Transaction Type</label>
            <select wire:model="transactionType" class='form-control'>
                <option value="">Select Transaction Type</option>
                @forelse (transactionType($typeloader) as $txtType)
                <option value="{{$txtType->id}}">{{$txtType->transaction_type}}</option>
                @empty
                @endforelse
            </select>
            @error('transactionType') <span class="text-danger error">{{ $message }}</span>@enderror
        </div>
        @if ($sentOTP)
        <div class='form-group'>
                <label>OTP</label>
                <input type='number' wire:model="otp" class='form-control'>
                @error('otp') <span class="text-danger error">{{ $message }}</span>@enderror
        </div>
        @endif
        @if ($sentOTP)
            <button type="submit"  class='btn btn-primary block-page'>Upload Transantion File</button>
        @else
            <a href="#" wire:loading.attr="disabled" wire:click="sendOTP" class='btn btn-primary'>Generate OTP</a>
        @endif


    </div>
</form>
</div>



