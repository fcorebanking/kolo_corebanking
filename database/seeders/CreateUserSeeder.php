<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\plans;
use App\Models\sub;
use App\Models\transactions;
use App\Models\sublog;


class CreateUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $users = [
            [
                'first_name' => 'Wired',
                'last_name' => 'Banking',
                'company_id' => '1',
                'uuid' => 'FHZR1JDNrvIsfAoxWgnO',
                'email' => 'wiredbanking@gmail.com',
                'username' => 'wiredbanking',
                'status_id' => '1',
                // 'branch_id' => '1',
                'bank_id' => '64',
                'user_type_id' => '2',
                'kyc_level_id' => '1',
                'virtual_account' => "7199871789",
                'phone' => '08020120120',
                'profile_pic' => "public/companya/profile/1/check_logo.png",
                'created_by' => '1',
                'password' => bcrypt('123456')
            ],
            [
                'first_name' => 'Chukwuma',
                'last_name' => 'Haliru',
                'company_id' => '2',
                'uuid' => 'isRJmvfO502GjGCDqZKz',
                'email' => 'abelgabriel@gmail.com',
                'username' => 'chukwuma',
                'status_id' => '1',
                'kyc_level_id' => '1',
                // 'branch_id' => '1',
                'user_type_id' => '2',
                'virtual_account' => "7199986825",
                'bank_id' => '64',
                'account_reference' => 'gYE5BWzbNsauiIneqaOu',
                'phone' => '08062500021',
                'profile_pic' => "public/companya/profile/2/check_logo.png",
                'created_by' => '1',
                'password' => bcrypt('123456')
            ],
            [
                'first_name' => 'Zainab',
                'last_name' => 'Ifeyinwa',
                'company_id' => '3',
                'uuid' => 'rgucF29cMaJzNCZRGpjX',
                'email' => 'zainabifeyinwa@gmail.com',
                'username' => 'zainab',
                'user_type_id' => '2',
                'status_id' => '1',
                'kyc_level_id' => '1',
                // 'branch_id' => '1',
                'virtual_account' => "7199990253",
                'bank_id' => '64',
                'account_reference' => 'T9HJ2BMWsyK8knXgnQgC',
                'phone' => '08051201200',
                'profile_pic' => "public/companya/profile/3/check_logo.png",
                'created_by' => '1',
                'password' => bcrypt('123456')
            ]

        ];

        foreach ($users as $user) {
            $user = User::create($user);
            $user->assignRole([4]);
        }
        $plan = plans::find(1);
        $endpayment =  add_day_to_date($plan->duration, date('Y-m-d'));
        $transactionId = generate_txnID();


        $transaction = transactions::create([
            "balance_before" => 0,
            "amount" => 0,
            "description" => "Wiredbanking Subsciption Trial",
            "transaction_reference" => $transactionId,
            "transaction_type_id" => 18,
            "credit_debit_type_id" => 2,
            "transaction_remarks_id" => 2,
            "balance_after" => 0,
            "created_date"  => date('Y-m-d'),
            "created_by" => 1
        ]);
        sublog::create([
            'company_id' => 1,
            'transaction_id' => $transaction->id,
            'plan_id' => $plan->id,
            'quantity' => $plan->duration,
            'start_date' => date('Y-m-d'),
            'end_date' => $endpayment,

        ]);
        sub::create([
            'company_id' => 1,
            'transaction_id' => $transaction->id,
            'plan_id' => 1,
            'quantity' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => $endpayment,
            'status' => 1
        ]);
    }
}
