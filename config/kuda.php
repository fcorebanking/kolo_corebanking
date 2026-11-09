<?php

return [
    //Test Variables
    'email_test'              => env('KUDA_EMAIL_TEST', ''),
    'api_key_test'            => env('KUDA_API_KEY_TEST', ''),
    'base_url_test'           => env('KUDA_BASE_URL_TEST', 'https://kuda-openapi-uat.kudabank.com/v2.1/'),
    'email_test_linkforte'    => env('KUDA_EMAIL_TEST_LINKFORTE', ''),
    'api_key_test_linkforte'  => env('KUDA_API_KEY_TEST_LINKFORTE', ""),

    //Live Variables
    'email_live'              => env('KUDA_EMAIL_LIVE', ''),
    'api_key_live'            => env('KUDA_API_KEY_LIVE', ""),
    'base_url_live'           => env('KUDA_BASE_URL_LIVE', 'https://kuda-openapi.kuda.com/v2.1/'),
    'api_maintenace_key_live' => env('KUDA_API_MAINTENANCE_KEY_LIVE', ""),
    'email_live_linkforte'    => env('KUDA_EMAIL_LIVE_LINKFORTE', ''),
    'api_key_live_linkforte'  => env('KUDA_API_KEY_LIVE_LINKFORTE', ""),
    'email_live_goldencredit' => env('KUDA_EMAIL_LIVE_GOLDENCREDIT', ''),
    'api_key_live_goldencredit' => env('KUDA_API_KEY_LIVE_GOLDENCREDIT', ""),
];
