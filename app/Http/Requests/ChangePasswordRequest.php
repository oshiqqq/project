<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Правила валидации для изменения пароля.
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:8', 
                 // Пароль должен содержать как минимум одну строчную букву, одну заглавную букву, одну цифру и один специальный символ.
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).+$/', 
            ],
            'confirm_password' => ['required', 'same:new_password'],
        ];
    }

    /**
     * Сообщения об ошибках валидации.
     */
    public function messages(): array
    {
        return [
            'new_password.regex' => 'Пароль должен содержать минимум одну заглавную букву, одну строчную букву, одну цифру и один специальный символ.',
        ];
    }

    /**
     * Обработка неудачной валидации.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}