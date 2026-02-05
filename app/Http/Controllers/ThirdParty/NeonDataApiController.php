<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Support\Facades\Http;

class NeonDataApiController extends WebhookController
{
    private $token;
    private $email;
    private $password;

    public function __construct()
    {

        $this->token    = "napi_qbrepzlp5sfd78skgber5qh395leeu1c9mcp0lvcj9swb4174wjf6tg71jx99klv";
        $this->email    = "fcorebanking@gmail.com";
        $this->password = "4t5@#1000##";
    }

    public function login()
    {

        $data = [
            "email"    => $this->email,
            "password" => $this->password,

        ];

        $data_string = json_encode($data);

        \logInfo($data_string, "Neon Request body");

        $curl = curl_init();
        // $bvn = $bvn == '' ? '' : $bvn;

        $ch = curl_init('https://ep-green-mountain-ad9fuebm.neonauth.c-2.us-east-1.aws.neon.tech/neondb/auth/sign-in/email');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json', "Authorization: Bearer {$this->token}",
            ]
        );

        $response = curl_exec($ch);
        return $response;
        $account = \json_decode($response, true);

        logInfo(json_encode($account), "Neon Data Api Account Response");

        return $this->NeonDataApiHeader();

    }

    public function getToken()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->VIDEOSDK_API_ENDPOINT . '/rooms/validate/' . $meetingId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $token,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

        return $this->NeonDataApiHeader();
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
            'Origin'        => "https://finova.ng",
            'Authorization' => 'Bearer ' . $this->token,
        ];

        return $header;
    }

}
