<?php

namespace Database\Seeders;

use App\Models\StaffWallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $staffWallets = [
            ['balance' => 0.0, "user_id" => 1, "created_by" => 1],
            ['balance' => 0.0, "user_id" => 2, "created_by" => 2],
            ['balance' => 0.0, "user_id" => 3, "created_by" => 3]
        ];

        DB::table('staff_wallet')->insert($staffWallets);
    }
}
