<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель UserToken представляет собой таблицу токенов пользователей.
 * Используется для аутентификации и управления сроками действия токенов.
 */
class UserToken extends Model
{
    protected $fillable = [
        'user_id',           
        'token',              
        'expires_at',         
        'refresh_token',      
        'refresh_expires_at', 
    ];

    /**
     * Определяет связь "многие к одному" с моделью User.
     * Позволяет получить пользователя, которому принадлежит токен.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
