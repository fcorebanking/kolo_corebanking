<div class="tab-pane fade" id="v-border-pills-remitta" role="tabpanel" aria-labelledby="v-border-pills-remitta-tab">
    <div class='alert alert-warning'>Note: To enable remitta kindly contact us
    </div>
    <div class="info remittastatus">
        <h5 class="">Remitta Credentials</h5>
        <div class='alert alert-warning'>Note: if you want to use remitta service and you have ur own remitta credential input it below else system will use wiredbanking credentials
        </div>
        {{-- <div class="col-md-12 m-5">
            <div class="form-group">
                <span id='activatingremitta'>Activate Remitter</span> <input type='checkbox' name='activateRemitta' id='activateRemitta' >
            </div>
        </div> --}}
            <div class="row ">
                <div class="col-md-11 mx-auto">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="domain_name">Enter API Key</label>
                                <input name='apikey' id='apikey' class='form-control' placeholder="Api Key">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="domain_name">Enter Machant ID</label>
                                <input name='machant' id='machant' class='form-control' placeholder="Machant">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="domain_name">Enter API Token</label>
                                <input name='apitoken' id='apitoken' class='form-control' placeholder="API Token">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <span>Set as default</span> <input type='checkbox' name='defaultCheck' id='defaultCheck' >
                            </div>
                        </div>
                    </div>
                    <center><button type='button' class='btn btn-warning' id='saveremitta' onclick='saveRemitta()'>Save</button></center>
                </div>
            </div>
    </div>
</div>

