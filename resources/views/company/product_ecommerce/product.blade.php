@extends('layouts.app')

@section('content')

<button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal"
    data-target="#exampleModal">
    Add Product &nbsp<i class='fa fa-plus'></i>
</button>
<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            @if(session()->has('success'))
                                <div class="alert alert-success">
                                    {{ session()->get('success') }}
                                </div>
                                @endif
                                @if (count($errors) > 0)
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>
                            <tr>
                            <th>S/N</th>
                                <th>IMG</th>
                                <th>Name</th>
                                <th>Amount</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Category</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                    <th>Action</th>
                            </tr>
                        </thead>
                        <tbody><?php $i =1; ?>
                            @if(count($product) > 0)
                                @foreach($product as $product_data)
                                <?php $product_data->cover_pic = str_replace('public','storage',$product_data->cover_pic); ?>
                                <tr>
                                <td>{{$i++}}</td>
                                    <td><img src='{{asset($product_data->cover_img)}}' width='30' ></td>
                                    <td>{{$product_data->name}}</td>
                                    <td>{{$product_data->amount}}</td>
                                    <td>{{$product_data->quantity}}</td>
                                    <td><span class="shadow-none badge badge-primary">{{$product_data->status->name}}</span></td>
                                    <td>{{$product_data->author->first_name.' '.$product_data->author->last_name}}</td>
                                    <td>{{@$product_data->category->name}}</td>
                                    <td>{{$product_data->created_at}}</td>

                                    <td>
                                        <div class="btn-group">
                                        <button class='btn btn-secondary' ><svg xmlns="http://www.w3.org/2000/svg" onclick="edit_product({{$product_data->id}})"  width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-2 edit"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg></button>
                                    <form id='ecommerce_{{$product_data->id}}' action='/company/ecommerce/product/{{$product_data->id}}'   method='post'> @method('delete') {{ csrf_field() }} <button type='button' class='btn btn-secondary' onclick="return comfirm_to_proceed('ecommerce_{{$product_data->id}}')"><svg xmlns="http://www.w3.org/2000/svg"  width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle table-cancel"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg></button></form></td>
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
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form  method='post' action='{{url("company/ecommerce/product")}}' enctype="multipart/form-data" >
                    @method('PUT')
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
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" class="form-control" id='name' name='name' aria-describedby="basic-addon3"
                            placeholder="product 1..">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Sale Price</span>
                        </div>
                        <input type="number" class="form-control" name='amount' id='amount' aria-describedby="basic-addon3"
                            placeholder="5000...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Actual Price</span>
                        </div>
                        <input type="number" class="form-control" name='actual_amount' id='actual_amount' aria-describedby="basic-addon3"
                            placeholder="5000...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Delivery Fee</span>
                        </div>
                        <input type="number" class="form-control" name='delivery_fee' id='delivery_fee' aria-describedby="basic-addon3"
                            placeholder="5...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Quantity</span>
                        </div>
                        <input type="number" class="form-control" name='quantity' id='quantity' aria-describedby="basic-addon3"
                            placeholder="5...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Description</span>
                        </div>
                        <textarea class="form-control" name='description' id='description' placeholder='Description'></textarea>
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
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Category</span>
                        </div>
                        <select required name='categories' id='categories' class='form-control'>
                            @if(count($categories) > 0)
                            @foreach($categories as $categorie)
                                <option value='{{$categorie->id}}'>{{$categorie->name}}</option>
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
                <h5 class="modal-title" id="exampleModalLabel">Edit Product</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id='editform'  method='post' action='{{url("/company/ecommerce/product/update")}}' enctype="multipart/form-data" >
                {{ csrf_field() }}

                <input type="hidden" class="form-control" id='editid' name='editid' required>
                <div class="modal-body">
                <div class="col-xl-12 col-lg-12 col-md-12">

                    <div class="upload editpic">
                        <input type="file" id="input-file-max-fs" name='cover_pic'  class="dropify editpic"    data-max-file-size="2M" />
                        <p class="mt-2"><i class="flaticon-cloud-upload mr-1"></i> Upload Picture</p>
                    </div>
                </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Name</span>
                        </div>
                        <input type="text" class="form-control" id='editname' name='name' aria-describedby="basic-addon3"
                            placeholder="product 1..">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Actual Price</span>
                        </div>
                        <input type="number" class="form-control" name='actual_amount' id='actualeditactual_amount' aria-describedby="basic-addon3"
                            placeholder="5000...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Sale Price</span>
                        </div>
                        <input type="text" class="form-control" name='amount' id='editamount' aria-describedby="basic-addon3"
                            placeholder="50...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Quantity</span>
                        </div>
                        <input type="number" class="form-control" name='quantity' id='editquantity' aria-describedby="basic-addon3"
                            placeholder="5...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Delivery Fee</span>
                        </div>
                        <input type="number" class="form-control" name='delivery_fee' id='editdelivery_fee' aria-describedby="basic-addon3"
                            placeholder="5...">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Description</span>
                        </div>
                        <textarea class="form-control" name='description' id='editdescription' placeholder='Description'></textarea>
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
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Category</span>
                        </div>
                        <select required name='categories' id='edit_categories' class='form-control'>
                            @if(count($categories) > 0)
                            @foreach($categories as $categorie)
                                <option value='{{$categorie->id}}'>{{$categorie->name}}</option>
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
    function edit_product(id){
        $('#editform').animate({ opacity: .4 });

        $('#editexampleModal').modal('show');
        $.ajax({
        url: '/company/ecommerce/product/edit/' + id,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('#editid').val(id);
            $('#editname').val(data.name);
            $('#editdescription').text(data.description);
            $('#editdelivery_fee').val(data.delivery_fee);

            $('#editamount').val(data.amount);
            $('#editstatus').val(data.status_id).change();
            $(".editpic").removeAttr("data-default-file");
            $(".editpic").attr("data-default-file",data.cover_img);
            $('#actualeditactual_amount').val(data.actual_amount);
            $('#editquantity').val(data.quantity);
            $('#editform').animate({ opacity: 1 });
            $('#edit_categories').val(data.ecommerce_categorie_id).change();
        }
    });

    }
</script>
@endsection
