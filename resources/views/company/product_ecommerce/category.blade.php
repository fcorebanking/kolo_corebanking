@extends('layouts.app')

@section('content')


                                        <div class="layout-px-spacing">
                                            <div class="row layout-top-spacing">
                                                <button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#exampleModal">
                                                    Add Category &nbsp<i class='fa fa-plus'></i></button>
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
                                                                        <th>Tag</th>
                                                                        <th>Created At</th>
                                                                        <th>Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php $sn=0; ?>
                                                                    @if ($data)
                                                                        @foreach ($data as $datas)
                                                                            <?php $sn++; ?>
                                                                            <tr><td>{{$sn}}</td><td>{{$datas->name}}</td><td>{{$datas->tag}}</td><td>{{$datas->created_at}}</td><td>
                                                                                <div class="dropdown">
                                                                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                                      Action
                                                                                    </button>
                                                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                                                      <a class="dropdown-item" href="#" onclick='edit("{{$datas->id}}")'>Edit</a>
                                                                                      <a class="dropdown-item" href="#"  title='Coin with linked wallet cannot be deleted'>
                                                                                        <form id="delete-form" method="POST" action='/company/ecommerce/delete-setting/{{$datas->id}}'>
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
                <h5 class="modal-title" id="exampleModalLabel">Add Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form action='/company/ecommerce/save-category' method='post'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">

                <div class="modal-body">
                    <label for="basic-url">Name</label>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" name='name' class="form-control" id='name' aria-describedby="basic-addon3" placeholder="Name...">
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
                <h5 class="modal-title" id="exampleModalLabel">Update Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form action='/company/ecommerce/update-category' method='post'>
            <input type = "hidden" class="token" name = "_token" value = "<?php echo csrf_token(); ?>">
            <input type = "hidden" id="catID" name = "catID" >


            <div class="modal-body">
                <label for="basic-url">Name</label>
                <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon7">Name</span>
                    </div>
                    <input type="text" name='edit_name' class="form-control" id='edit_name' aria-describedby="basic-addon3" placeholder="Name...">
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
    function edit(id){
        $('#editexampleModal').modal('show');
            $.ajax({
            url: '/company/ecommerce/view/' + id,
            type: 'post',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                $('#edit_name').val(data.name);
                $('#catID').val(data.id);
            }
        });
    }

</script>
@endsection
