<?php

return [
    //Test Variables
    'email_test'    => env('FINCRA_EMAIL_TEST', ''),
    'api_key_test'  => env('FINCRA_API_KEY_TEST', ''),
    'base_url_test' => env('FINCRA_BASE_URL_TEST', 'https://sandboxapi.fincra.com/'),

    //Live Variables
    'email_live'    => env('FINCRA_EMAIL_LIVE', 'simtec247@gmail.com'),
    'api_key_live'  => env('FINCRA_API_KEY_LIVE', "mBlDGKwSWQ8hyYj1vT4f"),
    'base_url_live' => env('FINCRA_BASE_URL_LIVE', 'https://sandboxapi.fincra.com/'),
];
