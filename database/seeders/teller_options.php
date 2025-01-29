<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
class teller_options extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [
            ['teller_option' => 'Type 1- credit teller wallet ', 'created_at' => now(), 'updated_at' => now()],
            ['teller_option' => 'Type 1- debit teller wallet ','created_at' => now(), 'updated_at' => now()],
            ['teller_option' => 'Type 3- Pend teller transaction ','created_at' => now(), 'updated_at' => now()],
            ['teller_option' => 'Type 4- Debit Customer - Pend teller debit ','created_at' => now(), 'updated_at' => now()],

        ];
        DB::table('teller_options')->insert($default);
    }
}
