<?php
namespace App\Jobs;

use App\Models\ScheduledPayment;
use App\Models\User;
use App\Services\TransactionService;
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

                $transaction = app(TransactionService::class);
                $user        = User::where('id', $payment->user_id)->first();

                $transactionReference = $this->getReference($payment, $user);
                $request              = app(Request::class);

                $request->merge([
                    'id'                    => $payment->user_id,
                    'final_amount'          => (float) $payment->amount,
                    'transaction_reference' => $transactionReference,
                    'destination_account'   => $payment->recipient_account,
                    'receipient_name '      => $payment->recipient_name,
                    'bank_code'             => $payment->bank_code,
                    'transaction_pin'       => $payment->transaction_pin,

                ]);

                $result = $transaction->transferToBankAccount($request, $user);

                $data = $result->getData(true);
                loginfo(json_encode($data), "Response Schedule transfer"); // true = associative array

// Now you can access status

                if ($data['status']) {

                    $payment->update([
                        'status'      => 'completed',
                        'executed_at' => now(),
                    ]);

                    //    $payment->delete();

                } else {
                    loginfo(json_encode($data), "Response Schedule transfer"); // true = associative array
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

    private function getReference($payment, $user)
    {
        $transaction = app(TransactionService::class);
        $request     = app(Request::class);

        $request->merge([
            'final_amount'     => (float) $payment->amount,
            'transaction_type' => 8,
            'description'      => "Schedule Transfer",

        ]);

        $result = $transaction->initiateTransaction($request, $user);

        $data = $result->getData(true);
        loginfo(json_encode($data), "Get Reference Schedule transfer"); // true = associative array

        if ($data['status']) {

            return $data['data']['transaction_reference'] ?? null;

        } else {
            loginfo(json_encode($data), "Get Reference Schedule transfer");
        }
// Now you can access status

    }
}
