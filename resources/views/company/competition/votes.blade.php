@extends('layouts.app')

@section('content')
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area text-center">

        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>Talents Votes</h4>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12 col-12 order-sm-0 order-1">
                <div class="tab-content" id="v-right-pills-tabContent">
                <div class="tab-pane fade show active" id="v-right-pills-home" role="tabpanel" aria-labelledby="v-right-pills-home-tab">
                    <div class="layout-px-spacing">

                        <div class="row layout-top-spacing">

                            <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                                <div class="table-responsive mb-4 mt-4">
                                    <span id='infobait'></span>
                                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                        <thead>

                                        <th>Voter</th>
                                        <th>votes</th>
                                        <th></th>
                                        </thead>
                                        <tbody>
                                            @if ($vote)
                                                @foreach ($vote as $talentdata)
                                                    <tr>
                                                        <td>{{$talentdata->first_name.' '.$talentdata->last_name}}</td>
                                                        <td>{{($talentdata->vote_count)}}</td>

                                                    </tr>
                                                @endforeach
                                            @endif
                                            <tr></tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>


                </div>
            </div>


        </div>
        </div>
    </div>

@endsection






