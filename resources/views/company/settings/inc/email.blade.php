<div class="tab-pane fade" id="v-border-pills-email" role="tabpanel" aria-labelledby="v-border-pills-email-tab">

    <div class="media">
        <div class="media-body">
            <h5 class="mt-0 mb-3">Email Settings</h5>
            <div class="info">
                <div class='responseMessage'></div>
                <div class="row">
                    <div class="col-md-11 mx-auto">
                        <div class="row">
                            <div class="col-md-6">
                        <div class="btn-group" style="font-weight:bold">
                            Turn Off/On Custom Email:
                            <label class="switch s-outline s-outline-success  mb-4 mr-2">
                                <input type="checkbox" id='companyemail_active'>
                                <span class="slider round"></span>
                            </label>
                        </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username_email">Email</label>
                                    <input type="text" class="form-control mb-4" id='username_email' value='{{@(auth()->user()->company->companyEmail->MAIL_USERNAME)}}'>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username_password">Password</label>
                                    <input type="text" class="form-control mb-4" id='username_password' value='{{@(auth()->user()->company->companyEmail->MAIL_PASSWORD)}}'>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mail_from">Mail From</label>
                                    <input type="text" class="form-control mb-4" id='mail_from' value='{{@(auth()->user()->company->companyEmail->MAIL_USERNAME)}}'>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mail_host">Mail Host (for Gmail smtp.gmail.com)</label>
                                    <input type="text" class="form-control mb-4" id='mail_host' value='{{@(auth()->user()->company->companyEmail->MAIL_HOST)}}'>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mail_host">Mail port (for Gmail use 587)</label>
                                    <input type="text" class="form-control mb-4" id='mail_host_port' value='{{@(auth()->user()->company->companyEmail->MAIL_PORT)}}'>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mail_encryption">Encryption (Gmail use TLS)</label>
                                    <select class="form-control mb-4" id='mail_encryption' >
                                    <option value='ssl' @if (@(auth()->user()->company->companyEmail->MAIL_ENCRYPTION) == 'ssl') selected @endif>SSL</option>
                                    <option value='tls' @if (@(auth()->user()->company->companyEmail->MAIL_ENCRYPTION) == 'tls') selected @endif>TLS</option>
                                </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <center><button type='button' class='btn btn-warning' onclick='saveEmail()'>Save Changes</button></center>
                            </div>


                    </div>
                    </div>
                </div>
            </div>
            <div class='info'>
                <div class='row'>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="test_to_email">Send to Email</label>
                            <input type="text" class="form-control mb-4" id='test_to_email' >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="test_subject">Subject</label>
                            <input type="text" class="form-control mb-4" id='test_subject' >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="test_message">Message</label>
                            <textarea class="form-control mb-4" id='test_message' ></textarea>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <center><button type='button' class='btn btn-warning' id='testMail' onclick='sendTest()'>Send Test</button></center>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
