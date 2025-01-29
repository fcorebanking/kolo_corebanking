<?php

namespace App\Http\Controllers\ThirdParty\Trylux;

class TryluxPayload
{
    public $bankCode;

    public $pan;
    public $stan;
    public $amount;
    public $rrn;
    public $iccData;
    public $trackData;
    public $postDataCode;
    public $cardExpDate;
    public $sequenceNumber;
    public $pin;
    public $acquiringCode;
    public $accountType;
    public $terminalId;
    public $serialNumber;
    public $onlyAccountInfo;
    public $transactionCurrency;
}
