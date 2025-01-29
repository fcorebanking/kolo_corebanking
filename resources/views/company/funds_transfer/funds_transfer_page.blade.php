@include('inc.styles')
<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h3>Funds Transfer</h3>


                <ul class="nav nav-tabs  mb-3 mt-3" id="iconTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="icon-home-tab" data-toggle="tab" href="#icon-home" role="tab"
                            aria-controls="icon-home" aria-selected="true"><svg xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="feather feather-home">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg> Company Wallet Transfer</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" id="icon-contact-tab" data-toggle="tab" href="#icon-contact" role="tab"
                            aria-controls="icon-contact" aria-selected="false"><svg xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="feather feather-phone">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                </path>
                            </svg> Bank Transfer</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" id="icon-mobile-money-tab" data-toggle="tab" href="#icon-mobile-money"
                            role="tab" aria-controls="icon-mobile-money" aria-selected="false"><svg
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="feather feather-send">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg> Mobile Money</a>
                    </li>
                </ul>


                <div class="tab-content" id="iconTabContent-1">
                    <div class="tab-pane fade show active" id="icon-home" role="tabpanel"
                        aria-labelledby="icon-home-tab">
                        <div class="container">
                            <form id="fund_wallet">

                                <div class="alert alert-success mb-4" id="statusMessageDiv" style="display: none;"
                                    role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    <strong>
                                        <span id="statusMessage"></span>
                                    </strong>
                                    </button>
                                </div>

                                @if ($message = Session::get('success'))
                                <div class="alert alert-success mb-4" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    <strong> {{$message}}.</button>
                                </div>
                                @endif


                                <div class="form-group ">
                                    <label for="amount">Account Number</label>
                                    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                    <input type="number" name="accountNumber" id="accountNumber" maxlength="10"
                                        placeholder="Enter Account Number" class="form-control"
                                        oninput="verifyWalletAccountNumber(this.value)" required>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="display: none;" id="myLoader">

                                    <div class="d-flex mb-4"
                                        style="flex-direction: row; justify-content:flex-center; align-items: center;">
                                        <div class="spinner-border text-success loader-xs inline-block "
                                            style="height: 20px; width: 20px;"></div>&nbsp;
                                        <p style="margin-top: 10px;">Getting Account Details</p>

                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="amount">Account Name</label>
                                    <input type="text" name="accountName" id="accountName" placeholder="Account Name"
                                        class="form-control" style="color: #555555;" readonly>
                                    <input type="hidden" name="currentCompanyCurrentCode" id="currentCompanyCurrentCode"
                                        value="{{Auth()->user()->company->currency->code}}" readonly>
                                    <input type="hidden" name="companyCurrencyCode" id="companyCurrencyCode" readonly>
                                    <input type="hidden" name="exchangeAmountValue" id="exchangeAmountValue" readonly>
                                    <input type="hidden" name="finalAmount" id="finalAmount" readonly>
                                    <input type="hidden" name="exchangeCharge" id="exchangeCharge" readonly>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group ">
                                    <label for="amount">Amount</label>
                                    <input id="amount" type="number" oninput="betterFunction(event)" name="amount"
                                        placeholder="Enter Amount" class="form-control" required>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                                <div class="row">
                                    <div class="col-sm-6">
                                        <p id="loaderText"></p>
                                    </div>
                                </div>

                                <div class="form-group" id="exchangeDiv" style="display:none">
                                    <label for="" class="col-sm-3 control-label" style="color:blue;"></label>
                                    <div class="col-sm-6">
                                        <div class="jumbotron jumbotron-fluid"
                                            style="padding-top: 8px; padding-bottom: 8px; margin-bottom: 10px">
                                            <div class="container">
                                                <h6><b>Exchange Rates</b></h6>
                                                <p><span id="baseCurrency"></span> <span id="baseAmount"></span> =
                                                    <span id="exchangeCurrency"></span> <span
                                                        id="exchangeAmount"></span> at
                                                    <span id="exchangeRate"></span> rate<br>
                                                    <b>Charge:</b> {{Auth()->user()->company->currency->code}} <span
                                                        id="exchangeCharger"></span><br>
                                                    <b>Total:</b> {{Auth()->user()->company->currency->code}} <span
                                                        id="finalAmountTxt"></span>
                                                </p>
                                                <p style="color: red; font-size: 18px" id="balanceStatus"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <label for="" class="col-sm-3 control-label"></label>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" placeholder="Enter Description" id="description"
                                        name="description" rows="3"></textarea>
                                    <span class="validation-text"></span>
                                </div>


                                <button type="button" id="initiateCompanyTransactionBtn" class="btn btn-primary start"
                                    onclick="showTransactionSummary()">
                                    <i class="icon-upload icon-large"></i>
                                    <span>Continue</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="icon-contact" role="tabpanel">
                        <div class="container">
                            <form id="bank_transfer_form">

                                <div class="alert alert-success mb-4" id="statusMessageDiv" style="display: none;"
                                    role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    <strong>
                                        <span id="statusMessage"></span>
                                    </strong>
                                    </button>
                                </div>

                                @if ($message = Session::get('success'))
                                <div class="alert alert-success mb-4" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    <strong> {{$message}}.</button>
                                </div>
                                @endif

                                <div class="form-group ">
                                    <label for="transferBankCode">Select Bank</label>

                                    <select class="form-control basic" id="transferBankCode" name="transferBankCode"
                                        onchange="verifyBankAccount()">

                                        <option value="">::Select Bank::</option>
                                        <option value="100001">FETS (My Wallet)</option>
                                        <option value="100002">Paga</option>
                                        <option value="100003">Parkway-Readycash</option>
                                        <option value="100004">Paycom (Opay)</option>
                                        <option value="100005">Cellulant</option>
                                        <option value="100006">Etranzact</option>
                                        <option value="100007">Stanbic IBTC @Ease Wal</option>
                                        <option value="100008">Ecobank Xpress</option>
                                        <option value="100009">GT Mobile Money</option>
                                        <option value="100010">Teasy Mobile</option>
                                        <option value="100011">M-Kudi</option>
                                        <option value="100012">Vtnetwork</option>
                                        <option value="100013">Accessmoney</option>
                                        <option value="100014">Firstmonie Wallet</option>
                                        <option value="100015">Kegow</option>
                                        <option value="100016">Fortis Mobile</option>
                                        <option value="100017">Hedonmark</option>
                                        <option value="100018">Zenith Mobile</option>
                                        <option value="100019">Fidelity Mobile</option>
                                        <option value="100020">Moneybox</option>
                                        <option value="100021">Eartholeum (Qik Qik)</option>
                                        <option value="100022">Gomoney</option>
                                        <option value="100023">Tagpay</option>
                                        <option value="100024">Imperial Homes Mortgage Bank</option>
                                        <option value="100025">Zinternet</option>
                                        <option value="100026">One Finance</option>
                                        <option value="100027">Mimoney Intellifin Solutions</option>
                                        <option value="100028">Ag Mortgage Bank</option>
                                        <option value="100029">Innovectives Kesh</option>
                                        <option value="100031">FCMB Easy Account</option>
                                        <option value="100032">Contec Global Infotech</option>
                                        <option value="100033">Palmpay</option>
                                        <option value="100052">Access Yello and Beta</option>
                                        <option value="110001">Payattitude Online</option>
                                        <option value="110002">Flutterwave Tech Solutions</option>
                                        <option value="110003">Interswitch</option>
                                        <option value="110004">First Apple</option>
                                        <option value="110005">3Line</option>
                                        <option value="110006">Paystack Payments</option>
                                        <option value="110007">TeamApt</option>
                                        <option value="400001">FSDH Merchant Bank</option>
                                        <option value="090270">Ab MFB</option>
                                        <option value="070010">Abbey Mortgage Bank</option>
                                        <option value="090260">Above Only MFB</option>
                                        <option value="090197">Abu MFB</option>
                                        <option value="000014" selected>Access Bank Nigeria</option>
                                        <option value="090134">Accion MFB</option>
                                        <option value="090160">Addosser MFB</option>
                                        <option value="090268">Adeyemi College Staff MFB</option>
                                        <option value="090292">Afekhafe MFB</option>
                                        <option value="090133">Al-Barkah MFB</option>
                                        <option value="090277">Al-Hayat MFB</option>
                                        <option value="090259">Alekun MFB</option>
                                        <option value="090297">Alert MFB</option>
                                        <option value="090131">Allworkers MFB</option>
                                        <option value="090169">Alpha Kapital MFB</option>
                                        <option value="090180">Amju Unique MFB</option>
                                        <option value="090116">AMML MFB</option>
                                        <option value="090143">Apeks MFB</option>
                                        <option value="090282">Arise MFB</option>
                                        <option value="090001">Aso Savings And Loans</option>
                                        <option value="090287">Assetmatrix MFB</option>
                                        <option value="090172">Astrapolaris MFB</option>
                                        <option value="090264">Auchi MFB</option>
                                        <option value="090188">Baines Credit MFB</option>
                                        <option value="090326">Balogun Gambari MFB</option>
                                        <option value="090136">Baobab MFB</option>
                                        <option value="090127">BC Kash MFB</option>
                                        <option value="090117">Boctrust MFB</option>
                                        <option value="090176">Bosak MFB</option>
                                        <option value="090148">Bowen MFB</option>
                                        <option value="070015">Brent Mortgage Bank</option>
                                        <option value="090293">Brethren MFB</option>
                                        <option value="090308">Brightway MFB</option>
                                        <option value="090154">CEMCS MFB</option>
                                        <option value="090141">Chikum MFB</option>
                                        <option value="090144">CIT MFB</option>
                                        <option value="000009">Citibank Nigeria</option>
                                        <option value="090130">Consumer MFB</option>
                                        <option value="060001">Coronation Merchant Bank</option>
                                        <option value="070006">Covenant</option>
                                        <option value="090159">Credit Afrique MFB</option>
                                        <option value="090167">Daylight MFB</option>
                                        <option value="090156">E-Barcs MFB</option>
                                        <option value="090294">Eagle Flight MFB</option>
                                        <option value="000010">Ecobank Bank</option>
                                        <option value="090310">EdFin MFB</option>
                                        <option value="090097">Ekondo MFB</option>
                                        <option value="090273">Emeralds MFB</option>
                                        <option value="090114">Empire Trust MFB</option>
                                        <option value="090189">Esan MFB</option>
                                        <option value="090166">Eso-E MFB</option>
                                        <option value="090304">Evangel MFB</option>
                                        <option value="090179">Fast MFB</option>
                                        <option value="060002">FBNQuest Merchant Bank</option>
                                        <option value="090290">FCT MFB</option>
                                        <option value="090318">Federal University Dutse MFB</option>
                                        <option value="090298">Fedpoly Nasarawa MFB</option>
                                        <option value="090153">FFS Microfinance</option>
                                        <option value="000007">Fidelity Bank</option>
                                        <option value="090126">Fidfund MFB</option>
                                        <option value="090111">Finatrust MFB</option>
                                        <option value="000016">First Bank of Nigeria</option>
                                        <option value="000003">First City Monument Bank</option>
                                        <option value="070014">First Gen Mortgage Bank</option>
                                        <option value="090163">First Multiple MFB</option>
                                        <option value="090285">First Option MFB</option>
                                        <option value="090164">First Royal MFB</option>
                                        <option value="090005">First Trust Mortgage Bank</option>
                                        <option value="090107">First Trust Mortgage Bank</option>
                                        <option value="070002">Fortis MFB</option>
                                        <option value="090145">Full Range MFB</option>
                                        <option value="090158">Futo MFB</option>
                                        <option value="090168">Gashua MFB</option>
                                        <option value="070009">Gateway Mortgage Bank</option>
                                        <option value="000027">Globus Bank</option>
                                        <option value="090278">Glory MFB</option>
                                        <option value="090122">Gowans MFB</option>
                                        <option value="090178">Greenbank MFB</option>
                                        <option value="090269">Greenville MFB</option>
                                        <option value="090195">Grooming MFB</option>
                                        <option value="000013">Guaranty Trust Bank</option>
                                        <option value="090147">Hackman MFB</option>
                                        <option value="070017">Haggai Mortgage Bank</option>
                                        <option value="090291">Hala MFB</option>
                                        <option value="090121">Hasal MFB</option>
                                        <option value="000020">Heritage Bank</option>
                                        <option value="090118">Ibile MFB</option>
                                        <option value="090275">Ikire MFB</option>
                                        <option value="090279">Ikire MFB</option>
                                        <option value="090258">Imo State MFB</option>
                                        <option value="090157">Infinity MFB</option>
                                        <option value="070016">Infinity Trust Mortgage Bank</option>
                                        <option value="090149">IRL MFB</option>
                                        <option value="090211">Itex Integrated Services</option>
                                        <option value="000006">Jaiz Bank</option>
                                        <option value="090003">Jubilee Life</option>
                                        <option value="090320">Kadpoly MFB</option>
                                        <option value="090191">KCMB MFB</option>
                                        <option value="000002">Keystone Bank</option>
                                        <option value="090299">Kontagora MFB</option>
                                        <option value="090380">Kredi Bank</option>
                                        <option value="090267">Kuda MFB</option>
                                        <option value="090155">La Fayette MFB</option>
                                        <option value="070012">Lagos Building Investment Company</option>
                                        <option value="090177">LAPO MFB</option>
                                        <option value="090271">Lavender MFB</option>
                                        <option value="090265">Lovonus MFB</option>
                                        <option value="090171">Mainstreet MFB</option>
                                        <option value="090174">Malachy MFB</option>
                                        <option value="090280">Megapraise MFB</option>
                                        <option value="090113">Microvis MFB</option>
                                        <option value="090192">Midland MFB</option>
                                        <option value="090281">Mint-Finex MFB</option>
                                        <option value="090129">Money Trust MFB</option>
                                        <option value="090190">Mutual Benefits Mifb</option>
                                        <option value="090151">Mutual Trust MFB</option>
                                        <option value="090152">Nargata MFB</option>
                                        <option value="090128">Ndiorah MFB</option>
                                        <option value="090205">New Dawn MFB</option>
                                        <option value="090108">New Prudential Bank</option>
                                        <option value="090263">Nigerian Navy MFB</option>
                                        <option value="070001">Nigerian Police Force MFB</option>
                                        <option value="090194">Nirsal MFB</option>
                                        <option value="090283">Nnew Women MFB</option>
                                        <option value="060003">Nova Merchant Bank</option>
                                        <option value="090119">Ohafia MFB</option>
                                        <option value="090161">Okpoga MFB</option>
                                        <option value="090272">Olabisi Onabanjo University MFB</option>
                                        <option value="090295">Omiye MFB</option>
                                        <option value="070007">Omoluabi Mortgage Bank</option>
                                        <option value="070008">Page Financials</option>
                                        <option value="090004">Parallex MFB</option>
                                        <option value="090317">Patrickgold MFB</option>
                                        <option value="090137">Pecantrust MFB</option>
                                        <option value="090196">Pennywise MFB</option>
                                        <option value="090135">Personal Trust MFB</option>
                                        <option value="090165">Petra MFB</option>
                                        <option value="090289">Pillar MFB</option>
                                        <option value="070013">Platinum Mortgage Bank</option>
                                        <option value="000008">Polaris Bank</option>
                                        <option value="090296">Polyuwana MFB</option>
                                        <option value="090274">Prestige MFB</option>
                                        <option value="000023">Providus Bank</option>
                                        <option value="090303">Purplemoney MFB</option>
                                        <option value="090261">Quickfund MFB</option>
                                        <option value="090170">Rahama MFB</option>
                                        <option value="000024">Rand Merchant Bank</option>
                                        <option value="070011">Refuge Mortgage Bank</option>
                                        <option value="090125">Regent MFB</option>
                                        <option value="090173">Reliance MFB</option>
                                        <option value="090198">Renmoney MFB</option>
                                        <option value="090132">Richway MFB</option>
                                        <option value="090138">Royal Exchange MFB</option>
                                        <option value="090175">Rubies MFB</option>
                                        <option value="090286">Safe Haven MFB</option>
                                        <option value="090006">Safetrust Mortgage Bank</option>
                                        <option value="090140">Sagamu MFB</option>
                                        <option value="090112">Seed Capital MFB</option>
                                        <option value="090325">Sparkle Bank</option>
                                        <option value="000012">Stanbic IBTC</option>
                                        <option value="000021">Standard Chartered Bank</option>
                                        <option value="090162">Stanford MFB</option>
                                        <option value="090262">Stellas MFB</option>
                                        <option value="000001">Sterling Bank</option>
                                        <option value="090305">Sulspap MFB</option>
                                        <option value="000022">Suntrust Bank</option>
                                        <option value="000026">Taj Bank</option>
                                        <option value="090115">TCF MFB</option>
                                        <option value="000025">Titan Trust Bank</option>
                                        <option value="090146">Trident MFB</option>
                                        <option value="090123">Trustbanc J6 MFB</option>
                                        <option value="090276">Trustfund MFB</option>
                                        <option value="090266">UNIBEN MFB</option>
                                        <option value="090193">UNICAL MFB</option>
                                        <option value="000018">Union Bank of Nigeria</option>
                                        <option value="000004">United Bank for Africa</option>
                                        <option value="000011">Unity Bank</option>
                                        <option value="090251">Unn MFB</option>
                                        <option value="090110">VFD MFB</option>
                                        <option value="090150">Virtue MFB</option>
                                        <option value="090139">Visa MFB</option>
                                        <option value="000017">Wema Bank</option>
                                        <option value="090120">Wetland MFB</option>
                                        <option value="090124">Xslnce MFB</option>
                                        <option value="090142">Yes MFB</option>
                                        <option value="000015">Zenith Bank</option>
                                    </select>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>


                                <div class="form-group ">
                                    <label for="amount">Account Number</label>
                                    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                    <input type="number" name="transferAccountNumber" id="transferAccountNumber"
                                        maxlength="10" placeholder="Enter Account Number" class="form-control"
                                        oninput="verifyBankAccount()" required>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>

                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="display: none;" id="myBankLoader">

                                    <div class="d-flex mb-4"
                                        style="flex-direction: row; justify-content:flex-center; align-items: center;">
                                        <div class="spinner-border text-success loader-xs inline-block "
                                            style="height: 20px; width: 20px;"></div>&nbsp;
                                        <p style="margin-top: 10px;">Getting Account Details</p>

                                    </div>
                                </div>



                                <div class="form-group mb-4">
                                    <label for="amount">Account Name</label>
                                    <input type="text" name="transferAccountName" id="transferAccountName"
                                        placeholder="Account Name" class="form-control" style="color: #555555;"
                                        readonly>
                                    <input type="hidden" name="bankName" id="bankName">

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="n-chk">
                                    <label
                                        class="new-control new-checkbox primary new-checkbox-rounded new-checkbox-text">
                                        <input type="checkbox" id="tagCustomerCheck" onchange="calc()"
                                            class="new-control-input">
                                        <span class="new-control-indicator"></span><span class="new-chk-content"> Tag a
                                            Customer</span>
                                    </label>
                                </div>

                                <div class="form-group mb-4" id="tagCustomerDiv" style="display: none;">

                                    <label for="transferBankCode">Select Customer</label>

                                    <select class="form-control js-data-example-ajax" width="100%" id="selectCustomer"
                                        name="selectCustomer"></select>
                                </div>

                                <div class="form-group ">
                                    <label for="transferAmount">Amount</label>
                                    <input id="transferAmount" type="number" name="transferAmount"
                                        placeholder="Enter Amount" class="form-control" required>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                                <div class="form-group mb-4">
                                    <label for="amount">Description</label>
                                    <textarea class="form-control" placeholder="Enter Description"
                                        id="transferDescription" name="transferDescription" rows="3"></textarea>
                                </div>

                                <span class="validation-text"></span>

                                <button type="button" class="btn btn-primary start" onclick="showBankTransferSummary()"
                                    id="initiateBankTransfer">
                                    <i class="icon-upload icon-large"></i>
                                    <span>Continue</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="icon-mobile-money" role="tabpanel">
                        <div class="container">
                            <form id="bank_transfer_form">

                                <div class="alert alert-success mb-4" id="statusMessageDiv" style="display: none;"
                                    role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    <strong>
                                        <span id="statusMessage"></span>
                                    </strong>
                                    </button>

                                </div>

                                @if ($message = Session::get('success'))
                                <div class="alert alert-success mb-4" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x close" data-dismiss="alert">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg></button>
                                    <strong> {{$message}}.</button>
                                </div>
                                @endif

                                <div class="form-group">
                                    <label for="mobileMoneyPaymentMethod">Select Country</label>

                                    <select class="form-control basic" id="mobileMoneyCountry" name="mobileMoneyCountry"
                                        onchange="getCountryPaymentMethod()">

                                        <option value="">::Select Country::</option>
                                        <option value="GHS">Ghana</option>
                                        <option value="KES">Kenya</option>
                                        <option value="RWF">Rwanda</option>
                                        <option value="TZS">Tanzania</option>
                                        <option value="UGX">Uganda</option>
                                        <option value="ZMW">Zambia</option>

                                    </select>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group" id="networkProvidersDiv">
                                    <label for="mobileMoneyPaymentMethod">Select Network Provider</label>

                                    <select class="form-control basic" id="mobileMoneyPaymentMethod"
                                        name="mobileMoneyPaymentMethod" required>
                                    </select>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>


                                <div class="form-group ">
                                    <label for="mobileMoneyPhone">Phone Number</label>
                                    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="phoneNumberPrefix"></span>
                                        </div>
                                        <input type="number" name="mobileMoneyPhone" id="mobileMoneyPhone"
                                            maxlength="10" placeholder="Enter Phone Number" class="form-control"
                                            oninput="verifyBankAccount()" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>

                                </div>


                                <div class="form-group mb-4">
                                    <label for="amount">Recipient Name</label>
                                    <input type="text" name="recipientName" id="recipientName"
                                        placeholder="Recipient Name" class="form-control" style="color: #555555;">
                                    <!-- <input type="hidden" name="bankName" id="bankName"> -->

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group ">
                                    <label for="mobileMoneyAmount" id="mobileMoneyAmountLabel">Amount</label>
                                    <input id="mobileMoneyAmount" type="number" oninput="mymobileMoneyAmount(event)" name="mobileMoneyAmount"
                                        placeholder="Enter Amount" class="form-control" required>
                                        <input type="hidden" name="mobileMOneyCurrentCompanyCurrency" id="mobileMOneyCurrentCompanyCurrency"
                                        value="{{Auth()->user()->company->currency->code}}" readonly>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <span class="validation-text"></span>
                                        </div>
                                    </div>
                                </div>

                                
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p id="mobileMoneyLoaderText"></p>
                                    </div>
                                </div>

                                <div class="form-group" id="mobileMoneyExchangeDiv" style="display:none">
                                    <label for="" class="col-sm-3 control-label" style="color:blue;"></label>
                                    <div class="col-sm-6">
                                        <div class="jumbotron jumbotron-fluid"
                                            style="padding-top: 8px; padding-bottom: 8px; margin-bottom: 10px">
                                            <div class="container">
                                                <h6><b>Exchange Rates</b></h6>
                                                <p><span id="mobileMoneyBaseCurrency"></span> <span id="mobileMoneyBaseAmount"></span> =
                                                    <span id="mobileMoneyExchangeCurrency"></span> <span
                                                        id="mobileMoneyExchangeAmount"></span> at
                                                    <span id="mobileMoneyExchangeRate"></span> rate<br>
                                                    <b>Charge:</b> {{Auth()->user()->company->currency->code}} <span
                                                        id="mobileMoneyExchangeCharger"></span><br>
                                                    <b>Total:</b> {{Auth()->user()->company->currency->code}} <span
                                                        id="mobileMoneyFinalAmountTxt"></span>
                                                </p>
                                                <p style="color: red; font-size: 18px" id="mobileMoneyBalanceStatus"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <label for="" class="col-sm-3 control-label"></label>
                                </div>


                                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                                <div class="form-group mb-4">
                                    <label for="mobileMoneyDescription">Description</label>
                                    <textarea class="form-control" placeholder="Enter Description"
                                        id="mobileMoneyDescription" name="mobileMoneyDescription" rows="3"></textarea>
                                </div>

                                <button type="button" class="btn btn-primary start" onclick="showMobileMoneyTransactionSummary()"
                                    id="initiateMobileMoneyTransfer">
                                    <i class="icon-upload icon-large"></i>
                                    <span>Continue</span>
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>


        </div>
    </div>

</div>

</div>
@include('inc.scripts')