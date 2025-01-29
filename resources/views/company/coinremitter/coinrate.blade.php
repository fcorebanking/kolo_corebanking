@extends('layouts.app')

@section('content')


                                        <div class="layout-px-spacing">
                                            <div class="row layout-top-spacing">
                                                <button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#exampleModal">
                                                    Add Crypto &nbsp<i class='fa fa-plus'></i></button>
                                                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                                                    @if($errors->any())
                                                    <h4>{{$errors->first()}}</h4>
                                                    @endif
                                                    @if (\Session::has('success'))
                                                    <div class="alert alert-success">
                                                        <h4>{!! \Session::get('success') !!}</h4>
                                                    </div>
                                                    @endif


                                                    <div class="widget-content widget-content-area br-6">
                                                        <div class="table-responsive mb-4 mt-4">
                                                            <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                                                <thead>
                                                                    <tr>
                                                                    <th>S/N</th>
                                                                        <th>Name</th>
                                                                        <th>Code</th>
                                                                        <th>Created At</th>
                                                                        <th>Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php $sn=0; ?>
                                                                    @if ($coins)
                                                                        @foreach ($coins as $coinsData)
                                                                            <?php $sn++; ?>
                                                                            <tr><td>{{$sn}}</td><td>{{$coinsData->name}}</td><td>{{$coinsData->code}}</td><td>{{$coinsData->created_at}}</td><td>action</td></tr>
                                                                        @endforeach
                                                                    @endif
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
           <!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Coin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form action='/company/coinremitter/storecoin' method='post'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">

                <div class="modal-body">

                    <label for="basic-url">Coin Name</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" name='name' class="form-control" id='name' aria-describedby="basic-addon3" placeholder="BTC...">
                    </div>
                    <label for="basic-url">Select Coin</label>
                    <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Coin</span>
                    </div>
                    <select class="form-control" id='code' name='code' aria-label="With coin">
                        @foreach($credentials as $key =>$value)
                            <option value='{{$key}}'>{{$key}}</option>
                        @endforeach
                    </select>
                    </div>
                    <label for="basic-url">Status</label>
                    <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Status</span>
                    </div>
                    <select class="form-control" id='status_id' name='status_id' aria-label="With status_id">
                        <option value='2'>Pending</option>
                        <option value='1'>Active</option>
                    </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit"  class="btn btn-primary" onclick='btnadd()'>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection
