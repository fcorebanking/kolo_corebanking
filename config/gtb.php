<?php

return [
    //Test Variables
    'secret_key_test'          => env('GTB_SECRET_KEY_TEST', ''),
    'public_key_test'          => env('GTB_PUBLIC_KEY_TEST', ''),
    'beneficiary_account_test' => env('GTB_BENEFICIARY_ACCOUNT_TEST', ''),
    'base_url_test'            => env('GTB_BASE_URL_TEST', ''),
    'base_url_test'            => "https://sandbox-api-d.squadco.com",
    'public_key_test_squado'   => env('GTB_PUBLIC_KEY_TEST', ''),

    //Live Variables
    'secret_key_live'          => env('GTB_SECRET_KEY_LIVE', ''),
    'public_key_live'          => env('GTB_PUBLIC_KEY_LIVE', ''),
    'beneficiary_account_live' => env('GTB_BENEFICIARY_ACCOUNT_LIVE', ''),
    'base_url_live'            => env('GTB_BASE_URL_LIVE', ''),
    'base_url_live_squado'     => "https://api-d.squadco.com",
    'public_key_live_squado'   => env('GTB_PUBLIC_KEY_TEST', ''),
];
