<?php

namespace App\Console\Commands;

use App\Models\CustomerOverdraft;
use App\Models\transactions;
use App\Models\user_wallet;
use Carbon\Carbon;
use Illuminate\Console\Command;
ini_set('memory_limit', '500000M');

class OverdraftInterestCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'overdraft:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        \logInfo("Overdraft_interest",  'Cron Job as started');

        $wallets = user_wallet::where('balance', '<', 0)->get();
        if ($wallets) {
            $return = (count($wallets) > 0) ? 'No message sent but user fuond' : 'No user';
            foreach ($wallets as $wallet) {
                $mainBalance = $wallet->balance;
                $originalBalance = $wallet->balance;
                $mainBalance *= -1;

                $user = $wallet->wb_account_details->user;

                $balanceAfter = 0;
                $debited = false;

                $overdraftRecord = CustomerOverdraft::where('user_id', $user->id)->first();

                if ($overdraftRecord) {
                    $start_date = Carbon::parse($overdraftRecord->updated_at);

                    if ($overdraftRecord->frequency_id == 1) {
                        if ($start_date->diffInDays() >= 1) {
                            $interestPercent = $overdraftRecord->interest / 100;

                            $interestAmount = $overdraftRecord->amount * $interestPercent;

                            $balanceAfter = $wallet->balance = $wallet->balance - $interestAmount;
                            $wallet->save();

                            $overdraftRecord->updated_at = Carbon::now();
                            $overdraftRecord->save();

                            $debited = true;
                        }
                    } else if ($overdraftRecord->frequency_id == 2) {
                        if ($start_date->diffInWeeks() >= 1) {
                            $interestPercent = $overdraftRecord->interest / 100;

                            $interestAmount = $overdraftRecord->amount * $interestPercent;

                            $balanceAfter = $wallet->balance = $wallet->balance - $interestAmount;
                            $wallet->save();

                            $overdraftRecord->updated_at = Carbon::now();
                            $overdraftRecord->save();
                            $debited = true;
                        }
                    } else if ($overdraftRecord->frequency_id == 3) {
                        if ($start_date->diffInMonths() >= 1) {
                            $interestPercent = $overdraftRecord->interest / 100;

                            $interestAmount = $overdraftRecord->amount * $interestPercent;

                            $balanceAfter = $wallet->balance = $wallet->balance - $interestAmount;
                            $wallet->save();

                            $overdraftRecord->updated_at = Carbon::now();
                            $overdraftRecord->save();

                            $debited = true;
                        }
                    }

                    $transactionId = generate_txnID();

                    if ($debited) {
                        $ledger_cr =null;
                        $ledger_dr =null;
                        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(60, $user->company_id);
                        if($ledger_Settings){
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $transaction = transactions::create([
                            "balance_before" => $originalBalance,
                            "amount" => $interestAmount,
                            "balance_after" => $balanceAfter,
                            "description" => "Overdraft Interest",
                            "transaction_reference" => $transactionId,
                            "transaction_remarks_id" => 2,
                            "account_id" => $wallet->wb_account_details->id,
                            "transaction_type_id" => 60,
                            "credit_debit_type_id" => 2,
                            "created_date"  => date('Y-m-d'),
                            "created_by" => $user->id,
                            "ledger_dr"=>$ledger_dr,
                            "ledger_cr"=>$ledger_cr
                        ]);
                    }
                }
            }
        }
        return 0;
    }
}
