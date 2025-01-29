<div class="tab-pane fade" id="v-border-pills-domain" role="tabpanel" aria-labelledby="v-border-pills-domain-tab">
    <div class="info">
        <h5 class="">Domain Name Settings</h5>
        @if(isset($company_settings_record) )
       @if($company_settings_record->subdomain == null )
            <div class='alert alert-warning'>Please contact your hosting / domain provider and request they profile this CNAME on your domain name ::
                <ul class='text-left'>
                <li>Name: ( domain)</li>
                <li>Value: wba.ng</li>
                <li>TTL: Automatic.</li>
                <li>SSL : Issue (Valid for 1year)</li>
                </ul>
                </div>
            <div class="row">
                <div class="col-md-11 mx-auto">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="domain_name">Enter Domain Name</label>
                                <input name='domain_name' id='domain_name' class='form-control' placeholder="Domain Name">
                            </div>
                        </div>
                        <div class='alert alert-info'>Subdomain will have domain.wba.ng attched to your domain name while Domain will have your selected domain name</div>
                        <div class="col-md-6">
                            <div class="form-group" title="Subdomain will have wba.ng attched to your domain name while Domain will have your selected domain name">
                                <label for="domain_type">Domain Type</label>
                                <select id='domain_type' name='domain_type' class='form-control'>
                                    <option value='1'>Sub-domain - {{config('app.subdomainprice')}}</option>
                                    <option value='2'>Domain - {{config('app.maindomainprice')}}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <center><button type='button' class='btn btn-warning' id='saveDomainx' onclick='saveDomain()'>Configure</button></center>
                </div>
            </div>

            @else

            <div class='alert alert-info'>
            <input type="text" class="form-control mb-4" readonly id="input-copy" value="{{($company_settings_record->domain_type==2)?$company_settings_record->subdomain:"https://$company_settings_record->subdomain.wba.ng"}}">

            <a class="mb-1 btn btn-primary" href="javascript:;" data-clipboard-action="copy"
            data-clipboard-target="#input-copy"><i class="fa fa-clipboard"></i> Copy Link</a></div>
        @endif
        @endif
    </div>
</div>
