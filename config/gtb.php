<?php

return [

    // =========================================================
    // FINOVA
    // =========================================================

    'secret_key_live' => env('GTB_SECRET_KEY_LIVE', ''),
    'public_key_live' => env('GTB_PUBLIC_KEY_LIVE', ''),
    'beneficiary_account_live' => env('GTB_BENEFICIARY_ACCOUNT_LIVE', ''),

    // =========================================================
    // ARIK
    // =========================================================

    'arik_secret_key_live' => env('GTB_ARIK_SECRET_KEY_LIVE', ''),
    'arik_public_key_live' => env('GTB_ARIK_PUBLIC_KEY_LIVE', ''),
    'arik_beneficiary_account_live' => env('GTB_ARIK_BENEFICIARY_ACCOUNT_LIVE', ''),

    // =========================================================
    // FUNDIT SAVE
    // =========================================================

    'fundit_save_secret_key_live' => env('GTB_FUNDIT_SAVE_SECRET_KEY_LIVE', ''),
    'fundit_save_public_key_live' => env('GTB_FUNDIT_SAVE_PUBLIC_KEY_LIVE', ''),
    'fundit_save_beneficiary_account_live' => env('GTB_FUNDIT_SAVE_BENEFICIARY_ACCOUNT_LIVE', ''),

    // =========================================================
    // TOMISIN
    // =========================================================

    'tomisin_secret_key_live' => env('GTB_TOMISIN_SECRET_KEY_LIVE', ''),
    'tomisin_public_key_live' => env('GTB_TOMISIN_PUBLIC_KEY_LIVE', ''),
    'tomisin_beneficiary_account_live' => env('GTB_TOMISIN_BENEFICIARY_ACCOUNT_LIVE', ''),

    // =========================================================
    // CURTIS
    // =========================================================

    'curtis_secret_key_live' => env('GTB_CURTIS_SECRET_KEY_LIVE', ''),
    'curtis_public_key_live' => env('GTB_CURTIS_PUBLIC_KEY_LIVE', ''),
    'curtis_beneficiary_account_live' => env('GTB_CURTIS_BENEFICIARY_ACCOUNT_LIVE', ''),

    // =========================================================
    // FORERUNNER
    // =========================================================

    'forerunner_secret_key_live' => env('GTB_FORERUNNER_SECRET_KEY_LIVE', ''),
    'forerunner_public_key_live' => env('GTB_FORERUNNER_PUBLIC_KEY_LIVE', ''),
    'forerunner_beneficiary_account_live' => env('GTB_FORERUNNER_BENEFICIARY_ACCOUNT_LIVE', ''),

    // =========================================================
    // COMMON
    // =========================================================

    'base_url_squado' => env(
        'GTB_BASE_URL_LIVE',
        'https://api-d.squadco.com'
    ),

    // Sandbox
    'secret_key_test' => env('GTB_SECRET_KEY_TEST', ''),
    'public_key_test' => env('GTB_PUBLIC_KEY_TEST', ''),
    'beneficiary_account_test' => env('GTB_BENEFICIARY_ACCOUNT_TEST', ''),
    'base_url_squado_test' => env(
        'GTB_BASE_URL_TEST',
        'https://sandbox-api-d.squadco.com'
    ),
];