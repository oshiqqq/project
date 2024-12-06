<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InfoController;

// группа маршрутов с префиксом "info"
// каждый маршрут обрабатывается методом контроллера InfoController
Route::prefix('info')->group(function () {
    Route::get('/server', [InfoController::class, 'serverInfo']);
    Route::get('/client', [InfoController::class, 'clientInfo']);
    Route::get('/database', [InfoController::class, 'databaseInfo']);
});

Route::get('/', function () {
    return view('welcome');
});
