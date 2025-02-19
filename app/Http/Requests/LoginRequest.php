<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    /**
     * Правила валидации для авторизации.
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:7', 
                'alpha', 
                'regex:/^[A-Z].*/', 
            ],
            'password' => [
                'required',
                'string',
                'min:8', 
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).+$/', 
            ],
        ];
    }

    /**
     * Сообщения об ошибках валидации.
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'Имя пользователя должно начинаться с заглавной буквы.',
            'password.regex' => 'Пароль должен содержать минимум одну заглавную букву, одну строчную букву, одну цифру и один специальный символ.',
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