@include('inc.styles')
<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h3>Fund Wallet</h3>

                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
                    <div class="col-md-8">
                        <form id="fund_wallet">

                            <!-- @if ($message = Session::get('success'))
                        <div class="alert alert-success mb-4" role="alert"> 
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"> 
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-dismiss="alert">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                             
                        </div>
                        @endif -->

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

                            @if ($message = Session::get('error'))
                            <div class="alert alert-danger mb-4" role="alert">
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

                            <div class="form-group mb-4">
                                <label for="amount">Fund Amount</label>
                                <input id="amount" type="number" name="amount" placeholder="Enter Amount"
                                    class="form-control" required>
                            </div>
                            <span class="validation-text"></span>
                            <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                            <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

                            <div class="form-group mb-4">
                                <label for="exampleFormControlSelect1">Fund Method</label>
                                <select class="form-control" name="fundMethod" id="fundMethod" required>
                                    <option value="">-- Select Fund Method --</option>
                                    <option value="3">Flutterwave</option>
                                    <option value="6">Bank Transfer</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <span class="validation-text"></span>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <button type="button" onclick="showTransactionSummary()" id="mySumit"
                                    class="mt-4 btn btn-primary">
                                    Submit</button>
                            </div>
                        </form>
                    </div>

                </div>


            </div>
        </div>

    </div>

</div>
@include('inc.scripts')