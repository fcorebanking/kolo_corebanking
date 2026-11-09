<?php

return [

    //Test Variables
    'client_id_test'          => env('PROVIDUS_CLIENT_ID_TEST', ''),
    'client_secret_test'      => env('PROVIDUS_CLIENT_SECRET_TEST', ''),
    'auth_signature_test'     => env('PROVIDUS_X_AUTH_SIGNATURE_TEST', ''),
    'base_url_test'           => env('PROVIDUS_BASE_URL_TEST', 'http://154.113.16.142:8088/appdevapi/api/'),
    'base_xpress_secret_test' => env('PROVIDUS_XPRESS_BASE_URL_TEST', ''),

    //Live Variables
    'client_id_live'          => env('PROVIDUS_CLIENT_ID_LIVE', ''),
    'client_secret_live'      => env('PROVIDUS_CLIENT_SECRET_LIVE', ''),
    'auth_signature_live'     => env('PROVIDUS_X_AUTH_SIGNATURE_LIVE', ''),
    'base_url_live'           => env('PROVIDUS_BASE_URL_LIVE', 'http://154.113.16.142:8088/appdevapi/api/'),
    'base_xpress_secret_live' => env('PROVIDUS_XPRESS_BASE_URL_LIVE', ''),

];
