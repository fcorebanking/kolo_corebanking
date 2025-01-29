<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VerificationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [
            ['verification_status' => 'Pending', 'created_at' => now(), 'updated_at' => now()],
            ['verification_status' => 'BVN', 'created_at' => now(), 'updated_at' => now()],
            ['verification_status' => 'NIN', 'created_at' => now(), 'updated_at' => now()],
            ['verification_status' => 'BVN + NIN', 'created_at' => now(), 'updated_at' => now()],
            ['verification_status' => 'Driver Licence', 'created_at' => now(), 'updated_at' => now()],
            ['verification_status' => 'Driver Licence + NIN', 'created_at' => now(), 'updated_at' => now()],
            ['verification_status' => 'Driver Licence + BVN', 'created_at' => now(), 'updated_at' => now()],
            ['verification_status' => 'Driver Licence + NIN + BVN', 'created_at' => now(), 'updated_at' => now()]
        ];

        DB::table('verification_statuses')->insert($default);
    }
}
