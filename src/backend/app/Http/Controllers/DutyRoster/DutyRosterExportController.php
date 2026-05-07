<?php

namespace App\Http\Controllers\DutyRoster;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\Position;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DutyRosterExportController extends Controller
{
    // ==================== СТАТУСЫ ====================
    private const STATUS_PRESENT = 1;        // Налицо
    private const STATUS_SICK = [2, 3];      // Больные
    private const STATUS_LEAVE = 4;          // Отпуск
    private const STATUS_DUTY = 5;           // Наряд
    private const STATUS_MISSION = 6;        // Командировка
    private const STATUS_OTHER = [7, 8];     // Прочее
    private const STATUS_DISMISSED = 9;      // Увольнение

    /**
     * Заполняет пустые значения символом "0"
     */
    private function fillEmptyCells(array $row, $default = '0')
    {
        foreach ($row as $key => $value) {
            if ($value === null || $value === '' || $value === false || $value === 0) {
                $row[$key] = $default;
            }
        }
        return $row;
    }

    /**
     * Получает штатную численность по категориям
     * Проходим по всем должностям подразделения и суммируем их count
     */
    private function getStaffCountByCategory(Unit $unit, array $categoryRanks): int
    {
        $staffCount = 0;
        
        $positions = Position::whereHas('category', function ($query) use ($unit) {
            $query->where('unit_id', $unit->id);
        })->with('personnel.rank')->get();
        
        foreach ($positions as $position) {
            // Проверяем, есть ли в должности сотрудник с рангом из нужной категории
            $hasMatchingRank = $position->personnel->contains(function ($person) use ($categoryRanks) {
                return $person->rank && in_array($person->rank->id, $categoryRanks);
            });
            
            if ($hasMatchingRank) {
                $staffCount += $position->count;
            }
        }
        
        return $staffCount;
    }

