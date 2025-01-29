@extends('layouts.app')

@section('content')

<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h3>Broadcast Message</h3>

                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">

                    <div class="col-md-8">
                        <div id="broadcast_form">
                            <div class="form-group ">
                                <label for="audience">Select Audience</label>

                                <select id='audience' class='form-control' onchange='fetch_user()'>

                                    <option value='all'>All</option>
                                    <option value='2'>Staff</option>
                                    <option value='1'>Customer</option>
                                </select>
                            </div>

                            <div class="form-group ">
                                <label for="userx">Select User (Optional)</label>

                                <select id='user' class='form-control form-small' style='margin:5px'>

                                </select>
                            </div>
                            <div class="form-group ">
                                <label for="option">Subject</label>
                                <input type='text' id='subject' class='form-control form-small' style='margin:5px'>
                            </div>

                            <div class="form-group ">

                                <label for="option">broadcast option</label>
                                <select id='option' class='form-control' onchange='swapSMSEmail(this.value)'>

                                    <option value='all'>SMS & Email</option>
                                    <option value='sms'>SMS Only</option>
                                    <option value='whatsapp'>Whatsapp Only</option>
                                    <option value='email'>Email Only</option>
                                </select>
                            </div>
                            <div class="form-group ">


                    <div id="basic" class="row layout-spacing layout-top-spacing">
                        <div class="col-lg-12">
                            <div class="statbox widget box box-shadow">
                                <div class="widget-header">
                                    <div class="row">
                                        <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                                            <h4> Message </h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content widget-content-area">
                                    <div id="editor-container">
                                     </div>
                                    <div class='count-msg'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id='sms-txt-area'>
                        <textarea class='form-control' id='txtarea-text'></textarea>
                        <div class='count-msg'></div>
                    </div>
                            </div>
                            {{--  --}}
                            <button class='btn btn-primary' id='broadcastBTN' onclick='broadcast()'  >Send Broadcast</button>

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
