<?php

return [
    //Test Variables
    'app_secret_test' => env('WIRELESSVERIFY_API_KEY', ''),
    'api_key_test' => env('WIRELESSVERIFY_API_KEY_TEST', ''),
    'base_url_test' => env('WOVEN_BASE_URL_TEST', ''),

    //Live Variables
    'app_secret_live' => env('WOVEN_APP_SECRET_LIVE', ''),
    'api_key_live' => env('WIRELESSVERIFY_API_KEY_LIVE', ''),
    'base_url_live' => env('WOVEN_BASE_URL_LIVE', ''),
];
