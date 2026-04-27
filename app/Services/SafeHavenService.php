<?php
namespace App\Services;

use App\Models\company_wallet;

class SafeHavenService
{
    public function initCredentials()
    {
        $currentRequest = request()->all();
        logInfo(json_encode($currentRequest), "Safe Haven request");

        $company_id = auth()->user()->comany_id ?? company_wallet::where('vaccount', $currentRequest['data']['creditAccountNumber'])->first()->company_id;

        return match ($company_id) {

            63 => roseBulgePayredentials(),      //7000299948
            67 => $this->oneZoyaPayredentials(), //7000320798
            92 => $this->jethoPayredentials(),   // 7000528130
            97 => $this->dfsPayredentials(),     // 7000671610
            default
               => $this->finovaCredentials(), //
        };
    }

    public function finovaCredentials()
    {
        [
            'owner'                      => 'Finova',
            'sendingAccount'             => "0117187948",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => env('SAFEHAVEN_CLIENT_ID_LIVE', 'd2a1252a1c5dc149737983ea62f2adaa'),
            'client_assertion_live'      => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImQyYTEyNTJhMWM1ZGMxNDk3Mzc5ODNlYTYyZjJhZGFhIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3MzI0NzcyMSwiZXhwIjoxODA0NzgzNTU0fQ.PWorYHViLvIXb21Q6IOUfcYay49Rt01cTwg7kpSYW2KaCqu--tXrorAJGx0O2hSa4zA_GawrxHduoF1G65BHtQ6FiX98mzV3M7mCwTC9tcjjvJrRubHRO0rhViXyaQX0qadxHmBcR1B2Fb1ZrUjdFPTPaaU5LCufcYa1DV7csYc'),
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0117187948" ?? "0115361250",

        ];

    }

    public function jethoPayredentials()
    {
        [
            'owner'                      => 'Jethopay',
            'sendingAccount'             => "0113408373",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => '740e7c6b3abaa33bb2735e22c989e1b6',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6Ijc0MGU3YzZiM2FiYWEzM2JiMjczNWUyMmM5ODllMWI2IiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3NzI5MDgyOSwiZXhwIjoxODA0NzgzNTU0fQ.UeqFRZEJBJynT0ZXwwfFV11f6CViGC80LpJaQWtN37G3sAfTkJvN2QyN1VDg_DHInspkOqIbagSpygOrvPaBUUQfeLHMoRi8olUd4d6GqA-sZgxULHvIyFYklURElg-PA3v-jkTL-0nTaxmwBmbglZmLBzEtpKYlufvYPqEbqXQ',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0113408373",

        ];

    }

    public function dfsPayredentials()
    {
        [
            'owner'                      => 'DFS',
            'sendingAccount'             => "0110858465",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => 'b214d88cd603a80ec43744b9d3430c7a',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImIyMTRkODhjZDYwM2E4MGVjNDM3NDRiOWQzNDMwYzdhIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3NzI5MzMxNSwiZXhwIjoxODA0NzgzNTU0fQ.tlJ3nfr69nWL_lzOUK8q22bxj13Wjw0f2_iNaiMjeorjpCV_WHivEhbptujA_f0NRMj09f_n35wm7qnWJexXv-1k7Z9Ue4ESQj4qbLV9JnYiFjp6ADNzVZOPNhtRDprWT4idL8j3zkGT1AWxTIVZMBDX1oH2BLGTPx9aSHU9H3k',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0110858465",

        ];

    }

    public function oneZoyaPayredentials()
    {
        [
            'owner'                      => 'One Zoya',
            'sendingAccount'             => "0117171310",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => 'c4656fb72ddafe9cbefb5cd0e7aa2038',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImM0NjU2ZmI3MmRkYWZlOWNiZWZiNWNkMGU3YWEyMDM4IiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3NzI5MjI4MCwiZXhwIjoxODA0NzgzNTU0fQ.dhNn0mOsXfTRLr_fpCoIM9t1U5PCY3Zu1wCC7PWOzoZP99CiM-icqsYEtAXvBlTAhp7qfhZJQDJBAr1yslABKi-AelguqM5dVMfZPvCH77Q1WKC5yPCuUFp9ClP61N6EXzlODQnQt_mq3LK0WFewPjaU_PW3phgwoYiyVtXALrk',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0117171310",

        ];

    }

    public function roseBulgePayredentials()
    {
        [
            'owner'                      => 'RoseBulge',
            'sendingAccount'             => "0113905054",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => env('SAFEHAVEN_CLIENT_ID_LIVE', '922260eaf8dc46963abeb7675e4f2036'),
            'client_assertion_live'      => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6IjkyMjI2MGVhZjhkYzQ2OTYzYWJlYjc2NzVlNGYyMDM2IiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3NzI4NjM2OSwiZXhwIjoxODA0NzgzNTU0fQ.iyIog4fjzmop0QMqg4hZMdBFJoJ26PfU42_v_va5kVFQuBVRZ0pw4i4XpoI5K_hVz9A5vZo-sxVh660IQj1NYk_YQpe9f3ZcIjTzPlQHCXB-mEWjtQ1v_PsczXfEv1kvnpxy2sr_K8tty7t0ZUBj-r2LZHc3_s2iHFvnolAHhtk'),
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0113905054",

        ];

    }
}
