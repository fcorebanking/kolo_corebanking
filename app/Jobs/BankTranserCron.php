<?php
namespace App\Jobs;

use App\Models\transactions;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BankTranserCron
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $transactions = transactions::where(['transaction_remarks_id' => 1, 'transaction_type_id' => 8])->whereDate('created_at', Carbon::today())->orderBy('id', 'ASC')->limit(5)->get();

            if (count($transactions) > 0) {
                foreach ($transactions as $key => $value) {
                    $cronTranferResult = cronJobBankTransfer($value->id);

                    logInfo($value->id, "transfer transaction id");
                    logInfo($cronTranferResult, "cron type");
                }
            }
        } catch (Exception $e) {

            Log::error("BankTranserCron Job has error: " . $e->getMessage());
            throw $e;
        }

    }

}
