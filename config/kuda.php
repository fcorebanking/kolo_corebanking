<?php

return [
    //Test Variables
    'email_test'    => env('KUDA_EMAIL_TEST', ''),
    'api_key_test'  => env('KUDA_API_KEY_TEST', ''),
    'base_url_test' => env('KUDA_BASE_URL_TEST', ''),

    //Live Variables
    'email_live'    => env('KUDA_EMAIL_LIVE', 'simtec247@gmail.com'),
    'api_key_live'  => env('KUDA_API_KEY_LIVE', "UlBV6z5u8xF19JeLIvf2"),
    'base_url_live' => env('KUDA_BASE_URL_LIVE', 'https://kuda-openapi-uat.kudabank.com/'),
];
