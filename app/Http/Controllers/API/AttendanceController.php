<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * Обрабатывает загрузку файла и извлекает данные.
     */
    public function upload(Request $request)
    {
        // Проверяем наличие файла в запросе
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'File not found'], 400);
        }

        $file = $request->file('file');

        try {
            // Преобразуем данные из Excel в массив
            $data = Excel::toArray([], $file)[0];
            // Обрабатываем полученные данные
            $result = $this->processData($data);
            return response()->json($result);
        } catch (\Exception $e) {
            // Логируем ошибку, если обработка файла не удалась
            Log::error('Excel processing error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Обрабатывает данные из Excel.
     */
    private function processData($data)
    {
        // Проверяем, что данные в файле не пустые
        if (empty($data)) {
            return ['error' => 'No data found in Excel sheet'];
        }

        // Инициализация переменных для групп, дат, времени и типов занятий
        $groups = [];
        $currentGroup = null;
        $dates = [];
        $times = [];
        $lessonTypes = [];
        $studentsForAutomaticCredit = [];

        // Определяем начальные и конечные колонки для обработки
        $startCol = 26; // Колонка AA
        $endCol = 116;  // Колонка DT (примерное количество колонок)

        // Обрабатываем типы занятий (строка 2)
        foreach ($data[1] ?? [] as $col => $value) {
            if ($col >= $startCol && $col <= $endCol) {
                $lessonTypes[$col] = $value ?: 'Unknown';
            }
        }

        // Обрабатываем даты (строка 3)
        foreach ($data[2] ?? [] as $col => $value) {
            if ($col >= $startCol && $col <= $endCol) {
                // Преобразуем числовые значения в дату
                if (is_numeric($value) && (int)$value > 0) {
                    $dates[$col] = Carbon::create(1900, 1, 1)->addDays((int)$value - 2)->format('d.m.Y');
                } elseif (!empty($value)) {
                    try {
                        $dates[$col] = Carbon::parse($value)->format('d.m.Y');
                    } catch (\Exception $e) {
                        // Логируем ошибку в случае неудачи
                        Log::warning('Failed to parse date: ' . $value);
                        $dates[$col] = '01.01.1970'; // Возвращаем дефолтную дату
                    }
                } else {
                    $dates[$col] = null;
                }
            }
        }

        // Обрабатываем время (строка 4)
        foreach ($data[3] ?? [] as $col => $value) {
            if ($col >= $startCol && $col <= $endCol) {
                if (!empty($value)) {
                    try {
                        // Преобразуем значение времени в формат HH:MM
                        if (is_numeric($value)) {
                            $hours = floor($value * 24);
                            $minutes = round(($value * 24 - $hours) * 60);
                            $times[$col] = sprintf('%02d:%02d', $hours, $minutes);
                        } else {
                            $times[$col] = Carbon::createFromFormat('H:i', $value)->format('H:i');
                        }
                    } catch (\Exception $e) {
                        // Логируем ошибку при обработке времени
                        Log::warning('Failed to parse time: ' . $value);
                        $times[$col] = '00:00'; // Возвращаем дефолтное значение
                    }
                } else {
                    $times[$col] = null;
                }
            }
        }

        // Обрабатываем данные учеников (начиная с 7 строки)
        foreach ($data as $rowIndex => $row) {
            // Пропускаем первые 6 строк, так как они содержат информацию о датах и типах
            if ($rowIndex < 6) continue;

            // Определяем группу, если она была указана
            if (!empty($row[0]) && preg_match('/^\d{4}б$/', $row[0])) {
                $currentGroup = $row[0];
                continue;
            }

            // Пропускаем строки, где нет данных
            if (empty($row[0])) continue;

            // Извлекаем имя, подгруппу и информацию о зачете
            $name = trim($row[0]);
            $subgroup = !empty($row[2]) ? (int)$row[2] : 1;
            $hasCredit = !empty($row[25]) && $row[25] === 'Зачет';

            // Подсчитываем количество выполненных лабораторных работ
            $submittedLabs = 0;
            $totalLabs = 12; // Общее количество лабораторных

            // Проверка данных о лабораторных работах
            if (isset($row[5]) && !empty($row[5])) {
                if (is_numeric($row[5])) {
                    $submittedLabs = (int)$row[5];
                } elseif (preg_match('/\d+/', $row[5], $matches)) {
                    $submittedLabs = (int)$matches[0]; // Извлекаем число из строки
                } else {
                    Log::warning('Invalid labs data in column F for student ' . $name . ': ' . $row[5]);
                }
            }

            // Инициализация переменных для посещаемости
            $attendance = [];
            $visitedCount = 0;
            $ownClasses = 0;

            // Проходим по всем типам занятий и проверяем посещаемость
            foreach ($lessonTypes as $col => $lessonType) {
                if (!isset($dates[$col])) continue;

                // Проверка, посещал ли студент занятие
                $visitMark = $row[$col] ?? '';
                $isVisited = in_array($visitMark, ['+', '👌', '🙋🏻', '😎']) || 
                             preg_match('/\d+✅/', $visitMark) || 
                             strpos($visitMark, '✅') !== false;

                // Определение типа занятия (лекция или лабораторная)
                $type = strpos($lessonType, 'ЛК') !== false ? 'lect' : 'lab';

                // Определение подгруппы занятия
                $lessonSubgroup = 1;
                if (preg_match('/\/(\d+)$/', $lessonType, $matches)) {
                    $lessonSubgroup = (int)$matches[1];
                }

                // Учитываем только занятия в своей подгруппе для подсчета 80%
                if ($lessonSubgroup == $subgroup) {
                    $ownClasses++;
                    if ($isVisited) {
                        $visitedCount++;
                    }
                }

                // Добавляем информацию о посещаемости
                $attendance[] = [
                    'date' => $dates[$col] ?? '01.01.1970',
                    'time' => $times[$col] ?? '00:00',
                    'type' => $type,
                    'number' => $col - $startCol,
                    'subgroup' => $lessonSubgroup,
                    'visit' => $isVisited
                ];
            }

            // Рассчитываем процент посещаемости и выполненных лабораторных
            $visitPercent = $ownClasses ? round(($visitedCount / $ownClasses) * 100, 2) : 0;
            $successLabsPercent = $totalLabs ? round(($submittedLabs / $totalLabs) * 100, 2) : 0;

            // Проверка, засчитан ли студент (автоматический зачет)
            $result = $hasCredit || ($visitPercent >= 80 && $submittedLabs >= 4);

            // Инициализация группы, если она еще не существует
            if (!isset($groups[$currentGroup])) {
                $groups[$currentGroup] = [
                    'group_name' => $currentGroup,
                    'students' => [],
                    'result' => ['success' => 0, 'unsuccessfully' => 0]
                ];
            }

            // Добавление студента в группу
            $groups[$currentGroup]['students'][] = [
                'name' => $name,
                'subgroup' => $subgroup,
                'leasons' => $attendance,
                'visit_percent' => $visitPercent,
                'success_labs_percent' => $successLabsPercent,
                'success_labs' => $submittedLabs,
                'result' => $result
            ];

            // Обновляем количество студентов с автоматическим зачетом
            if ($result) {
                $studentsForAutomaticCredit[] = $name;
                $groups[$currentGroup]['result']['success']++;
            } else {
                $groups[$currentGroup]['result']['unsuccessfully']++;
            }
        }

        // Возвращаем итоговые данные
        return [
            'groups' => array_values($groups),
            'studentsForAutomaticCredit' => $studentsForAutomaticCredit,
            'totalStudentsWithAutomaticCredit' => count($studentsForAutomaticCredit)
        ];
    }
}
