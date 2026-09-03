<?php

namespace App\Services;

class MonnifyService
{
    public function initCredentials($companyId)
    {
        $companyId = (int) $companyId;

        logInfo(
            json_encode($companyId),
            "MonnifyService company_id"
        );

        return match ($companyId) {

            // DFS company
            00 => $this->dfsCredentials(),

            // All other companies currently use Finova
            default => $this->finovaCredentials(),
        };
    }

    public function finovaCredentials()
    {
        return [
            'apiKey' => config('monnify.finova.api_key'),
            'secretKey' => config('monnify.finova.secret_key'),
            'walletAccount' => config('monnify.finova.wallet_account'),
            'contractCode' => config('monnify.finova.contract_code'),
        ];
    }

    public function dfsCredentials()
    {
        return [
            'apiKey' => config('monnify.dfs.api_key'),
            'secretKey' => config('monnify.dfs.secret_key'),
            'walletAccount' => config('monnify.dfs.wallet_account'),
            'contractCode' => config('monnify.dfs.contract_code'),
        ];
    }
}