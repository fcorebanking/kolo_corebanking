<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhoneVerificationStatusSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $transactionTypes = [
            ['phone_verification_status' => 'Not Verified'],
            ['phone_verification_status' => 'Verified']
        ];

        DB::table('phone_verification_status')->insert($transactionTypes);

    }
}
