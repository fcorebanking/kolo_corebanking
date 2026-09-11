<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    // FIX: Explicitly declare the property so the class knows it exists
    public $data;

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
            // Typo Fix: Corrected MAIL_FROM_ADDRES to MAIL_FROM_ADDRESS
            $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@finova.ng');
            
            \logInfo($fromAddress, "Email Credentials");

            return $this->from($fromAddress, $this->data['company_name'] ?? 'Finova')
                        ->subject($this->data['subtext'] ?? 'Account Created')
                        ->markdown('mails.account-created')
                        ->with('data', $this->data);

        } catch (Exception $e) {
            \logInfo($e->getMessage(), 'Mailable Build Error');
            
            // Re-throw or return default behavior so the mail pipeline doesn't fail silently
            throw $e; 
        }
    }
}
