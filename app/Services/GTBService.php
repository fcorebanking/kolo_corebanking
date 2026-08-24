<?php
namespace App\Services;

class GTBService
{
    public function initCredentials($company_id)
    {
        $company_id = (int) $company_id;

        logInfo(json_encode($company_id), "GTBService company_id");

        return match ($company_id) {

            46  => $this->ariksCredentials(),
            79  => $this->funditSaveCredentials(),
            80  => $this->tomisinCredentials(),
            101 => $this->curtisCredentials(), //7000219629
            105  => $this->foreRunnerCredentials(),

            default
                => $this->finovaCredentials(), //
        };
    }

    //expires 2027-03-11T16:45:54Z - in 9 months

    public function finovaCredentials()
    {
        return [
            'secretKeySquado'     => "sk_878b9cf9f3a5cb8b84d5e5332348f02a9bdbf8d2",
            'secretKeySquadoTest' => "sk_878b9cf9f3a5cb8b84d5e5332348f02a9bdbf8d2",
            'beneficiaryAccount'  => config('gtb.beneficiary_account_live'),
            'baseUrlSquado'       => config('gtb.base_url_squado'),
            'baseUrlSquadoTest'   => config('gtb.base_url_squado_test'),
        ];

    }

    //expires 2027-03-11T16:45:54Z - in 9 months

    public function ariksCredentials()
    {
        return [
            'secretKeySquado'     => "sk_878b9cf9f3a5cb8b84d5e5332348f02a9bdbf8d2",
            'secretKeySquadoTest' => "sk_878b9cf9f3a5cb8b84d5e5332348f02a9bdbf8d2",
            'beneficiaryAccount'  => config('gtb.ariks_beneficiary_account_live'),
            'baseUrlSquado'       => config('gtb.base_url_squado'),
            'baseUrlSquadoTest'   => config('gtb.base_url_squado_test'),

        ];

    }

    public function tomisinCredentials()
    {
        return [
            'secretKeySquado'     => "sk_13a440c6597b8c1798819c5eb8158af6790b673b",
            'secretKeySquadoTest' => "sk_13a440c6597b8c1798819c5eb8158af6790b673b",
            'beneficiaryAccount'  => config('gtb.tomisin_beneficiary_account_live'),
            'baseUrlSquado'       => config('gtb.base_url_squado'),
            'baseUrlSquadoTest'   => config('gtb.base_url_squado_test'),
        ];

    }

    public function curtisCredentials()
    {
        return [
            'secretKeySquado'     => "sk_878b9cf9f3a5cb8b84d5e5332348f02a9bdbf8d2",
            'secretKeySquadoTest' => "sk_878b9cf9f3a5cb8b84d5e5332348f02a9bdbf8d2",
            'beneficiaryAccount'  => config('gtb.curtis_beneficiary_account_live'),
            'baseUrlSquado'       => config('gtb.base_url_squado'),
            'baseUrlSquadoTest'   => config('gtb.base_url_squado_test'),
        ];

    }

    public function funditSaveCredentials()
    {
        return [
            'secretKeySquado'     => "sk_68c7ad2b9c0c00a673cd8087438b0011d887d9d5",
            'secretKeySquadoTest' => "sk_68c7ad2b9c0c00a673cd8087438b0011d887d9d5",
            'beneficiaryAccount'  => config('gtb.fundit_save_beneficiary_account_live'),
            'baseUrlSquado'       => config('gtb.base_url_squado'),
            'baseUrlSquadoTest'   => config('gtb.base_url_squado_test'),
        ];

    }

        public function foreRunnerCredentials()
    {
        return [
            'secretKeySquado'     => "sk_4a8b19c8c2d71135237951424a6dc4c944b1391f",
            'secretKeySquadoTest' => "sk_4a8b19c8c2d71135237951424a6dc4c944b1391f",
            'beneficiaryAccount'  => config('gtb.forerunner_beneficiary_account_live'),
            'baseUrlSquado'       => config('gtb.base_url_squado'),
            'baseUrlSquadoTest'   => config('gtb.base_url_squado_test'),
        ];

    }
}
