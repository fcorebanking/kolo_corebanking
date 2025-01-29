<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
class auto_renewals extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

            ['auto_renewal' => 'On', 'created_at' => now(), 'updated_at' => now()],
            ['auto_renewal' => 'Off','created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('auto_renewals')->insert($default);
    }
}
