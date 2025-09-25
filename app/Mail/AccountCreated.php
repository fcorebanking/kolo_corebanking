<?php
namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
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
            \logInfo(env('MAIL_FROM_ADDRES'), "Email Credentials");
            return $this->from(env('MAIL_FROM_ADDRES'), $this->data['company_name'])->subject($this->data['subtext'])->markdown('mails.account-created')->with('data', $this->data);

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
