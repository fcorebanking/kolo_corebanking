@extends('layouts.app')

@section('content')
<style>
    .hidden {
        display:none;
    }
</style>
<div class="widget-content widget-content-area br-6">
    <h3>Broadcast Message Log</h3>
    <div class="table-responsive mb-4 mt-4">

        <span id='infobait'></span>
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
                <th>Name</th>
                <th>A/C Number</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Num Page</th>
                <th>Amount</th>
                {{-- <th>status</th> --}}
                <th>Created By</th>
                <th>Created At</th>
            </thead>
            <tbody>
                @if($list)
                @forelse($list as $Data)
                <tr>
                    <td>{{$Data->User->first_name.' '.$Data->User->last_name}}</td>
                    <td>{{$Data->User->virtual_account}}</td>
                    <td>{{$Data->subject}}</td>
                    <td>{{($Data->message)}}</td>
                    <td>{{($Data->num_page)}}</td>
                    <td>{{$Data->amount}}</td>
                    {{-- <td>@if($Data->status==1)? <span class="badge badge-success">Done</span> @else <span class="badge badge-waarning">Pending</span> @endif</td> --}}
                    <td><!-- Example single danger button -->
                        {{($Data->created_by != null)?$Data->CreatedBy->username:"---"}}
                    </td>
                    <td>{{$Data->created_at}}</td>
                </tr>
                @empty
                    <caption class='alert alert-info'>No Record Found!</caption>
                @endforelse
                @endif
            </tbody>
        </table>
        {!! $list->render() !!}
            <!-- Modal -->
    </div>
</div>
<!-- Modal -->


@endsection
