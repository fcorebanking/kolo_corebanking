<div class="tab-pane fade" id="v-border-pills-developers" role="tabpanel" aria-labelledby="v-border-pills-developers-tab">

    @if($auth->company->developer_api_status == 1)
    <h5 class="mb-3">Developer Settings</h5>

    <div class="row">

        <div class="col-md-6">
            <div class="form-group">
                <label for="api_key">API Key</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="api_key" value="{{isset($auth->company->apiKey->api_key) ? $auth->company->apiKey->api_key : ''}}" placeholder="Generate New API Key" required readonly aria-label="dropdown">
                    <div class="input-group-append">
                        <a class="btn btn-success dropdown-toggle" href="javascript:copyApiKey()" data-clipboard-action="copy" data-clipboard-target="#api_key" type="button" aria-haspopup="true" aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg> </a>
                    </div>
                </div>
                <input type="hidden" id="base_url" value="{{url('')}}" />
                <a href="javascript:generateAPIKey()" style="float: left; color: blue">Generate API Key</a><br>
                <span class='validation-text'></span>

            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="company_reference">Company Reference</label>
                <div class="input-group mb-4">
                    <input type="text" class="form-control" id="company_reference" readonly value="{{$auth->company->company_reference}}" aria-label="dropdown">
                    <div class="input-group-append">
                        <a href="javascript:copyCompanyReference();" class="btn btn-success dropdown-toggle" type="button" data-clipboard-action="copy" data-clipboard-target="#company_reference" aria-haspopup="true" aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg> </a>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-md-12 mt-4">
            <div class="form-group">
                <label for="request_type">Send Webhook Notification</label>
                <select id='send_notification' onchange="onSendNotficationChange()" name='send_notification' class='form-control'>

                    <option value='0' @if($send_notification=='0' ) selected @endif>No</option>
                    <option value='1' @if($send_notification=='1' ) selected @endif>Yes</option>

                </select>
            </div>
        </div>
        <div class="col-md-12 mb-4" id="webhookUrlDiv">
            <div class="alert alert-info">Kindly note that all funds will be swept and transferred to your company account. This implies that the customer's wallet will not hold funds</div>
            <div class="form-group">
                <label for="webhook_url">Webhook Url</label>
                <input type="text" class="form-control" name="webhook_url" value='{{$webhook_url}}' id="webhook_url" placeholder="Enter Webhook Url">

            </div>
            <span class='validation-text'></span>
        </div>

        <div class="col-md-12">

            <center><button type='button' class='btn btn-warning' onclick='saveDeveloperSettings()'>Save Settings</button></center>
        </div>

        <div class="code-section-container show-code" style="width: 100%;">

            <h4>Sample Webhook Request (POST)</h4>

            <button class="btn toggle-code-snippet"><span>Hide/Show Sample Code</span></button>

            <div class="code-section text-left" style="width: 100%;">
                <pre>
                    {
                        "account_number": "2384958332",
                        "amount": "12400",
                        "transaction_id": "KD02KDLSKDLSDILSKDS",
                        "narration": "very important money",
                        "status": "PENDING",
                        "transaction_date": "12-08-2022 00:12:23"
                    }
                    </pre>
            </div>
        </div>
    </div>



    @endif

    @if($auth->company->developer_api_status == 0)
    <div class="row">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h3><b>Contact Support</b></h3>

                <div class="col-md-10" style="margin: 0 auto;">
                    <h5>Kindly contact Finova Customer support to Activate Developer Settings
                    </h5>

                    <p style="font-size: 16px;">For more details: <br> mail: <a href="mailto:support@finova.ng">support@finova.ng</a> or call:
                        <a href="tel:09155839310">09155839310</a>
                    </p>
                </div>
            </div>
        </div>

    </div>
    @endif
</div>
