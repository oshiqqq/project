<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class TokenService
{
    /**
     * Генерация нового access и refresh токенов для пользователя.
     * Проверяет лимит активных токенов перед созданием новых.
     */
    public function generateToken(User $user)
    {
        $this->checkTokenLimit($user); // Проверка, не превышен ли лимит токенов
        $accessToken = $this->createToken(); 
        $refreshToken = $this->createToken(); 
        $this->storeToken($user, $accessToken, $refreshToken); // Сохранение токенов в БД

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Проверка, не превышен ли лимит активных токенов пользователя.
     */
    protected function checkTokenLimit(User $user): void
    {
        $maxTokens = env('MAX_ACTIVE_TOKENS', 5); // Максимальное количество токенов из конфигурации
        $activeTokensCount = UserToken::where('user_id', $user->id)->count(); // Подсчет токенов пользователя

        if ($activeTokensCount >= $maxTokens) {
            throw new HttpResponseException(response()->json([
                'message' => 'The maximum number of active tokens has been exceeded.'
            ], 403));
        }
    }

    /**
     * Генерация случайного токена в безопасном формате.
     */
    protected function createToken(): string
    {
        $bytes = random_bytes(40);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); // Убираем ненужные символы
    }

    /**
     * Сохранение нового access и refresh токенов в базе данных.
     */
    protected function storeToken(User $user, $accessToken, $refreshToken)
    {
        $accessTokenExpiresAt = Carbon::now()->addMinutes((int)env('TOKEN_LIFETIME', 15)); // Время жизни access токена
        $refreshTokenExpiresAt = Carbon::now()->addDays((int)env('REFRESH_TOKEN_LIFETIME', 7)); // Время жизни refresh токена

        UserToken::create([
            'user_id' => $user->id,
            'token' => $accessToken,
            'expires_at' => $accessTokenExpiresAt,
            'refresh_token' => $refreshToken,
            'refresh_expires_at' => $refreshTokenExpiresAt,
        ]);
    }

    /**
     * Проверяет, истек ли срок действия access токена.
     * Если токен истек, он удаляется из базы данных.
     */
    public function isTokenExpired(UserToken $userToken)
    {
        $expiryTime = $userToken->expires_at;
        $currentTime = Carbon::now();

        if ($currentTime->gte($expiryTime)) {
            $userToken->delete();
            return true;
        }
        return false;
    }

    /**
     * Обновление access токена с использованием refresh токена.
     */
    public function refreshAccessToken($refreshToken)
    {
        $userToken = UserToken::where('refresh_token', $refreshToken)->first();

        if (!$userToken) {
            throw new HttpResponseException(response()->json([
                'message' => 'Invalid refresh token.'
            ], 401));
        }

        // Проверяем истечение срока действия refresh токена
        if ($this->isRefreshTokenExpired($userToken)) {
            $userToken->delete();
            throw new HttpResponseException(response()->json([
                'message' => 'The refresh token has expired.'
            ], 401));
        }

        // Генерируем новый access токен
        $newAccessToken = $this->createToken();
        $newAccessExpiresAt = Carbon::now()->addMinutes((int)env('TOKEN_LIFETIME', 15));

        // Генерируем новый refresh токен
        $newRefreshToken = $this->createToken();
        $newRefreshExpiresAt = Carbon::now()->addDays((int)env('REFRESH_TOKEN_LIFETIME', 7));

        // Обновляем данные токенов в БД
        $userToken->update([
            'token' => $newAccessToken,
            'expires_at' => $newAccessExpiresAt,
            'refresh_token' => $newRefreshToken,
            'refresh_expires_at' => $newRefreshExpiresAt,
        ]);

        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_at' => $newAccessExpiresAt,
        ];
    }

    /**
     * Проверяет, истек ли срок действия refresh токена.
     */
    public function isRefreshTokenExpired(UserToken $userToken)
    {
        $expiryTime = $userToken->refresh_expires_at;
        $currentTime = Carbon::now();

        return $currentTime->gte($expiryTime);
    }
}