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

];
