@extends('layouts.app')

@section('content')
<div class="widget-content widget-content-area br-6">
    @if($errors->any())
    <h4>{{$errors->first()}}</h4>
    @endif
    @if (\Session::has('success'))
    <div class="alert alert-success">
        <h4>{!! \Session::get('success') !!}</h4>
    </div>
    @endif
    <div class="card mb-4 mt-4" style='padding:10px;'>

        {{-- tab start --}}
        <ul class="nav nav-pills mb-3 mt-3 nav-fill" id="justify-pills-tab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="justify-pills-home-tab" data-toggle="pill" href="#justify-pills-home" role="tab" aria-controls="justify-pills-home" aria-selected="true">Loan Schedule</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" id="justify-pills-profile-tab" data-toggle="pill" href="#justify-pills-profile" role="tab" aria-controls="justify-pills-profile" aria-selected="false">Repayment</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="justify-pills-contact-tab" data-toggle="pill" href="#justify-pills-contact" role="tab" aria-controls="justify-pills-contact" aria-selected="false">Payment History</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="justify-pills-details-tab" data-toggle="pill" href="#justify-pills-details" role="tab" aria-controls="justify-pills-details" aria-selected="false">Loan Details</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="justify-pills-due-tab" data-toggle="pill" href="#justify-pills-due" role="tab" aria-controls="justify-pills-due" aria-selected="false">Due Loan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="justify-pills-extend-tab" data-toggle="pill" href="#justify-pills-extend" role="tab" aria-controls="justify-pills-extend" aria-selected="false">Extended Fee</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="justify-pills-remitta-tab" data-toggle="pill" href="#justify-pills-remitta" role="tab" aria-controls="justify-pills-remitta" aria-selected="false">Remitta</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="justify-pills-comment-tab" data-toggle="pill" href="#justify-pills-comment" role="tab" aria-controls="justify-pills-comment" aria-selected="false">Loan Comments</a>
            </li>

        </ul>
        <div class="tab-content" id="justify-pills-tabContent">
            <div class="tab-pane fade show active" id="justify-pills-home" role="tabpanel" aria-labelledby="justify-pills-home-tab">
                @include('company.loan.inc.repaymentTable')
            </div>
            <div class="tab-pane fade" id="justify-pills-profile" role="tabpanel" aria-labelledby="justify-pills-profile-tab">
                <h2>Loan Payment</h2>
                <div class='row'>
                    <div class='col-sm-6 col-md-6' style='text-align:left;'>
                        <h5>First Name: {{$loanData->user->first_name.' '.$loanData->user->last_name}}</h5>
                        <h5>Account Number: {{$loanData->user->virtual_account}}</h5>
                        <h5>Account Phone: {{$loanData->user->phone}}</h5>
                        <h5>Account Email: {{$loanData->user->email}}</h5>
                    </div>
                    <div class='col-sm-6 col-md-6'>
                        <?php $user = $loanData->user;
                            $logo = url(str_replace('public','storage',$user->profile_pic));?>
                        <img src="{{($user->profile_pic== null) ? 'http://safe.wbalite.com/wba/public/passports/default_image.jpg' : $logo}}"    alt="avatar" width='100'>
                    </div>

                </div>
                <h4>Borrowers Accounts</h4>
                <table class='table table-striped'>
                    <tr >
                        <th>Account Type</th>
                        <th>Account Number</th>
                        <th>Balance</th>
                    </tr>
                    @if($loanData)
                        @foreach($loanData->user->wb_account_details as $loanRec)
                        <tr>
                            <td>{{$loanRec->accountType->savings_product}}</td>
                            <td>{{$loanRec->virtual_accnum}}</td>
                            <td>{{@$auth->company->currency->symbol.@$loanRec->user_wallet->balance}}</td>
                        </tr>
                        @endforeach
                    @endif
                </table>
                <h4>Paid Loan</h4>
                <table class='table'>
                    <tr class='bg-dark'>
                        <th>#</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                    </tr>
                    <tbody id='paidloanTable'>

                    </tbody>
                </table>
                <h4>Make Payment</h4>

                <form id='pay-loan' enctype="multipart/form-data">
                    <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" id="loanID" name="loanID" value="{{Crypt::encryptString($loanData->id)}}">
                    <div class="modal-body">
                        <div class='row'>
                            <div class='col-sm-6 col-md-6'>
                            <label>Payment Method</label>
                            <select name='method' id='method' onchange="changewalletCash(this.value)" class='form-control form-small' style='margin:5px' >
                                <option value='wallet'>Wallet</option>
                                <option value='cash'>Cash</option>
                            </select>
                            <span class='validation-text'></span>
                            </div>
                            <div class='col-sm-6 col-md-6'>
                                <label>Amount</label>
                                    <input type='number' name='amount' id='amount' class='form-control'>
                                <span class='validation-text'></span>
                            </div>
                            <div class='col-sm-6 col-md-6'>
                                <label>Payment Date</label>
                                    <input type='date' name='payment' id='payment' class='form-control'>
                                <span class='validation-text'></span>
                            </div>
                            <div class='col-sm-6 col-md-6 accountType'>
                                <label>Account Type</label>
                                <select name='accountType' id='accountType' class='form-control form-small' style='margin:5px' >
                                    @if($loanData)
                                        @foreach($loanData->user->wb_account_details as $loanRec)
                                            <option value="{{$loanRec->id}}">{{$loanRec->virtual_accnum}}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <span class='validation-text'></span>
                            </div>
                            @if (isset($auth->company->Transactionsettings))
                                @if ($auth->company->Transactionsettings->view_vault_on_txn == 1)
                                    @if (($CompanyValut))
                                    <div class='col-sm-6 col-md-6 accountType'>
                                    <div class="input-group mb-4">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Source Vault</span>
                                        </div>
                                        <select  name='companyVault'  id='companyVault' class='form-control'>
                                            @foreach ($CompanyValut as $companyVaultData)
                                                <option value="{{$companyVaultData->id}}">{{setTitle($companyVaultData->vault)}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                    @endif
                                @endif
                                @if ($auth->company->Transactionsettings->view_instrument_number == 1)
                                <div class='col-sm-6 col-md-6 accountType'>
                                    <div class="input-group mb-4">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" >Instrument Code</span>
                                        </div>
                                        <input type="text" class="form-control"  id='instrumentCode' name='instrumentCode' aria-describedby="basic-addon3"
                                            placeholder="Instrument Code"  >
                                    </div>
                                </div>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="reset"  class="btn" ><i class="flaticon-cancel-12"></i> Discard</button>
                        <button type="button" class="btn btn-primary" id="loanPayBTN" onclick='payloan()'>Pay</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="justify-pills-contact" role="tabpanel" aria-labelledby="justify-pills-contact-tab">
                @include('company.loan.inc.paymentHistory')
            </div>
            <div class="tab-pane fade" id="justify-pills-details" role="tabpanel" aria-labelledby="justify-pills-details-tab">
                @include('company.loan.inc.loanDetails2')
                <a href="{{url('company/'.$auth->company->short_name.'/loan/offer/'.$loanID)}}" class='btn btn-success'> View Offer</a>
            </div>
            <div class="tab-pane fade" id="justify-pills-due" role="tabpanel" aria-labelledby="justify-pills-due-tab">
                @include('company.loan.inc.dueLoan')
            </div>
            <div class="tab-pane fade" id="justify-pills-extend" role="tabpanel" aria-labelledby="justify-pills-extend-tab">
                @include('company.loan.inc.extended')
            </div>
            <div class="tab-pane fade" id="justify-pills-remitta" role="tabpanel" aria-labelledby="justify-pills-remitta-tab">
                @if ($auth->company->setting->activate_remitta)
                    @include('company.loan.inc.remitta')
                @else
                    <div class='alert alert-warning'>Note: Contact us for Remitta credential</div>

                @endif
            </div>
            <div class="tab-pane fade" id="justify-pills-comment" role="tabpanel" aria-labelledby="justify-pills-comment-tab">
                @include('company.loan.inc.loanComment')
            </div>
        </div>
        {{-- tab end --}}
    </div>
</div>
<input type='hidden' id='remitaPhone' value="{{$loanData->user->phone}}">
<input type='hidden' id='remittar_bankCode' value="{{$loanData->user->remittar_bankCode}}">
<input type='hidden' id='remittar_accountNo' value="{{$loanData->user->remittar_accountNo}}">
@endsection
