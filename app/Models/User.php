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
        'is_two_fa_enabled',
        'two_fa_code',
        'two_fa_expires_at',
        'two_fa_device_id',
    ];

    // Поля, скрытые при сериализации 
    protected $hidden = [
        'password',
        'two_fa_code', 
    ];

    // Приведение типов для полей 
    protected $casts = [
        // 'email_verified_at' => 'datetime',
        'birthday' => 'date',
        'is_two_fa_enabled' => 'boolean',
        'two_fa_expires_at' => 'datetime',
        'last_request_time' => 'datetime',
    ];

    /* 
    Проверка наличия разрешения у пользователя через его роли 
    */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()->whereNull('user_roles.deleted_at') // Фильтр на мягкое удаление
        ->whereHas('permissions', function ($query) use ($permissionSlug) {
            $query->where('slug', $permissionSlug);
        })->exists();
    }
    /* 
    Связь многие-ко-многим с таблицей roles через user_roles 
    */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

}