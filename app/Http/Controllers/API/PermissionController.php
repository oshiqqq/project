<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Http\Requests\PermissionRequest\CreatePermissionRequest;
use App\Http\Requests\PermissionRequest\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\DTO\PermissionDTO\PermissionDTO;
use App\DTO\PermissionDTO\PermissionCollectionDTO;
use Illuminate\Support\Facades\Auth;

/**
 * Контроллер для управления разрешениями через API.
 * Реализует CRUD-операции, мягкое удаление и восстановление разрешений.
 */
class PermissionController extends Controller
{
    /**
     * Возвращает список всех разрешений.
     */
    public function indexPermission()
    {
        $permissionList = Permission::all()->toArray(); // Получаем массив разрешений из базы данных
        $permissionCollectionDTO = new PermissionCollectionDTO($permissionList); // Создаем коллекцию DTO

        return response()->json($permissionCollectionDTO->toArray()); // Возвращаем JSON
    }

    /**
     * Возвращает данные конкретного разрешения по его ID.
     */
    public function showPermission($id)
    {
        $permission = Permission::findOrFail($id); // Извлекаем разрешение по ID
        $permissionDTO = new PermissionDTO(
            $permission->name,
            $permission->
            , 
            $permission->description,
            $permission->created_by
        ); // Преобразуем модель Permission в DTO

        return new PermissionResource($permissionDTO); // Возвращаем DTO через PermissionResource
    }

    /**
     * Создает новое разрешение на основе данных запроса.
     */
    public function storePermission(CreatePermissionRequest $request)
    {
        $permissionDTO = $request->toDTO(); // Получаем DTO из данных запроса
        $permission = Permission::create($permissionDTO->toArray()); // Создаем новое разрешение

        return (new PermissionResource($permission))->response()->setStatusCode(201);
    }

    /**
     * Обновляет существующее разрешение на основе данных запроса.
     */
    public function updatePermission(UpdatePermissionRequest $request, $id)
    {
        $permission = Permission::findOrFail($id); // Находим разрешение по ID
        $permissionDTO = $request->toPermissionDTO(); 
        $permission->update($permissionDTO->toArray()); // Обновляем данные разрешения

        return response()->json(new PermissionResource($permission), 200);
    }

    /**
     * Выполняет жесткое удаление разрешения по ID.
     */
    public function destroyPermission($id)
    {
        $permission = Permission::find($id); // Находим разрешение по ID

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        $permission->forceDelete(); // Выполняем жесткое удаление

        return response()->json(['message' => 'Permission permanently deleted'], 200);
    }

    /**
     * Выполняет мягкое удаление разрешения по ID.
     */
    public function softDeletePermission($id)
    {
        $permission = Permission::find($id); // Находим разрешение по ID

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        $permission->deleted_by = Auth::id(); // Устанавливаем текущего пользователя как удалившего
        $permission->save();

        $permission->delete(); // Выполняем мягкое удаление

        return response()->json(['message' => 'Permission soft deleted'], 200);
    }

    /**
     * Восстанавливает мягко удаленное разрешение по ID.
     */
    public function restorePermission($id)
    {
        $permission = Permission::onlyTrashed()->findOrFail($id); // Находим удаленное разрешение по ID

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        $permission->deleted_by = null; // Сбрасываем поле удаления
        $permission->save();

        $permission->restore(); // Восстанавливаем разрешение

        return response()->json(['message' => 'Permission restored'], 200);
    }
}