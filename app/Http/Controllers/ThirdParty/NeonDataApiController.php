<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Support\Facades\Http;

class NeonDataApiController extends WebhookController
{
    private $token;

    public function __construct()
    {
        $this->token = "napi_qbrepzlp5sfd78skgber5qh395leeu1c9mcp0lvcj9swb4174wjf6tg71jx99klv";
    }

    public function login()
    {

        $response = Http::withHeaders($this->NeonDataApiHeader())->get(
            'https://ep-green-mountain-ad9fuebm.neonauth.c-2.us-east-1.aws.neon.tech/neondb/auth/get-session'
        );
        return $response->json();
    }

    public function addCluster($businessname, $email, $phone, $contactfirstname, $contactlastname, $bvn)
    {
        $body = [
            'businessname'     => $businessname,
            'email'            => $email,
            'phone'            => $phone,
            'contactfirstname' => $contactfirstname,
            'contactlastname'  => $contactlastname,
            'bvn'              => $bvn,
        ];

        logInfo($body, "Add cluster request");

        try {
            $response = $this->vPayPostRequest("query/merchant/cluster/add", $body);

            if (isset($response['id'])) {
                return ['status' => true, 'message' => 'Cluster Added', 'data' => ['cluster_id' => $response['id']]];
            } else {
                return ['status' => false, 'message' => 'Failed to get Cluster id'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "VPay Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    private function NeonDataApiHeader()
    {
        $header = [
            'Content-Type'  => 'application/json',
            //  'Origin'        => "https://finova.ng",
            'Authorization' => 'Bearer ' . $this->token,
        ];

        return $header;
    }

}
