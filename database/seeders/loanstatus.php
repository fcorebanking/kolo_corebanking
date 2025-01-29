<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class loanstatus extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //loan_status
        // $default = [
        //     ['name' => 'Active','statusID'=>1,'tag'=>'green', 'created_at' => now(), 'updated_at' => now()],
        //     ['name' => 'Disbursment','statusID'=>2,'tag'=>'yellow', 'created_at' => now(), 'updated_at' => now()],
        //     ['name' => 'Pending','statusID'=>3,'tag'=>'yellow', 'created_at' => now(), 'updated_at' => now()],
        //     ['name' => 'Complete','statusID'=>10,'tag'=>'grey', 'created_at' => now(), 'updated_at' => now()],
        //     ['name' => 'Roll-Over','statusID'=>6,'tag'=>'grey', 'created_at' => now(), 'updated_at' => now()],
        //     ['name' => 'Written-Off','statusID'=>7,'tag'=>'grey', 'created_at' => now(), 'updated_at' => now()],
        //     ['name' => 'Rejected','statusID'=>4,'tag'=>'grey', 'created_at' => now(), 'updated_at' => now()],

        //     ];
        //     DB::table('loan_status')->insert($default);
    }
}
