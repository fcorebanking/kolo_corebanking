<link href="{{asset('assets/css/scrollspyNav.css')}}" rel="stylesheet" type="text/css" />
      <link href="{{asset('plugins/notification/snackbar/snackbar.min.css')}}" rel="stylesheet" type="text/css" />
    
<!-- <div class="toast-container" style="position: absolute; top: 0px; "> -->
<!-- <div class="toast fade align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
  <div class="d-flex">
    <div class="toast-body toast-msg">
      Hello, world! This is a toast message.
    </div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
  </div>
</div> -->


<script src="{{asset('assets/js/scrollspyNav.js')}}"></script>
      <script src="{{asset('plugins/notification/snackbar/snackbar.min.js')}}"></script>
      <script src="{{asset('assets/js/components/notification/custom-snackbar.js')}}"></script>
      <script>

function alert_toast(status){
  
  if(status == 1){
    $('.toast').removeClass("toast-danger");
    $('.toast').addClass("toast-primary");
  }else{
    $('.toast').addClass("toast-danger");
    $('.toast').removeClass("toast-success");
  }
  $('.toast').toast('show');
}

</script>    
    
@if(session()->has('success'))

<div style="position: absolute; top: 0; right: 22px;left: 22px;z-index: 9999;">
    <div class="toast bg-success fade hide" role="alert" data-delay="6000" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="mr-auto">Notification</strong>
            
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <div class="toast-body">
           {{ session()->get('success') }}
        </div>
    </div>
</div>
<script>
  alert_toast(1);
 
</script>
@elseif(session()->has('fail') )

<div style="position: absolute; top: 0; right: 22px;left: 22px;z-index: 9999;">
    <div class="toast bg-danger fade hide" role="alert" data-delay="6000" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="mr-auto">Notification</strong>
            
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <div class="toast-body">
           {{ session()->get('fail') }}
        </div>
    </div>
</div>
<script>
  alert_toast(0);
  
</script>
@else
<div style="position: absolute; top: 0; right: 22px;left: 22px;z-index: 9999;">
    <div class="toast toast-warning fade hide" role="alert" data-delay="6000" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="mr-auto">Notification</strong>
            
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <div class="toast-body toast-msg">
           
        </div>
    </div>
</div>

@endif



<!-- </div> -->