<div class="modal voucherModel" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Approve vault</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class='alert alert-info'>Click to select destination wallet</div>

            <div class='row'>
                <div class='col-sm-6 col-md-4'><button onclick="viewVault('staffvault')" class='btn btn-primary'>Staff Vault</button></div>
                <div class='col-sm-6 col-md-4'><button onclick="viewVault('branchvault')" class='btn btn-info'>Branch Vault</button></div>
                <div class='col-sm-6 col-md-4'><button onclick="viewVault('othervault')" class='btn btn-success'>Other Vault</button></div>
            </div>
            <input type='hidden' name='selectedtxn' id='selectedtxn' ?>
            <input type='hidden' name='selectedaction' id='selectedaction' ?>
            <input type='hidden' name='vaultamount' id='vaultamount' ?>
            <h4>Amount: <span class='vaultamountdisplay'></span></h4>
            <div class="form-group branchvault" id="branchvault">
                <label for="branchvaultvalue">Branch</label>
                <select id='branchvaultvalue' class='form-control' >
                    @if($branchdata)
                    @foreach($branchdata as $company_branch)
                    <option value='{{$company_branch->id}}'>{{setTitle($company_branch->branch_name)}}</option>
                    @endforeach
                    @endif
                </select>
            </div>
            <div class="form-group staffvault" id="staffvault">
                <label for="staffvaultvalue">Staff</label>
                <select id='staffvaultvalue' class='form-control' >
                    @if($staff)
                    @foreach($staff as $company_staff)
                    <option value='{{@$company_staff->uuid}}'>{{setTitle(@$company_staff->first_name).' '.setTitle(@$company_staff->last_name).' - '.number_format(@$company_staff->wallet->balance)}}</option>
                    @endforeach
                    @endif
                </select>
            </div>
            <div class="form-group othervault" id="othervault">
                <label for="othervaultvalue">Wallet</label>
                <select id='othervaultvalue' class='form-control' >
                    @if($company_vault)
                    @foreach($company_vault as $company_vault_data)
                    <option value='{{$company_vault_data->id}}'>{{setTitle($company_vault_data->vault)}}</option>
                    @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary approveVoucherbtn" onclick="approveVoucher()">Send</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
