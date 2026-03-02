<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendTierMail extends Mailable
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
        \logInfo(env('MAIL_FROM_ADDRESS'), "Mail from ");
        return $this->from(env('MAIL_FROM_ADDRESS'), $this->data['company_name'])->subject($this->data['subtext'])->markdown('mails.tier-mail')->with('data', $this->data);

    }
}
