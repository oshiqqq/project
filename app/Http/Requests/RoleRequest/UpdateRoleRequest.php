<?php

namespace App\Http\Requests\RoleRequest;

use Illuminate\Foundation\Http\FormRequest;
use App\DTO\RoleDTO\RoleDTO;

class UpdateRoleRequest extends FormRequest
{
    /* 
    Правила валидации для обновления роли 
    */
    public function rules(): array
    {
        $roleId = $this->route('id');
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $roleId,
            'slug' => 'required|string|max:50|unique:roles,slug,' . $roleId,
            'description' => 'nullable|string|max:1000',
        ];
    }

    /* 
    Преобразование валидированных данных в RoleDTO 
    */
    public function toRoleDTO(): RoleDTO
    {
        $data = $this->validated(); // Используем данные после валидации 

        return new RoleDTO(
            $data['name'] ?? null,
            $data['slug'] ?? null,
            $data['description'] ?? null,
            $this->user()->id
        );
    }
}