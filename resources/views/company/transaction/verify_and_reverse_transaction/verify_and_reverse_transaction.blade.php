@extends('layouts.app')

@section('content')
<div id="saImage" class="col-lg-12 col-12 layout-spacing layout-top-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12 mt-3">
                    <h4>Verify and Reverse Transaction</h4>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area text-center">
            <form id='submit_Rform' enctype="multipart/form-data">
                <div class="row pb-4">

                    <div class="col-md-8 offset-md-2 alert alert-info mb-20"><b>Notice:</b> Kindly ensure transaction has been performed at least 10mins ago before attempting to check Nibss status.  If the Bank transfer has failed reversal will be done else contact us</div>

                    <div class="form-group mb-4 col-sm-12 col-md-8 offset-md-2">
                        <label for="transaction_reference">Transaction Reference</label>
                        <input id="transaction_reference" oninput="validity.valid||(value='');" type="text" name="transaction_reference" placeholder="Enter Transaction Reference" class="form-control" required>
                    </div>

                    <div class="form-group mb-4 col-sm-12 col-md-6 offset-md-3 mb-5 mt-2">
                        <button type='submit' id="mySumit" class="btn btn-block btn-primary" class='text-info'>Continue</button>
                    </div>

            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mandateGuidelineModal" tabindex="-1" role="dialog" aria-labelledby="mandateGuidelineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="mandateGuidelineModalLabel">Setting Up A Direct Debit Mandate</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                </button>
            </div>
            <form id='add-branch'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">

                <div class="modal-body">
                    <p style="text-align: left; font-size: 16px">
                        In order to set up a Direct Debit Mandate a DDI (or Direct Debit Instruction) instruction form must be completed.
                    <ol style="text-align: left; font-size: 16px">
                        <li>Download the mandate form customized with your logo and business name.</li>
                        <br>
                        <li>The customer is expected to hand-fill the form, sign and submit to the company for upload.</li>
                        <br>
                        <li>The bank contacts him/her for confirmation, to prevent fraud a request. </li>
                        <br>
                        <li><b>Note:</b>Kindly select At least two (2) days from the application date as <b>START DATE</b> and at least Three (3) days from application date as <b>FIRST REPAYMENT DATE</b> </li>
                        <br>
                        <li>Variable amount should sum collection amount or leave it empty as it is optional.</li>
                        <br>

                        <li>Ensure the form is pdf or jpg, or png before you upload.</li>
                    </ol>

                    <a target="_blank" href='https://www.cbn.gov.ng/out/2013/bpsd/direct%20debit%20faq.pdf' class='text-info text-right'>Learn More</a>

                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal" id="modalCancelButton"><i class="flaticon-cancel-12"></i>
                        Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    function previewFile3(chooser) {
        console.log('hello');


        var file = $('#' + chooser).get(0).files[0];

        if (file) {
            var reader = new FileReader();

            reader.onload = function() {
                var previewer = chooser + '_preview';

                // $('#' + previewer).css('background-image', 'url("' + reader.result + '")');
                $("#previewImg2").attr("src", reader.result);

                // $("#bg-img").css("background-image", "url(" + reader.result + ")");
            }

            reader.readAsDataURL(file);
        }
    }
</script>

@endsection