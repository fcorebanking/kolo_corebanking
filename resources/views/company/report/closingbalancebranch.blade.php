@extends('layouts.app')

@section('content')
<div class="box-body">
    <div class="row m-2">
        <div class="col-sm-6 col-md-4">
            <select  name="from" class="form-control is-datepick" id="currency"  required="">
                @foreach($currencies as $currency)
                    <option value='{{ $currency->code }}'>{{$currency->name}} - {{$currency->code}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-md-4">
            <select  name="staff" class="form-control is-datepick" id="staff"  required="">
                @foreach($branchstaff as $staffdata)
                    <option value='{{ $staffdata->uuid }}'>{{$staffdata->first_name}} - {{$staffdata->first_name}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-md-4">
            <span class="input-group-btn">
                <button type="button" class="btn bg-info btn-flat" onclick='balancesheet()'>Search!</button>
            </span>
        </div>
        <div class="col-sm-6 col-md-4">
            <span class="input-group-btn">
        <button class='btn btn-primary' id='verifybtn' onclick="getNumberSystemBalance('verifybtn', 'Verify')">Verify</button>
        <div id='till-status'></div>
    </span>
</div>
    </div>
    <table>
        <tr>
        <th><h5>Denom</h5></th>
        <th><h5>Quality</h5></th>
        <th><h5>Amount</h5></th>
        <th><h5>Computer balance</h5></th>

        </tr>
        <tbody>
        <tr>
            <td><p>10,000</p></td>
            <td><input type='number' class='form-control' id='ten-thousand-quantity' /></td>
            <td><input type='number' class='form-control' id='ten-thousand' /></td>
            <td><input type='number' class='form-control db_data' id='ten-thousand-quantity-res' /></td>

        </tr>

        <tr>
            <td><p>5,000</p></td>
                        <td> <input type='number' class='form-control' id='five-thousand-quantity' /></td>
                        <td><input type='number' class='form-control' id='five-thousand' /></td>
                        <td><input type='number' class='form-control db_data' id='five-thousand-quantity-res' /></td>


                    </tr>
        <tr>
            <td><p>2,000</p></td>
            <td><input type='number' class='form-control' id='two-thousand-quantity' /></td>
            <td><input type='number' class='form-control' id='two-thousand' /></td>
            <td><input type='number' class='form-control db_data' id='two-thousand-quantity-res' /></td>

        </tr>
        <tr>
            <td><p>1,000</p></td>
                        <td><input type='number' class='form-control' id='one-thousand-quantity' /></td>
                        <td><input type='number' class='form-control' id='one-thousand' /></td>
            <td><input type='number' class='form-control db_data' id='one-thousand-quantity-res' /></td>


        </tr>
        <tr>
            <td><p>500 Note</p></td>
                        <td><input type='number' class='form-control' id='five-hundred-note-quantity' /></td>
                        <td><input type='number' class='form-control' id='five-hundred-note' /></td>
            <td><input type='number' class='form-control db_data' id='five-hundred-note-quantity-res' /></td>

        </tr>
        <tr>
            <td><p>500 Coin</p></td>
                        <td><input type='number' class='form-control' id='five-hundred-coin-quantity' /></td>
                        <td><input type='number' class='form-control' id='five-hundred-coin' /></td>
                        <td><input type='number' class='form-control db_data' id='five-hundred-coin-quantity-res' /></td>


        </tr>
        <tr>
            <td><p>100</p></td>
                        <td><input type='number' class='form-control' id='one-hundred-quantity' /></td>
                        <td><input type='number' class='form-control' id='one-hundred' /></td>
                        <td><input type='number' class='form-control db_data' id='one-hundred-quantity-res' /></td>


        </tr>
        <tr>
            <td><p>50</p></td>
                        <td><input type='number' class='form-control' id='fifthy-quantity' /></td>
                        <td><input type='number' class='form-control' id='fifthy' /></td>
                        <td><input type='number' class='form-control db_data' id='fifthy-quantity-res' /></td>


        </tr>
        <tr>
            <td><p>25</p></td>
                        <td><input type='number' class='form-control' id='twenty-five-quantity' /></td>
                        <td><input type='number' class='form-control' id='twenty-five' /></td>
                        <td><input type='number' class='form-control db_data' id='twenty-five-quantity-res' /></td>

        </tr>
        <tr>
            <td><p>10</p></td>
                        <td><input type='number' class='form-control' id='ten-quantity' /></td>
                        <td><input type='number' class='form-control' id='ten' /></td>
                        <td><input type='number' class='form-control db_data' id='ten-quantity-res' /></td>

        </tr>
        <tr>
            <td><p>5</p></td>
                        <td><input type='number' class='form-control' id='five-quantity' /></td>
                        <td><input type='number' class='form-control' id='five' /></td>
                        <td><input type='number' class='form-control db_data' id='five-quantity-res' /></td>

        </tr>
        <tr>
            <td><p>2</p></td>
                        <td><input type='number' class='form-control' id='two-quantity' /></td>
                        <td><input type='number' class='form-control' id='two' /></td>
                        <td><input type='number' class='form-control db_data' id='two-quantity-res' /></td>

        </tr>
        <tr>
            <td><p>1</p></td>
                        <td><input type='number' class='form-control' id='one-quantity' /></td>
                        <td><input type='number' class='form-control' id='one' /></td>
                        <td><input type='number' class='form-control db_data' id='one-quantity-res' /></td>

        </tr>
        <tr>
            <td></td>
            <td>
                <label>Total</label>
            </td>
            <td>
                <input type='number' class='form-control' id='total-input' />
            </td>
            <td>
                <input type='number' class='form-control' id='system-balance' />

            </td>
        </tr>
    </tbody>
    </table>

<div class='row'>
    <div class='col-sm-6 col-md-6'><button type='button' class='btn btn-primary' onclick='openBalance("closebtn", "Close balance","close")'>Close Balance</button></div>
    <div class='col-sm-6 col-md-6'><button type='button' class='btn btn-primary' onclick='openBalance("openbtn", "Open balance","open")'>Open Balance</button></div>
</div>
</div>


<script>

    document.addEventListener('DOMContentLoaded', function() {
        // Add event listener for each quantity input
        $('.db_data').hide()
        function updateAmount(rowId) {
            const quantityInput = document.getElementById(`${rowId}-quantity`);
            const amountInput = document.getElementById(`${rowId}`);
            let rate =0;
            if(rowId  === 'ten-thousand'){
                rate = 10000
            }else if(rowId  === 'five-thousand'){
                rate = 5000
            }else if(rowId  === 'two-thousand'){
                rate = 2000
            }else if(rowId  === 'one-thousand'){
                rate = 1000
            }else if(rowId  === 'five-hundred-note'){
                rate = 500
            }else if(rowId  === 'five-hundred-coin'){
                rate = 500
            }else if(rowId  === 'one-hundred'){
                rate = 100
            }else if(rowId  === 'fifthy'){
                rate = 50
            }else if(rowId  === 'twenty-five'){
                rate = 25
            }else if(rowId  === 'ten'){
                rate = 10
            }else if(rowId  === 'five'){
                rate = 5
            }else if(rowId  === 'two'){
                rate = 2
            }else if(rowId  === 'one'){
                rate = 1
            }
            quantityInput.addEventListener('input', function() {
                const quantity = parseInt(this.value) || 0;
                const amount = rate * quantity; // Replace 10000 with the appropriate amount for each row
                amountInput.value = amount;
                sumInput();
            });
        }
function sumInput(){
    const tenthousand = Number($('#ten-thousand').val())
    const fivethousand = Number($('#five-thousand').val())
    const twothousand = Number($('#two-thousand').val())
    const onethousand = Number($('#one-thousand').val())
    const fivehundrednote = Number($('#five-hundred-note').val())
    const onehundred = Number($('#one-hundred').val())
    const fivehundredcoin = Number($('#five-hundred-coin').val())
    const fifthy = Number($('#fifthy').val())
    const twothundred = Number($('#twenty-five').val())
    const ten = Number($('#ten').val())
    const five = Number($('#five').val())
    const two = Number($('#two').val())
    const one = Number($('#one').val())
    const total = document.getElementById(`total-input`);
    const coin = fivehundredcoin/100;
    total.value = (tenthousand +fivethousand+ twothousand + onethousand+fivehundrednote+ onehundred +fifthy+twothundred+ ten+five+two+one+coin )
}
        // Add event listeners for each row
        updateAmount('ten-thousand');
        updateAmount('five-thousand');
        updateAmount('two-thousand');
        updateAmount('one-thousand');
        updateAmount('five-hundred-note');
        updateAmount('five-hundred-coin');
        updateAmount('one-hundred');
        updateAmount('fifthy');
        updateAmount('twenty-five');
        updateAmount('five');
        updateAmount('ten');
        updateAmount('two');
        updateAmount('one');
        // Add similar calls for other rows

    });

    function getNumberSystemBalance(btn, txt){

        $.ajax({
        url: '/company/accounting/verify_closing_balance?currency='+$('#currency').val(),
        type: 'get',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('.toast').removeClass("bg-success");
            $('.toast').removeClass("bg-danger");
            $(".toast-msg").html(data.message);
            var status;
            if (data.status) {
                $('.db_data').show()
                if(data.data.status=='closed'){
                    $('#till-status').html("<div class='badge badge-danger'>Till Closed</div>");

                }else{

                    $('#till-status').html("<div class='badge badge-success'>Till Open</div>");

                }

                $('#ten-thousand-quantity-res').val(data.data.ten_thousand_quantity)
            $('#five-thousand-quantity-res').val(data.data.five_thousand_quantity)
            $('#two-thousand-quantity-res').val(data.data.two_thousand_quantity)
            $('#one-thousand-quantity-res').val(data.data.one_thousand_quantity)
            $('#five-hundred-note-quantity-res').val(data.data.five_hundred_note_quantity)
            $('#five-hundred-coin-quantity-res').val(data.data.five_hundred_coin_quantity)
           $('#one-hundred-quantity-res').val(data.data.one_hundred_quantity)
            $('#fifthy-quantity-res').val(data.data.fifthy_quantity)
            $('#twenty-five-quantity-res').val(data.data.twenty_five_quantity)
            $('#ten-quantity-res').val(data.data.ten_quantity)
           $('#five-quantity-res').val(data.data.five_quantity)
            $('#two-quantity-res').val(data.data.two_quantity)
            $('#one-quantity-res').val(data.data.one_quantity)
            $('#system-balance').val(data.data.total)

            if(Number(data.data.total) != Number($('#total-input').val())){
                $('.db_data').css('border', '1px solid red');
                console.log(Number(data.data.total), Number($('#total-input').val()))
            }

            } else if (data.status == 0) {
                data.status = 'bg-danger';
                status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    status
                )
                $('.responseMessage').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
            $('.toast').addClass(data.status);
            $('.token').val(data.token);
            if (btn != null) {
                stopLoadingButton(btn, txt);
                enableBtn(btn);
            }
            return data;

        },
        error: function(jqXHR, status, error) {


            $('.toast').addClass("bg-danger");
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connected\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else {
                msg = 'Uncaught Error.\n' + error;
            }

            $('.toast-msg').html(msg);
        },
        xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            xhr.onprogress = function e() {
                // For downloads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                    $(".toast-msg").html(e.loaded / e.total);
                }
            };
            xhr.upload.onprogress = function(e) {
                // For uploads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                }
            };
            return xhr;
        }
    })
}
    function openBalance(btn, txt,status){
        if(($('#total-input').val()).length < 1){
            alert('input atleast one denomination');
            return false;
        }
        const pageUrl = (status==='close')?'/company/accounting/close_balance':'/company/accounting/open_balance'
        data_info={
            ten_thousand_quantity:$('#ten-thousand-quantity').val(),
            five_thousand_quantity:$('#five-thousand-quantity').val(),
            two_thousand_quantity:$('#two-thousand-quantity').val(),
            one_thousand_quantity:$('#one-thousand-quantity').val(),
            five_hundred_note_quantity:$('#five-hundred-note-quantity').val(),
            five_hundred_coin_quantity:$('#five-hundred-coin-quantity').val(),
            one_hundred_quantity:$('#one-hundred-quantity').val(),
            fifthy_quantity:$('#fifthy-quantity').val(),
            twenty_five_quantity:$('#twenty-five-quantity').val(),
            ten_quantity:$('#ten-quantity').val(),
            five_quantity:$('#five-quantity').val(),
            two_quantity:$('#two-quantity').val(),
            one_quantity:$('#one-quantity').val(),
            currency:$('#currency').val(),

            ten_thousand_amount:$('#ten-thousand').val(),
            five_thousand_amount:$('#five-thousand').val(),
            two_thousand_amount:$('#two-thousand').val(),
            one_thousand_amount:$('#one-thousand').val(),
            five_hundred_note_amount:$('#five-hundred-note').val(),
            five_hundred_coin_amount:$('#five-hundred-coin').val(),
            one_hundred_amount:$('#one-hundred').val(),
            fifthy_amount:$('#fifthy').val(),
            twenty_five_amount:$('#twenty-five').val(),
            ten_amount:$('#ten').val(),
            five_amount:$('#five').val(),
            two_amount:$('#two').val(),
            one_amount:$('#one').val(),
            total:$('#total-input').val()

        }
        ajax_operation('post', pageUrl, data_info, btn , txt )

    }
    </script>
@endsection
