<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware("custom_permission:broadcast|root");

    }
    public function index()
    {
        $data = [
            'category_name' => 'forms',
            'page_name' => 'quill',
            'page_menu' => 'company_broadcast',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            // 'status' => status::all(),


        ];
        // $pageName = 'html5'; /Users/mac/Documents/laravel project/wiredbanking/resources/views/company/broadcast/main.blade.php

        return view('company.broadcast.main')->with($data);
    }

    public function send_broadcast(Request $request){

        $validator = Validator::make($request->all(), [
            'audience' => 'required',
            'option' => 'required',
            'message' => 'required',
            'subject'=>'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            return response()->json(['status'=>0,'message'=>$errors]);
        }
        $message_sent = $request->message;
        $audience = $request->audience;
        $option = $request->option;
        $users= $request->user;
        $subject= $request->subject;
        $msgcount =[];
        $totalchar =strlen($message_sent);
        $takeout = $totalchar -300;
        if($option !='email' && $option !='whatsapp'){
            if($totalchar > 300){
                return response()->json(['status'=>0,'message'=>"Maximun Character is 300 remove $takeout character from your text"]);
            }
        }
        $smsstate = 1;
        if($option =='email'){
            $smsstate = 3;
        }
        if($option =='sms'){
            $smsstate = 2;
        }
        if($option =='whatsapp'){
            $smsstate = 4;
        }
        $sms_charge= auth()->user()->company->setting->sms_charge;
        $num_page=1;
        if($totalchar >150){
            $num_page=2;
        }
        $sms_charge *= $num_page;

        //
        $users_to_send_to  = User::where(['company_id'=>auth()->user()->company_id,'status_id'=>1]);
        if( $audience ==1){
            $users_to_send_to  = $users_to_send_to->where('user_type_id',1);
        }else if($audience ==2){
            $users_to_send_to  = $users_to_send_to->where('user_type_id',2);
        }
        if($users != '' ){
            $users_to_send_to = $users_to_send_to->where('uuid',$users);
        }

        $users_to_send_to = $users_to_send_to->get();

        if($users_to_send_to){
            foreach($users_to_send_to as $users_to_send_to_data){
            $logdata =  [
                'user_id'=>$users_to_send_to_data->id,
                'subject'=>$subject,
                'message'=>$message_sent ,
                'num_page'=>$num_page,
                'amount'=>$sms_charge,
                ];
                $msgcount[] =  sendSMS($users_to_send_to_data->id,"$message_sent",true,true,$subject,'',0,false,$smsstate, $logdata);
            }

        }


        return response()->json(['status'=>1,'message'=>'Message sent','msgsent'=>$msgcount ]);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function notificationLog(Request $request)
    {

            $category_name = 'forms';
            $page_name = 'quill';
            $page_menu = 'company_broadcast_log';
            $has_scrollspy = 0;
            $scrollspy_offset = '';
            $alt_menu = 0;
        $list = Notification::join('users','users.id','notifications.user_id')->select('notifications.*','users.first_name','users.last_name','users.virtual_account')->where('users.company_id',auth()->user()->company_id)->orderBy('notifications.id','DESC')->paginate(10);
        // $list = Notification::whereHas('User', function ($q){
        //     $q->where(['company_id'=>auth()->user()->company_id]);
        //         })->select('notifications.*')->orderBy('notifications.id','DESC')->paginate(10);
        return view('company.broadcast.notification',compact('scrollspy_offset','alt_menu','category_name','page_name','page_menu','has_scrollspy','list'))->with('i', ($request->input('page', 1) - 1) * 10);
    }
    public function verifyCustomerEmail($user_id)
    {
        $userAccount = User::where('uuid',$user_id)->first();
        if($userAccount->email_verified_at == null){
            $verificationHash = \generateAPIKey(12);
            $userAccount->updated_at = Carbon::now();
            $userAccount->verification_hash = $verificationHash;
            $userAccount->save();
            logInfo($verificationHash, "UUID $user_id Verification Hash by ".auth()->user()->username." at ".now());
            $url = url("verify-account/" . $verificationHash);
            $msg = "<p>Hi $userAccount->first_name $userAccount->last_name, </p><p> Your account need verification in other for us to keep sending you notifications.</p>";
            $msg .= '<p><a href="' . $url . '">Click this to verify your account</a></p>';
            $sendSms = sendEmail("Account Activation Email", $msg, $userAccount);
            return response(json_encode(['status' => 1, 'message' => 'Account verification has been sent to customer!']));
        }else{
            return response(json_encode(['status' => 0, 'message' => 'Account Already Verified!']));

        }

    }
}
