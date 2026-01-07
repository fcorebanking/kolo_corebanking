<?php
namespace App\Jobs;

use App\Models\pendingCustomerSavingMaintenance;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Maintenance
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

    /*   public function handle()
    {
        \logInfo('Maintenance cron job', 'Maintenance');

        $today = date("Y-m-d");
        try {
            $user = User::where(['user_type_id' => 1, 'status_id' => 1])->get();
            foreach ($user as $userData) {
                $wb_account_detailsData = wb_account_details::where('virtual_accnum', $userData->virtual_account)
                    ->where(function ($query) use ($today) {
                        $query->where('next_maintenance_date', '<', $today)
                            ->orWhereNull('next_maintenance_date');
                    })
                    ->first();
                if ($wb_account_detailsData) {
                    // $wbCurrentID = $wb_account_detailsData->id;
                    if (! isset($wb_account_detailsData->accountType)) {

                        continue;
                    }

                    $wbdataType = $wb_account_detailsData->accountType;
                    if (! isset($wb_account_detailsData->user)) {

                        continue;
                    }

                    $user      = $wb_account_detailsData->user;
                    $createdBy = User::where('company_id', $user->company_id)->first();
                    if (! $createdBy) {continue;}
                    // $frequency =(isset($wbdataType->frequency->frequency))? strtolower($wbdataType->frequency->frequency):'day';
                    $frequency = 'day';

                    $maintenance_charges = $wbdataType->maintenance_charges;
                    if ($maintenance_charges > 0) {
                        $duration = $wbdataType->maintenace_duration;
                        if ($duration <= 0) {

                            continue;
                        }

                        if (isset($wb_account_detailsData->user_wallet)) {

                            $txn     = transactions::where(['account_id' => $wb_account_detailsData->id, 'transaction_type_id' => 58])->orderBy('id', 'desc')->first();
                            $proceed = 0;
                            if ($txn) {
                                $withdraw_diff = date("Y-m-d", strtotime($txn->created_at . "+$duration $frequency"));
                                if ($withdraw_diff < date('Y-m-d')) {
                                    $proceed = 1;
                                }
                            } else {
                                $withdraw_diff = date("Y-m-d", strtotime($user->created_at . "+$duration $frequency"));
                                if ($withdraw_diff < date('Y-m-d')) {
                                    $proceed = 1;
                                }
                            }

                            if ($proceed) {

                                $lastPayment     = null;
                                $ExistingPending = pendingCustomerSavingMaintenance::where('wb_account_id', $wb_account_detailsData->id)->orderBy('id', 'desc')->first();
                                if ($ExistingPending) {
                                    $lastPayment = date("Y-m-d", strtotime($ExistingPending->mark_date . "+$duration $frequency"));
                                    if ($lastPayment < date('Y-m-d')) {

                                        $save                = new pendingCustomerSavingMaintenance;
                                        $save->wb_account_id = $wb_account_detailsData->id;
                                        $save->amount        = $maintenance_charges;
                                        $save->status        = 'pending';
                                        $save->mark_date     = date('Y-m-d');
                                        $save->save();
                                    }
                                } else {
                                    $save                = new pendingCustomerSavingMaintenance;
                                    $save->wb_account_id = $wb_account_detailsData->id;
                                    $save->amount        = $maintenance_charges;
                                    $save->status        = 'pending';
                                    $save->mark_date     = date('Y-m-d');
                                    $save->save();
                                }
                                \logInfo('Message: Success maintenance', 'Line: Success');

                            } else {
                                echo "Exiting \n";
                            }

                        } else {
                            echo "fail wallet-- ** $duration";
                        }
                    } else {
                    }

                }
            }
        } catch (Exception $e) {

            Log::error("Maintenance Cron Job has error: " . $e->getMessage());
            throw $e;
        }
        return 0;

    }*/

    public function handle()
    {
        \logInfo('Maintenance cron job', 'Maintenance');

        $today = date("Y-m-d");

        try {

            foreach ($this->activeUsersStream() as $userData) {

                $wb_account_detailsData = wb_account_details::where('virtual_accnum', $userData->virtual_account)
                    ->where(function ($query) use ($today) {
                        $query->where('next_maintenance_date', '<', $today)
                            ->orWhereNull('next_maintenance_date');
                    })
                    ->first();

                if (! $wb_account_detailsData || ! isset($wb_account_detailsData->accountType) || ! isset($wb_account_detailsData->user)) {
                    continue;
                }

                $wbdataType = $wb_account_detailsData->accountType;
                $user       = $wb_account_detailsData->user;
                $createdBy  = User::where('company_id', $user->company_id)->first();

                if (! $createdBy) {
                    continue;
                }

                $frequency           = 'day';
                $maintenance_charges = $wbdataType->maintenance_charges;

                if ($maintenance_charges <= 0) {
                    continue;
                }

                $duration = $wbdataType->maintenace_duration;

                if ($duration <= 0 || ! isset($wb_account_detailsData->user_wallet)) {
                    continue;
                }

                $txn = transactions::where([
                    'account_id'          => $wb_account_detailsData->id,
                    'transaction_type_id' => 58,
                ])->orderBy('id', 'desc')->first();

                $proceed = 0;

                if ($txn) {
                    $withdraw_diff = date("Y-m-d", strtotime($txn->created_at . "+$duration $frequency"));
                } else {
                    $withdraw_diff = date("Y-m-d", strtotime($user->created_at . "+$duration $frequency"));
                }

                if ($withdraw_diff < date('Y-m-d')) {
                    $proceed = 1;
                }

                if (! $proceed) {
                    continue;
                }

                $ExistingPending = pendingCustomerSavingMaintenance::where('wb_account_id', $wb_account_detailsData->id)
                    ->orderBy('id', 'desc')
                    ->first();

                $shouldCreate = true;

                if ($ExistingPending) {
                    $lastPayment = date("Y-m-d", strtotime($ExistingPending->mark_date . "+$duration $frequency"));
                    if ($lastPayment >= date('Y-m-d')) {
                        $shouldCreate = false;
                    }
                }

                if ($shouldCreate) {
                    pendingCustomerSavingMaintenance::create([
                        'wb_account_id' => $wb_account_detailsData->id,
                        'amount'        => $maintenance_charges,
                        'status'        => 'pending',
                        'mark_date'     => date('Y-m-d'),
                    ]);
                }

                \logInfo('Message: Success maintenance', 'Line: Success');
            }

        } catch (\Exception $e) {
            \Log::error("Maintenance Cron Job has error: " . $e->getMessage());
            throw $e;
        }

        return 0;
    }

/**
 * Stream users using a generator
 */
    private function activeUsersStream(): \Generator
    {
        $query = User::where('user_type_id', 1)
            ->where('status_id', 1)
            ->select('id', 'virtual_account', 'company_id'); // fetch only fields needed

        foreach ($query->cursor() as $user) {
            yield $user;
        }
    }

}
