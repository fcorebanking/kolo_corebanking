<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use App\Models\transaction_types;


class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $transactionTypes = [
                    ['transaction_type' => "Cash Deposit"], //trhis
                    ['transaction_type' => "Paystack Deposit"],
                    ['transaction_type' => "Flutterwave Deposit"],
                    ['transaction_type' => "Wallet to Wallet Debit"],
                    ['transaction_type' => "Company Wallet Transfer"],
                    ['transaction_type' => "Bank Transfer Deposit"],
                    ['transaction_type' => "Cash Withdrawal"],
                    ['transaction_type' => "Wallet to Bank Transfer"],
                    ['transaction_type' => "Virtual Card Funding"],
                    ['transaction_type' => "Virtual Card Withdrawal"],
                    ['transaction_type' => "Airtime Topup"],
                    ['transaction_type' => "Data Subscription"],
                    ['transaction_type' => "Cable TV Subscription"],
                    ['transaction_type' => "Electricity Payment"],
                    ['transaction_type' => "USSD Topup"],
                    ['transaction_type' => "SMS Topup"],
                    ['transaction_type' => "Maintenance"],
                    ['transaction_type' => "Subscription"],
                    ['transaction_type' => "Mobile Money Transfer"],
                    ['transaction_type' => "Teller Commission"],
                    ['transaction_type' => "Investment plan credit"],
                    ['transaction_type' => "Payment via Payment Link"],
                    ['transaction_type' => "Card Processing Fee"],
                    ['transaction_type' => "Physical Card Funding"],
                    ['transaction_type' => "Deposit Reversal"],
                    ['transaction_type' => "Debit Reversal"],
                    ['transaction_type' => "Bank Transfer Charge"],
                    ['transaction_type' => "Wallet to Wallet Reversal"],
                    ['transaction_type' => "WhatsApp Topup"],
                    ['transaction_type' => "Customer Registration"],
                    ['transaction_type' => "Branch Transaction"],
                    ['transaction_type' => "Wallet to Wallet Credit"],
                    ['transaction_type' => "SMS Charge"],
                    ['transaction_type' => "Investment Plan Debit"],
                    ['transaction_type' => "Esusu Deposit"], //35
                    ['transaction_type' => "Esusu Overdraft credit"],
                    ['transaction_type' => "Esusu withdraw"],
                    ['transaction_type' => "Esusu Overdraft Charge"],
                    ['transaction_type' => "Esusu Accum. Charge"],
                    ['transaction_type' => "Esusu Overdraft Debit"], //40
                    ['transaction_type' => 'Whatsapp Session Charge'],
                    ['transaction_type' => 'USSD Session Charge'],
                    ['transaction_type' => "BVN Verification fee"],
                    ['transaction_type' => "Teller Credit"],
                    ['transaction_type' => "Teller Debit"],
                    ['transaction_type' => "NIN Verification fee"],
                    ['transaction_type' => "Direct Debit Mandate"],
                    ['transaction_type' => "Reverse Teller Transaction"],
                    ['transaction_type' => "NIBSS Direct Credit"],
                    ['transaction_type' => "NIBSS Direct Debit"],
                    ['transaction_type' => "Bill payment charge"],
                    ['transaction_type' => "Topup Betting Account"],
                    ['transaction_type' => "Loan Credit"],
                    ['transaction_type' => "Loan Fee Charge"],
                    ['transaction_type' => "Loan Management Fee Charge"],
                    ['transaction_type' => "Loan Repayment"],
                    ['transaction_type' => "Setup Fee Payment"],
                    ['transaction_type' => "Savings Maintenance Charge"],
                    ['transaction_type' => "Savings Interest"],
                    ['transaction_type' => "Overdraft"],
                    ['transaction_type' => "Loan LookUp"],
                    ['transaction_type' => "Loan Penalty Charge"],
                    ['transaction_type' => "POS Cashout"],
                    ['transaction_type' => "POS Bills Payment"],
                    ['transaction_type' => "POS Funds Transfer"],
                    ['transaction_type' => "Transaction Cashback"],
                    ['transaction_type' => "Bonus SMS"],
                    ['transaction_type' => "POS Admin Topup"],
                    ['transaction_type' => "POS Admin Debit"],
                    ['transaction_type' => "POS Company commission"],
                    ['transaction_type' => "POS Airtime Purchase"],
                    ['transaction_type' => "POS Data Purchase"],
                    ['transaction_type' => "Esusu Wallet Debit"],
                    ['transaction_type' => "POS Agent Company Credit"],
                    ['transaction_type' => "POS Agent Company Debit"],
                    ['transaction_type' => "POS Credit Charge"],
                    ['transaction_type' => "Ecommerce Product Payment"],
                    ['transaction_type' => "Cheque Book Deposit"],
                    ['transaction_type' => "Naira to Crypto Payment"],
                    ['transaction_type' => "Crypto to Naira Payment"],
                    ['transaction_type' => "Cheque Withdrawal"],
                    ['transaction_type' => "POS Cable Subscription"],
                    ['transaction_type' => "POS Bet Topup"],
                    ['transaction_type' => "POS Electricity Purchase"],
                    ['transaction_type' => "Salary Payment"],
                    ['transaction_type' => "Esusu Agent TRansaction Debit"],
                    ['transaction_type' => "Esusu Agent TRansaction Credit"],
                    ['transaction_type' => "Esusu Withdrawal Request"],
                    ['transaction_type' => "Domain Registration"],
                    ['transaction_type' => "Sub-Domain Registration"],
                    ['transaction_type' => "Company Whatsapp Charge"],
                    ['transaction_type' => "Customer Whatsapp Charge"],
                    ['transaction_type' => "Local Wallet to Bank Transfer"],
                    ['transaction_type' => "Local Bank Transfer Deposit"],
                    ['transaction_type' => "Driver License Verification fee"],
                    ['transaction_type' => "Bank Transfer Reversal"],
                    ['transaction_type' => "USSD Wallet Funding"],
                    ['transaction_type' => "Company Settlement Maximum Balance"],
                    ['transaction_type' => "USSD Wallet Funding Charge"],
                    ['transaction_type' => "Virtual Card Funding Charge"],
                    ['transaction_type' => "Funding via PayAttitude"],
                    ['transaction_type' => "Debit on remitta salary history"],
                    ['transaction_type' => "ATM Withdrawal"],
                    ['transaction_type' => "Debit for customer savings"],
                    ['transaction_type' => "Debit ATM Debit Card"],
                    ['transaction_type' => "Ecommerce Delivery Fee"],
                    ['transaction_type' => "Physical Card Funding Charge"],
                    ['transaction_type' => "Company Admin Topup"],
                    ['transaction_type' => "Company Admin Debit"],//110
                    ['transaction_type' => "Referal Commission"],//111
                    ['transaction_type' => "ATM Reversal"],//112
                    ['transaction_type' => "Failed ATM withdrawal reversal"],//113
                    ['transaction_type' => "Delete-Investment"],//114
                    ['transaction_type' => "POS Debit Reversal"],//115
                    ['transaction_type' => "Talent Regitration Fee"],//116
                    ['transaction_type' => "Vote Talent Fee"],//117
                    ['transaction_type' => "Receive Payment"],//118
                    ['transaction_type' => "Company Mobile Money Transaction"],//119
                    ['transaction_type' => "Customer Wallet TopUp"],//120 mobile app
                    ['transaction_type' => "FoodStash withdraw"],//121 mobile app
                    ['transaction_type' => "FoodStash Deposit"],//122 mobile app
                    ['transaction_type' => "Esusu topup wallet debit"],//123 mobile app
                    ['transaction_type' => "Mini POS Payout"],//124 mobile app
                ];
                for($i=0;$i<count($transactionTypes);$i++){
                    $type = $transactionTypes[$i]['transaction_type'];
                    $check_transactions = transaction_types::where('transaction_type',$type)->count();
                    if($check_transactions==0){
                        DB::table('transaction_types')->insert($transactionTypes[$i]);
                    }
                }
    }
}
