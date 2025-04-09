<?php

namespace App\Http\Requests\PermissionRequest;

use Illuminate\Foundation\Http\FormRequest;
use App\DTO\PermissionDTO\PermissionDTO;

class UpdatePermissionRequest extends FormRequest
{
    /* 
    Правила валидации для обновления разрешения 
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
    Преобразование валидированных данных в PermissionDTO 
    */
    public function toPermissionDTO(): PermissionDTO
    {
        $data = $this->validated(); // Используем данные после валидации

        return new PermissionDTO(
            $data['name'] ?? null,
            $data['slug'] ?? null,
            $data['description'] ?? null,
            $this->user()->id
        );
    }
}