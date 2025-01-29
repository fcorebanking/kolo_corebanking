<?php

namespace App\Http\Controllers;
use App\Mail\NewAccountCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SMSController extends Controller
{
    public function testEmail(Request $request){
        try{
        $userinfo = auth()->user();
        if(auth()->user()->company->companyEmail){
            $datax = [
                'message' => $request->test_message,
                'subtext' => "$request->test_subject",
                'desc' => $request->test_message,
                'status' => 1,
                'email' => $request->test_to_email,
                'from' => $userinfo->company->companyEmail->MAIL_FROM_ADDRESS,
                'company_name' => $userinfo->company->company_name,
                'user' => $userinfo,
                'name' => '',
                'account_number'=>''
            ];
            initSMTPconfiguration($userinfo->company->companyEmail);
            try{
            Mail::to($request->test_to_email)
            ->send(new NewAccountCreated($datax));
        } catch (\Exception $e) {
            // Log the exception for internal debugging purposes
            \Log::error($e);

            // Handle specific SMTP authentication errors
            if ($e instanceof \Symfony\Component\Mailer\Exception\TransportException
                && $e->getPrevious() instanceof \Symfony\Component\Mailer\Exception\TransportException
            ) {
                $errorDetails = "Failed to send email: Incorrect authentication data. Please check your email credentials.";
        //         email: Incorrect authentication data. Please check your email credentials
        // email: Incorrect authentication data. Please check your email
        // email: Incorrect authentication data.
        // email: Incorrect authentication
        // email: Incorrect
        // ;
            } else {
                // Handle other exceptions generically
                $errorDetails = "Failed to send email: " . $e->getMessage();
            }

            // Return a user-friendly error response
            return response()->json(["status" => 0, "message" => $errorDetails]);
        }
            return response()->json(["status" => 1, "message" => "Message Sent!"]);
        }else{
            return response()->json(["status" => 0, "message" => "Message failed!"]);
        }
    }catch(Exception $e){
        return response()->json(["status" => 0, "message" => $e->getMessage()]);
    }
    }
    public function change_custom_email_state(){
        $auth = auth()->user()->company->companyEmail;
        if($auth->companyemail_active){
            $auth->companyemail_active =0;
        }else{
            $auth->companyemail_active =1;
        }
        $auth->save();
        return response()->json(["status" => 1, "message" =>$auth->companyemail_active]);

    }
}
