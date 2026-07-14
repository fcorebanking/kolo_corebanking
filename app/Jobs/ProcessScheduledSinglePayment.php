<?php
namespace App\Jobs;

use App\Models\ScheduledPayment;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledSinglePayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payment;

    /**
     * Create a new job instance.
     */
    public function __construct(ScheduledPayment $payment)
    {
        $this->payment = $payment;
    }

    public function handle()
    {
        try {
            $payment = $this->payment;
            \logInfo("ProcessScheduledPayments Cron for {$payment->id}");

            loginfo(json_encode($payment), "payment transfer for {$payment->id}");

           
            $transaction = app(TransactionService::class);
            $user        = User::where('id', $payment->user_id)->first();

            $transactionReference = $this->getReference($payment, $user);

            $description          = "Scheduled Transfer";
            $amount               = (float) $payment->amount;
            $transactionReference = $transactionReference;
            $destinationAccount   = $payment->recipient_account;
            $bankCode             = $payment->bank_code;
            $transactionPin       = $payment->transaction_pin;
            $recipientName        = $payment->recipient_name;

            $result = $transaction->transferToBankAccount(
                $description,
                $amount,
                $transactionReference,
                $destinationAccount,
                $bankCode,
                $transactionPin,
                $user,
                $recipientName
            );
            loginfo(json_encode($result), "Process Schedule transfer result ");
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

        } catch (\Throwable $e) {

            \Log::error("ProcessScheduledPayments Cron Failed", [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
                'Line: '     => $e->getLine(),
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

    private function getReference($payment, $user)
    {
        $transaction = app(TransactionService::class);
        $request     = app(Request::class);

        $request->merge([
            'final_amount'     => (float) $payment->amount,
            'transaction_type' => 8,
            'description'      => "Schedule Transfer",

        ]);

        $result = $transaction->initiateTransfer($request, $user);
        loginfo(json_encode($result), "Process get reference result ");
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
