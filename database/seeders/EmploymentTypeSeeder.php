<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmploymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

            ['employment_type' => 'Permanent', 'created_at' => now(), 'updated_at' => now()],
            ['employment_type' => 'Full time','created_at' => now(), 'updated_at' => now()],
            ['employment_type' => 'Part time','created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('employment_types')->insert($default);
    }
}
