<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Support\Facades\Mail;

class TwoFactorService
{
    protected int $codeLength = 6;

    /**
     * Генерирует 6-значный код для двухфакторной аутентификации.
     */
    public function generateCode(): string
    {
        return str_pad((string)random_int(0, 999999), $this->codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * Создаёт и сохраняет код 2FA для пользователя, отправляя его на email.
     */
    public function setCode($user, string $deviceId): string
    {
        $code = $this->generateCode();
        $expiry = (int) config('auth.two_fa_expiry', 10); // Используем config(), а не env()

        // Обновляем код и срок его действия в базе данных
        $user->update([
            'two_fa_code' => Hash::make($code), // Храним код в зашифрованном виде
            'two_fa_expires_at' => now()->addMinutes($expiry),
            'two_fa_device_id' => $deviceId,
        ]);

        // Отправляем код на email пользователя
        Mail::to($user->email)->send(new TwoFactorCodeMail($code));

        return $code;
    }

    /**
     * Проверяет, является ли введённый код 2FA корректным и актуальным.
     */
    public function isValid(string $code, $user): bool
    {
        return Hash::check($code, $user->two_fa_code) && optional($user->two_fa_expires_at)->isFuture();
    }

    /**
     * Очищает код 2FA для пользователя, удаляя его из базы данных.
     */
    public function clearCode($user): void
    {
        $user->update([
            'two_fa_code' => null,
            'two_fa_expires_at' => null,
            'two_fa_device_id' => null,
        ]);
    }
}
