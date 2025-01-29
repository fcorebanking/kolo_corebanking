{{-- @extends('layouts.app')

@section('content') --}}
<link rel="stylesheet" type="text/css" href="{{asset('plugins/editors/quill/quill.snow.css')}}">
<link href="{{asset('assets/css/apps/mailbox.css')}}" rel="stylesheet" type="text/css" />
<link href="{{asset('plugins/notification/snackbar/snackbar.min.css')}}" rel="stylesheet" type="text/css" />
    <div class="layout-px-spacing">
        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="row">
                    <div class="col-xl-12  col-md-12">
                        <a class="btn btn-primary m-5" id="addTask" href="#" onclick="enterMessage()"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> New Message</a>
                        <div class="mail-box-container">
                            <div class="mail-overlay"></div>
                            <div id="mailbox-inbox" class="accordion mailbox-inbox">
                                <div class="search">
                                    {{-- <input type="text" class="form-control input-search" placeholder="Search Here..."> --}}
                                </div>
                                <div class="message-box">
                                    <div class="message-box-scroll" id="ct"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <!-- Modal -->
     <div class="modal fade" id="addMessageModal" tabindex="-1" role="dialog" aria-labelledby="addTaskModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-dismiss="modal"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    <div class="compose-box">
                        <div class="compose-content" id="addTaskModalTitle">
                            <h5 class="">Add Remark</h5>
                            <form id='commentForm'>
                                <div class="d-flex  mail-subject mb-4">
                                    <input type='file'  name='attachment' id='attachment' style='display:none' >
                                    <label for='attachment'><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text flaticon-menu-list"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></label>
                                    <div class="w-100">
                                        <div id="taskdescription" class="">
                                            <textarea class='form-control' name='message' id='message'></textarea>
                                        </div>
                                        <span class="validation-text"></span>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>

                    <button class="btn btn-info" id='submitmsg' onclick="sendMessage()">Add</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewMessageModal" tabindex="-1" role="dialog" aria-labelledby="viewTaskModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-dismiss="modal"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    <div class="compose-box">
                        <div class="compose-content" >
                            <h5 class="remark_author">View Remark</h5>
                            <p class='load-content'>Loading...</p>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                </div>
            </div>
        </div>
    </div>
    <script src="{{asset('assets/js/ie11fix/fn.fix-padStart.js')}}"></script>
    <script src="{{asset('plugins/editors/quill/quill.js')}}"></script>
    <script src="{{asset('plugins/sweetalerts/sweetalert2.min.js')}}"></script>
    <script src="{{asset('plugins/notification/snackbar/snackbar.min.js')}}"></script>
    <script src="{{asset('assets/js/apps/custom-mailbox.js')}}"></script>
{{-- @endsection --}}
