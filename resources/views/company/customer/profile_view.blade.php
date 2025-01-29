@extends('layouts.app')

@section('content')

            <div class="layout-px-spacing">

                <div class="row layout-spacing">

                    <!-- Content -->
                    <div class="col-xl-4 col-lg-6 col-md-5 col-sm-12 layout-top-spacing">

                        <div class="user-profile layout-spacing">
                            <div class="widget-content widget-content-area">
                                <div class="d-flex justify-content-between">
                                    <h3 class="">Profile</h3>
                                    <a href="{{url('company/'.Auth()->user()->company->short_name.'/customer/customer_profile/'.$user->uuid)}}" class="mt-2 edit-profile"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-3"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg></a>
                                </div>
                                <div class="text-center user-info">

                                    <?php  $logo = url(str_replace('public','storage',$user->profile_pic));?>
                                     <img src="{{$user->profile_pic== null ? 'http://safe.wbalite.com/wba/public/passports/default_image.jpg' : $logo}}"    alt="avatar" width='100'>
                                    <p class="">{{$user->first_name.' '.$user->last_name}}</p>
                                </div>
                                <div>

                                    <div class="">
                                        <ul class="contacts-block list-unstyled">
                                            <li class="contacts-block__item">

                                                    {{-- @if($user->bank->bank_name) --}}
                                                        {{-- @foreach($user->bank->bank_name as $banks)
                                                            {{$banks->branch_name}}
                                                        @endforeach --}}
                                                    {{-- @endif --}}
                                                 {{$user->username}}
                                            </li>
                                            <li class="contacts-block__item">
                                                {{$user->dob}} |   {{$user->sex}}
                                            </li>
                                            <li class="contacts-block__item">
                                               {{$user->address}}
                                            </li>
                                            <li class="contacts-block__item">
                                                <a href="mailto:{{$user->email}}">{{$user->email}}</a>
                                            </li>
                                            <li class="contacts-block__item">
                                                 {{$user->phone}}
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="education layout-spacing ">
                            <div class="widget-content widget-content-area">
                                <h3 class="">Recent Txn</h3>
                                <div class="timeline-alter">
                                    @if($txn )
                                    @foreach($txn as $txns)
                                    <div class="item-timeline">
                                        <div class="t-meta-date">
                                            <p class="">{{$txns->created_date}}</p>
                                        </div>
                                        <div class="t-dot">
                                        </div>
                                        <div class="t-text">
                                            <b>{{$txns->transaction_type}}</b>
                                          <p>{{auth()->user()->company->currency->symbol}}{{$txns->amount}}</p>
                                            <p>{{$txns->description}}</p>

                                        </div>
                                    </div>
                                    @endforeach
                                    @endif


                                </div>
                            </div>
                        </div>



                    </div>

                    <div class="col-xl-8 col-lg-6 col-md-7 col-sm-12 layout-top-spacing">

                    <div class="bio layout-spacing ">

                            <div class="widget-content widget-content-area">
                                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
                                    <div class="col-md-6">
                                      <div class="clipboard">
                                        <form class="form-horizontal">
                                          <label for="input-copy"><strong>{{($hasRef)?'Referal':'Customer'}} Registration Link</strong></label>
                                          <input type="text" class="form-control mb-4" readonly id="input-copy" value="{{$registrationLink}}">

                                          <a class="mb-1 btn btn-primary" href="javascript:;" data-clipboard-action="copy" data-clipboard-target="#input-copy"><i class="fa fa-clipboard"></i> Copy Link</a>
                                        </form>
                                      </div>
                                    </div>
                                  </div>

                                <h3 class="">Accounts</h3>
                              <p></p>

                                <div class="bio-skill-box">

                                    <div class="row">
                                        @if($product)
                                        @foreach($product as $productdata)
                                        <div class="col-12 col-xl-6 col-lg-12 mb-xl-5 mb-5 ">

                                            <div class="d-flex b-skills">
                                                <div>
                                                </div>
                                                <div class="">
                                                    <div class='row'>
                                                        <div class='col-sm-6 col-md-12'>
                                                            <h5>{{$productdata->accountType->savings_product}} <i class='fa fa-edit float-right' onclick="edit('{{$productdata->id}}')"></i></h5>

                                                    <p>{{$productdata->virtual_accnum}} | {{auth()->user()->company->currency->symbol}}{{isset($productdata->user_wallet->balance )?$productdata->user_wallet->balance:0}}</p>
                                                </div>

                                            </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                        @endif
                                    </div>
                                    <h3 class="">Active Loan</h3>
                                    <p></p>
                                    <div class="row">
                                        @if(isset($user->loan))
                                        @foreach($user->loan as $loanData)
                                        <div class="col-12 col-xl-6 col-lg-12 mb-xl-5 mb-5">
                                            <div class="d-flex b-skills">
                                                <div>
                                                </div>
                                                <div class="">
                                                    <h5>{{$loanData->duration}} {{$loanData->DurationPeriod}}</h5>

                                                    {{$loanData->ref}} | {{$loanData->loanBalance}}
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                        @endif

                                    </div>
                                    <h3 class="">Active Investment</h3>
                                    <p></p>
                                          <div class="row">
                                              @if(isset($user->wb_account_details))
                                              @foreach($user->wb_account_details as $wbdatails)
                                              @if(isset($wbdatails->investment))
                                                @foreach($wbdatails->investment as $dataInvest)
                                                <div class="col-12 col-xl-6 col-lg-12 mb-xl-5 mb-5">
                                                    <div class="d-flex b-skills">
                                                        <div>
                                                        </div>
                                                        <div class="">
                                                            <h5>{{$dataInvest->plan->product??'No Plan'}} </h5>
                                                            {{$dataInvest->expected_balance??'No invest'}}
                                                            <p>Start: {{$dataInvest->start_date??'Null'}} - Maturity: {{$dataInvest->end_date??'Null'}}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                                @endif
                                              @endforeach
                                              @endif

                                          </div>
                                    <div class="row">
                                        <div class="col-md-6 p-3">
                                        <?php  $validid = url(str_replace('public','storage',$user->validID_file));?>


                                            <a href='{{$validid}}' target='_blank' class='btn btn-warning'>View Valid ID</a>

                                        </div>


                                        <div class="col-md-6 p-3">
                                        <?php  $otherID_file = url(str_replace('public','storage',$user->otherID_file));?>


                                            <a href='{{$otherID_file}}' target='_blank' class='btn btn-warning'>View Other ID</a>

                                        </div>
                                        <?php  $signature = url(str_replace('public','storage',$user->signature));?>

                                        <div class="col-md-6 p-3">


                                            <a href='{{$signature}}' target='_blank' class='btn btn-warning'>View Signature</a>


                                        </div>

                                        <?php  $utilityBillID_file = url(str_replace('public','storage',$user->utilityBillID_file));?>
                                        <div class="col-md-6 p-3">

                                            <a href='{{$utilityBillID_file}}' target='_blank' class='btn btn-warning'>View Utility Bill File</a>
                                        </div>
                                    </div>

                                    <div class="table-responsive" style="overflow-x: scroll">

                                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">

                                            <thead>
                                                <tr>
                                                    <th>SN</th>
                                                    <th>Name</th>
                                                    <th>Customer Name</th>
                                                    <th>Account No</th>
                                                    <th>Phone</th>
                                                    <th>Rate</th>
                                                    <th>Target Amount</th>
                                                    <th>Current Balance</th>
                                                    <th>Expected Balance</th>
                                                    <th>Status</th>
                                                    <th>Duration</th>
                                                    <th>Start</th>
                                                    <th>End</th>
                                                    <th>Agent</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if ($totalAccountData)
                                                @php
                                                    $sn =1;
                                                    $repayment =0;
                                                @endphp
                                                    @forelse ($totalAccountData as $totalAccountDatas)

                                                        <tr>
                                                            <td>{{$sn++}}</td>
                                                            <td>{{$totalAccountDatas->name}}</td>
                                                            <td>{{$totalAccountDatas->user->first_name}}</td>
                                                            <td>{{$totalAccountDatas->user->virtual_account}}</td>
                                                            <td>{{$totalAccountDatas->user->phone}}</td>
                                                            <td>{{$totalAccountDatas->rate}}</td>
                                                            <td>{{$totalAccountDatas->target}}</td>
                                                            <td>{{$totalAccountDatas->balance}}</td>
                                                            <td>{{$totalAccountDatas->pastPayment()}}</td>
                                                            <td>{{strtoupper($totalAccountDatas->status)}}</td>
                                                            <td>{{$totalAccountDatas->duration}}</td>
                                                            <td>{{$totalAccountDatas->start_date}}</td>
                                                            <td>{{$totalAccountDatas->end_date}}</td>
                                                            <td>{{@$totalAccountDatas->agent->first_name.' '.@$totalAccountDatas->agent->last_name}}</td>


                                                        </tr>
                                                    @empty

                                                    @endforelse
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
        </div>
    </div></div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="ProductEXM" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Edit Account Wallet</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                            <div class="modal-body">
                                <input type='hidden' id='uuid' >
                                <label for="basic-url">Product</label>
                                <div class="input-group mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="basic-addon7">Product</span>
                                    </div>
                                    <select id='product' class='form-control'>
                                        @foreach($savingsproduct as $prodcuts)
                                        <option value='{{$prodcuts->id}}'>{{$prodcuts->savings_product}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                                <button type="button"  class="btn btn-primary" onclick='changeProduct()'>Change</button>
                            </div>
                    </div>
                </div>
            </div>
<script>
    function edit(uuid){
        $('#ProductEXM').modal('show')
        $('#uuid').val(uuid)
    }
    function changeProduct(){
        $id =$('#uuid').val();
        $productID =$('#product').val();
        ajax_operation('put', '/company/customer/saving-product/'+$id, {productID:$productID});
    }
</script>
@endsection
