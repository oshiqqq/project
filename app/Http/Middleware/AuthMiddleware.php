<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UserToken;
use App\Services\TokenService;

class AuthMiddleware
{
    protected TokenService $tokenService;

    /**
     * Конструктор для внедрения зависимости TokenService.
     */
    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Обрабатывает входящий запрос.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем наличие заголовка Authorization
        $authorizationHeader = $request->header('Authorization');
        if (!$authorizationHeader) {
            return response()->json(['message' => 'Authorization token not provided'], Response::HTTP_UNAUTHORIZED);
        }

        // Извлекаем токен из заголовка
        $token = str_replace('Bearer ', '', $authorizationHeader);

        // Ищем токен в базе данных
        $userToken = UserToken::where('token', $token)->first();
        if (!$userToken) {
            return response()->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        // Проверяем, истек ли срок действия токена
        if ($this->tokenService->isTokenExpired($userToken)) {
            return response()->json(['message' => 'Token expired'], Response::HTTP_UNAUTHORIZED);
        }

        // Устанавливаем пользователя в запрос для дальнейшего использования
        $request->attributes->set('user', $userToken->user);

        // Передаем управление следующему middleware
        return $next($request);
    }
}