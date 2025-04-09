<?php

namespace App\Traits;

use App\Models\ChangeLog;
use Illuminate\Support\Facades\Auth;

trait LogsChanges
{
    public static function bootLogsChanges()
    {
        // Логирование после создания модели
        static::created(function ($model) {
            static::logChange($model, 'create', [], $model->attributesToArray());
        });

        // Логирование перед обновлением модели
        static::updating(function ($model) {
            $before = $model->getOriginal(); // Данные до изменений
            $after = $model->getDirty(); // Только измененные атрибуты
            static::logChange($model, 'update', $before, $after);
        });

        // Логирование перед удалением модели
        static::deleting(function ($model) {
            static::logChange($model, 'delete', $model->attributesToArray(), []);
        });
    }

    /**
     * Записывает изменения модели в журнал
     */
    private static function logChange($model, string $action, array $before, array $after)
    {
        // Получение ID текущего пользователя, если он авторизован
        $userId = Auth::check() ? Auth::id() : null;

        if ($userId === null){
            $userId = 1;
        }

        ChangeLog::create([
            'entity_type' => $model->getTable(),
            'entity_id' => $model->id, 
            'before' => json_encode($before, JSON_UNESCAPED_UNICODE), 
            'after' => json_encode($after, JSON_UNESCAPED_UNICODE),
            'created_by' => $userId, 
        ]);
    }
}
