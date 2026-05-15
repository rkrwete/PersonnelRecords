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

class DutyRosterExportController extends Controller
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

    /**
     * Группировка по РАНГАМ (для научной роты)
     */
    private function getStatsByRanks(Unit $unit): array
    {
        $rankCategories = [
            'officers' => ['ranks' => range(10, 20), 'name' => 'Офицеры'],
            'praporshchiks' => ['ranks' => [8, 9], 'name' => 'Прапорщики'],
            'sergeants' => ['ranks' => range(5, 7), 'name' => 'Сержанты'],
            'soldiers' => ['ranks' => range(1, 4), 'name' => 'Солдаты'],
        ];

        $stats = [];

        foreach ($rankCategories as $category) {
            $personnel = $unit->personnel->filter(function ($p) use ($category) {
                return $p->rank && in_array($p->rank->id, $category['ranks']);
            });

            $staff = $this->service->getStaffCountByRankCategory($unit->id, $category['ranks']);
            $total = $personnel->count();
            $present = $personnel->where('current_status_id', 1)->count();
            $duty = $personnel->where('current_status_id', 5)->count();
            $mission = $personnel->where('current_status_id', 6)->count();
            $leave = $personnel->where('current_status_id', 4)->count();
            $sick = $personnel->filter(fn($p) => in_array($p->current_status_id, [2, 3]))->count();
            $dismissed = $personnel->where('current_status_id', 9)->count();
            $other = $personnel->filter(fn($p) => in_array($p->current_status_id, [7, 8]))->count();

            $stats[] = [
                'name' => $category['name'],
                'staff' => $staff,
                'total' => $total,
                'present' => $present,
                'duty' => $duty,
                'mission' => $mission,
                'leave' => $leave,
                'sick' => $sick,
                'dismissed' => $dismissed,
                'other' => $other,
            ];
        }

        return $stats;
    }

    /**
     * Список отсутствующих по РАНГАМ (для научной роты)
     */
    private function getAbsentListByRanks(Unit $unit): array
    {
        $rankCategories = [
            'officers' => ['ranks' => range(10, 20), 'name' => 'Офицеры'],
            'praporshchiks' => ['ranks' => [8, 9], 'name' => 'Прапорщики'],
            'sergeants' => ['ranks' => range(5, 7), 'name' => 'Сержанты'],
            'soldiers' => ['ranks' => range(1, 4), 'name' => 'Солдаты'],
        ];

        $absentList = [];

        foreach ($rankCategories as $category) {
            $personnel = $unit->personnel->filter(function ($p) use ($category) {
                return $p->rank && in_array($p->rank->id, $category['ranks']);
            });

            foreach ($personnel as $p) {
                if ($p->current_status_id != 1) {
                    $absentList[] = [
                        'rank' => $p->rank->name ?? '',
                        'fio' => trim($p->last_name . ' ' . $p->first_name . ' ' . $p->middle_name),
                        'category' => $category['name'],
                        'reason' => $p->currentStatus->name ?? 'Неизвестно'
                    ];
                }
            }
        }

        return $absentList;
    }

    /**
     * Группировка по КАТЕГОРИЯМ (для остальных подразделений)
     */
    private function getStatsByCategories(Unit $unit): array
    {
        $stats = [];

        foreach ($unit->categories as $category) {
            $positionIds = $category->positions->pluck('id')->toArray();
            $personnel = $unit->personnel->filter(function ($p) use ($positionIds) {
                return in_array($p->position_id, $positionIds);
            });

            $staff = $category->positions->sum('count');
            $total = $personnel->count();
            $present = $personnel->where('current_status_id', 1)->count();
            $duty = $personnel->where('current_status_id', 5)->count();
            $mission = $personnel->where('current_status_id', 6)->count();
            $leave = $personnel->where('current_status_id', 4)->count();
            $sick = $personnel->filter(fn($p) => in_array($p->current_status_id, [2, 3]))->count();
            $dismissed = $personnel->where('current_status_id', 9)->count();
            $other = $personnel->filter(fn($p) => in_array($p->current_status_id, [7, 8]))->count();

            $stats[] = [
                'name' => $category->name,
                'staff' => $staff,
                'total' => $total,
                'present' => $present,
                'duty' => $duty,
                'mission' => $mission,
                'leave' => $leave,
                'sick' => $sick,
                'dismissed' => $dismissed,
                'other' => $other,
            ];
        }

        return $stats;
    }

    /**
     * Список отсутствующих по КАТЕГОРИЯМ (для остальных подразделений)
     */
    private function getAbsentListByCategories(Unit $unit): array
    {
        $absentList = [];

        foreach ($unit->categories as $category) {
            $positionIds = $category->positions->pluck('id')->toArray();
            $personnel = $unit->personnel->filter(function ($p) use ($positionIds) {
                return in_array($p->position_id, $positionIds);
            });

            foreach ($personnel as $p) {
                if ($p->current_status_id != 1) {
                    $absentList[] = [
                        'rank' => $p->rank->name ?? '',
                        'fio' => trim($p->last_name . ' ' . $p->first_name . ' ' . $p->middle_name),
                        'category' => $category->name,
                        'reason' => $p->currentStatus->name ?? 'Неизвестно'
                    ];
                }
            }
        }

        return $absentList;
    }

    public function export($unitId)
    {
        try {
            $unit = Unit::with(['personnel.rank', 'personnel.currentStatus', 'categories.positions'])->findOrFail($unitId);

            // ==================== ВЫБИРАЕМ СПОСОБ ГРУППИРОВКИ ====================
            // Для научной роты (id 42) используем группировку по РАНГАМ
            // Для всех остальных - группировку по КАТЕГОРИЯМ
            
            $isScientificCompany = ($unit->id == 42 || $unit->name == 'Научная рота');
            
            if ($isScientificCompany) {
                $stats = $this->getStatsByRanks($unit);
                $absentList = $this->getAbsentListByRanks($unit);
            } else {
                $stats = $this->getStatsByCategories($unit);
                $absentList = $this->getAbsentListByCategories($unit);
            }

            // Считаем итоги
            $totals = ['staff' => 0, 'total' => 0, 'present' => 0, 'duty' => 0, 'mission' => 0, 'leave' => 0, 'sick' => 0, 'dismissed' => 0, 'other' => 0];

            foreach ($stats as $stat) {
                $totals['staff'] += $stat['staff'];
                $totals['total'] += $stat['total'];
                $totals['present'] += $stat['present'];
                $totals['duty'] += $stat['duty'];
                $totals['mission'] += $stat['mission'];
                $totals['leave'] += $stat['leave'];
                $totals['sick'] += $stat['sick'];
                $totals['dismissed'] += $stat['dismissed'];
                $totals['other'] += $stat['other'];
            }

            // ==================== EXCEL ====================
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Строевая записка');

            // Заголовки
            $sheet->setCellValue('A1', 'СТРОЕВАЯ ЗАПИСКА');
            $sheet->mergeCells('A1:K1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', $unit->name);
            $sheet->mergeCells('A2:K2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A3', 'на "' . date('d.m.Y') . '"');
            $sheet->mergeCells('A3:K3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Шапка таблицы
            $headers = ['№', 'Категория', 'По штату', 'По списку', 'Налицо', 'Наряд', 'Командировка', 'Отпуск', 'Больные', 'Увольнение', 'Прочее'];
            $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

            foreach ($headers as $i => $header) {
                $sheet->setCellValue($cols[$i] . '5', $header);
                $sheet->getStyle($cols[$i] . '5')->getFont()->setBold(true);
                $sheet->getStyle($cols[$i] . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
            }

            // Вертикальная ориентация для заголовков (кроме первых двух)
            for ($i = 2; $i < count($headers); $i++) {
                $this->setVerticalText($sheet, $cols[$i] . '5');
            }

            // Данные
            $row = 6;
            $num = 1;

            foreach ($stats as $stat) {
                $sheet->setCellValue('A' . $row, $num++);
                $sheet->setCellValue('B' . $row, $stat['name']);
                $sheet->setCellValue('C' . $row, $this->fillEmpty($stat['staff']));
                $sheet->setCellValue('D' . $row, $this->fillEmpty($stat['total']));
                $sheet->setCellValue('E' . $row, $this->fillEmpty($stat['present']));
                $sheet->setCellValue('F' . $row, $this->fillEmpty($stat['duty']));
                $sheet->setCellValue('G' . $row, $this->fillEmpty($stat['mission']));
                $sheet->setCellValue('H' . $row, $this->fillEmpty($stat['leave']));
                $sheet->setCellValue('I' . $row, $this->fillEmpty($stat['sick']));
                $sheet->setCellValue('J' . $row, $this->fillEmpty($stat['dismissed']));
                $sheet->setCellValue('K' . $row, $this->fillEmpty($stat['other']));
                $row++;
            }

            // Итого
            $sheet->setCellValue('B' . $row, 'ИТОГО');
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            $sheet->setCellValue('C' . $row, $this->fillEmpty($totals['staff']));
            $sheet->setCellValue('D' . $row, $this->fillEmpty($totals['total']));
            $sheet->setCellValue('E' . $row, $this->fillEmpty($totals['present']));
            $sheet->setCellValue('F' . $row, $this->fillEmpty($totals['duty']));
            $sheet->setCellValue('G' . $row, $this->fillEmpty($totals['mission']));
            $sheet->setCellValue('H' . $row, $this->fillEmpty($totals['leave']));
            $sheet->setCellValue('I' . $row, $this->fillEmpty($totals['sick']));
            $sheet->setCellValue('J' . $row, $this->fillEmpty($totals['dismissed']));
            $sheet->setCellValue('K' . $row, $this->fillEmpty($totals['other']));

            // Границы
            $sheet->getStyle('A5:K' . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            $sheet->getStyle('A5:K5')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');

            // Центрирование чисел
            $sheet->getStyle('C6:K' . $row)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ==================== ОБОРОТНАЯ СТОРОНА ====================
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Отсутствующие');

            $sheet2->setCellValue('A1', 'СПИСОК ОТСУТСТВУЮЩЕГО ЛИЧНОГО СОСТАВА');
            $sheet2->mergeCells('A1:E1');
            $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet2->setCellValue('A2', $unit->name);
            $sheet2->mergeCells('A2:E2');
            $sheet2->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers2 = ['№ п/п', 'Звание', 'ФИО', 'Категория', 'Причина'];
            $cols2 = ['A', 'B', 'C', 'D', 'E'];

            foreach ($headers2 as $i => $header) {
                $sheet2->setCellValue($cols2[$i] . '4', $header);
                $sheet2->getStyle($cols2[$i] . '4')->getFont()->setBold(true);
                $sheet2->getStyle($cols2[$i] . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getColumnDimension($cols2[$i])->setAutoSize(true);
            }

            $row2 = 5;
            $num2 = 1;

            if (empty($absentList)) {
                $sheet2->setCellValue('A' . $row2, 'Все военнослужащие находятся в строю');
                $sheet2->mergeCells('A' . $row2 . ':E' . $row2);
                $sheet2->getStyle('A' . $row2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle('A' . $row2)->getFont()->setItalic(true)->getColor()->setRGB('808080');
            } else {
                foreach ($absentList as $absent) {
                    $sheet2->setCellValue('A' . $row2, $num2++);
                    $sheet2->setCellValue('B' . $row2, $absent['rank']);
                    $sheet2->setCellValue('C' . $row2, $absent['fio']);
                    $sheet2->setCellValue('D' . $row2, $absent['category']);
                    $sheet2->setCellValue('E' . $row2, $absent['reason']);
                    $row2++;
                }

            $sheet2->getStyle('A4:E' . ($row2 - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                    ]
                ]
            ]);
            }

            // Центрирование номера
            $sheet2->getStyle('A5:A' . ($row2 - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Сохраняем
            $filename = 'stroevaya_' . $unit->id . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            $path = storage_path('app/public/' . $filename);

            if (!file_exists(storage_path('app/public'))) {
                mkdir(storage_path('app/public'), 0777, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($path);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ], 500);
        }
    }
}