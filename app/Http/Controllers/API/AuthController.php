<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\RegisterResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Facades\Hash;
use App\Services\TokenService;
use Illuminate\Http\Request;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Регистрация нового пользователя.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'birthday' => $request->birthday,
        ]);
        return response()->json(new RegisterResource($user), 201);
    }

    /**
     * Авторизация пользователя.
     */
    public function login(LoginRequest $request, TwoFactorService $service)
{
    Log::info('Попытка входа', ['username' => $request->username]);

    $user = $this->authenticateUser($request->username, $request->password);

    if (!$user) {
        Log::warning('Ошибка аутентификации', ['username' => $request->username]);
        return response()->json([
            'message' => 'Invalid username or password.',
        ], 401);
    }

    Log::info('Аутентификация успешна', ['user_id' => $user->id]);

    if ($user->is_two_fa_enabled) {
        Log::info('2FA включён', ['user_id' => $user->id]);

        $deviceId = $request->header('Device-ID');
        Log::info('Device-ID', ['device_id' => $deviceId]);

        try {
            $code = $service->setCode($user, $deviceId);
            Log::info('2FA код сгенерирован', ['user_id' => $user->id, 'code' => $code]);

            $tempToken = $this->tokenService->generateTemporaryToken($user);
            Log::info('Временный токен создан', ['temp_token' => $tempToken]);

            return response()->json([
                'message' => '2FA code sent.',
                'requires_2fa' => true,
                'temp_token' => $tempToken,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка при генерации 2FA кода', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    Log::info('2FA не включён, выдаём основной токен', ['user_id' => $user->id]);

    try {
        $tokens = $this->tokenService->generateToken($user);
        Log::info('Основной токен создан', ['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user' => new AuthResource($user),
        ], 200);
    } catch (\Exception $e) {
        Log::error('Ошибка при генерации основного токена', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Internal Server Error'], 500);
    }
}


    /**
     * Проверка учетных данных пользователя.
     */
    protected function authenticateUser(string $username, string $password): ?User
    {
        $user = User::where('username', $username)->first();
        return ($user && Hash::check($password, $user->password)) ? $user : null;
    }

    /**
     * Получение информации о текущем пользователе.
     */
    public function me(Request $request) 
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Authorization token not provided'], 401);
        }
        $userToken = UserToken::where('token', $token)->first();
        if (!$userToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        return response()->json(new UserResource($userToken->user));
    }
    
    /**
     * Удаление текущего токена (выход из системы).
     */
    public function logout(Request $request)
    {
        UserToken::where('token', $request->bearerToken())->delete();
        return response()->json(['message' => 'Вы вышли из системы.'], 200);
    }

    /**
     * Удаление всех токенов пользователя (выход со всех устройств).
     */
    public function logoutAll(Request $request)
    {
        UserToken::where('user_id', $request->user->id)->delete();
        return response()->json(['message' => 'Все токены удалены.'], 200);
    }

    /**
     * Получение списка активных токенов пользователя.
     */
    public function tokens(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Authorization token not provided'], 401);
        }
        $userToken = UserToken::where('token', $token)->first();
        if (!$userToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        $tokens = UserToken::where('user_id', $userToken->user_id)->get();
        return response()->json($tokens);
    }
    
    /**
     * Изменение пароля пользователя.
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        // Проверяем, передается ли токен
        $token = $request->bearerToken();

        // Ищем токен в базе
        $userToken = UserToken::where('token', $token)->first();
        if (!$userToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        // Ищем пользователя по токену
        $user = User::find($userToken->user_id);
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Проверяем текущий пароль
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'The current password is incorrect.'], 400);
        }

        // Изменяем пароль
        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password successfully updated'], 200);
    }

     /**
     * Валидация токена.
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return response()->json([
                'message' => 'The Refresh token has not been provided.'
            ], 400);
        }

        try {
            $tokens = $this->tokenService->refreshAccessToken($refreshToken);

            return response()->json([
                'status' => 'success',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_at' => $tokens['expires_at'],
            ], 200);
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Обновление профиля авторизованного пользователя.
     * Проверяет наличие разрешения 'UPDATE_USER' и обновляет данные пользователя (username, email, birthday).
     */
    public function updateProfile(Request $request)
    {
        // Получаем текущего пользователя
        $user = $request->user();

        // Проверяем наличие разрешения 'UPDATE_USER'
        if (!$user->hasPermission('UPDATE_USER')) {
            return response()->json([
                'error' => 'У вас нет доступа к этой операции. Необходимое разрешение: UPDATE_USER'
            ], 403);
        }

        // Валидация входных данных
        $request->validate([
            'username' => 'string|max:255|alpha|regex:/^[A-Z]/',
            'email' => 'string|email|max:255|unique:users,email,' . $user->id,
            'birthday' => 'date|nullable',
        ]);

        // Обновляем данные пользователя
        $user->update($request->only(['username', 'email', 'birthday']));

        // Возвращаем обновленную информацию о пользователе
        return new UserResource($user);
    }
    public function requestTwoFactorCode(Request $request, TwoFactorService $service)
     {
        $tempToken = $request->header('Authorization');
        $tempToken = str_replace('Bearer ', '', $tempToken);
 
        $userToken = UserToken::where('token', $tempToken)->where('is_tmp', 1)->first();
 
        if (!$userToken) {
            return response()->json(['message' => 'Invalid or unauthorized request'], 401);
        }
 
        $user = $userToken->user;
        $deviceId = $request->header('Device-ID');
        $service->setCode($user, $deviceId);

        return response()->json(['message' => '2FA code resent.']);
     }
 
     // Подтверждение кода
     public function confirmTwoFactorCode(Request $request, TwoFactorService $service)
     {
         $user = $request->user();
         $code = $request->input('code');
         $deviceId = $request->header('Device-ID');
 
         if (!$user->is_two_fa_enabled || $user->two_fa_device_id !== $deviceId) { // Проверка на правильное устройство
             return response()->json(['message' => 'Invalid device or 2FA not enabled'], 400);
         }
 
         if (!$service->isValid($code, $user)) {
             return response()->json(['message' => 'Invalid or expired code'], 400);
         }
 
         $service->clearCode($user);
 
         return response()->json(['message' => '2FA verified successfully']);
     }
     // Включение/выключение 2FA
     public function toggleTwoFactor(Request $request)
     {
         $user = $request->user();
         $currentPassword = $request->input('password');
 
         if (!Hash::check($currentPassword, $user->password)) {
             return response()->json(['message' => 'Invalid password'], 400);
         }
 
         $user->is_two_fa_enabled = !$user->is_two_fa_enabled; // Переименовано поле
         $user->save();
 
         return response()->json([
             'message' => $user->is_two_fa_enabled ? '2FA enabled' : '2FA disabled',
         ]);
     }
 
     public function confirmLogin(Request $request, TwoFactorService $service)
     {
         $user = User::where('email', $request->input('email'))->first();
 
         // Если пользователь не найден или не включён 2FA
         if (!$user || !$user->is_two_fa_enabled) {
             return response()->json(['message' => 'Invalid request or 2FA not enabled.'], 400);
         }
 
         $code = $request->input('code');
         $deviceId = $request->header('Device-ID');
 
         // Проверяем код 2FA
         if (!$service->isValid($code, $user) || ($user->two_fa_device_id && $user->two_fa_device_id !== $deviceId)) {
             return response()->json(['message' => 'Invalid or expired code.'], 400);
         }
 
         // Очищаем код 2FA
         $service->clearCode($user);
 
         // Выдаём токен
         $tokens = $this->tokenService->generateToken($user);
 
         return response()->json([
             'status' => 'success',
             'access_token' => $tokens['access_token'],
             'refresh_token' => $tokens['refresh_token'],
             'user' => new AuthResource($user),
         ], 200);
     }
}
