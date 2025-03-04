<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UserToken;
use App\Services\TokenService;

/**
 * Middleware для проверки аутентификации пользователя по токену.
 * Устанавливает авторизованного пользователя в запрос и систему аутентификации.
 */
class AuthMiddleware
{
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Обрабатывает входящий запрос, проверяя токен авторизации.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['message' => 'Authorization token not provided'], 401);
        }

        $token = str_replace('Bearer ', '', $token); // Удаляем префикс Bearer
        $userToken = UserToken::where('token', $token)->first();

        if (!$userToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if ($this->tokenService->isTokenExpired($userToken)) {
            return response()->json(['message' => 'Token expired'], 401);
        }

        Auth::setUser($userToken->user); // Устанавливаем пользователя в систему аутентификации
        $request->merge(['user' => $userToken->user]); // Добавляем пользователя в запрос

        return $next($request);
    }
}