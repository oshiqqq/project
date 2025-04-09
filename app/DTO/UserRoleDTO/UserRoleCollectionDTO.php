<?php

namespace App\DTO\UserRoleDTO;

class UserRoleCollectionDTO
{
    private array $userRoleLinks;

    public function __construct(array $userRoleLinks)
    {
        $this->userRoleLinks = $userRoleLinks;
    }

    /**
     * Преобразует коллекцию связок пользователей и ролей в массив для использования в API-ответах.
     * Если элемент коллекции — массив, он фильтруется и преобразуется в UserRoleDTO.
     */
    public function toArray(): array
    {
        return array_map(function ($userRole) {
            // Если элемент — массив, фильтруем лишние ключи и создаем объект UserRoleDTO
            if (is_array($userRole)) {
                $userRoleData = array_intersect_key($userRole, array_flip(['user_id', 'role_id', 'created_by']));
                $userRole = new UserRoleDTO(
                    $userRoleData['user_id'] ?? null,
                    $userRoleData['role_id'] ?? null,
                    $userRoleData['created_by'] ?? null
                );
            }
            return $userRole->toArray();
        }, $this->userRoleLinks);
    }
}