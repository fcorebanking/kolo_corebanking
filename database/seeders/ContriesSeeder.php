<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

            ['name' => 'Nigeria', 'code' => "NG", 'phone_code' => "+234",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Zambia', 'code' => "ZM", 'phone_code' => "+260",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'United State of America', 'code' => "US", 'phone_code' => "+1",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ghana', 'code' => "GH", 'phone_code' => "+233",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Uganda', 'code' => "UG", 'phone_code' => "+256",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tanzania', 'code' => "TZ", 'phone_code' => "+255",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Congo', 'code' => "CG", 'phone_code' => "+243",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rwanda', 'code' => "RW", 'phone_code' => "+250",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'South Africa', 'code' => "ZA", 'phone_code' => "+228",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Togo', 'code' => "TG", 'phone_code' => "+254",  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Democratic Republic of Congo', 'code' => "CD", 'phone_code' => "+243",  'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('contries')->insert($default);
    }
}
