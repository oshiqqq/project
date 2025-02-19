<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\RegisterResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\TokenService;
use Illuminate\Http\Exceptions\HttpResponseException;


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
    public function login(LoginRequest $request)
    {
        $user = $this->authenticateUser($request->username, $request->password);
        if (!$user) {
            return response()->json(['message' => 'Invalid username or password.'], 401);
        }

        // Генерируем токены с помощью сервиса 
        $tokens = $this->tokenService->generateToken($user);

        return response()->json([
            'status' => 'success',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user' => new AuthResource($user),
        ], 200);
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
    
}