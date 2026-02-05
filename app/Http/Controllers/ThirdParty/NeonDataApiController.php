<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;

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

        $getToken = $this->loginUser();
        return $this->getToken($getToken);
    }

    public function loginUser()
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

        \logInfo($response, "Neon Login Response");

        return \json_decode($response, true);
    }

    public function getToken($getToken)
    {
        $token = $getToken['token'];

        \logInfo($token, "Neon Login token");

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://ep-green-mountain-ad9fuebm.neonauth.c-2.us-east-1.aws.neon.tech/neondb/auth/get-session",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Origin: https://finova.ng',
            ],
        ]);

        $response = curl_exec($curl);

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $rawHeaders = substr($response, 0, $headerSize);
        $body       = substr($response, $headerSize);

        curl_close($curl);

        // Parse headers
        $headers = [];
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value]       = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        $jwt = $headers['Set-Auth-Jwt'] ?? null;

        \logInfo($jwt, "Set-Auth-Jwt");

        return $jwt;

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
