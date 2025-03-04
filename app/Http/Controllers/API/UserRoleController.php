<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRoleRequest\UserRoleRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserRoleResource;
use App\Models\User;
use App\Models\UserRole;
use App\DTO\UserDTO\UserCollectionDTO;
use App\DTO\UserDTO\UserDTO;
use Illuminate\Support\Facades\Auth;

/**
 * Контроллер для управления связками пользователей и ролей, а также получения данных пользователей через API.
 * Реализует получение списка пользователей, создание, удаление и восстановление связок.
 */
class UserRoleController extends Controller
{
    /**
     * Возвращает список всех пользователей.
     */
    public function indexUser()
    {
        $userList = User::all()->toArray(); // Получаем массив пользователей из базы данных
        $userCollectionDTO = new UserCollectionDTO($userList); // Создаем коллекцию DTO

        return response()->json($userCollectionDTO->toArray()); // Возвращаем JSON
    }

    /**
     * Возвращает данные конкретного пользователя по его ID.
     */
    public function showUser($id)
    {
        $user = User::findOrFail($id); // Извлекаем пользователя по ID
        $userDTO = new UserDTO(
            $user->id,
            $user->username,
            $user->email,
            $user->birthday
        ); // Преобразуем модель User в DTO

        return new UserResource($userDTO); // Возвращаем DTO через UserResource
    }

    /**
     * Создает новую связку пользователя и роли на основе данных запроса.
     */
    public function storeUserRole(UserRoleRequest $request)
    {
        $userRoleDTO = $request->toDTO(); // Получаем DTO из данных запроса
        $userRoleLink = UserRole::create($userRoleDTO->toArray()); // Создаем новую связку

        return response()->json([
            'message' => 'User role created successfully',
            'data' => (new UserRoleResource($userRoleLink))->resolve()
        ], 201);
    }

    /**
     * Выполняет жесткое удаление связки пользователя и роли по ID.
     */
    public function destroyUserRole($id)
    {
        $userRoleLink = UserRole::find($id); // Находим связку по ID

        if (!$userRoleLink) {
            return response()->json(['message' => 'The users connection to the role was not found'], 404);
        }

        $userRoleLink->forceDelete(); // Выполняем жесткое удаление

        return response()->json(['message' => 'The users connection to the role permanently deleted'], 200);
    }

    /**
     * Выполняет мягкое удаление связки пользователя и роли по ID.
     */
    public function softDeleteUserRole($id)
    {
        $userRoleLink = UserRole::find($id); // Находим связку по ID

        if (!$userRoleLink) {
            return response()->json(['message' => 'The users connection to the role was not found'], 404);
        }

        $userRoleLink->deleted_by = Auth::id(); // Устанавливаем текущего пользователя как удалившего
        $userRoleLink->save();

        $userRoleLink->delete(); // Выполняем мягкое удаление

        return response()->json(['message' => 'The users connection to the role soft deleted'], 200);
    }

    /**
     * Восстанавливает мягко удаленную связку пользователя и роли по ID.
     */
    public function restoreUserRole($id)
    {
        $userRoleLink = UserRole::onlyTrashed()->findOrFail($id); // Находим удаленную связку по ID

        if (!$userRoleLink) {
            return response()->json(['message' => 'The users connection to the role was not found'], 404);
        }

        $userRoleLink->deleted_by = null; // Сбрасываем поле удаления
        $userRoleLink->save();

        $userRoleLink->restore(); // Восстанавливаем связку

        return response()->json(['message' => 'The users connection to the role restored'], 200);
    }
}