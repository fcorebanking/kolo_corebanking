<?php

namespace App\Console\Commands;

use App\Models\transactions;
use Carbon\Carbon;
use Illuminate\Console\Command;

ini_set('memory_limit', '500000M');

class BankTranserCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank_tranfer:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This cron job is to complete user's bank transfer request";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $transactions = transactions::where(['transaction_remarks_id' => 1, 'transaction_type_id' => 8])->whereDate('created_at',  Carbon::today())->orderBy('id', 'ASC')->limit(5)->get();

        if (count($transactions) > 0) {
            foreach ($transactions as $key => $value) {
                $cronTranferResult =  cronJobBankTransfer($value->id);

                logInfo($value->id, "transfer transaction id");
                logInfo($cronTranferResult, "cron type");
            }
        }

        return Command::SUCCESS;
    }
}
