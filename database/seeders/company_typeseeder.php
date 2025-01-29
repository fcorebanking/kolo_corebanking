<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class company_typeseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

            ['name' => "Cooperative society", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Cooperative Bank", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Staff cooperative", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Thrift Collector", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Microfinance institution", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Microfinance Bank", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Secco", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Financial NGO", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Micro Lender", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Investment club", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Mini Bank", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Esusu Ajo group", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Multiple CSL", 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Others", 'created_at' => now(), 'updated_at' => now()]
        ];

        DB::table('company_types')->insert($default);
    }
}
