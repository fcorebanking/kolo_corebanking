<?php

namespace App\Services;

use App\Http\Controllers\ThirdParty\AmuchaController;
use App\Http\Controllers\ThirdParty\AssetMatrixController;
use App\Http\Controllers\ThirdParty\BaniController;
use App\Http\Controllers\ThirdParty\FairMoneyController;
use App\Http\Controllers\ThirdParty\FincraController;
use App\Http\Controllers\ThirdParty\KudaBusinessController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\NinePSBController;
use App\Http\Controllers\ThirdParty\ParkwayController;
use App\Http\Controllers\ThirdParty\ProvidusController;
use App\Http\Controllers\ThirdParty\SafehavenController;
use App\Http\Controllers\ThirdParty\StroWalletController;
use App\Http\Controllers\ThirdParty\VPayController;
use App\Http\Controllers\ThirdParty\WovenController;
use App\Models\User;

class AccountCreationService
{
    public function createVirtualAccount(VirtualAccountType $virtualAccount)
    {
        $virtualAccountNumber  = "";
        $accountReference      = "";
        $createAccountResponse = "";

        $allBanks = [
            "000023",
            "090267",
            "090287",
            "060001",
            "090286",
            "000017",
            "100003",
            "120001",
            "090325",
            "000013",
            "090110",
            "090551",
            "000003",
            "000001",
            "000012",
            "090645",
            "000007"
        ];

        logInfo(
            $virtualAccount->country,
            "Virtual Code for country"
        );

        if (! in_array($virtualAccount->bankCode, $allBanks)) {
            return [
                'status'  => false,
                'message' => 'Selected Bank is not supported. Kindly select a supported Bank',
            ];
        }

        if ($virtualAccount->companyAccountCreationStatus != 1) {
            return [
                'status'  => false,
                'message' =>
                    'You are currently not allowed to create virtual accounts, you might have created several virtual accounts without usage or not in line with Policy.

                    Kindly click the previous, button below to change the account type to Internal account',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Providus
        |--------------------------------------------------------------------------
        */

        if ($virtualAccount->bankCode == '000023') {

            $providusController =
                new ProvidusController();

            $accountName =
                $virtualAccount->accountType == 'coperate'
                    ? $virtualAccount->companyName
                    : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $createAccountResponse =
                $providusController->createVirtualAccount(
                    $virtualAccount
                );

            if ($createAccountResponse['status']) {

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['account_number']
                    );

                $accountReference =
                    $virtualAccountNumber;

                $checkVirtualAccount =
                    User::where(
                        'virtual_account',
                        $virtualAccountNumber
                    )->first();

                if ($checkVirtualAccount) {
                    return [
                        'status'  => false,
                        'message' => "Account number already exists"
                    ];
                }
            }

        /*
        |--------------------------------------------------------------------------
        | Asset Matrix
        |--------------------------------------------------------------------------
        */

        } elseif ($virtualAccount->bankCode == '090287') {

            $assetMatrixController =
                new AssetMatrixController();

            $requestReference =
                generateTransactionReference();

            $accountName =
                $virtualAccount->accountType == 'coperate'
                    ? $virtualAccount->companyName
                    : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $createAccountResponse =
                $assetMatrixController->createAccount(
                    $virtualAccount->firstName,
                    $virtualAccount->lastName
                );

            if ($createAccountResponse['status']) {

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['account_number']
                    );

                $accountReference =
                    $virtualAccountNumber;

                $checkVirtualAccount =
                    User::where(
                        'virtual_account',
                        $virtualAccountNumber
                    )->first();

                if ($checkVirtualAccount) {
                    return [
                        'status'  => false,
                        'message' => "Account number already exists"
                    ];
                }
            }

        /*
        |--------------------------------------------------------------------------
        | Parkway
        |--------------------------------------------------------------------------
        */

        } elseif ($virtualAccount->bankCode == '100003') {

            $parkwayController =
                new ParkwayController();

            $requestReference =
                generateTransactionReference();

            $accountName =
                $virtualAccount->accountType == 'coperate'
                    ? $virtualAccount->companyName
                    : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $createAccountResponse =
                $parkwayController->createVirtualAccount(
                    $virtualAccount->firstName,
                    $virtualAccount->lastName,
                    $virtualAccount->email,
                    $virtualAccount->phone,
                    $virtualAccount->bvn,
                    $virtualAccount->address ?? ""
                );

            if ($createAccountResponse['status']) {

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['account_number']
                    );

                $accountReference =
                    $virtualAccountNumber;

                $checkVirtualAccount =
                    User::where(
                        'virtual_account',
                        $virtualAccountNumber
                    )->first();

                if ($checkVirtualAccount) {
                    return [
                        'status'  => false,
                        'message' => "Account number already exists"
                    ];
                }
            }

        /*
        |--------------------------------------------------------------------------
        | SafeHaven
        |--------------------------------------------------------------------------
        */

        } elseif ($virtualAccount->bankCode == '090286') {

            if (empty($virtualAccount->bvnId)) {
                return [
                    'status'  => false,
                    'message' => 'Kindly enter your BVN Id to proceed',
                ];
            }

            if (
                (int) $virtualAccount->companyID !== 92 &&
                empty($virtualAccount->otp)
            ) {
                return [
                    'status'  => false,
                    'message' => 'Kindly enter your OTP to proceed',
                ];
            }

            $safehaveController =
                new SafehavenController();

            $requestReference =
                generateTransactionReference();

            $createAccountResponse =
                $safehaveController->createVirtualAccount(
                    $virtualAccount->firstName,
                    $virtualAccount->lastName,
                    $virtualAccount->email,
                    $virtualAccount->phone,
                    $requestReference,
                    $virtualAccount->bvn,
                    $virtualAccount->nin,
                    $virtualAccount->otp,
                    $virtualAccount->bvnId,
                    $virtualAccount->companyID,
                    $virtualAccount->dob,
                    $virtualAccount->type
                );

            if ($createAccountResponse['status']) {

                $accountReference =
                    $requestReference;

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['accountNumber']
                    );
            }

