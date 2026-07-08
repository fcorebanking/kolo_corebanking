<?php
namespace App\Jobs;

use App\Http\Controllers\api\v1\TransactionController;
use App\Models\ScheduledPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledPayments
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    public function handle()
    {
        try {

            \logInfo("ProcessScheduledPayments Cron");

            foreach ($this->schedulePaymentStream() as $payment) {

                $payment->update([
                    'status' => 'processing',
                ]);

                $transaction = app(TransactionController::class);

                $transactionReference = $this->getReference();
                $request              = app(Request::class);

                $request->id                    = $payment->user_id;
                $request->final_amount          = $payment->amount;
                $request->transaction_reference =
                $request->destination_account   = $payment->recipient_account;
                $request->receipient_name       = $payment->recipient_name;
                $request->bank_code             = $payment->bank_code;
                $request->transaction_pin       = $payment->transaction_pin;
                $request->transaction_reference = $transactionReference;

                $result = $transaction->transferToBankAccount($request);

                $data = $result->getData(true);
                loginfo(json_encode($data), "Response Schedule transfer"); // true = associative array

// Now you can access status
                $status = $data['status'] ?? null;

                if ($status && $status != null) {

                    $payment->update([
                        'status'      => 'completed',
                        'executed_at' => now(),
                    ]);

                    //    $payment->delete();

                }
            }

        } catch (\Throwable $e) {

            \Log::error("ProcessScheduledPayments Cron Failed", [
                'loan_id' => $payment->id,
                'error'   => $e->getMessage(),
                'Line: '  => $e->getLine(),
            ]);

            $payment->increment('attempts');

            if ($payment->attempts >= 1) { //Only 1 attempt is allowed for scheduled payments. If it fails, mark it as failed and do not retry.

                $payment->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);

            } else {

                $payment->update([
                    'status'         => 'pending',
                    'failure_reason' => $e->getMessage(),
                ]);

            }
        }
    }

    private function schedulePaymentStream(): \Generator
    {
        foreach (
            $payments = ScheduledPayment::where('status', 'pending')
            ->whereDate('scheduled_date', '<=', today())
            ->whereTime('scheduled_time', '<=', now()->format('H:i:s'))
            ->cursor() as $payment
        ) {
            yield $payment;
        }
    }

    private function getReference()
    {
        $transaction = app(TransactionController::class);
        $request     = app(Request::class);

        $request->final_amount     = $payment->amount;
        $request->transaction_type = "8";
        $request->description      = "Schedule Transfer";

        $result = $transaction->initiateTransaction($request);

        $data = $result->getData(true);
        loginfo(json_encode($data), "Get Reference Schedule transfer"); // true = associative array

// Now you can access status
        return $data['data']['transaction_reference'] ?? null;
    }
}
