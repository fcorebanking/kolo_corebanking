<?php

return [
    //Test Variables
    'email_test'    => env('KUDA_EMAIL_TEST', ''),
    'api_key_test'  => env('KUDA_API_KEY_TEST', ''),
    'base_url_test' => env('KUDA_BASE_URL_TEST', ''),

    //Live Variables
    'email_live'    => env('KUDA_BUSINESS_EMAIL_LIVE', 'Betapey@gmail.com'),
    'api_key_live'  => env('KUDA_BUSINESS_API_KEY_LIVE', "oIQvymWqwKxEFOrSA1fp"),
    'base_url_live' => env('KUDA_BUSINESS_BASE_URL_LIVE', 'https://kuda-openapi.kuda.com/'),
];
