<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $rolesPermissions = [
            ['name' => 'Root'],
            ['name' => 'Admin'],
            ['name' => 'Partner'],
            ['name' => 'Company'],
            ['name' => 'Teller'],
            ['name' => 'Subadmin'],
            ['name' => 'Officer'],
            ['name' => 'Staff'],
            ['name' => 'Customer'],
        ];

        foreach ($rolesPermissions as $key => $permission) {

            if (count(Role::where('name', $permission)->get()) < 1) {
                $r6 = Role::create($permission);
            }
        }
    }
}
