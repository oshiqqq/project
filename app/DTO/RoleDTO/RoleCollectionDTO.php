<?php

namespace App\DTO\RoleDTO;

class RoleCollectionDTO
{
    private array $roles;
    
    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    /**
     * Преобразует коллекцию ролей в массив для использования в API-ответах.
     * Если элемент коллекции — массив, он фильтруется и преобразуется в RoleDTO.
     */
    public function toArray(): array
    {
        return array_map(function ($role) {
            // Если элемент — массив, фильтруем лишние ключи и создаем объект RoleDTO
            if (is_array($role)) {
                $roleData = array_intersect_key($role, array_flip(['name', 'description', 'slug', 'created_by']));
                $role = new RoleDTO(...$roleData);
            }
            return $role->toArray();
        }, $this->roles);
    }
}