<?php

return [
    //Test Variables
    'base_url_test'      => env('IDENTITYPASS_BASE_URL_TEST', 'https://api.prembly.com/'),
    'authorization_test' => env('IDENTITYPASS_AUTHORIZATION_TEST', ''),
    'app_id_test'        => env('IDENTITYPASS_APP_ID_TEST', ''),
    'app_key_test'       => env('IDENTITYPASS_APP_KEY_TEST', ''),

    //Live Variables
    'base_url_live'      => env('IDENTITYPASS_BASE_URL_LIVE', 'https://api.prembly.com/'),
    'authorization_live' => env('IDENTITYPASS_AUTHORIZATION_LIVE', ''),
    'app_id_live'        => env('IDENTITYPASS_APP_ID_LIVE', ''),

    'app_key_live'       => env('IDENTITYPASS_APP_KEY_LIVE', ''),

];
