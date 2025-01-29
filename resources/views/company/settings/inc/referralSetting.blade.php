<div class="tab-pane fade" id="v-border-pills-referral" role="tabpanel" aria-labelledby="v-border-pills-referral-tab">

    <div class="media">
        <div class="media-body">
            <h5 class="mt-0 mb-3">Referral Settings</h5>
            <div class="info">

                <div class="row">
                    <div class="col-md-11 mx-auto">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="direct_comission" title='Referral of this account will be creditted this amount once you approve the registered users. input 0 to ignore'>Direct Commission(Pending Entry)</label>
                                    <input type="text" class="form-control mb-4" id='direct_comission' name='direct_comission' value="{{@(auth()->user()->company->CustomerReferalSetting->direct_comission)}}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="credit_method" title="select how you want to countinue to credit customers who refer users to you system, Onetime= referral will be credited with above amount once when the reffered user deposit, countinues will credit referral for every deposit done by their downline">Credit Method (Saving Manager - Credit)</label>
                                    <select name='credit_method' id='credit_method' class='form-control'>
                                        <option value='1' {{@(auth()->user()->company->CustomerReferalSetting->credit_method ==1)?'selected':''}}>One Time</option>
                                        <option value='2' {{@(auth()->user()->company->CustomerReferalSetting->credit_method ==2)?'selected':''}}>Continue Credit</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="indirect_comission">In-Direct Commission</label>
                                    <input type="text" class="form-control mb-4" id='indirect_comission' name='indirect_comission' value="{{@(auth()->user()->company->CustomerReferalSetting->indirect_comission)}}">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <center><button type='button' class='btn btn-warning' onclick='saveReferal()'>Save Changes</button></center>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
