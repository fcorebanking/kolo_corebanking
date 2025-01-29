@extends('layouts.app')

@section('content')
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Account Form</title>`
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container-fluid {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        label {
            margin-top: 10px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            box-sizing: border-box;
        }

        button {
            background-color: #3498db;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

    </style>

</head>
<body>

<div class="container-fluid">

    <form action="{{ route('submit-form') }}" method="POST">
        @csrf
        <h2>Account Information</h2>
        <div class='alert alert-warning'>By deleting your account all your info will be deleted within our system</div>
        <label for="account_number">Account Number:</label>
        <input type="text" id="account_number" name="account_number" required>

        <label for="pin">Transaction PIN:</label>
        <input type="password" id="pin" name="pin" required>

        <button type="submit">Submit</button>
    </form>
</div>

</body>
@endsection

