<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AttendanceController;

Route::post('/attendance', [AttendanceController::class, 'upload']);