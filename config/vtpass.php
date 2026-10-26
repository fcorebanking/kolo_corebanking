<?php

return [
    //Test Variables
    'base_url_test' => env('VTUPASS_BASE_URL_TEST', 'https://sandbox.vtpass.com/api/'),
    'username_test' => env('VTUPASS_USERNAME_TEST', 'email'),
    'password_test' => env('VTUPASS_PASSWORD_TEST', 'password'),

    //Live Variables
    'base_url_live' => env('VTUPASS_BASE_URL_LIVE', 'https://vtpass.com/api/'),
    'username_live' => env('VTUPASS_USERNAME_LIVE', 'email'),
    'password_live' => env('VTUPASS_PASSWORD_LIVE', 'password'),
    'api_key_live' => env('VTPASS_API_KEY_LIVE'),
    'public_key_live' => env('VTPASS_PUBLIC_KEY_LIVE'),
    'secret_key_live' => env('VTPASS_SECRET_KEY_LIVE'),

];
