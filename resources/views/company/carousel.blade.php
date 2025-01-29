@extends('layouts.app')

@section('content')

<button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal"
    data-target="#exampleModal">
    Add Slide &nbsp<i class='fa fa-plus'></i>
</button>
<div class="layout-px-spacing">
                <div class="row layout-top-spacing">
                    <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                        <div class="widget-content widget-content-area br-6">
                            <div class="table-responsive mb-4 mt-4">
                                <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                        <th>S/N</th>
                                            <th>IMG</th>
                                            <th>Link</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Created At</th>
                                            <th>Edit</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($carousel)
                                        @php $sn=0; @endphp
                                        @foreach($carousel as $carouselData)
                                        @php $sn++; @endphp
                                        <tr>
                                            <td>{{$sn}}</td>
                                            <td><a href="{{$carouselData->image_url}}" target="_blank"><img  width='30' src='{{$carouselData->image_url}}'></a></td>
                                            <td>{{$carouselData->link_url}}</td>
                                            <td>{{($carouselData->status_id == 1)?"Active":"Disabled"}}</td>
                                            <td>{{@$carouselData->author->username}}</td>
                                            <td>{{@$carouselData->created_at}}</td>
                                            <td><button onclick='edit_product("{{$carouselData->id}}","{{$carouselData->link_url}}","{{$carouselData->status_id}}")' class='btn btn-warning'>Edit</button></td>
                                                <td><form action='/company/delete-slide/{{$carouselData->id}}' method='post'>{{ csrf_field() }}<button type='submit' class='btn btn-warning'>Delete</button></form></td>
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
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Product</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form  method='post' action='' enctype="multipart/form-data" >
                               {{ csrf_field() }}
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                <div class="modal-body">
                <div class="col-xl-12 col-lg-12 col-md-12">
                    <div class="upload ">
                        <input type="file" id="input-file-max-fs" name='cover_pic' class="dropify" data-default-file="" data-max-file-size="2M" />
                        <p class="mt-2"><i class="flaticon-cloud-upload mr-1"></i> Upload Picture</p>
                    </div>
                </div>


                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Link</span>
                        </div>
                        <input type="text" class="form-control" id='img_link' name='img_link' aria-describedby="basic-addon3"
                            placeholder="Anchor Link">
                    </div>

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
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editexampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Image</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form  method='post' action='/company/update-advert-banner' enctype="multipart/form-data" >

                               {{ csrf_field() }}
                <input type="hidden" class="form-control" id='reqID' name='id' >
                <div class="modal-body">
                    <div class="col-xl-12 col-lg-12 col-md-12">
                        <div class="upload ">
                            <input type="file" id="input-file-max-fs" name='cover_pic' class="dropify" data-default-file="" data-max-file-size="2M" />
                            <p class="mt-2"><i class="flaticon-cloud-upload mr-1"></i> Upload Picture</p>
                        </div>
                    </div>


                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7Link">Link</span>
                            </div>
                            <input type="text" class="form-control" id='editimg_link' name='img_link' aria-describedby="basic-addon3"
                                placeholder="Anchor Link">
                        </div>

                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Status</span>
                            </div>
                            <select required name='status' id='editstatus' class='form-control'>
                                @if(count($status) > 0)
                                @foreach($status as $status_data)
                                <option value='{{$status_data->id}}'>{{$status_data->name}}</option>

                                @endforeach

                                @endif
                            </select>
                        </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function comfirm_to_proceed(formtodelete){
        swal({
        title: 'Are you sure?',
        text: "You want to delete this record",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        padding: '2em'
        }).then(function(result) {
            if (result.value) {

                   $("#"+formtodelete).submit();
            }
        });
        return false;
    }
    function edit_product(id,link,status){
        $('#editexampleModal').modal('show');
        $('#reqID').val(id);
        $('#editstatus').val(status).change();
        $('#editimg_link').val(link);
    }

</script>
@endsection
