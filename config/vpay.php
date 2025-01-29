<?php

return [
    //Test Variables
    'username_test' => env('VPAY_USERNAME_TEST', 'simtec247@gmail.com'),
    'password_text' => env('VPAY_PASSWORD_TEST', 'Oluwoyo@24'),
    'public_key_test' => env('VPAY_PUBLIC_KEY_TEST', 'f43001f8-2388-4a2a-97b1-be5a82568cf1'),
    'secret_key_test' => env('VPAY_SECRET_KEY_TEST', 'd2f53bed-14e8-4498-bc85-72e3cc57c797'),
    'base_url_test' => env('VPAY_BASE_URL_TEST', 'https://services2.vpay.africa/api/service/v1/'),

    //Live Variables
    'username_live' => env('VPAY_USERNAME_LIVE', 'simtec247@gmail.com'),
    'password_live' => env('VPAY_PASSWORD_LIVE', 'Oluwoyo@24'),
    'public_key_live' => env('VPAY_PUBLIC_KEY_LIVE', 'f43001f8-2388-4a2a-97b1-be5a82568cf1'),
    'secret_key_live' => env('VPAY_SECRET_KEY_LIVE', 'd2f53bed-14e8-4498-bc85-72e3cc57c797'),
    'base_url_live' => env('VPAY_BASE_URL_LIVE', 'https://services2.vpay.africa/api/service/v1/'),
];
