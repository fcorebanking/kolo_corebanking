<?php
namespace App\Services;

use App\Http\Controllers\ThirdParty\AmuchaController;
use App\Http\Controllers\ThirdParty\AssetMatrixController;
use App\Http\Controllers\ThirdParty\BaniController;
use App\Http\Controllers\ThirdParty\FairMoneyController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\ParkwayController;
use App\Http\Controllers\ThirdParty\ProvidusController;
use App\Http\Controllers\ThirdParty\SafehavenController;
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

        $allBanks = ["000023", "090267", "090287", "060001", "090286", "000017", "100003", "120001", "090325", "000013", "090110", '090551', '000003', '000001', '000012', '090645', "000003"];

        if (! in_array($virtualAccount->bankCode, $allBanks)) {
            return [
                'status'  => false,
                'message' => 'Selected Bank is not supported. Kindly select a supported Bank',
            ];
        }

        if ($virtualAccount->companyAccountCreationStatus != 1) {
            return [
                'status'  => false,
                'message' => 'You are currently not allowed to create virtual accounts, you might have created several virtual accounts without usage or not in line with Policy.

                Kindly click the previous, button below to change the account type to Internal account',
            ];
        }

        if ($virtualAccount->bankCode == '000023') {

            $providusController = new ProvidusController();

            $accountName           = $virtualAccount->accountType == 'coperate' ? $virtualAccount->companyName : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;
            $createAccountResponse = $providusController->createVirtualAccount($virtualAccount);

            if ($createAccountResponse['status']) {

                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['account_number']);
                $accountReference     = $virtualAccountNumber;

                $checkVirtualAccount = User::where('virtual_account', $virtualAccountNumber)->first();

                if ($checkVirtualAccount) {
                    return $createAccountResponse = ['status' => false, 'message' => "Account number already exists"];
                }
            }
            //Providus Account creation
        } else if ($virtualAccount->bankCode == '090287') {

            $assetMatrixController = new AssetMatrixController();

            $requestReference = generateTransactionReference();

            $accountName           = $virtualAccount->accountType == 'coperate' ? $virtualAccount->companyName : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;
            $createAccountResponse = $assetMatrixController->createAccountInternal();

            if ($createAccountResponse['status']) {

                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['account_number']);
                $accountReference     = $virtualAccountNumber;

                $checkVirtualAccount = User::where('virtual_account', $virtualAccountNumber)->first();

                if ($checkVirtualAccount) {
                    return $createAccountResponse = ['status' => false, 'message' => "Account number already exists"];
                }
            }
            //Providus Account creation
        } else if ($virtualAccount->bankCode == '100003') {

            $parkwayController = new ParkwayController();

            $requestReference = generateTransactionReference();

            $accountName = $virtualAccount->accountType == 'coperate' ? $virtualAccount->companyName : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $createAccountResponse = $parkwayController->createVirtualAccount($virtualAccount->firstName, $virtualAccount->lastName, $virtualAccount->email, $virtualAccount->phone, $virtualAccount->bvn, $virtualAccount->address ?? "");

            if ($createAccountResponse['status']) {

                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['account_number']);
                $accountReference     = $virtualAccountNumber;

                $checkVirtualAccount = User::where('virtual_account', $virtualAccountNumber)->first();

                if ($checkVirtualAccount) {
                    return $createAccountResponse = ['status' => false, 'message' => "Account number already exists"];
                }
            }
            //Providus Account creation
        } else if ($virtualAccount->bankCode == '090286') {

            if (! isset($virtualAccount->otp)) {
                return $createAccountResponse = ['status' => false, 'message' => "Kindly enter your OTP to proceed"];
            }

            if (! isset($virtualAccount->bvnId)) {
                return $createAccountResponse = ['status' => false, 'message' => "Kindly enter your BVN Id to proceed"];
            }

            $safehaveController = new SafehavenController();

            $requestReference = generateTransactionReference();

            $createAccountResponse = $safehaveController->createVirtualAccount(
                $virtualAccount->firstName,
                $virtualAccount->lastName,
                $virtualAccount->email,
                $virtualAccount->phone,
                $requestReference,
                $virtualAccount->bvn,
                $virtualAccount->otp,
                $virtualAccount->bvnId
            );

            if ($createAccountResponse['status']) {

                $accountReference     = $requestReference;
                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['accountNumber']);
            }
            //Providus Account creation
        } else if ($virtualAccount->bankCode == "090267") {

            logInfo($virtualAccount->bankCode, "Kuda Bank Code in accountcreationservicer");

            $kudaController = new KudaController();

            $requestReference  = generateTransactionReference();
            $trackingReference = generateTransactionReference();

            if ($virtualAccount->accountType == 'coperate') {
                $virtualAccount->firstName = "";
                $virtualAccount->lastName  = $virtualAccount->companyName;
            }

            //     logInfo($requestReference, "Kuda Bank Code in requestReference");
            //      logInfo($trackingReference, "Kuda Bank Code in trackingReference");
            //     logInfo(json_encode($virtualAccount), "Kuda Bank Virtual account");

            $createAccountResponse = $kudaController->createAccount($requestReference, $virtualAccount->email, $virtualAccount->phone, $virtualAccount->firstName, $virtualAccount->lastName, $trackingReference);

            $accountReference = $trackingReference;

            if ($createAccountResponse['status']) {
                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['virtual_account_number']);
            }
        } else if ($virtualAccount->bankCode == "000013" || $virtualAccount->bankCode == "000003" || $virtualAccount->bankCode == "120001" || $virtualAccount->bankCode == "000012" || $virtualAccount->bankCode == "000003" || $virtualAccount->bankCode == "000017") {

            // $gtbController = new GTBController();

            // $requestReference = generateAccountReference();

            // $createAccountResponse = $virtualAccount->accountType == 'coperate' ?
            //     $gtbController->createCompanyVirtualAccount($virtualAccount->companyName, $virtualAccount->bvn, $virtualAccount->phone, $virtualAccount->firstName) :
            //     $gtbController->createVirtualAccount($virtualAccount->firstName, $virtualAccount->lastName, $virtualAccount->dob, $virtualAccount->gender, $virtualAccount->bvn, $virtualAccount->email, $virtualAccount->phone, $virtualAccount->address, $requestReference);

            // $accountReference = $requestReference;
            // if ($createAccountResponse['status']) {
            //     $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['virtual_account_number']);
            // }

            $baniController = new BaniController();

            $accountName = $virtualAccount->accountType == 'coperate' ? $virtualAccount->companyName : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;
            // $createAccountResponse = $providusController->createAccount($accountName);

            $requestReference = generateTransactionReference();

            switch ($virtualAccount->bankCode) {
                case '000013':
                    $bankName = "guaranty trust bank";
                    break;
                case '120001':
                    $bankName = "9 payment service bank";
                    break;
                case '100003':
                    $bankName = "parkway-readycash bank";
                    break;
                case '000003':
                    $bankName = "first city monument bank";
                    break;
                case '000017':
                    $bankName = "wema bank";
                    break;
                default:
                    $bankName = "stanbic ibtc bank";
                    break;
            }

            $createAccountResponse = $baniController->createAccount($virtualAccount->firstName, $virtualAccount->lastName, $virtualAccount->phone, $virtualAccount->address, $virtualAccount->email, $bankName, $virtualAccount->bvn);

            if ($createAccountResponse['status']) {
                $accountReference     = $requestReference;
                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['virtual_account_number']);
            }
        } else if ($virtualAccount->bankCode == "090110") {

            $vpayController = new VPayController();

            $requestReference = generateAccountReference();

            $createAccountResponse = $vpayController->createAccount($virtualAccount->companyShortName, $virtualAccount->email, $virtualAccount->phone, $virtualAccount->firstName, $virtualAccount->lastName, $virtualAccount->bvn);

            if ($createAccountResponse['status']) {
                $accountReference = $createAccountResponse['data']['cluster_id'];

                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['virtual_account_number']);
            }
        } else if ($virtualAccount->bankCode == "090645") {

            $amuchaController = new AmuchaController();
            $requestReference = generateAccountReference();

            $accountName = $virtualAccount->accountType == 'coperate' ? $virtualAccount->companyName : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $firstName = $virtualAccount->accountType == 'coperate' ? $virtualAccount->companyName : $virtualAccount->firstName;

            $createAccountResponse = $amuchaController->createAccount($accountName, $requestReference);

            if ($createAccountResponse['status']) {

                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['account_number']);
                $accountReference     = $virtualAccountNumber;

                $checkVirtualAccount = User::where('virtual_account', $virtualAccountNumber)->first();

                if ($checkVirtualAccount) {
                    return $createAccountResponse = ['status' => false, 'message' => "Account number already exists"];
                }
            }
        } else if ($virtualAccount->bankCode == '090551') {

            $fairmoneyController = new FairMoneyController();
            $accountReference    = rand(1000000000, 9999999999) . "";

            $accountName = $virtualAccount->accountType == 'coperate' ? $virtualAccount->companyName : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $firstName = $virtualAccount->accountType == 'coperate' ? $virtualAccount->companyName : $virtualAccount->firstName;

            $createAccountResponse = $fairmoneyController->createAccount($firstName, $virtualAccount->lastName, $virtualAccount->phone, $virtualAccount->address, $virtualAccount->bvn, $accountReference, $virtualAccount->accountType, $virtualAccount->companyShortName);

            if ($createAccountResponse['status']) {
                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['virtual_account_number']);
            }
        } else {

            $wovenController  = new WovenController();
            $requestReference = generateAccountReference();
            $accountReference = $requestReference;

            $accountName = $virtualAccount->accountType == 'coperate' ? $virtualAccount->companyName : $virtualAccount->firstName . ' ' . $virtualAccount->lastName;

            $createAccountResponse = $wovenController->createCustomerAccount($accountName, $virtualAccount->email, $virtualAccount->phone, $virtualAccount->bankCode, $accountReference);

            if ($createAccountResponse['status']) {
                $virtualAccountNumber = $this->sanitizeAccountNumber($virtualAccount->currencyCode, $createAccountResponse['data']['vnuban']);
            }
        }

        if (! $createAccountResponse['status']) {
            return $createAccountResponse;
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

    public function sanitizeAccountNumber($countryCode, $accountNumber)
    {
        return $countryCode == "NGN" ? $accountNumber : $accountNumber . "_";
    }

    public function createCompanyVirtualAccount()
    {
    }
}
