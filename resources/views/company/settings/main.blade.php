@extends('layouts.app')

@section('content')
    <div class="layout-px-spacing">
        <div class="account-settings-container layout-top-spacing">
            <?php foreach ($company as $company_record);
            $logo = str_replace('public', 'storage', $company_record->logo);
            $licensecert = str_replace('public', 'storage', $company_record->license_certificate_image);
            $company_phone = $company_record->company_phone;
            $company_email = $company_record->company_email;
            if (count($company_settings) > 0) {
                foreach ($company_settings as $company_settings_record);
            }
            if (count($smsapi) > 0) {
                foreach ($smsapi as $smsapi_record);
            }
            $link1 = $link2 = $link3 = $link4 = '';
            if (count($social) > 0) {
                foreach ($social as $social_record) {
                    if ($social_record->name == 'linkedin') {
                        $link1 = $social_record->media_link;
                    }
                    if ($social_record->name == 'Twitter') {
                        $link2 = $social_record->media_link;
                    }
                    if ($social_record->name == 'Facebook') {
                        $link3 = $social_record->media_link;
                    }
                    if ($social_record->name == 'Youtube') {
                        $link4 = $social_record->media_link;
                    }
                }
            }
            $auth = auth()->user();
            ?>

            <form id='save-all' method='post' action='save' enctype="multipart/form-data" class="section general-info">
                @method('PUT')
                {{ csrf_field() }}
                <div class="scrollspy-example" data-spy="scroll" data-target="#account-settings-scroll" data-offset="-100">
                    <div id="tabsBordered" class="col-lg-12 col-12 layout-spacing">
                        <div class="statbox widget box box-shadow" style='padding-top:20px;'>
                            <div class="widget-header">
                                <div class="row">
                                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                                        <h4>Company Setting</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="widget-content widget-content-area vertical-border-pill">

                                <div class="row mb-4 mt-3">
                                    <div class="col-sm-3 col-12">
                                        <div class="nav flex-column nav-pills mb-sm-0 mb-5   text-center mx-auto"
                                            id="v-border-pills-tab" role="tablist" aria-orientation="vertical">
                                            <a class="nav-link active" id="v-border-pills-home-tab" data-toggle="pill"
                                                href="#v-border-pills-home" role="tab"
                                                aria-controls="v-border-pills-home" aria-selected="true"
                                                style='padding:5px'>General</a>
                                            <a class="nav-link  text-center" id="v-border-pills-profile-tab"
                                                data-toggle="pill" href="#v-border-pills-profile" role="tab"
                                                aria-controls="v-border-pills-profile" aria-selected="false"
                                                style='padding:5px'>Advance</a>
                                            <a class="nav-link  text-center" id="v-border-pills-transaction-tab"
                                                data-toggle="pill" href="#v-border-pills-transaction" role="tab"
                                                aria-controls="v-border-pills-transaction" aria-selected="false"
                                                style='padding:5px'>Transaction</a>
                                            <a class="nav-link  text-center" id="v-border-pills-messages-tab"
                                                data-toggle="pill" href="#v-border-pills-messages" role="tab"
                                                aria-controls="v-border-pills-messages" aria-selected="false"
                                                style='padding:5px'>SMS</a>
                                            <a class="nav-link  text-center" id="v-border-pills-settings-tab"
                                                data-toggle="pill" href="#v-border-pills-settings" role="tab"
                                                aria-controls="v-border-pills-settings" aria-selected="false"
                                                style='padding:5px'>Social</a>
                                            <a class="nav-link  text-center" id="v-border-pills-domain-tab"
                                                data-toggle="pill" href="#v-border-pills-domain" role="tab"
                                                aria-controls="v-border-pills-domain" aria-selected="false"
                                                style='padding:5px'>Customize Domain</a>
                                            <a class="nav-link  text-center" id="v-border-pills-settlement-tab"
                                                data-toggle="pill" href="#v-border-pills-settlement" role="tab"
                                                aria-controls="v-border-pills-settlement" aria-selected="false"
                                                style='padding:5px'>Settlement</a>
                                            <a class="nav-link  text-center" id="v-border-pills-developers-tab"
                                                data-toggle="pill" href="#v-border-pills-developers" role="tab"
                                                aria-controls="v-border-pills-developers" aria-selected="false"
                                                style='padding:5px'>Developer Settings</a>
                                            <a class="nav-link  text-center" id="v-border-pills-security-tab"
                                                data-toggle="pill" href="#v-border-pills-security" role="tab"
                                                aria-controls="v-border-pills-security" aria-selected="false"
                                                style='padding:5px'>Security</a>
                                            <a class="nav-link  text-center" id="v-border-pills-remitta-tab"
                                                data-toggle="pill" href="#v-border-pills-remitta" role="tab"
                                                aria-controls="v-border-pills-remitta" aria-selected="false"
                                                style='padding:5px'>Remitta</a>
                                            <a class="nav-link  text-center" id="v-border-pills-email-tab"
                                                data-toggle="pill" href="#v-border-pills-email" role="tab"
                                                aria-controls="v-border-pills-email" aria-selected="false"
                                                style='padding:5px'>Email Configuration</a>
                                            <a class="nav-link  text-center" id="v-border-pills-referral-tab"
                                                data-toggle="pill" href="#v-border-pills-referral" role="tab"
                                                aria-controls="v-border-pills-referral" aria-selected="false"
                                                style='padding:5px'>Referral Setting</a>
                                            {{-- <a class="nav-link  text-center" id="v-border-pills-transfer-tab" data-toggle="pill" href="#v-border-pills-transfer" role="tab" aria-controls="v-border-pills-transfer" aria-selected="false" style='padding:5px'>Transfer</a> --}}

                                        </div>
                                    </div>

                                    <div class="col-sm-9 col-12">
                                        <div class="tab-content" id="v-border-pills-tabContent">
                                            <div class="tab-pane fade show active" id="v-border-pills-home"
                                                role="tabpanel" aria-labelledby="v-border-pills-home-tab">
                                                <h4 class="mb-4"> Set up company Settings!</h4>

                                                <div class="row">
                                                    <div class="col-sm-6 col-xl-12 col-lg-12 col-md-12 mx-auto">
                                                        <div class="row">
                                                            <div class="col-sm-6 col-xl-2 col-lg-3 col-md-3">
                                                                <div class="upload mt-4 pr-md-4">
                                                                    <input type="file" id="input-file-max-fs"
                                                                        name='logo' class="dropify"
                                                                        data-default-file="{{ asset($logo) }}"
                                                                        data-max-file-size="2M" />
                                                                    <p class="mt-2"><i
                                                                            class="flaticon-cloud-upload mr-1"></i> Upload
                                                                        Picture</p>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6 col-xl-10 col-lg-9 col-md-9 mt-md-0 mt-4">
                                                                <div class="form">
                                                                    <div class="row">
                                                                        <div class="col-sm-6">
                                                                            <div class="form-group">
                                                                                <label for="company_name">Company
                                                                                    Name</label>
                                                                                <input type="text"
                                                                                    class="form-control mb-4"
                                                                                    value='{{ $company_record->company_name }}'
                                                                                    name="company_name" readonly
                                                                                    id="company_name"
                                                                                    placeholder="Wired Banking">
                                                                            </div>

                                                                            <span class='validation-text'></span>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <div class="form-group">
                                                                                <label for="company_short_name">Company
                                                                                    Short Name</label>
                                                                                <input type="text"
                                                                                    class="form-control mb-4"
                                                                                    value='{{ $company_record->short_name }}'
                                                                                    readonly name="company_short_name"
                                                                                    id="company_short_name"
                                                                                    placeholder="WB">
                                                                                <span class='validation-text'></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-sm-6">
                                                                            <div class="form-group">
                                                                                <label for="license_no">License No</label>
                                                                                <input type="text"
                                                                                    class="form-control mb-4" readonly
                                                                                    value='{{ $company_record->license_no }}'
                                                                                    name="license_no" id="license_no"
                                                                                    placeholder="1234...">
                                                                                <span class='validation-text'></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <div class="form-group">
                                                                                <label for="company_name">CAC No.</label>
                                                                                <input type="text"
                                                                                    class="form-control mb-4" readonly
                                                                                    id="cac_no"
                                                                                    value='{{ $company_record->cac_no }}'
                                                                                    name='cac_no' placeholder="1234...">
                                                                                <span class='validation-text'></span>
                                                                            </div>

                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-sm-6">
                                                                            <div class="form-group">
                                                                                <label for="companycurrency">
                                                                                    Currency</label>
                                                                                <select name="companycurrency"
                                                                                    id="companycurrency" disabled
                                                                                    class="form-control @error('companycurrency') is-invalid @enderror">
                                                                                    <option value=null>Choose Currency
                                                                                    </option>
                                                                                    @foreach ($currency as $currency2)
                                                                                        @if ($company_record->currency_id == $currency2->id)
                                                                                            <option
                                                                                                value='{{ $currency2->id }}'
                                                                                                selected>
                                                                                                {{ $currency2->code }}
                                                                                            </option>
                                                                                        @else
                                                                                            <option
                                                                                                value='{{ $currency2->id }}'>
                                                                                                {{ $currency2->code }}
                                                                                            </option>
                                                                                        @endif
                                                                                    @endforeach
                                                                                </select>
                                                                                <span class='validation-text'></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <div class="form-group">
                                                                                <div id="companycountry-field"
                                                                                    class="field-wrapper ">
                                                                                    <label
                                                                                        for="companycountry">Country</label>
                                                                                    <select name="companycountry"
                                                                                        id="companycountry" readonly
                                                                                        class="form-control @error('companycountry') is-invalid @enderror">
                                                                                        <option value="">Choose
                                                                                            Country</option>
                                                                                        @foreach ($country as $country2)
                                                                                            @if ($company_record->countries_id == $country2->id)
                                                                                                <option
                                                                                                    value='{{ $country2->id }}'
                                                                                                    selected>
                                                                                                    {{ $country2->name }}
                                                                                                </option>
                                                                                            @else
                                                                                                <option
                                                                                                    value='{{ $country2->id }}'>
                                                                                                    {{ $country2->name }}
                                                                                                </option>
                                                                                            @endif
                                                                                        @endforeach
                                                                                    </select>

                                                                                </div>
                                                                                <span class='validation-text'></span>
                                                                            </div>

                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <div class="form-group">
                                                                                <div id="companyaddress-field"
                                                                                    class="field-wrapper ">
                                                                                    <label
                                                                                        for="companyaddress">Address</label>
                                                                                    <textarea name="companyaddress" id="companyaddress"
                                                                                        class="form-control @error('companyaddress') is-invalid @enderror" placeholder="Office Address">{{ $company_record->address }}</textarea>

                                                                                </div>
                                                                                <span class='validation-text'></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <div class="form-group">
                                                                                @if ($licensecert == null)
                                                                                    <label>Enter Certificate
                                                                                        Document</label><br>
                                                                                    <input type='file' name='cert'
                                                                                        title='Enter Certificate'
                                                                                        placeholder="Enter Certificate">
                                                                                @else
                                                                                    <a class="btn btn-warning"
                                                                                        href='{{ $licensecert == null ? '#' : asset($licensecert) }}'>Download
                                                                                        License Certificate</a>


                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        @if (count($company_settings) > 0)

                                                                        @endif
                                                                        <div class="container" style="margin:0 auto;">
                                                                            <div id="pricingWrapper">

                                                                                <div class="card stacked mt-5">
                                                                                    <div class="card-header pt-0">
                                                                                        <span
                                                                                            class="card-price">${{ @$company_settings_record->company->sub->plan->plan_amount }}</span>
                                                                                        <h3 class="card-title mt-3 mb-1">
                                                                                            {{ @$company_settings_record->company->sub->plan->plan_name }}
                                                                                        </h3>
                                                                                        <!-- <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</p> -->
                                                                                    </div>
                                                                                    <div class="card-body">
                                                                                        <ul
                                                                                            class="list-group list-group-minimal mb-3">
                                                                                            <li
                                                                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                <strong>{{ @$company_settings_record->company->sub->plan->duration > 1 ? @$company_settings_record->plan->duration . ' ' . @$company_settings_record->plan->period . 's' : @$company_settings_record->plan->duration . ' ' . @$company_settings_record->plan->period }}</strong>
                                                                                            </li>
                                                                                            <li
                                                                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                Max Customers :
                                                                                                {{ @$company_settings_record->company->sub->plan->max_customers }}
                                                                                            </li>
                                                                                            <li
                                                                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                Max Staff :
                                                                                                {{ @$company_settings_record->company->sub->plan->max_staff }}
                                                                                            </li>
                                                                                            <li
                                                                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                Max Branch :
                                                                                                {{ @$company_settings_record->company->sub->plan->max_branch }}
                                                                                            </li>

                                                                                        </ul>
                                                                                        <a href="/company/{{ $auth->company->short_name }}/plan"
                                                                                            class="btn btn-block btn-primary">Check
                                                                                            Subscription</a>
                                                                                    </div>
                                                                                </div>

                                                                            </div>


                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>


                                            </div>
                                            <div class="tab-pane fade" id="v-border-pills-profile" role="tabpanel"
                                                aria-labelledby="v-border-pills-profile-tab">

                                                <div class="media">
                                                    <!-- <img class="mr-3 rounded-circle" src="{{-- asset('storage/img/90x90.jpg') --}}" -->
                                                    <!-- alt="Generic placeholder image"> -->
                                                    <div class="media-body">
                                                        <h5 class="mt-0 mb-3">Advance Settings</h5>
                                                        <div class="info">

                                                            <div class="row">
                                                                <div class="col-md-11 mx-auto">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="teller_option">Teller
                                                                                    Option</label>
                                                                                <select class="form-control"
                                                                                    name="teller_option"
                                                                                    id="teller_option">
                                                                                    @foreach ($teller_option as $teller_options)
                                                                                        @if (count($company_settings) > 0)
                                                                                            <option
                                                                                                value='{{ $teller_options->id }}'
                                                                                                {{ @$company_settings_record->teller_option_id == $teller_options->id ? 'selected' : '' }}>
                                                                                                {{ $teller_options->teller_option }}
                                                                                            </option>
                                                                                        @else
                                                                                            <option
                                                                                                value='{{ $teller_options->id }}'>
                                                                                                {{ $teller_options->teller_option }}
                                                                                            </option>
                                                                                        @endif

                                                                                        <!--  -->
                                                                                    @endforeach

                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="teller_commission">Teller
                                                                                    Commission(%)</label>
                                                                                <input type="text"
                                                                                    class="form-control mb-4"
                                                                                    value="{{ count($company_settings) > 0 ? @$company_settings_record->teller_commission : '' }}"
                                                                                    name="teller_commission"
                                                                                    id="teller_commission"
                                                                                    placeholder="Teller Commission">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="company_type">Company
                                                                                    Type</label>
                                                                                <select class="form-control"
                                                                                    name='company_type' id="company_type">


                                                                                    @foreach ($cooperative as $datarecords)
                                                                                        @if (count($company_settings) > 0)
                                                                                            <option
                                                                                                value='{{ $datarecords->id }}'
                                                                                                {{ @$company_settings_record->company_type_id == $datarecords->id ? 'selected' : '' }}>
                                                                                                {{ $datarecords->name }}
                                                                                            </option>
                                                                                        @else
                                                                                            <option
                                                                                                value='{{ $datarecords->id }}'>
                                                                                                {{ $datarecords->name }}
                                                                                            </option>
                                                                                        @endif
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="esusu_type">Esusu Type</label>

                                                                                <select class="form-control"
                                                                                    name='esusu_type' id="esusu_type">


                                                                                    @foreach ($esusu_types as $datarecords)
                                                                                        @if (count($company_settings) > 0)
                                                                                            <option
                                                                                                value='{{ $datarecords['id'] }}'
                                                                                                {{ @$company_settings_record->esusu_type_id == $datarecords['id'] ? 'selected' : '' }}>
                                                                                                {{ $datarecords['esusu_type'] }}
                                                                                            </option>
                                                                                        @else
                                                                                            <option
                                                                                                value='{{ $datarecords['id'] }}'>
                                                                                                {{ $datarecords['esusu_type'] }}
                                                                                            </option>
                                                                                        @endif
                                                                                    @endforeach

                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">

                                                                                <label for="auto_renewal">Auto Renew
                                                                                    Plan</label>
                                                                                <select class="form-control"
                                                                                    name='auto_renewal' id="auto_renewal">


                                                                                    @foreach ($auto_renewal as $datarecords)
                                                                                        @if (count($company_settings) > 0)
                                                                                            <option
                                                                                                value='{{ $datarecords->id }}'
                                                                                                {{ @$company_settings_record->auto_renewal_id == $datarecords->id ? 'selected' : '' }}>
                                                                                                {{ $datarecords->auto_renewal }}
                                                                                            </option>
                                                                                        @else
                                                                                            <option
                                                                                                value='{{ $datarecords->id }}'>
                                                                                                {{ $datarecords->auto_renewal }}
                                                                                            </option>
                                                                                        @endif
                                                                                    @endforeach

                                                                                </select>
                                                                            </div>
                                                                        </div>




                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="registration_fee">Registration
                                                                                    Fee</label>
                                                                                <input type="number"
                                                                                    class="form-control mb-4"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->registration_fee : '' }}'
                                                                                    name="registration_fee"
                                                                                    id="registration_fee"
                                                                                    placeholder="Registration Amount">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="back_date_status">Back Date
                                                                                    Status</label>
                                                                                <select class="form-control"
                                                                                    name='back_date_status'
                                                                                    id="back_date_status">


                                                                                    @foreach ($back_date_status as $datarecords)
                                                                                        @if (count($company_settings) > 0)
                                                                                            <option
                                                                                                value='{{ $datarecords->id }}'
                                                                                                {{ @$company_settings_record->back_date_status_id == $datarecords->id ? 'selected' : '' }}>
                                                                                                {{ $datarecords->back_date_status }}
                                                                                            </option>
                                                                                        @else
                                                                                            <option
                                                                                                value='{{ $datarecords->id }}'>
                                                                                                {{ $datarecords->back_date_status }}
                                                                                            </option>
                                                                                        @endif
                                                                                    @endforeach

                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="date_control">Alter txn
                                                                                    Date(D/W)</label>
                                                                                <select class="form-control"
                                                                                    name='date_control' id="date_control">

                                                                                    <option value='0'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->date_control == 0 ? 'selected' : '' }} @endif>
                                                                                        Off</option>


                                                                                    <option value='1'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->date_control == 1 ? 'selected' : '' }} @endif>
                                                                                        On</option>

                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label
                                                                                    for="internal_account_creation">Internal
                                                                                    Account Creation</label>
                                                                                <select class="form-control"
                                                                                    name='internal_account_creation'
                                                                                    id="internal_account_creation">

                                                                                    <option value='1'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->internal_account_creation == 1 ? 'selected' : '' }} @endif>
                                                                                        Enable</option>
                                                                                    <option value='0'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->internal_account_creation == 0 ? 'selected' : '' }} @endif>
                                                                                        Disable</option>

                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="account_approval">Account
                                                                                    Approval</label>
                                                                                <select class="form-control"
                                                                                    name='account_approval'
                                                                                    id="account_approval">

                                                                                    <option value='1'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->account_approval == 1 ? 'selected' : '' }} @endif>
                                                                                        Auto Approval</option>
                                                                                    <option value='0'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->account_approval == 0 ? 'selected' : '' }} @endif>
                                                                                        Manual Approval</option>

                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">

                                                                                <div class="form-group ">
                                                                                    <label for="preferred_bank">Select
                                                                                        Mobile App Account Opening
                                                                                        Bank</label>
                                                                                    <select name="preferred_bank"
                                                                                        id="preferred_bank"
                                                                                        class="form-control">

                                                                                        @foreach ($in_use_banks as $bank)
                                                                                            <option
                                                                                                value="{{ $bank->bank_code }}"
                                                                                                @if (count($company_settings) > 0) {{ @$company_settings_record->preferred_bank_id == $bank->id ? 'selected' : '' }} @endif>
                                                                                                {{ $bank->bank_name }}
                                                                                            </option>
                                                                                        @endforeach

                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label
                                                                                    for="preferred_virtual_card_currency">Preferred
                                                                                    Virtual Card Currency</label>
                                                                                <select class="form-control"
                                                                                    name='preferred_virtual_card_currency'
                                                                                    id="preferred_virtual_card_currency">

                                                                                    <option value='1'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->preferred_virtual_card_currency == 1 ? 'selected' : '' }} @endif>
                                                                                        NGN - Nigerian Naira</option>
                                                                                    <option value='3'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->preferred_virtual_card_currency == 3 ? 'selected' : '' }} @endif>
                                                                                        USD - US Dollars</option>

                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label
                                                                                    for="additional_virtual_card_funding_fee"
                                                                                    id="additional_virtual_card_funding_fee_label">Additional
                                                                                    Virtual Card Funding Fee</label>
                                                                                <input type="number" step="any"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->additional_virtual_card_funding_fee : '' }}'
                                                                                    class="form-control mb-4"
                                                                                    name="additional_virtual_card_funding_fee"
                                                                                    id="additional_virtual_card_funding_fee"
                                                                                    placeholder="Additional Virtual Card Funding Fee">
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">

                                                                                <div class="form-group ">
                                                                                    <label
                                                                                        for="enable_txn_created_by">Enable
                                                                                        TXN created by</label>
                                                                                    <select name="enable_txn_created_by"
                                                                                        id="enable_txn_created_by"
                                                                                        class="form-control">
                                                                                        <option value="0"
                                                                                            @if (count($company_settings) > 0) {{ @$company_settings_record->enable_txn_created_by == 0 ? 'selected' : '' }} @endif>
                                                                                            Disable</option>
                                                                                        <option value="1"
                                                                                            @if (count($company_settings) > 0) {{ @$company_settings_record->enable_txn_created_by == 1 ? 'selected' : '' }} @endif>
                                                                                            Enable</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>


                                                                        <div class="col-md-6">
                                                                            <div class="form-group">

                                                                                <div class="form-group ">
                                                                                    <label for="restrict_customer">Restrict
                                                                                        Customer On Registration</label>
                                                                                    <select name="restrict_customer"
                                                                                        id="restrict_customer"
                                                                                        class="form-control">
                                                                                        <option value="0"
                                                                                            @if (count($company_settings) > 0) {{ @$company_settings_record->restrict_customers == 0 ? 'selected' : '' }} @endif>
                                                                                            Allow Customers</option>
                                                                                        <option value="1"
                                                                                            @if (count($company_settings) > 0) {{ @$company_settings_record->restrict_customers == 1 ? 'selected' : '' }} @endif>
                                                                                            Restrict Customers</option>

                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>


                                                                        <fieldset class="col-md-12">
                                                                            <legend>SMS Charge:</legend>

                                                                            <div class='row'>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="sms_charge_frequencie_id">SMS
                                                                                            Charge Frequency</label>
                                                                                        <select class="form-control"
                                                                                            name='sms_charge_frequencie_id'
                                                                                            id="sms_charge_frequencie_id">


                                                                                            @foreach ($charge_customer_status as $datarecords)
                                                                                                @if (count($company_settings) > 0)
                                                                                                    <option
                                                                                                        value='{{ $datarecords->id }}'
                                                                                                        {{ @$company_settings_record->sms_charge_frequencie_id == $datarecords->id ? 'selected' : '' }}>
                                                                                                        {{ $datarecords->charge_customer_status }}
                                                                                                    </option>
                                                                                                @else
                                                                                                    <option
                                                                                                        value='{{ $datarecords->id }}'>
                                                                                                        {{ $datarecords->charge_customer_status }}
                                                                                                    </option>
                                                                                                @endif
                                                                                            @endforeach

                                                                                        </select>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="sms_charge">SMS
                                                                                            Charge</label>
                                                                                        <input type="number"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->sms_charge : '' }}'
                                                                                            class="form-control mb-4"
                                                                                            name="sms_charge"
                                                                                            id="sms_charge"
                                                                                            placeholder="Enter SMS Charge">

                                                                                    </div>
                                                                                </div>


                                                                            </div>
                                                                        </fieldset>

                                                                        <fieldset class="col-md-12">
                                                                            <legend>POS Charge:</legend>

                                                                            <div class='row'>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="additional_pos_charge_percent">Additional
                                                                                            POS Charge Percentage</label>
                                                                                        <input type="number"
                                                                                            step="any"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->additional_pos_charge_percent * 100 : '' }}'
                                                                                            class="form-control mb-4"
                                                                                            name="additional_pos_charge_percent"
                                                                                            id="additional_pos_charge_percent"
                                                                                            placeholder="Additional POS Charge Percentage">
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="sms_charge">POS Charge
                                                                                            Capped at</label>
                                                                                        <input type="number"
                                                                                            step="any"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->pos_charge_capped_at : '' }}'
                                                                                            class="form-control mb-4"
                                                                                            name="pos_charge_capped_at"
                                                                                            id="pos_charge_capped_at"
                                                                                            placeholder="POS Charge Capped at">

                                                                                    </div>
                                                                                </div>


                                                                            </div>
                                                                        </fieldset>


                                                                        <fieldset>
                                                                            <legend>Whatsapp</legend>
                                                                            <div class='row'>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="whatsapp_control">Whatsapp
                                                                                            Control</label>
                                                                                        <select class="form-control"
                                                                                            name='whatsapp_control'
                                                                                            id="whatsapp_control" require>

                                                                                            <option value='1'
                                                                                                @if (count($company_settings) > 0) @if (@$company_settings_record->whatsapp_control == 1)selected @endif
                                                                                                @endif
                                                                                                >On</option>

                                                                                            <option value='2'
                                                                                                @if (count($company_settings) > 0) @if (@$company_settings_record->whatsapp_control == 2)selected @endif
                                                                                                @endif
                                                                                                >Off</option>

                                                                                        </select>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="whatsapp_charge">Whatsapp</label>
                                                                                        <input type="number"
                                                                                            class="form-control mb-4"
                                                                                            name="whatsapp_charge"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->whatsapp_charge : '' }}'
                                                                                            id="whatsapp_charge"
                                                                                            placeholder="whatsapp charge">

                                                                                    </div>
                                                                                </div>

                                                                            </div>
                                                                        </fieldset>

                                                                        <fieldset class="col-md-12">
                                                                            <legend>Bills Payment</legend>
                                                                            <div class='row'>
                                                                                <div class="col-md-12">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="bills_payment_charge">Bills
                                                                                            Payment Charge</label>
                                                                                        <input type="number"
                                                                                            class="form-control mb-4"
                                                                                            name="bills_payment_charge"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->bills_payment_charge : '0' }}'
                                                                                            id="bills_payment_charge"
                                                                                            placeholder="Bills payment Charge">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </fieldset>

                                                                        <fieldset class="col-md-12">
                                                                            <legend>Withdrawal Charge:</legend>

                                                                            <div class='row'>
                                                                                <div class="col-md-4">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="withdraw_charge_state">Withdraw
                                                                                            Charge Type</label>
                                                                                        <select class="form-control"
                                                                                            name='withdraw_charge_state'
                                                                                            id="withdraw_charge_state">
                                                                                            <option value='1'
                                                                                                @if (count($company_settings) > 0) @if (@$company_settings_record->withdraw_charge_state == 1)selected @endif
                                                                                                @endif
                                                                                                >Percentage</option>
                                                                                            <option value='0'
                                                                                                @if (count($company_settings) > 0) @if (@$company_settings_record->withdraw_charge_state == 0)selected @endif
                                                                                                @endif
                                                                                                >Flat</option>

                                                                                        </select>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-4"
                                                                                    id="minWithdrawalDiv">
                                                                                    <div class="form-group">
                                                                                        <label for="min_withdraw_charge"
                                                                                            id="min_withdraw_label">Min-Withdraw
                                                                                            Charge </label>
                                                                                        <input type="number"
                                                                                            step="any"
                                                                                            class="form-control mb-4"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->min_withdraw_charge : '' }}'
                                                                                            name="min_withdraw_charge"
                                                                                            id="min_withdraw_charge"
                                                                                            placeholder="Min Withdraw">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <div class="form-group">
                                                                                        <label for="max_withdraw_charge"
                                                                                            id="max_withdraw_label">Max-Withdraw
                                                                                            Charge </label>
                                                                                        <input type="number"
                                                                                            step="any"
                                                                                            class="form-control mb-4"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->max_withdraw_charge : '' }}'
                                                                                            name="max_withdraw_charge"
                                                                                            id="max_withdraw_charge"
                                                                                            placeholder="Max Withdraw">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </fieldset>
                                                                        <fieldset class="col-md-12">
                                                                            <legend>Outgoing Bank Transfer Charge:</legend>
                                                                            <div class='row'>
                                                                                <div class="col-md-4">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="bank_transfer_charge_state">Transfer
                                                                                            Charge type</label>
                                                                                        <select class="form-control"
                                                                                            name='bank_transfer_charge_state'
                                                                                            id="bank_transfer_charge_state">
                                                                                            <option value='1'
                                                                                                @if (count($company_settings) > 0) @if (@$company_settings_record->bank_transfer_charge_state == 1)selected @endif
                                                                                                @endif
                                                                                                >Percentage</option>
                                                                                            <option value='0'
                                                                                                @if (count($company_settings) > 0) @if (@$company_settings_record->bank_transfer_charge_state == 0)selected @endif
                                                                                                @endif
                                                                                                >Flat</option>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-4" id="minTransferDiv">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="min_bank_transfer_charge_or_percentage">Min.
                                                                                            Transfer Charge </label>
                                                                                        <input type="number"
                                                                                            step="any"
                                                                                            class="form-control mb-4"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->min_bank_transfer_charge_or_percentage : '' }}'
                                                                                            name="min_bank_transfer_charge_or_percentage"
                                                                                            id="min_bank_transfer_charge_or_percentage"
                                                                                            placeholder="Min Transfer Charge">

                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="max_bank_transfer_charge"
                                                                                            id="max_bank_transfer_label">Max-Withdraw
                                                                                            Charge </label>
                                                                                        <input type="number"
                                                                                            step="any"
                                                                                            class="form-control mb-4"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->max_bank_transfer_charge : '' }}'
                                                                                            name="max_bank_transfer_charge"
                                                                                            id="max_bank_transfer_charge"
                                                                                            placeholder="Max Bank transfer Charge">

                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                        </fieldset>

                                                                        <fieldset class="col-md-12">
                                                                            <legend>Incoming Transfer</legend>

                                                                            <div class='row'>
                                                                                <div class="col-md-4">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="who_to_pay_incoming_transfer_charge">Who
                                                                                            pays Incoming Transfer
                                                                                            Charge</label>
                                                                                        <select class="form-control"
                                                                                            name='who_to_pay_incoming_transfer_charge'
                                                                                            id="who_to_pay_incoming_transfer_charge">
                                                                                            <option value='0'
                                                                                                @if (count($company_settings) > 0) {{ @$company_settings_record->who_to_pay_incoming_transfer_charge == 0 ? 'selected' : '' }} @endif>
                                                                                                Company</option>

                                                                                            <option value='1'
                                                                                                @if (count($company_settings) > 0) {{ @$company_settings_record->who_to_pay_incoming_transfer_charge == 1 ? 'selected' : '' }} @endif>
                                                                                                Customer</option>

                                                                                        </select>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-4"
                                                                                    id="chargeIncomingUsingDiv">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="incoming_transfer_charge_state">Charge
                                                                                            Incoming transfer using</label>
                                                                                        <select class="form-control"
                                                                                            name='incoming_transfer_charge_state'
                                                                                            id="incoming_transfer_charge_state">

                                                                                            <option value='1'
                                                                                                @if (count($company_settings) > 0) @if (@$company_settings_record->incoming_transfer_charge_state == 1)selected @endif
                                                                                                @endif
                                                                                                >Percentage</option>
                                                                                            <option value='0'
                                                                                                @if (count($company_settings) > 0) @if (@$company_settings_record->incoming_transfer_charge_state == 0)selected @endif
                                                                                                @endif
                                                                                                >Flat</option>

                                                                                        </select>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-4"
                                                                                    id="incomingTransferChargeDiv">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="incoming_transfer_charge_amount">Incoming
                                                                                            Transfer Charge </label>
                                                                                        <input type="number"
                                                                                            step="0.01"
                                                                                            class="form-control mb-4"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->incoming_transfer_charge_amount : '' }}'
                                                                                            name="incoming_transfer_charge_amount"
                                                                                            id="incoming_transfer_charge_amount"
                                                                                            placeholder="Incoming Transfer Charge">

                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-4"
                                                                                    id="incomingTransferChargePercentageDiv">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="incoming_transfer_charge_percentage">Incoming
                                                                                            Transfer Percentage </label>
                                                                                        <input type="number"
                                                                                            step="0.01"
                                                                                            class="form-control mb-4"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->incoming_transfer_charge_percentage : '' }}'
                                                                                            name="incoming_transfer_charge_percentage"
                                                                                            id="incoming_transfer_charge_percentage"
                                                                                            placeholder="Enter Percentage">

                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-4"
                                                                                    id="incomingTransferCapDiv">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="incoming_transfer_charge_capped_at">Incoming
                                                                                            Transfer Capped at </label>
                                                                                        <input type="number"
                                                                                            step="0.01"
                                                                                            class="form-control mb-4"
                                                                                            value='{{ count($company_settings) > 0 ? @$company_settings_record->incoming_transfer_charge_capped_at : '' }}'
                                                                                            name="incoming_transfer_charge_capped_at"
                                                                                            id="incoming_transfer_charge_capped_at"
                                                                                            placeholder="Enter Maximum Charge">

                                                                                    </div>
                                                                                </div>


                                                                            </div>



                                                                        </fieldset>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="minimum_balance">Minimum
                                                                                    Customer Wallet Balance</label>
                                                                                <input type="number" step="any"
                                                                                    class="form-control mb-4"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->minimum_balance : '' }}'
                                                                                    name="minimum_balance"
                                                                                    id="minimum_balance"
                                                                                    placeholder="Minimum Customer Balance">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label
                                                                                    for="charge_customer_maintenance_id ">Maintainance
                                                                                    Charge (Option)</label>
                                                                                <select class="form-control"
                                                                                    name='charge_customer_maintenance_id'
                                                                                    id="charge_customer_maintenance_id ">


                                                                                    @foreach ($charge_customer_status as $datarecords)
                                                                                        @if (count($company_settings) > 0)
                                                                                            <option
                                                                                                value='{{ $datarecords->id }}'
                                                                                                {{ @$company_settings_record->charge_customer_maintenance_id == $datarecords->id ? 'selected' : '' }}>
                                                                                                {{ $datarecords->charge_customer_status }}
                                                                                            </option>
                                                                                        @else
                                                                                            <option
                                                                                                value='{{ $datarecords->id }}'>
                                                                                                {{ $datarecords->charge_customer_status }}
                                                                                            </option>
                                                                                        @endif
                                                                                    @endforeach

                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label
                                                                                    for="maintenance_change_amount">Maintenance
                                                                                    Charge Amount</label>
                                                                                <input type="number"
                                                                                    class="form-control mb-4"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->maintenance_change_amount : '' }}'
                                                                                    name="maintenance_change_amount"
                                                                                    id="maintenance_change_amount"
                                                                                    placeholder="Maintenance Amount">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="ussd_charge">ussd
                                                                                    Charge</label>
                                                                                <input type="number"
                                                                                    class="form-control mb-4"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->ussd_charge : '' }}'
                                                                                    name="ussd_charge" id="ussd_charge"
                                                                                    placeholder="USSD Charge">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="ussd_control">USSD Control
                                                                                    (Option)</label>
                                                                                <select class="form-control"
                                                                                    name='ussd_control' id="ussd_control">

                                                                                    <option value='1'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->ussd_control == 1 ? 'selected' : '' }} @endif>
                                                                                        Allow All</option>
                                                                                    <option value='2'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->ussd_control == 2 ? 'selected' : '' }} @endif>
                                                                                        Disable</option>
                                                                                    <option value='3'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->ussd_control == 3 ? 'selected' : '' }} @endif>
                                                                                        Allow only Balance Check</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="company_prefix">Virtual Account
                                                                                    Prefix</label>
                                                                                <select class="form-control"
                                                                                    name='company_prefix'
                                                                                    id="company_prefix">

                                                                                    <option value='1'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->company_prefix == 1 ? 'selected' : '' }} @endif>
                                                                                        On</option>
                                                                                    <option value='0'
                                                                                        @if (count($company_settings) > 0) {{ @$company_settings_record->company_prefix == 0 ? 'selected' : '' }} @endif>
                                                                                        Off</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="min_transfer_amount">Min
                                                                                    Outgoing Transfer Amount</label>
                                                                                <input type="number"
                                                                                    class="form-control mb-4"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->min_transfer_amount : '' }}'
                                                                                    name="min_transfer_amount"
                                                                                    id="min_transfer_amount"
                                                                                    placeholder="Min Transfer">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="max_localtransfer_amount">Max
                                                                                    Outgoing transfer Amount</label>
                                                                                <input type="number" step="any"
                                                                                    class="form-control mb-4"
                                                                                    name="max_localtransfer_amount"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->max_localtransfer_amount : '' }}'
                                                                                    id="max_localtransfer_amount"
                                                                                    placeholder="200">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="max_onlinetransfer_amount">Max
                                                                                    Online-Transfer Amount</label>
                                                                                <input type="number" step="any"
                                                                                    class="form-control mb-4"
                                                                                    name="max_onlinetransfer_amount"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->max_onlinetransfer_amount : '' }}'
                                                                                    id="max_onlinetransfer_amount"
                                                                                    placeholder="200">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="card_request_charge">Card
                                                                                    Request Charge</label>
                                                                                <input type="number" step="any"
                                                                                    class="form-control mb-4"
                                                                                    name="card_request_charge"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->new_physical_card_charge : '' }}'
                                                                                    id="card_request_charge"
                                                                                    placeholder="Enter Charge Amount">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="card_maintenance_fee">Card
                                                                                    Maintenance Fee</label>
                                                                                <input type="number"
                                                                                    class="form-control mb-4"
                                                                                    name="card_maintenance_fee"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->physical_card_maintenance_charge : '' }}'
                                                                                    id="card_maintenance_fee"
                                                                                    placeholder="Enter Maintenance Fee">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="card_maintenance_fee">Card
                                                                                    Withdrawal Fee</label>
                                                                                <input type="number"
                                                                                    class="form-control mb-4"
                                                                                    name="card_withdrawal_charge"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->physical_card_withdrawal_charge : '' }}'
                                                                                    id="card_withdrawal_charge"
                                                                                    placeholder="Enter withdrawal Fee">

                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label
                                                                                    for="daily_transfer_limit_count">Daily
                                                                                    Transfer Limit Count</label>
                                                                                <input type="number" step="any"
                                                                                    class="form-control mb-4"
                                                                                    name="daily_transfer_limit_count"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->daily_transfer_limit_count : '' }}'
                                                                                    id="daily_transfer_limit_count"
                                                                                    placeholder="200">

                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label
                                                                                    for="investment_termination_control">Investment
                                                                                    Termination Control</label>
                                                                                <select class="form-control"
                                                                                    name='investment_termination_control'
                                                                                    id="investment_termination_control">

                                                                                    <option value='1'
                                                                                        @if (count($company_settings) > 0) @if (@$company_settings_record->investment_termination_control == 1)selected @endif
                                                                                        @endif>Deduct
                                                                                        from Interest</option>

                                                                                    <option value='2'
                                                                                        @if (count($company_settings) > 0) @if (@$company_settings_record->investment_termination_control == 2)selected @endif
                                                                                        @endif>Deduct
                                                                                        from Capital</option>
                                                                                    <option value='3'
                                                                                        @if (count($company_settings) > 0) @if (@$company_settings_record->investment_termination_control == 3)selected @endif
                                                                                        @endif>Deduct
                                                                                        from Capital+Investment</option>



                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="daily_transfer_limit_count">DOM
                                                                                    Account duration</label>
                                                                                <input type="number"
                                                                                    class="form-control mb-4"
                                                                                    name="dom_account_duration"
                                                                                    value='{{ count($company_settings) > 0 ? @$company_settings_record->dom_account_duration : '' }}'
                                                                                    id="dom_account_duration"
                                                                                    placeholder="EG 30 for 30 days">

                                                                            </div>
                                                                        </div>


                                                                        <fieldset class="col-md-12">
                                                                            <legend>Birthday Notification</legend>

                                                                            <div class="form-group ">

                                                                                <label for="birthday_msg_channel">broadcast
                                                                                    option</label>
                                                                                <select name='birthday_msg_channel'
                                                                                    class='form-control'>

                                                                                    <option value='1'
                                                                                        @if (count($company_settings) > 0) @if (@$company_settings_record->birthday_msg_channel == 1)selected @endif
                                                                                        @endif>SMS &
                                                                                        Email</option>
                                                                                    <option value='2'
                                                                                        @if (count($company_settings) > 0) @if (@$company_settings_record->birthday_msg_channel == 2)selected @endif
                                                                                        @endif>SMS Only
                                                                                    </option>
                                                                                    <option value='3'
                                                                                        @if (count($company_settings) > 0) @if (@$company_settings_record->birthday_msg_channel == 3)selected @endif
                                                                                        @endif>Email
                                                                                        Only</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="form-group ">
                                                                                <label for="birthday_msg">Message</label>
                                                                                <textarea name='birthday_msg' rows="4" cols="150" maxlength="150" class='form-control'> @if (count($company_settings) > 0) {{ @$company_settings_record->birthday_msg }} @endif
