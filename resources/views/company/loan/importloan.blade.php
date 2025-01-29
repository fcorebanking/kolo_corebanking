@extends('layouts.app')

@section('content')

<style>
    #personal_account:hover {
        cursor: pointer;
        border: 2px solid green;
    }

    #cooperate_account:hover {
        cursor: pointer;
        border: 2px solid green;
    }

    .activated {
        border: 2px solid green;
    }
</style>


<div class="col-md-12 mx-auto ">
    <div class="p-3"></div>



    @if (\Session::has('message'))
        @if(\Session::get('status'))
            <div class="alert alert-info shadow">
                @else
            <div class="alert alert-danger shadow">
        @endif
            {!! \Session::get('message') !!}
        </div>
    @endif
</div>

<div class="p-3"></div>

<div class="col-lg-12 layout-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12 py-5">
                    <h4>Import Beyond Customer Loan Account</h4>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area">
            <form action="{{url('company/loan/bulk-upload')}}"  enctype="multipart/form-data" method="post">
                @csrf
                <div id="example-basici">


                    <section>

                        <div class="container">
                            <h4>How to import your loan to Wired Banking Lite</h4>
                            <ul class='text-left'>
                                <li>
                            1. Download our sample file or download from WBA customer management.

                                </li>
                                <li>
                                    2. Populate the sample file according to the  structure of the sample file if you are new to WBL

                                </li>
                                <li>
                            3. Upload the  file  you download from WBA and select  configuration option if required.

                            </li>
                            </ul>
<p class='text-center'>File Type: XLS or Excel<br>
    Download Sample</p>
                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <div id="personal_account" onclick="selectx(this.id)" class="card py-5 shadow ">
                                        <div class="card-body">
                                            <div class="upload ">
                                                <input type="file" id="input-file-max-fs" name='cover_pic' class="dropify" data-default-file="" data-max-file-size="2M" required accept=".csv,.xlsx,.xls"/>
                                                <p class="mt-2"><i class="flaticon-cloud-upload mr-1"></i> Upload Picture</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-md-6">

                                    <div class="form-group">
                                        <label for="">Product Type</label>
                                        <div class='col-sm-6 col-md-6'>
                                            <label>Loan Product</label>
                                            <select name='loan_products' id='loan_products' class='form-control'  >

                                            @foreach ($product as $productData)
                                                <option value="{{Crypt::encryptString($productData->id)}}">{{$productData->product_name}}</option>
                                            @endforeach

                                        </select>
                                    </div>
                                </div>
                            </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Branch</label>
                                        <div class="col-sm-6 col-md-6">
                                            <div class="form-group">
                                                <label for="">Branch</label>
                                                <select name="branch" id='branch' onchange="renderStaff()" class="form-control">
                                                    <option value="null">Select Branch</option>
                                                    @if(count($branch))
                                                    @foreach($branch as $branchData)
                                                    <option value="{{Crypt::encryptString($branchData->id)}}">{{$branchData->branch_name}}</option>
                                                    @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Account Officer</label>
                                        <select name="officer" id='officer' class="form-control">

                                        </select>
                                    </div>
                                    <script>
                                        function renderStaff() {
                                            $branch = $('#branch').val();
                                            $.ajax({
                                                url: '/company/{{auth()->user()->company->short_name}}/branch/branch_staff/' + $branch,
                                                type: 'GET',
                                                dataType: 'json',
                                                success: function(data) {
                                                    $('select[name="officer"]').empty();

                                                    data.forEach(function(staff) {

                                                        $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
                                                    });
                                                }
                                            });
                                        }
                                    </script>
                                </div>
                            </div>
                        </div>
                        <button type='submit' class='btn btn-primary'>Upload</button>
                    </section>
                </div>
            </form>
        </div>
    </div>
</div>



@endsection
