<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePermission extends Model
{
    use HasFactory, SoftDeletes;

    // Отключаем автоматические временные метки 
    public $timestamps = false;

    // Поля, доступные для массового заполнения 
    protected $fillable = [
        'role_id',
        'permission_id',
        'created_by'
    ];
}