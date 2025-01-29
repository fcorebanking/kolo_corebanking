<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarouselImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cards = [
            ['image_url' => 'https://wiredbanking.ng/slide1.jpeg','link_url' => 'https://google.com', 'status_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['image_url' => 'https://wiredbanking.ng/slide2.jpeg','link_url' => 'https://google.com', 'status_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('carousel_images')->insert($cards);
    }
}
