<?php

namespace Database\Seeders;

use App\Models\Staffrole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $staffRoles = [
            ['name' => "Company Owner", "company_id" => 1, "created_by" => 1],
            ['name' => "Staff", "company_id" => 1, "created_by" => 1],
            ['name' => "Teller", "company_id" => 1, "created_by" => 1],
            ['name' => "Company Owner", "company_id" => 2, "created_by" => 2],
            ['name' => "Staff", "company_id" => 2, "created_by" => 2],
            ['name' => "Teller", "company_id" => 2, "created_by" => 2],
            ['name' => "Company Owner", "company_id" => 3, "created_by" => 3],
            ['name' => "Staff", "company_id" => 3, "created_by" => 3],
            ['name' => "Teller", "company_id" => 3, "created_by" => 3],
        ];

        DB::table('staffrole')->insert($staffRoles);
    }
}
