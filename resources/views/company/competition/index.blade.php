@extends('layouts.app')

@section('content')


<form action='/company/competition' method='post'>
    <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

    <div class=" container  ">

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
                    <span class="input-group-text" id="talent_payment">Talent Registration Fee</span>
                </div>
                <input type="text" class="form-control" id='talent_payment' name='talent_payment' aria-describedby="basic-addon3" placeholder="10" value="{{ $talent->talent_payment??'0' }}">
            </div>
            <div class="input-group mb-4 col-lx-6 col-md-12">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="vote_payment">Voters Fee</span>
                </div>
                <input type="text" class="form-control" id='vote_payment' name='vote_payment' aria-describedby="basic-addon3" placeholder="10"  value="{{ $talent->vote_payment??'0' }}">
            </div>
            <button type="submit" class="btn btn-primary" >Update</button>
        </div>
    </div>
</form>


@endsection
