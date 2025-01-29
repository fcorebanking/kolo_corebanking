<?php

namespace App\Http\Controllers;
//
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\LedgerAccountModel;
use App\Models\LedgerSettings;
use App\Models\transaction_types;
use App\Models\OtherIncome;
use App\Models\transactions;
use App\Models\Expenses;
use Illuminate\Support\Facades\DB;
class TestController extends Controller
{
    // transactions OtherIncome Expenses
    public function index(){
        $ledgerSettings =LedgerSettings::where('company_id',1)->get();
        foreach($ledgerSettings as $ledgerSettingsData){
            echo "['page'=>'".$ledgerSettingsData->page->transaction_type."','ledger_cr'=>'".$ledgerSettingsData->ledgerCR->name."','ledger_dr'=>'".$ledgerSettingsData->ledgerDR->name."'],";
        }
    }    public function calculatePrincipalPayment() {
        $company = Company::where('id','292')->get();
        foreach($company as $companyData){
            OtherIncome::whereHas('author', function ($query) use ($companyData) {
                $query->where('company_id', $companyData->id);
            })->update(['ledger_cr' => null, 'ledger_dr' => null]);
            Expenses::whereHas('author', function ($query) use ($companyData) {
                $query->where('company_id', $companyData->id);
            })->update(['ledger_cr' => null, 'ledger_dr' => null]);
            transactions::whereHas('author', function ($query) use ($companyData) {
                $query->where('company_id', $companyData->id);
            })->update(['ledger_cr' => null, 'ledger_dr' => null]);
            var_dump($this->addCompanySettings($companyData->id));
        }
    }
    public function resetImport()
    {
        $auth = auth()->user();
        $companyData = $auth->company;
        OtherIncome::whereHas('author', function ($query) use ($auth) {
            $query->where('company_id', $auth->company_id);
        })->update(['ledger_cr' => null, 'ledger_dr' => null]);
        Expenses::whereHas('author', function ($query) use ($auth) {
            $query->where('company_id', $auth->company_id);
        })->update(['ledger_cr' => null, 'ledger_dr' => null]);
        transactions::whereHas('author', function ($query) use ($auth) {
            $query->where('company_id', $auth->company_id);
        })->update(['ledger_cr' => null, 'ledger_dr' => null]);
        return $this->addCompanySettings($auth->company_id);
    }
    public function syncTransaction(){
        $auth = auth()->user();
        $transaction =transactions::whereHas('author', function ($query) use ($auth) {
            $query->where('company_id', $auth->company_id);
        })->get();
        foreach($transaction as $transactionData){
            $get_ledger_setting = get_CR_DR_TXN_Type_Ledger($transactionData->transaction_type_id,$auth->company_id);
            if($get_ledger_setting){
                $transactionData->ledger_cr =$get_ledger_setting->ledger_cr;
                $transactionData->ledger_dr =$get_ledger_setting->ledger_dr;
                $transactionData->save();
            }
        }
        return true;
    }
    public function addCompanySettings($companyID){
        $company = Company::where('id',$companyID)->get();
        foreach($company as $companyData){
            if(isset($companyData->createdby->id)){
                $insertAccount = false;
                $createdBy =$companyData->createdby->id;
                foreach($this->ledgerLibrary($companyData,$createdBy) as $ledgerAccounts){
                    $ledgerAccountModel =LedgerAccountModel::where(['company_id'=>$companyData->id,'name'=>$ledgerAccounts['name']])->count();
                    if($ledgerAccountModel ==0){
                        $ledgerAccountModel =new LedgerAccountModel;
                        $ledgerAccountModel->company_id=$companyID ;
                        $ledgerAccountModel->name=$ledgerAccounts['name'] ;
                        $ledgerAccountModel->code=$ledgerAccounts['code'] ;
                        $ledgerAccountModel->deleteable=$ledgerAccounts['deleteable'];
                        $ledgerAccountModel->created_by=$ledgerAccounts['created_by'];
                        $ledgerAccountModel->branch_id=$ledgerAccounts['branch_id'];
                        $ledgerAccountModel->access=$ledgerAccounts['access'];
                        $ledgerAccountModel->type=$ledgerAccounts['type'];
                        $ledgerAccountModel->category=$ledgerAccounts['category'];
                        $ledgerAccountModel->save();
                        $insertAccount=true;
                    }
                }
                if($this->settingsImport($companyID)){
                    return ['status'=>true,'message'=>'Settings Complete'];
                }
            }
        }
        return ['status'=>false,'message'=>'Settings Failed'];
    }
    public function settingsImport($companyID){
        $company = Company::where('id',$companyID)->get();
        foreach($company as $companyData){
            $settingsData = $this->settingsData();
            for($i=0; $i<count($settingsData); $i++){
                $transaction_type_id = $settingsData[$i]['page'];
                $ledger_cr = $settingsData[$i]['ledger_cr'];
                $ledger_dr = $settingsData[$i]['ledger_dr'];
                $accountCR  =LedgerAccountModel::where(['company_id'=>$companyData->id,'name'=>$ledger_cr])->first();
                $accountDR  =LedgerAccountModel::where(['company_id'=>$companyData->id,'name'=>$ledger_dr])->first();
                if(!$accountCR){ echo "error at $transaction_type_id cr $companyData->id $ledger_cr $ledger_dr"; continue; }
                if(!$accountDR){ echo "error at $transaction_type_id dr $companyData->id $ledger_cr $ledger_dr"; continue; }
                $transaction_types = transaction_types::where(['transaction_type'=>$transaction_type_id])->first();
                if(!$transaction_types){ echo "error at tr $transaction_type_id $companyData->id $ledger_cr $ledger_dr"; continue; }
                if(LedgerSettings::where(['transaction_type_id'=>$transaction_types->id,'company_id'=>$accountCR->company_id])->count() ==0){
                    $addAccount =new LedgerSettings;
                    $addAccount->transaction_type_id= $transaction_types->id;
                    $addAccount->ledger_cr= $accountCR->id;
                    $addAccount->ledger_dr= $accountDR->id;
                    $addAccount->company_id= $accountCR->company_id;
                    $addAccount->created_by= $accountCR->created_by;
                    $addAccount->save();
                }
            }
        }
        return true;
    }
    public function settingsData(){
        return [
            ['page'=>'Cash Deposit','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'cash'],['page'=>'Paystack Deposit','ledger_cr'=>'Subscription Expense','ledger_dr'=>'Subscription Expense'],['page'=>'Flutterwave Deposit','ledger_cr'=>'Subscription Expense','ledger_dr'=>'Subscription Expense'],['page'=>'Wallet to Wallet Debit','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Company Wallet Transfer','ledger_cr'=>'cash','ledger_dr'=>'cash'],['page'=>'Bank Transfer Deposit','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'cash'],['page'=>'Cash Withdrawal','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Wallet to Bank Transfer','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'cash'],['page'=>'Virtual Card Funding','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Virtual Card Withdrawal','ledger_cr'=>'cash','ledger_dr'=>'cash'],['page'=>'Airtime Topup','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Data Subscription','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Cable TV Subscription','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Electricity Payment','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'USSD Topup','ledger_cr'=>'USSD Expenses','ledger_dr'=>'USSD Expenses'],['page'=>'SMS Topup','ledger_cr'=>'SMS Expenses','ledger_dr'=>'SMS Expenses'],['page'=>'Maintenance','ledger_cr'=>'General Expenses','ledger_dr'=>'General Expenses'],['page'=>'Subscription','ledger_cr'=>'General Expenses','ledger_dr'=>'General Expenses'],['page'=>'Mobile Money Transfer','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Teller Commission','ledger_cr'=>'Teller Comission','ledger_dr'=>'Teller Comission'],['page'=>'Payment via Payment Link','ledger_cr'=>'Service Revenue','ledger_dr'=>'Service Revenue'],['page'=>'Card Processing Fee','ledger_cr'=>'General Expenses','ledger_dr'=>'General Expenses'],['page'=>'Physical Card Funding','ledger_cr'=>'Funded Card','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Deposit Reversal','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Debit Reversal','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'cash'],['page'=>'Bank Transfer Charge','ledger_cr'=>'Service Revenue','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Wallet to Wallet Reversal','ledger_cr'=>'cash','ledger_dr'=>'cash'],['page'=>'WhatsApp Topup','ledger_cr'=>'General Expenses','ledger_dr'=>'General Expenses'],['page'=>'Customer Registration','ledger_cr'=>'Service Revenue','ledger_dr'=>'Service Revenue'],['page'=>'Wallet to Wallet Credit','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'Customer Saving Deposit'],['page'=>'SMS Charge','ledger_cr'=>'Service Revenue','ledger_dr'=>'Service Revenue'],['page'=>'Investment plan credit','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'Investment Wallet'],['page'=>'Investment Plan Debit','ledger_cr'=>'Investment Wallet','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Esusu Deposit','ledger_cr'=>'Esusu Wallet','ledger_dr'=>'cash'],['page'=>'Esusu Overdraft credit','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'Esusu Wallet'],['page'=>'Esusu withdraw','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'Esusu Wallet'],['page'=>'Esusu Overdraft Charge','ledger_cr'=>'Esusu Overdraft Charge','ledger_dr'=>'Esusu Wallet'],['page'=>'Esusu Accum. Charge','ledger_cr'=>'Esusu Overdraft Charge','ledger_dr'=>'Esusu Wallet'],['page'=>'Esusu Overdraft Debit','ledger_cr'=>'Esusu Wallet','ledger_dr'=>'Esusu Wallet'],['page'=>'Whatsapp Session Charge','ledger_cr'=>'WhatsApp Session Charge','ledger_dr'=>'Cash In Bank'],['page'=>'BVN Verification fee','ledger_cr'=>'BVN Verification','ledger_dr'=>'Cash In Bank'],['page'=>'Teller Credit','ledger_cr'=>'Teller Cash','ledger_dr'=>'cash'],['page'=>'Teller Debit','ledger_cr'=>'cash','ledger_dr'=>'Teller Cash'],['page'=>'NIN Verification fee','ledger_cr'=>'NIN Verification','ledger_dr'=>'Cash In Bank'],['page'=>'Reverse Teller Transaction','ledger_cr'=>'cash','ledger_dr'=>'cash'],['page'=>'NIBSS Direct Credit','ledger_cr'=>'cash','ledger_dr'=>'Cash In Bank'],['page'=>'NIBSS Direct Debit','ledger_cr'=>'Cash In Bank','ledger_dr'=>'cash'],['page'=>'Bill payment charge','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Topup Betting Account','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Loan Credit','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'cash'],['page'=>'Loan Fee Charge','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'Loan Receivable'],['page'=>'Loan Management Fee Charge','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'Loan Receivable'],['page'=>'Loan Repayment','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'Loan Receivable'],['page'=>'Setup Fee Payment','ledger_cr'=>'General Expenses','ledger_dr'=>'cash'],['page'=>'Savings Maintenance Charge','ledger_cr'=>'Service Revenue','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Savings Interest','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'cash'],['page'=>'Overdraft','ledger_cr'=>'Accounts Receivable','ledger_dr'=>'cash'],['page'=>'Loan LookUp','ledger_cr'=>'General Expenses','ledger_dr'=>'cash'],['page'=>'Loan Penalty Charge','ledger_cr'=>'Loan Receivable','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Ecommerce Product Payment','ledger_cr'=>'Customer Saving Deposit','ledger_dr'=>'cash'],['page'=>'Salary Payment','ledger_cr'=>'Salaries Expense','ledger_dr'=>'cash'],['page'=>'Esusu Agent TRansaction Debit','ledger_cr'=>'cash','ledger_dr'=>'cash'],['page'=>'Esusu Agent TRansaction Credit','ledger_cr'=>'cash','ledger_dr'=>'cash'],['page'=>'Esusu Withdrawal Request','ledger_cr'=>'Esusu Wallet','ledger_dr'=>'cash'],['page'=>'Domain Registration','ledger_cr'=>'General Expenses','ledger_dr'=>'cash'],['page'=>'Sub-Domain Registration','ledger_cr'=>'General Expenses','ledger_dr'=>'cash'],['page'=>'company whatsapp charge','ledger_cr'=>'General Expenses','ledger_dr'=>'cash'],['page'=>'customer whatsapp charge','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Local Wallet to Bank Transfer','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Debit on remitta salary history','ledger_cr'=>'cash','ledger_dr'=>'Customer Saving Deposit'],['page'=>'Funding via PayAttitude','ledger_cr'=>'cash','ledger_dr'=>'cash'],['page'=>'Company Settlement Maximum Balance','ledger_cr'=>'Cash In Bank','ledger_dr'=>'cash'],['page'=>'Driver License Verification fee','ledger_cr'=>'General Expenses','ledger_dr'=>'cash'],['page'=>'Local Bank Transfer Deposit','ledger_cr'=>'cash','ledger_dr'=>'cash'],['page'=>'USSD Wallet Funding','ledger_cr'=>'USSD Cash','ledger_dr'=>'cash'],['page'=>'USSD Wallet Funding Charge','ledger_cr'=>'General Expenses','ledger_dr'=>'cash'],
            ['page'=>'USSD Session Charge','ledger_cr'=>'General Expenses','ledger_dr'=>'USSD Cash'],
            ['page'=>'Esusu Wallet Debit','ledger_cr'=>'Esusu Wallet','ledger_dr'=>'Esusu Wallet'],
        ];
    }
    public function ledgerLibrary($companyData,$createdBy){
        return $data = [
            [
                'company_id' => $companyData->id,
                'name' => 'Customer Saving Deposit',
                'code' => '2310',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Liability'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'cash',
                'code' => '1010',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Asset'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Accounts Receivable',
                'code' => '1020',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Asset'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Inventory',
                'code' => '1100',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Asset'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Equipment',
                'code' => '1111',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Asset'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Buildings',
                'code' => '1122',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Asset'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Accounts Payable',
                'code' => '2000',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Liability'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Loans Payable',
                'code' => '2100',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Liability'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Accrued Expenses',
                'code' => '2101',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Liability'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Notes Payable',
                'code' => '2111',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Liability'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Owners Equity',
                'code' => '3000',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Equity'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Retained Earnings',
                'code' => '3100',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Equity'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Common Stock',
                'code' => '3110',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Equity'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Sales Revenue',
                'code' => '4110',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Revenue'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Service Revenue',
                'code' => '4000',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Revenue'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Interest Income',
                'code' => '4001',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Revenue'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Rental Income',
                'code' => '4111',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Revenue'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Salaries Expense',
                'code' => '5111',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Subscription Expense',
                'code' => '5101',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Utilities Expense',
                'code' => '5100',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Advertising Expense',
                'code' => '5105',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Subscription Revenue',
                'code' => '1605',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Equity'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Expenses: Company Whatsapp Charge',
                'code' => '5156',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Company Whatsapp Wallet',
                'code' => '1369',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Revenue'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'USSD Expenses',
                'code' => '5600',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Cash In Bank',
                'code' => '1665',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Asset'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'SMS Expenses',
                'code' => '5625',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'General Expenses',
                'code' => '5555',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Teller Comission',
                'code' => '2222',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Liability'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Funded Card',
                'code' => '2333',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Liability'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Investment Wallet',
                'code' => '2303',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Liability'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Esusu Wallet',
                'code' => '2707',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Liability'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Esusu Overdraft Charge',
                'code' => '4707',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'credit',
                'category' => 'Revenue'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'WhatsApp Session Charge',
                'code' => '5234',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'USSD Session Charge',
                'code' => '5244',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'BVN Verification',
                'code' => '5245',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Teller Cash',
                'code' => '1212',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Asset'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'NIN Verification',
                'code' => '5212',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Expenses'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'Loan Receivable',
                'code' => '1112',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Asset'
            ],
            [
                'company_id' => $companyData->id,
                'name' => 'USSD Cash',
                'code' => '1172',
                'deleteable' => 1,
                'created_by' => $createdBy,
                'branch_id' => NULL,
                'access' => 1,
                'type' => 'debit',
                'category' => 'Asset'
            ]
        ];


    }
}
