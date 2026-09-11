<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendTierMail extends Mailable
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
            // FIX 2: Set a safe environment fallback default for the email address
            $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@finova.ng');
            
            \logInfo($fromAddress, "Mail from ");

            // FALLBACKS: Dynamically extract app name or use generic string fallbacks
            $senderName = $this->data['company_name'] ?? config('app.name', 'Finova');
            $subjectText = $this->data['subtext'] ?? 'Account Tier Update';

            return $this->from($fromAddress, $senderName)
                        ->subject($subjectText)
                        ->markdown('mails.tier-mail')
                        ->with('data', $this->data);

        } catch (Exception $e) {
            \logInfo($e->getMessage(), 'SendTierMail Mailable Build Error');
            
            // Re-throw so your execution context registers the failure rather than failing silently
            throw $e;
        }
    }
}
