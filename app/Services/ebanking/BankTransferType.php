<?php

namespace App\Services\ebanking;

class BankTransferType
{
    public $bankCode;
    public $shortBankCode;
    public $accountNumber;
    public $sessionId;
    public $firstName;
    public $description;
    public $amount; // personal or coperate
    public $transactionReference;
}
