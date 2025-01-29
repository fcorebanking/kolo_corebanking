<?php

return [
    //Test Variables
    'email_test' => env('EMAIL_TEST', ''),
    'authorization_test' => env('PARKWAY_AUTHORIZATION_TEST', ''),
    'signature_test' => env('PARKWAY_SIGNATURE_TEST', ''),
    'base_url_test' => env('PARKWAY_BASE_URL_TEST', ''),

    //Live Variables
    'email_live' => env('EMAIL_LIVE', ''),
    'authorization_live' => env('PARKWAY_AUTHORIZATION_LIVE', ''),
    'signature_live' => env('PARKWAY_SIGNATURE_LIVE', ''),
    'base_url_live' => env('PARKWAY_BASE_URL_LIVE', ''),
];
