<?php

namespace App\Http\Requests\RoleRequest;

use Illuminate\Foundation\Http\FormRequest;
use App\DTO\RoleDTO\RoleDTO;

class CreateRoleRequest extends FormRequest
{
    /* 
    Правила валидации для создания роли 
    */
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:roles,name|max:255',
            'slug' => 'required|string|unique:roles,slug|max:50',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /* 
    Преобразование данных запроса в RoleDTO 
    */
    public function toDTO()
    {
        return new RoleDTO(
            $this->input('name'),
            $this->input('description'),
            $this->input('slug'),
            (int) $this->user()->id
        );
    }
}