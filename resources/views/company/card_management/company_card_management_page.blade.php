@include('inc.styles')
<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h3>Company Card Management</h3>

                <ul class="nav nav-tabs  mb-3 mt-3" id="iconTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="icon-home-tab" onclick="setCardCategory(1)" data-toggle="tab"
                            href="#icon-home" role="tab" aria-controls="icon-home" aria-selected="true">
                            <i class="fa fa-credit-card"></i> Company Card</a>
                    </li>

                    <!-- <li class="nav-item">
                        <a class="nav-link" id="icon-contact-tab" onclick="setCardCategory(2)" data-toggle="tab"
                            href="#icon-contact" role="tab" aria-controls="icon-contact" aria-selected="false">
                            <i class="fa fa-history"></i> Card Transaction History</a>
                    </li> -->
                </ul>


                <div class="tab-content" id="iconTabContent-1">
                    <div class="tab-pane fade show active" id="icon-home" role="tabpanel"
                        aria-labelledby="icon-home-tab">
                        <div class="container">

                            @if(count($card_details)
                            < 1) <div class="container  mb-4">

                                <img src="{{url('/wba_images/empty_image.svg')}}" style="margin-top: 40px;" class="py-4"
                                    width="300" />

                                <h4 class="py-3">No Virtual Card Available</h4>

                                <button type="button" class="btn btn-primary start" onclick="createCompanyCard()"
                                    id="createCompanyCardBtn">
                                    <i class="fa fa-plus"></i> &nbsp;&nbsp;
                                    <span>Create Card</span>
                                </button>
                        </div>
                        @endif @if(count($card_details) > 0)
                        <div class="container  mb-4">

                            <div class="myWrapper table-responsive">
                                <div class="myBase">
                                    <div class="Inner-wrap">

                                        <div class="Card-number">
                                            <ul>
                                                <li class="liClass" id="first-li">{{$card_details['firstFour']}}</li>
                                                <li class="liClass">{{$card_details['secondFour']}}</li>
                                                <li class="liClass">{{$card_details['thirdFour']}}</li>
                                                <li class="liClass" id="last-li">{{$card_details['lastFour']}}</li>
                                            </ul>
                                        </div>

                                        <div class='Expire'>
                                            <h6>CVV</h6>
                                            <p>{{ $card_details['cvv'] }}</p>
                                        </div>

                                        <div class='ExpireMain'>
                                            <p>{{ $card_details['exp'] }}</p>
                                        </div>

                                        <div class='companyCardName'>
                                            <h3>{{$card_details['name']}}</h3>
                                        </div>
                                        <!-- Visa Logo SVG -->
                                    </div>
                                </div>
                            </div>

                            <div class="row py-4">
                                <div class="col-md-12">
                                    <h5><b>Available:</b> {!! $card_details['cardCurrency'] !!}
                                        <span id="cardAvailableBalance">{{$card_details['availableCardBalance']}}</span>
                                    </h5>
                                </div>
                            </div>

                            <div class="row py-4">
                                <div class="col-md-12">

                                    <input type="hidden" id="cardReference" value="{{$card_details['cardReference']}}"
                                        aria-describedby="card-reference">
                                    <button class="btn btn-primary btn-lg" onclick="showfundWithdrawModal(1)">Fund
                                        Card</button>
                                    <button class="btn btn-warning btn-lg"
                                        onclick="showfundWithdrawModal(2)">Withdraw</button>
                                    <!-- <button class="btn btn-danger btn-lg" onclick="showfundBlockCardModal(1)">Freeze Card</button> -->

                                </div>
                            </div>
                        </div>
                        @endif



                    </div>
                </div>

                <div class="tab-pane fade" id="icon-contact" role="tabpanel">
                    <div class="container">
                        <h5 class="text-left">Select Date Range</h5>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="transferBankCode">From</label>
                                    <input id="basicFlatpickr" class="form-control flatpickr flatpickr-input active"
                                        type="text" placeholder="Select Date..">
                                </div>
                                <div class="col-md-4">
                                    <label for="transferBankCode">To</label>
                                    <input id="basicDateFrom" class="form-control flatpickr flatpickr-input active"
                                        type="text" placeholder="Select Date..">

                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary text-left" style="float: left; margin-top: 34px;">Submit</button>
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

</div>
@include('inc.scripts')