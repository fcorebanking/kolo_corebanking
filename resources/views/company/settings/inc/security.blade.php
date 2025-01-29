<div class="tab-pane fade" id="v-border-pills-security" role="tabpanel" aria-labelledby="v-border-pills-security-tab">
    <div class="info">
        <style>
            .top-m {
                margin-top: 5px;
                margin-bottom: 15px;
            }
        </style>
        <h5 class="">Security</h5>

        <div class='row'>
            <div class='col-sm-6 col-md-12 col-lg-12'>
                <label>2-Factor Authentication:  </label>
                <select id="authfactorsetup" class='form-control m-2' >
                    <option value='0' {{(@$company_settings_record->authfactorsetup==0)?'selected':'' }}>Off 2Factor</option>
                    <option value='1' {{(@$company_settings_record->authfactorsetup==1)?'selected':'' }}>SMS OTP</option>
                    <option value='2' {{(@$company_settings_record->authfactorsetup==2)?'selected':'' }}>Google 2 Factor Authenticator</option>

                </select>
            </div>
        </div>
        <div class='form-group text-center'>
            <button class='btn btn-primary btnsettlement' type='button' onclick="settingForm()">Save</button>
        </div>
    </div>
</div>
