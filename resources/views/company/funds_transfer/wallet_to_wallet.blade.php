@extends('layouts.app')

@section('content')
<div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
                            <div class="statbox widget box box-shadow">
                                <div class="widget-header">                                
                                    <div class="row">
                                        <div class="col-xl-12 col-md-12 col-sm-12 col-12" style='margin:10px'>
                                            <h4>Wallet to Wallet Transfer</h4>
                                        </div>
                                    </div>
                                </div>                            
                                <div class="widget-content widget-content-area text-center">
                                <form id='submit_form'>
                                    <div class="row">
                                        <div class="col-sm-6 col-md-6">
                                            <label>Sender </label>

                                            <select name='customer' id='customer' class='form-control form-small' style='margin:5px' onchange='rendersave1()'>
                                            
                                            </select>
                                        </div>
                                        <div class="col-sm-6 col-md-6">
                                            <label>Beneficiary</label>
                                            <select name='beneficiary' id='beneficiary' class='form-control basic' style='margin:5px' onchange='rendersave2()'>
                                            </select>
                                        </div>
                                        <div class="col-sm-6 col-md-6">
                                            <label>Sender Account </label>
                                            <select name='sender_account' id='savetype1' class='form-control form-small' style='margin:5px'>
                                            
                                            </select>
                                        </div>
                                        <div class="col-sm-6 col-md-6">
                                            <label>Beneficiary Account </label>
                                            <select name='beneficiary_account' id='savetype2' class='form-control basic' style='margin:5px'>
                                         
                                            </select>
                                        </div>
                                        <div class="col-sm-6 col-md-6">
                                            <label>Transfer Amount</label>
                                            <input type='text' name='amount' onkeypress="return isNumberKey(event)" id='amount' class='form-control' style='margin:5px'>
                                        </div>
                                        @if(auth()->user()->company->setting->min_withdraw_charge < auth()->user()->company->setting->max_withdraw_charge && auth()->user()->company->setting->max_withdraw_charge > 0)
                                        <div class="col-sm-6 col-md-6">    
                                            <label>Transfer Charge</label>
                                            <div class="input-group mb-4 " id='wcharge'>
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Charge ({{auth()->user()->company->setting->min_withdraw_charge}}{{auth()->user()->company->setting->withdraw_charge_state?'%':auth()->user()->company->currency->symbol}})</span>
                                                    </div>
                                                    <input type="number" class="form-control" min="{{auth()->user()->company->setting->min_withdraw_charge}}" max="{{auth()->user()->company->setting->max_withdraw_charge}}" id='w_charge' name='w_charge' aria-describedby="basic-addon3"
                                                        placeholder="Withdraw Charge" value='{{auth()->user()->company->setting->min_withdraw_charge}}'>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-sm-12 col-md-12">
                                            <label>Remark</label>
                                            <textarea name='remark' id='remark' class='form-control' style='margin:5px'></textarea>
                                        </div>
                                    </div>
                                    <button type='submit' id='transfer-wallet' class="mr-2 btn btn-primary">Transfer</button>
                                    </form>
                                </div>
                            </div>
                        </div>

<!-- Modal -->
@endsection 

