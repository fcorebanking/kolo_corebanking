<?php

namespace Database\Seeders;

use App\Models\RoleHasPermission;
use Illuminate\Database\Seeder;

class RoleHasPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roleHasPermissions = [
            ['permission_id' => 1, "role_id" => 4]
        ];
        foreach ($roleHasPermissions as $key => $roleHasPermission) {
            if (
                count(RoleHasPermission::where('permission_id', $roleHasPermission['permission_id'])->get()) < 1 &&
                count(RoleHasPermission::where('role_id', $roleHasPermission['role_id'])->get()) < 1
            ) {
                RoleHasPermission::create($roleHasPermission);
            }
        }
    }
}
