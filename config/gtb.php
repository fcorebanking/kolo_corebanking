<?php

return [

    // =========================================================
    // FINOVA
    // =========================================================

    'finova_secret_key_live' => env('GTB_FINOVA_SECRET_KEY_LIVE', ''),
    'finova_public_key_live' => env('GTB_FINOVA_PUBLIC_KEY_LIVE', ''),
    'finova_beneficiary_account_live' => env('GTB_FINOVA_BENEFICIARY_ACCOUNT_LIVE', ''),

    'finova_secret_key_test' => env('GTB_FINOVA_SECRET_KEY_TEST', ''),
    'finova_public_key_test' => env('GTB_FINOVA_PUBLIC_KEY_TEST', ''),
    'finova_beneficiary_account_test' => env('GTB_FINOVA_BENEFICIARY_ACCOUNT_TEST', ''),


    // =========================================================
    // ARIK
    // =========================================================

    'arik_secret_key_live' => env('GTB_ARIK_SECRET_KEY_LIVE', ''),
    'arik_public_key_live' => env('GTB_ARIK_PUBLIC_KEY_LIVE', ''),
    'arik_beneficiary_account_live' => env('GTB_ARIK_BENEFICIARY_ACCOUNT_LIVE', ''),

    'arik_secret_key_test' => env('GTB_ARIK_SECRET_KEY_TEST', ''),
    'arik_public_key_test' => env('GTB_ARIK_PUBLIC_KEY_TEST', ''),
    'arik_beneficiary_account_test' => env('GTB_ARIK_BENEFICIARY_ACCOUNT_TEST', ''),


    // =========================================================
    // FUNDIT SAVE
    // =========================================================

    'fundit_save_secret_key_live' => env('GTB_FUNDIT_SAVE_SECRET_KEY_LIVE', ''),
    'fundit_save_public_key_live' => env('GTB_FUNDIT_SAVE_PUBLIC_KEY_LIVE', ''),
    'fundit_save_beneficiary_account_live' => env('GTB_FUNDIT_SAVE_BENEFICIARY_ACCOUNT_LIVE', ''),

    'fundit_save_secret_key_test' => env('GTB_FUNDIT_SAVE_SECRET_KEY_TEST', ''),
    'fundit_save_public_key_test' => env('GTB_FUNDIT_SAVE_PUBLIC_KEY_TEST', ''),
    'fundit_save_beneficiary_account_test' => env('GTB_FUNDIT_SAVE_BENEFICIARY_ACCOUNT_TEST', ''),


    // =========================================================
    // TOMISIN
    // =========================================================

    'tomisin_secret_key_live' => env('GTB_TOMISIN_SECRET_KEY_LIVE', ''),
    'tomisin_public_key_live' => env('GTB_TOMISIN_PUBLIC_KEY_LIVE', ''),
    'tomisin_beneficiary_account_live' => env('GTB_TOMISIN_BENEFICIARY_ACCOUNT_LIVE', ''),

    'tomisin_secret_key_test' => env('GTB_TOMISIN_SECRET_KEY_TEST', ''),
    'tomisin_public_key_test' => env('GTB_TOMISIN_PUBLIC_KEY_TEST', ''),
    'tomisin_beneficiary_account_test' => env('GTB_TOMISIN_BENEFICIARY_ACCOUNT_TEST', ''),


    // =========================================================
    // CURTIS
    // =========================================================

    'curtis_secret_key_live' => env('GTB_CURTIS_SECRET_KEY_LIVE', ''),
    'curtis_public_key_live' => env('GTB_CURTIS_PUBLIC_KEY_LIVE', ''),
    'curtis_beneficiary_account_live' => env('GTB_CURTIS_BENEFICIARY_ACCOUNT_LIVE', ''),

    'curtis_secret_key_test' => env('GTB_CURTIS_SECRET_KEY_TEST', ''),
    'curtis_public_key_test' => env('GTB_CURTIS_PUBLIC_KEY_TEST', ''),
    'curtis_beneficiary_account_test' => env('GTB_CURTIS_BENEFICIARY_ACCOUNT_TEST', ''),


    // =========================================================
    // FORERUNNER
    // =========================================================

    'forerunner_secret_key_live' => env('GTB_FORERUNNER_SECRET_KEY_LIVE', ''),
    'forerunner_public_key_live' => env('GTB_FORERUNNER_PUBLIC_KEY_LIVE', ''),
    'forerunner_beneficiary_account_live' => env('GTB_FORERUNNER_BENEFICIARY_ACCOUNT_LIVE', ''),

    'forerunner_secret_key_test' => env('GTB_FORERUNNER_SECRET_KEY_TEST', ''),
    'forerunner_public_key_test' => env('GTB_FORERUNNER_PUBLIC_KEY_TEST', ''),
    'forerunner_beneficiary_account_test' => env('GTB_FORERUNNER_BENEFICIARY_ACCOUNT_TEST', ''),


    // =========================================================
    // SQUAD / GTB URLS
    // =========================================================

    'base_url_squado' => env(
        'GTB_BASE_URL_LIVE',
        'https://api-d.squadco.com'
    ),

    'base_url_squado_test' => env(
        'GTB_BASE_URL_TEST',
        'https://sandbox-api-d.squadco.com'
    ),
];