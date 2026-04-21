<?php
namespace App\Jobs;

use App\Models\BranchWallet;
use App\Models\loan;
use App\Models\LoanRepayment;
use App\Models\payLoanPenalty;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

class LoanCron
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
    /*  public function handle()
    {
        try {
            \logInfo("Loan Cron");
            $infoData = loan::where(['loan_status_id' => 1, 'auto_pay' => 1])->where('user_id', '!=', 0)->get();
            foreach ($infoData as $info) {
                $EncloanID = Crypt::encryptString($info->id);
                // $penaltyChargeMethod = $info->LoanProduct->penaltyChargeMethod;
                $repayment = json_decode(app('App\Http\Controllers\LoanController')->loanRepaymentBreakDown($EncloanID, new Request));
                $dateNow   = date("Y-m-d");

                if (isset($repayment) && $repayment != null) {
                    if ($repayment->missed <= 0) {
                        foreach ($repayment->repaymentTable as $repaymentData) {
                            if (isset($repaymentData->repaymentDateDueloan)) {
                                if ($repaymentData->currentRepayStatus == 2) {
                                    if ($repaymentData->repaymentDateDueloan == $dateNow) {
                                        if ($info->auto_pay == 1) {
                                            $amtToPay = $repaymentData->EachRepayment;
                                            $this->loanpayer($info, $amtToPay);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $amtToPay = $repayment->missed;
                        $this->penaltyCharge($repayment, $info, $amtToPay);
                        $this->loanpayer($info, $amtToPay);
                    }
                }
            }
        } catch (Exception $e) {

            Log::error("LoanCron Job has error: " . $e->getMessage());
            throw $e;
        }

    }*/

    public function handle()
    {
        try {

            \logInfo("Loan Cron");

            foreach ($this->loanStream() as $loan) {

                $encLoanId = Crypt::encryptString($loan->id);
                $repayment = json_decode(
                    app('App\Http\Controllers\LoanController')
                        ->loanRepaymentBreakDown($encLoanId, new Request)
                );

                if (! $repayment) {
                    continue;
                }

                $today = now()->toDateString();

                \logInfo(json_encode($repayment), "Loan Repayment for $loan->id");

                // No missed repayment
                if ($repayment->missed <= 0) {

                    foreach ($this->repaymentTableStream($repayment) as $row) {
                        /* if (isset($row->repaymentDateDueloan)) {
                            if (
                                $row->currentRepayStatus == 2 &&
                                $row->repaymentDateDueloan == $today &&
                                $loan->auto_pay == 1
                            ) {
                                $this->loanpayer($loan, $row->EachRepayment);
                            }
                        }*/

                        $status = is_array($row)
                            ? ($row['currentRepayStatus'] ?? null)
                            : ($row->currentRepayStatus ?? null);

                        $dueDate = is_array($row)
                            ? ($row['repaymentDateDueloan'] ?? null)
                            : ($row->repaymentDateDueloan ?? null);

                        $amount = is_array($row)
                            ? ($row['EachRepayment'] ?? null)
                            : ($row->EachRepayment ?? null);

                        if ($status == 2 && $dueDate == $today) {

                            $this->loanpayer($loan, $amount);

                        }

                    }

                } else {
                    // Missed repayment flow
                    $amtToPay = $repayment->missed;
                    $this->penaltyCharge($repayment, $loan, $amtToPay);
                    $this->loanpayer($loan, $amtToPay);
                }
            }

        } catch (\Throwable $e) {

            \Log::error("Loan Cron Failed", [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
                'Line: '  => $e->getLine(),
            ]);
        }
    }

    private function loanStream(): \Generator
    {
        foreach (
            loan::where([
                'loan_status_id' => 1,
                'auto_pay'       => 1,
            ])
            ->where('user_id', '!=', 0)
            ->cursor() as $loan
        ) {
            yield $loan;
        }
    }

    private function repaymentTableStream($repayment): \Generator
    {

        if (! isset($repayment->repaymentTable)) {
            return;
        }

        foreach ($repayment->repaymentTable as $row) {
            yield $row;
        }
    }

    public function loanpayer($info, $amtToPay)
    {
        $user = User::where(['id' => $info->user_id])->first();
        if (! $user) {
            return false;
        }
        $wb_account_details = wb_account_details::where('virtual_accnum', $user->virtual_account)->first();
        if (! $wb_account_details) {
            return false;
        }

        \logInfo(json_encode($info), "loanpayer");
        $wbbal = $wb_account_details->user_wallet->balance;
        \logInfo(json_encode($wbbal), "wbbal");
        if ($wbbal > 0) {
            $account_typeid = $wb_account_details->id;
            $balanceBefore  = $wb_account_details->user_wallet->balance;
            if ($amtToPay > $balanceBefore) {
                $amtToPay = $balanceBefore;
            }
            $balance   = $balanceBefore - $amtToPay;
            $branch_id = $user->branch_id;
            $backdate  = $paymentDate  = date("Y-m-d");
            $wallet    = user_wallet::where('id', $wb_account_details->user_wallet->id)->update(['balance' => $balance]);
            \logInfo(json_encode($amtToPay), "amtToPay");
            if ($amtToPay > 0) {
                if ($wallet) {
                    $BranchWallet           = BranchWallet::where('branch_id', $branch_id)->first();
                    $BranchWallet->balance -= $amtToPay;
                    $BranchWallet->save();
                    $auth                       = User::where('company_id', $info->user->company_id)->first();
                    $AmtInterest                = ($info->rate / 100) * $amtToPay;
                    $principal4rAmount          = $amtToPay - $AmtInterest;
                    $loancharges                = app('App\Http\Controllers\LoanController')->LoanMethodPercentReturn($principal4rAmount, $AmtInterest, $info->LoanFee, $info->LoanFeeMethod, $info->mrgtFee, $info->mrgtFeeMethod);
                    $loanPaymentStateData       = app('App\Http\Controllers\LoanController')->getLoanCurrentRepayment($info, $amtToPay);
                    $principalLeft              = $loanPaymentStateData['Pleft'];
                    $interestLeft               = $loanPaymentStateData['Ileft'];
                    $loanBalance                = $info->loanBalance - $amtToPay;
                    $loanFee                    = $loancharges['loanFee'];
                    $mrgFee                     = $loancharges['mrgFee'];
                    $loanPayment                = new LoanRepayment;
                    $loanPayment->loan_id       = $info->id;
                    $loanPayment->paymentDate   = $paymentDate;
                    $loanPayment->amount        = $amtToPay;
                    $loanPayment->branch_id     = $branch_id;
                    $loanPayment->mgrtFee       = $mrgFee;
                    $loanPayment->loanFee       = $loanFee;
                    $loanPayment->P             = $loanPaymentStateData['paymentP'];
                    $loanPayment->I             = $loanPaymentStateData['paymentI'];
                    $loanPayment->remark        = $loanPaymentStateData['message'];
                    $loanPayment->payment_ref   = str_shuffle(time());
                    $loanPayment->method        = 2;
                    $loanPayment->balance       = $loanBalance;
                    $loanPayment->initiator_id  = $auth->id;
                    $loanPayment->created_by    = $auth->id;
                    $loanPayment->save();
                    if ($loanPayment) {
                        $loanTXN = transactions::create([
                            "balance_before"         => $balanceBefore,
                            "amount"                 => $amtToPay,
                            "description"            => "Loan auto repayment of $amtToPay from wallet",
                            "account_id"             => $account_typeid,
                            "branch_id"              => $branch_id,
                            "transaction_status_id"  => 1,
                            "transaction_reference"  => generate_txnID(),
                            "transaction_type_id"    => 56, //Loan repayment 56
                            "credit_debit_type_id"   => 2,  //debit 2
                            "transaction_remarks_id" => 2,  //success 2
                            "balance_after"          => $balance,
                            "created_date"           => $backdate,
                            "created_by"             => $auth->id,
                            "loan_repayment_id"      => $loanPayment->id,
                        ]);
                        $loan_status_id = $info->loan_status_id;
                        if (($principalLeft + $interestLeft) <= 0) {
                            $loan_status_id = 10;
                        }
                        loan::where(['id' => $info->id])->update(['principalLeft' => $principalLeft, 'InterestLeft' => $interestLeft, 'loanBalance' => ($principalLeft + $interestLeft), 'loan_status_id' => $loan_status_id]);
                        $info->loanBalance = $principalLeft + $interestLeft;
                        $appMSG            = "Your repayment of loan of $amtToPay via (Auto-Debit is acknowledged and your current loan balance is $info->loanBalance";
                        sendSMS($info->user_id, "$appMSG", true, true, 'Loan Repayment');
                    }
                }
            }
        }
    }
    public function penaltyCharge($repayment, $info, $amtToPay)
    {
        try {
            $penaltyMethod = 10; //$info->LoanProduct->penaltyChargeMethod;
            $principal     = $info->principal;
            $missedFee     = $info->missedFee;
            $interest      = ($info->rate / 100) * $principal;
            $penaltycharge = 0;
            if ($penaltyMethod == 1) {
                $penaltycharge = ($missedFee / 100) * $principal;
            } else if ($penaltyMethod == 2) {
                $penaltycharge = ($missedFee / 100) * $interest;
            } else if ($penaltyMethod == 3) {
                $penaltycharge = ($missedFee / 100) * ($interest + $principal);
            } else if ($penaltyMethod == 4) {
                $penaltycharge = ($missedFee / 100) * $amtToPay;
            } else if ($penaltyMethod == 5) {
                $penaltycharge = ($missedFee / 100) * $info->principalLeft;
            } else if ($penaltyMethod == 6) {
                $penaltycharge = ($missedFee / 100) * $info->InterestLeft;
            } else if ($penaltyMethod == 7) {
                $penaltycharge = ($missedFee / 100) * $info->loanBalance;
            } else if ($penaltyMethod == 8) {
                //missed principal
                $interest      = ($info->rate / 100) * $repayment->missed;
                $penaltycharge = ($missedFee / 100) * ($repayment->missed - $interest);
            } else if ($penaltyMethod == 9) {
                //missed Interest
                $interest      = ($info->rate / 100) * $repayment->missed;
                $penaltycharge = ($missedFee / 100) * $interest;
            } else if ($penaltyMethod == 10) {
                $penaltycharge = ($missedFee / 100) * $repayment->missed;
            }
            $today          = date('Y-m-d');
            $lastmissed     = $repayment->lastmissed;
            $payLoanPenalty = payLoanPenalty::where(['loan_id' => $info->id])
                ->whereDate('logDate', date('Y-m-d'))
                ->count();
            // echo $today;
            loan::where('id', $info->id)->update(['missedLoan' => $amtToPay, 'missedLoanLeft' => $penaltycharge]);

            if ($payLoanPenalty == 0 && $penaltycharge > 0) {
                $payLoanPenalty           = new payLoanPenalty;
                $payLoanPenalty->lastDate = $lastmissed;
                $payLoanPenalty->amount   = $penaltycharge;
                $payLoanPenalty->loan_id  = $info->id;
                $payLoanPenalty->logDate  = $today;
                $payLoanPenalty->status   = 'pending';
                $payLoanPenalty->save();
                $user                  = User::where(['id' => $info->user_id])->first();
                $usewb_account_details = null;
                \logInfo(json_encode($user->wb_account_details), "wb_account_details");
                foreach ($user->wb_account_details as $wb_account_details) {
                    if ($penaltycharge <= $wb_account_details->user_wallet->balance) {
                        $usewb_account_details = $wb_account_details;
                        break;
                    }
                }
                if ($usewb_account_details != null) {
                    $balanceBefore          = $usewb_account_details->user_wallet->balance;
                    $account_typeid         = $usewb_account_details->id;
                    $balance                = $balanceBefore - $penaltycharge;
                    $auth                   = User::where('company_id', $info->user->company_id)->first();
                    $wallet                 = user_wallet::where('id', $usewb_account_details->user_wallet->id)->update(['balance' => $balance]);
                    $BranchWallet           = BranchWallet::where('branch_id', $user->branch_id)->first();
                    $BranchWallet->balance -= $penaltycharge;
                    $BranchWallet->save();
                    $ledger_cr        = null;
                    $ledger_dr        = null;
                    $ledger_Settings  = get_CR_DR_TXN_Type_Ledger(62, $auth->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    transactions::create([
                        "balance_before"         => $balanceBefore,
                        "amount"                 => $penaltycharge,
                        "description"            => "Loan Penalty debit $penaltycharge from wallet",
                        "account_id"             => $account_typeid,
                        "branch_id"              => $user->branch_id,
                        "transaction_status_id"  => 1,
                        "transaction_reference"  => generate_txnID(),
                        "transaction_type_id"    => 62, //Loan repayment penlty
                        "credit_debit_type_id"   => 2,  //debit 2
                        "transaction_remarks_id" => 2,  //success 2
                        "balance_after"          => $balance,
                        "created_date"           => date("Y-m-d"),
                        "created_by"             => $auth->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);
                    $payLoanPenalty->status = 'paid';
                    $payLoanPenalty->save();
                    \logInfo($info, 'penalty logged for ');
                } else {
                    \logInfo('penalty error ', "usewb_account_details is null at Userid: " . $info->user_id);
                }
            }
        } catch (\Exception $e) {
            \logInfo(['loaninfo' => $info, "error" => 'Message: ' . $e->getMessage() . 'Line: ' . $e->getLine()], 'error logg');
        }
    }

}
