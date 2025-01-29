<div class="widget-content widget-content-area">
    <h3>List of Active Savings</h3>
    <div>
        <div class='container'>
            @if(cando('root'))
            <form>
                <div class="row">
                    <div class="col-md-12">
                        @if ($this->responseStatus == 1)
                            <div class="alert alert-success">
                                {{ $this->response }}
                            </div>
                            @endif
                        @if ($this->responseStatus == 2)
                            <div class="alert alert-danger">
                                {{ $this->response }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="form-row align-items-center">
                    <div class="col-auto">
                        <label for="inputText" class="sr-only">Interest</label>
                        <input type="number" class="form-control mb-2" wire:model="interest" id="interest" placeholder="Enter Interest %">
                    </div>
                    <div class="col-auto">
                        <label for="inputText" class="sr-only">Agent Commission</label>
                        <input type="number" class="form-control mb-2" wire:model="commission" id="commission" placeholder="Enter Charge %">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary mb-2" wire:click='save'>Save</button>
                    </div>
                </div>
            </form>
            @endif

            <div class='row layout-spacing ' style='margin-top:2rem;'>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img  src="{{asset('assets/dashboard/wallet.png')}}" alt="money-bag"  width="50">
                                        </span>
                                    </div>

                                    <div class="balance-info">
                                        <h6>Total Target Amount</h6>
                                        <p class="acc-amount">{{$auth->company->currency->symbol.''.$totalTargetAmount}}</p>
                                    </div>
                                </div>

                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                                    <a href="javascript:void(0);" class="">Tab to view</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img  src="{{asset('assets/dashboard/wallet.png')}}" alt="money-bag"  width="50">
                                        </span>
                                    </div>
                                    <div class="balance-info">
                                        <h6>Current Balance </h6>
                                        <p class='text-center'>{{$auth->company->currency->symbol.''.$totalCurrentBalance}}</p>
                                    </div>
                                </div>

                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                                    <a href='#' class="" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Tab to view</a>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img src="{{asset('assets/dashboard/wallet.png')}}" alt="money-bag"  width="50">
                                        </span>
                                    </div>

                                    <div class="text-center" >
                                            <h6>Expected Balance</h6>
                                        <p class='text-center'>{{$auth->company->currency->symbol.''.$totalExpectedBalance}}</p>
                                    </div>
                                </div>

                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                                    <a href='#' class="" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Tab to view</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-five">
                        <div class="widget-content">
                            <div class="account-box">

                                <div class="info-box">
                                    <div class="icon">
                                        <span>
                                            <img src="{{asset('assets/dashboard/wallet.png')}}" alt="money-bag"  width="50">
                                        </span>
                                    </div>

                                    <div class="balance-info">
                                        <h6>Total Account</h6>
                                        <p class='text-center'>{{$totalAccount}}</p>
                                    </div>
                                </div>
                                <div class="card-bottom-section">
                                    <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                                    <a href='#' class="" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Tab to view</a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
              </div>
            <div class="row">
                <div class="col-md-12">
                    @if ($this->responseStatus == 1)
                        <div class="alert alert-success">
                            {{ $this->response }}
                        </div>
                        @endif
                    @if ($this->responseStatus == 2)
                        <div class="alert alert-danger">
                            {{ $this->response }}
                        </div>
                    @endif
                </div>
            </div>
            @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if(session('fail'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

            @if ($accountRepaymentData) <div class='text-left'><button wire:click="back" class='btn btn-info '><i class='fa fa-back'><< </i> Back</button> </div>@endif
            @if(!$hideTable)
            <form class='container ' method='post' action='/create-target-saving'>
                {{ csrf_field() }}

                <div class='m-2'>
                <div class='form-group'>
                    <label>Target Name</label>
                    <input type='text' class='form-control' name="name" />
                </div>
                <div class='form-group'>
                    <label>Duration</label>
                    <input type='number' class='form-control' name="duration" />
                </div>
                <div class='form-group'>
                    <label>Target</label>
                    <input type='number' class='form-control' name="target" />
                </div>
                <div class='form-group'>
                    <label>Frequency</label>
                    <select  class='form-control' name="frequency">
                        <option value='Daily'>Daily</option>
                        <option value='Weekly'>Weekly</option>
                        <option value='Monthly'>Monthly</option>
                    </select>
                </div>
                <div class='form-group'>
                    <label>Periods</label>
                    <select  class='form-control' name="selectPeriod">
                        <option value='weeks'>Weeks</option>
                        <option value='months'>Months</option>
                        <option value='years'>Years</option>
                    </select>
                </div>
                <div class='form-group'>
                    <label>Customer</label>
                    <select name='customer' id='customerx'  class='form-control form-small' style='margin:5px'>

                    </select>
                </div>
                <div class='form-group'>
                    <label>Set as Agent</label>
                    <select  id='staff_select' name='agent_id' class='form-control form-small ' style='margin:5px' >
                        <option value="">None</option>
                        @foreach ($staff as $staffData)
                            <option value="{{$staffData->id}}">{{$staffData->first_name}} {{$staffData->last_name}}</option>
                        @endforeach
                    </select>
                </div>
                <button class='btn btn-primary' >+Add</button>
            </div>

            </form>
            <input type='search' name='search' class='form-control m-5' wire:onchange='loadData'  wire:model="search" placeholder="Search e.g email,phone, account number or name">

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
                            <th>Action</th>
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

                                    <td>
                                        <button class='btn btn-info' wire:loading.attr="disabled" wire:target="performOperation" wire:click="repaymentData({{$totalAccountDatas->id}})">View Repayment</button>

                                        @if (cando('root'))
                                        <button class='btn btn-warning' wire:loading.attr="disabled" wire:target="performOperation" wire:click="liquedateBTN({{$totalAccountDatas->id}})">Liquedate</button>

                                        @endif
                                    </td>
                                </tr>
                            @empty

                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
            @endif
            @if ($accountRepaymentData)
            <div class='container'>
                <form action='/change-target-saving-agent' method='post'>
                    {{ csrf_field() }}
                <label>Change as Agent</label>
                <select  id='agent_select' name='agent_id' class='form-control form-small ' style='margin:5px' >
                    <option value="">None</option>
                    @foreach ($staff as $staffData)
                        <option value="{{$staffData->id}}">{{$staffData->first_name}} {{$staffData->last_name}}</option>
                    @endforeach
                </select>
                <input type='hidden' name='selectedSaving'  wire:model="selectedTargetSaving">
                <button class='btn btn-primary' >+Change Agent</button>
            </form>
            </div>
            <div class="table-responsive" style="overflow-x: scroll">
                <table class="table mb-4 contextual-table">
                    {{-- <table id="html5-extension" class="table table-hover non-hover" style="width:100%"> --}}

                    <thead>
                        <tr class="">
                            <th class="text-center">#</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Method</th>

                        </tr>
                    </thead>
                    <tbody>
                        @if ($accountRepaymentData)
                            @foreach ($accountRepaymentData as $accountRepaymentDatas)
                            <tr @if(($accountRepaymentDatas->status == 'pending')) class='table->warning' @else class='table=primary'@endif>
                                {{-- <tr  class="table-danger"> --}}
                                <td class="text-center">{{$accountRepaymentDatas->uuid}}</td>
                                <td>{{$accountRepaymentDatas->customersaving->name}}</td>
                                <td>{{$accountRepaymentDatas->amount}}</td>
                                <td>{!! ($accountRepaymentDatas->paymentDate < date('Y-m-d') && $accountRepaymentDatas->status =='pending') ?'<span class="badge badge-danger">Missed</span>':(($accountRepaymentDatas->status =='complete')?'<span class="badge badge-success">'.$accountRepaymentDatas->status.'</>':'<span class="badge badge-warning">'.$accountRepaymentDatas->status.'</div>') !!}</td>
                                <td>{{$accountRepaymentDatas->paymentDate}}</td>
                                <td>{{$accountRepaymentDatas->method}}</td>
                            </tr>
                            @endforeach
                        @endif

                    </tbody>
                </table>
            </div>
            @endif

</div>
</div>
{{-- @push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        Livewire.on('operationSuccessful', () => {
            // Reset the button state after successful operation
            document.querySelector('[wire:click="performOperation"]').removeAttribute('disabled');
        });
    });
</script>
@endpush --}}
