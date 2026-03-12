<?php
namespace App\Console\Commands;

use App\Models\BranchWallet;
use App\Models\pendingCustomerSavingMaintenance;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

ini_set('memory_limit', '500000M');

class maintenancePackCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pendingmaintenance:charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    /* public function handle()
    {
        $savingPending = pendingCustomerSavingMaintenance::where('status', 'pending')->get();
        if ($savingPending) {
            foreach ($savingPending as $savingPendingData) {
                try {
                    echo "*******Cron********";
                    $wb_account_detailsData = $savingPendingData->wb_account;
                    $today                  = date('Y-m-d');
                    $wbCurrentID            = $wb_account_detailsData->id;
                    $wbdataType             = $wb_account_detailsData->accountType;
                    $user                   = $wb_account_detailsData->user;
                    // $frequency = strtolower($wbdataType->frequency->frequency);
                    $maintenance_charges = $wbdataType->maintenance_charges;
                    $duration            = $wbdataType->duration;

                    $createdBy = User::where('company_id', $user->company_id)->first();


                    if ($maintenance_charges == 0) {
                        continue;
                    }
                    $current_acc = user_wallet::where('wb_account_detail_id', $wb_account_detailsData->id)->first();
                    if ($current_acc) {
                        $balanceBefore     = $current_acc->balance;
                        $userbalance       = $current_acc->balance -= $maintenance_charges;
                        $previousMonthName = date('F', strtotime('-1 month'));
                        $desc              = "Account Maintenance Charge for " . $previousMonthName;
                        $transactionId     = generate_txnID();
                        $ledger_cr         = null;
                        $ledger_dr         = null;
                        $ledger_Settings   = get_CR_DR_TXN_Type_Ledger(58, $createdBy->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $debitdata = [
                            "balance_before"         => $balanceBefore,
                            "amount"                 => $maintenance_charges,
                            "description"            => $desc,
                            "account_id"             => $wb_account_detailsData->id,
                            "branch_id"              => $user->branch_id,
                            "transaction_status_id"  => 1,
                            "transaction_reference"  => $transactionId,
                            "transaction_type_id"    => 58, //debit 7
                            "credit_debit_type_id"   => 2,  //debit 2
                            "transaction_remarks_id" => 2,  //success 2
                            "balance_after"          => $userbalance,
                            "created_date"           => $today,
                            "created_by"             => $createdBy->id,
                            "ledger_dr"              => $ledger_dr,
                            "ledger_cr"              => $ledger_cr,
                        ];
                        $transactionDebit     = transactions::create($debitdata);
                        $current_acc->balance = $userbalance;
                        $current_acc->save();
                        $BranchWallet = BranchWallet::where('branch_id', $user->branch_id)->first();
                        $BranchWallet->balance -= $maintenance_charges;
                        $BranchWallet->save();
                        $wpData             = wb_account_details::where('id', $wbCurrentID)->first();
                        $nextpayment        = date("Y-m-d", strtotime($today . "+$duration day"));
                        $wpData->updated_at = $nextpayment;
                        $wpData->save();
                        $savingPendingData->status = 'paid';
                        $savingPendingData->save();
                        fundVault(['type' => 'maintenance_fee', 'value' => $maintenance_charges, 'company_id' => $createdBy->company_id, 'created_by' => $createdBy->id, 'branch_id' => $createdBy->branch_id, 'transaction_id' => $transactionDebit->id]);
                        sendSMS(['txnid' => $transactionDebit->id, 'userid' => $wb_account_detailsData->id], "Debit Amt:$maintenance_charges  Desc: $desc Avail Bal:$userbalance Date:$today Ref:$transactionId", false, true, 'Debit Transaction');
                    }
                } catch (Exception $e) {
                    \logInfo('Message: ' . $e->getMessage(), 'Line: ' . $e->getLine());
                }
            }
        }
        return Command::SUCCESS;
    }*/

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
                        'next_maintenance_date' => now()
                            ->addDays($duration)
                            ->toDateString(),
                    ]);

                    $savingPendingData->update([
                        'status' => 'paid',
                    ]);

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
