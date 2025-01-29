<?php

return [
    //Test Variables
    'email_test' => env('KUDA_EMAIL_TEST', ''),
    'api_key_test' => env('KUDA_API_KEY_TEST', ''),
    'base_url_test' => env('KUDA_BASE_URL_TEST', ''),
 
    //Live Variables
    'email_live' => env('KUDA_EMAIL_LIVE', 'bWVyY2hhbnQxQG1haWxpbmF0b3IuY29t'),
    'api_key_live' => env('KUDA_API_KEY_LIVE', 'cGFzc3dvcmQ='),
    'base_url_live' => env('KUDA_BASE_URL_LIVE', ''),
];
