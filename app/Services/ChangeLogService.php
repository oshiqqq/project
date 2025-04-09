<?php

namespace App\Services;

use App\Models\ChangeLog;

class ChangeLogService
{
    /**
     * Записывает изменения сущности в журнал
     */
    public function logChange(string $entityType, int $entityId, array $before, array $after, int $userId): void
    {
        ChangeLog::create([
            'entity_type' => $entityType, 
            'entity_id' => $entityId, 
            'before' => json_encode($before, JSON_UNESCAPED_UNICODE), 
            'after' => json_encode($after, JSON_UNESCAPED_UNICODE), 
            'created_by' => $userId, 
        ]);
    }
}
