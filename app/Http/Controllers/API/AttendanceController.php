<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

class AttendanceController extends Controller
{
    /**
     * Метод загрузки
     */
    public function upload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'File not found'], 400);
        }

        $file = $request->file('file');

        try {
            $data = Excel::toArray([], $file)[0];
            $result = $this->processData($data);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to process file: ' . $e->getMessage()], 500);
        }
    }

    /**
    * Метод обрабатывает загруженные данные Excel и возвращает результаты обработки студентов и групп
    */ 
    private function processData($data)
    {
        if (empty($data)) {
            return ['error' => 'No data found in Excel sheet'];
        }

        // Определение колонок для посещений (AA - DT)
        $startCol = 26; // AA - начальная колонка для данных о посещениях
        $endCol = 116;  // DT - конечная колонка для данных о посещениях

        // Извлекаем информацию о занятиях
        $lessonInfo = $this->extractLessonInfo($data, $startCol, $endCol);

        // Обрабатываем студентов
        $groups = $this->processStudents($data, $lessonInfo, $startCol, $endCol);

        return ['groups' => array_values($groups)];
    }

    /**
     * Метод извлекает информацию о занятиях, датах и времени из Excel
     */
    private function extractLessonInfo($data, $startCol, $endCol)
    {
        $lessonGroupsSubgroups = [];
        $dates = [];
        $times = [];

        foreach (range($startCol, $endCol) as $col) {
            // Группа и подгруппа (строка 2)
            if (isset($data[1][$col])) {
                $lessonGroupsSubgroups[$col] = $this->parseGroupAndSubgroup($data[1][$col]);
            } else {
                $lessonGroupsSubgroups[$col] = ['group' => null, 'subgroup' => 1];
            }

            // Даты (строка 3)
            if (isset($data[2][$col])) {
                $dates[$col] = $this->parseDate($data[2][$col]);
            } else {
                $dates[$col] = null;
            }

            // Время (строка 4, пока не используется)
            if (isset($data[3][$col])) {
                $times[$col] = $this->parseTime($data[3][$col]);
            } else {
                $times[$col] = null;
            }
        }

        return compact('lessonGroupsSubgroups', 'dates', 'times');
    }

    /**
    * Метод парсит дату из значения ячейки Excel
    */ 
    private function parseDate($value)
    {
        if (is_numeric($value) && (int)$value > 0) {
            return Carbon::create(1900, 1, 1)->addDays((int)$value - 2)->format('Y-m-d');
        } elseif (!empty($value)) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return '1970-01-01';
            }
        }
        return null;
    }

    /**
    * Метод парсит время из значения ячейки Excel
    */ 
    private function parseTime($value)
    {
        if (!empty($value)) {
            try {
                if (is_numeric($value)) {
                    $hours = floor($value * 24);
                    $minutes = round(($value * 24 - $hours) * 60);
                    return sprintf('%02d:%02d', $hours, $minutes);
                } else {
                    return Carbon::createFromFormat('H:i', $value)->format('H:i');
                }
            } catch (\Exception $e) {
                return '00:00';
            }
        }
        return null;
    }

    /**
    * Метод парсит группу и подгруппу из строки
    */ 
    private function parseGroupAndSubgroup($value)
    {
        if (preg_match('/(\d{4}б)\/(\d)/', $value, $matches)) {
            return [
                'group' => $matches[1],
                'subgroup' => (int)$matches[2]
            ];
        }
        return ['group' => null, 'subgroup' => 1];
    }

    /**
    * Метод обрабатывает данные студентов, группируя их по группам и рассчитывая посещаемость
    */ 
    private function processStudents($data, $lessonInfo, $startCol, $endCol)
    {
        $groups = [];
        
        // Определяем границы групп в таблице
        $groupBoundaries = [
            '1111б' => [
                'start' => 6,   // Строки A7-A32 (индексы 6-31)
                'end' => 31,
                'labs_col' => 5,     // Колонка F (индекс 5)
                'credit_col' => 25,  // Колонка Z (индекс 25)
                'min_labs' => 6
            ],
            '1511б' => [
                'start' => 33,  // Строки A34-A58 (индексы 33-57)
                'end' => 57,
                'labs_col' => 5,     // Колонка F (индекс 5)
                'credit_col' => 25,  // Колонка Z (индекс 25)
                'min_labs' => 9
            ]
        ];
    
        foreach ($groupBoundaries as $groupName => $boundary) {
            $groups[$groupName] = [
                'group_name' => $groupName,
                'students' => [],
                'result' => ['success' => 0, 'unsuccessfully' => 0]
            ];
    
            // Обрабатываем студентов в границах группы
            for ($rowIndex = $boundary['start']; $rowIndex <= $boundary['end']; $rowIndex++) {
                if (empty($data[$rowIndex][0])) continue; // Пропускаем пустые строки
    
                $name = trim($data[$rowIndex][0]); // ФИО студента
                $subgroup = !empty($data[$rowIndex][2]) && is_numeric($data[$rowIndex][2]) 
                    ? (int)$data[$rowIndex][2] 
                    : 1; // Подгруппа (по умолчанию 1)
                
                // Получаем всю строку для расчета лабораторных
                $rowData = $data[$rowIndex];

                // Рассчитываем количество сданных лабораторных вручную
                $submittedLabs = $this->calculateSubmittedLabs($rowData, $rowIndex, $startCol, $endCol);
                
                // Проверяем автоматический зачет из колонки Z
                $creditValue = $data[$rowIndex][$boundary['credit_col']] ?? '';
                $hasAutomaticCredit = $this->hasAutomaticCredit($creditValue);
    
                // Рассчитываем посещаемость
                $attendanceInfo = $this->calculateAttendance($rowData, $lessonInfo, $startCol, $endCol, $groupName);
                $visitedCount = $attendanceInfo['visitedCount'];
                $totalClasses = $attendanceInfo['totalClasses'];
                $visitPercent = $totalClasses > 0 ? round(($visitedCount / $totalClasses) * 100, 2) : 0;
    
                // Определяем зачет (автоматический имеет приоритет)
                $result = $hasAutomaticCredit || ($visitPercent >= 80 && $submittedLabs >= $boundary['min_labs']);
    
                // Добавляем студента
                $groups[$groupName]['students'][] = [
                    'name' => $name,
                    'subgroup' => $subgroup,
                    'attendance' => $attendanceInfo['attendance'],
                    'visit_percent' => $visitPercent,
                    'submitted_labs' => $submittedLabs,
                    'result' => $result,
                    'automatic_credit' => $hasAutomaticCredit
                ];
    
                // Обновляем статистику
                if ($result) {
                    $groups[$groupName]['result']['success']++;
                } else {
                    $groups[$groupName]['result']['unsuccessfully']++;
                }
            }
        }
    
        return $groups;
    }
    
    /**
     * Метод рассчитывает количество сданных лабораторных работ на основе специальных маркеров
     */ 
    private function calculateSubmittedLabs($rowData, $rowIndex, $startCol, $endCol)
    {
        $countCheck = 0;
        $countBird = 0;
        $countSad = 0;
        $countFire = 0;
        $countCheckT = 0;

        // Проходим по колонкам с AA до DT (индексы от $startCol до $endCol)
        for ($col = $startCol; $col <= $endCol; $col++) {
            if (!isset($rowData[$col])) continue;

            $value = trim((string)$rowData[$col]);

            // Считаем каждый маркер
            if (strpos($value, '✅') !== false) $countCheck++;
            if ($value === '🐤') $countBird++;
            if ($value === '🥲') $countSad++;
            if (strpos($value, '🔥') !== false) $countFire++;
            if (strpos($value, '✅T') !== false) $countCheckT++;
        }

        // Получаем значение из колонки V (индекс 21, если A=0, B=1, ..., V=21)
        $vColumnIndex = 21;
        $vValue = isset($rowData[$vColumnIndex]) ? trim((string)$rowData[$vColumnIndex]) : '0';

        // Вычисляем по формуле
        $result = $countCheck + ($countBird / 20) + ($countSad / 2) + ($countFire * 1.5) - (is_numeric($vValue) ? (float)$vValue : 0) + ($countCheckT * 0.3);

        return max(0, round($result));
    }
    
    /**
     * Обработка автоматического зачёта.
     * Метод проверяет различные форматы данных для определения автоматического зачёта.
     */
    private function hasAutomaticCredit($value)
    {   
        if (empty($value)) {
            return false;
        }

        $value = strtolower(trim($value));

        // Текстовый автоматический зачет
        if (in_array($value, ['зачет', 'зачёт'])) {
            return true;
        }

        // Обработка значений с символом процента, например "100%"
        if (substr($value, -1) === '%') {
            $number = floatval(substr($value, 0, -1));
            if ($number >= 100) {
                return true;
            }
        }

        // Обработка числовых значений
        if (is_numeric($value)) {
            $number = floatval($value);
            // Если число меньше 10, считаем его соотношением (например, 1 означает 100%); иначе – абсолютное значение
            if ($number < 10) {
                if ($number >= 1.0) {
                    return true;
                }
            } else {
                if ($number >= 100) {
                    return true;
                }
            }
        }

        // Если значение начинается с "=" – пробуем вычислить формулу
        if (strpos($value, '=') === 0) {
            try {
                $calculatedValue = Calculation::getInstance()->calculateFormula($value);
                if (is_numeric($calculatedValue)) {
                    $number = floatval($calculatedValue);
                    // Аналогичная логика: если число меньше 10 – соотношение, иначе – абсолютное значение
                    if ($number < 10) {
                        if ($number >= 1.0) {
                            return true;
                        }
                    } else {
                        if ($number >= 100) {
                            return true;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Formula calculation error', [
                    'formula' => $value,
                    'error' => $e->getMessage()
                ]);
            }
        }
        return false;
    }

    /**
     * Метод рассчитывает посещаемость студента на основе данных о занятиях
     */ 
    private function calculateAttendance($row, $lessonInfo, $startCol, $endCol, $studentGroup)
    {
        $attendance = [];
        $visitedCount = 0;
        $totalClasses = 0;
    
        foreach (range($startCol, $endCol) as $col) {
            if (!isset($lessonInfo['lessonGroupsSubgroups'][$col]) || 
                !isset($lessonInfo['dates'][$col])) {
                continue;
            }
    
            $lessonGroup = $lessonInfo['lessonGroupsSubgroups'][$col]['group'];
            $lessonSubgroup = $lessonInfo['lessonGroupsSubgroups'][$col]['subgroup'];
    
            // Учитываем только занятия своей группы
            if ($lessonGroup !== $studentGroup) {
                continue;
            }
    
            $totalClasses++;
            $visitMark = $row[$col] ?? '';
            $isVisited = $this->isVisitedMark($visitMark);
    
            if ($isVisited) {
                $visitedCount++;
            }
    
            $attendance[] = [
                'date' => $lessonInfo['dates'][$col] ?? '1970-01-01',
                'subgroup' => $lessonSubgroup,
                'group' => $lessonGroup,
                'visit' => $isVisited,
                'mark' => $visitMark
            ];
        }
    
        return compact('attendance', 'visitedCount', 'totalClasses');
    }

    /**
     * Метод определяет, является ли маркер посещения действительным (студент присутствовал)
     */ 
    private function isVisitedMark($visitMark)
    {
        $visitMark = trim((string)$visitMark);
        return !empty($visitMark) && (
            $visitMark === '+' || 
            $visitMark === '✅' || 
            $visitMark === '🙋🏻' || 
            strpos($visitMark, '😎') !== false || 
            $visitMark === '👌'
        );
    }
}