<?php

namespace App\Http\Controllers\DutyRoster;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\Position;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AcademicDutyRosterController extends Controller
{
    // ==================== СТАТУСЫ ====================
    private const STATUS_PRESENT = 1;        // Налицо
    private const STATUS_SICK = [2, 3];      // Больные
    private const STATUS_LEAVE = 5;          // Отпуск
    private const STATUS_DUTY = 4;           // Наряд
    private const STATUS_MISSION = 6;        // Командировка
    private const STATUS_DISMISSED = 9;      // Увольнение
    private const STATUS_OTHER = [7, 8];     // Прочее

    /**
     * Заполняет пустые значения символом "0"
     */
    private function fillEmptyCells($value)
    {
        return ($value === null || $value === '' || $value === false) ? '0' : $value;
    }

    /**
     * Получает штатную численность по категории (постоянный/переменный состав)
     */
    private function getStaffCountByCategory(Unit $unit, string $categoryType): int
    {
        $staffCount = 0;
        
        // Получаем категорию по типу (Постоянный состав или Переменный состав)
        $category = $unit->categories()->where('name', $categoryType)->first();
        
        if ($category) {
            $positions = $category->positions;
            foreach ($positions as $position) {
                $staffCount += $position->count;
            }
        }
        
        return $staffCount;
    }

    /**
     * Получает статистику по подразделению
     */
    private function getUnitStats(Unit $unit): array
    {
        // ==================== ПОСТОЯННЫЙ СОСТАВ ====================
        // Получаем категорию "Постоянный состав"
        $permanentCategory = $unit->categories()->where('name', 'Постоянный состав')->first();
        
        if ($permanentCategory) {
            $permanentPositionIds = $permanentCategory->positions->pluck('id')->toArray();
            $permanentPersonnel = $unit->personnel->filter(function ($p) use ($permanentPositionIds) {
                return in_array($p->position_id, $permanentPositionIds);
            });
        } else {
            $permanentPersonnel = collect();
        }
        
        // ==================== ПЕРЕМЕННЫЙ СОСТАВ ====================
        $temporaryCategory = $unit->categories()->where('name', 'Переменный состав')->first();
        
        if ($temporaryCategory) {
            $temporaryPositionIds = $temporaryCategory->positions->pluck('id')->toArray();
            $temporaryPersonnel = $unit->personnel->filter(function ($p) use ($temporaryPositionIds) {
                return in_array($p->position_id, $temporaryPositionIds);
            });
        } else {
            $temporaryPersonnel = collect();
        }
        
        // ==================== ПОСТОЯННЫЙ СОСТАВ - СТАТИСТИКА ====================
        $permanentStaff = $this->getStaffCountByCategory($unit, 'Постоянный состав');
        $permanentTotal = $permanentPersonnel->count();
        $permanentPresent = $permanentPersonnel->where('current_status_id', self::STATUS_PRESENT)->count();
        $permanentDuty = $permanentPersonnel->where('current_status_id', self::STATUS_DUTY)->count();
        $permanentMission = $permanentPersonnel->where('current_status_id', self::STATUS_MISSION)->count();
        $permanentLeave = $permanentPersonnel->where('current_status_id', self::STATUS_LEAVE)->count();
        $permanentSick = $permanentPersonnel->filter(fn($p) => in_array($p->current_status_id, self::STATUS_SICK))->count();
        $permanentDismissed = $permanentPersonnel->where('current_status_id', self::STATUS_DISMISSED)->count();
        $permanentOther = $permanentPersonnel->filter(fn($p) => in_array($p->current_status_id, self::STATUS_OTHER))->count();
        
        // ==================== ПЕРЕМЕННЫЙ СОСТАВ - СТАТИСТИКА ====================
        $temporaryStaff = $this->getStaffCountByCategory($unit, 'Переменный состав');
        $temporaryTotal = $temporaryPersonnel->count();
        $temporaryPresent = $temporaryPersonnel->where('current_status_id', self::STATUS_PRESENT)->count();
        $temporaryDuty = $temporaryPersonnel->where('current_status_id', self::STATUS_DUTY)->count();
        $temporaryMission = $temporaryPersonnel->where('current_status_id', self::STATUS_MISSION)->count();
        $temporaryLeave = $temporaryPersonnel->where('current_status_id', self::STATUS_LEAVE)->count();
        $temporarySick = $temporaryPersonnel->filter(fn($p) => in_array($p->current_status_id, self::STATUS_SICK))->count();
        $temporaryDismissed = $temporaryPersonnel->where('current_status_id', self::STATUS_DISMISSED)->count();
        $temporaryOther = $temporaryPersonnel->filter(fn($p) => in_array($p->current_status_id, self::STATUS_OTHER))->count();

        return [
            'unit_id' => $unit->id,
            'unit_name' => $unit->name,
            'parent_id' => $unit->parent_id,
            'children' => [],
            'permanent' => [
                'staff' => $permanentStaff,
                'total' => $permanentTotal,
                'present' => $permanentPresent,
                'duty' => $permanentDuty,
                'mission' => $permanentMission,
                'leave' => $permanentLeave,
                'sick' => $permanentSick,
                'dismissed' => $permanentDismissed,
                'other' => $permanentOther,
            ],
            'temporary' => [
                'staff' => $temporaryStaff,
                'total' => $temporaryTotal,
                'present' => $temporaryPresent,
                'duty' => $temporaryDuty,
                'mission' => $temporaryMission,
                'leave' => $temporaryLeave,
                'sick' => $temporarySick,
                'dismissed' => $temporaryDismissed,
                'other' => $temporaryOther,
            ]
        ];
    }

