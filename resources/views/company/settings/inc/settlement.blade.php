<div class="tab-pane fade" id="v-border-pills-settlement" role="tabpanel" aria-labelledby="v-border-pills-settlement-tab">
    <div class="info">
        <style>
            .top-m {
                margin-top: 5px;
                margin-bottom: 15px;
            }
        </style>
        <h5 class="">Settlements</h5>

        <div class='form-group'>
            <p class='alert alert-info'>Once threshold is reached we will send you notification to top up your balance</p>
            <label>Minimum Balance Threshold: </label>
            <input type='text' class='form-control top-m' onkeypress="return onlyNumberKey(event)" name='minimum_balance_threshold' id='minimum_balance_threshold' placeholder="E.G 500..." value="{{@$company_record->CompanySettlement->minimum_balance_threshold}}" />
        </div>
        <div class='form-group'>
            <p class='alert alert-info'>Once Maximum balance is exceeded the system will send your remaining balance to your settlement account</p>
            <label>Maximum Balance: </label>
            <input type='text' class='form-control top-m' onkeypress="return onlyNumberKey(event)" name='maximum_balance' id='maximum_balance' placeholder="E.G 500..." value="{{@$company_record->CompanySettlement->maximum_balance}}" />
        </div>
        <div class='row'>
            <div class='col-sm-6 col-md-6 col-lg-6'>
                <label>Settlement Bank: </label>
                <select name='bank' id='bank' class='form-control' onchange="verifyBankAccount()">
                    @if ($banks)
                    @foreach ($banks as $banksData)
                    <option value='{{$banksData->id}}' code='{{$banksData->bank_code}}' {{(@$company_record->CompanySettlement->bank_id ==$banksData->id )?'selected':''}}>{{$banksData->bank_name}}</option>
                    @endforeach
                    @endif
                </select>
            </div>
            <div class='col-sm-6 col-md-6 col-lg-6'>
                <label>Settlement Account: </label>
                <input type='text' class='form-control top-m' onkeypress="return onlyNumberKey(event)" onkeyup="verifyBankAccount()" name='accountNumber' id='accountNumber' placeholder="E.G 050..." value="{{@$company_record->CompanySettlement->accountNumber}}" />
            </div>
        </div>
        <div class='form-group'>
            <input type='hidden' id='accountName' value="{{@$company_record->CompanySettlement->accountName}}">
            <div id='myBankLoader'>{{@$company_record->CompanySettlement->accountName}}</div>
        </div>
        <div class='form-group text-center'>

            <div class='row m-2'>
                <div class='col-sm-6 col-md-3'><label>Company Setup Pin: </label></div>
                <div class='col-sm-6 col-md-6'><input type='password' maxlength="4" id='pin' onkeypress="return onlyNumberKey(event)" class='form-control' placeholder="0000"></div>
            </div>
            <button class='btn btn-primary btnsettlement' type='button' onclick="settlementForm()">Save</button>
        </div>
    </div>
</div>