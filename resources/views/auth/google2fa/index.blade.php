@extends('layouts.app')

@section('content')
@php
       $auth = auth()->user();

@endphp
<style>
    .containerx{
        margin: auto 0;
        padding:15px;
    }
    .w-10{
        width:100px;
    }
</style>
<div class="containerx">
    <div style="height: 70vh;">
        <div>
            <div class="panel panel-default">
                <div class="panel-heading font-weight-bold">{{$auth->company->company_name}}</div>
                <hr>
                @if($errors->any())
                    <div class="col-md-12">
                        <div class="alert alert-danger">
                          <strong>{{$errors->first()}}</strong>
                        </div>
                    </div>
                @endif

                <div class="panel-body">
                    <form class="form-horizontal" method="POST" action="{{ route('2fa') }}">
                        <div class='alert alert-warning'>Note: if you can't access your account please contact wiredbanking support</div>
                        {{ csrf_field() }}
                                @php
                                $google2fa = app('pragmarx.google2fa');

                                $exist_secret_len = strlen($auth->google2fa_secret);
                                if($exist_secret_len  < 2 || $auth->google2fa_secret ==null){
                                    $secret= $google2fa->generateSecretKey();
                                    $auth->update(['google2fa_secret'=>$secret]);
                                }else{
                                    $secret = $auth->google2fa_secret;
                                }
                                    $QR_Image = $google2fa->getQRCodeInline(
                                        $auth->company->company_name,
                                            $auth->email,
                                            $secret
                                        );
                                @endphp
                                @if($exist_secret_len  < 2)
                                    <p>Scan the Barcode below with Google Authenticator app to access dashboard</p>
                                    {!! $QR_Image !!}
                                @endif
                                <div class='text-center'>
                        <div class="form-group">
                            <p>Please enter the  <strong>OTP</strong> generated on your Authenticator App. <br> Ensure you submit the current one because it refreshes every 30 seconds.</p>
                            <p for="one_time_password" class="control-label bold">One Time Password</p>
                            <input id="one_time_password" type="password" placeholder="****" class="w-10" name="one_time_password" required autofocus pattern="[0-9]{1,6}" maxlength="6">

                        </div>

                        <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    Authenticate
                                </button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
