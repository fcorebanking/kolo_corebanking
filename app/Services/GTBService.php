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
        'secretKeySquado'        => config('gtb.finova_secret_key_live'),
        'secretKeySquadoTest'    => config('gtb.finova_secret_key_test'),

        'publicKeySquado'        => config('gtb.finova_public_key_live'),
        'publicKeySquadoTest'    => config('gtb.finova_public_key_test'),

        'beneficiaryAccount'     => config('gtb.finova_beneficiary_account_live'),
        'beneficiaryAccountTest' => config('gtb.finova_beneficiary_account_test'),

        'baseUrlSquado'          => config('gtb.base_url_squado'),
        'baseUrlSquadoTest'      => config('gtb.base_url_squado_test'),
    ];

    }

    //expires 2027-03-11T16:45:54Z - in 9 months

    public function ariksCredentials()
    {
        return [
        'secretKeySquado'        => config('gtb.arik_secret_key_live'),
        // 'secretKeySquadoTest'    => config('gtb.arik_secret_key_test'),

        'publicKeySquado'        => config('gtb.arik_public_key_live'),
        // 'publicKeySquadoTest'    => config('gtb.arik_public_key_test'),

        'beneficiaryAccount'     => config('gtb.arik_beneficiary_account_live'),
        // 'beneficiaryAccountTest' => config('gtb.arik_beneficiary_account_test'),

        'baseUrlSquado'          => config('gtb.base_url_squado'),
        'baseUrlSquadoTest'      => config('gtb.base_url_squado_test'),
    ];

    }

    public function tomisinCredentials()
    {
        return [
        'secretKeySquado'        => config('gtb.tomisin_secret_key_live'),
        // 'secretKeySquadoTest'    => config('gtb.tomisin_secret_key_test'),

        'publicKeySquado'        => config('gtb.tomisin_public_key_live'),
        // 'publicKeySquadoTest'    => config('gtb.tomisin_public_key_test'),

        'beneficiaryAccount'     => config('gtb.tomisin_beneficiary_account_live'),
        // 'beneficiaryAccountTest' => config('gtb.tomisin_beneficiary_account_test'),

        'baseUrlSquado'          => config('gtb.base_url_squado'),
        'baseUrlSquadoTest'      => config('gtb.base_url_squado_test'),
    ];

    }

    public function curtisCredentials()
    {
        return [
        'secretKeySquado'        => config('gtb.curtis_secret_key_live'),
        // 'secretKeySquadoTest'    => config('gtb.curtis_secret_key_test'),

        'publicKeySquado'        => config('gtb.curtis_public_key_live'),
        // 'publicKeySquadoTest'    => config('gtb.curtis_public_key_test'),

        'beneficiaryAccount'     => config('gtb.curtis_beneficiary_account_live'),
        // 'beneficiaryAccountTest' => config('gtb.curtis_beneficiary_account_test'),

        'baseUrlSquado'          => config('gtb.base_url_squado'),
        'baseUrlSquadoTest'      => config('gtb.base_url_squado_test'),
    ];

    }

    public function funditSaveCredentials()
    {
        return [
        'secretKeySquado'        => config('gtb.fundit_save_secret_key_live'),
        // 'secretKeySquadoTest'    => config('gtb.fundit_save_secret_key_test'),

        'publicKeySquado'        => config('gtb.fundit_save_public_key_live'),
        // 'publicKeySquadoTest'    => config('gtb.fundit_save_public_key_test'),

        'beneficiaryAccount'     => config('gtb.fundit_save_beneficiary_account_live'),
        // 'beneficiaryAccountTest' => config('gtb.fundit_save_beneficiary_account_test'),

        'baseUrlSquado'          => config('gtb.base_url_squado'),
        'baseUrlSquadoTest'      => config('gtb.base_url_squado_test'),
    ];

    }

        public function foreRunnerCredentials()
    {
        return [
        'secretKeySquado'        => config('gtb.fore_runner_secret_key_live'),
        // 'secretKeySquadoTest'    => config('gtb.fore_runner_secret_key_test'),

        'publicKeySquado'        => config('gtb.fore_runner_public_key_live'),
        // 'publicKeySquadoTest'    => config('gtb.fore_runner_public_key_test'),

        'beneficiaryAccount'     => config('gtb.fore_runner_beneficiary_account_live'),
        // 'beneficiaryAccountTest' => config('gtb.fore_runner_beneficiary_account_test'),

        'baseUrlSquado'          => config('gtb.base_url_squado'),
        'baseUrlSquadoTest'      => config('gtb.base_url_squado_test'),
    ];

    }
}
