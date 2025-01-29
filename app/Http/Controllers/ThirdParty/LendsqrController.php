<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Http;


class LendsqrController extends Controller
{
    private $authorization;
    private $baseUrl;

    function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->authorization = config('lendsqr.authorization_test');
            $this->baseUrl = config('lendsqr.base_url_test');
        } else {

            $this->authorization = config('lendsqr.authorization_live');
            $this->baseUrl = config('lendsqr.base_url_live');
        }

        \logInfo($this->baseUrl, "lendsqr Base Url");
    }


    public function bvnLookup($bvn)
    {
        $url = "verification/karma/$bvn";

        try {
            $bvnLookup = $this->lendsqrGetRequest($url);

            logInfo($bvnLookup, "Verify bvn");

            if ($bvnLookup['status'] == "success") {

                if (isset($bvnLookup['data'])) {

                    return [
                        'status' => true,
                        'message' => 'BVN Lookup Successfuly',
                        'data' => [
                            "bvn" => $bvn,
                            "type" => "karma",
                            "loan_amount" => $bvnLookup["data"]["amount_in_contention"],
                            "reason" => $bvnLookup["data"]["reason"],
                            "default_date" => $bvnLookup["data"]["default_date"],
                            "identification_type" => $bvnLookup["data"]["karma_identity_type"]['identity_type'],
                            "reporting_company_name" => $bvnLookup["data"]["reporting_entity"]["name"],
                            "reporting_company_email" => $bvnLookup["data"]["reporting_entity"]["email"]
                        ]
                    ];
                } else {
                    return ['status' => false, 'message' => $bvnLookup['message']];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to Lookup BVN'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lendsqr Error");

            return ['status' => false, 'message' => 'Failed to Lookup BVN'];
        }
    }

    public function ecosystemChecker($bvn)
    {
        $url = "verification/ecosystem/$bvn";

        try {
            $bvnLookup = $this->lendsqrGetRequest($url);

            logInfo($bvnLookup, "Ecosystem Verify bvn");

            if ($bvnLookup['status'] == "success") {

                return [
                    'status' => true,
                    'message' => 'BVN Lookup Successfuly',
                    'data' => [
                        "bvn" => $bvn,
                        "type" => "ecosystem",
                        "first_name" => $bvnLookup["data"]["first_name"],
                        "last_name" => $bvnLookup["data"]["last_name"],
                        "bvn_phone_number" => $bvnLookup["data"]["bvn_phone_number"],
                        "date_of_birth" => $bvnLookup["data"]["date_of_birth"],
                        "age" => $bvnLookup["data"]["age"],
                        "phone_number" => $bvnLookup["data"]["phone_number"],
                        "email" => $bvnLookup["data"]["email"],
                        "gender" => $bvnLookup["data"]["gender"],
                        "loans" => $bvnLookup["data"]["loans"],
                        "loan_amount" => $bvnLookup["data"]["loan_amount"],
                        "loan_amount_minimum" => $bvnLookup["data"]["loan_amount_minimum"],
                        "loan_amount_maximum" => $bvnLookup["data"]["loan_amount_maximum"],
                        "loan_amount_average" => $bvnLookup["data"]["loan_amount_average"],
                        "settled_loans" => $bvnLookup["data"]["settled_loans"],
                        "settled_loan_amount" => $bvnLookup["data"]["settled_loan_amount"],
                        "settled_loan_amount_paid" => $bvnLookup["data"]["settled_loan_amount_paid"],
                        "running_loans" => $bvnLookup["data"]["running_loans"],
                        "running_loan_amount" => $bvnLookup["data"]["running_loan_amount"],
                        "past_due_loans" => $bvnLookup["data"]["past_due_loans"],
                        "past_due_loan_amount" => $bvnLookup["data"]["past_due_loan_amount"],
                        "past_due_loan_amount_due" => $bvnLookup["data"]["past_due_loan_amount_due"],
                        "penalty" => $bvnLookup["data"]["penalty"],
                        "penalty_paid" => $bvnLookup["data"]["penalty_paid"],
                        "delayed_paid_loans" => $bvnLookup["data"]["delayed_paid_loans"],
                        "delayed_paid_loan_amount" => $bvnLookup["data"]["delayed_paid_loan_amount"],
                        "delayed_paid_loans_trials" => $bvnLookup["data"]["delayed_paid_loans_trials"],
                        "delayed_paid_loans_avg" => $bvnLookup["data"]["delayed_paid_loans_avg"],
                        "delayed_paid_loans_trials_max" => $bvnLookup["data"]["delayed_paid_loans_trials_max"],
                        "delayed_paid_loans_trials_min" => $bvnLookup["data"]["delayed_paid_loans_trials_min"],
                        "first_loan_date" => $bvnLookup["data"]["first_loan_date"],
                        "last_loan_date" => $bvnLookup["data"]["last_loan_date"],
                        "loan_requests" => $bvnLookup["data"]["loan_requests"],
                        "failed_loan_requests" => $bvnLookup["data"]["failed_loan_requests"],
                        "logins" => $bvnLookup["data"]["logins"],
                        "first_login" => $bvnLookup["data"]["first_login"],
                        "last_login" => $bvnLookup["data"]["last_login"],
                        "unique_login_ips" => $bvnLookup["data"]["unique_login_ips"],
                        "unique_device_ids" => $bvnLookup["data"]["unique_device_ids"],
                        "distinct_mobile_os" => $bvnLookup["data"]["distinct_mobile_os"],
                        "duplicated_devices" => $bvnLookup["data"]["duplicated_devices"],
                        "shared_device_users" => $bvnLookup["data"]["shared_device_users"],
                        "credit_delinquency" => $bvnLookup["data"]["credit_delinquency"],
                        "processed_on" => $bvnLookup["data"]["processed_on"]
                    ]
                ];
            } else {
                return ['status' => false, 'message' => $bvnLookup["message"]];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lendsqr Error");

            return ['status' => false, 'message' => 'Failed to Lookup BVN'];
        }
    }


    public function lendsqrPostRequest($endpoint, $body)
    {
        \logInfo("$this->baseUrl$endpoint");
        $response = Http::withHeaders($this->lendsqrHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function lendsqrGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint", "Convertion url");
        logInfo($this->lendsqrHeader(), "Authorization key");
        $response = Http::withHeaders($this->lendsqrHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function lendsqrHeader()
    {
        $header = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer $this->authorization",
        ];

        return $header;
    }
}
