      $(function() {
          $('#upload').change(function() {
              var input = this;
              var url = $(this).val();
              var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
              if (input.files && input.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) {
                  var reader = new FileReader();

                  reader.onload = function(e) {
                      $('#img').attr('src', e.target.result);
                  }
                  reader.readAsDataURL(input.files[0]);
              } else {
                  $('#img').attr('src', '/assets/no_preview.png');
              }
          });

      });

      $('#profileBTN').attr('class', 'badge badge-primary  text-light');
      $('.password-setting').hide();
      $('.pin-setting').hide();
      $("#profileBTN").click(function() {
          $('.password-setting').hide();
          $('.pin-setting').hide();
          $('.profile-setting').show();

          $('#profileBTN').attr('class', 'badge badge-primary text-light');

          $('#passwordBTN').removeAttr('class');
          $('#passwordBTN').attr('class', 'badge badge-flat');
          $('#pinBTN').removeAttr('class');
          $('#pinBTN').attr('class', 'badge badge-flat');
          $('#statustxt').val('profile');
          $('.state').html('Profile Setting');


      });
      $("#passwordBTN").click(function() {
          $('.password-setting').show();
          $('.pin-setting').hide();
          $('.profile-setting').hide();

          $('#passwordBTN').attr('class', 'badge badge-primary text-light');

          $('#profileBTN').removeAttr('class');
          $('#profileBTN').attr('class', 'badge badge-flat');
          $('#pinBTN').removeAttr('class');
          $('#pinBTN').attr('class', 'badge badge-flat');
          $('#statustxt').val('password');
          $('.state').html('Password Setting');


      });
      $("#pinBTN").click(function() {
          $('.password-setting').hide();
          $('.pin-setting').show();
          $('.profile-setting').hide();

          $('#pinBTN').attr('class', 'badge badge-primary text-light');

          $('#profileBTN').removeAttr('class');
          $('#profileBTN').attr('class', 'badge badge-flat');
          $('#passwordBTN').removeAttr('class');
          $('#passwordBTN').attr('class', 'badge badge-flat');
          $('#statustxt').val('pin');
          $('.state').html('Pin Setting');


      });