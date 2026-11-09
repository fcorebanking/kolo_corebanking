<?php

return [
    //Test Variables
    'username_test'   => env('VPAY_USERNAME_TEST', ''),
    'password_text'   => env('VPAY_PASSWORD_TEST', ''),
    'public_key_test' => env('VPAY_PUBLIC_KEY_TEST', ''),
    'secret_key_test' => env('VPAY_SECRET_KEY_TEST', ''),
    'base_url_test'   => env('VPAY_BASE_URL_TEST', 'https://api-devapps.vfdbank.systems/vtech-wallet/api/v2/wallet2/'),

    //Live Variables
    'username_live'   => env('VPAY_USERNAME_LIVE', ''),
    'password_live'   => env('VPAY_PASSWORD_LIVE', ''),
    'public_key_live' => env('VPAY_PUBLIC_KEY_LIVE', ''),
    'secret_key_live' => env('VPAY_SECRET_KEY_LIVE', ''),
    'base_url_live'   => env('VPAY_BASE_URL_LIVE', 'https://api-apps.vfdbank.systems/vtech-wallet/api/v2/wallet2/'),
];
