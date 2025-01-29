<?php

return [
    //Test Variables
    'password_test' => env('ERRAND_PAY_PASSWORD_TEST', ''),
    'login_token_test' => env('ERRAND_PAY_LOGIN_TOKEN_TEST', ''),
    'base_url_test' => env('ERRAND_PAY_BASE_URL_TEST', ''),

    //Live Variables
    'password_live' => env('ERRAND_PAY_PASSWORD_LIVE', ''),
    'login_token_live' => env('ERRAND_PAY_LOGIN_TOKEN_LIVE', ''),
    'base_url_live' => env('ERRAND_PAY_BASE_URL_LIVE', ''),
];
