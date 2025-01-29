<?php

return [
    //Test Variables
    'public_key_test' => env('ASSET_MATRIX_PUBLIC_KEY_TEST', ''),
    'secret_key_test' => env('ASSET_MATRIX_SECRET_KEY_TEST', ''),
    'base_url_test' => env('ASSET_MATRIX_BASE_URL_TEST', ''),

    //Live Variables
    'public_key_live' => env('ASSET_MATRIX_PUBLIC_KEY_LIVE', ''),
    'secret_key_live' => env('ASSET_MATRIX_SECRET_KEY_LIVE', ''),
    'base_url_live' => env('ASSET_MATRIX_BASE_URL_LIVE', '')
];
