<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransactionReport extends Mailable
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
            // FIX 2: Added a fallback email address from the configuration context
            $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@finova.ng');
            
            \logInfo($fromAddress, "Mail from ");

            // FALLBACKS: Handle missing data components safely using config settings
            $senderName = $this->data['company_name'] ?? config('app.name', 'Finova');
            $subjectText = $this->data['subtext'] ?? 'Transaction Activity Report';

            return $this->from($fromAddress, $senderName)
                        ->subject($subjectText)
                        ->markdown('mails.transaction-report')
                        ->with('data', $this->data);

        } catch (Exception $e) {
            \logInfo($e->getMessage(), 'TransactionReport Mailable Build Error');
            
            // Re-throw to prevent silent email pipeline failures
            throw $e;
        }
    }
}
