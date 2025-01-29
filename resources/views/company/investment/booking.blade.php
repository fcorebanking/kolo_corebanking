@extends('layouts.app')

@section('content')


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

                        <th>Action</th>

                        </thead>
                        <tbody>

                        </tbody>
                    </table>


           <!-- Modal -->


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
                        <input type="number" class="form-control"   id='amount' name='amount' aria-describedby="basic-addon3"
                            placeholder="amount" onkeyup="calculateinvestment()">

                    </div>
                    <span class='validation-text'></span>


                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Account Type</span>
                        </div>
                        <select  name='account_type'  id='account_type' class='form-control' >

                        </select>

                    </div>
                    <p>
                    <span id='vaccinfo'>


                    </span>
                    </p>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Investment Product</span>
                        </div>
                        <select  name='investmentproduct'  id='investmentproduct' class='form-control' onchange='calculateinvestment()'>

                        </select>

                    </div>

                    <span class='validation-text'></span>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Status</span>
                        </div>
                        <select required name='status' id='status' class='form-control'>
                            @if(count($status) > 0)
                                @foreach($status as $status_data)
                                <option value='{{$status_data->id}}'>{{$status_data->name}}</option>

                                @endforeach

                            @endif
                        </select>
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >Start Date (Optional)</span>
                        </div>
                        <input type="date" class="form-control" id='created_at'  onchange="calculateinvestment()" name='created_at' aria-describedby="basic-addon3"
                            placeholder="Date" >

                    </div>
            <div class="card component-card_8">
            <div class="card-body">

                <div class="progress-order">
                    <div class="progress-order-header">
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-12">
                                <h6>Plan Rate</h6>
                            </div>
                            <div class="col-md-6 pl-0 col-sm-6 col-12 text-right">
                                <span class="badge badge-info planname">IN PROGRESS</span>
                            </div>
                        </div>
                    </div>

                    <div class="progress-order-body">
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <ul class=" mb-0 mb-3" style=" list-style-type: none;">
                                    <li>Maximum Amount - <span  class='max'></span></li>
                                    <li >Minimum Amount - <span  class='min'></span></li>
                                    <li >Start Date - <span  class='start'></span></li>
                                    <li >Maturity Date - <span  class='end'></span></li>
                                    <li >Expected Amount - <span  class='totalcash'></span></li>
                                    <li >Amount Interest - <span  class='interest'></span></li>
                                    <li >Termination fee- <span  class='termination'></span></li>
                                    <li >Plan Interest - <span  class='plan_interest'></span></li>
                                    <li>Duration|Frequency -<span   class='duration'></span> <span   class='plantype'></span></li>

                                </ul>
                            </div>

                            <div class="col-md-12 text-right">
                                <span class=" p-o-percentage mr-4 "><span class='percentvalue'></span>%</span>
                                <div class="progress p-o-progress mt-2">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id='percentvalue'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" class="btn btn-primary" id='btn-investment'>Book</button>
                </div>
            </form>



        </div>
    </div>
</div>


@endsection

