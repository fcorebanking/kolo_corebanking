<?php
namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        try {
            \logInfo($this->data['user'], "Reset Password Log");
            \logInfo(env('MAIL_USERNAME'), "Email Credentials");
            return $this->from(env('MAIL_USERNAME'), $this->data['company_name'])->subject($this->data['subtext'])->markdown('mails.reset-password')->with('data', $this->data);
        } catch (Exception $e) {
            \logInfo($e->getMessage());
        }
        // return $this->markdown('mails.transaction-alert',[
        //         'data' => $this->data
        //     ]);

        // return $this->markdown('mails.transaction-alert',[
        //     'data' => $this->data
        // ]);
        //    return  $this
        //     ->from(env('MAIL_USERNAME'), 'Sender name')
        //     ->subject('Here is my subject')
        //     ->view('mails.transaction-alert',)->with('data',$this->data);
    }
}
