<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class CreateAdminRole extends Seeder
{
    public function run(): void
    {
        // Ищем пользователя с нужным name
        $user = User::where('username', 'chal300103adm')->first();
        // Ищем роль с кодом 'ADMIN'
        $adminRole = Role::where('slug', 'ADMIN')->first();
        // Если пользователь и роль существуют, связываем их
        if ($user && $adminRole) {
            $user->roles()->attach($adminRole->id, ['created_by' => $user->id]); // Привязываем роль к пользователю
        }
    }
}
