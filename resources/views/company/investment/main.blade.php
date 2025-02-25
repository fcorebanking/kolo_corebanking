@extends('layouts.app')

@section('content')

    <button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" data-toggle="modal"
        data-target="#exampleModal">
        Add Product &nbsp<i class='fa fa-plus'></i>
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
                                    <th>Product</th>

                                    <th>Interest</th>
                                    <th>Min Invest</th>
                                    <th>Max Invest</th>
                                    <th>Termination</th>

                                    <th>Frequency</th>
                                    <th>Created By</th>
                                    <th>Modified By</th>
                                    <th>Updated At</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody><?php $i = 1; ?>
                                @if (count($product) > 0)
                                    @foreach ($product as $product_data)
                                        <?php $product_data->cover_pic = str_replace('public', 'storage', $product_data->cover_pic); ?>
                                        <tr>
                                            <td>{{ $i++ }}</td>


                                            <td><img src='{{ asset($product_data->cover_pic) }}' width='70'></td>

                                            <td>{{ $product_data->product }}</td>
                                            <td>{{ $product_data->interest_rate }}%</td>

                                            <td>{{ $product_data->minimum_investment }}</td>
                                            <td>{{ $product_data->maximum_investment }}</td>
                                            <td>{{ $product_data->termination_charge }}%</td>


                                            <td><span
                                                    class="shadow-none badge badge-primary">{{ $product_data->frequency_id ? $product_data->investmentfrequency->frequency : 'none' }}</span>
                                            </td>
                                            <td>{{ $product_data->author->first_name }}</td>
                                            <td>{{ $product_data->modified_by ? $product_data->modifier->first_name : 'none' }}
                                            </td>
                                            <td>{{ $product_data->updated_at }}</td>
                                            <td>
                                                {{ $product_data->created_at }}
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class='btn btn-secondary'><svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            onclick="edit_product('{{ $product_data->id }}','{{ $product_data->product }}','{{ $product_data->interest_rate }}','{{ $product_data->frequency_id }}','{{ $product_data->minimum_investment }}','{{ $product_data->maximum_investment }}','{{ $product_data->cover_pic }}','{{ $product_data->duration }}','{{ $product_data->status_id }}','{{ $product_data->termination_charge }}')"
                                                            width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" class="feather feather-edit-2 edit">
                                                            <path
                                                                d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z">
                                                            </path>
                                                        </svg></button>

                                                    <form id='investment_{{ $product_data->id }}'
                                                        action='investment/{{ $product_data->id }}' method='post'>
                                                        @method('delete') {{ csrf_field() }} <button type='button'
                                                            class='btn btn-secondary'
                                                            onclick="return comfirm_to_proceed('investment_{{ $product_data->id }}')"><svg
                                                                xmlns="http://www.w3.org/2000/svg" width="24"
                                                                height="24" viewBox="0 0 24 24" fill="none"
                                                                stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                class="feather feather-x-circle table-cancel">
                                                                <circle cx="12" cy="12" r="10"></circle>
                                                                <line x1="15" y1="9" x2="9"
                                                                    y2="15"></line>
                                                                <line x1="9" y1="9" x2="15"
                                                                    y2="15"></line>
                                                            </svg></button></form>
                                            </td>


                    </div>
                    </td>
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
                <form method='post' action='save-product' enctype="multipart/form-data">
                    @method('PUT')
                    {{ csrf_field() }}
                    <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                    <div class="modal-body">
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="upload ">
                                <input type="file" id="input-file-max-fs" name='cover_pic' class="dropify"
                                    data-default-file="" data-max-file-size="2M" />
                                <p class="mt-2"><i class="flaticon-cloud-upload mr-1"></i> Upload Picture</p>
                            </div>
                        </div>


                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Name</span>
                            </div>
                            <input type="text" class="form-control" id='name' name='name'
                                aria-describedby="basic-addon3" placeholder="product 1..">
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Interest</span>
                            </div>
                            <input type="number" min='0' max='100' step="any" class="form-control"
                                name='interest' id='interest' aria-describedby="basic-addon3" placeholder="50...">
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Min. Investment</span>
                            </div>
                            <input type="text" class="form-control" name='minimum_investment' id='minimum_investment'
                                aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Max. Investment</span>
                            </div>
                            <input type="text" class="form-control" name='maximum_investment' id='maximum_investment'
                                aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Termination Charge%</span>
                            </div>
                            <input type="text" class="form-control" name='termination_charge' id='termination_charge'
                                aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Duration</span>
                            </div>
                            <input type="text" class="form-control" name='duration' id='duration'
                                aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Frequency</span>
                            </div>
                            <select required name='freq' id='freq' class='form-control'>
                                @if (count($freq) > 0)
                                    @foreach ($freq as $status_data)
                                        <option value='{{ $status_data->id }}'>{{ $status_data->frequency }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Status</span>
                            </div>
                            <select required name='status' id='status' class='form-control'>
                                @if (count($status) > 0)
                                    @foreach ($status as $status_data)
                                        <option value='{{ $status_data->id }}'>{{ $status_data->name }}</option>
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
                <form method='post' action='update-product' enctype="multipart/form-data">

                    {{ csrf_field() }}
                    <input type="hidden" class="form-control" id='editid' name='id'>
                    <div class="modal-body">
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="upload ">
                                <input type="file" id="input-file-max-fs" name='cover_pic' class="dropify editpic"
                                    data-max-file-size="2M" />
                                <p class="mt-2"><i class="flaticon-cloud-upload mr-1"></i> Upload Picture</p>
                            </div>
                        </div>


                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Name</span>
                            </div>
                            <input type="text" class="form-control" id='editname' name='name'
                                aria-describedby="basic-addon3" placeholder="product 1..">
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Interest</span>
                            </div>
                            <input type="text" class="form-control" step="any" name='interest' id='editinterest'
                                aria-describedby="basic-addon3" placeholder="50...">
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Min. Investment</span>
                            </div>
                            <input type="text" class="form-control" name='min_withdraw' id='editmin_withdraw'
                                aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Max. Investment</span>
                            </div>
                            <input type="text" class="form-control" name='max_deposit' id='editmax_deposit'
                                aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Termination Charge%</span>
                            </div>
                            <input type="text" class="form-control" name='termination_charge'
                                id='edittermination_charge' aria-describedby="basic-addon3" placeholder="100..."
                                value='0'>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Duration</span>
                            </div>
                            <input type="text" class="form-control" name='duration' id='editduration'
                                aria-describedby="basic-addon3" placeholder="100..." value='0'>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Frequency</span>
                            </div>
                            <select required name='freq' id='editfreq' class='form-control'>
                                @if (count($freq) > 0)
                                    @foreach ($freq as $status_data)
                                        <option value='{{ $status_data->id }}'>{{ $status_data->frequency }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Status</span>
                            </div>
                            <select required name='status' id='editstatus' class='form-control'>
                                @if (count($status) > 0)
                                    @foreach ($status as $status_data)
                                        <option value='{{ $status_data->id }}'>{{ $status_data->name }}</option>
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
        function comfirm_to_proceed(formtodelete) {
            swal({
                title: 'Are you sure?',
                text: "You want to delete this record",
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Proceed',
                padding: '2em'
            }).then(function(result) {
                if (result.value) {

                    $("#" + formtodelete).submit();
                }
            });
            return false;
        }

        function edit_product(id, product, interest, freq, minwith, maxdep, cover, dur, status, edittermination) {
            $('#editexampleModal').modal('show');
            $('#editid').val(id);
            $('#editname').val(product);
            $('#editinterest').val(interest);
            $('#editmin_withdraw').val(minwith);
            $('#editmax_deposit').val(maxdep);
            $('#editduration').val(dur);
            $('#editfreq').val(freq);
            $('#editstatus').val(status);
            $('#edittermination_charge').val(edittermination);

            $(".editpic").removeAttr("data-default-file");
            $(".editpic").attr("data-default-file", document.location.host + '/' + cover);

        }
    </script>
@endsection
