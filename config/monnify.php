<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    */

    'base_url' => env('MONNIFY_BASE_URL', 'https://api.monnify.com'),
    'base_url_test' => env('MONNIFY_BASE_URL_TEST', 'https://sandbox.monnify.com'),

    /*
    |--------------------------------------------------------------------------
    | Finova
    |--------------------------------------------------------------------------
    */

    'finova' => [
        'api_key' => env('FINOVA_MONNIFY_API_KEY'),
        'secret_key' => env('FINOVA_MONNIFY_SECRET_KEY'),
        'wallet_account' => env('FINOVA_MONNIFY_WALLET_ACCOUNT'),
        'contract_code' => env('FINOVA_MONNIFY_CONTRACT_CODE')
    ],

    /*
    |--------------------------------------------------------------------------
    | DFS
    |--------------------------------------------------------------------------
    */

    'dfs' => [
        'api_key' => env('DFS_MONNIFY_API_KEY'),
        'secret_key' => env('DFS_MONNIFY_SECRET_KEY'),
        'wallet_account' => env('DFS_MONNIFY_WALLET_ACCOUNT'),
        'contract_code' => env('DFS_MONNIFY_CONTRACT_CODE')
    ],

];