    /**
     * Рекурсивно собирает статистику по всем подразделениям
     */
    private function collectUnitStats(Unit $unit): array
    {
        $stats = $this->getUnitStats($unit);
        
        foreach ($unit->children as $child) {
            $stats['children'][] = $this->collectUnitStats($child);
        }
        
        return $stats;
    }

    /**
     * Рекурсивно заполняет Excel строки
     */
    private function fillExcelRows($sheet, $stats, &$row, &$num, &$academyTotals, $level = 0)
    {
        // Отступ для отображения иерархии
        $indent = str_repeat('    ', $level);
        $unitName = $indent . $stats['unit_name'];
        
        $permanent = $stats['permanent'];
        $temporary = $stats['temporary'];
        
        // Общее количество налицо (сумма постоянного и переменного)
        $totalPresent = $permanent['present'] + $temporary['present'];
        
        // Записываем строку подразделения
        $sheet->setCellValue('A' . $row, $num);
        $sheet->setCellValue('B' . $row, $unitName);
        
        // По штату
        $sheet->setCellValue('C' . $row, $this->fillEmptyCells($permanent['staff']));
        $sheet->setCellValue('D' . $row, $this->fillEmptyCells($temporary['staff']));
        
        // По списку
        $sheet->setCellValue('E' . $row, $this->fillEmptyCells($permanent['total']));
        $sheet->setCellValue('F' . $row, $this->fillEmptyCells($temporary['total']));
        
        // Налицо
        $sheet->setCellValue('G' . $row, $this->fillEmptyCells($permanent['present']));
        $sheet->setCellValue('H' . $row, $this->fillEmptyCells($temporary['present']));
        
        // Наряд
        $sheet->setCellValue('I' . $row, $this->fillEmptyCells($permanent['duty']));
        $sheet->setCellValue('J' . $row, $this->fillEmptyCells($temporary['duty']));
        
        // Командировка
        $sheet->setCellValue('K' . $row, $this->fillEmptyCells($permanent['mission']));
        $sheet->setCellValue('L' . $row, $this->fillEmptyCells($temporary['mission']));
        
        // Отпуск
        $sheet->setCellValue('M' . $row, $this->fillEmptyCells($permanent['leave']));
        $sheet->setCellValue('N' . $row, $this->fillEmptyCells($temporary['leave']));
        
        // Больные
        $sheet->setCellValue('O' . $row, $this->fillEmptyCells($permanent['sick']));
        $sheet->setCellValue('P' . $row, $this->fillEmptyCells($temporary['sick']));
        
        // Увольнение
        $sheet->setCellValue('Q' . $row, $this->fillEmptyCells($permanent['dismissed']));
        $sheet->setCellValue('R' . $row, $this->fillEmptyCells($temporary['dismissed']));
        
        // Прочее
        $sheet->setCellValue('S' . $row, $this->fillEmptyCells($permanent['other']));
        $sheet->setCellValue('T' . $row, $this->fillEmptyCells($temporary['other']));
        
        // ИТОГО (сумма налицо)
        $sheet->setCellValue('U' . $row, $this->fillEmptyCells($totalPresent));
        
        // Обновляем итоги по академии
        $academyTotals['permanent_staff'] += $permanent['staff'];
        $academyTotals['temporary_staff'] += $temporary['staff'];
        $academyTotals['permanent_total'] += $permanent['total'];
        $academyTotals['temporary_total'] += $temporary['total'];
        $academyTotals['permanent_present'] += $permanent['present'];
        $academyTotals['temporary_present'] += $temporary['present'];
        $academyTotals['permanent_duty'] += $permanent['duty'];
        $academyTotals['temporary_duty'] += $temporary['duty'];
        $academyTotals['permanent_mission'] += $permanent['mission'];
        $academyTotals['temporary_mission'] += $temporary['mission'];
        $academyTotals['permanent_leave'] += $permanent['leave'];
        $academyTotals['temporary_leave'] += $temporary['leave'];
        $academyTotals['permanent_sick'] += $permanent['sick'];
        $academyTotals['temporary_sick'] += $temporary['sick'];
        $academyTotals['permanent_dismissed'] += $permanent['dismissed'];
        $academyTotals['temporary_dismissed'] += $temporary['dismissed'];
        $academyTotals['permanent_other'] += $permanent['other'];
        $academyTotals['temporary_other'] += $temporary['other'];
        $academyTotals['total_present'] += $totalPresent;
        
        $row++;
        $num++;
        
        // Рекурсивно обрабатываем дочерние подразделения
        foreach ($stats['children'] as $child) {
            $this->fillExcelRows($sheet, $child, $row, $num, $academyTotals, $level + 1);
        }
    }

