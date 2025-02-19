<?php

use Illuminate\Support\Facades\Route;

// Страницы веб-интерфейса (только отображение форм)
Route::view('/register', 'auth.register')->name('register.form');
Route::view('/login', 'auth.login')->name('login.form');
Route::view('/home', 'home')->name('home');

// Главная страница
Route::get('/', function () {
    return view('welcome');
});

