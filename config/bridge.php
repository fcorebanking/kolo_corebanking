<?php

return [
    //Test Variables
  
    'api_key_test'  => env('BRIDGE_API_KEY_TEST', ''),
    'base_url_test' => env('BRIDGE_BASE_URL_TEST', ' https://api.bridge.xyz/v0/'),

    //Live Variables

    'api_key_live'  => env('BRIDGE_API_KEY_LIVE', "mBlDGKwSWQ8hyYj1vT4f"),
    'base_url_live' => env('BRIDGE_BASE_URL_LIVE', ' https://api.bridge.xyz/v0/'),
];