    public function export($unitId)
    {
        try {
            $unit = Unit::with(['personnel.rank', 'personnel.currentStatus', 'categories.positions.personnel.rank'])
                ->findOrFail($unitId);

            // ==================== КАТЕГОРИИ ====================
            $categories = [
                'officers' => ['ranks' => range(10, 20), 'name' => 'Офицеры'],
                'praporshchiks' => ['ranks' => [8, 9], 'name' => 'Прапорщики'],
                'sergeants' => ['ranks' => range(5, 7), 'name' => 'Сержанты'],
                'soldiers' => ['ranks' => range(1, 4), 'name' => 'Солдаты'],
            ];

            $stats = [];
            $absentList = [];

            foreach ($categories as $key => $cat) {

                // Фильтруем личный состав по категории рангов
                $personnel = $unit->personnel->filter(function ($p) use ($cat) {
                    return $p->rank && in_array($p->rank->id, $cat['ranks']);
                });

                // ==================== ПО ШТАТУ (из таблицы positions) ====================
                $staff = $this->getStaffCountByCategory($unit, $cat['ranks']);

                $total = $personnel->count(); // По списку (фактически)

                // ==================== ПОДСЧЁТ СТАТУСОВ ====================
                $present = $personnel->where('current_status_id', self::STATUS_PRESENT)->count();
                $duty = $personnel->where('current_status_id', self::STATUS_DUTY)->count();
                $mission = $personnel->where('current_status_id', self::STATUS_MISSION)->count();
                $leave = $personnel->where('current_status_id', self::STATUS_LEAVE)->count();

                $sick = $personnel->filter(fn($p) =>
                    in_array($p->current_status_id, self::STATUS_SICK)
                )->count();

                $dismissed = $personnel->where('current_status_id', self::STATUS_DISMISSED)->count();

                $other = $personnel->filter(fn($p) =>
                    in_array($p->current_status_id, self::STATUS_OTHER)
                )->count();

                $stats[$key] = [
                    'name' => $cat['name'],
                    'staff' => $staff,        // По штату (новый столбец)
                    'total' => $total,        // По списку
                    'present' => $present,    // Налицо
                    'duty' => $duty,          // Наряд
                    'mission' => $mission,    // Командировка
                    'leave' => $leave,        // Отпуск
                    'sick' => $sick,          // Больные
                    'dismissed' => $dismissed, // Увольнение
                    'other' => $other,        // Прочее
                ];

                // ==================== ОТСУТСТВУЮЩИЕ (включая НАРЯД) ====================
                $absentStatuses = array_merge(
                    self::STATUS_SICK,
                    self::STATUS_OTHER,
                    [self::STATUS_LEAVE, self::STATUS_MISSION, self::STATUS_DUTY, self::STATUS_DISMISSED]
                );

                foreach ($personnel as $p) {
                    if (in_array($p->current_status_id, $absentStatuses)) {
                        $absentList[] = [
                            'rank' => $p->rank->name ?? '@',
                            'fio' => trim($p->last_name . ' ' . $p->first_name . ' ' . $p->middle_name) ?: '@',
                            'category' => $cat['name'] ?? '@',
                            'reason' => $p->currentStatus->name ?? '@'
                        ];
                    }
                }
            }

            // ==================== EXCEL ====================
            $spreadsheet = new Spreadsheet();

            // ---------- ЛИСТ 1 ----------
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Строевая записка');

            $sheet->setCellValue('A1', 'СТРОЕВАЯ ЗАПИСКА');
            $sheet->mergeCells('A1:K1');  // Было J1, стало K1 (добавили столбец)
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', $unit->name);
            $sheet->mergeCells('A2:K2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A3', 'на "' . date('d.m.Y') . '"');
            $sheet->mergeCells('A3:K3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Заголовки с новым столбцом "По штату"
            $headers = ['№', 'Категория', 'По штату', 'По списку', 'Налицо', 'Наряд', 'Командировка', 'Отпуск', 'Больные', 'Увольнение', 'Прочее'];
            $cols = range('A', 'K');  // A-K = 11 столбцов

            foreach ($headers as $i => $header) {
                $sheet->setCellValue($cols[$i] . '5', $header);
                $sheet->getStyle($cols[$i] . '5')->getFont()->setBold(true);
                $sheet->getStyle($cols[$i] . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
            }

            $row = 6;
            $num = 1;

            $totals = [
                'staff' => 0,      // Итог по штату (новый)
                'total' => 0, 
                'present' => 0, 
                'duty' => 0,
                'mission' => 0, 
                'leave' => 0, 
                'sick' => 0,
                'dismissed' => 0, 
                'other' => 0
            ];

            foreach ($stats as $stat) {
                // Формируем данные с новым столбцом "staff"
                $rowData = $this->fillEmptyCells([
                    $num++,
                    $stat['name'],
                    $stat['staff'],      // По штату
                    $stat['total'],      // По списку
                    $stat['present'],
                    $stat['duty'],
                    $stat['mission'],
                    $stat['leave'],
                    $stat['sick'],
                    $stat['dismissed'],
                    $stat['other'],
                ]);

                $sheet->fromArray($rowData, null, 'A' . $row);

                // Обновляем итоги
                $totals['staff'] += $stat['staff'];
                $totals['total'] += $stat['total'];
                $totals['present'] += $stat['present'];
                $totals['duty'] += $stat['duty'];
                $totals['mission'] += $stat['mission'];
                $totals['leave'] += $stat['leave'];
                $totals['sick'] += $stat['sick'];
                $totals['dismissed'] += $stat['dismissed'];
                $totals['other'] += $stat['other'];

                $row++;
            }

            // ИТОГО
            $sheet->setCellValue('B' . $row, 'ИТОГО');
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);

            // Заполняем итоговую строку
            $totalRowData = $this->fillEmptyCells([
                $totals['staff'],
                $totals['total'],
                $totals['present'],
                $totals['duty'],
                $totals['mission'],
                $totals['leave'],
                $totals['sick'],
                $totals['dismissed'],
                $totals['other']
            ]);
            
            $sheet->fromArray($totalRowData, null, 'C' . $row);

            // Центрирование чисел
            $sheet->getStyle('C6:K' . $row)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Границы
            $sheet->getStyle('A5:K' . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // ---------- ЛИСТ 2 ----------
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Отсутствующие');

            $sheet2->setCellValue('A1', 'СПИСОК ОТСУТСТВУЮЩИХ');
            $sheet2->mergeCells('A1:E1');
            $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet2->setCellValue('A2', $unit->name);
            $sheet2->mergeCells('A2:E2');
            $sheet2->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers2 = ['№', 'Звание', 'ФИО', 'Категория', 'Причина'];

            foreach ($headers2 as $i => $header) {
                $col = chr(65 + $i);
                $sheet2->setCellValue($col . '4', $header);
                $sheet2->getStyle($col . '4')->getFont()->setBold(true);
                $sheet2->getStyle($col . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getColumnDimension($col)->setAutoSize(true);
            }

            $row2 = 5;

            foreach ($absentList as $i => $a) {
                $rowData = $this->fillEmptyCells([
                    $i + 1,
                    $a['rank'],
                    $a['fio'],
                    $a['category'],
                    $a['reason']
                ]);
                
                $sheet2->fromArray($rowData, null, 'A' . $row2++);
            }

            if (!empty($absentList)) {
                $sheet2->getStyle('A4:E' . ($row2 - 1))->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
            } else {
                $sheet2->setCellValue('A' . $row2, 'Нет отсутствующих');
                $sheet2->mergeCells('A' . $row2 . ':E' . $row2);
                $sheet2->getStyle('A' . $row2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // ==================== СОХРАНЕНИЕ ====================
            $filename = 'stroevaya_' . $unit->id . '_' . date('Y-m-d') . '.xlsx';
            $path = storage_path('app/public/' . $filename);

            if (!file_exists(storage_path('app/public'))) {
                mkdir(storage_path('app/public'), 0777, true);
            }

            (new Xlsx($spreadsheet))->save($path);

            return response()->json([
                'success' => true,
                'file' => $filename,
                'url' => url('/storage/' . $filename),
                'total_personnel' => $unit->personnel->count(),
                'total_absent' => count($absentList)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}