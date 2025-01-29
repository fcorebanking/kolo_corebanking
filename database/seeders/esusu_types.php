<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class esusu_types extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

            ['esusu_type' => 'Type 1', 'created_at' => now(), 'updated_at' => now()],
            ['esusu_type' => 'Type 2','created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('esusu_types')->insert($default);
    }
}
