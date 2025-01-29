@extends('layouts.app')
@section('content')
@php
    $auth = auth()->user();
@endphp
<div class="layout-px-spacing">
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <span id='infobait'></span>
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>
                        <th>FirstName</th>
                        <th>LastName</th>
                        <th>AccountNo</th>
                        <th>Phone</th>
                        <th>Account Type</th>
                        <th>Balance</th>
                        @if(cando('credit-customer|manage-saving|root'))
                        <th>Credit</th>
                        @endif
                        @if(cando('debit-customer|manage-saving|root'))
                        <th>Debit</th>
                        @endif
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog rotateInDownLeft" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Deposit</h5>
            </div>
            <form id='submit_form'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" id='formtype' >
                <input type="hidden" id='uuid' name='uuid' >
                <div class="modal-body">
                    @if (cando('control-customer-notification|root'))
                    <div class='row'>
                        <div class='col-sm-6 col-md-6'>
                        <span>SMS</span><br>
                            <label class="switch s-outline s-outline-success  mb-4 mr-2">
                            <input type="checkbox" id='smscheck'>
                            <span class="slider round"></span>
                        </label>
                        </div>
                        <div class='col-sm-6 col-md-6'>
                        <span>WhatsApp</span><br>
                        <label class="switch s-outline s-outline-success  mb-4 mr-2">
                        <input type="checkbox" id='whatsappcheck'>
                            <span class="slider round"></span>
                        </label>
                        </div>
                    </div>
                    @endif
                    <button onclick='functionfetchinfo()' type='button' class='btn btn-outline-primary btn-sm'>Verify</button>
                    <div class='row'>
                    <div class='col-sm-6 col-md-12'>
                        <div class='verify_Restriction'>
                            <div class="card " >
                                    <div class="card-body">
                                        <div class="user-info">
                                        <img id='customer_RestricTprofile_pic' width='200' src="">
                                            <h5 class="card-user_name" id='customer_RestricTName'>...</h5>
                                            <p class="card-user_occupation" id='customer_RestricTusername'>...</p>
                                            <div class="card-star_rating">
                                            <p  id='customer_RestricTemail'>...</p>
                                            </div>
                                            <p class="card-text">
                                                <ul style=" list-style-type: none;">
                                                    <li id='customer_RestricTphone'></li>
                                                    <li id='customer_RestricTaddress'></li>
                                                    <li id='customer_RestricTvirtual_account'></li>
                                                    <li id='customer_RestricTsavings_product'></li>
                                                    <li id='customer_RestricTpermission'></li>


                                                </ul>
                                            <p id='customer_RestricTremark'></p>

                                        <p> <img id='customer_RestricTsignature' width='200' src=""></p>
                                            <small id="customer_RestricTcreatedat"></small>
                                        </p>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Amount</span>
                        </div>
                        <input type="text" class="form-control" id='amount' name='amount' aria-describedby="basic-addon3"
                            placeholder="amount" onkeypress="return /^\d*\.?\d*$/.test(String.fromCharCode(((event||window.event).which||(event||window.event).which)));">
                    </div>
                    <span class='validation-text'></span>
                    @if($auth->company->setting->min_withdraw_charge < $auth->company->setting->max_withdraw_charge && $auth->company->setting->max_withdraw_charge > 0)
                        <div class="input-group mb-4 " id='wcharge'>
                            <div class="input-group-prepend">
                                <span class="input-group-text">Withdrawal Charge ({{$auth->company->setting->withdraw_charge_state?'%':$auth->company->currency->symbol}})</span>
                            </div>
                            <input type="number" class="form-control" min="{{$auth->company->setting->min_withdraw_charge}}" max="{{$auth->company->setting->max_withdraw_charge}}" id='w_charge' name='w_charge' aria-describedby="basic-addon3"
                                placeholder="Withdraw Charge" value='{{$auth->company->setting->min_withdraw_charge}}'>
                        </div>
                    @endif
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Transaction Type</span>
                        </div>
                        <select  name='transaction_type_id'  id='transaction_type' class='form-control' onchange="onselectType()">
                        </select>
                    </div>
                    <div class='bankdetails'>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Select Bank</span>
                        </div>
                        <select  name='bank_id'  id='bank_id' class='form-control'>
                            @if ($bank)
                                @foreach ($bank as $bankData)
                                    <option value='{{$bankData->id}}' code='{{$bankData->bank_code}}'>{{$bankData->bank_name}}</option>
                                @endforeach
                            @endif
                            <option></option>
                        </select>
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Account Number</span>
                        </div>
                        <input type="text" class="form-control" id='accountNo' name='accountNo' aria-describedby="basic-addon3"
                            placeholder="Account Number" onkeyup='verifyBankAccount();' onkeypress=" return /^\d*\.?\d*$/.test(String.fromCharCode(((event||window.event).which||(event||window.event).which))); " >
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Account Name</span>
                        </div>
                        <input type="text" class="form-control" readonly id='transferAccountName' name='transferAccountName' aria-describedby="basic-addon3"
                            placeholder="Account Name"  >
                    </div>
                    <span id='myBankLoader'></span>
                </div>

                    @if($auth->company->setting->enable_txn_created_by == 1)
                        @if (cando('root|can-set-txn-created-by'))
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" >Created By</span>
                            </div>
                            <select  name='created_byx'  id='created_byx' class='form-control'>
                                @php
                                    $getStaff = getStaff();
                                @endphp
                                @if($getStaff)
                                @foreach ( $getStaff as $getStaffData)
                                    <option value='{{$getStaffData->uuid}}' @if($auth->id == $getStaffData->id) selected @endif>{{$getStaffData->username}}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        @endif
                    @endif


                  @if($auth->company->setting->backdate->id == 2)
                     <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Created At</span>
                        </div>
                        <input type="datetime-local" class="form-control"  name='backdate' aria-describedby="basic-addon3"
                            placeholder="Back Date" required>

                    </div>

                  @endif
                  {{-- CompanyValut --}}
                  <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Account Type</span>
                    </div>
                    <select  name='account_type'  id='account_type' class='form-control'></select>
                </div>
                @if (isset($auth->company->Transactionsettings))
                @if ($auth->company->Transactionsettings->view_vault_on_txn == 1)
                    @if (($CompanyValut))
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
                    @endif
                @endif
                @if ($auth->company->Transactionsettings->view_instrument_number == 1)
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Instrument Code</span>
                        </div>
                        <input type="text" class="form-control"  id='instrumentCode' name='instrumentCode' aria-describedby="basic-addon3"
                            placeholder="Instrument Code"  >
                    </div>
                @endif
                @endif
                <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text" >Initiator</span>
                    </div>
                    <input type="text" class="form-control"  id='initiator' name='initiator' aria-describedby="basic-addon3"
                        placeholder="Initiator"  >
                </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Decription</span>
                        </div>
                        <textarea maxlength="100" name='desc' id='desc' class='form-control'></textarea>

                    </div>

                    <span class='validation-text'></span>
                    @if(cando('root'))
                    <legend>
                        <h5>Accounting</h5>
                        <p>Select your financial account for journal entry:</p>
                        @if ($ledger)
                            <div class='form-group'>
                                <label>Cash/Bank</label>
                                <select name='ledger' class='form-control' >
                                    <option value=''>Use Default Ledger</option>
                                        @foreach ($ledger as $ledgerData)
                                            @if ($ledgerData->access ==1 ||  ($ledgerData->access ==2 && $ledgerData->branch_id !=null && $ledgerData->branch_id ==auth()->user()->branch_id))
                                                <option value='{{$ledgerData->id}}'>{{$ledgerData->name.'-'.$ledgerData->code}}</option>
                                            @endif
                                        @endforeach
                                </select>
                            </div>
                        @endif

                    </legend>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id='savingsbtn'>Send</button>

                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                </div>
                <div class='table-responsive container'>
                <table>
                    <tr>
                    <th><h5>Denom</h5></th>
                    <th><h5>Quality</h5></th>
                    <th><h5>Amount</h5></th>
                    </tr>
                    <tbody>
                    <tr>
                        <td><p>10,000</p></td>
                        <td><input type='number' class='form-control' id='ten-thousand-quantity' name='ten_thousand_quantity'  /></td>
                        <td><input type='number' class='form-control' id='ten-thousand' readonly /></td>

                    </tr>

                    <tr>
                        <td><p>5,000</p></td>
                                    <td> <input type='number' class='form-control' id='five-thousand-quantity' name='five_thousand_quantity'/></td>
                                    <td><input type='number' class='form-control' id='five-thousand' readonly /></td>


                                </tr>
                    <tr>
                        <td><p>2,000</p></td>
                        <td><input type='number' class='form-control' id='two-thousand-quantity' name='two_thousand_quantity'/></td>
                        <td><input type='number' class='form-control' id='two-thousand' readonly /></td>

                    </tr>
                    <tr>
                        <td><p>1,000</p></td>
                                    <td><input type='number' class='form-control' id='one-thousand-quantity'  name='one_thousand_quantity' /></td>
                                    <td><input type='number' class='form-control' id='one-thousand' readonly /></td>


                    </tr>
                    <tr>
                        <td><p>500 Note</p></td>
                                    <td><input type='number' class='form-control' id='five-hundred-note-quantity' name='five_hundred_note_quantity'/></td>
                                    <td><input type='number' class='form-control' id='five-hundred-note' readonly/></td>

                    </tr>
                    <tr>
                        <td><p>500 Coin</p></td>
                                    <td><input type='number' class='form-control' id='five-hundred-coin-quantity' name='five_hundred_coin_quantity'/></td>
                                    <td><input type='number' class='form-control' id='five-hundred-coin' readonly/></td>


                    </tr>
                    <tr>
                        <td><p>100</p></td>
                                    <td><input type='number' class='form-control' id='one-hundred-quantity'  name='one_hundred_quantity'/></td>
                                    <td><input type='number' class='form-control' id='one-hundred'readonly /></td>


                    </tr>
                    <tr>
                        <td><p>50</p></td>
                                    <td><input type='number' class='form-control' id='fifthy-quantity' name='fifthy_quantity'/></td>
                                    <td><input type='number' class='form-control' id='fifthy' readonly/></td>


                    </tr>
                    <tr>
                        <td><p>25</p></td>
                                    <td><input type='number' class='form-control' id='twenty-five-quantity' name='twenty_five_quantity'/></td>
                                    <td><input type='number' class='form-control' id='twenty-five' readonly/></td>

                    </tr>
                    <tr>
                        <td><p>10</p></td>
                                    <td><input type='number' class='form-control' id='ten-quantity' name='ten_quantity'/></td>
                                    <td><input type='number' class='form-control' id='ten' readonly/></td>

                    </tr>
                    <tr>
                        <td><p>5</p></td>
                                    <td><input type='number' class='form-control' id='five-quantity' name='five_quantity'/></td>
                                    <td><input type='number' class='form-control' id='five' readonly/></td>

                    </tr>
                    <tr>
                        <td><p>2</p></td>
                                    <td><input type='number' class='form-control' id='two-quantity' name='two_quantity'/></td>
                                    <td><input type='number' class='form-control' id='two' readonly/></td>

                    </tr>
                    <tr>
                        <td><p>1</p></td>
                                    <td><input type='number' class='form-control' id='one-quantity' name='one_quantity'/></td>
                                    <td><input type='number' class='form-control' id='one' readonly/></td>

                    </tr>
                    <tr class='m-5'>
                        <td></td>
                        <td>
                            <label>Total</label>
                        </td>
                        <td>
                            <input type='number' class='form-control' readonly id='total-input' />
                        </td>

                    </tr>
                </tbody></div>
            </form>



        </div>
    </div>
</div>

@endsection

