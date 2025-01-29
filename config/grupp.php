<?php

return [

    //Test Variables
    'secret_key_test' => env('GRUPP_SECRET_KEY_TEST', ''),
    'public_key_test' => env('GRUPP_PUBLIC_KEY_TEST', ''),
    'encryption_key_test' => env('GRUPP_ENCRYPTION_KEY_TEST', ''),
    'base_url_test' => env('GRUPP_BASE_URL_TEST', ''),
 
    //Live Variables
    'secret_key_live' => env('GRUPP_SECRET_KEY_LIVE', ''),
    'public_key_live' => env('GRUPP_PUBLIC_KEY_LIVE', ''),
    'encryption_key_live' => env('GRUPP_ENCRYPTION_KEY_LIVE', ''),
    'base_url_live' => env('GRUPP_BASE_URL_LIVE', ''),

];
