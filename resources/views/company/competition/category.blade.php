@extends('layouts.app')

@section('content')


<form action='/company/competition/category' method='post'>
    <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

    <div class=" container  ">

        <div class="row  m-5 p-5">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                <div class="widget widget-card-five">
                    <div class="widget-content">
                        <div class="account-box">

                            <div class="info-box">


                                <div class="balance-info">
                                    <h6>Total Talents</h6>
                                    <p class="acc-amount">{{$talent}}</p>
                                </div>
                            </div>

                            <div class="card-bottom-section">
                                <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
                <div class="widget widget-card-five">
                    <div class="widget-content">
                        <div class="account-box">

                            <div class="info-box">


                                <div class="balance-info">
                                    <h6>Total Votes</h6>
                                    <p class="acc-amount">{{$vote}}</p>
                                </div>
                            </div>

                            <div class="card-bottom-section">
                                <div><span class="badge badge-light-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row card m-5 p-5">

            @if(Session::has('success'))
            <p class="alert alert-info shadow">{{ Session::get('success') }}</p>
            @endif


            @if ($errors->any())
            @foreach ($errors->all() as $error)
            <p class="alert alert-warning shadow">{{$error}}</p>
            @endforeach
            @endif
            <div class="input-group mb-4 col-lx-6 col-md-12">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="name">Name</span>
                </div>
                <input type="text" class="form-control" id='name' name='name' aria-describedby="basic-addon3" placeholder="Category Name" >
            </div>
            {{-- <div class="input-group mb-4 col-lx-6 col-md-12">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="icon">Icon</span>
                </div>
                <input type="file" class="form-control" id='icon' name='icon'   >
            </div> --}}
            <button type="submit" class="btn btn-primary" >Save</button>
        </div>
    </div>
</form>

<table class='table'>
    <head><th>Name</th><th>Action</th></head>
    <tbody>
        @if ($talent_category)
            @foreach ($talent_category as $talent_category_data)
                <tr>
                    <form method='post' action="/company/competition/category/{{$talent_category_data->id}}">
                        @csrf
                    {{-- <td><input type="text" class="form-control"  name='icon' aria-describedby="basic-addon3" placeholder="10" value="{{$talent_category_data->icon}}"  ></td> --}}
                    <td><input type="text" class="form-control"  name='name' aria-describedby="basic-addon3" placeholder="10" value="{{$talent_category_data->name}}"  ></td>
                    <td><button type="submit" class="btn btn-primary" >Update</button></td>
                    </form>
                </tr>
            @endforeach

        @endif
    </tbody>
</table>
@endsection
