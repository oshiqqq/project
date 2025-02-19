<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Атрибуты, которые можно массово назначать.
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'birthday',
    ];

    /**
     * Атрибуты, которые должны быть скрыты при сериализации.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Атрибуты, которые должны быть приведены к определенным типам.
     */
    protected $casts = [
        'birthday' => 'date', 
    ];
}