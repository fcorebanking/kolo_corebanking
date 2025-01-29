@extends('layouts.app')

@section('content')

<button type="button" style="margin:10px;" onclick='newModal()' class="btn btn-primary mb-2 mr-2" >
    Loan Guarantor &nbsp<i class='fa fa-plus'></i>
</button>

<div class="widget-content widget-content-area br-6">

<div class="card add-g-card">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Loan Guarantor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id='add-loan-guarantor' action='/company/loan/save-guarantor' enctype="multipart/form-data" method='post'>

                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <div class="modal-body">
                <input type="hidden" name='guarantor_id' id='guarantor_id' value='0'>
                <label>Customer</label>
                    <select  id='customer' class=" form-control" name="customer" style='margin:5px'></select>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">First|Middle Name</span>
                        </div>
                        <input type="text" class="form-control" require name='first_name' onblur="checkname()" id='first_name' aria-describedby="basic-addon3"
                            placeholder="John..." >
                    </div>
                    <span class='validation-text'></span>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Last Name</span>
                        </div>
                        <input type="text" class="form-control" require name='last_name' onblur="checkname()" id='last_name' aria-describedby="basic-addon3"
                            placeholder="Paul..." >
                    </div>
                    <span class='validation-text'></span>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addonemail">Email</span>
                        </div>
                        <input type="text" class="form-control" require name='email' id='email' aria-describedby="basic-addon3"
                            placeholder="@Paul..." >
                    </div>
                    <span class='validation-text'></span>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Phone</span>
                        </div>
                        <input type="number" class="form-control" name='phone' id='phone' aria-describedby="basic-addon3" onblur="checkname()">
                    </div>


                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Salary</span>
                        </div>
                        <input type="text" class="form-control" name='salary' id='salary' aria-describedby="basic-addon3"
                            placeholder="100000..." >
                    </div>
                    <span class='validation-text'></span>
                    <div class='row'>

                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Passport (Optional)</span>
                        </div>
                        <input type="file" accept=".jpg,.png,.jpeg" class="form-control" name='passport' id='passport' aria-describedby="basic-addon3">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Identity Number</span>
                        </div>
                        <input type="text" class="form-control" name='bvn' id='bvn' aria-describedby="basic-addon3">
                    </div>

                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Relationship</span>
                        </div>
                        <input type="text" class="form-control" name='relationship' id='relationship' aria-describedby="basic-addon3">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Address</span>
                        </div>
                        <input type="text" class="form-control" name='address' id='address' aria-describedby="basic-addon3">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Occupation</span>
                        </div>
                        <input type="text" class="form-control" name='occupation' id='occupation' aria-describedby="basic-addon3">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Other Document optional</span>
                        </div>
                        <input type="file" class="form-control" name='signature' id='signature' aria-describedby="basic-addon3">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Other Details</span>
                        </div>
                        <textarea class="form-control" name='other_remarks' id='other_remarks' ></textarea>
                    </div>
                    <span class='validation-text'></span>
                </div>
                <div class="modal-footer">
                    <button class="btn" type='button' onclick='newModal()'><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" disabled class="btn btn-primary" id="loanBTN">Save Guarantor</button>
                </div>


                <div class="modal proceedData"  tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Duplicate Notification</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                          </button>
                        </div>
                        <div class="modal-body">
                            <p class='checkname'></p>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        </div>
                      </div>
                    </div>
                  </div>
            </form>
        </div>





    <div class="table-responsive mb-4 mt-4">
        <span id='infobait'></span>
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
            <th>sn</th>
            <th>Customer</th>
            <th>AccountNo</th>
            <th>Phone</th>
            <th>Guarantor</th>
            <th>Salary</th>
            <th>Occupation</th>
            <th>BVN</th>
            <th>Status</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Other Remarks</th>
            <th>Action</th>
            </thead>
            <tbody>

            </tbody>
        </table>
            <!-- Modal -->
    </div>

        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form id='edit-loan-guarantor' action='/company/loan/update-guarantor' enctype="multipart/form-data" method='post'>
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Edit Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                    <div class="modal-body">
                            <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                        <input type='hidden'  id='editCustomer' class=" form-control" name="customer" style='margin:5px'></select>
                        <input type='hidden'  id='editGloan' class=" form-control" name="editID" style='margin:5px'></select>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">First Name</span>
                            </div>
                            <input type="text" class="form-control" require name='first_name' id='editfirst_name' aria-describedby="basic-addon3"
                                placeholder="John..." >
                        </div>
                        <span class='validation-text'></span>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Last Name</span>
                            </div>
                            <input type="text" class="form-control" require name='last_name' id='editlast_name' aria-describedby="basic-addon3"
                                placeholder="Paul..." >
                        </div>
                        <span class='validation-text'></span>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addonemail">Email</span>
                            </div>
                            <input type="text" class="form-control" require name='email' id='editemail' aria-describedby="basic-addon3"
                                placeholder="@Paul..." >
                        </div>
                        <span class='validation-text'></span>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Phone</span>
                            </div>
                            <input type="number" class="form-control" name='phone' id='editphone' aria-describedby="basic-addon3" >
                        </div>
                        <span class='row'><span class='col-sm-6 col-md-12'><p class='checkname'></p></span></span>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Salary</span>
                            </div>
                            <input type="text" class="form-control" name='salary' id='editsalary' aria-describedby="basic-addon3"
                                placeholder="100000..." >
                        </div>
                        <span class='validation-text'></span>
                        <div class='row'>
                        <div class="col-sm-6 col-md-6">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Status</span>
                            </div>
                                <select class='form-control'  name='status_id' id='editstatus_id' >
                                    <option value='1'>Verified</option>
                                    <option value='2'>Pending</option>
                                    <option value='3'>Inquiry</option>
                                    <option value='4'>Decline</option>
                                </select>
                        </div>
                        </div>
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Passport (Optional)</span>
                            </div>
                            <input type="file" accept=".jpg,.png,.jpeg" class="form-control" name='passport' id='editpassport' aria-describedby="basic-addon3">
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Identity Number</span>
                            </div>
                            <input type="number" class="form-control" name='bvn' id='editbvn' aria-describedby="basic-addon3">
                        </div>

                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Relationship</span>
                            </div>
                            <input type="text" class="form-control" name='relationship' id='editrelationship' aria-describedby="basic-addon3">
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Address</span>
                            </div>
                            <input type="text" class="form-control" name='address' id='editaddress' aria-describedby="basic-addon3">
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Occupation</span>
                            </div>
                            <input type="text" class="form-control" name='occupation' id='editoccupation' aria-describedby="basic-addon3">
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Other Document  (Optional)</span>
                            </div>
                            <input type="file" class="form-control" name='signature' id='editsignature' aria-describedby="basic-addon3">
                        </div>
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon7">Other Details</span>
                            </div>
                            <textarea class="form-control" name='other_remarks' id='editother_remarks' ></textarea>
                        </div>
                        <span class='validation-text'></span>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                      </div>
                </div>

                </form>
              </div>
            </div>
</div>
<!-- Modal -->
<script>
    function checkname(){
        $lname = $('#last_name').val();
        $fname = $('#first_name').val();
        $phone = $('#phone').val();
        $.ajax({
        url: '/company/loan/check-guarantor',
        type: 'post',
        dataType: 'json',
        data:{first_name:$fname,last_name:$lname,phone:$phone},
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            if(data.status == 0){
                $('.proceedData').modal('show');
                $("#loanBTN").attr("disabled","disabled");
                $('.checkname').html("<div class='alert alert-danger'>"+data.message+"</div><br><center>Proceed <button type='button' data-dismiss='modal' class='btn btn-primary' onclick='enableBTN()'>Proceed</button></center>");
            }else{
                $("#loanBTN").removeAttr("disabled");
            }
        }
    });
    }
    function enableBTN(){
        $("#loanBTN").removeAttr("disabled");
        $('.proceedData').modal('close');
    }
    //     $('#approveduplicate').on('change', '.checkbox', function() {
    // if(this.checked) {
    //     $("#loanBTN").removeAttr("disabled");
    // }else{
    //     $("#loanBTN").attr("disabled","disabled");
    // }
    // });
</script>
@endsection
