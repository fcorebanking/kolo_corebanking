<section class="contact_page_section">


    @section('extra-style')
    <style>
        .spinner-text {
            animation: spinner-text-animation 2s infinite;
            }

        @keyframes spinner-text-animation {
        0% {
            transform: translateX(-100%);
        }
        100% {
            transform: translateX(100%);
        }
        }
    </style>

    @endsection
    <div class="container">
        <div class="contact_inner">
            <div class="contact_form">
              <div class="section_title">
                  <h2>To <span>Upgrade</span> your KYC</h2>
                  <p>Fill up the KYC update form to upgrade your account tier to transact higher amount.. after submission our compliance team will review your document and upgrade if need be you will be notified via email on additional information.</p>
              </div>



              {{-- @if (session()->has('message'))
                <x-message :message="session('message')" :type="'Success'" :color="'success'"/>
             @endif

             @if (session()->has('error'))
                <x-message :message="session('error')" :type="'Error'" :color="'danger'"/>
             @endif --}}

                <form  wire:submit.prevent='submit' class="row">
                  <div class="form-group col-md-6">
                    <label for="usr">Enter Account Number :</label>
                      <input  wire:model="account_number" name="account_number" id="account_number" type="text" placeholder="Enter Account Number" class="form-control">
                      @error('account_number')
                      <p class="text-danger">{{ $message }}</p>
                  @enderror
                    </div>

                    @if ($nextIsBvn)


                  <div class="form-group  col-md-6">
                    <label for="usr">Enter BVN:  </label>
                      <input type="number"  wire:model="bvn" placeholder="Enter BVN" class="form-control">
                      @error('bvn')
                      <p class="text-danger">{{ $message }}</p>
                  @enderror
                  </div>
                  @endif






                  @if ($nextIsFace  && $alreadyPending==null)
                  <div class="col-md-6 text-center">
                    <img src="{{ $passport }}" alt="Passport face"
                        class="rounded mt-1" id="avatar" width="200px" height="220px">
                </div>
                @endif

                  <div class="form-group col-md-6">

                    @if ($nextIsFace && !$validationStatus)
                        <label for="usr">Click And Take Your Face Picture</label>

                        <input class="btn btn-primary" type=button value="My Face" id="startCamBtn">


                        {{-- @if ($imageVerifying) --}}
                    <button class="btn btn-primary d-none" id="verifyingImage">
                      <span class="spinner-border spinner-border-sm"></span>
                      <span class="spinner-text">Please Wait for Verification..</span>
                    </button>

                    {{-- @endif --}}
                    @endif
                    <input  wire:model="passport" type="hidden" id="passport">


                  </div>





                  @if ($validationStatus)
                  <div class="form-group col-md-6">
                    <label for="usr">Prefer Tier:</label>
                      <select  wire:model="tier" class="form-control">
                        @foreach ($tiers as $tei)
                          <option value="{{ $tei->id }}" {{ $tei->id ==$tier?"selected":null }}>{{ $tei->name }}</option>
                        @endforeach

                      </select>


                      @error('tier')
                      <p class="text-danger">{{ $message }}</p>
                  @enderror
                  </div>

                  <div class="form-group col-md-6">
                    <label for="usr">ID Card Type:</label>
                      <select  wire:model="id_card_type" class="form-control">
                        @foreach ($ids as $item)
                        <option value="{{$item->id}}"> {{ $item->name }} </option>

                        @endforeach

                      </select>


                      @error('id_card_type')
                      <p class="text-danger">{{ $message }}</p>
                  @enderror
                  </div>


                  <div class="form-group col-md-4">
                    <label for="usr">ID card Front Face:</label>
                    <input type="file"  wire:model="id_card_front" accept=".png, .jpeg, .jpg, .pdf" placeholder="ID card File" class="form-control">
                    @error('id_card_front')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
                </div>

                <div class="form-group col-md-4">
                    <label for="usr">ID card Back Face (Optional):</label>
                    <input type="file"  wire:model="id_card_back" accept=".png, .jpeg, .jpg, .pdf" placeholder="ID card File" class="form-control">
                    @error('id_card_back')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
                </div>
                <div class="form-group col-md-4">
                  <label for="usr">ID Card Serial Number:</label>
                  <input type="text"  wire:model="id_card_serial_number"  placeholder="ID card Serial Number" class="form-control">
                  @error('id_card_serial_number')
                  <p class="text-danger">{{ $message }}</p>
              @enderror
              </div>


                  <div class="form-group col-md-6">
                    <label for="usr">Proof Of Address:</label>
                      <select  wire:model="proof_of_address_type" class="form-control">
                        <option value="nepa_bill">Nepa Bill</option>
                        <option value="pvc">PVC</option>
                        <option value="water_bill">Water  Bill</option>
                        <option value="account_statement">Account Statement</option>
                        <option value="house_agreement">House Agreement</option>
                      </select>


                      @error('proof_of_address_type')
                      <p class="text-danger">{{ $message }}</p>
                  @enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="usr">Proof Of Address File:</label>
                    <input  wire:model="proof_of_address" type="file" accept=".png, .jpeg, .jpg, .pdf" placeholder="ID card File" class="form-control">

                    @error('proof_of_address')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
                </div>



                  <div class="form-group col-md-6">
                    <label for="usr">Next Of Kin:</label>
                      <input type="text"  wire:model="next_of_kin" placeholder="Next Of Kin" class="form-control">
                      @error('next_ok_kin')
                      <p class="text-danger">{{ $message }}</p>
                  @enderror
                    </div>

                  <div class="form-group col-md-6">
                    <label for="usr">Next Of kin Phone Number:</label>
                      <input type="tel" wire:model="next_of_kin_phone_number" placeholder="Next Of Kin Phone" class="form-control">
                      @error('next_of_kin_phone_number')
                      <p class="text-danger">{{ $message }}</p>
                  @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="usr">Next Of kin Address:</label>
                          <textarea class="form-control" wire:model="next_of_kin_address" placeholder="Your message"></textarea>
                          @error('next_of_kin_address')
                          <p class="text-danger">{{ $message }}</p>
                      @enderror
                        </div>
                  <div class="form-group col-md-6">
                    <label for="usr">Source Of Income:</label>
                      <textarea class="form-control" wire:model="source_of_income" placeholder="Your message"></textarea>
                      @error('source_of_income')
                      <p class="text-danger">{{ $message }}</p>
                  @enderror
                    </div>




                  <div class="form-group mb-0">
                    <button type="submit" class="btn puprple_btn">Request Upgrade</button>
                  </div>

                  @endif
                </form>
            </div>

        </div>
    </div>
  </section>




  <div id="cam_holder"
  style="display: none; width: 400px; height: 430px; border: solid 1px #eee; position: fixed;top: 0; left: 0;z-index:10000;">
  <div id="my_camera" style="width: 400px; height: 400px; border: solid 1px #efe"></div>
  <div style="position: absolute; bottom: 0;">
      <div id="pre_take_buttons">
          <input type=button value="Take Snapshot" id="preview_snapshot">
      </div>
      <div id="post_take_buttons" style="display:none">
          <input type=button value="&lt; Take new photo" id="cancel_preview">
          <input type=button value="Use this photo &gt;" id="save_photo" style="font-weight:bold;">
      </div>
  </div>
