<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreditDebitTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            ['type' => 'Credit'],
            ['type' => 'Debit'],
        ];
        DB::table('credit_debit_types')->insert($types);
    }
}
