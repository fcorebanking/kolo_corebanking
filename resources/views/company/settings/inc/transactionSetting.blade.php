<div class="col-md-11 mx-auto">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>
                    View Vault On TXN
                </label>
                <select id='view_vault_on_txn' name='view_vault_on_txn' class='form-control'>
                    <option value='1' @if($transactionSetting) @if($transactionSetting->view_vault_on_txn == 1) selected @endif @endif>Enable</option>
                    <option value='2' @if($transactionSetting) @if($transactionSetting->view_vault_on_txn == 2) selected @endif @endif>Disable</option>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label>
                    Hide Charge on Statement
                </label>
                <select id='hide_charge_on_statement' name='hide_charge_on_statement' class='form-control'>
                    <option value='1' @if($transactionSetting)@if($transactionSetting->hide_charge_on_statement == 1)selected @endif @endif>Enable</option>
                    <option value='2' @if($transactionSetting)@if($transactionSetting->hide_charge_on_statement == 2)selected @endif @endif>Disable</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>
                    View Instrument Number
                </label>
                <select id='view_instrument_number' name='view_instrument_number' class='form-control'>
                    <option value='1' @if($transactionSetting)@if($transactionSetting->view_instrument_number == 1)selected @endif @endif>Enable</option>
                    <option value='2' @if($transactionSetting)@if($transactionSetting->view_instrument_number == 2)selected @endif @endif>Disable</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>
                    Customer can Transfer
                </label>
                <select id='customer_can_transfer' name='customer_can_transfer' class='form-control'>
                    <option value='1' @if($transactionSetting)@if($transactionSetting->customer_can_transfer == 1)selected @endif @endif>Enable</option>
                    <option value='2' @if($transactionSetting)@if($transactionSetting->customer_can_transfer == 2)selected @endif @endif>Disable</option>
                </select>
            </div>
    </div>
    <div class='text-center'>
        <button class='btn btn-primary ' onclick="saveTransactionSettings()">Save</button>
    </div>


    </div>
</div>
