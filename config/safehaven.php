<?php

return [
    //Test Variables
    'grant_type_test' => env('SAFEHAVEN_GRANT_TYPE_TEST', 'client_credentials'),
    'client_assertion_type_test' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_TEST', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
    'client_id_test' => env('SAFEHAVEN_CLIENT_ID_TEST', '043a500992a0308a607d8e9665836b34'),
    'client_assertion_test' => env('SAFEHAVEN_CLIENT_ASSERTION_TEST', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL2NvcmUuZmlub3ZhLm5nIiwic3ViIjoiMDQzYTUwMDk5MmEwMzA4YTYwN2Q4ZTk2NjU4MzZiMzQiLCJhdWQiOiJodHRwczovL2FwaS5zYWZlaGF2ZW5tZmIuY29tIiwiaWF0IjoxNzMxNTg5NzAwLCJleHAiOjE3NjI5ODQ0ODd9.gZhZ7yEseSENcgOaUCxhpwBg8lyyW-smIK7oj2a93207EKRE6MBfzCpSz8saoISiXRWrNXHuZ2rIkOz562VCiX7ncZ5a39UJEuV4l5CH9zSr4Ky2xSJLkqJanJujpNtKlA3CBfp4vB_7FYGNImmhtKlBugUk7v8cIQBy77_earM'),
    'base_url_test' => env('SAFEHAVEN_BASE_URL_TEST', 'https://api.sandbox.safehavenmfb.com/'),

    //Live Variables
    'grant_type_live' => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
    'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
    'client_id_live' => env('SAFEHAVEN_CLIENT_ID_LIVE', '043a500992a0308a607d8e9665836b34'),
    'client_assertion_live' => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL2NvcmUuZmlub3ZhLm5nIiwic3ViIjoiMDQzYTUwMDk5MmEwMzA4YTYwN2Q4ZTk2NjU4MzZiMzQiLCJhdWQiOiJodHRwczovL2FwaS5zYWZlaGF2ZW5tZmIuY29tIiwiaWF0IjoxNzMxNTg5NzAwLCJleHAiOjE3NjI5ODQ0ODd9.gZhZ7yEseSENcgOaUCxhpwBg8lyyW-smIK7oj2a93207EKRE6MBfzCpSz8saoISiXRWrNXHuZ2rIkOz562VCiX7ncZ5a39UJEuV4l5CH9zSr4Ky2xSJLkqJanJujpNtKlA3CBfp4vB_7FYGNImmhtKlBugUk7v8cIQBy77_earM'),
    'base_url_live' => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),

];
