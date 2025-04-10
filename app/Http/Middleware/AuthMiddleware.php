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
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['message' => 'Authorization token not provided'], 401);
        }

        $token = str_replace('Bearer ', '', $token);
        $userToken = UserToken::where('token', $token)->first();

        if (!$userToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        // Проверка на истечение срока действия токена
        if ($this->tokenService->isTokenExpired($userToken)) {
            return response()->json(['message' => 'Token expired'], 401);
        }

        // Проверка, если токен временный (is_tmp = 1)
        if ($userToken->is_tmp) {
            return response()->json(['message' => 'Temporary token. 2FA verification required.'], 403);
        }

        // Авторизация пользователя
        Auth::setUser($userToken->user);
        $request->merge(['user' => $userToken->user]);

        return $next($request);
    }
}