        /*
        |--------------------------------------------------------------------------
        | Kuda
        |--------------------------------------------------------------------------
        */

        } elseif ($virtualAccount->bankCode == "090267") {

            logInfo(
                $virtualAccount->bankCode,
                "Kuda Bank Code in AccountCreationService"
            );

            logInfo(
                $virtualAccount->bankID,
                "Kuda Bank ID in AccountCreationService"
            );

            if ($virtualAccount->bankID == 242) {
                $kudaController =
                    new KudaBusinessController();
            } else {
                $kudaController =
                    new KudaController();
            }

            $requestReference =
                generateTransactionReference();

            $trackingReference =
                generateTransactionReference();

            logInfo(
                $virtualAccount->accountType,
                "Virtual Account Type"
            );

            if ($virtualAccount->accountType == "coperate") {
                $virtualAccount->firstName = "";
                $virtualAccount->lastName =
                    $virtualAccount->companyName;
            }

            $createAccountResponse =
                $kudaController->createAccount(
                    $requestReference,
                    $virtualAccount->email,
                    $virtualAccount->phone,
                    $virtualAccount->firstName,
                    $virtualAccount->lastName,
                    $trackingReference
                );

            $accountReference =
                $trackingReference;

            if ($createAccountResponse['status']) {

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['virtual_account_number']
                    );
            }

        /*
        |--------------------------------------------------------------------------
        | 9PSB
        |--------------------------------------------------------------------------
        |
        | 120001 = 9 Payment Service Bank
        |
        | This provider now goes directly to NinePSBController.
        | It no longer goes through BaniController.
        |
        */

        } elseif ($virtualAccount->bankCode == "120001") {

            $ninePSBController =
                new NinePSBController();

            $requestReference =
                generateTransactionReference();

            $trackingReference =
                generateTransactionReference();

            /*
            |--------------------------------------------------------------------------
            | 9PSB expects the actual customer's first and last names.
            |--------------------------------------------------------------------------
            */

            $firstName =
                $virtualAccount->firstName;

            $lastName =
                $virtualAccount->lastName;

            if (
                $virtualAccount->accountType == "coperate" &&
                ! empty($virtualAccount->companyName)
            ) {
                $firstName = "";
                $lastName =
                    $virtualAccount->companyName;
            }

            logInfo([
                'bank_code'          => $virtualAccount->bankCode,
                'first_name'         => $firstName,
                'last_name'          => $lastName,
                'email'              => $virtualAccount->email,
                'phone'              => $virtualAccount->phone,
                'bvn'                => $virtualAccount->bvn,
                'request_reference'  => $requestReference,
                'tracking_reference' => $trackingReference,
            ], "9PSB Account Creation Request");

            $createAccountResponse =
                $ninePSBController->createAccount(
                    $requestReference,
                    $virtualAccount->email,
                    $virtualAccount->phone,
                    $firstName,
                    $lastName,
                    $trackingReference
                );

            logInfo(
                $createAccountResponse,
                "9PSB Account Creation Service Response"
            );

            if ($createAccountResponse['status']) {

                /*
                |--------------------------------------------------------------------------
                | 9PSB returns the virtual account number here.
                |--------------------------------------------------------------------------
                */

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['virtual_account_number']
                    );

                /*
                |--------------------------------------------------------------------------
                | Use the request reference as our account reference.
                |--------------------------------------------------------------------------
                */

                $accountReference =
                    $requestReference;
            }

        /*
        |--------------------------------------------------------------------------
        | Bani / Other Banks
        |--------------------------------------------------------------------------
        |
        | 9PSB has intentionally been removed from this branch.
        |
        */

        } elseif (
            $virtualAccount->bankCode == "000013" ||
            $virtualAccount->bankCode == "000003" ||
            $virtualAccount->bankCode == "000012" ||
            $virtualAccount->bankCode == "000017"
        ) {

            $baniController =
                new BaniController();

            $accountName =
                $virtualAccount->accountType == 'coperate'
                    ? $virtualAccount->companyName
                    : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $requestReference =
                generateTransactionReference();

            switch ($virtualAccount->bankCode) {

                case '000013':
                    $bankName =
                        "guaranty trust bank";
                    break;

                case '000003':
                    $bankName =
                        "first city monument bank";
                    break;

                case '000017':
                    $bankName =
                        "wema bank";
                    break;

                default:
                    $bankName =
                        "stanbic ibtc bank";
                    break;
            }

            $createAccountResponse =
                $baniController->createAccount(
                    $virtualAccount->firstName,
                    $virtualAccount->lastName,
                    $virtualAccount->phone,
                    $virtualAccount->address,
                    $virtualAccount->email,
                    $bankName,
                    $virtualAccount->bvn
                );

            if ($createAccountResponse['status']) {

                $accountReference =
                    $requestReference;

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['virtual_account_number']
                    );
            }

        /*
        |--------------------------------------------------------------------------
        | VPay
        |--------------------------------------------------------------------------
        */

        } elseif ($virtualAccount->bankCode == "090110") {

            $vpayController =
                new VPayController();

            $requestReference =
                generateAccountReference();

            $createAccountResponse =
                $vpayController->createAccount(
                    $virtualAccount->companyShortName,
                    $virtualAccount->email,
                    $virtualAccount->phone,
                    $virtualAccount->firstName,
                    $virtualAccount->lastName,
                    $virtualAccount->bvn
                );

            if ($createAccountResponse['status']) {

                $accountReference =
                    $createAccountResponse['data']['cluster_id'];

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['virtual_account_number']
                    );
            }

        /*
        |--------------------------------------------------------------------------
        | Amucha
        |--------------------------------------------------------------------------
        */

        } elseif ($virtualAccount->bankCode == "090645") {

            $amuchaController =
                new AmuchaController();

            $requestReference =
                generateAccountReference();

            $accountName =
                $virtualAccount->accountType == 'coperate'
                    ? $virtualAccount->companyName
                    : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $firstName =
                $virtualAccount->accountType == 'coperate'
                    ? $virtualAccount->companyName
                    : $virtualAccount->firstName;

            $createAccountResponse =
                $amuchaController->createAccount(
                    $accountName,
                    $requestReference
                );

            if ($createAccountResponse['status']) {

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['account_number']
                    );

                $accountReference =
                    $virtualAccountNumber;

                $checkVirtualAccount =
                    User::where(
                        'virtual_account',
                        $virtualAccountNumber
                    )->first();

                if ($checkVirtualAccount) {
                    return [
                        'status'  => false,
                        'message' => "Account number already exists"
                    ];
                }
            }

        /*
        |--------------------------------------------------------------------------
        | FairMoney
        |--------------------------------------------------------------------------
        */

        } elseif ($virtualAccount->bankCode == '090551') {

            $fairmoneyController =
                new FairMoneyController();

            $accountReference =
                rand(1000000000, 9999999999) . "";

            $accountName =
                $virtualAccount->accountType == 'coperate'
                    ? $virtualAccount->companyName
                    : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $firstName =
                $virtualAccount->accountType == 'coperate'
                    ? $virtualAccount->companyName
                    : $virtualAccount->firstName;

            $createAccountResponse =
                $fairmoneyController->createAccount(
                    $firstName,
                    $virtualAccount->lastName,
                    $virtualAccount->phone,
                    $virtualAccount->address,
                    $virtualAccount->bvn,
                    $accountReference,
                    $virtualAccount->accountType,
                    $virtualAccount->companyShortName
                );

            if ($createAccountResponse['status']) {

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['virtual_account_number']
                    );
            }

        /*
        |--------------------------------------------------------------------------
        | StroWallet
        |--------------------------------------------------------------------------
        */

        } elseif ($virtualAccount->bankCode == "000007") {

            logInfo(
                $virtualAccount->bankCode,
                "StroWallet Fidelity Bank Code in AccountCreationService"
            );

            $stroWalletController =
                new StroWalletController();

            $requestReference =
                generateTransactionReference();

            $trackingReference =
                generateTransactionReference();

            logInfo(
                $virtualAccount->accountType,
                "Virtual Account Type"
            );

            if ($virtualAccount->accountType == "coperate") {
                $virtualAccount->firstName = "";
                $virtualAccount->lastName =
                    $virtualAccount->companyName;
            }

            $createAccountResponse =
                $stroWalletController->createVirtualAccount(
                    $virtualAccount->firstName,
                    $virtualAccount->lastName,
                    $virtualAccount->email,
                    $virtualAccount->phone,
                    $requestReference,
                    $virtualAccount->companyID,
                    $virtualAccount->type
                );

            if ($createAccountResponse['status']) {

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']
                    );
            }

        /*
        |--------------------------------------------------------------------------
        | Woven
        |--------------------------------------------------------------------------
        */

        } else {

            $wovenController =
                new WovenController();

            $requestReference =
                generateAccountReference();

            $accountReference =
                $requestReference;

            $accountName =
                $virtualAccount->accountType == 'coperate'
                    ? $virtualAccount->companyName
                    : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $createAccountResponse =
                $wovenController->createCustomerAccount(
                    $accountName,
                    $virtualAccount->email,
                    $virtualAccount->phone,
                    $virtualAccount->bankCode,
                    $accountReference
                );

            if ($createAccountResponse['status']) {

                $virtualAccountNumber =
                    $this->sanitizeAccountNumber(
                        $virtualAccount->currencyCode,
                        $createAccountResponse['data']['vnuban']
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Provider Failure
        |--------------------------------------------------------------------------
        */

        if (
            ! is_array($createAccountResponse) ||
            ! isset($createAccountResponse['status'])
        ) {
            return [
                'status'  => false,
                'message' => 'Invalid account creation response from selected bank provider.'
            ];
        }

        if (! $createAccountResponse['status']) {
            return $createAccountResponse;
        }

        if (empty($virtualAccountNumber)) {
            return [
                'status'  => false,
                'message' => 'Bank account was created but no virtual account number was returned.'
            ];
        }

        return [
            'status'  => true,
            'message' => 'Account Created Successfully',
            'data'    => [
                'virtual_account'   => $virtualAccountNumber,
                'account_reference' => $accountReference,
            ],
        ];
    }

    public function sanitizeAccountNumber(
        $countryCode,
        $accountNumber
    ) {
        return $countryCode == "NGN"
            ? $accountNumber
            : $accountNumber . "_";
    }

    public function createCompanyVirtualAccount()
    {
    }

    public function createForeignAccount($virtualAccount)
    {
        $fincraController =
            new FincraController();

        $requestReference =
            generateTransactionReference();

        $trackingReference =
            generateTransactionReference();

        logInfo(
            $virtualAccount->accountType,
            "Virtual Account Type"
        );

        $createAccountResponse =
            $fincraController->createAccount(
                $requestReference,
                $virtualAccount
            );

        $accountReference =
            $trackingReference;

        if ($createAccountResponse['status']) {

            $virtualAccountNumber =
                $this->sanitizeAccountNumber(
                    $virtualAccount->currencyCode,
                    $createAccountResponse['data']['virtual_account_number']
                );
        }
    }
}