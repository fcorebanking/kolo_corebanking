<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    // FIX 1: Changed visibility to public so Blade/Markdown templates can read it automatically
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
            // Safe logging with null-coalescing to avoid missing key errors
            \logInfo($this->data['user'] ?? 'No user data', "Reset Password Log");
            \logInfo(env('MAIL_USERNAME'), "Email Credentials");

            // FIX 2: Corrected the typo from MAIL_FROM_ADDRES to MAIL_FROM_ADDRESS
            $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@finova.ng');
            
            // FALLBACKS: Match company_name to config or default text
            $senderName = $this->data['company_name'] ?? config('app.name', 'Finova');
            $subjectText = $this->data['subtext'] ?? 'Reset Account Password';

            return $this->from($fromAddress, $senderName)
                        ->subject($subjectText)
                        ->markdown('mails.reset-password')
                        ->with('data', $this->data);

        } catch (Exception $e) {
            \logInfo($e->getMessage(), 'ResetAccountMail Mailable Build Error');
            
            // Re-throw so your application pipeline registers the failure instead of failing silently
            throw $e; 
        }
    }
}
