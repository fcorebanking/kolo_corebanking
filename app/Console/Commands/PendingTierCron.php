<?php

namespace App\Console\Commands;

use App\Http\Controllers\ThirdParty\FairMoneyController;
use App\Models\TempWebhook;
use App\Models\transactions;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Illuminate\Console\Command;

ini_set('memory_limit', '500000M');

class PendingTierCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pending_tier:cron';

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

        logInfo("Cron Job", "Tier Cron job running");
        $pendingTiers = TempWebhook::where(['status_id' => 7])->get();

        if (count($pendingTiers) > 0) {
            foreach ($pendingTiers as $key => $value) {

                $wbAccountDetails = wb_account_details::where('virtual_accnum', $value->account_number)->first();

                if(!$wbAccountDetails){
                    logInfo("User Details not found for $value->account_number", "Pending Tier Status");
                    break;
                }

                $currentUser = $wbAccountDetails->user;

                $acocuntTier = $currentUser->tier;

                if ($acocuntTier->max_amount_received >= $value->amount) {

                    $request = json_decode($value->body, true);

                    $value->status_id = 3;
                    $value->save();

                    $fairmoneyController = new FairMoneyController();

                    $fairmoneyController->transactionReference = $value->unique_reference;
                    $fairmoneyController->wbaCharge = $value->charge;
                    $fairmoneyController->virtualAccount = $value->account_number;
                    $fairmoneyController->amount = $value->amount;
                    $fairmoneyController->webhookType = $value->webhook_type;
                    $fairmoneyController->description = $value->description;
                    $fairmoneyController->transactionDate = $value->transaction_date;
                    $fairmoneyController->tempWebookRequest = $request;
                    $fairmoneyController->fromTempWebhook = 'true';

                    $handleWebhook = $fairmoneyController->handleWebhook();

                    logInfo($handleWebhook);
                }
            }
        }

        return Command::SUCCESS;
    }
}
