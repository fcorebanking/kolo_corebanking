@extends('layouts.app')
@include('company.fcm.index')
@section('content')
<style>
    .pageviewer{
        margin:0 auto;
    }
</style>
<div class='pageviewer'>
    <p class='alert alert-info'>in order to send broadcast message to your mobile app user make sure to login with the account use in configuring your app on this browser and click the link below to enjoy swift messaging</p>
    <a class='btn btn-warning' href='https://console.firebase.google.com/u/1/project/wealthtribe-e2ceb/messaging'>Go to google Message =>></a>
</div>
<div class="container">
    <div class=" justify-content-center">
        {{-- <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="text-center">{{ __('Send Notification') }}</h3>
                    <hr/>
                    <form action="{{ route('notification') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                @if (session('success'))
                                    <div class="alert alert-success" role="alert">
                                        {{ session('success') }}
                                    </div>
                                @endif
                                @if (session('error'))
                                    <div class="alert alert-danger" role="alert">
                                        {{ session('error') }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="font-weight-bold" for="title">Title</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" placeholder="Enter Title">
                                    @error('title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="font-weight-bold" for="title">Message</label>
                                    <textarea name="message" placeholder="Enter Message" class="form-control @error('message') is-invalid @enderror"></textarea>
                                    @error('message')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-block">Send</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> --}}

    </div>
</div>

@endsection
