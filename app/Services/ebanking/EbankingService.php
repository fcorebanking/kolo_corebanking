<?php
namespace App\Services\ebanking;

use App\Http\Controllers\ThirdParty\FairMoneyController;
use App\Http\Controllers\ThirdParty\KudaBusinessController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\MonnifyController;
use App\Http\Controllers\ThirdParty\NinePSBController;
use App\Http\Controllers\ThirdParty\SafehavenController;
use App\Http\Controllers\ThirdParty\WovenController;
use App\Services\ebanking\BankTransferType;

class EbankingService
{
    public $ebankingPartner;
    public $bankTransferPartner;
    public $accountVerificationPartner;

    public function __construct($ebankingPartner)
    {
        $this->ebankingPartner            = $ebankingPartner;
        $this->bankTransferPartner        = $ebankingPartner[1]->ebanking_partners_id;
        $this->accountVerificationPartner = $ebankingPartner[0]->ebanking_partners_id;
    }

    public function transferToBankAccount(BankTransferType $bankTransferType)
    {
         loginfo('BANK TRANSFER STARTED', [
        'bank_code' => $bankTransferType->bankCode,
        'account_number' => $bankTransferType->accountNumber,
        'amount' => $bankTransferType->amount,
        'account_verification_partner' => $this->accountVerificationPartner,
        'bank_transfer_partner' => $this->bankTransferPartner,
     ]);

        $bankAccount = $this->verifyAccountNumberFull($bankTransferType->bankCode, $bankTransferType->accountNumber);


        if (! $bankAccount['status']) {
            $bankAccount['status']  = "01";
            $bankAccount['message'] = "Failed to Complete Bank transfer. If you have been debited kindly contact support for reversal";

            return $bankAccount;
        }

        if ($this->bankTransferPartner == 1 || $this->bankTransferPartner == 15) {

            $kudaController = new KudaController();

            $bankTransfer = $kudaController->bankTransfer($bankTransferType->transactionReference, $bankTransferType->accountNumber, $bankTransferType->bankCode, $bankTransferType->sessionId, $bankTransferType->amount, $bankAccount['data']['account_name'], $bankTransferType->description, $this->bankTransferPartner);
        } else if ($this->bankTransferPartner == 17) {

            $kudaController = new KudaBusinessController();

            $bankTransfer = $kudaController->bankTransfer($bankTransferType->transactionReference, $bankTransferType->accountNumber, $bankTransferType->bankCode, $bankTransferType->sessionId, $bankTransferType->amount, $bankAccount['data']['account_name'], $bankTransferType->description, $this->bankTransferPartner);
        } else if ($this->bankTransferPartner == 2) {

            $accountName = $bankAccount["data"]["account_name"];

            $myAccountName = [];

            $myAccountName = explode(" ", $accountName);

            $firstName = strlen($myAccountName[0]) < 3 ? $myAccountName[1] : $myAccountName[0];

            $wovenController = new WovenController();

            $bankTransfer = $wovenController->transferToBank($bankTransferType->accountNumber, $firstName, $bankTransferType->bankCode, $bankTransferType->description, $bankTransferType->amount, $bankTransferType->transactionReference);
        } else if ($this->bankTransferPartner == 11) {

            $monnifyController = new MonnifyController();

            $bankTransfer = $monnifyController->bankTransfer($bankTransferType->amount, $bankTransferType->transactionReference, $bankTransferType->description, $bankTransferType->bankCode, $bankTransferType->accountNumber);
        } else if ($this->bankTransferPartner == 13) {

            $safeHavenController = new SafehavenController();

            $bankTransfer = $safeHavenController->bankTransfer($bankTransferType->amount, $bankTransferType->transactionReference, $bankTransferType->description, $bankTransferType->bankCode, $bankTransferType->accountNumber, $bankAccount['data']['session_id']);
        } else if ($this->bankTransferPartner == 14) {

            $accountName = $bankAccount["data"]["account_name"];

            $myAccountName = [];

            $myAccountName = explode(" ", $accountName);

            $firstName = strlen($myAccountName[0]) < 3 ? $myAccountName[1] : $myAccountName[0];

            $safeHavenController = new FairMoneyController();

            $bankTransfer = $safeHavenController->bankTransfer($bankTransferType->accountNumber, $bankTransferType->shortBankCode, $accountName, $bankTransferType->amount, $bankTransferType->description, $bankTransferType->transactionReference, );
        } else if ($this->bankTransferPartner == 19) {

            $ninePSBController = new NinePSBController();

            $bankTransfer = $ninePSBController->bankTransfer($bankTransferType->transactionReference, $bankTransferType->accountNumber, $bankTransferType->bankCode, $bankTransferType->sessionId, $bankTransferType->amount, $bankAccount['data']['account_name'], $bankTransferType->description, $this->bankTransferPartner);
        }

        loginfo([
     'bank_transfer_partner' => $this->bankTransferPartner,
     'status' => $bankTransfer['status'] ?? null,
     'message' => $bankTransfer['message'] ?? null,
     'response' => $bankTransfer,
     ], 'BANK TRANSFER PROVIDER RESPONSE');

        if (! $bankTransfer['status']) {
            $bankTransfer['status'] = "02";
            return $bankTransfer;
        }


        $bankTransfer["status"] = "00";
        return $bankTransfer;
    }

    public function verifyAccountNumberFull($bankCode, $accountNumber)
    {
        $bankAccount = [
            'status'  => false,
            'message' => "Service unavailable. Please try again later",
        ];

        if ($this->accountVerificationPartner == 1 || $this->accountVerificationPartner == 15) {
            $kudaController = new KudaController();
            $bankAccount    = $kudaController->verifyBankAccount($bankCode, $accountNumber);
        } else if ($this->accountVerificationPartner == 17) {
            $kudaController = new KudaBusinessController();
            $bankAccount    = $kudaController->verifyBankAccount($bankCode, $accountNumber);
        } else if ($this->accountVerificationPartner == 2) {
            $wovenController = new WovenController();
            $bankAccount     = $wovenController->verifyBankAccountNumberGuzzle($bankCode, $accountNumber);
        } else if ($this->accountVerificationPartner == 11) {
            $monnifyController = new MonnifyController();
            $bankAccount       = $monnifyController->verifyBankAccountNumberGuzzle($bankCode, $accountNumber);
        } else if ($this->accountVerificationPartner == 13) {
            $safeHavenController = new SafehavenController();
            $bankAccount         = $safeHavenController->verifyAccount($bankCode, $accountNumber);
        } else if ($this->accountVerificationPartner == 19) {

            $ninePSBController = new NinePSBController();

            $bankTransfer = $ninePSBController->verifyBankAccount($bankCode, $accountNumber);
        }
        return $bankAccount;
    }

    public function sanitizeAccountNumber($countryCode, $accountNumber)
    {
        return $countryCode == "NGN" ? $accountNumber : $accountNumber . "_";
    }

    public function createCompanyVirtualAccount()
    {
    }
}
