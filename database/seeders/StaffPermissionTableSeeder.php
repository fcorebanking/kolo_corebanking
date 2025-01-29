<?php

namespace Database\Seeders;

use App\Models\StaffpermissionTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $staffPermissions = [
            ['staffrole_id' => 1, "user_id" => 1, "created_by" => 1],
            ['staffrole_id' => 1, "user_id" => 2, "created_by" => 2],
            ['staffrole_id' => 1, "user_id" => 3, "created_by" => 3]
        ];
        DB::table('staffpermission')->insert($staffPermissions);
    }
}
