<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;

class RolePermissionSeeder extends Seeder
{
    /* 
    Привязка разрешений к ролям (Admin, User, Guest) 
    */
    public function run()
    {
        $adminRole = Role::where('slug', 'ADMIN')->first(); 
        $userRole = Role::where('slug', 'USER')->first(); 
        $guestRole = Role::where('slug', 'GUEST')->first(); 

        $allPermissions = Permission::all(); 

        foreach ($allPermissions as $permission) { 
            RolePermission::create([
                'role_id' => $adminRole->id,
                'permission_id' => $permission->id,
                'created_by' => 1,
            ]);
        }
        // Разрешения для User 
        $userPermissions = Permission::whereIn('slug', [ 
            'GET-LIST_USER',
            'READ_USER',
            'UPDATE_USER',
        ])->get();

        foreach ($userPermissions as $permission) {
            RolePermission::create([
                'role_id' => $userRole->id,
                'permission_id' => $permission->id,
                'created_by' => 1,
            ]);
        }
        // Разрешение для Guest 
        $guestPermissions = Permission::where('slug', 'GET-LIST_USER')->get(); 

        foreach ($guestPermissions as $permission) {
            RolePermission::create([
                'role_id' => $guestRole->id,
                'permission_id' => $permission->id,
                'created_by' => 1,
            ]);
        }
    }
}