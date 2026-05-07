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

    public function export($unitId)
    {
        try {
            $unit = Unit::with(['personnel.rank', 'personnel.currentStatus'])->findOrFail($unitId);

            $rankCategories = [
                'officers' => ['ranks' => range(10, 20), 'name' => 'Офицеры'],
                'praporshchiks' => ['ranks' => [8, 9], 'name' => 'Прапорщики'],
                'sergeants' => ['ranks' => range(5, 7), 'name' => 'Сержанты'],
                'soldiers' => ['ranks' => range(1, 4), 'name' => 'Солдаты'],
            ];

            $stats = [];
            $absentList = [];

            foreach ($rankCategories as $category) {
                $personnel = $unit->personnel->filter(function ($p) use ($category) {
                    return $p->rank && in_array($p->rank->id, $category['ranks']);
                });

                $staff = $this->service->getStaffCountByRankCategory($unit->id, $category['ranks']);
                $personnelStats = $this->service->getFullStats($personnel);

                $stats[] = array_merge(
                    ['name' => $category['name'], 'staff' => $staff],
                    $personnelStats
                );

                // Собираем отсутствующих
                $absentList = array_merge($absentList, $this->service->getAbsentPersonnel($personnel, $category['name']));
            }

            // Считаем итоги
            $totals = ['staff' => 0, 'total' => 0, 'present' => 0];
            foreach ($this->service->getAbsentStatuses() as $status) {
                $totals[$status->name] = 0;
            }

            foreach ($stats as $stat) {
                $totals['staff'] += $stat['staff'];
                $totals['total'] += $stat['total'];
                $totals['present'] += $stat['present'];
                foreach ($this->service->getAbsentStatuses() as $status) {
                    $totals[$status->name] += $stat[$status->name] ?? 0;
                }
            }

            // ==================== EXCEL ====================
            $spreadsheet = new Spreadsheet();

            // ---------- ЛИСТ 1: Строевая записка ----------
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
            $headers = ['№', 'Категория', 'По штату', 'По списку', 'Налицо'];
            foreach ($this->service->getAbsentStatuses() as $status) {
                $headers[] = $status->name;
            }

            foreach ($headers as $i => $header) {
                $col = chr(65 + $i);
                $sheet->setCellValue($col . '5', $header);
                $sheet->getStyle($col . '5')->getFont()->setBold(true);
                $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Вертикальная ориентация для заголовков (кроме первых двух)
            for ($i = 2; $i < count($headers); $i++) {
                $col = chr(65 + $i);
                $this->setVerticalText($sheet, $col . '5');
            }

            // Данные
            $row = 6;
            $num = 1;

            foreach ($stats as $stat) {
                $rowData = [$num++, $stat['name'], $stat['staff'], $stat['total'], $stat['present']];
                foreach ($this->service->getAbsentStatuses() as $status) {
                    $rowData[] = $stat[$status->name] ?? 0;
                }

                foreach ($rowData as $idx => $val) {
                    $col = chr(65 + $idx);
                    $sheet->setCellValue($col . $row, $this->fillEmpty($val));
                }
                $row++;
            }

            // Итого
            $sheet->setCellValue('B' . $row, 'ИТОГО');
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);

            $totalRow = [$totals['staff'], $totals['total'], $totals['present']];
            foreach ($this->service->getAbsentStatuses() as $status) {
                $totalRow[] = $totals[$status->name] ?? 0;
            }

            foreach ($totalRow as $idx => $val) {
                $col = chr(65 + $idx + 2);
                $sheet->setCellValue($col . $row, $this->fillEmpty($val));
            }

            // Стили для первой страницы
            $lastCol = chr(65 + count($headers) - 1);
            $sheet->getStyle('A5:' . $lastCol . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            $sheet->getStyle('A5:' . $lastCol . '5')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');

            $sheet->getStyle('C6:' . $lastCol . $row)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ---------- ЛИСТ 2: Отсутствующие (оборотная сторона) ----------
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Отсутствующие');
            $sheet2->setCellValue('A1', 'СПИСОК ОТСУТСТВУЮЩЕГО ЛИЧНОГО СОСТАВА');
            $sheet2->mergeCells('A1:E1');
            $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet2->setCellValue('A2', $unit->name);
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

            if (empty($absentList)) {
                $sheet2->setCellValue('A' . $row2, 'Все военнослужащие находятся в строю');
                $sheet2->mergeCells('A' . $row2 . ':E' . $row2);
                $sheet2->getStyle('A' . $row2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle('A' . $row2)->getFont()->setItalic(true)->getColor()->setRGB('808080');
            } else {
                foreach ($absentList as $absent) {
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

            // Центрирование номера
            $sheet2->getStyle('A5:A' . ($row2 - 1))
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Сохраняем
            $filename = 'stroevaya_' . $unit->id . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            $path = storage_path('app/public/' . $filename);

            if (!file_exists(storage_path('app/public'))) {
                mkdir(storage_path('app/public'), 0777, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($path);

            return response()->json([
                'success' => true,
                'message' => 'Строевая записка создана',
                'file' => $filename,
                'download_url' => url('/storage/' . $filename),
                'total_personnel' => $unit->personnel->count(),
                'total_absent' => count($absentList),
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