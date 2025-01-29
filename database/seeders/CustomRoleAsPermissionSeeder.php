<?php

namespace Database\Seeders;

use App\Models\Custom_role_as_permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomRoleAsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customRoles = [
            ['staffrole_id' => 1, "permission_id" => 1],
          
        ];


        DB::table('custom_role_as_permissions')->insert($customRoles);
    }
}
