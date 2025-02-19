<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс для возврата данных пользователя при авторизации.
 */
class AuthResource extends JsonResource
{
    /**
     * Преобразует ресурс в массив.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id, 
            'username' => $this->username, 
            'email' => $this->email, 
            'birthday' => $this->birthday, 
        ];
    }
}