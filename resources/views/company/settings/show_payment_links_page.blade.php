<div class="layout-px-spacing">

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12">
            <div class="widget-content widget-content-area">

                <h4>Payment link IFO</h4>
                <h3 style="margin-bottom: 30px;"></h3>
                <div class="alert alert-info">Share your payment link with anyone to accept payment</div>

                <div class="row" style="flex-direction: row; justify-content: center; align-items: center;">
                    <div class="col-md-10">
                        <div class="clipboard">
                            <form class="form-horizontal">
                                <input type="text" class="form-control mb-4" readonly id="input-copy" value="{{$paymentLink}}">
                                <a class="mb-1 btn btn-primary" href="javascript:;" data-clipboard-action="copy" data-clipboard-target="#input-copy"><i class="fa fa-clipboard"></i> Copy Link</a>
                            </form>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>

</div>
@include('inc.scripts')