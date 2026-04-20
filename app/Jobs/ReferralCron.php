<?php
namespace App\Jobs;

use App\Models\transactions;
use App\Models\User;
use App\Models\wb_account_details;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReferralCron
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
        \logInfo('ReferralCron  job', 'ReferralCron');

        $today = now()->toDateString();

        User::where([
            'user_type_id' => 1,
            'status_id'    => 1,

        ])
            ->whereNotNull('referral_id')
            ->cursor() // generator
            ->each(function ($userData) {

                try {
                    \logInfo('ReferralCron  job', 'ReferralCron');
                    if (isset($userData->company->CustomerReferalSetting->direct_comission)) {

                        $referalsetting                  = $userData->company->CustomerReferalSetting;
                        $referalsetting_direct_comission = $referalsetting->direct_comission;
                        if ($referalsetting_direct_comission > 0) {
                            \logInfo("Message: Referral set for $userData->company_id ", 'Referral Direct commission for ' . $userData->ReferralUpline->company_id);

                            if (isset($userData->ReferralUpline)) {
                                if ($userData->ReferralUpline->company_id != $userData->company_id) {
                                    $authuser            = $userData; // Fetch the user model
                                    $authuser->status_id = 5;         // Update the status
                                    $authuser->save();                // Save the changes

                                    \logInfo('Message: possible fraud transaction ', 'possible fraud transaction for ' . $userData->id);
                                }
                                $wb_account_details            = wb_account_details::where('user_id', $data_User->ReferralUpline->id)->first();
                                $referalPaymentTransactionType = 111; //refPayment
                                $transactions                  = transactions::where(['account_id' => $wb_account_details->id, 'transaction_type_id' => $referalPaymentTransactionType, "created_by" => $userData->id, "initiator" => $userData->id])->count();
                                if ($transactions == 0) {

                                    $ledgerCr                      = null;
                                    $ledgerDr                      = null;
                                    $get_ledger_setting_commission = get_CR_DR_TXN_Type_Ledger($referalPaymentTransactionType, $data_User->company_id);
                                    if ($get_ledger_setting_commission) {
                                        $ledgerCr = $get_ledger_setting->ledger_cr;
                                        $ledgerDr = $get_ledger_setting->ledger_dr;
                                    }

                                    $userWallet    = $wb_account_details->user_wallet;
                                    $balanceBefore = $userWallet->balance;
                                    $balanceAfter  = $balanceBefore + $referalsetting_direct_comission;

                                    $data = [
                                        "balance_before"         => $balanceBefore,
                                        "amount"                 => $referalsetting_direct_comission,
                                        "description"            => "Referal Comission",
                                        "account_id"             => $wb_account_details->id,
                                        "branch_id"              => $data_User->branch_id,
                                        "transaction_status_id"  => 1,
                                        "transaction_reference"  => generate_txnID(),
                                        "transaction_type_id"    => $referalPaymentTransactionType, //debit 7
                                        "credit_debit_type_id"   => 1,
                                        "transaction_remarks_id" => 2,
                                        "balance_after"          => $balanceAfter,
                                        "created_date"           => date('Y-m-d'),
                                        "created_by"             => $userData->id,
                                        "ledger_cr"              => $ledgerCr,
                                        "ledger_dr"              => $ledgerDr,
                                        "initiator"              => $userData->id,
                                    ];
                                    $data['userParamType'] = 2;
                                    $data['userId']        = $wb_account_details->id;
                                    $data['remark']        = 2;
                                    $data['chargeamt']     = 0;
                                    $CreditCustomer        = DebitCreditcustomer($data);
                                    \logInfo('Message: Success comission paid', 'Commisiion: paid for ' . $userData->id);
                                }
                                \logInfo('Message: Failed comission paid', 'Commisiion: already paid for ' . $userData->id);
                            }
                        }
                    }

                } catch (\Throwable $e) {

                    \Log::error('Maintenance cron failed for user ' . $userData->id, [
                        'error' => $e->getMessage(),
                    ]);

                }

            });

        return 0;
    }

}
