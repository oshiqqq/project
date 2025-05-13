<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RolePermissionRequest\RolePermissionRequest;
use App\Http\Resources\RolePermissionResource;
use App\Models\RolePermission;
use App\DTO\RolePermissionDTO\RolePermissionDTO;
use App\DTO\RolePermissionDTO\RolePermissionCollectionDTO;
use Illuminate\Support\Facades\Auth;

/**
 * Контроллер для управления связками ролей и разрешений через API.
 * Реализует получение списка связок, создание, удаление и восстановление связок.
 */
class RolePermissionController extends Controller
{
    /**
     * Возвращает список всех связок разрешений для указанной роли.
     */
    public function showRolePermission($roleId)
    {
        $rolePermissionLinks = RolePermission::where('role_id', $roleId)->get(); // Извлекаем все связи для роли

        $rolePermissionDTOList = $rolePermissionLinks->map(function ($rolePermission) {
            return new RolePermissionDTO(
                $rolePermission->permission_id,
                $rolePermission->role_id,
                $rolePermission->created_by
            );
        })->toArray(); // Преобразуем коллекцию моделей в массив DTO

        $rolePermissionCollectionDTO = new RolePermissionCollectionDTO($rolePermissionDTOList); // Оборачиваем в коллекцию DTO

        return ($rolePermissionCollectionDTO->toArray() == null)
            ? response()->json(['message' => 'Role and permission not found'], 404)
            : $rolePermissionCollectionDTO->toArray(); // Возвращаем результат или ошибку
    }

    /**
     * Создает новую связку роли и разрешения на основе данных запроса.
     */
    public function storeRolePermission(RolePermissionRequest $request)
    {
        $rolePermissionDTO = $request->toDTO(); // Получаем DTO из данных запроса
        $rolePermission = RolePermission::create($rolePermissionDTO->toArray()); // Создаем новую связку

        return (new RolePermissionResource($rolePermission))->response()->setStatusCode(201);
    }

    /**
     * Выполняет жесткое удаление связки роли и разрешения по ID.
     */
    public function destroyRolePermission($id)
    {
        $rolePermission = RolePermission::find($id); // Находим связку по ID

        if (!$rolePermission) {
            return response()->json(['message' => 'The permissions connection to the role was not found'], 404);
        }

        $rolePermission->forceDelete(); // Выполняем жесткое удаление

        return response()->json(['message' => 'The permissions connection to the role permanently deleted'], 200);
    }

    /**
     * Выполняет мягкое удаление связки роли и разрешения по ID.
     */
    public function softDeleteRolePermission($id)
    {
        $rolePermission = RolePermission::find($id); // Находим связку по ID

        if (!$rolePermission) {
            return response()->json(['message' => 'The permissions connection to the role was not found'], 404);
        }

        $rolePermission->deleted_by = Auth::id(); // Устанавливаем текущего пользователя как удалившего
        $rolePermission->save();

        $rolePermission->delete(); // Выполняем мягкое удаление

        return response()->json(['message' => 'The permissions connection to the role soft deleted'], 200);
    }

    /**
     * Восстанавливает мягко удаленную связку роли и разрешения по ID.
     */
    public function restoreRolePermission($id)
    {
        $rolePermission = RolePermission::onlyTrashed()->findOrFail($id); // Находим удаленную связку по ID

        if (!$rolePermission) {
            return response()->json(['message' => 'The permissions connection to the role was not found'], 404);
        }

        $rolePermission->deleted_by = null; // Сбрасываем поле удаления
        $rolePermission->save();

        $rolePermission->restore(); // Восстанавливаем связку

        return response()->json(['message' => 'The permissions connection to the role restored'], 200);
    }
}