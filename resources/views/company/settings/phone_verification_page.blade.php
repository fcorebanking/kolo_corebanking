<div class="layout-px-spacing">
    <div class="row layout-top-spacing" style="justify-content: center;">

        @if(auth()->user()->phone_verification_status_id == 0)
        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h4>Phone Verification</h4>
                <h3 style="margin-bottom: 30px;"></h3>

                <div class="col-md-10" style="margin: 0 auto;">
                    <div class="alert alert-info">
                        <h5>Kindly Enter the OTP sent to <b>{{$phone_number}}</b></h5>
                    </div>
                </div>
                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
                    <div class="col-md-10">
                        <div class="clipboard">
                            <form class="form-horizontal">
                                <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                <input type="number" maxlength="4" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" class="form-control mb-4" id="otpCode">

                                <span class="validation-text"></span>

                                <button class="mb-1 btn btn-primary" onclick="verifyPhoneNumber()" disabled id="verifyBtn"><i class="fa fa-clipboard"></i> Verify
                                    Phone</button>
                                <button type="button" class="mb-1 btn btn-info" onclick="resendingOtp()" id="resendOtpBtn"><i class="fa fa-clipboard"></i> Resend OTP </button>
                            </form>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        @endif

        @if(auth()->user()->phone_verification_status_id == 1 && (auth()->user()->company->status_id == 7 ||
        auth()->user()->company->status_id == 10 || auth()->user()->company->status_id == 11))

        @if(auth()->user()->phone_verification_status_id == 1 && auth()->user()->company->status_id == 10)

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <!--<h3><b>Pay Setup Fee</b></h3>-->
                <!--<h4 style="margin-top: 30px;font-weight:600">Pay Via USD Card</h4>-->

                <!--<div class="col-md-10" style="margin: 0 auto;">-->
                <!--    <h5>Kindly click the button below to pay your setup fee of <strong>$15</strong>. while you complete-->
                <!--        your KYC form.-->
                <!--    </h5>-->
                <!--</div>-->


                <!--<div class="row" style="flex-direction: row; justify-content: center; margin-top: 20px; align-items: center;">-->
                <!--    <div class="col-md-10">-->
                <!--        <div class="clipboard">-->

                <!--            <input type="hidden" id="company_email" value="{{auth()->user()->email}}" />-->
                <!--            <input type="hidden" id="company_phone" value="{{auth()->user()->phone}}" />-->
                <!--            <input type="hidden" id="full_name" value="{{auth()->user()->first_name}}" />-->

                <!--            <input type="hidden" id="companyCurrency" value="{{auth()->user()->company->currency->code}}" />-->
                <!--            <button class="btn btn-info btn-lg mt-3" id="paySetupFeeBtn" onclick="payWithKlasha()">-->
                <!--                Pay With USD Card-->
                <!--            </button>-->

                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->

                <div class="col-md-10" style="margin: 0 auto; margin-top: 100px">
                <h4 style="margin-top: 30px;font-weight:600">Pay Via Transfer in Naira (Nigerian Companies)</h4>

                    <h5> kindly transfer the amount below to the account number below to pay in naira. The amount can be above the displayed value but cannot be less.
                    </h5>
                </div>

                <div class="alert alert-info" style="margin-top: 30px;">
                    <h5>Exchange Rate:</h5>
                    <h4><b> 15 USD -> <span id="exchangeAmounter"></span>
                            {{auth()->user()->company->currency->code}} (NGN 27,000) </span></b></h4>
                </div>

                <h4 style="margin-top: 30;"><b>Account Details</b></h4>
                <div class="col-md-6 offset-md-3" style="display:flex; flex-direction: column; justify-content: center; align-items: center; ">
                    <h6 style="color: #aaaaaa;">Account Name</h6>
                    <h5>{{auth()->user()->company->company_name}}</h5>
                    <h6 style="margin-top: 10px; color: #aaaaaa;">Account Number</h6>
                    <h5>{{auth()->user()->company->wallet->vaccount}}</h5>
                    <h6 style="margin-top: 10px; color: #aaaaaa;">Bank</h6>
                    <h5>{{auth()->user()->company->wallet->bank->bank_name}}</h5>
                </div>

                <div class="col-md-10" style="margin: 0 auto; padding-top: 50px">
                <h4 style="margin-top: 30px;font-weight:600">Support</h4>
                    <h5>If you encounter issue with payment do let us know</h5>
                </div>



            </div>
        </div>

        @endif

        @if(auth()->user()->phone_verification_status_id == 1 && auth()->user()->company->status_id == 7)

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h4><b>Company KYC</b></h4>
                <h3 style="margin-bottom: 30px;"></h3>

                <div class="col-md-10" style="margin: 0 auto;">
                    <h5>Kindly click on the Button below to complete your company KYC. once verification is completed
                        you will be given acesss to use the system
                    </h5>

                    <p style="font-size: 16px;">For more details: mail: <a href="mailto:info@wiredbanking.ng">info@wiredbanking.ng</a> or call:
                        <a href="tel:018880311">018880311</a>
                    </p>
                </div>
                <div class="row" style="flex-direction: row; justify-content: center; margin-top: 20px; align-items: center;">
                    <div class="col-md-10">
                        <div class="clipboard">
                            <form class="form-horizontal">
                                <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                <a target="_blank" class="btn btn-info btn-lg mt-3" href="#">
                                    Complete Company Profile Now
                                </a>


                            </form>
                        </div>
                    </div>

                </div>

            </div>
        </div>
        @endif

        @if(auth()->user()->phone_verification_status_id == 1 && auth()->user()->company->status_id == 11 )

        <div class="col-xl-10 col-lg-10 col-sm-10" style="align-self: center;">
            <div class="widget-content widget-content-area">

                <h4><b>Terms and Service</b></h4>
                <h3 style="margin-bottom: 30px;"></h3>

                <div class="col-md-12" style="text-align: left;">

                    <p>These Terms of Service is a contract between you and Finova Limited (referred to in these
                        Terms of Service as "Beyond Money MFB", "us", "we", or "our"), the provider of the Finova Limited website, and the
                        services accessible from the finova website (which are collectively referred to in these Terms of
                        Service as the "Finova Limited")</p>

                    <p>AND <b> {{auth()->user()->company->company_name}}</b> </p>

                    <p>You accept that these terms of service will apply to you. Please refrain from using the Finova Limited
                        Service if you disagree with our terms of service. "You" refers to the individual, client,
                        and/or organization you represent in these Terms of Service. Suppose you violate any of these
                        Terms of Service. In that case, we reserve the right to suspend your account or block access to
                        your account without notice.</p>

                    <p>1. <strong>YOUR ACCOUNT</strong></p>

                    <p>1.1 Eligibility: Your account must be registered by a human. Accounts registered by "bots" or
                        other automated methods are not permitted. Additionally, you must be 18 years and above.</p>

                    <p>1.2 Registration Information: During the registration process, you must supply a working
                        permanent email address, a working phone number, your BVN (for Nigerian merchants) or NIN (for
                        foreign merchants), as well as any additional information the Finova Limited compliance team may request
                        during kyc. A single person or company is only permitted to have one account.</p>

                    <p>1.3 Restrictions: You agree not to use the Finova Limited service in or for any illegal, unauthorized or
                        improper manner or purpose and agree that the Finova Limited Service will only be used in a way that
                        complies with all applicable laws, rules, and regulations, including all appropriate state,
                        federal and international internet, data, telecommunications, telemarketing, "spam", and
                        import/export laws and regulations. Without limitation, you agree that you will not breach this
                        restriction by:</p>

                    <ul>
                        <li> Violating or infringing on the rights of any third party, including without limitation any
                            intellectual property rights, privacy rights, or publicity rights; disseminating material
                            that is obscene, offensive, fraudulent, deceptive, defamatory, threatening, harassing,
                            abusive, slanderous, hateful, or causes embarrassment to any third party as determined by
                            Finova Limited in its sole discretion; or purposefully misrepresenting your identity, impersonating
                            someone else</li>
                        <li>Disseminate any material that would expose us or any related third party to liability.</li>
                    </ul>

                    <p>2. <strong>PAYMENT | SUBSCRIPTION</strong> </p>

                    <p>A valid credit card is required for automated recurring payments. Payment may also be made via
                        Wallet or bank transfer, or other payment methods that may be enabled from time to time. <br>
                        <br>
                        <br>
                    </p>

                    <p>3. <strong>DISPUTE AND REFUND</strong></p>

                    <p>When a Transaction is performed, and the recipient did not receive value, this can be due to the
                        provider network or internal error. You are to engage the Finova Limited team with your reference ID after
                        twenty-four (24) hours of the transaction. Resolution or concern should be resolved within the
                        end of the business day. If not successful, payment will be refunded to your account.</p>

                    <p>4. <strong>INFLOW, INWARD PAYMENT REFUNDABLE</strong> </p>

                    <p>When a transfer is made to you, or you received payment from a third-party bank, and we were
                        engaged from the source account provider (bank) stating the funds are illegitimate or
                        fraudulent, funds will be held, and an internal investigation will commence. If this turns out
                        to be accurate, funds will be reversed or refunded back to the origin, multiple report of such
                        transaction will lead to suspension of merchant account without notice for due diligence and
                        compliance purpose.</p>


                    <p>6. <strong>REFUNDABLE AND NOON- REFUNDABLE</strong></p>

                    <p>Here are the situations in which you can expect us to give a refund:</p>

                    <ul>
                        <li>The platform is "not as described </li>
                        <li>The platform does not work the way it should, as stated in the video or live demo.</li>
                        <li>The platform has not been activated after setup fee and subscription fee has been paid.</li>
                    </ul>

                    <p>7. Non-refundable</p>

                    <p>Suppose the platform is materially similar to the description and preview and works the way it
                        should. In that case, there is generally no obligation to provide a refund in situations like
                        the following:</p>

                    <p>• You do not want it after you've activated your account.</p>

                    <p>• You changed your mind.</p>

                    <p>• You paid and activated your account by mistake.</p>

                    <p>• You do not have sufficient expertise to use the platform.</p>

                    <p>• You did not receive support on time.</p>

                    <p>• You can no longer access our API</p>

                    <p>• Our platform is not accessible on your device.</p>

                    <p><strong>8. TERMS AND TERMINATION</strong></p>

                    <p>At least five (5) days before the expiration of the Initial Service Period, the Customer may
                        renew for a month or one (1) year term unless one party provides the other party with written
                        notice of termination. If the customer does not renew before the expiration of the Initial
                        Service Period, all Support Services will be terminated.</p>

                    <p>Your Finova Limited or WBL account will be temporarily suspended, and access to the service will be denied
                        for B2B - B2C. However, during this phase, your data is safe and backup and can be accessed when
                        you renew.</p>

                    <p><strong>9. SECURITY BREACH</strong></p>

                    <p>• Internal Breach: You are solely responsible for taking corrective action in the event of an
                        internal security breach, as that term is defined by applicable law, caused by an employee,
                        contractor, or agent of yours, or as a result of your failure to maintain your systems, network,
                        or data in a secure manner. You must also notify Finova Limited right away of the breach
                        and the steps you intend to take to address it. To protect you, us, the service, or other third
                        parties from harm, we reserve the right to take any action, including suspending your access to
                        the service. You give up the right to sue us for any losses you sustain as a result of our
                        activities.</p>

                    <p>• External Breach: In the event that a security breach, as defined by applicable law, is caused
                        by a person other than an employee, contractor, or agent of yours, Finova Limited will:
                        (a) initiate remedial actions that are compliant with applicable law and consistent with
                        industry standards; and (b) notify you of the security breach, its nature and scope, the
                        remedial actions Finova Limited will take, and the timeframe within which Wired Banking
                        Africa will complete them. </p>

                    <p>10. <strong>DATA PROTECTION</strong></p>

                    <p>The parties herein agree to strictly comply with all applicable privacy and data protection laws,
                        such as the Nigeria Data Protection Regulation, as well as all other applicable regulations and
                        directives in collecting, using, processing, handling, and transferring any the confidential
                        information disclosed by a party in connection with the service, which may contain any personal
                        data. Each party agrees to fully indemnify the other party if there is any breach of any
                        applicable laws concerning privacy and data protection laws by such party.</p>

                    <p>11. <strong>AMENDMENTS</strong></p>

                    <p>This privacy and cookie policy may be amended from time to time consistent with the requirements
                        of the General Data Protection Regulation. We will post any revised policy on this website.</p>

                    <p>12. <strong>NIGERIA DATA PROTECTION REGULATION</strong></p>

                    <p>Under the Nigeria Data Protection Regulation 2019 (NDPR), there are certain occasions when Wired
                        Banking Africa must obtain your explicit consent to use your information, known as 'Personal
                        Data'. Personal Data includes but is not limited to your name, address, telephone number, email
                        address, card details, and Bank Verification Number (BVN).</p>

                    <p>We shall hold and use your information for the purposes set in the Data Processing Notice, which
                        requires consent under the Privacy Policy. You shall grant Finova Limited consent to
                        collect your name, official Government ID Number (ID Number), and photo image.</p>

                    <p>Details Collection</p>

                    <p>Finova Limited may share this information with a third-party service provider to validate
                        it against a government source. The third-party service provider may use your ID Number to
                        gather your additional data, such as:</p>

                    <ul>
                        <li>Full Name</li>
                        <li>Date of Birth</li>
                        <li>ID Number</li>
                        <li>Image</li>
                        <li>Address</li>
                        <li>Phone Number</li>
                        <li>Gender</li>
                    </ul>

                    <p>This data helps confirm your identity and may be processed or stored outside the borders of
                        Nigeria.</p>

                    <p><strong>12. WARRANTIES, DISCLAIMERS, AND EXCLUSIVE REMEDIES</strong><br>
                        12.1 Finova Limited warrants that support services will be provided in a professional
                        manner consistent with industry standards. Customer must notify Finova Limited of any service deficiencies
                        within two (2) days (48) from performance of the defective services.</p>

                    <p>Finova Limited does not guarantee that the software will perform error-free or uninterrupted or that Finova Limited
                        will correct all software errors immediately.</p>

                    <p>12.2 for any breach of the above warranties, customer’s exclusive remedy and Finova Limited’s entire
                        liability shall be: (a) the correction of software errors that cause breach of the warranty; or,
                        if Finova Limited cannot substantially correct such breach in a commercially reasonable time frame,
                        customer may end the services period and recover a prorated portion of any maintenance fees
                        prepaid to Finova Limited; or (b) the reperformance of the deficient support services. </p>

                    <p>To the extent permitted by law, these warranties are exclusive<br>
                        and there are no other express or implied warranties or conditions including warranties or
                        conditions of merchantability and fitness for a particular purpose.</p>

                    <p><strong>13. ABUSE AND MISUSE OF SUPPORT</strong><br>
                        Abuse to Finova Limited support platforms in the form of spam, unsolicited contents, submission of requests
                        in multiple-duplicates, use of "offensive or abusive" contents and other similar abuses are
                        prohibited. Finova Limited reserves the right to terminate access to its entire or part of support platform
                        on the bases of abuse, with or without notice</p>

                    <p><strong>14. MISCELLANEOUS PROVISIONS</strong><br>
                        14.1 confidentiality. Neither party will disclose any support services-related terms,
                        conditions, and/or fee amounts to any third party, unless required to do so by order of any
                        court or governmental authority of competent jurisdiction.</p>

                    <p>14.2 binding. No assignment. This agreement shall be binding upon and inure to the benefit of the
                        parties and their successors, permitted assigns, and legal representatives. Customer may not
                        assign, sublicense, or otherwise transfer the support services unless in conjunction with a
                        transfer of the software.</p>

                    <p>14.3 language. This agreement has been agreed to only in the english language. This english
                        language version will control regardless of whether any translations of this agreement have been
                        prepared or exchanged. Customer acknowledges and represents that it has carefully reviewed this
                        agreement with the involvement and assistance of your employees, advisors, and/ or legal counsel
                        fluent in the english language, that it has consulted with local legal counsel and counsel
                        competent to render advice with respect to transactions governed by the law applicable to this
                        agreement, that it has no questions regarding the meaning or effect of any of this agreement’s
                        terms, and that it has obtained high-quality translations of this agreement for use by customer
                        or any of customer’s team who are not fluent in the english language, with the understanding
                        that customer alone will bear the risk of any misunderstandings that may arise as a result of
                        such translation. All communications in connection with this agreement will be in the english
                        language.</p>

                    <p>14.4 notices to Finova Limited. Any notices to Finova Limited under this agreement must be in writing and either
                        delivered in person, sent by email, or first class mail (if within the united states), or sent
                        by air courier to the address set forth below. Notices will be considered to have been given at
                        the time of actual delivery in person, five (5) business days after deposit in the mail, or two
                        business (2) days after delivery to an air courier service or confirmation of email receipt.</p>

                    <p>15. <strong>PUBLICITY</strong></p>

                    <p>The Customer agrees that Finova Limited may disclose that the Customer is using the Finova Limited Software for the
                        Customer's business operations. In this regard, Finova Limited may use the Customer's name, trade mark,
                        trade name, service mark, and logo (collectively referred to as the trademarks) to identify the
                        Customer as a Finova Limited client on the marketing information, either on the Finova Limited website or elsewhere.
                    </p>

                    <p>I, <b>{{auth()->user()->first_name}} {{auth()->user()->last_name}}</b>, of the following address;
                        <b>{{auth()->user()->address}}</b>,
                        hereby declare that I fully understand and agree to the above terms and conditions.
                    </p>
                    <br>
                    <p><strong>FOR MERCHANT ONLY</strong></p>

                    <p><strong>Name:</strong> {{auth()->user()->first_name}} {{auth()->user()->last_name}} </p>

                    <p><strong>Business Name:</strong> {{auth()->user()->company->company_name}} </p>

                    <p><strong>Designation:</strong> </p>

                    <p><strong>Phone Number:</strong> {{auth()->user()->phone}} </p>

                    <p><strong>Address:</strong> {{auth()->user()->address}} </p>

                    <p><strong>Signature:</strong> <img src="{{auth()->user()->company->signature}}" height="80" /> <br>
                        <br>
                    </p>

                    <p><strong>AUTHORIZED FOR Finova Limited PERSONNEL ONLY</strong></p>

                    <p><strong>Name:</strong> _______________________________________________________</p>

                    <p><strong>Designation:</strong> CEO</p>

                    <p><strong>Phone:</strong> _______________________________________________________</p>

                    <p><strong>Address:</strong>_______________________________________________________</p>

                    <p><strong>Signature:</strong> <img src="/sign.png" height="80" /> </p>

                    <p><br>
                        <br>
                    </p>

                    <form class="form-horizontal">
                        <div class="row" style="flex-direction: row; justify-content: space-evenly; margin-top: 20px; align-items: center;">
                            <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                            <a class="btn btn-danger btn-lg mt-3" href="{{route('terms-of-service-disagree')}}">
                                Disagree
                            </a>

                            <a class="btn btn-info btn-lg mt-3" href="{{route('terms-of-service-agree')}}">
                                Agree and Continue
                            </a>

                        </div>
                    </form>

                </div>
            </div>
            @endif

            @endif

            @if(auth()->user()->company->status_id == 5)

            <div class="col-xl-12 col-lg-12 col-sm-12">
                <div class="widget-content widget-content-area">

                    <h4><b>Fraud Detect</b></h4>
                    <h3 style="margin-bottom: 30px;"></h3>

                    <div class="col-md-10" style="margin: 0 auto;">
                        <h5>Your Account has been suspended due to suspected fraud. Kindly contact customer support to
                            re-activate your account
                        </h5>

                        <p style="font-size: 16px;">For more details: mail: <a href="mailto:info@finova.ng">info@finova.ng</a> or call:
                            <a href="tel:0000000">0000000000</a>
                        </p>
                    </div>

                </div>
            </div>

            @endif

        </div>

        <script>
            $('#otpCode').on('input', function(event) {

                $('#verifyBtn').prop("disabled", $('#otpCode').val().length != 4);

            })
        </script>

    </div>
    @include('inc.scripts')