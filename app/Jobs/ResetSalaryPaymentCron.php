<?php
namespace App\Jobs;

use App\Models\StaffPayRoll;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetSalaryPaymentCron
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
            \logInfo("Reset Salary cron Job is Working currently", "Cron Job Started");

            $payrollStaff = StaffPayRoll::where(['payment_mode' => 0, 'salary_payment_status' => 1])->get();

            foreach ($payrollStaff as $staffInfo) {

                $startingDate    = Carbon::now();
                $transactionDate = Carbon::parse($staffInfo->updated_at)->addDays(3);
                if ($startingDate->gt($transactionDate)) {
                    $staffInfo->salary_payment_status = 0;
                    $staffInfo->save();
                }
            }
        } catch (Exception $e) {
            DB::rollback();

            Log::error("ResetSalaryPaymentCron Job has error: " . $e->getMessage());
            throw $e;
        }

    }

}
