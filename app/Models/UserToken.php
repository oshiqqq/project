<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsChanges;

class UserToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'refresh_token',
        'refresh_expires_at',
    ];

    /* 
    Связь один-ко-многим с таблицей users 
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}