<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Http\Requests\PermissionRequest\CreatePermissionRequest;
use App\Http\Requests\PermissionRequest\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\ChangeLogResource;
use App\DTO\PermissionDTO\PermissionDTO;
use App\DTO\PermissionDTO\PermissionCollectionDTO;
use App\DTO\ChangeLogDTO\ChangeLogDTO;
use App\DTO\ChangeLogDTO\ChangeLogCollectionDTO;
use Illuminate\Support\Facades\Auth;
use App\Models\ChangeLog;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    /** 
    *Получение списка всех разрешений
    */ 
    public function indexPermission()
    {
        $permissions = Permission::all()->toArray(); // Получаем все разрешения из базы данных
        $permissionCollectionDTO = new PermissionCollectionDTO($permissions); // Преобразуем в DTO коллекцию

        return response()->json($permissionCollectionDTO->toArray()); // Возвращаем данные в формате JSON
    }

    /** 
    * Получение конкретного разрешения по ID
    */
    public function showPermission($id)
    {
        // Извлекаем разрешение по id
        $permission = Permission::findOrFail($id);
        // Преобразуем модель разрешения в DTO
        $permissionDTO = new PermissionDTO(
            $permission->name,
            $permission->description,
            $permission->slug,
            $permission->created_by
        );

        // Возвращаем DTO через PermissionResource
        return new PermissionResource($permissionDTO);
    }

    /** 
    * Создание нового разрешения
    */ 
    public function storePermission(CreatePermissionRequest $request)
    {
        DB::beginTransaction(); // Начинаем транзакцию

        try {
            // Преобразуем данные запроса в DTO
            $permissionDTO = $request->toDTO();

            // Создаем разрешение в базе данных, используя данные из DTO
            $permission = Permission::create($permissionDTO->toArray());

            DB::commit(); // Подтверждаем транзакцию

            // Возвращаем созданное разрешение с кодом ответа 201
            return (new PermissionResource($permission))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack(); // Откатываем транзакцию в случае ошибки
            return response()->json(['message' => 'Failed to store permission'], 500);
        }
    }

    /** 
    * Обновление существующего разрешения
    */ 
    public function updatePermission(UpdatePermissionRequest $request, $id)
    {
        DB::beginTransaction(); // Начинаем транзакцию

        try {
            // Находим разрешение по ID
            $permission = Permission::findOrFail($id);
            // Получаем DTO из запроса и обновляем разрешение
            $permissionDTO = $request->toPermissionDTO();
            $permission->update($permissionDTO->toArray());

            DB::commit(); // Подтверждаем транзакцию

            // Возвращаем обновленное разрешение с кодом ответа 200
            return response()->json(new PermissionResource($permission), 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Откатываем транзакцию в случае ошибки
            return response()->json(['message' => 'Failed to update permission'], 500);
        }
    }

    /**
    *Жесткое удаление разрешения по ID
    */ 
    public function destroyPermission($id)
    {
        DB::beginTransaction(); // Начинаем транзакцию

        try {
            // Находим разрешение по ID
            $permission = Permission::find($id);

            // Проверяем, существует ли разрешение
            if (!$permission) {
                return response()->json(['message' => 'Permission not found'], 404);
            }

            // Выполняем жесткое удаление
            $permission->forceDelete();

            DB::commit(); // Подтверждаем транзакцию

            // Возвращаем сообщение об успешном удалении с кодом ответа 200
            return response()->json(['message' => 'Permission permanently deleted'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Откатываем транзакцию в случае ошибки
            return response()->json(['message' => 'Failed to delete permission'], 500);
        }
    }

    /**
    * Мягкое удаление разрешения
    */
    public function softDeletePermission($id)
    {
        // Находим разрешение по ID
        $permission = Permission::find($id);
        // Проверяем, существует ли разрешение
        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        // Устанавливаем `deleted_by` текущим пользователем перед мягким удалением
        $permission->deleted_by = Auth::id();
        $permission->save();

        // Выполняем мягкое удаление
        $permission->delete();

        // Возвращаем сообщение об успешном мягком удалении
        return response()->json(['message' => 'Permission soft deleted'], 200);
    }

    /** 
    *Восстановление мягко удаленного разрешения
    */ 
    public function restorePermission($id)
    {
        // Ищем удаленное разрешение
        $permission = Permission::onlyTrashed()->findOrFail($id);

        // Проверяем, существует ли разрешение
        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        // Сбрасываем поле `deleted_by`
        $permission->deleted_by = null;
        $permission->save();

        // Восстанавливаем разрешение
        $permission->restore();

        // Возвращаем сообщение об успешном восстановлении
        return response()->json(['message' => 'Permission restored'], 200);
    }

    /**
    * Получение истории изменения разрешения по ID
    */ 
    public function permissionStory($entityId)
    {
        // Извлекаем все записи истории изменений для разрешения по entity_id
        $permissions = ChangeLog::where('entity_type', 'permissions')
            ->where('entity_id', $entityId)
            ->get();

        // Преобразуем записи изменений в коллекцию DTO
        $permissionsDTOs = $permissions->map(function ($permissionLog) {
            return new ChangeLogDTO(
                $permissionLog->entity_type,
                $permissionLog->entity_id,
                $permissionLog->before,
                $permissionLog->after,
                $permissionLog->created_by,
            );
        })->toArray();

        // Создаем коллекцию изменений и возвращаем ее в ответ
        $changeLogCollectionDTO = new ChangeLogCollectionDTO($permissionsDTOs);

        return ($changeLogCollectionDTO->toArray() == null)
            ? response()->json(['message' => 'Permission not found'], 404)
            : response()->json(new ChangeLogResource($changeLogCollectionDTO->toArray()), 200);
    }
}
