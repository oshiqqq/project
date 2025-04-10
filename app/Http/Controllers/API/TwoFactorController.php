<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserToken;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Hash;
use App\Services\TokenService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TwoFactorController extends Controller
{
    
  //Экземпляр сервиса двухфакторной аутентификации

  protected $twoFactorService;

  public function __construct(TwoFactorService $twoFactorService)
  {
    $this->twoFactorService = $twoFactorService;
  }

  /**
   * Запрос нового 2FA-кода.
   */
  public function requestNewCode(Request $request)
  {
    $tempToken = $request->bearerToken();

    // Проверяем временный токен
    $userToken = UserToken::where('token', $tempToken)->where('is_tmp', 1)->first();
    if (!$userToken) {
      return response()->json(['message' => 'Invalid or expired temporary token'], 401);
    }

    // Проверяем срок действия временного токена
    if (now()->greaterThan($userToken->expires_at)) {
      return response()->json(['message' => 'Temporary token expired'], 401);
    }

    $user = $userToken->user;
    $deviceId = $request->header('Device-ID');

    // Лимит запросов на получение кода
    $maxRequests = 3;
    $delaySeconds = 30;
    $currentTime = Carbon::now();

    // Если прошло больше установленного времени, сбросить счётчик
    if ($user->last_request_time && ($currentTime->timestamp - $user->last_request_time->timestamp) > $delaySeconds) {
      $user->code_request_count = 0;
    }

    // Проверяем превышение лимита запросов
    if ($user->code_request_count >= $maxRequests) {
      $secondsSinceLastRequest = $currentTime->timestamp - $user->last_request_time->timestamp;
      if ($secondsSinceLastRequest < $delaySeconds) {
        return response()->json([
          'message' => 'Too many requests, please try again in ' . ceil($delaySeconds - $secondsSinceLastRequest) . ' seconds.'
        ], 429);
      } else {
        // Если задержка прошла, сбрасываем счётчик
        $user->code_request_count = 0;
      }
    }

    // Увеличиваем счётчик запросов
    $user->code_request_count += 1;
    $user->last_request_time = $currentTime;
    $user->save();

    // Генерируем и отправляем 2FA-код
    $this->twoFactorService->setCode($user, $deviceId);

    return response()->json(['message' => '2FA the code was sent again']);
  }

  /**
   * Подтверждение 2FA-кода.
   */
  public function confirmCode(Request $request, TokenService $tokenService)
{
    $tempToken = $request->bearerToken();

    // Проверяем временный токен
    $userToken = UserToken::where('token', $tempToken)->where('is_tmp', 1)->first();
    if (!$userToken) {
        return response()->json(['message' => 'Invalid or expired temporary token'], 401);
    }

    // Проверяем срок действия временного токена
    if (now()->greaterThan($userToken->expires_at)) {
        return response()->json(['message' => 'Temporary token expired'], 401);
    }

    $user = $userToken->user;
    $code = $request->input('code');
    $deviceId = $request->header('Device-ID');

    // Проверяем наличие кода
    if (!$code) {
        return response()->json(['message' => '2FA code is required'], 400);
    }

    // Проверяем корректность 2FA-кода
    if (
        !$user->is_two_fa_enabled ||
        !$this->twoFactorService->isValid($code, $user) ||
        $user->two_fa_device_id !== $deviceId
    ) {
        return response()->json(['message' => 'Invalid or expired 2FA code'], 400);
    }

    // Очистка 2FA-кода и сброс счётчика
    $this->twoFactorService->clearCode($user);
    $user->code_request_count = 0;
    $user->last_request_time = null;
    $user->save();

    // Удаляем временный токен
    $userToken->delete();

    // Генерация нового токена
    $newTokens = $tokenService->generateToken($user);

    return response()->json([
        'status' => 'success',
        'access_token' => $newTokens['access_token'],
        'refresh_token' => $newTokens['refresh_token'],
    ], 200);
}


  /**
   * Включение/выключение 2FA.
   */
  public function toggleTwoFactor(Request $request)
  {
    $user = $request->user();
    $currentPassword = $request->input('password');

    // Проверка введённого пароля
    if (!Hash::check($currentPassword, $user->password)) {
      return response()->json(['message' => 'Invalid password'], 400);
    }

    // Переключение состояния 2FA
    $user->is_two_fa_enabled = !$user->is_two_fa_enabled;
    $user->save();

    return response()->json([
      'message' => $user->is_two_fa_enabled ? '2FA enabled' : '2FA disabled',
    ]);
  }
}