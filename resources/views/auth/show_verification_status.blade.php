@extends('layouts.app')

@section('content')


<body>
    <div class="layout-px-spacing" style="flex: 1; height: 100%; background-color: #efefef;">

        <div class="row layout-top-spacing">

            @if($isReferenceValid)

            <div class="col-xl-12 col-lg-12 col-sm-12 ">
                <div class="widget-content widget-content-area" style=" margin-top: 120px;">

                    @if($status == 'successful')
                    <svg width="150" height="150" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                        <circle class="path circle" fill="none" stroke="#73AF55" stroke-width="6" stroke-miterlimit="10"
                            cx="65.1" cy="65.1" r="62.1" />
                        <polyline class="path check" fill="none" stroke="#73AF55" stroke-width="6"
                            stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 " />
                    </svg>
                    @endif
                    @if($status == 'failed')
                    <svg width="150" height="150" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                        <circle class="path circle" fill="none" stroke="#D06079" stroke-width="6" stroke-miterlimit="10"
                            cx="65.1" cy="65.1" r="62.1" />
                        <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round"
                            stroke-miterlimit="10" x1="34.4" y1="37.9" x2="95.8" y2="92.3" />
                        <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round"
                            stroke-miterlimit="10" x1="95.8" y1="38" x2="34.4" y2="92.2" />
                    </svg>
                    @endif
                    @if($status == 'expired')
                    <svg width="150" height="150" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                        <circle class="path circle" fill="none" stroke="#D06079" stroke-width="6" stroke-miterlimit="10"
                            cx="65.1" cy="65.1" r="62.1" />
                        <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round"
                            stroke-miterlimit="10" x1="34.4" y1="37.9" x2="95.8" y2="92.3" />
                        <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round"
                            stroke-miterlimit="10" x1="95.8" y1="38" x2="34.4" y2="92.2" />
                    </svg>
                    @endif

                    <h3 class="mt-5">{{$title   }} </h3>

                    <h5>{!! $description !!}</h5>

                    <div class="row" style="flex-direction: row; justify-content: center; align-items: center; margin-top: 30px;">
                        <div class="col-md-8 col-lg-6 col-sm-11 col-11">
                            @if($usertype == 2)
                                @if($status == 'successful')
                                <a href="{{ url('/login') }}" class="btn btn-primary">Login</a>
                                @endif
                                @if($status == 'expired')
                                <a href="{{$resendUrl}}" class="btn btn-primary btn-md mt-6">Resend Activation
                                    Email</a>
                                @endif
                            @endif
                        </div>

                    </div>

                    <div class="row mt-8" style="margin-top: 50px;">
                        <div class="col-md-6 mt-6" style="margin: 0 auto;">
                            @if ($message = Session::get('success'))
                            <div class="alert alert-success mb-4" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <svg
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg></button>
                                <strong> {{$message}}.</button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @endif

        </div>


    </div>
</body>

@endsection

@include('inc.scripts')
