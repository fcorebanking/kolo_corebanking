@extends('layouts.app')

@section('content')


        <div class="layout-px-spacing">
            <div class="row layout-top-spacing">
                <button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" onclick='addFunct()'>
                    Add Product &nbsp<i class='fa fa-plus'></i></button>
                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                    <div class="widget-content widget-content-area br-6">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif


                        <div class="table-responsive mb-4 mt-4">
                            <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>StartDate</th>
                                        <th>EndDate</th>
                                        <th>Maximal</th>
                                        <th>Minimal</th>
                                        <th>Circleout Charge%</th>
                                        <th>Withdrawal Charge%</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($esusuproduct)
                                        @foreach ($esusuproduct as $esusuproductData)
                                            <tr>
                                                <td>{{$esusuproductData->name}}</td>
                                                <td>{{$esusuproductData->start_date}}</td>
                                                <td>{{$esusuproductData->end_date}}</td>
                                                <td>{{$esusuproductData->maximal}}</td>
                                                <td>{{$esusuproductData->minimun}}</td>
                                                <td>{{$esusuproductData->circle_out_charge}}%</td>
                                                <td>{{$esusuproductData->withdraw_out_charge}}%</td>
                                                <td>{{$esusuproductData->status}}</td>
                                                <td>{{$esusuproductData->created_at}}</td>
                                                <td><button class='btn btn-warning' onclick='editFunct("{{$esusuproductData->id}}", "{{$esusuproductData->name}}", "{{$esusuproductData->start_date}}", "{{$esusuproductData->end_date}}", "{{$esusuproductData->minimun}}","{{$esusuproductData->maximal}}","{{$esusuproductData->status}}","{{$esusuproductData->circle_out_charge}}","{{$esusuproductData->withdraw_out_charge}}","{{$esusuproductData->t_c}}","{{$esusuproductData->agent_commission}}","{{$esusuproductData->description}}" )'><i class="fa fa-edit"></i></button></td>
                                            </tr>
                                        @endforeach

                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Food Stash Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form method='post' action="/company/esusu/schedule/product">
                    {{ csrf_field() }}
                    <input type="hidden" class="form-control" name='editid' id='editid' >

                    <div class="modal-body">
                        <label for="basic-url">Name</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Name</span>
                            </div>
                            <input type="text" class="form-control" name='name' id='name' aria-describedby="basic-addon3" placeholder="...">
                        </div>
                        <label for="basic-url">Start Date</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Start Date</span>
                            </div>
                            <input type='date' class="form-control" name='start_date' id='start_date' />
                        </div>
                        <label for="basic-url">End Date</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">End Date</span>
                            </div>
                            <input type='date' class="form-control" name='end_date' id='end_date' />
                        </div>

                        <label for="basic-url">Circle Out Charge</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Circle Out Charge%</span>
                            </div>
                            <input type='number' class="form-control" name='circle_out_charge' id='circle_out_charge' />
                        </div>
                        <label for="basic-url">Withdraw out Charge</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Withdraw out Charge%</span>
                            </div>
                            <input type='number' class="form-control" name='withdraw_out_charge' id='withdraw_out_charge' />
                        </div>
                        <label for="basic-url">Agent Commission</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Agent Commission%</span>
                            </div>
                            <input type='number' class="form-control" name='agent_commission' id='agent_commission' />
                        </div>
                        <label for="basic-url">Minimal</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Minimun</span>
                            </div>
                            <input type='number' class="form-control" name='minimun' id='minimun' />
                        </div>
                        <label for="basic-url">Maximal</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Maximal</span>
                            </div>
                            <input type='number' class="form-control" name='maximal' id='maximal' />
                        </div>
                        <label for="basic-url">Status</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Status</span>
                            </div>
                            <select class="form-control" id='status' name='status'>
                                <option value='pending'>Pending</option>
                                <option value='active'>Active</option>
                            </select>
                        </div>
                        {{--    --}}
                        <label for="basic-url">Description</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Description</span>
                            </div>
                            <textarea class="form-control" name='description' id='description' ></textarea>
                        </div>
                        <label for="basic-url">Terms and condition</label>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Terms and condition</span>
                            </div>
                            <textarea class="form-control" name='t_c' id='t_c' ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                        <button type="submit"  class="btn btn-primary" >Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


@endsection
