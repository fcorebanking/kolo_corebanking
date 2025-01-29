<?php

return [
    //Test Variables
    'base_url_test' => env('DOJAH_BASE_URL_TEST', ''),
    'authentication_key_test' => env('DOJAH_AUTHORIZATION_TEST', ''),
    'institution_code_test' => env('DOJAH_AUTHORIZATION_TEST', ''),
    'app_id_test' => env('DOJAH_APP_ID_TEST', ''),

    //Live Variables
    'base_url_live' => env('DOJAH_BASE_URL_LIVE', ''),
    'authentication_key_live' => env('DOJAH_AUTHORIZATION_LIVE', ''),
    'app_id_live' => env('DOJAH_APP_ID_LIVE', '')

];
