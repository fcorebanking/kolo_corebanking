<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CardTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cards = [
            ['card_type' => 'VISA', 'created_at' => now(), 'updated_at' => now()],
            ['card_type' => 'Verve', 'created_at' => now(), 'updated_at' => now()],
            ['card_type' => 'Master', 'created_at' => now(), 'updated_at' => now()],
            
        ];

        DB::table('card_types')->insert($cards);
    }
}
