<?php
namespace App\Jobs;

use App\Http\Controllers\ThirdParty\FairMoneyController;
use App\Models\TempWebhook;
use App\Models\wb_account_details;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PendingTierCron
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

        logInfo("Cron Job", "Tier Cron job running");
        try {
            $pendingTiers = TempWebhook::where(['status_id' => 7])->get();

            if (count($pendingTiers) > 0) {
                foreach ($pendingTiers as $key => $value) {

                    $wbAccountDetails = wb_account_details::where('virtual_accnum', $value->account_number)->first();

                    if (! $wbAccountDetails) {
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
                        $fairmoneyController->wbaCharge            = $value->charge;
                        $fairmoneyController->virtualAccount       = $value->account_number;
                        $fairmoneyController->amount               = $value->amount;
                        $fairmoneyController->webhookType          = $value->webhook_type;
                        $fairmoneyController->description          = $value->description;
                        $fairmoneyController->transactionDate      = $value->transaction_date;
                        $fairmoneyController->tempWebookRequest    = $request;
                        $fairmoneyController->fromTempWebhook      = 'true';

                        $handleWebhook = $fairmoneyController->handleWebhook();

                        logInfo($handleWebhook);
                    }
                }
            }
        } catch (Exception $e) {
            //throw $th;
            Log::error("PendingTier Cron Job has error: " . $e->getMessage());
            throw $e;
        }

    }

}
