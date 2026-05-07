<?php

namespace App\Http\Controllers\DutyRoster;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\DutyRoster\DutyRosterService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AcademicDutyRosterController extends Controller
{
    private DutyRosterService $service;

    public function __construct(DutyRosterService $service)
    {
        $this->service = $service;
    }

    private function fillEmpty($value): string
    {
        return ($value === null || $value === '' || $value === false) ? '0' : (string) $value;
    }

    private function setVerticalText($sheet, string $cell): void
    {
        $sheet->getStyle($cell)->getAlignment()->setTextRotation(90);
    }

    private function getPersonnelByCategory(Unit $unit, $category): \Illuminate\Support\Collection
    {
        if (!$category) {
            return collect();
        }
        
        $positionIds = $category->positions->pluck('id')->toArray();
        return $unit->personnel->filter(fn($p) => in_array($p->position_id, $positionIds));
    }

    private function collectUnitStats(Unit $unit): array
    {
        $permanentCategory = $unit->categories()->where('name', 'Постоянный состав')->first();
        $temporaryCategory = $unit->categories()->where('name', 'Переменный состав')->first();

        $permanentPersonnel = $this->getPersonnelByCategory($unit, $permanentCategory);
        $temporaryPersonnel = $this->getPersonnelByCategory($unit, $temporaryCategory);

        $permanentStaff = $this->service->getStaffCount($unit->id, $permanentCategory?->positions->pluck('id')->toArray());
        $temporaryStaff = $this->service->getStaffCount($unit->id, $temporaryCategory?->positions->pluck('id')->toArray());

        return [
            'unit_id' => $unit->id,
            'unit_name' => $unit->name,
            'parent_id' => $unit->parent_id,
            'children' => [],
            'permanent' => array_merge(
                ['staff' => $permanentStaff],
                $this->service->getFullStats($permanentPersonnel)
            ),
            'temporary' => array_merge(
                ['staff' => $temporaryStaff],
                $this->service->getFullStats($temporaryPersonnel)
            ),
            'absent_list' => array_merge(
                $this->service->getAbsentPersonnel($permanentPersonnel, 'Постоянный состав'),
                $this->service->getAbsentPersonnel($temporaryPersonnel, 'Переменный состав')
            ),
        ];
    }

    private function collectAllStats(Unit $unit): array
    {
        $stats = $this->collectUnitStats($unit);
        
        foreach ($unit->children as $child) {
            $stats['children'][] = $this->collectAllStats($child);
        }
        
        return $stats;
    }

