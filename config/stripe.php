<?php

return [

    //Test Variables
    'secret_key_test' => env('STRIPE_SECRET_KEY_TEST', ''),
    'public_key_test' => env('STRIPE_PUBLIC_KEY_TEST', ''),

    //Live Variables
    'secret_key_live' => env('STRIPE_SECRET_KEY_LIVE', ''),
    'public_key_live' => env('STRIPE_PUBLIC_KEY_LIVE', ''),
];
