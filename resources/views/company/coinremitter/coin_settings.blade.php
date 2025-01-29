@extends('layouts.app')

@section('content')


                                        <div class="layout-px-spacing">
                                            <div class="row layout-top-spacing">
                                                <button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#exampleModal">
                                                    Add Crypto Settings &nbsp<i class='fa fa-plus'></i></button>
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
                                                                        <th>ApiKey</th>
                                                                        <th>Created At</th>
                                                                        <th>Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php $sn=0; ?>
                                                                    @if ($coins)
                                                                        @foreach ($coins as $coinsData)
                                                                            <?php $sn++; ?>
                                                                            <tr><td>{{$sn}}</td><td>{{$coinsData->code}}</td><td>{{$coinsData->api_key}}</td><td>{{$coinsData->author->username}}</td><td>{{$coinsData->created_at}}</td><td>
                                                                                <div class="dropdown">
                                                                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                                      Action
                                                                                    </button>
                                                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                                                      <a class="dropdown-item" href="#" onclick='editCrypto("{{$coinsData->id}}}")'>Edit</a>
                                                                                      <a class="dropdown-item" href="#"  title='Coin with linked wallet cannot be deleted'>
                                                                                        <form id="delete-form" method="POST" action='/company/coinremitter/delete-setting/{{$coinsData->id}}'>
                                                                                            {{ csrf_field() }}
                                                                                            {{ method_field('DELETE') }}
                                                                                            <button type='submit' class='btn btn-danger'><i class='fa fa-trash'></i></button>
                                                                                        </form>
                                                                                      </a>
                                                                                    </div>
                                                                                  </div></td></tr>
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
            <form action='/company/coinremitter/save-settings' method='post'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">

                <div class="modal-body">
                    <div class='alert alert-warning'>Note: Coin Code should be the proper acronym of the coin name in Coinremitter else this API will not work with the mobile app and should be in uppercase</div>
                    <label for="basic-url">Coin Code</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Crypto Name</span>
                        </div>
                        <input type="text" name='code' class="form-control" id='code' aria-describedby="basic-addon3" placeholder="BTC...">
                    </div>

                    <label for="basic-url">API Key</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">API KEY</span>
                        </div>
                        <input type="text" name='api_key' class="form-control" id='api_key' aria-describedby="basic-addon3" placeholder="AYEioisodui362...">
                    </div>
                    <label for="basic-url">Password</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Password</span>
                        </div>
                        <input type="text" name='password' class="form-control" id='password' aria-describedby="basic-addon3" placeholder="***">
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
                    <button type="submit"  class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="editexampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Coin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form action='/company/coinremitter/update-settings' method='post'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">
            <input type = "hidden" class="key" name = "coin_ID"  id='coin_ID' >

                <div class="modal-body">

                    <label for="basic-url">Coin Name</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Crypto Name</span>
                        </div>
                        <input type="text" name='code' class="form-control" id='editcode' aria-describedby="basic-addon3" placeholder="BTC...">
                    </div>

                    <label for="basic-url">API Key</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">API KEY</span>
                        </div>
                        <input type="text" name='api_key' class="form-control" id='editapi_key' aria-describedby="basic-addon3" placeholder="AYEioisodui362...">
                    </div>
                    <label for="basic-url">Password</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Password</span>
                        </div>
                        <input type="text" name='password' class="form-control" id='editpassword' aria-describedby="basic-addon3" placeholder="***">
                    </div>
                    <label for="basic-url">Status</label>
                    <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Status</span>
                    </div>
                    <select class="form-control" id='editstatus_id' name='status_id' aria-label="With status_id">
                        <option value='2'>Pending</option>
                        <option value='1'>Active</option>
                    </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit"  class="btn btn-primary" >Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function editCrypto(id){
        $('#editexampleModal').modal('show');
            $.ajax({
            url: '/company/coinremitter/edit-setting/' + id,
            type: 'post',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                $('#editcode').val(data.code);
                $('#editapi_key').val(data.api_key);
                $('#editpassword').val(data.password);
                $('#coin_ID').val(data.id);
                $('#editstatus_id').val(data.status_id).change();
            }
        });
    }

</script>
@endsection
