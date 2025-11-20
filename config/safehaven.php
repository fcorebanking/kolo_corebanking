<?php

return [
    //Test Variables
    'grant_type_test'            => env('SAFEHAVEN_GRANT_TYPE_TEST', 'client_credentials'),
    'client_assertion_type_test' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_TEST', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
    'client_id_test'             => env('SAFEHAVEN_CLIENT_ID_TEST', '043a500992a0308a607d8e9665836b34'),
    'client_assertion_test'      => env('SAFEHAVEN_CLIENT_ASSERTION_TEST', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL2NvcmUuZmlub3ZhLm5nIiwic3ViIjoiMDQzYTUwMDk5MmEwMzA4YTYwN2Q4ZTk2NjU4MzZiMzQiLCJhdWQiOiJodHRwczovL2FwaS5zYWZlaGF2ZW5tZmIuY29tIiwiaWF0IjoxNzMxNTg5NzAwLCJleHAiOjE3NjI5ODQ0ODd9.gZhZ7yEseSENcgOaUCxhpwBg8lyyW-smIK7oj2a93207EKRE6MBfzCpSz8saoISiXRWrNXHuZ2rIkOz562VCiX7ncZ5a39UJEuV4l5CH9zSr4Ky2xSJLkqJanJujpNtKlA3CBfp4vB_7FYGNImmhtKlBugUk7v8cIQBy77_earM'),
    'base_url_test'              => env('SAFEHAVEN_BASE_URL_TEST', 'https://api.sandbox.safehavenmfb.com/'),
    'base_url_sudo_test'         => env('SUDO_BASE_URL_TEST', 'https://api.sandbox.sudo.cards'),
    'base_url_sudo_test_key'     => env('SUDO_BASE_URL_TEST_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI2NmQwZThkMTc2MWVmMWYxY2Y2YmRmZGUiLCJlbWFpbEFkZHJlc3MiOiJpbmZvckBmaW5vdmEubmciLCJqdGkiOiI2OTFmMWMxYTk2YjEzMjJjMTEyYzkyYWUiLCJtZW1iZXJzaGlwIjp7Il9pZCI6IjY2ZDBlOGQxNzYxZWYxZjFjZjZiZGZlMSIsImJ1c2luZXNzIjp7Il9pZCI6IjY2ZDBlOGQxNzYxZWYxZjFjZjZiZGZkYyIsIm5hbWUiOiJGSU5PVkEgTElNSVRFRCIsImlzQXBwcm92ZWQiOnRydWV9LCJ1c2VyIjoiNjZkMGU4ZDE3NjFlZjFmMWNmNmJkZmRlIiwicm9sZSI6IkFQSUtleSJ9LCJpYXQiOjE3NjM2NDY0OTAsImV4cCI6MTc5NTIwNDA5MH0.bOruiVrOMVohDsKAPzAs3Ds7rIQwT0RsqQPi9-lKgfg'),

    //Live Variables
    'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
    'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
    'client_id_live'             => env('SAFEHAVEN_CLIENT_ID_LIVE', '043a500992a0308a607d8e9665836b34'),
    'client_assertion_live'      => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL2NvcmUuZmlub3ZhLm5nIiwic3ViIjoiMDQzYTUwMDk5MmEwMzA4YTYwN2Q4ZTk2NjU4MzZiMzQiLCJhdWQiOiJodHRwczovL2FwaS5zYWZlaGF2ZW5tZmIuY29tIiwiaWF0IjoxNzMxNTg5NzAwLCJleHAiOjE3NjI5ODQ0ODd9.gZhZ7yEseSENcgOaUCxhpwBg8lyyW-smIK7oj2a93207EKRE6MBfzCpSz8saoISiXRWrNXHuZ2rIkOz562VCiX7ncZ5a39UJEuV4l5CH9zSr4Ky2xSJLkqJanJujpNtKlA3CBfp4vB_7FYGNImmhtKlBugUk7v8cIQBy77_earM'),
    'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
    'base_url_sudo_live'         => env('SUDO_BASE_URL_LIVE', 'https://api.sandbox.sudo.cards'),
    'base_url_sudo_live_key'     => env('SUDO_BASE_URL_LIVE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI2NmQwZThkMTc2MWVmMWYxY2Y2YmRmZGUiLCJlbWFpbEFkZHJlc3MiOiJpbmZvckBmaW5vdmEubmciLCJqdGkiOiI2N2QyZjExZGFkMWJlODFjNTdmZGUzNWMiLCJtZW1iZXJzaGlwIjp7Il9pZCI6IjY2ZDBlOGQxNzYxZWYxZjFjZjZiZGZlMSIsImJ1c2luZXNzIjp7Il9pZCI6IjY2ZDBlOGQxNzYxZWYxZjFjZjZiZGZkYyIsIm5hbWUiOiJGSU5PVkEgTElNSVRFRCIsImlzQXBwcm92ZWQiOnRydWV9LCJ1c2VyIjoiNjZkMGU4ZDE3NjFlZjFmMWNmNmJkZmRlIiwicm9sZSI6IkFQSUtleSJ9LCJpYXQiOjE3NDE4Nzc1MzMsImV4cCI6MTc3MzQzNTEzM30.dtDFTlTPzwWgHoVct9500W8GBi00fCqILzKXR-d678U'),

];
