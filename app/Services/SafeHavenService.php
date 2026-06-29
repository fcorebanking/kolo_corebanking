<?php
namespace App\Services;

class SafeHavenService
{
    public function initCredentials($company_id)
    {
        $company_id = (int) $company_id;

        logInfo(json_encode($company_id), "SafeHavenService company_id");

        return match ($company_id) {

            46  => $this->myPayCredentials(),       //7000219629
            63  => $this->roseBulgePayredentials(), //7000299948
            67  => $this->oneZoyaPayredentials(),   //7000320798
            74  => $this->kokakashCredentials(),
            80  => $this->tomisinPayCredentials(), //7000434624
            92  => $this->jethoPayredentials(),    // 7000528130
            96  => $this->benjafaPayCredentials(), //7000645174
            97  => $this->dfsPayredentials(),      // 7000671610
            99  => $this->tsgPayCredentials(),     // 7000847167
            100 => $this->greenPayCredentials(),   //7000910744
            101 => curtisCredentials(),
            default
                => $this->finovaCredentials(), //
        };
    }

    //expires 2027-03-11T16:45:54Z - in 9 months

    public function finovaCredentials()
    {
        return [
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

    //expires 2027-03-11T16:45:54Z - in 9 months

    public function jethoPayredentials()
    {
        return [
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

    //expires 2027-03-11T16:45:54Z - in 9 months

    public function dfsPayredentials()
    {
        return [
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

    //expires 2027-03-11T16:45:54Z - in 9 months

    public function oneZoyaPayredentials()
    {
        return [
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

    //expires 2027-03-11T16:45:54Z - in 9 months

    public function roseBulgePayredentials()
    {
        return [
            'owner'                      => 'RoseBulge',
            'sendingAccount'             => "0113905054",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => '922260eaf8dc46963abeb7675e4f2036',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6IjkyMjI2MGVhZjhkYzQ2OTYzYWJlYjc2NzVlNGYyMDM2IiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3NzI4NjM2OSwiZXhwIjoxODA0NzgzNTU0fQ.iyIog4fjzmop0QMqg4hZMdBFJoJ26PfU42_v_va5kVFQuBVRZ0pw4i4XpoI5K_hVz9A5vZo-sxVh660IQj1NYk_YQpe9f3ZcIjTzPlQHCXB-mEWjtQ1v_PsczXfEv1kvnpxy2sr_K8tty7t0ZUBj-r2LZHc3_s2iHFvnolAHhtk',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0113905054",

        ];

    }

    //expires 2027-05-27T19:22:55Z - in 1 year
    public function tomisinPayCredentials()
    {
        return [
            'owner'                      => 'Tomisin',
            'sendingAccount'             => "0114082640",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => 'd3b6ed88797b77b01bd0a8c6e370130b',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImQzYjZlZDg4Nzk3Yjc3YjAxYmQwYThjNmUzNzAxMzBiIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3OTkxMzUxOSwiZXhwIjoxODExNDQ1Nzc1fQ.ueZjWrSJjBB1SaG7-643oDhRWop0WALe9kuTWcjkrszJ27MitWQg6wFMW3A9MUSUENNDL44bb0a0jy5JMIvGrnFhmQ2Q0rWOAS7yW9-IjFE1QsF9BuzvLC1hkO36OhFqiw609dH49ObtadoXTsTLZikAlgnAE-22weKbAM34KTg',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0114082640",

        ];

    }

    //expires 2027-05-27T19:22:55Z - in 1 year
    public function benjafaPayCredentials()
    {
        return [
            'owner'                      => 'Benjafa',
            'sendingAccount'             => "0116455251",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => '71c1e31a62198bd587437b04be39f52c',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6IjcxYzFlMzFhNjIxOThiZDU4NzQzN2IwNGJlMzlmNTJjIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3OTkxMzMzOSwiZXhwIjoxODExNDQ1Nzc1fQ.RYyQT3Z8uC1dxbYWwou3xU6we6BUq3XE9q9eyDpprXm-EHYo8v8H5OlM59PrpuUAt4Zwapd4K_Y_ARnTy0rW3_Xc5o4vKF-OpkJRWZ-K_-XxhnACLajdZYhq11NbxI2O7J0wPOxITpIBk1jNkDhvoEhY2Zki5qHjQ4YyShaUysk',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0116455251",

        ];

    }

    //expires 2027-05-27T19:22:55Z - in 1 year
    public function greenPayCredentials()
    {
        return [
            'owner'                      => 'Greenpay',
            'sendingAccount'             => "0111031898",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => 'a4c597400913fe437b344bcc46077ca8',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImE0YzU5NzQwMDkxM2ZlNDM3YjM0NGJjYzQ2MDc3Y2E4IiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3OTkxNDU5NiwiZXhwIjoxODExNDQ1Nzc1fQ.ADAz14pSp2EamyfKhgsySry8TKVrmsmB_v7apjJBLNI71IyrUUA6tGa33tmc9SNklOMY1ZJmwY7X3f8F26B9hiFxsJvu8Cr_QcexpU2ZeUG6WpMirfks7Pg7HKHfc42H_kBhnIM3Wkc26j-_leOTkIsQRfwQ69Ta60ix0O8yn6o',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0111031898",

        ];

    }

    //expires 2027-05-27T19:22:55Z - in 1 year
    public function myPayCredentials()
    {
        return [
            'owner'                      => 'myPay',
            'sendingAccount'             => "0112262239",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => '7c29a9919a7c23e51b7221194e2ce4e9',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6IjdjMjlhOTkxOWE3YzIzZTUxYjcyMjExOTRlMmNlNGU5IiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3OTkxNTIxNCwiZXhwIjoxODExNDQ1Nzc1fQ.ZnzFAyGgfBvJevkoo78-dkXN3VsBMAkpY-K6sso_r80MHKNpdwcY_pioYo8Kj9NweQVUX0rsE3rfijHFHHESqK3g8MSAHLYfGEwjnLvguhYyT3Sav4ZcwpDE4mAvmQgvmP-rTJ5NcNY3FOlTS7iJX5KJl8iq-oiSUfXny9aaz3Q',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0112262239",

        ];

    }

    //expires 2027-05-27T19:22:55Z - in 1 year
    public function tsgPayCredentials()
    {
        return [
            'owner'                      => 'TSG Pay',
            'sendingAccount'             => "0114894731",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => 'a57313174bc5a632b75d793fb9086f8c',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImE1NzMxMzE3NGJjNWE2MzJiNzVkNzkzZmI5MDg2ZjhjIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3OTkxNjMxNSwiZXhwIjoxODExNDQ1Nzc1fQ.k7NA1Y1bv0qkonfrS9Gez1ATsSR9CyD6P7lHeeYCuDdhzCdY7B_TXDXjMSk1WlW6LOzkWdIjzLtIXsNCLBvLDzxwVx2Zg48AJMTgyzRvxWLGz-K_jZJbJC1QjfqukkrGkhG2G0Irk_-zobCZkk5HnWDsZfJBY4MZJnxhqNg6ptM',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0114894731",

        ];

    }

    public function curtisCredentials()
    {
        return [
            'owner'                      => 'Curtis Pay',
            'sendingAccount'             => "0118534446",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => 'a57313174bc5a632b75d793fb9086f8c',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImI0YzJhN2UxNDM5NzMzNGI2Y2NkNzE3Mzc1ZTdlNjNhIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc4MTg3MDY0MywiZXhwIjoxODEzNDA2MzY0fQ.SYmpmfQiWA8Lek3a6VlMIlKHVmgYDhz0DBWlb2ImQK7xD3TvmQ9qErF7LqLvW7dXLcVXB6Uvw8ox9io1cZpQa3fAlYfkhYoGT6Jl2ngQrKhf2KoSZ_OlEvkZqthLEXQgM1mL0pBF4qzVJUJm_mNFx05mVgN2zFlS5KdwJeGug9k',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0118534446",

        ];

    }

    public function kokakashCredentials()
    {
        return [
            'owner'                      => 'Kokakash',
            'sendingAccount'             => "0114888048",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => '55b0070068588a82ce236d8ea737c407',
            'client_assertion_live'      => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6IjU1YjAwNzAwNjg1ODhhODJjZTIzNmQ4ZWE3MzdjNDA3IiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc4Mjc0MjgwMiwiZXhwIjoxODEzNDA2MzY0fQ.GYg5jKC-wRW-sFHTOeF-GsT6hf5fLTZmD-p5DBNwuD0UMBinU7k_e0tocqA58BBzh236R5HaQzb-2bGgUYRCyIk6czjqyQsPMo8xsuIfNJ-QLVWtU_SeAWaAeyJAnjpTH9VxSba8Fwlv15dkR7bnyod7SkBZDeoRixVy08sHgzY',
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            'debitAccount'               => "0114888048",

        ];

    }
}
