<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки наличия у пользователя конкретного разрешения.
 * Возвращает ошибку 403, если разрешение отсутствует.
 */
class CheckPermission
{
    /**
     * Обрабатывает входящий запрос, проверяя наличие указанного разрешения.
     */
    public function handle(Request $request, Closure $next, string $permissionSlug): Response
    {
        if (!$request->user()->hasPermission($permissionSlug)) {
            return response()->json([
                'error' => "У вас нет доступа к данной операции. Необходимое разрешение: {$permissionSlug}"
            ], 403);
        }

        return $next($request);
    }
}