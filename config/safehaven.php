<?php

return [
    //Test Variables
    'grant_type_test'            => env('SAFEHAVEN_GRANT_TYPE_TEST', 'client_credentials'),
    'client_assertion_type_test' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_TEST', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
    'client_id_test'             => env('SAFEHAVEN_CLIENT_ID_TEST', ''),
    'client_assertion_test'      => env('SAFEHAVEN_CLIENT_ASSERTION_TEST', ''),
    'base_url_test'              => env('SAFEHAVEN_BASE_URL_TEST', 'https://api.sandbox.safehavenmfb.com/'),
    'base_url_sudo_test'         => env('SUDO_BASE_URL_TEST', 'https://api.sandbox.sudo.cards'),
    'base_url_sudo_test_key'     => env('SUDO_BASE_URL_TEST_KEY', ''),

    //Live Variables
    'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
    'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
    'client_id_live'             => env('SAFEHAVEN_CLIENT_ID_LIVE', ''),
    'client_assertion_live'      => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', ''),
    'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
    'base_url_sudo_live'         => env('SUDO_BASE_URL_LIVE', 'https://api.sudo.africa'),
    'base_url_sudo_live_key'     => env('SUDO_BASE_URL_LIVE_KEY', ''),

];
