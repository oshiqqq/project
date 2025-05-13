<?php

namespace App\Http\Requests\PermissionRequest;

use Illuminate\Foundation\Http\FormRequest;
use App\DTO\PermissionDTO\PermissionDTO;

class CreatePermissionRequest extends FormRequest
{
    /* 
    Правила валидации для создания разрешения 
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
    Преобразование валидированных данных в PermissionDTO 
    */
    public function toDTO()
    {
        return new PermissionDTO(
            $this->input('name'),
            $this->input('description'),
            $this->input('slug'),
            (int) $this->user()->id
        );
    }
}