<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
{
    /* 
    Преобразует данные связки пользователя и роли в массив для API-ответа 
    */
    public function toArray($request)
    {
        return [
            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'created_by' => $this->created_by,
        ];
    }
}