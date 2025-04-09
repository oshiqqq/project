<?php

namespace App\DTO\RolePermissionDTO;

class RolePermissionDTO
{
    public int $permission_id;
    public int $role_id;
    public int $created_by;

    public function __construct(int $permission_id, int $role_id, int $created_by)
    {
        $this->permission_id = $permission_id;
        $this->role_id = $role_id;
        $this->created_by = $created_by;
    }

    public function toArray(): array
    {
        return [
            'permission_id' => $this->permission_id,
            'role_id' => $this->role_id,
            'created_by' => $this->created_by,
        ];
    }
}