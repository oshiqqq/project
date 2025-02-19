<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс для возврата данных пользователя после регистрации.
 */
class RegisterResource extends JsonResource
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