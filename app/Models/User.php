<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\LogsChanges;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsChanges;

    // Поля, доступные для массового заполнения 
    protected $fillable = [
        'username',
        'email',
        'password',
        'birthday',
    ];

    // Поля, скрытые при сериализации 
    protected $hidden = [
        'password',
        // 'remember_token',
    ];

    // Приведение типов для полей 
    protected $casts = [
        // 'email_verified_at' => 'datetime',
        'birthday' => 'date',
    ];

    /* 
    Связь многие-ко-многим с таблицей roles через user_roles 
    */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /* 
    Проверка наличия разрешения у пользователя через его роли 
    */
    public function hasPermission(string $permissionName): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permissionName) {
            $query->where('slug', $permissionName);
        })->exists();
    }
}