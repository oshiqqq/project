<?php

namespace App\Traits;

use App\Models\ChangeLog;
use Illuminate\Support\Facades\Auth;

trait LogsChanges
{
    /**
     * Метод boot вызывается Laravel автоматически при инициализации модели.
     * Здесь мы подписываемся на события Eloquent: создание, обновление и удаление.
     * Для каждого события вызывается метод logChange, который сохраняет изменения в таблицу журнала.
     */
    public static function bootLogsChanges()
    {
        // Логирование после создания модели
        static::created(function ($model) {
            // При создании: до этого модель не существовала, поэтому "before" — пусто,
            // а "after" содержит все атрибуты новой записи
            static::logChange($model, 'create', [], $model->attributesToArray());
        });

        // Логирование перед обновлением модели
        static::updating(function ($model) {
            // Получаем оригинальные значения (до изменений)
            $before = $model->getOriginal();

            // Получаем только изменённые поля с новыми значениями
            $after = $model->getDirty();

            // Логируем только те поля, которые действительно были изменены
            static::logChange($model, 'update', $before, $after);
        });

        // Логирование перед удалением модели
        static::deleting(function ($model) {
            // При удалении: сохраняем все текущие данные модели как "before",
            // а "after" будет пустым, так как запись будет удалена
            static::logChange($model, 'delete', $model->attributesToArray(), []);
        });
    }

    /**
     * Записывает изменения модели в таблицу журнала (change_logs)
     * Illuminate\Database\Eloquent\Model $model - модель, с которой произошло действие
     * $action - действие: 'create', 'update' или 'delete'
     * $before - массив данных до изменения
     * $after - массив данных после изменения
     */
    private static function logChange($model, string $action, array $before, array $after)
    {
        // Получаем ID текущего пользователя, если он авторизован
        $userId = Auth::check() ? Auth::id() : null;

        // Если пользователь не авторизован (например, системное действие) — подставляем ID 1
        if ($userId === null) {
            $userId = 1;
        }

        // Создаём новую запись в таблице change_logs
        ChangeLog::create([
            // Тип сущности (таблица модели, например: 'users', 'posts')
            'entity_type' => $model->getTable(),

            // ID конкретной записи модели
            'entity_id' => $model->id,

            // Сохраняем старые данные в JSON (можно позже удобно парсить)
            'before' => json_encode($before, JSON_UNESCAPED_UNICODE),

            // Сохраняем новые данные в JSON
            'after' => json_encode($after, JSON_UNESCAPED_UNICODE),

            // ID пользователя, который выполнил действие
            'created_by' => $userId,
        ]);
    }
}
