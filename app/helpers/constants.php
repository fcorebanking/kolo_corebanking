<?php



$appMode = env('APP_MODE', 'test');

$flutterwaveSecretKey = $appMode == "test" ? env('FLUTTERWAVE_SECRET_KEY_TEST') : env('FLUTTERWAVE_SECRET_KEY_LIVE');
$flutterwavePublicKey = $appMode == "test" ? env('FLUTTERWAVE_PUBLIC_KEY_TEST') : env('FLUTTERWAVE_PUBLIC_KEY_LIVE');
$flutterwaveEncryptionKey = $appMode == "test" ? env('FLUTTERWAVE_ENCRYPTION_KEY_TEST') : env('FLUTTERWAVE_ENCRYPTION_KEY_LIVE');


function getFlutterwaveSecretKey()
{
    global $flutterwaveSecretKey;
    return $flutterwaveSecretKey;
}

