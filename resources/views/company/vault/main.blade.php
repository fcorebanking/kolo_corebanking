@include('inc.styles')

@include('inc.scripts')

@extends('layouts.app')

@section('content')

    <div class="layout-px-spacing">

        <div class="row layout-top-spacing">

            <div class="col-xl-12 col-lg-12 col-sm-12">
                <div class="widget-content widget-content-area">

                    <button class='btn btn-primary' onclick='transfer_bank()'>Vault Transfer to Bank</button>
                    <button class='btn btn-primary' onclick='transfer_vault()'>Vault Transfer</button>
                    <button class='btn btn-warning' onclick='deposit_vault()'>Fund Vault</button>
                    <button class='btn btn-info' onclick='view_vault()'>Create Vault</button>
                    <button class='btn btn-success' onclick='staff_deposit()'>Staff Vault</button>
                    <button class='btn btn-secondary' onclick='branch_deposit()'>Branch Vault</button>

                    <div id='transfer_bank'>
                        <h3>Company Wallet to Bank</h3>

                        <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">

                            <div class="col-md-8">
                                <div>
                                    <div class="form-group ">
                                        <label for="source_wallet">Source Wallet</label>
                                        <select id='source_wallet' class='form-control'>
                                            @if ($company_vault)
                                                @foreach ($company_vault as $company_vault_data)
                                                    <option value='{{ $company_vault_data->id }}'>
                                                        {{ setTitle($company_vault_data->vault) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="form-group ">
                                        <label for="transferBankCode">Select Bank</label>

                                        <select class="form-control basic" id="transferBankCode" name="transferBankCode"
                                            onchange="verifyBankAccount()">

                                            <option value="">::Select Bank::</option>

                                            @foreach ($banks as $bank)
                                                <option value="{{ $bank->bank_code }}">{{ $bank->bank_name }}
                                                </option>
                                            @endforeach

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
                                    <div class="col-lg-12" style="display: none;" id="myBankLoader">

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
                                        <input type="hidden" name="requestReference" id="requestReference">
                                        <input type="hidden" name="sessionID" id="sessionID">

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <span class="validation-text"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group ">
                                        <label for="amount">Amount</label>
                                        <input type='text' id='amount' class='form-control form-small'
                                            style='margin:5px'>
                                    </div>

                                    <button class='btn btn-primary' onclick='send_transfer()'>Transfer</button>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div id='transfer_vault'>
                        <h3>Company Wallet to Vault,Wallet Transfer</h3>

                        <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">

                            <div class="col-md-8">
                                <div>
                                    <div class="form-group ">
                                        <label for="source_wallet">Source Wallet</label>
                                        <select id='source_wallet' class='form-control'>
                                            @if ($company_vault)
                                                @foreach ($company_vault as $company_vault_data)
                                                    <option value='{{ $company_vault_data->id }}'>
                                                        {{ setTitle($company_vault_data->vault) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="form-group ">
                                        <label for="destination">Destination Wallet</label>
                                        <select id='destination' class='form-control form-small' style='margin:5px'>
                                            @if ($company_vault)
                                                @foreach ($company_vault as $company_vault_data)
                                                    <option value='{{ $company_vault_data->id }}'>
                                                        {{ setTitle($company_vault_data->vault) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group ">
                                        <label for="amount">Amount</label>
                                        <input type='text' id='amount' class='form-control form-small'
                                            style='margin:5px'>
                                    </div>

                                    <button class='btn btn-primary' onclick='send_transfer()'>Transfer</button>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div id='deposit_vault'>
                        <h3>Company Wallet Deposit</h3>

                        <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">

                            <div class="col-md-8">
                                <div>
                                    <div class="form-group ">
                                        <label for="wallet">Wallet</label>
                                        <select id='wallet' class='form-control'>
                                            @if ($company_vault)
                                                @foreach ($company_vault as $company_vault_data)
                                                    <option value='{{ $company_vault_data->id }}'>
                                                        {{ setTitle($company_vault_data->vault) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="form-group ">
                                        <label for="amount">Amount</label>
                                        <input type='text' id='amount_deposit' class='form-control form-small'
                                            style='margin:5px'>
                                    </div>

                                    <button class='btn btn-primary' onclick='send_deposit()'>Deposit</button>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div id='create_vault'>
                        <h3>Create Vault</h3>

                        <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">

                            <div class="col-md-8">
                                <div>
                                    <div class="form-group ">
                                        <label for="wallet">New Vault</label>
                                        <input type='text' id='new_vault' class='form-control form-small'
                                            style='margin:5px'>

                                    </div>



                                    <button class='btn btn-primary' onclick='create_vault()'>Create</button>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div id='staff_deposit'>
                        <h3>Fund Company Staff Wallet </h3>

                        <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">

                            <div class="col-md-8">
                                <div>
                                    <div class="form-group ">
                                        <label for="staff">Staff</label>
                                        <select id='staff' class='form-control'>
                                            @if ($staff)
                                                @foreach ($staff as $company_staff)
                                                    <option value='{{ @$company_staff->uuid }}'>
                                                        {{ setTitle(@$company_staff->first_name) . ' ' . setTitle(@$company_staff->last_name) . ' - ' . number_format(@$company_staff->wallet->balance) }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group ">
                                        <label for="staff_credit_option">Credit Option</label>
                                        <select id='staff_credit_option' class='form-control'>
                                            <option value='credit'>Credit</option>
                                            <option value='debit'>Debit</option>
                                        </select>
                                    </div>
                                    <div class="form-group ">
                                        <label for="amount">Amount</label>
                                        <input type='text' id='amount_staff' class='form-control form-small'
                                            style='margin:5px'>
                                    </div>

                                    <button class='btn btn-primary' onclick='send_staff_deposit()'>Process</button>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div id='branch_deposit'>
                        <h3>Fund Company Branch Wallet </h3>

                        <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">

                            <div class="col-md-8">
                                <div>
                                    <div class="form-group ">
                                        <label for="branch">Branch</label>
                                        <select id='branch' class='form-control'>
                                            @if ($branchdata)
                                                @foreach ($branchdata as $company_branch)
                                                    <option value='{{ $company_branch->id }}'>
                                                        {{ setTitle($company_branch->branch_name) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="form-group ">
                                        <label for="amount">Amount</label>
                                        <input type='text' id='amount_branch' class='form-control form-small'
                                            style='margin:5px'>
                                    </div>

                                    <button class='btn btn-primary' onclick='send_branch_deposit()'>Fund Branch</button>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
                <br>
                <div class="widget-content widget-content-area br-6 walletview vault-card ">

                </div>
                <div class="widget-content widget-content-area br-6 logview">
                    <div class="table-responsive mb-4 mt-4">
                        <span id='infobait'>
                            <h3>Transaction History</h3>
                        </span>
                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                            <thead>
                                <th>SN</th>
                                <th>Name</th>
                                <th>Acnumber</th>
                                <th>Balance</th>
                                <th>Branch</th>
                                <th>Vault</th>
                                <th>Ref</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>

                    </div>
                </div>
            </div>


        </div>

        @include('company.vault.inc')
    </div>


@endsection
