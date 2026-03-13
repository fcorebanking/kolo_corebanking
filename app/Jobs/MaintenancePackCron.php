<?php
namespace App\Jobs;

use App\Models\BranchWallet;
use App\Models\pendingCustomerSavingMaintenance;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MaintenancePackCron
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
        foreach (
            pendingCustomerSavingMaintenance::where('status', 'pending')->cursor() as $savingPendingData
        ) {

            try {
                echo "*******Cron********";

                $account = $savingPendingData->wb_account;

                if (! $account) {
                    continue;
                }

                $user = $account->user;
                $type = $account->accountType;

                if (! $type || ! $user) {
                    continue;
                }

                $charges  = $type->maintenance_charges;
                $duration = $type->duration;

                if ($charges <= 0) {
                    continue;
                }

                $admin = User::where('company_id', $user->company_id)->first();

                $wallet = user_wallet::where(
                    'wb_account_detail_id',
                    $account->id
                )->first();

                if (! $wallet) {
                    continue;
                }

                DB::transaction(function () use (
                    $savingPendingData,
                    $account,
                    $wallet,
                    $charges,
                    $duration,
                    $user,
                    $admin
                ) {

                    $desc          = "Account Maintenance Charge for " . $previousMonthName;
                    $transactionId = generate_txnID();

                    $today = now()->toDateString();

                    $balanceBefore     = $wallet->balance;
                    $balanceAfter      = $balanceBefore - $charges;
                    $previousMonthName = date('F', strtotime('-1 month'));

                    $transactionId = generate_txnID();

                    $ledger_cr       = null;
                    $ledger_dr       = null;
                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(58, $admin->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }

                    $transaction = transactions::create([
                        'balance_before'         => $balanceBefore,
                        'amount'                 => $charges,
                        'description'            => "Account Maintenance Charge " . $previousMonthName,
                        'account_id'             => $account->id,
                        'branch_id'              => $user->branch_id,
                        'transaction_status_id'  => 1,
                        'transaction_reference'  => $transactionId,
                        'transaction_type_id'    => 58,
                        'credit_debit_type_id'   => 2,
                        'transaction_remarks_id' => 2,
                        'balance_after'          => $balanceAfter,
                        'created_date'           => $today,
                        "created_by"             => $admin->id,
                        "ledger_dr"              => $ledger_dr,
                        "ledger_cr"              => $ledger_cr,
                    ]);

                    $wallet->update([
                        'balance' => $balanceAfter,
                    ]);

                    $BranchWallet           = BranchWallet::where('branch_id', $user->branch_id)->first();
                    $BranchWallet->balance -= $charges;
                    $BranchWallet->save();

                    $account->update([
                        'next_maintenance_date' => now()->addMonth()->startOfMonth()->toDateString(),
                    ]);

                    $savingPendingData->update([
                        'status' => 'paid',
                    ]);

                    fundVault(['type' => 'maintenance_fee', 'value' => $charges, 'company_id' => $admin->company_id, 'created_by' => $admin->id, 'branch_id' => $admin->branch_id, 'transaction_id' => $transaction->id]);
                    sendSMS(['txnid' => $transaction->id, 'userid' => $account->id], "Debit Amt:$charges  Desc: $desc Avail Bal:$balanceAfter Date:$today Ref:$transactionId", false, true, 'Debit Transaction');;

                });

            } catch (\Throwable $e) {

                \Log::error('Maintenance debit failed', [
                    'pending_id' => $savingPendingData->id,
                    'error'      => $e->getMessage(),
                ]);

            }

        }
    }

}
