$.ajax({
    type:'POST',
    url: "/validate_details",
    async:false,
    headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    // enctype: 'multipart/form-data',
        data:
            {
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                BVN: $('#BVN').val(),
                email: $('#email').val(),
                PIN: $('#PIN').val(),
                phone: $('#phone').val(),
                address: $('#address').val(),
                password: $('#password').val(),
                username: $('#username').val(),
                

            },


    success: (data) => {

                console.log(data)


                return 2;

                // alert('success')

                // alert($('#BVN').val());

            if (data['first_name']) {

                
                should_passx = 'no';

                // alert($('#last_name').val());


                document.getElementById('first_name_error').innerHTML= data['first_name']


                return should_pass;
                
                
            }

            if (data['last_name']) {

             
                should_passx = 'no';

                // alert($('#last_name').val());


                document.getElementById('last_name_error').innerHTML= data['last_name']


                return should_pass;
                
                
            }

            if (data['phone']) {

                should_passx = 'no';


                // alert($('#phone').val());


                document.getElementById('phone_error').innerHTML= data['phone']


                return should_pass;
                
                
            }

            
            if (data['address']) {

                should_passx = 'no';


                // alert($('#address_error').val());


                document.getElementById('address_error').innerHTML= data['address']


                return should_pass;
                
                
            }

            if (data['email']) {

                should_passx = 'no';


                // alert($('#email_error').val());


                document.getElementById('email_error').innerHTML= data['email']


                return should_pass;
                
                
            }

            if (data['BVN']) {

               


                // alert($('#BVN').val());


                document.getElementById('BVN_error').innerHTML= data['BVN']


                return should_pass;
                
                
            }

            if (data['PIN']) {

                should_passx = 'no';


                // alert($('#PIN').val());


                document.getElementById('PIN_error').innerHTML= data['PIN']


                return should_pass;
                
                
            }

            if (data['password']) {

                should_passx = 'no';

                // alert($('#password').val());


                document.getElementById('password_error').innerHTML= data['password']


                return should_pass;
                
                
            }

            if (data['username']) {

                should_passx = 'no';


                // alert($('#username').val());


                document.getElementById('username_error').innerHTML= data['username']



                return should_pass;
                
                
            }else{

                // $(form).steps("next");

                should_passx = 'no';

                alert('we are errors '+should_passx)

                return should_passx;

                // alert(should_passx)


                // alert('no errors')


            }


            // return should_passx;


    
    },
    error: function (data) {

        console.log(data);

        should_passx = 'yes';

        alert('we ajax errors'+should_passx)

        return should_passx;
    }


 });