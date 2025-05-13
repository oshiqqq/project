<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon; // Добавляем Carbon для работы с датами

class RegisterRequest extends FormRequest
{
    /**
     * Правила валидации для регистрации.
     */
    public function rules(): array
    {
        // Вычисляем дату, которая была 14 лет назад от текущего момента
        $fourteenYearsAgo = Carbon::today()->subYears(14)->toDateString();

        return [
            'username' => [
                'required',
                'string',
                'min:7',
                'alpha',
                'regex:/^[A-Z]/',
                'unique:users,username',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).+$/',
            ],
            'c_password' => [
                'required',
                'same:password',
            ],
            'birthday' => [
                'required',
                'date',
                'date_format:Y-m-d',
                "before:$fourteenYearsAgo", 
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
            'birthday.before' => 'Возраст должен быть больше 14 лет.', // Сообщение для возраста
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