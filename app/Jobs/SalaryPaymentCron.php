<?php
namespace App\Jobs;

use App\Models\banks;
use App\Models\PreferredEbankingPartner;
use App\Models\StaffPayRoll;
use App\Models\StaffPayRollLog;
use App\Models\transactions;
use App\Models\wb_account_details;
use App\Services\ebanking\BankTransferType;
use App\Services\ebanking\EbankingService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SalaryPaymentCron
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
        try {
            \logInfo("Salary cron Job is Working currently", "Cron Job Started");

            $numberOfDaysInMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));

            $payrollStaff = StaffPayRoll::where(['payment_mode' => 0, 'salary_payment_status' => 0])->get();

            foreach ($payrollStaff as $staffInfo) {

                $selectedDate = 0;

                if ($staffInfo->day_of_payment != "") {

                    $selectedDate = (int) $staffInfo->day_of_payment;

                    $description = date("F") . " Salary payment from " . $staffInfo->staff->company->company_name . " paid to " . $staffInfo->staff->first_name . " " . $staffInfo->staff->last_name;

                    if ($selectedDate >= $numberOfDaysInMonth) {
                        $selectedDate = $numberOfDaysInMonth;
                    }

                    if ($selectedDate == date("d")) {

                        $transactionId = generate_txnID();

                        $payrollLog = StaffPayRollLog::create([
                            "transaction_reference"    => $transactionId,
                            "staff_id"                 => $staffInfo->staff_id,
                            "basic_salary"             => $staffInfo->basic_salary,
                            "date_of_payment"          => $staffInfo->day_of_payment,
                            "house_allowance"          => $staffInfo->house_allowance,
                            "medical_allowance"        => $staffInfo->medical_allowance,
                            "special_allowance"        => $staffInfo->special_allowance,
                            "overtime_allowance"       => $staffInfo->overtime_allowance,
                            "transport_allowance"      => $staffInfo->transport_allowance,
                            "paid_leave"               => $staffInfo->paid_leave,
                            "pension_deduction"        => $staffInfo->pension_deduction,
                            "provident_fund_allowance" => $staffInfo->provident_fund_allowance,
                            "unpaid_leave_deduction"   => $staffInfo->unpaid_leave_deduction,
                            "loan_deduction"           => $staffInfo->loan_deduction,
                            "other_allowances"         => $staffInfo->other_allowances,
                            "tax_deductions"           => $staffInfo->tax_deductions,
                            "provident_fund_deduction" => $staffInfo->provident_fund_deduction,
                            "other_deductions"         => $staffInfo->other_deductions,
                            "gross_salary"             => $staffInfo->gross_salary,
                            "total_deduction"          => $staffInfo->total_deduction,
                            "bank_id"                  => $staffInfo->bank_id,
                            "account_number"           => $staffInfo->payment_method == 1 ? $staffInfo->account_number : $staffInfo->wallet_account_number,
                            "account_name"             => $staffInfo->account_name,
                            "payment_mode"             => $staffInfo->payment_mode,
                            "net_salary"               => $staffInfo->net_salary,
                            "status_id"                => $staffInfo->payment_method != 1 ? 2 : 7,
                        ]);

                        if ($staffInfo->payment_method == 1) {
                            $bank = banks::find($staffInfo->bank_id);

                            $myAccountName = [];

                            $myAccountName = explode(" ", $staffInfo->account_name);

                            $firstName = $myAccountName[0];

                            $debitCompany    = \debitCompanyWithCharge($staffInfo->staff->company->id, $staffInfo->net_salary, 30, $staffInfo->staff);
                            $ledger_cr       = null;
                            $ledger_dr       = null;
                            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(85, $staffInfo->staff->company_id);
                            if ($ledger_Settings) {
                                $ledger_cr = $ledger_Settings->ledger_cr;
                                $ledger_dr = $ledger_Settings->ledger_dr;
                            }
                            $transaction = transactions::create([
                                "balance_before"         => 0,
                                "amount"                 => 0,
                                "description"            => $description,
                                "company_id"             => $staffInfo->staff->company_id,
                                "transaction_status_id"  => 1,
                                "transaction_reference"  => $transactionId,
                                "transaction_type_id"    => 85, //debit 7
                                "credit_debit_type_id"   => 1,  //debit 2
                                "transaction_remarks_id" => 1,  //success 2
                                "balance_after"          => 0,
                                "company_balance_before" => $debitCompany["balance_before"],
                                "company_amount"         => $staffInfo->net_salary,
                                "company_balance_after"  => $debitCompany["balance_after"],
                                "created_date"           => Carbon::now(),
                                "created_by"             => $staffInfo->staff_id,
                                "ledger_cr"              => $ledger_cr,
                                "ledger_dr"              => $ledger_dr,
                            ]);

                            $payrollLog->transaction_id = $transaction->id;
                            $payrollLog->save();
                            $ledger_cr       = null;
                            $ledger_dr       = null;
                            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $staffInfo->staff->company_id);
                            if ($ledger_Settings) {
                                $ledger_cr = $ledger_Settings->ledger_cr;
                                $ledger_dr = $ledger_Settings->ledger_dr;
                            }
                            $chargeTransaction = transactions::create([
                                "balance_before"         => 0,
                                "amount"                 => 0,
                                "description"            => "Charge for Salary Payment Bank transfer - $transactionId",
                                "company_id"             => $staffInfo->staff->company_id,
                                "transaction_status_id"  => 1,
                                "transaction_reference"  => generate_txnID(),
                                "transaction_type_id"    => 27, //debit 7
                                "credit_debit_type_id"   => 1,  //debit 2
                                "transaction_remarks_id" => 2,  //success 2
                                "balance_after"          => 0,
                                "company_balance_before" => $debitCompany["charge_balance_before"],
                                "company_amount"         => 30,
                                "company_balance_after"  => $debitCompany["charge_balance_before"],
                                "created_date"           => Carbon::now(),
                                "created_by"             => $staffInfo->staff_id,
                                "ledger_cr"              => $ledger_cr,
                                "ledger_dr"              => $ledger_dr,
                            ]);

                            $preferredEbankingPartner = PreferredEbankingPartner::all();

                            $ebankingService                        = new EbankingService($preferredEbankingPartner);
                            $bankTransferType                       = new BankTransferType();
                            $bankTransferType->bankCode             = $bank->bank_code;
                            $bankTransferType->shortBankCode        = $bank->short_code;
                            $bankTransferType->accountNumber        = $staffInfo->account_number;
                            $bankTransferType->description          = "Salary payment from " . $staffInfo->staff->company->name;
                            $bankTransferType->amount               = $staffInfo->net_salary;
                            $bankTransferType->transactionReference = $transactionId;

                            $bankTransfer = $ebankingService->transferToBankAccount($bankTransferType);

                            if ($bankTransfer['status'] == "01") {
                                debitReversal($transactionId, null, $staffInfo->staff->company_id, 0, 30 + $staffInfo->net_salary, 'company');

                                $bankTransfer['message'] = "Unable to restrieve customer name, Funds has been reversed, try again later or use another bank";
                                $bankTransfer['status']  = false;

                                return response()->json($bankTransfer, 400);
                            }

                            if ($bankTransfer['status'] != "00") {
                                $bankTransfer['status'] = false;
                                return response()->json($bankTransfer, 200);
                            }
                            $payrollLog->status_id = 3;
                            $payrollLog->save();

                            $transaction->transaction_remarks_id = 2;
                            $transaction->save();

                            sendSMS($staffInfo->staff_id, "Hi " . $staffInfo->staff->first_name . ", your " . date("F") . " salary has been paid NGN" . $staffInfo->net_salary . "  in your bank account", true, true, date("F") . ' Salary Payment');

                            StaffPayRoll::where('staff_id', $staffInfo->staff_id)->update(["salary_payment_status" => 1, "updated_at" => Carbon::now()]);
                        } else {

                            $wbAccountDetails = wb_account_details::where('virtual_accnum', $staffInfo->wallet_account_number)->first();

                            $description .= " Internal Payment";

                            if ($wbAccountDetails) {
                                $payStaffWallet  = \creditCustomerOnly($wbAccountDetails->id, $staffInfo->net_salary);
                                $ledger_cr       = null;
                                $ledger_dr       = null;
                                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(85, $staffInfo->staff->company_id);
                                if ($ledger_Settings) {
                                    $ledger_cr = $ledger_Settings->ledger_cr;
                                    $ledger_dr = $ledger_Settings->ledger_dr;
                                }

                                if (! $payStaffWallet['status']) {
                                    logInfo($payrollStaff);
                                    return $payStaffWallet;
                                }

                                $transaction = transactions::create([
                                    "balance_before"         => $payStaffWallet['balance_before'],
                                    "amount"                 => $staffInfo->net_salary,
                                    "description"            => $description,
                                    "account_id"             => $wbAccountDetails->id,
                                    "branch_id"              => $staffInfo->staff->company_id,
                                    "company_id"             => $staffInfo->staff->company_id,
                                    "transaction_status_id"  => 1,
                                    "company_balance_before" => 0,
                                    "company_amount"         => 0,
                                    "company_balance_after"  => 0,
                                    "transaction_reference"  => $transactionId,
                                    "transaction_type_id"    => 85, //debit 7
                                    "credit_debit_type_id"   => 1,  //debit 2
                                    "transaction_remarks_id" => 2,  //success 2
                                    "balance_after"          => $payStaffWallet['balance_after'],
                                    "created_date"           => Carbon::now(),
                                    "created_by"             => $staffInfo->staff->id,
                                    "ledger_cr"              => $ledger_cr,
                                    "ledger_dr"              => $ledger_dr,
                                ]);

                                $payrollLog->transaction_id = $transaction->id;
                                $payrollLog->save();
                                sendSMS($staffInfo->staff_id, "Hi " . $staffInfo->staff->first_name . ", your " . date("F") . " salary has been paid NGN" . $staffInfo->net_salary . "  in your wallet", true, true, date("F") . ' Salary Payment');

                                StaffPayRoll::where('staff_id', $staffInfo->staff_id)->update(["salary_payment_status" => 1, "updated_at" => Carbon::now()]);
                            } else {
                                \logInfo("Unable to find wallet account $staffInfo->wallet_account_number", "Wallet Payment status");
                            }
                        }
                    }
                }
                // } else {
                //     logInfo($staffInfo->staff->company_id, "company id of staff");
                // }
            }
        } catch (Exception $e) {

            Log::error("SalaryPaymentCron Job has error: " . $e->getMessage());
            throw $e;
        }

    }

}
