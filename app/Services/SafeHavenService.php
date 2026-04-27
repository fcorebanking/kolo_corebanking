<?php
namespace App\Services;

class SafeHavenService
{
    public function initCredentials()
    {
        return match (auth()->user()->comany_id) {

            67 => $this->oneZoyaPayredentials(),

            97 => $this->dfsPayredentials(),

            92 => $this->jethoPayredentials(),

            default
               => $this->finovaCredentials(),
        };
    }

    public function finovaCredentials()
    {
        [
            $this->sendingAccount = "0117187948",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => env('SAFEHAVEN_CLIENT_ID_LIVE', 'd2a1252a1c5dc149737983ea62f2adaa'),
            'client_assertion_live'      => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImQyYTEyNTJhMWM1ZGMxNDk3Mzc5ODNlYTYyZjJhZGFhIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3MzI0NzcyMSwiZXhwIjoxODA0NzgzNTU0fQ.PWorYHViLvIXb21Q6IOUfcYay49Rt01cTwg7kpSYW2KaCqu--tXrorAJGx0O2hSa4zA_GawrxHduoF1G65BHtQ6FiX98mzV3M7mCwTC9tcjjvJrRubHRO0rhViXyaQX0qadxHmBcR1B2Fb1ZrUjdFPTPaaU5LCufcYa1DV7csYc'),
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            $this->debitAccount = "0117187948" ?? "0115361250",

        ];

    }

    public function jethoPayredentials()
    {
        [
            $this->sendingAccount = "0117187948",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => env('SAFEHAVEN_CLIENT_ID_LIVE', 'd2a1252a1c5dc149737983ea62f2adaa'),
            'client_assertion_live'      => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImQyYTEyNTJhMWM1ZGMxNDk3Mzc5ODNlYTYyZjJhZGFhIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3MzI0NzcyMSwiZXhwIjoxODA0NzgzNTU0fQ.PWorYHViLvIXb21Q6IOUfcYay49Rt01cTwg7kpSYW2KaCqu--tXrorAJGx0O2hSa4zA_GawrxHduoF1G65BHtQ6FiX98mzV3M7mCwTC9tcjjvJrRubHRO0rhViXyaQX0qadxHmBcR1B2Fb1ZrUjdFPTPaaU5LCufcYa1DV7csYc'),
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            $this->debitAccount = "0117187948" ?? "0115361250",

        ];

    }

    public function dfsPayredentials()
    {
        [
            $this->sendingAccount = "0117187948",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => env('SAFEHAVEN_CLIENT_ID_LIVE', 'd2a1252a1c5dc149737983ea62f2adaa'),
            'client_assertion_live'      => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImQyYTEyNTJhMWM1ZGMxNDk3Mzc5ODNlYTYyZjJhZGFhIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3MzI0NzcyMSwiZXhwIjoxODA0NzgzNTU0fQ.PWorYHViLvIXb21Q6IOUfcYay49Rt01cTwg7kpSYW2KaCqu--tXrorAJGx0O2hSa4zA_GawrxHduoF1G65BHtQ6FiX98mzV3M7mCwTC9tcjjvJrRubHRO0rhViXyaQX0qadxHmBcR1B2Fb1ZrUjdFPTPaaU5LCufcYa1DV7csYc'),
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            $this->debitAccount = "0117187948" ?? "0115361250",

        ];

    }

    public function oneZoyaPayredentials()
    {
        [
            $this->sendingAccount = "0117187948",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => env('SAFEHAVEN_CLIENT_ID_LIVE', 'd2a1252a1c5dc149737983ea62f2adaa'),
            'client_assertion_live'      => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImQyYTEyNTJhMWM1ZGMxNDk3Mzc5ODNlYTYyZjJhZGFhIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3MzI0NzcyMSwiZXhwIjoxODA0NzgzNTU0fQ.PWorYHViLvIXb21Q6IOUfcYay49Rt01cTwg7kpSYW2KaCqu--tXrorAJGx0O2hSa4zA_GawrxHduoF1G65BHtQ6FiX98mzV3M7mCwTC9tcjjvJrRubHRO0rhViXyaQX0qadxHmBcR1B2Fb1ZrUjdFPTPaaU5LCufcYa1DV7csYc'),
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            $this->debitAccount = "0117187948" ?? "0115361250",

        ];

    }

    public function roseBulgePayredentials()
    {
        [
            $this->sendingAccount = "0117187948",
            'grant_type_live'            => env('SAFEHAVEN_GRANT_TYPE_LIVE', 'client_credentials'),
            'client_assertion_type_live' => env('SAFEHAVEN_CLIENT_ASSERTION_TYPE_LIVE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'),
            'client_id_live'             => env('SAFEHAVEN_CLIENT_ID_LIVE', 'd2a1252a1c5dc149737983ea62f2adaa'),
            'client_assertion_live'      => env('SAFEHAVEN_CLIENT_ASSERTION_LIVE', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJjb3JlLmZpbm92YS5uZyIsInN1YiI6ImQyYTEyNTJhMWM1ZGMxNDk3Mzc5ODNlYTYyZjJhZGFhIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3MzI0NzcyMSwiZXhwIjoxODA0NzgzNTU0fQ.PWorYHViLvIXb21Q6IOUfcYay49Rt01cTwg7kpSYW2KaCqu--tXrorAJGx0O2hSa4zA_GawrxHduoF1G65BHtQ6FiX98mzV3M7mCwTC9tcjjvJrRubHRO0rhViXyaQX0qadxHmBcR1B2Fb1ZrUjdFPTPaaU5LCufcYa1DV7csYc'),
            'base_url_live'              => env('SAFEHAVEN_BASE_URL_LIVE', 'https://api.safehavenmfb.com/'),
            $this->debitAccount = "0117187948" ?? "0115361250",

        ];

    }
}
