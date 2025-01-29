<?php

return [
    //Test Variables
    'private_key_test' => env('KLASHA_PRIVATE_KEY_TEST', ''),
    'public_key_test' => env('KLASHA_PUBLIC_KEY_TEST', ''),
    'encryption_key_test' => env('KLASHA_ENCRYPTION_KEY_TEST', ''),
    'base_url_test' => env('KLASHA_BASE_URL_TEST', ''),
 
    //Live Variables
    'private_key_live' => env('KLASHA_PRIVATE_KEY_LIVE', ''),
    'public_key_live' => env('KLASHA_PUBLIC_KEY_LIVE', ''),
    'encryption_key_live' => env('KLASHA_ENCRYPTION_KEY_LIVE', ''),
    'base_url_live' => env('KLASHA_BASE_URL_LIVE', ''),
];
