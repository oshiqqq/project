<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /* 
    Создание начальных ролей: Admin, User, Guest 
    */
    public function run(): void
    {
        Role::create(['name' => 'Admin', 'description' => 'Administrator role', 'slug' => 'ADMIN', 'created_by' => 1]); 
        Role::create(['name' => 'User', 'description' => 'User role', 'slug' => 'USER', 'created_by' => 1]); 
        Role::create(['name' => 'Guest', 'description' => 'Guest role', 'slug' => 'GUEST', 'created_by' => 1]); 
    }
}