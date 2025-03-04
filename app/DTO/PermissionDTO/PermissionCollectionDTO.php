<?php

namespace App\DTO\PermissionDTO;

class PermissionCollectionDTO
{
    private array $permissions;

    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Преобразует коллекцию разрешений в массив для использования в API-ответах.
     */
    public function toArray(): array
    {
        return array_map(function ($permission) {
            // Если элемент — массив, фильтруем лишние ключи и создаем объект PermissionDTO
            if (is_array($permission)) {
                $permissionData = array_intersect_key($permission, array_flip(['name', 'description', 'slug', 'created_by']));
                $permission = new PermissionDTO(...$permissionData);
            }
            return $permission->toArray();
        }, $this->permissions);
    }
}