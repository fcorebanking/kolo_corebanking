<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideosdkModelRequest;
use App\Http\Requests\UpdateVideosdkModelRequest;
use App\Models\VideosdkModel;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Models\company_settings;
use Klein\Klein as Route;
use DateTimeImmutable;
class VideosdkModelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $VIDEOSDK_API_KEY = "072ebb28-aff9-43c5-a63d-4f7d35e7e017";
    public  $VIDEOSDK_SECRET_KEY = "6c2fe4d3a02c39cd37f5ede1c794b2255aa966eaf5eacb5a16d748455452cfe0";
    public   $VIDEOSDK_API_ENDPOINT = "https://api.videosdk.live/v2";
    public function index()
    {


            if(!auth()->user()->company->setting->access_meeting){
                return abort(403);
            }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'meeting',
            'page_menu' => 'meeting',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        return view('company.video_page.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreVideosdkModelRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVideosdkModelRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VideosdkModel  $videosdkModel
     * @return \Illuminate\Http\Response
     */
    public function show(VideosdkModel $videosdkModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\VideosdkModel  $videosdkModel
     * @return \Illuminate\Http\Response
     */
    public function edit(VideosdkModel $videosdkModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateVideosdkModelRequest  $request
     * @param  \App\Models\VideosdkModel  $videosdkModel
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVideosdkModelRequest $request, VideosdkModel $videosdkModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VideosdkModel  $videosdkModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(VideosdkModel $videosdkModel)
    {
        //
    }
    public function tokengenerator(){
        /** Your API key and secret */

        $route = new Route();
        $route->respond('GET', '/get-token', function ()
        {

            header("Content-type: application/json; charset=utf-8");
            $issuedAt = new DateTimeImmutable();
            $expire = $issuedAt->modify('+24 hours')->getTimestamp();
            $payload = [];
            $payload['apikey'] =  $this->VIDEOSDK_API_KEY;
            $payload['permissions'] = array(
                "allow_join",
                "allow_mod"
            );
            $payload['iat'] = $issuedAt->getTimestamp();
            $payload['exp'] = $expire;
            $alg = 'HS256'; // This is alg
            $jwt = JWT::encode($payload, $this->VIDEOSDK_SECRET_KEY,$alg);
            return json_encode(array(
                "token" => $jwt
            ));
        });
        $route->respond('POST', '/create-meeting', function ()
        {

            header("Content-type: application/json; charset=utf-8");

            $data = json_decode(file_get_contents('php://input') , true);

            $token = $data["token"];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->VIDEOSDK_API_ENDPOINT . '/rooms',
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $token,
                    'Content-Type: application/json'
                ) ,
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return $response;
        });
        $route->respond('GET', '/validate-meeting/[:meetingId]', function ($request)
        {
            header("Content-type: application/json; charset=utf-8");

            $meetingId = $request->meetingId;

            $data = json_decode(file_get_contents('php://input') , true);

            // $token = $data["token"];
            $token =$request->token;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->VIDEOSDK_API_ENDPOINT . '/rooms/validate/' . $meetingId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $token,
                    'Content-Type: application/json'
                ) ,
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return $response;
        });
        $route->respond('POST', '/start-recording', function ($request)
        {
            header("Content-type: application/json; charset=utf-8");
            $data = json_decode(file_get_contents('php://input') , true);
            $token = $request->token;
            $roomId = $request->roomId;
            $curl = curl_init();
            $body = array(
                "roomId" => $roomId,
                "config" => array(
                    "layout" => array(
                        "type" => "GRID",
                        "priority" => "SPEAKER",
                        "gridSize" => 1
                    ) ,
                    "theme" => "DARK",
                    "mode" => "video-and-audio",
                    "quality" => "low"
                ) ,
            );
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->VIDEOSDK_API_ENDPOINT . '/recordings/start',
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $token,
                    'Content-Type: application/json'
                ) ,
                CURLOPT_POSTFIELDS => json_encode($body) ,
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
        });
        $route->respond('POST', '/stop-recording', function ($request)
        {
            header("Content-type: application/json; charset=utf-8");
            $data = json_decode(file_get_contents('php://input') , true);
            $token = $request->token;
            $roomId = $request->roomId;
            $curl = curl_init();
            $body = array(
                "roomId" => $roomId,
            );
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->VIDEOSDK_API_ENDPOINT . '/recordings/end',
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $token,
                    'Content-Type: application/json'
                ) ,
                CURLOPT_POSTFIELDS => json_encode($body) ,
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return $response;
        });
        $route->dispatch();
    }
    public function saveMeetingInfo(Request $request){
        $company_settings =  company_settings::where('company_id',auth()->user()->company_id)->first();
        $company_settings->meetingID=$request->meetingid;
        $company_settings->meetingToken=$request->token;
        $company_settings->save();
        $ra = array("status" => 0, "message" => "Meeting Info Updated!");
        return \json_encode($ra);
    }
}
