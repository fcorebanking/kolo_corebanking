<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CardCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cards = [
            ['card_category_name' => 'Virtual', 'created_at' => now(), 'updated_at' => now()],
            ['card_category_name' => 'Physical', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('card_categories')->insert($cards);
    }
}
