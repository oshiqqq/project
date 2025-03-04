<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Http\Requests\RoleRequest\CreateRoleRequest;
use App\Http\Requests\RoleRequest\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\DTO\RoleDTO\RoleDTO;
use App\DTO\RoleDTO\RoleCollectionDTO;
use Illuminate\Support\Facades\Auth;

/**
 * Контроллер для управления ролями через API.
 * Реализует CRUD-операции, мягкое удаление и восстановление ролей.
 */
class RoleController extends Controller
{
    /**
     * Возвращает список всех ролей.
     */
    public function indexRole()
    {
        $roleList = Role::all()->toArray(); // Получаем массив ролей из базы данных
        $roleCollectionDTO = new RoleCollectionDTO($roleList); // Создаем коллекцию DTO

        return response()->json($roleCollectionDTO->toArray()); // Возвращаем JSON
    }

    /**
     * Возвращает данные конкретной роли по ее ID.
     */
    public function showRole($id)
    {
        $role = Role::findOrFail($id); // Извлекаем роль по ID
        $roleDTO = new RoleDTO(
            $role->name,
            $role->slug, // Предполагается, что в модели используется slug вместо code
            $role->description,
            $role->created_by
        ); // Преобразуем модель Role в DTO

        return new RoleResource($roleDTO); // Возвращаем DTO через RoleResource
    }

    /**
     * Создает новую роль на основе данных запроса.
     */
    public function storeRole(CreateRoleRequest $request)
    {
        $roleDTO = $request->toDTO(); // Получаем DTO из данных запроса
        $role = Role::create($roleDTO->toArray()); // Создаем новую роль

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    /**
     * Обновляет существующую роль на основе данных запроса.
     */
    public function updateRole(UpdateRoleRequest $request, $id)
    {
        $role = Role::findOrFail($id); // Находим роль по ID
        $roleDTO = $request->toRoleDTO(); // Получаем DTO из запроса 
        $role->update($roleDTO->toArray()); // Обновляем данные роли

        return response()->json(new RoleResource($role), 200);
    }

    /**
     * Выполняет жесткое удаление роли по ID.
     */
    public function destroyRole($id)
    {
        $role = Role::find($id); // Находим роль по ID

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->forceDelete(); // Выполняем жесткое удаление

        return response()->json(['message' => 'Role permanently deleted'], 200);
    }

    /**
     * Выполняет мягкое удаление роли по ID.
     */
    public function softDeleteRole($id)
    {
        $role = Role::find($id); // Находим роль по ID

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->deleted_by = Auth::id(); // Устанавливаем текущего пользователя как удалившего
        $role->save();

        $role->delete(); // Выполняем мягкое удаление

        return response()->json(['message' => 'Role soft deleted'], 200);
    }

    /**
     * Восстанавливает мягко удаленную роль по ID.
     */
    public function restoreRole($id)
    {
        $role = Role::onlyTrashed()->findOrFail($id); // Находим удаленную роль по ID

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->deleted_by = null; // Сбрасываем поле удаления
        $role->save();

        $role->restore(); // Восстанавливаем роль

        return response()->json(['message' => 'Role restored'], 200);
    }
}