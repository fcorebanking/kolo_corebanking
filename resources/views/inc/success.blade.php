@extends('layouts.app')

@section('content')

<body style="flex: 1; height: 100%; background-color: #efefef;">
    <div class="layout-px-spacing" >

        <div class="row layout-top-spacing">


            <div class="col-xl-12 col-lg-12 col-sm-12 ">
                <div
                    style=" margin-top: 120px; justify-content: center; align-items: center;">

                    <div class="col-md-6" style="align-self: center; margin: auto;">
                        @if ($status =='success')
                        <img src="{{asset('assets/images/check.png')}}" height="140" width="140" />
                        @else
                        <img src="{{asset('assets/images/cancel.png')}}" height="140" width="140" />
                        @endif
                        <h3 class="mt-5"><b>{{$message}}</b></h3>
                        <div class="col-md-12">
                            <p>Click the top back button to exist</p>
                        </div>
                    </div>


                </div>
            </div>

        </div>


    </div>

</body>

@endsection

@include('inc.scripts')
