<div class="tab-pane fade" id="v-border-pills-transfer" role="tabpanel" aria-labelledby="v-border-pills-transfer-tab">

    <div class="media">
        <div class="media-body">
            <h5 class="mt-0 mb-3">User Transfer</h5>
            <div class="info">
                <p class='alert alert-danger'>Transfer Staff and Customer from Source Branch to Destination Branch A=>B</p>
                <div class="row">
                    <div class="col-md-11 mx-auto">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sourceBranch">Source Branch</label>
                                    <select id='sourceBranch' class='form-control'></select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="destination">Destination Branch</label>
                                    <select id='destinationBranch' class='form-control'></select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <center><button type='button' class='btn btn-danger' onclick='transferBranch()'>Transfer =></button></center>
                            </div>
                    </div>
                    </div>
                </div>
            </div>
            <div class="info">
                <div class="row">
                    <div class="col-md-11 mx-auto">
                        <p class='alert alert-danger'>Transfer Customer from Source Staff to Destination Staff A=>B</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sourceStaff">Source Staff</label>
                                    <select id='sourceStaff' class='form-control'></select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="destinationStaff">Destination Staff</label>
                                    <select id='destinationStaff' class='form-control'></select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <center><button type='button' class='btn btn-danger' onclick='transferStaff()'>Transfer =></button></center>
                            </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
