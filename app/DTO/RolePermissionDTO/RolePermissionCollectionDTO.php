<?php

namespace App\DTO\RolePermissionDTO;

class RolePermissionCollectionDTO
{
    private array $rolePermissionLinks;

    public function __construct(array $rolePermissionLinks)
    {
        $this->rolePermissionLinks = $rolePermissionLinks;
    }
    
    /**
     * Преобразует коллекцию связок ролей и разрешений в массив для использования в API-ответах.
     */
    public function toArray(): array
    {
        return array_map(fn(RolePermissionDTO $rolePermission) => $rolePermission->toArray(), $this->rolePermissionLinks);
    }
}