<?php

namespace App\Http\Requests\RolePermissionRequest;

use Illuminate\Foundation\Http\FormRequest;
use App\DTO\RolePermissionDTO\RolePermissionDTO;

class RolePermissionRequest extends FormRequest
{
    /* 
    Подготовка данных перед валидацией, добавление параметров из маршрута
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'permission_id' => $this->route('permission_id'),
            'role_id' => $this->route('role_id')
        ]);
    }

    /* 
    Правила валидации для связки роли и разрешения 
    */
    public function rules(): array
    {
        return [
            'permission_id' => 'required|exists:permissions,id',
            'role_id' => 'required|exists:roles,id',
        ];
    }

    /* 
    Преобразование данных запроса в RolePermissionDTO 
    */
    public function toDTO()
    {
        return new RolePermissionDTO(
            (int) $this->route('permission_id'),
            (int) $this->route('role_id'),
            (int) $this->user()->id
        );
    }
}