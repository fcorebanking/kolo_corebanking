<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserCardStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $cardStatus = [
            ['user_card_status' => "No Card"],
            ['user_card_status' => "Pending Delivery"],
            ['user_card_status' => "Has Card"],
        ];

        DB::table('user_card_statuses')->insert($cardStatus);
    }
}
