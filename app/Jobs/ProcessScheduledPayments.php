<?php
namespace App\Jobs;

use App\Jobs\ProcessScheduledSinglePayment;
use App\Models\ScheduledPayment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
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
        \logInfo("Dispatch ProcessScheduledPayments Cron");

        foreach ($this->schedulePaymentStream() as $payment) {

            loginfo(json_encode($payment), "Dispatch payment transfer {$payment->id}");
            $delay = now()->addSeconds(($index + 1) * 120);
            ProcessScheduledSinglePayment::dispatch($payment)->delay($delay);

        }

    }

    private function schedulePaymentStream(): \Generator
    {
        $now = Carbon::now();

        /*  $paymentscron = ScheduledPayment::where('status', 'pending')
            ->where(function ($query) use ($now) {
                $query->whereDate('scheduled_date', '<', $now->toDateString())
                    ->orWhere(function ($q) use ($now) {
                        $q->whereDate('scheduled_date', '=', $now->toDateString())
                            ->whereTime('scheduled_time', '<=', $now->format('H:i:s'));
                    });
            })
            ->get();
        loginfo(json_encode($paymentscron), "paymentscron");*/
        foreach (
            $payments = ScheduledPayment::where('status', 'pending')
            ->where(function ($query) use ($now) {
                $query->whereDate('scheduled_date', '<', $now->toDateString())
                ->orWhere(function ($q) use ($now) {
                    $q->whereDate('scheduled_date', '=', $now->toDateString())
                    ->whereTime('scheduled_time', '<=', $now->format('H:i:s'));
                });
            })
            ->cursor() as $payment
        ) {
            yield $payment;
        }
    }

}
