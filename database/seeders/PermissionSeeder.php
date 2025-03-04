<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /* 
    Создание списка разрешений для сущностей и действий 
    */
    public function run()
    {
        $entities = ['user', 'role', 'permission'];
        $actions = ['get-list', 'read', 'create', 'update', 'delete', 'restore'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::create([
                    'name' => "{$action}-{$entity}",
                    'description' => ucfirst($action) . " permission for {$entity}",
                    'slug' => strtoupper("{$action}_{$entity}"),
                    'created_by' => 1,
                ]);
            }
        }
    }
}