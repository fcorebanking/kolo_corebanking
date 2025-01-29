<div class="tab-pane fade" id="v-border-pills-settings" role="tabpanel" aria-labelledby="v-border-pills-settings-tab">
    <h5 class="">Social</h5>
    <div class="row">

        <div class="col-md-11 mx-auto">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group social-linkedin mb-3">
                        <div class="input-group-prepend mr-3">
                            <span class="input-group-text" id="linkedin"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-linkedin">
                                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                                    <rect x="2" y="9" width="4" height="12"></rect>
                                    <circle cx="4" cy="4" r="2"></circle>
                                </svg></span>
                        </div>
                        <input type="text" class="form-control" placeholder="linkedin Username" aria-label="Username" aria-describedby="linkedin" value="{{$link1}}" name='link1'>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="input-group social-tweet mb-3">
                        <div class="input-group-prepend mr-3">
                            <span class="input-group-text" id="tweet"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-twitter">
                                    <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
                                </svg></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Twitter Username" aria-label="Username" aria-describedby="tweet" value="{{$link2}}" name='link2'>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-11 mx-auto">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group social-fb mb-3">
                        <div class="input-group-prepend mr-3">
                            <span class="input-group-text" id="fb"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-facebook">
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                                </svg></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Facebook Username" aria-label="Username" aria-describedby="fb" name='link3' value="{{$link3}}">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="input-group social-github mb-3">
                        <div class="input-group-prepend mr-3">
                            <span class="input-group-text" id="github"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-github">
                                    <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
                                </svg></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Youtube Channel" aria-label="Username" aria-describedby="github" name='link4' value="{{$link4}}">
                    </div>
                </div>
                <div class="col-md-=12">
                    <div class='alert alert-info'>Note: The following information will be visible in your app settings, in order to save changes enter set-up pin in advance tab</div>
                </div>
                    <div class="col-md-6">
                    <div class="input-group social-github mb-3">

                        <input type="text" class="form-control" placeholder="Contact Email" aria-label="company_email" aria-describedby="company_email" name='company_email' value="{{$company_email}}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-group social-github mb-3">

                        <input type="text" class="form-control" placeholder="Contact Phone" aria-label="company_phone" aria-describedby="company_phone" name='company_phone' value="{{$company_phone}}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
