<?php

return [
    //Test Variables
    'secret_key_test' => env('MONNIFY_SECRET_KEY_TEST', ''),
    'api_key_test' => env('MONNIFY_API_KEY_TEST', ''),
    'base_url_test' => env('MONNIFY_BASE_URL_TEST', ''),

    //Live Variables
    'secret_key_live' => env('MONNIFY_SECRET_KEY_LIVE', ''),
    'api_key_live' => env('MONNIFY_API_KEY_LIVE', ''),
    'base_url_live' => env('MONNIFY_BASE_URL_LIVE', ''),
];
