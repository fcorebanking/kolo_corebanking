<?php

return [
    //Test Variables
    'app_secret_test' => env('WOVEN_APP_SECRET_TEST', ''),
    'api_key_test' => env('WOVEN_API_KEY_TEST', ''),
    'base_url_test' => env('WOVEN_BASE_URL_TEST', ''),
    'settlement_account_test' => env('WOVEN_SETTLEMENT_ACCOUNT_TEST', ''),

    //Live Variables
    'app_secret_live' => env('WOVEN_APP_SECRET_LIVE', ''),
    'api_key_live' => env('WOVEN_API_KEY_LIVE', ''),
    'base_url_live' => env('WOVEN_BASE_URL_LIVE', ''),
    'settlement_account_live' => env('WOVEN_SETTLEMENT_ACCOUNT_LIVE', ''),
];