    private function fillRows($sheet, array $stats, int &$row, int &$num, array &$academyTotals, int $level = 0): void
    {
        $indent = str_repeat('    ', $level);
        $permanent = $stats['permanent'];
        $temporary = $stats['temporary'];
        $totalPresent = $permanent['present'] + $temporary['present'];

        $sheet->setCellValue('A' . $row, $num);
        $sheet->setCellValue('B' . $row, $indent . $stats['unit_name']);

        // По штату
        $sheet->setCellValue('C' . $row, $this->fillEmpty($permanent['staff']));
        $sheet->setCellValue('D' . $row, $this->fillEmpty($temporary['staff']));

        // По списку
        $sheet->setCellValue('E' . $row, $this->fillEmpty($permanent['total']));
        $sheet->setCellValue('F' . $row, $this->fillEmpty($temporary['total']));

        // Налицо
        $sheet->setCellValue('G' . $row, $this->fillEmpty($permanent['present']));
        $sheet->setCellValue('H' . $row, $this->fillEmpty($temporary['present']));

        // Остальные статусы
        $colIndex = 9;
        foreach ($this->service->getAbsentStatuses() as $status) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $permanentVal = $permanent[$status->name] ?? 0;
            $temporaryVal = $temporary[$status->name] ?? 0;

            $sheet->setCellValue($col . $row, $this->fillEmpty($permanentVal));
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $row, $this->fillEmpty($temporaryVal));
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2) . $row, $this->fillEmpty($permanentVal + $temporaryVal));

            $academyTotals['permanent_' . $status->name] += $permanentVal;
            $academyTotals['temporary_' . $status->name] += $temporaryVal;
            $colIndex += 3;
        }

        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $row, $this->fillEmpty($totalPresent));

        // Обновляем итоги
        $academyTotals['permanent_staff'] += $permanent['staff'];
        $academyTotals['temporary_staff'] += $temporary['staff'];
        $academyTotals['permanent_total'] += $permanent['total'];
        $academyTotals['temporary_total'] += $temporary['total'];
        $academyTotals['permanent_present'] += $permanent['present'];
        $academyTotals['temporary_present'] += $temporary['present'];
        $academyTotals['total_present'] += $totalPresent;

        $row++;
        $num++;

        foreach ($stats['children'] as $child) {
            $this->fillRows($sheet, $child, $row, $num, $academyTotals, $level + 1);
        }
    }

    private function addTotalRow($sheet, int $row, array $academyTotals): void
    {
        $sheet->setCellValue('B' . $row, 'ИТОГО по академии');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);

        $sheet->setCellValue('C' . $row, $this->fillEmpty($academyTotals['permanent_staff']));
        $sheet->setCellValue('D' . $row, $this->fillEmpty($academyTotals['temporary_staff']));
        $sheet->setCellValue('E' . $row, $this->fillEmpty($academyTotals['permanent_total']));
        $sheet->setCellValue('F' . $row, $this->fillEmpty($academyTotals['temporary_total']));
        $sheet->setCellValue('G' . $row, $this->fillEmpty($academyTotals['permanent_present']));
        $sheet->setCellValue('H' . $row, $this->fillEmpty($academyTotals['temporary_present']));

        $colIndex = 9;
        foreach ($this->service->getAbsentStatuses() as $status) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $permanentVal = $academyTotals['permanent_' . $status->name] ?? 0;
            $temporaryVal = $academyTotals['temporary_' . $status->name] ?? 0;

            $sheet->setCellValue($col . $row, $this->fillEmpty($permanentVal));
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $row, $this->fillEmpty($temporaryVal));
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2) . $row, $this->fillEmpty($permanentVal + $temporaryVal));
            $colIndex += 3;
        }

        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $row, $this->fillEmpty($academyTotals['total_present']));
    }

    private function createAbsentSheet($spreadsheet, array $allAbsentList, string $academyName): void
    {
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Отсутствующие');

        $sheet2->setCellValue('A1', 'СПИСОК ОТСУТСТВУЮЩЕГО ЛИЧНОГО СОСТАВА');
        $sheet2->mergeCells('A1:E1');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet2->setCellValue('A2', $academyName);
        $sheet2->mergeCells('A2:E2');
        $sheet2->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers2 = ['№ п/п', 'Воинское звание', 'ФИО', 'Категория', 'Причина отсутствия'];

        foreach ($headers2 as $i => $header) {
            $col = chr(65 + $i);
            $sheet2->setCellValue($col . '4', $header);
            $sheet2->getStyle($col . '4')->getFont()->setBold(true);
            $sheet2->getStyle($col . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $row2 = 5;
        $num2 = 1;

        if (empty($allAbsentList)) {
            $sheet2->setCellValue('A' . $row2, 'Все военнослужащие находятся в строю');
            $sheet2->mergeCells('A' . $row2 . ':E' . $row2);
            $sheet2->getStyle('A' . $row2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle('A' . $row2)->getFont()->setItalic(true)->getColor()->setRGB('808080');
        } else {
            foreach ($allAbsentList as $absent) {
                $sheet2->setCellValue('A' . $row2, $num2++);
                $sheet2->setCellValue('B' . $row2, $absent['rank'] ?: '—');
                $sheet2->setCellValue('C' . $row2, $absent['fio'] ?: '—');
                $sheet2->setCellValue('D' . $row2, $absent['category'] ?: '—');
                $sheet2->setCellValue('E' . $row2, $absent['reason'] ?: '—');
                $row2++;
            }

            $sheet2->getStyle('A4:E' . ($row2 - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
        }

        $sheet2->getStyle('A5:A' . ($row2 - 1))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function collectAllAbsentList(array $stats): array
    {
        $absentList = [];
        
        // Добавляем отсутствующих из текущего подразделения
        if (isset($stats['absent_list'])) {
            $absentList = array_merge($absentList, $stats['absent_list']);
        }
        
        // Рекурсивно собираем из дочерних подразделений
        foreach ($stats['children'] as $child) {
            $absentList = array_merge($absentList, $this->collectAllAbsentList($child));
        }
        
        return $absentList;
    }

    public function export($unitId = 1)
    {
        try {
            $academy = Unit::with([
                'personnel.rank', 
                'personnel.currentStatus',
                'children' => function($q) {
                    $q->orderBy('name');
                },
                'children.personnel',
                'categories.positions'
            ])->findOrFail($unitId);

            $stats = $this->collectAllStats($academy);
            $allAbsentList = $this->collectAllAbsentList($stats);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Строевая записка');

            // Заголовки
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

            // Шапка таблицы
            $sheet->setCellValue('A4', '№ п/п');
            $sheet->setCellValue('B4', 'Подразделение');
            $sheet->setCellValue('C4', 'По штату');
            $sheet->setCellValue('D4', '');
            $sheet->setCellValue('E4', 'По списку');
            $sheet->setCellValue('F4', '');
            $sheet->setCellValue('G4', 'Налицо');
            $sheet->setCellValue('H4', '');

            $colIndex = 9;
            foreach ($this->service->getAbsentStatuses() as $status) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($col . '4', $status->name);
                $sheet->mergeCells($col . '4:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2) . '4');
                $colIndex += 3;
            }
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . '4', 'Итого');

            // Подзаголовки
            $sheet->setCellValue('C5', 'пост.');
            $sheet->setCellValue('D5', 'перем.');
            $sheet->setCellValue('E5', 'пост.');
            $sheet->setCellValue('F5', 'перем.');
            $sheet->setCellValue('G5', 'пост.');
            $sheet->setCellValue('H5', 'перем.');

            $colIndex = 9;
            foreach ($this->service->getAbsentStatuses() as $status) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($col . '5', 'пост.');
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . '5', 'перем.');
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2) . '5', 'всего');
                
                // Вертикальная ориентация
                $this->setVerticalText($sheet, $col . '5');
                $this->setVerticalText($sheet, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . '5');
                $colIndex += 3;
            }

            // Объединение ячеек
            $sheet->mergeCells('C4:D4');
            $sheet->mergeCells('E4:F4');
            $sheet->mergeCells('G4:H4');

            // Данные
            $row = 6;
            $num = 1;
            $academyTotals = [
                'permanent_staff' => 0, 'temporary_staff' => 0,
                'permanent_total' => 0, 'temporary_total' => 0,
                'permanent_present' => 0, 'temporary_present' => 0,
                'total_present' => 0,
            ];
            foreach ($this->service->getAbsentStatuses() as $status) {
                $academyTotals['permanent_' . $status->name] = 0;
                $academyTotals['temporary_' . $status->name] = 0;
            }

            $this->fillRows($sheet, $stats, $row, $num, $academyTotals);
            $this->addTotalRow($sheet, $row, $academyTotals);

            // Стили
            $lastColIndex = $colIndex;
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);
            
            $sheet->getStyle('A4:' . $lastCol . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            $sheet->getStyle('A4:' . $lastCol . '5')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');

            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getFont()->setBold(true);
            $sheet->getStyle('B6:B' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C6:' . $lastCol . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Автоширина
            $sheet->getColumnDimension('A')->setWidth(8);
            $sheet->getColumnDimension('B')->setWidth(35);
            for ($i = 3; $i <= $lastColIndex; $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($col)->setWidth(6);
            }

            // Оборотная сторона
            $this->createAbsentSheet($spreadsheet, $allAbsentList, $academy->name);

            // Сохраняем
            $filename = 'akademicheskaya_stroevaya_' . date('Y-m-d_H-i-s') . '.xlsx';
            $path = storage_path('app/public/' . $filename);

            if (!file_exists(storage_path('app/public'))) {
                mkdir(storage_path('app/public'), 0777, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($path);

            return response()->json([
                'success' => true,
                'message' => 'Академическая строевая записка создана',
                'file' => $filename,
                'download_url' => url('/storage/' . $filename),
                'total_personnel' => $academy->personnel->count(),
                'total_absent' => count($allAbsentList),
                'generated_at' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ], 500);
        }
    }
}