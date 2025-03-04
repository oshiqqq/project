<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\UserRoleController;
use App\Http\Controllers\API\RolePermissionController;
use App\Http\Middleware\CheckPermission;

/* 
Открытые маршруты для аутентификации 
*/
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

/* 
Защищенные маршруты с проверкой токена 
*/
Route::middleware(['auth.custom'])->group(function () {
    /* 
    Управление аутентификацией 
    */
    Route::get('/auth/me', [AuthController::class, 'me']); /* Получение данных текущего пользователя */
    Route::post('/auth/out', [AuthController::class, 'logout']); /* Выход из текущей сессии */
    Route::get('/auth/tokens', [AuthController::class, 'tokens']); /* Получение списка токенов */
    Route::post('/auth/out_all', [AuthController::class, 'logoutAll']); /* Выход со всех устройств */
    Route::post('/auth/change_password', [AuthController::class, 'changePassword']); /* Смена пароля */
    Route::post('/auth/refresh', [AuthController::class, 'refresh']); /* Обновление токена */
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']); /* Обновление профиля */

    /* 
    Роли 
    */
    Route::get('/policy/role', [RoleController::class, 'indexRole'])
        ->middleware(CheckPermission::class . ':GET-LIST_ROLE'); /* Список всех ролей */
    Route::get('/policy/role/{id}', [RoleController::class, 'showRole'])
        ->middleware(CheckPermission::class . ':READ_ROLE'); /* Просмотр роли */
    Route::post('/policy/role', [RoleController::class, 'storeRole'])
        ->middleware(CheckPermission::class . ':CREATE_ROLE'); /* Создание роли */
    Route::put('/policy/role/{id}', [RoleController::class, 'updateRole'])
        ->middleware(CheckPermission::class . ':UPDATE_ROLE'); /* Обновление роли */
    Route::delete('/policy/role/{id}', [RoleController::class, 'destroyRole'])
        ->middleware(CheckPermission::class . ':DELETE_ROLE'); /* Жесткое удаление роли */
    Route::delete('/policy/role/{id}/soft', [RoleController::class, 'softDeleteRole'])
        ->middleware(CheckPermission::class . ':DELETE_ROLE'); /* Мягкое удаление роли */
    Route::post('/policy/role/{id}/restore', [RoleController::class, 'restoreRole'])
        ->middleware(CheckPermission::class . ':RESTORE_ROLE'); /* Восстановление роли */

    /* 
    Разрешения 
    */
    Route::get('/policy/permission', [PermissionController::class, 'indexPermission'])
        ->middleware(CheckPermission::class . ':GET-LIST_PERMISSION'); /* Список всех разрешений */
    Route::get('/policy/permission/{id}', [PermissionController::class, 'showPermission'])
        ->middleware(CheckPermission::class . ':READ_PERMISSION'); /* Просмотр разрешения */
    Route::post('/policy/permission', [PermissionController::class, 'storePermission'])
        ->middleware(CheckPermission::class . ':CREATE_PERMISSION'); /* Создание разрешения */
    Route::put('/policy/permission/{id}', [PermissionController::class, 'updatePermission'])
        ->middleware(CheckPermission::class . ':UPDATE_PERMISSION'); /* Обновление разрешения */
    Route::delete('/policy/permission/{id}', [PermissionController::class, 'destroyPermission'])
        ->middleware(CheckPermission::class . ':DELETE_PERMISSION'); /* Жесткое удаление разрешения */
    Route::delete('/policy/permission/{id}/soft', [PermissionController::class, 'softDeletePermission'])
        ->middleware(CheckPermission::class . ':DELETE_PERMISSION'); /* Мягкое удаление разрешения */
    Route::post('/policy/permission/{id}/restore', [PermissionController::class, 'restorePermission'])
        ->middleware(CheckPermission::class . ':RESTORE_PERMISSION'); /* Восстановление разрешения */

    /* 
    Пользователи 
    */
    Route::get('/policy/users', [UserRoleController::class, 'indexUser'])
        ->middleware(CheckPermission::class . ':GET-LIST_USER'); /* Список всех пользователей */
    Route::get('/policy/user/{id}', [UserRoleController::class, 'showUser'])
        ->middleware(CheckPermission::class . ':READ_USER'); /* Просмотр пользователя */

    /* 
    Связи пользователей и ролей 
    */
    Route::post('/policy/user/{user_id}/role/{role_id}', [UserRoleController::class, 'storeUserRole'])
        ->middleware(CheckPermission::class . ':CREATE_USER'); /* Создание связи пользователя и роли */
    Route::delete('/policy/userRole/{id}', [UserRoleController::class, 'destroyUserRole'])
        ->middleware(CheckPermission::class . ':DELETE_USER'); /* Жесткое удаление связи */
    Route::delete('/policy/userRole/{id}/soft', [UserRoleController::class, 'softDeleteUserRole'])
        ->middleware(CheckPermission::class . ':DELETE_USER'); /* Мягкое удаление связи */
    Route::post('/policy/userRole/{id}/restore', [UserRoleController::class, 'restoreUserRole'])
        ->middleware(CheckPermission::class . ':RESTORE_USER'); /* Восстановление связи */

    /* Связи ролей и разрешений */
    Route::get('/policy/rolePermission/{role_id}', [RolePermissionController::class, 'showRolePermission'])
        ->middleware(CheckPermission::class . ':READ_ROLE'); /* Список разрешений для роли */
    Route::post('/policy/role/{role_id}/permission/{permission_id}', [RolePermissionController::class, 'storeRolePermission'])
        ->middleware(CheckPermission::class . ':CREATE_ROLE'); /* Создание связи роли и разрешения */
    Route::delete('/policy/rolePermission/{id}', [RolePermissionController::class, 'destroyRolePermission'])
        ->middleware(CheckPermission::class . ':DELETE_ROLE'); /* Жесткое удаление связи */
    Route::delete('/policy/rolePermission/{id}/soft', [RolePermissionController::class, 'softDeleteRolePermission'])
        ->middleware(CheckPermission::class . ':DELETE_ROLE'); /* Мягкое удаление связи */
    Route::post('/policy/rolePermission/{id}/restore', [RolePermissionController::class, 'restoreRolePermission'])
        ->middleware(CheckPermission::class . ':RESTORE_ROLE'); /* Восстановление связи */
});