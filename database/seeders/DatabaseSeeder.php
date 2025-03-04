<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /* 
    Заполнение базы данных начальными данными 
    */
    public function run(): void
    {
        // User::factory(10)->create(); // Создание 10 тестовых пользователей через фабрику 
        User::factory()->create([
            'username' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}