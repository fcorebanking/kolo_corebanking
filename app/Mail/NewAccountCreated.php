<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    // FIX 1: Explicitly declare the property so the class can access it safely
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
            // FIX 2: Corrected the typo from MAIL_FROM_ADDRES to MAIL_FROM_ADDRESS
            $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@finova.ng');
            
            // FALLBACKS: If data keys are missing, use config('app.name') or safe generic alternatives
            $senderName = $this->data['company_name'] ?? config('app.name', 'Finova');
            $subjectText = $this->data['subtext'] ?? 'New Account Update';

            return $this->from($fromAddress, $senderName)
                        ->subject($subjectText)
                        ->markdown('mails.transaction-alert')
                        ->with('data', $this->data);

        } catch (Exception $e) {
            \logInfo($e->getMessage(), 'NewAccountCreated Mailable Build Error');
            
            // Re-throw so it alerts your logging pipeline instead of crashing silently
            throw $e; 
        }
    }
}
