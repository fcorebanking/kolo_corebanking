<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top up Currency</title>

    <!-- Include Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 50px;
        }

        .card-header {
            background-color: #007bff;
            color: #ffffff;
            font-weight: bold;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Pending Transaction Details</h2>

        <!-- Display Transaction Details -->
        <div class="card mt-3">
            <div class="card-header">
                Transaction Information
            </div>
            <div class="card-body">
                <!-- Display your transaction details here -->
                <p class="mb-1"><strong>Transaction ID:</strong> {{ $dataT['instrumentCode'] }}</p>
                <p class="mb-1"><strong>Amount:</strong> {{ $dataT['currency'].$dataT['amount'] }}</p>
                <p class="mb-1"><strong>Sender:</strong> {{ $dataT['phone'] }}</p>
                <p class="mb-1"><strong>Naration:</strong> {{ $dataT['narration'] }}</p>
                <p class="mb-1"><strong>Status:</strong> <span class='status_txn'>Pending</span></p>
                <a class='' onclick="sendRequest()">Manualy check response</a>
                <!-- Add more details as needed -->
            </div>
        </div>

        <!-- Transaction Processing Indicator -->
        <div class="alert alert-info mt-3 res" role="alert">
            <strong>Transaction is processing.</strong> Please stay on this page for response.
        </div>

        <!-- JavaScript Script to Send Request Every Minute -->
        <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
        <script>
            // Function to send a request to your endpoint
            function sendRequest() {
                // Use your Laravel route or endpoint URL
                var endpointUrl = "/admin-company-wallet-webhook?reference={{ $dataT['instrumentCode'] }}&currency={{$dataT['currency']}}";

                // Use AJAX to send a GET request to the endpoint
                $.ajax({
                    url: endpointUrl,
                    type: 'GET',
                    success: function(response) {
                        console.log('Request successful:', response);
                        if("{{$dataT['currency']}}" === 'XAF'){
                            if(response.data.data.status=='failed'){
                                $('.status_txn').html('<span class="btn btn-danger">'+response.data.data.status+'</span>')
                            }else if(response.data.data.status=='pending'){
                                $('.status_txn').html('<span class="btn btn-warning">'+response.data.data.status+'</span>')
                            }else if(response.data.data.status=='successful'){
                                $('.status_txn').html('<span class="btn btn-success">'+response.data.data.status+'</span>')
                            }
                            $('.res').html(response.data.data.processor_response)
                        }
                        if("{{$dataT['currency']}}" === 'ZMW'){
                            if(response.data.status ==='Successful'){
                                $('.status_txn').html('<span class="btn btn-success">'+response.data.status+'</span>')

                            }else if(response.data.status ==='Failed'){
                                $('.status_txn').html('<span class="btn btn-danger">'+response.data.status+'</span>')

                            }else{
                                $('.status_txn').html('<span class="btn btn-warning">'+response.data.status+'</span>')

                            }
                            $('.res').html(response.data.message)
                        }
                    },
                    error: function(error) {
                        console.error('Error in request:', error);
                    }
                });
            }

            // Call the sendRequest function every one minute
            setInterval(sendRequest, 60000); // 60000 milliseconds = 1 minute
        </script>
    </div>
</body>
</html>
