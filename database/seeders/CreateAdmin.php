<?php
 
 namespace Database\Seeders;
 
 use App\Models\User;
 use App\Models\Role;
 use Illuminate\Database\Console\Seeds\WithoutModelEvents;
 use Illuminate\Database\Seeder;
 use Illuminate\Support\Facades\Hash;
 
 class CreateAdmin extends Seeder
 {
     public function run()
     {
         User::firstOrCreate(
             ['email' => "chal300103@mail.ru"],
             [
                 'username' => "Chaladmin",
                 'password' => Hash::make("Woh4777."),
                 'birthday' => "2003-01-30",
             ]
         );
     }
 }