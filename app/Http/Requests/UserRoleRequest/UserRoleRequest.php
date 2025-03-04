<?php

namespace App\Http\Requests\UserRoleRequest;

use Illuminate\Foundation\Http\FormRequest;
use App\DTO\UserRoleDTO\UserRoleDTO;

class UserRoleRequest extends FormRequest
{
    /* 
    Подготовка данных перед валидацией, добавление параметров из маршрута
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->route('user_id'),
            'role_id' => $this->route('role_id')
        ]);
    }

    /* 
    Правила валидации для связки пользователя и роли 
    */
    public function rules(): array
    {
        return [
            'user_id' => "required|exists:users,id",
            'role_id' => 'required|exists:roles,id',
        ];
    }

    /* 
    Преобразование данных запроса в UserRoleDTO
     */
    public function toDTO()
    {
        return new UserRoleDTO(
            (int) $this->route('user_id'),
            (int) $this->route('role_id'),
            (int) $this->user()->id
        );
    }
}