<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RolePermissionResource extends JsonResource
{
    /* 
    Преобразует данные связки роли и разрешения в массив для API-ответа
    */
    public function toArray($request)
    {
        return [
            'role_id' => $this->role_id,
            'permission_id' => $this->permission_id,
            'created_by' => $this->created_by,
        ];
    }
}