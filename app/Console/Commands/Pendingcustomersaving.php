<?php
namespace App\Console\Commands;

use App\Models\Customersavings;
use App\Models\Pendingcustomersaving as PendingModel;
use App\Models\wb_account_details;
use Illuminate\Console\Command;

ini_set('memory_limit', '500000M');

class Pendingcustomersaving extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Pendingcustomersavingcommand:name';
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
    public function handle()
    {
        $createdDate            = date('Y-m-d');
        $pendingCustomersavings = PendingModel::where(['status' => 'pending', 'method' => 'wallet'])->whereDate('paymentDate', '<=', $createdDate)->get();
        if ($pendingCustomersavings) {
            foreach ($pendingCustomersavings as $customerpendingpay) {
                // DB::beginTransaction();
                try {
                    $amount = $customerpendingpay->amount;

                    $userWallet = wb_account_details::select('wb_account_details.id', 'wb_account_details.virtual_accnum', 'user_wallets.balance')
                        ->join('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
                        ->where('wb_account_details.user_id', $customerpendingpay->customersaving->user_id)
                        ->first();
                    echo "** pending ID =$customerpendingpay->id X Wallet account $customerpendingpay->customersaving->user_id--- ";

                    if (! $userWallet) {
                        echo "Insufficient Balance $amount \n";
                        continue;
                    }
                    echo "** Y Wallet account $userWallet->virtual_accnum --- ";

                    $balanceBefore  = $userWallet->balance;
                    $account_typeid = $userWallet->id;
                    $auth           = $customerpendingpay->customersaving->user;
                    $branch         = $auth->branch_id;
                    $desc           = "Customer Target Savings Debit";
                    $transactionId  = generate_txnID();
                    if ($balanceBefore < $amount) {
                        echo "Insufficient Balance $balanceBefore < $amount \n";
                        continue;
                    }
                    echo "Balance exist \n";

                    $balanceAfter = $balanceBefore - $amount;
                    $txnType      = 104;
                    $ledger_cr    = null;
                    $ledger_dr    = null;

                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger($txnType, $auth->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    $data = [
                        "balance_before"         => $balanceBefore,
                        "amount"                 => $amount,
                        "description"            => $desc,
                        "account_id"             => $account_typeid,
                        "branch_id"              => $branch,
                        "transaction_status_id"  => 1,
                        "transaction_reference"  => $transactionId,
                        "transaction_type_id"    => $txnType,
                        "credit_debit_type_id"   => 2,
                        "transaction_remarks_id" => 2,
                        "balance_after"          => $balanceAfter,
                        "created_date"           => date("Y-m-d"),
                        "created_by"             => $auth->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                        "instrumentCode"         => null,
                    ];
                    $data['userParamType'] = 2;
                    $data['userId']        = $account_typeid;
                    $data['remark']        = 2;
                    $data['chargeamt']     = 0;
                    $data['companyVault']  = null;
                    $CreditCustomer        = DebitCreditcustomer($data);
                    if (! $CreditCustomer['status']) {
                        \logInfo($CreditCustomer, 'Customer Saving Error');
                        continue;
                    }
                    $customerpendingpay->status = 'complete';
                    $customerpendingpay->save();
                    $customerSavingModel = Customersavings::where('id', $customerpendingpay->customersaving->id)->first();
                    $customerSavingModel->balance += $amount;
                    $customerSavingModel->status = ($customerSavingModel->balance >= $customerSavingModel->target) ? 'complete' : 'active';
                    $customerSavingModel->save();
                    echo "Commiting end ##/n";
                    // DB::commit();
                } catch (Exception $e) {
                    \logInfo($e->getMessage(), 'Error customer saving ' . $e->getLine());

                }
            }
        }
        return Command::SUCCESS;
    }
}
