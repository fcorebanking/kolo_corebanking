@extends('layouts.app')

@section('content')

<body style="flex: 1; height: 100%; background-color: #efefef;">
    <div class="layout-px-spacing" >
        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12 ">
                <div class="widget-content widget-content-area"
                    style=" margin-top: 120px; justify-content: center; align-items: center;">
                    <div class="col-md-6" style="align-self: center; margin: auto;">
                        @if($img != null)<img src="{{$img}}" height="140" width="140" />@endif
                        <h3 class="mt-5"><b>{{$page_menu}}</b></h3>
                        @if (\Session::has('success'))
                            <div class="alert alert-success">
                                {!! \Session::get('success') !!}
                            </div>
                        @elseif(\Session::has('fail'))
                                <div class="alert alert-danger">
                                    {!! \Session::get('fail') !!}
                                </div>
                        @else
                        <h5>{{$guarantorData}}</h5>
                        @endif
                        @if($status == 3)
                        <div class="col-md-12">
                            <a href="{{ url('company/loan/guarantor/action/accept/'.$id) }}" id="resendBtn"  class="btn btn-success mt-3">Accept</a>
                            <a href="{{ url('company/loan/guarantor/action/decline/'.$id) }}" class="btn btn-danger mt-3">Decline</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

@endsection

@include('inc.scripts')