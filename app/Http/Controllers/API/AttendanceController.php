<?php

namespace App\Http\Controllers\API;

/**
 * Контроллер для обработки данных посещаемости студентов из Excel-файла.
 */
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * Обрабатывает загрузку файла Excel и возвращает данные о посещаемости студентов.
     */
    public function upload(Request $request)
    {
        // Проверяем, передан ли файл в запросе
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'File not found'], 400);
        }

        $file = $request->file('file'); // Получаем загруженный файл

        try {
            $data = Excel::toArray([], $file)[0]; // Преобразуем первый лист Excel в массив
            $result = $this->processData($data); // Обрабатываем данные
            return response()->json($result); // Возвращаем результат в формате JSON
        } catch (\Exception $e) {
            Log::error('Ошибка обработки Excel-файла: ' . $e->getMessage()); // Логируем ошибку
            return response()->json(['error' => 'Не удалось обработать файл: ' . $e->getMessage()], 500); // Возвращаем ошибку 500
        }
    }

    /**
     * Обрабатывает данные из Excel-файла и вычисляет статистику посещаемости студентов.
     */
    private function processData($data)
    {
        // Проверяем, что данные не пусты и содержат минимум 5 строк (заголовки + даты + времена + студенты)
        if (empty($data) || count($data) < 5) {
            return ['error' => 'В таблице Excel не найдено данных'];
        }

        $groups = []; // Массив для хранения данных по группам
        $currentGroup = null; // Текущая группа студентов
        $dates = []; // Массив дат занятий
        $times = []; // Массив времен занятий
        $lessonTypes = []; // Массив типов занятий
        $studentsForAutomaticCredit = []; // Массив студентов, получивших автоматический зачет

        // Извлекаем даты (строка 4, индекс 3) и времена (строка 5, индекс 4)
        $dateRow = $data[3] ?? [];
        $timeRow = $data[4] ?? [];

        // Обрабатываем типы занятий из заголовков (строка 3, индекс 2)
        $headerRow = $data[2] ?? [];
        $lessonStartCol = 8; // Индекс колонки, с которой начинаются занятия (9-я колонка)

        // Извлекаем даты, времена и типы занятий
        for ($col = $lessonStartCol; $col < count($headerRow); $col++) {
            if (!empty($dateRow[$col])) {
                try {
                    $dates[$col] = Carbon::parse($dateRow[$col])->format('d.m.Y'); // Парсим дату и форматируем
                } catch (\Exception $e) {
                    Log::warning('Не удалось разобрать дату: ' . $dateRow[$col]); // Логируем предупреждение
                    $dates[$col] = null;
                }
            }

            if (!empty($timeRow[$col])) {
                try {
                    $times[$col] = Carbon::parse($timeRow[$col])->format('H:i'); // Парсим время и форматируем
                } catch (\Exception $e) {
                    Log::warning('Не удалось разобрать время: ' . $timeRow[$col]); // Логируем предупреждение
                    $times[$col] = null;
                }
            }

            if (!empty($headerRow[$col])) {
                $lessonTypes[$col] = $headerRow[$col]; // Сохраняем тип занятия
            }
        }

        // Фильтруем null значения из массивов
        $dates = array_filter($dates);
        $times = array_filter($times);
        $lessonTypes = array_filter($lessonTypes);

        // Проверяем, найдены ли даты, времена и типы занятий
        if (empty($dates) || empty($times) || empty($lessonTypes)) {
            Log::error('Даты, времена или типы занятий не найдены: dates=' . json_encode($dates) . 
                      ', times=' . json_encode($times) . ', lessonTypes=' . json_encode($lessonTypes));
            return ['error' => 'Даты, времена или типы занятий не найдены в таблице Excel'];
        }

        // Обрабатываем строки с данными студентов
        foreach ($data as $rowIndex => $row) {
            if (empty($row[0])) continue; // Пропускаем пустые строки

            // Проверяем, является ли строка заголовком группы (например, 1111б или 1511б)
            if (preg_match('/^\d{4}б$/', $row[0])) {
                $currentGroup = $row[0]; // Устанавливаем текущую группу
                continue;
            }

            // Пропускаем строки заголовков и пустые строки
            if ($rowIndex < 5 || !isset($row[1])) continue;

            // Обрабатываем строку студента
            $name = trim($row[0] . ' ' . $row[1]); // Формируем полное имя
            $subgroup = !empty($row[2]) ? (int)$row[2] : 1; // Получаем подгруппу или устанавливаем 1 по умолчанию
            $hasCredit = !empty($row[25]) && $row[25] === 'Зачет'; // Проверяем наличие зачета
            $submittedLabs = 0; // Инициализируем счетчик сданных лабораторных

            // Подсчитываем сданные лабораторные (колонки G-S, индексы 6-18)
            for ($labCol = 6; $labCol <= 18; $labCol++) {
                if (!empty($row[$labCol]) && strpos($row[$labCol], '✅') !== false) {
                    $submittedLabs++; // Увеличиваем счетчик при наличии отметки ✅
                }
            }

            $attendance = []; // Массив для хранения данных о посещаемости
            $visitedCount = 0; // Счетчик посещенных занятий

            // Обрабатываем данные о посещаемости
            foreach ($lessonTypes as $col => $lessonType) {
                if (!isset($dates[$col]) || !isset($times[$col])) continue; // Пропускаем, если нет даты или времени

                $visitMark = $row[$col] ?? ''; // Получаем отметку посещаемости
                $isVisited = in_array($visitMark, ['+', '👌', '🙋🏻']) || 
                            preg_match('/\d+✅/', $visitMark); // Проверяем, было ли посещение

                $type = strpos($lessonType, 'ЛК') !== false ? 'lect' : 'lab'; // Определяем тип занятия
                
                // Извлекаем подгруппу из типа занятия (например, Лб 1111б/1)
                $lessonSubgroup = 1;
                if (preg_match('/\/(\d+)$/', $lessonType, $matches)) {
                    $lessonSubgroup = (int)$matches[1];
                }

                $attendance[] = [
                    'date' => $dates[$col], // Дата занятия
                    'time' => $times[$col], // Время занятия
                    'type' => $type, // Тип занятия
                    'number' => $col - $lessonStartCol + 1, // Номер занятия
                    'subgroup' => $lessonSubgroup, // Подгруппа
                    'visit' => $isVisited // Флаг посещения
                ];

                if ($isVisited) $visitedCount++; // Увеличиваем счетчик посещенных занятий
            }

            $totalClasses = count($attendance); // Общее количество занятий
            $visitPercent = $totalClasses ? round(($visitedCount / $totalClasses) * 100, 2) : 0; // Процент посещаемости
            $successLabsPercent = $totalClasses ? round(($submittedLabs / 13) * 100, 2) : 0; // Процент сданных лабораторных

            // Определяем, получает ли студент автоматический зачет
            $result = $hasCredit || ($visitPercent >= 80 && $submittedLabs >= 4);

            if (!isset($groups[$currentGroup])) {
                $groups[$currentGroup] = [
                    'group_name' => $currentGroup, // Название группы
                    'students' => [], // Список студентов
                    'result' => ['success' => 0, 'unsuccessfully' => 0] // Статистика по зачетам
                ];
            }

            $groups[$currentGroup]['students'][] = [
                'name' => $name, // Имя студента
                'subgroup' => $subgroup, // Подгруппа студента
                'leasons' => $attendance, // Данные о посещаемости
                'visit_percent' => $visitPercent, // Процент посещаемости
                'success_labs_percent' => $successLabsPercent, // Процент сданных лабораторных
                'success_labs' => $submittedLabs, // Количество сданных лабораторных
                'result' => $result // Получил ли зачет
            ];

            if ($result) {
                $studentsForAutomaticCredit[] = $name; // Добавляем имя студента в список зачета
                $groups[$currentGroup]['result']['success']++; // Увеличиваем счетчик успешных зачетов
            } else {
                $groups[$currentGroup]['result']['unsuccessfully']++; // Увеличиваем счетчик неуспешных зачетов
            }
        }

        return [
            'groups' => array_values($groups), // Список групп
            'studentsForAutomaticCredit' => $studentsForAutomaticCredit, // Список студентов с автоматическим зачетом
            'totalStudentsWithAutomaticCredit' => count($studentsForAutomaticCredit) // Общее количество студентов с зачетом
        ];
    }
}