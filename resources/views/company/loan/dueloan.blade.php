@extends('layouts.app')

@section('content')
<div class="widget-content widget-content-area br-6">
    <div class="card mb-4 mt-4">
        @include('company.loan.inc.dueLoan')
    </div>
</div>

@endsection