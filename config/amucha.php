<?php

return [
    //Test Variables
    'base_url_test' => env('AMUCHA_BASE_URL_TEST', 'https://api.nomba.com/v1/'),
    'account_id_test' => env('AMUCHA_AUTHORIZATION_TEST', ''),
    'client_secret_test' => env('AMUCHA_AUTHORIZATION_TEST', ''),
    'client_id_test' => env('AMUCHA_APP_ID_TEST', ''),

    //Live Variables
    'base_url_live' => env('AMUCHA_BASE_URL_LIVE', 'https://api.nomba.com/v1/'),
    'account_id_live' => env('AMUCHA_AUTHORIZATION_LIVE', ''),
    'client_secret_live' => env('AMUCHA_AUTHORIZATION_LIVE', ''),
    'client_id_live' => env('AMUCHA_APP_ID_LIVE', ''),

];