</div>



@section('extra-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="{{ asset('ready/webcam/webcam.js') }}"></script>


<script language="JavaScript">




$(document).on("click", "#startCamBtn", function () {
		cam_caller = 'add';
		$('#cam_holder').css('display', '');
		startCam();
	});


	$(document).on("click", "#preview_snapshot", function () {
		preview_snapshot();
	});
	$(document).on("click", "#cancel_preview", function () {
		cancel_preview();
	});
	$(document).on("click", "#save_photo", function () {
		save_photo();
		$('#cam_holder').hide();
	});



	// function getBase64Image(img) {
	// 	var canvas = document.createElement("canvas");
	// 	canvas.width = img.width;
	// 	canvas.height = img.height;
	// 	var ctx = canvas.getContext("2d");
	// 	ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
	// 	var dataURL = canvas.toDataURL("image/png");
	// 	return dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
	// }

	// $(document).on("change", "#passport", function () {
	// 	setTimeout(function () {
	// 		var base64 = getBase64Image(document.getElementById("avatar"));
	// 		// alert(base64);
	// 		document.getElementById('passport').value = base64;
	// 	}, 500);
	// });



	function startCam() {
        //alert('start');
		try {
			Webcam.set({
				width: 600,
				height: 400,
				// final cropped size
				crop_width: 400,
				crop_height: 400,

				image_format: 'jpeg',
				jpeg_quality: 90,
				// flip horizontal (mirror mode)
				flip_horiz: true
			});
			Webcam.attach('#my_camera');
			Webcam.on('error', function (err) {

                Swal.fire({
                    title: webCamError+"\n"+err,
                    icon: 'error',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: "OK",
                    denyButtonText: 'Exit',
                  })
			});
		} catch (e) {
			// console.log('No Support Found')

            Swal.fire({
                title: webCamNotSupported,
                icon: 'error',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "OK",
                denyButtonText: 'Exit',
              })
		}
	}


	function preview_snapshot() {
		try { shutter.currentTime = 0; } catch (e) { ; }
		shutter.play();
		Webcam.freeze();

		$('#pre_take_buttons').css('display', 'none');
		$('#post_take_buttons').css('display', '');
	}

	function cancel_preview() {
		Webcam.unfreeze();
		$('#pre_take_buttons').css('display', '');
		$('#post_take_buttons').css('display', 'none');
	}

	function save_photo() {
		Webcam.snap(function (data_uri) {
			if (cam_caller == 'add')
				$('#avatar').attr("src", data_uri);
        $("#verifyingImage").removeClass('d-none')

			Webcam.reset();
			// var raw_image_data = data_uri.replace(/^data\:image\/\w+\;base64\,/, '');
            // console.log(raw_image_data);
            // $("#passport").val(raw_image_data).trigerChange;
            // $("#passport").val(raw_image_data).trigger("change");
                // Emit an `input` event to the Livewire server
                @this.set('passport', data_uri)
                // $this->emit('change', 'passport', raw_image_data);

		});
	}

	var shutter = new Audio();
	shutter.autoplay = false;
	shutter.src = navigator.userAgent.match(/Firefox/) ? "{{ asset('ready/webcam/shutter.ogg') }}":"{{ asset('ready/webcam/shutter.mp3') }}";


</script>
@endsection
