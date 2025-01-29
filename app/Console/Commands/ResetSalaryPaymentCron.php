<?php

namespace App\Console\Commands;

use App\Http\Controllers\ThirdParty\KudaController;
use App\Models\banks;
use App\Models\StaffPayRoll;
use App\Models\StaffPayRollLog;
use App\Models\transactions;
use App\Models\User;
use App\Models\wb_account_details;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
ini_set('memory_limit', '500000M');

class ResetSalaryPaymentCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset_salary_payment:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron job is basically to reset Salary payroll table to 0... So that paid staff can receive their salaries the next month';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        \logInfo("Reset Salary cron Job is Working currently", "Cron Job Started");

        $payrollStaff = StaffPayRoll::where(['payment_mode' => 0, 'salary_payment_status' => 1])->get();

        foreach ($payrollStaff as $staffInfo) {

            $startingDate = Carbon::now();
            $transactionDate = Carbon::parse($staffInfo->updated_at)->addDays(3);
            if ($startingDate->gt($transactionDate)) {
                $staffInfo->salary_payment_status = 0;
                $staffInfo->save();
            }
        }

        return 0;
    }
}