</textarea>
                                                                            </div>
                                                                        </fieldset>
                                                                        <!-- s_m_s__setting_id   -->
                                                                    </div>
                                                                    <center>
                                                                        <div class="row">
                                                                            <div class="col-sm-6 col-md-2"></div>
                                                                            <div class="col-sm-6 col-md-4">
                                                                                <div class="form-group">
                                                                                    <label for="pin_verification">Enter
                                                                                        company Pin to comfirm
                                                                                        changes</label>
                                                                                    <input type="number" value=""
                                                                                        class="form-control mb-4"
                                                                                        name="pin_verification"
                                                                                        id="pin_verification"
                                                                                        placeholder="****" required>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-sm-6 col-md-2"></div>
                                                                        </div>
                                                                    </center>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="tab-pane fade" id="v-border-pills-transaction" role="tabpanel"
                                                aria-labelledby="v-border-pills-transaction-tab">
                                                <h5 class="">Transaction Settings</h5>
                                                <div class="row">
                                                    @include('company.settings.inc.transactionSetting')
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="v-border-pills-messages" role="tabpanel"
                                                aria-labelledby="v-border-pills-messages-tab">
                                                <div class="info">
                                                    <h5 class="">SMS Settings</h5>
                                                    <div class="row">
                                                        <div class="col-md-11 mx-auto">
                                                            <div class="btn-group" style="font-weight:bold">
                                                                Turn Off/On all SMS:
                                                                <label
                                                                    class="switch s-outline s-outline-success  mb-4 mr-2">
                                                                    <input type="checkbox" id='allsmsstate'>
                                                                    <span class="slider round"></span>
                                                                </label>
                                                            </div>

                                                        </div>
                                                        <div class="col-md-11 mx-auto">
                                                            <div class="btn-group row allowedsms">
                                                            </div>
                                                        </div>

                                                        <input type="hidden" id='current_smsid' name='current_smsid'
                                                            value='{{ count($smsapi) > 0 ? $smsapi_record->id : '' }}'>

                                                        <div class="col-md-11 mx-auto">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label for="smsmessage">Name</label>
                                                                        <input type="text" class="form-control"
                                                                            maxlength="10" id='smsname' name='smsname'
                                                                            value='{{ count($smsapi) > 0 ? $smsapi_record->name : '' }}'
                                                                            placeholder="Api Name" aria-label="name"
                                                                            aria-describedby="name">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label for="smsapiurl">SMS API URL</label>
                                                                        <textarea name='smsapiurl' id='smsapiurl' class='form-control' placeholder="Api URL">{{ count($smsapi) > 0 ? $smsapi_record->api_url : '' }}</textarea>
                                                                    </div>
                                                                </div>


                                                                <div class="col-md-11 mx-auto">
                                                                    <div class="row">
                                                                        <!-- <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="sms">Select SMS</label>
                                                                               <select>
                                                                                   <option>Select SMS</option>
                                                                                   <option>Type 1</option>
                                                                                   <option>Type 2</option>
                                                                                   <option>Type 3</option>
                                                                               </select>
                                                                            </div>
                                                                            </div> -->

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="sender">Sender</label>
                                                                                <input type="text" class="form-control"
                                                                                    id='sender_variable'
                                                                                    name='sender_variable'
                                                                                    value='{{ count($smsapi) > 0 ? $smsapi_record->sender_variable : '' }}'
                                                                                    placeholder="Sender Variable"
                                                                                    aria-label="Sender Variable"
                                                                                    aria-describedby="Sender Variable">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="smsmessage">SMS Message</label>
                                                                                <input type="text" class="form-control"
                                                                                    value='{{ count($smsapi) > 0 ? $smsapi_record->message_variable : '' }} '
                                                                                    id='smsmessage' name='smsmessage'
                                                                                    placeholder="Message Field"
                                                                                    aria-label="messagephone"
                                                                                    aria-describedby="messagephone">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="smsphone">SMS Phone</label>
                                                                                <input type="text" class="form-control"
                                                                                    value='{{ count($smsapi) > 0 ? $smsapi_record->phone_variable : '' }}'
                                                                                    id='smsphone' name='smsphone'
                                                                                    placeholder="Phone Field"
                                                                                    aria-label="smsphone"
                                                                                    aria-describedby="smsphone">
                                                                            </div>
                                                                        </div>



                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="smsmessage">Content
                                                                                    Type</label>
                                                                                <input type="text" class="form-control"
                                                                                    value='{{ count($smsapi) > 0 ? $smsapi_record->content_type : '' }} '
                                                                                    id='content_type' name='content_type'
                                                                                    placeholder="API Content Type"
                                                                                    aria-label="content_type"
                                                                                    aria-describedby="content_type">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="smsphone">Authorization</label>
                                                                                <input type="text" class="form-control"
                                                                                    value='{{ count($smsapi) > 0 ? $smsapi_record->authorization : '' }}'
                                                                                    id='authorization'
                                                                                    name='authorization'
                                                                                    placeholder="API Authorization"
                                                                                    aria-label="Authorization"
                                                                                    aria-describedby="Authorization">
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="request_type">Request
                                                                                    Method</label>
                                                                                <select id='request_type'
                                                                                    name='request_type'
                                                                                    class='form-control'>
                                                                                    @foreach ($request_methods as $methods)
                                                                                        <option
                                                                                            value='{{ $methods->id }}'
                                                                                            @if (count($smsapi) > 0) @if ($smsapi_record->request_method_id == $methods->id) selected @endif
                                                                                            @endif
                                                                                            >{{ $methods->request_method }}
                                                                                        </option>
                                                                                    @endforeach


                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <center><button type='button' class='btn btn-warning'
                                                                    onclick='save_sms_settings()'>Save SMS
                                                                    Settings</button></center>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            @include('company.settings.inc.social')
                                            @include('company.settings.inc.developer')
                                            @include('company.settings.inc.domainname')
                                            @include('company.settings.inc.settlement')
                                            @include('company.settings.inc.security')
                                            @include('company.settings.inc.remitta')
                                            @include('company.settings.inc.email')
                                            @include('company.settings.inc.referralSetting')
                                            @include('company.settings.inc.transfer_user')
                                        </div>

                                    </div>
                                </div>
                            </div>



                        </div>
                    </div>
                </div>


        </div>

        <div class="account-settings-footer">

            <div class="as-footer-container">


                <div class="blockui-growl-message">
                    <i class="flaticon-double-check"></i>&nbsp; Processing...
                </div>
                <input type='submit' class="btn btn-primary" style="color: black !important" value='Save Changes'>
                <input type='reset' class="btn btn-dark" value='Reset All'>

            </div>

        </div>
        </form>
    </div>

    </div>
@endsection
