@extends('layouts.app')

@section('content')


<div class="layout-px-spacing">
<div class="row layout-top-spacing">
    <div class="col-xl-3 col-lg-3 col-sm-6  layout-spacing">
    <button type="button" style="margin:10px;" class="btn btn-info mb-2 mr-2" data-toggle="modal" data-target="#exampleModal">
        Add Crypto &nbsp<i class='fa fa-plus'></i></button>
    </div>
    <div class="col-xl-3 col-lg-3 col-sm-6  layout-spacing">
        <div class="input-group mb-4">
            <div class="input-group-prepend">
                <span class="input-group-text" id="basic-addon7">Sell Naira-USD</span>
            </div>
            <input type='number' name='naira_to_usd' id='naira_to_usd' class='form-control' placeholder="Enter Naira to Crypto Rate" value='{{@$rate->naira_to_USD}}'>
        </div>
    </div>
    <div class="col-xl-3 col-lg-3 col-sm-6  layout-spacing">
        <div class="input-group mb-4">
            <div class="input-group-prepend">
                <span class="input-group-text" id="basic-addon7">Buy Naira-USD</span>
            </div>
            <input type='number' name='buy_naira_to_usd' id='buy_naira_to_usd' class='form-control' placeholder="Enter Naira to Crypto Rate" value='{{@$rate->buy_rate}}'>

        </div>
    </div>
    <div class="col-xl-3 col-lg-3 col-sm-6  layout-spacing">
        <button class='btn btn-primary' onclick='SaveCryptoRate()'>Save</button>
    </div>

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
                        <th>Icon</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Sell Rate</th>
                            <th>Buy Rate</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn=0; ?>
                        @if ($coins)
                            @foreach ($coins as $coinsData)
                                <?php $sn++; ?>
                                <tr><td>{{$sn}}</td><td><img src='{{$coinsData->icon_link}}' width='30'></td><td>{{$coinsData->name}}</td><td>{{$coinsData->code}}</td><td>{{$coinsData->rate}}</td><td>{{$coinsData->buy_rate}}</td><td>{{@$coinsData->status->name}}</td><td>{{$coinsData->created_at}}</td><td>
                                    <div class="dropdown">
                                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                          Action
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                          <a class="dropdown-item" href="#" onclick='editCrypto("{{$coinsData->id}}}")'>Edit</a>
                                          <a class="dropdown-item" href="#" onclick='deleteCrypto("{{$coinsData->id}}}")' title='Coin with linked wallet cannot be deleted'>Delete</a>
                                        </div>
                                      </div>
                                </td></tr>
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
                    <label for="basic-url">Naira to USDT Rate</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Sell Rate</span>
                        </div>
                        <input type="text" name='rate' class="form-control" id='rate' aria-describedby="basic-addon3" placeholder="2.0...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Buy Rate</span>
                        </div>
                        <input type="text" name='buy_rate' class="form-control" id='buy_rate' aria-describedby="basic-addon3" placeholder="2.0...">
                    </div>
                    <label for="basic-url">Crypto Icon</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Icon</span>
                        </div>
                        <input type="text" name='icon_link' class="form-control" id='icon_link' aria-describedby="basic-addon3" placeholder="https://...png">
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
                        {{-- <option value='NGN'>NGN</option> --}}
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



<div class="modal fade" id="editexampleModal" tabindex="-1" role="dialog" aria-labelledby="editexampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editexampleModalLabel">Edit Coin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form action='/company/coinremitter/updatecoin' method='post'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">
            <input type="hidden" name='coin_ID' class="form-control" id='coin_ID' >

                <div class="modal-body">

                    <label for="basic-url">Coin Name</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" name='name' class="form-control" id='editname' aria-describedby="basic-addon3" placeholder="BTC...">
                    </div>
                    <label for="basic-url">Naira to USDT Rate</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Sell Rate</span>
                        </div>
                        <input type="text" name='rate' class="form-control" id='editrate' aria-describedby="basic-addon3" placeholder="2.0...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Buy Rate</span>
                        </div>
                        <input type="text" name='buy_rate' class="form-control" id='editbuy_rate' aria-describedby="basic-addon3" placeholder="2.0...">
                    </div>
                    <label for="basic-url">Crypto Icon</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Icon</span>
                        </div>
                        <input type="text" name='icon_link' class="form-control" id='editicon_link' aria-describedby="basic-addon3" placeholder="https://...png">
                    </div>
                    <label for="basic-url">Select Coin</label>
                    <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Coin</span>
                    </div>
                    <select class="form-control" id='editcode' name='code' aria-label="With coin">
                        @foreach($credentials as $key =>$value)
                            <option value='{{$key}}'>{{$key}}</option>
                        @endforeach
                        {{-- <option value='NGN'>NGN</option> --}}
                    </select>
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
                    <button type="submit"  class="btn btn-primary" onclick='btnupdate()'>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function SaveCryptoRate(){
        $rate = $("#naira_to_usd").val();
        $buyrate = $("#buy_naira_to_usd").val();
        ajax_operation('post', '/company/coinremitter/storerate', {rate:$rate,buy_rate:$buyrate});
    }
    function editCrypto(id){
        $('#editexampleModal').modal('show');
            $.ajax({
            url: '/company/coinremitter/edit/' + id,
            type: 'post',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                $('#editname').val(data.name);
                $('#editrate').val(data.rate);
                $('#editbuy_rate').val(data.buy_rate);
                $('#coin_ID').val(data.id);
                $('#editicon_link').val(data.icon_link);
                $('#editcode').val(data.code).change();
                //
                $('#editstatus_id').val(data.status_id).change();
            }
        });
    }
    function deleteCrypto($id){
        ajax_operation('delete', '/company/coinremitter/deletecoin/'+$id, {});
    }
</script>
@endsection
