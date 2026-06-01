<?php

return [
    //Test Variables

    'api_key_test'  => env('BRIDGE_API_KEY_TEST', 'sk-test-9aa44f62b0bfc1ded6d68eeff5aa2496'),
    'base_url_test' => env('BRIDGE_BASE_URL_TEST', 'https://api.sandbox.bridge.xyz/v0/'),

    //Live Variables

    'api_key_live'  => env('BRIDGE_API_KEY_LIVE', "sk-live-ab49d274063f4d749197d2cbbec7ca14"),
    'base_url_live' => env('BRIDGE_BASE_URL_LIVE', 'https://api.bridge.xyz/v0/'),
];
