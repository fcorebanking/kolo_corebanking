<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewAccountCreated extends Mailable
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
        return $this->from(env('MAIL_USERNAME'),$this->data['company_name'])->subject($this->data['subtext'])->markdown('mails.transaction-alert')->with('data', $this->data);
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
