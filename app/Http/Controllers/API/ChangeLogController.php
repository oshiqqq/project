<?php

namespace App\Http\Controllers\API;

use App\Models\ChangeLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ChangeLogController extends Controller
{
    /**
     * Восстанавливает сущность в состояние, сохранённое в поле "before" записи журнала изменений.
     */
    public function restoreEntity($id)
    {
        // Начинаем транзакцию, чтобы при любой ошибке можно было откатить изменения
        DB::beginTransaction();

        try {
            // Пытаемся найти запись лога по переданному ID
            $changeLog = ChangeLog::find($id);

            // Если запись не найдена, возвращаем ошибку 404
            if (!$changeLog) {
                return response()->json(['message' => 'Entry not found'], 404);
            }

            // Строим имя класса модели на основе названия таблицы:
            // например, из "users" получим "App\Models\User"
            $modelClass = 'App\\Models\\' . ucfirst(\Illuminate\Support\Str::singular($changeLog->entity_type));

            // Проверяем, существует ли такой класс
            if (!class_exists($modelClass)) {
                return response()->json(['message' => 'Model not found'], 404);
            }

            // Пытаемся найти конкретную запись в базе по ID, указанному в логе
            $entity = $modelClass::find($changeLog->entity_id);

            // Если сущность не найдена (возможно, была удалена), возвращаем ошибку
            if (!$entity) {
                return response()->json(['message' => 'Entity not found'], 404);
            }

            // Получаем старое состояние сущности, сохранённое в поле 'before'
            $beforeState = json_decode($changeLog->before, true); // преобразуем JSON в массив

            // Заполняем объект модели новыми (в данном случае старыми) значениями
            $entity->fill($beforeState);

            // Сохраняем восстановленное состояние обратно в БД
            $entity->save();

            // Фиксируем транзакцию, т.е. сохраняем все изменения
            DB::commit();

            // Возвращаем успешный ответ с восстановленной сущностью
            return response()->json([
                'message' => 'Entity restored successfully',
                'restored_entity' => $entity,
            ]);
        } catch (\Exception $e) {
            // В случае любой ошибки — откатываем все изменения
            DB::rollBack();

            // Возвращаем ошибку 500 и текст исключения
            return response()->json([
                'message' => 'Failed to restore entity',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