    public function export($unitId = 1)
    {
        try {
            // Получаем корневое подразделение (академию) с иерархией
            $academy = Unit::with(['personnel.rank', 'personnel.currentStatus', 'children' => function($query) {
                $query->orderBy('name');
            }, 'categories.positions'])->findOrFail($unitId);
            
            // Собираем статистику по всей иерархии
            $stats = $this->collectUnitStats($academy);
            
            // ==================== EXCEL ====================
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Строевая записка');
            
            // ==================== ЗАГОЛОВКИ ====================
            $sheet->setCellValue('A1', 'СТРОЕВАЯ ЗАПИСКА');
            $sheet->mergeCells('A1:U1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $sheet->setCellValue('A2', $academy->name);
            $sheet->mergeCells('A2:U2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $sheet->setCellValue('A3', 'на "' . date('d.m.Y') . '"');
            $sheet->mergeCells('A3:U3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // ==================== ШАПКА ТАБЛИЦЫ ====================
            $headers = [
                'A4' => '№ п/п',
                'B4' => 'Подразделение',
                'C4' => 'По штату',
                'D4' => '',
                'E4' => 'По списку',
                'F4' => '',
                'G4' => 'Налицо',
                'H4' => '',
                'I4' => 'Наряд',
                'J4' => '',
                'K4' => 'Командировка',
                'L4' => '',
                'M4' => 'Отпуск',
                'N4' => '',
                'O4' => 'Больные',
                'P4' => '',
                'Q4' => 'Увольнение',
                'R4' => '',
                'S4' => 'Прочее',
                'T4' => '',
                'U4' => 'Итого',
            ];
            
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
                $sheet->getStyle($cell)->getFont()->setBold(true);
                $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            
            // Подзаголовки
            $subHeaders = [
                'C5' => 'пост.',
                'D5' => 'перем.',
                'E5' => 'пост.',
                'F5' => 'перем.',
                'G5' => 'пост.',
                'H5' => 'перем.',
                'I5' => 'пост.',
                'J5' => 'перем.',
                'K5' => 'пост.',
                'L5' => 'перем.',
                'M5' => 'пост.',
                'N5' => 'перем.',
                'O5' => 'пост.',
                'P5' => 'перем.',
                'Q5' => 'пост.',
                'R5' => 'перем.',
                'S5' => 'пост.',
                'T5' => 'перем.',
                'U5' => '',
            ];
            
            foreach ($subHeaders as $cell => $value) {
                $sheet->setCellValue($cell, $value);
                $sheet->getStyle($cell)->getFont()->setBold(true);
                $sheet->getStyle($cell)->getFont()->setSize(9);
                $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            
            // Объединяем ячейки
            $sheet->mergeCells('C4:D4');  // По штату
            $sheet->mergeCells('E4:F4');  // По списку
            $sheet->mergeCells('G4:H4');  // Налицо
            $sheet->mergeCells('I4:J4');  // Наряд
            $sheet->mergeCells('K4:L4');  // Командировка
            $sheet->mergeCells('M4:N4');  // Отпуск
            $sheet->mergeCells('O4:P4');  // Больные
            $sheet->mergeCells('Q4:R4');  // Увольнение
            $sheet->mergeCells('S4:T4');  // Прочее
            $sheet->mergeCells('U4:U5');  // Итого
            
            // ==================== ЗАПОЛНЕНИЕ ДАННЫХ ====================
            $row = 6;
            $num = 1;
            $academyTotals = [
                'permanent_staff' => 0, 'temporary_staff' => 0,
                'permanent_total' => 0, 'temporary_total' => 0,
                'permanent_present' => 0, 'temporary_present' => 0,
                'permanent_duty' => 0, 'temporary_duty' => 0,
                'permanent_mission' => 0, 'temporary_mission' => 0,
                'permanent_leave' => 0, 'temporary_leave' => 0,
                'permanent_sick' => 0, 'temporary_sick' => 0,
                'permanent_dismissed' => 0, 'temporary_dismissed' => 0,
                'permanent_other' => 0, 'temporary_other' => 0,
                'total_present' => 0,
            ];
            
            $this->fillExcelRows($sheet, $stats, $row, $num, $academyTotals);
            
            // ==================== ИТОГО ПО АКАДЕМИИ ====================
            $sheet->setCellValue('B' . $row, 'ИТОГО по академии');
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            
            // По штату
            $sheet->setCellValue('C' . $row, $this->fillEmptyCells($academyTotals['permanent_staff']));
            $sheet->setCellValue('D' . $row, $this->fillEmptyCells($academyTotals['temporary_staff']));
            
            // По списку
            $sheet->setCellValue('E' . $row, $this->fillEmptyCells($academyTotals['permanent_total']));
            $sheet->setCellValue('F' . $row, $this->fillEmptyCells($academyTotals['temporary_total']));
            
            // Налицо
            $sheet->setCellValue('G' . $row, $this->fillEmptyCells($academyTotals['permanent_present']));
            $sheet->setCellValue('H' . $row, $this->fillEmptyCells($academyTotals['temporary_present']));
            
            // Наряд
            $sheet->setCellValue('I' . $row, $this->fillEmptyCells($academyTotals['permanent_duty']));
            $sheet->setCellValue('J' . $row, $this->fillEmptyCells($academyTotals['temporary_duty']));
            
            // Командировка
            $sheet->setCellValue('K' . $row, $this->fillEmptyCells($academyTotals['permanent_mission']));
            $sheet->setCellValue('L' . $row, $this->fillEmptyCells($academyTotals['temporary_mission']));
            
            // Отпуск
            $sheet->setCellValue('M' . $row, $this->fillEmptyCells($academyTotals['permanent_leave']));
            $sheet->setCellValue('N' . $row, $this->fillEmptyCells($academyTotals['temporary_leave']));
            
            // Больные
            $sheet->setCellValue('O' . $row, $this->fillEmptyCells($academyTotals['permanent_sick']));
            $sheet->setCellValue('P' . $row, $this->fillEmptyCells($academyTotals['temporary_sick']));
            
            // Увольнение
            $sheet->setCellValue('Q' . $row, $this->fillEmptyCells($academyTotals['permanent_dismissed']));
            $sheet->setCellValue('R' . $row, $this->fillEmptyCells($academyTotals['temporary_dismissed']));
            
            // Прочее
            $sheet->setCellValue('S' . $row, $this->fillEmptyCells($academyTotals['permanent_other']));
            $sheet->setCellValue('T' . $row, $this->fillEmptyCells($academyTotals['temporary_other']));
            
            // Итого (сумма налицо)
            $sheet->setCellValue('U' . $row, $this->fillEmptyCells($academyTotals['total_present']));
            
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            
            // ==================== СТИЛИ ====================
            // Центрирование всех числовых ячеек
            $sheet->getStyle('C6:U' . $row)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Выравнивание подразделений по левому краю
            $sheet->getStyle('B6:B' . ($row - 1))
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
            // Границы
            $sheet->getStyle('A4:U' . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]);
            
            // Фон для заголовков
            $sheet->getStyle('A4:U5')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            
            // Жирный шрифт для итоговой строки
            $sheet->getStyle('A' . $row . ':U' . $row)->getFont()->setBold(true);
            
            // Автоширина колонок
            $sheet->getColumnDimension('A')->setWidth(8);
            $sheet->getColumnDimension('B')->setWidth(35);
            
            for ($i = 3; $i <= 21; $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($col)->setWidth(6);
            }
            
            // ==================== СОХРАНЕНИЕ ====================
            $filename = 'akademicheskaya_stroevaya_' . date('Y-m-d') . '.xlsx';
            $path = storage_path('app/public/' . $filename);
            
            if (!file_exists(storage_path('app/public'))) {
                mkdir(storage_path('app/public'), 0777, true);
            }
            
            (new Xlsx($spreadsheet))->save($path);
            
            return response()->json([
                'success' => true,
                'message' => 'Академическая строевая записка создана',
                'file' => $filename,
                'download_url' => url('/storage/' . $filename),
                'generated_at' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}