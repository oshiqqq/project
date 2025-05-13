<?php

namespace App\DTO\UserRoleDTO;

class UserRoleDTO
{
    public int $user_id;
    public int $role_id;
    public int $created_by;

    public function __construct(int $user_id, int $role_id, int $created_by)
    {
        $this->user_id = $user_id;
        $this->role_id = $role_id;
        $this->created_by = $created_by;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'created_by' => $this->created_by,
        ];
    }
}