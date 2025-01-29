<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class CustomerPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customper = [
            ["permission" => "block-deposit", "title" => "Block  Deposit (Apply PNC)"],
            ["permission" => "block-withdraw", "title" => "Block Withdrawal (Apply PND)"],
            ["permission" => "block-transaction", "title" => "Block Transaction"],
            ["permission" => "block-loan", "title" => "Block Loan"],
            ["permission" => "block-investment", "title" => "Block Investment"],
        ];
        DB::table('customer_permissions')->insert($customper);
    }
}
