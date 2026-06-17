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
use PhpOffice\PhpSpreadsheet\Style\Font;

class DutyRosterExportController extends Controller
{
    private DutyRosterService $service;

    // ID статусов
    private const STATUS_IDS = [
        // Вне кафедры
        'NARYAD' => 2,                    // Наряд
        'LAZARET' => 3,                   // Лазарет
        'OSVOBOZHDENIE_PO_BOLEZNI' => 17, // Освобождены по болезни
        
        // Вне академии
        'GOSPITAL' => 4,                  // Госпиталь
        'OTPUSK' => 5,                    // Отпуск
        'KOMANDIROVKA' => 6,              // Командировка
        'UVOLNENIE' => 7,                 // Увольнение
        'SUTOCHNOE_UVOLNENIE' => 8,       // Суточное увольнение
        'AREST' => 9,                     // Арест
        'SOCH' => 10,                     // СОЧ
        'PROCHEE' => 11,                  // Прочее
        'GARNIZON' => 18,                 // Гарнизон
        
        // На лицо
        'NALITSO_123' => 12,              // 123 в/г
        'NALITSO_84' => 13,               // 84 в/г
        'NALITSO_6' => 14,                // 6 в/г
        'NALITSO_UC_IT' => 15,            // УЦ (ИТ)
        'NALITSO_DR' => 16,               // Др. прич.
    ];

    public function __construct(DutyRosterService $service)
    {
        $this->service = $service;
    }

    private function fillEmpty($value): string
    {
        return ($value === null || $value === '' || $value === false) ? '0' : (string) $value;
    }

    /**
     * Статистика для одной строки (категории личного состава)
     */
    private function getRowStats($personnel, int $staffCount, string $categoryName): array
    {
        // На лицо (сумма подстатусов)
        $nalitso_123 = $personnel->where('current_status_id', self::STATUS_IDS['NALITSO_123'])->count();
        $nalitso_84 = $personnel->where('current_status_id', self::STATUS_IDS['NALITSO_84'])->count();
        $nalitso_6 = $personnel->where('current_status_id', self::STATUS_IDS['NALITSO_6'])->count();
        $nalitso_uc_it = $personnel->where('current_status_id', self::STATUS_IDS['NALITSO_UC_IT'])->count();
        $nalitso_dr = $personnel->where('current_status_id', self::STATUS_IDS['NALITSO_DR'])->count();
        $nalitso_total = $nalitso_123 + $nalitso_84 + $nalitso_6 + $nalitso_uc_it + $nalitso_dr;

        // Вне кафедры
        $naryad_v_akademii = $personnel->where('current_status_id', self::STATUS_IDS['NARYAD'])->count();
        $lazaret = $personnel->where('current_status_id', self::STATUS_IDS['LAZARET'])->count();
        $osvobozhdenie_po_bolezni = $personnel->where('current_status_id', self::STATUS_IDS['OSVOBOZHDENIE_PO_BOLEZNI'])->count();
        $vne_kafedry_total = $naryad_v_akademii + $lazaret + $osvobozhdenie_po_bolezni;

        // Вне академии
        $otpusk = $personnel->where('current_status_id', self::STATUS_IDS['OTPUSK'])->count();
        $garnizon = $personnel->where('current_status_id', self::STATUS_IDS['GARNIZON'])->count();
        $gospital = $personnel->where('current_status_id', self::STATUS_IDS['GOSPITAL'])->count();
        $komandirovka = $personnel->where('current_status_id', self::STATUS_IDS['KOMANDIROVKA'])->count();
        
        $prochee = $personnel->where('current_status_id', self::STATUS_IDS['UVOLNENIE'])->count()
                  + $personnel->where('current_status_id', self::STATUS_IDS['SUTOCHNOE_UVOLNENIE'])->count()
                  + $personnel->where('current_status_id', self::STATUS_IDS['AREST'])->count()
                  + $personnel->where('current_status_id', self::STATUS_IDS['SOCH'])->count()
                  + $personnel->where('current_status_id', self::STATUS_IDS['PROCHEE'])->count();
        
        $vne_akademii_total = $otpusk + $garnizon + $gospital + $komandirovka + $prochee;

        return [
            'name'                => $categoryName,
            'staff'               => $staffCount,
            'total'               => $personnel->count(),
            'nalitso_total'       => $nalitso_total,
            'nalitso_123'         => $nalitso_123,
            'nalitso_84'          => $nalitso_84,
            'nalitso_6'           => $nalitso_6,
            'nalitso_uc_it'       => $nalitso_uc_it,
            'nalitso_dr'          => $nalitso_dr,
            'naryad_v_akademii'   => $naryad_v_akademii,
            'lazaret'             => $lazaret,
            'osvobozhdenie_po_bolezni' => $osvobozhdenie_po_bolezni,
            'vne_kafedry_total'   => $vne_kafedry_total,
            'otpusk'              => $otpusk,
            'garnizon'            => $garnizon,
            'gospital'            => $gospital,
            'komandirovka'        => $komandirovka,
            'prochee'             => $prochee,
            'vne_akademii_total'  => $vne_akademii_total,
        ];
    }

    // ---------- Группировка по РАНГАМ (научная рота) ----------
    private function getStatsByRanks(Unit $unit): array
    {
        $rankCategories = [
            'officers'     => ['ranks' => range(10, 20), 'name' => 'Офицеры'],
            'praporshchiks'=> ['ranks' => [8, 9], 'name' => 'Прапорщики'],
            'sergeants'    => ['ranks' => range(5, 7), 'name' => 'Сержанты'],
            'soldiers'     => ['ranks' => range(1, 4), 'name' => 'Солдаты'],
        ];
        $stats = [];
        foreach ($rankCategories as $cat) {
            $personnel = $unit->personnel->filter(fn($p) => $p->rank && in_array($p->rank->id, $cat['ranks']));
            $staff = $this->service->getStaffCountByRankCategory($unit->id, $cat['ranks']);
            $stats[] = $this->getRowStats($personnel, $staff, $cat['name']);
        }
        return $stats;
    }

    // ---------- Группировка по КАТЕГОРИЯМ должностей ----------
    private function getStatsByCategories(Unit $unit): array
    {
        $stats = [];
        foreach ($unit->categories as $category) {
            $positionIds = $category->positions->pluck('id')->toArray();
            $personnel = $unit->personnel->filter(fn($p) => in_array($p->position_id, $positionIds));
            $staff = $category->positions->sum('count');
            $stats[] = $this->getRowStats($personnel, $staff, $category->name);
        }
        return $stats;
    }

    /**
     * Получить данные для оборотной стороны
     * Возвращает: personnel_list - массив всех сотрудников с информацией
     */
    private function getReverseSideData(Unit $unit): array
    {
        $nalitsoStatusIds = [
            self::STATUS_IDS['NALITSO_123'],
            self::STATUS_IDS['NALITSO_84'],
            self::STATUS_IDS['NALITSO_6'],
            self::STATUS_IDS['NALITSO_UC_IT'],
            self::STATUS_IDS['NALITSO_DR'],
        ];
        
        $personnelList = [];
        $total = 0;
        $present = 0;
        
        foreach ($unit->personnel as $p) {
            // Определяем категорию
            $categoryName = '';
            if ($p->rank) {
                $rid = $p->rank->id;
                if ($rid >= 10) $categoryName = 'Офицеры';
                elseif ($rid >= 8) $categoryName = 'Прапорщики';
                elseif ($rid >= 5) $categoryName = 'Сержанты';
                else $categoryName = 'Солдаты';
            } else {
                $categoryName = $p->position->category->name ?? '';
            }
            
            // Определяем причину отсутствия
            $isPresent = in_array($p->current_status_id, $nalitsoStatusIds);
            $reason = $isPresent ? '+' : ($p->currentStatus->name ?? 'Неизвестно');
            
            if ($isPresent) {
                $present++;
            }
            $total++;
            
            $personnelList[] = [
                'category' => $categoryName,
                'rank' => $p->rank->name ?? '',
                'fio' => trim($p->last_name . ' ' . $p->first_name . ' ' . $p->middle_name),
                'reason' => $reason,
                'is_present' => $isPresent,
            ];
        }
        
        return [
            'personnel' => $personnelList,
            'total' => $total,
            'present' => $present,
        ];
    }

    
    // ==================== ОСНОВНОЙ МЕТОД ЭКСПОРТА ====================

    public function export($unitId)
    {
        try {
            $unit = Unit::with(['personnel.rank', 'personnel.currentStatus', 'categories.positions'])->findOrFail($unitId);

            $isScientificCompany = ($unit->id == 42 || $unit->name == 'Научная рота');
            $stats = $isScientificCompany ? $this->getStatsByRanks($unit) : $this->getStatsByCategories($unit);
            $reverseData = $this->getReverseSideData($unit);

            // Подсчёт итогов для первой страницы
            $totals = [
                'staff' => 0, 'total' => 0,
                'nalitso_total' => 0,
                'nalitso_123' => 0, 'nalitso_84' => 0, 'nalitso_6' => 0, 'nalitso_uc_it' => 0, 'nalitso_dr' => 0,
                'naryad_v_akademii' => 0, 'lazaret' => 0, 'osvobozhdenie_po_bolezni' => 0,
                'otpusk' => 0, 'garnizon' => 0, 'gospital' => 0, 'komandirovka' => 0, 'prochee' => 0,
            ];
            foreach ($stats as $stat) {
                foreach ($totals as $key => $value) {
                    if (isset($stat[$key])) $totals[$key] += $stat[$key];
                }
            }

            // ==================== ЛИСТ 1: СТРОЕВАЯ ЗАПИСКА ====================
            $spreadsheet = new Spreadsheet();
            
            // Установка шрифта Times New Roman для всего документа
            $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(11);
            
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Строевая записка');

            // Заголовок
            $sheet->setCellValue('A1', 'СТРОЕВАЯ ЗАПИСКА');
            $sheet->mergeCells('A1:R1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setName('Times New Roman');
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', $unit->name);
            $sheet->mergeCells('A2:R2');
            $sheet->getStyle('A2')->getFont()->setName('Times New Roman');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A3', 'на "' . date('d.m.Y') . '"');
            $sheet->mergeCells('A3:R3');
            $sheet->getStyle('A3')->getFont()->setName('Times New Roman');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // --- Двухстрочная шапка ---
            $sheet->setCellValue('A5', '№ п/п');
            $sheet->setCellValue('B5', 'Подразделение');
            $sheet->setCellValue('C5', 'По штату');
            $sheet->setCellValue('D5', 'По списку');
            $sheet->mergeCells('A5:A6');
            $sheet->mergeCells('B5:B6');
            $sheet->mergeCells('C5:C6');
            $sheet->mergeCells('D5:D6');

            $sheet->setCellValue('E5', 'На лицо');
            $sheet->mergeCells('E5:J5');
            $nalitsoSub = ['всего', '123 в/г', '84 в/г', '6 в/г', 'УЦ (ИТ)', 'Др. прич.'];
            $col = 'E';
            foreach ($nalitsoSub as $sub) {
                $sheet->setCellValue($col . '6', $sub);
                $col++;
            }

            $sheet->setCellValue('K5', 'Вне кафедры');
            $sheet->mergeCells('K5:M5');
            $sheet->setCellValue('K6', 'Наряд');
            $sheet->setCellValue('L6', 'Лазарет');
            $sheet->setCellValue('M6', 'Освобождены по болезни');

            $sheet->setCellValue('N5', 'Находятся вне академии');
            $sheet->mergeCells('N5:R5');
            $vneSub = ['Отпуск', 'Гарнизон', 'Госпиталь', 'Командировка', 'Прочее'];
            $col = 'N';
            foreach ($vneSub as $sub) {
                $sheet->setCellValue($col . '6', $sub);
                $col++;
            }

            $headerStyle = [
                'font'      => ['bold' => true, 'name' => 'Times New Roman', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $sheet->getStyle('A5:R6')->applyFromArray($headerStyle);
            $sheet->getStyle('A5:R5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D3D3D3');

            // Данные
            $row = 7;
            $num = 1;
            foreach ($stats as $stat) {
                $sheet->setCellValue('A' . $row, $num++);
                $sheet->setCellValue('B' . $row, $stat['name']);
                $sheet->setCellValue('C' . $row, $this->fillEmpty($stat['staff']));
                $sheet->setCellValue('D' . $row, $this->fillEmpty($stat['total']));
                $sheet->setCellValue('E' . $row, $this->fillEmpty($stat['nalitso_total']));
                $sheet->setCellValue('F' . $row, $this->fillEmpty($stat['nalitso_123']));
                $sheet->setCellValue('G' . $row, $this->fillEmpty($stat['nalitso_84']));
                $sheet->setCellValue('H' . $row, $this->fillEmpty($stat['nalitso_6']));
                $sheet->setCellValue('I' . $row, $this->fillEmpty($stat['nalitso_uc_it']));
                $sheet->setCellValue('J' . $row, $this->fillEmpty($stat['nalitso_dr']));
                $sheet->setCellValue('K' . $row, $this->fillEmpty($stat['naryad_v_akademii']));
                $sheet->setCellValue('L' . $row, $this->fillEmpty($stat['lazaret']));
                $sheet->setCellValue('M' . $row, $this->fillEmpty($stat['osvobozhdenie_po_bolezni']));
                $sheet->setCellValue('N' . $row, $this->fillEmpty($stat['otpusk']));
                $sheet->setCellValue('O' . $row, $this->fillEmpty($stat['garnizon']));
                $sheet->setCellValue('P' . $row, $this->fillEmpty($stat['gospital']));
                $sheet->setCellValue('Q' . $row, $this->fillEmpty($stat['komandirovka']));
                $sheet->setCellValue('R' . $row, $this->fillEmpty($stat['prochee']));
                $row++;
            }

            // Итоговая строка
            $sheet->setCellValue('B' . $row, 'ИТОГО');
            $sheet->getStyle('B' . $row)->getFont()->setBold(true)->setName('Times New Roman');
            $sheet->setCellValue('C' . $row, $this->fillEmpty($totals['staff']));
            $sheet->setCellValue('D' . $row, $this->fillEmpty($totals['total']));
            $sheet->setCellValue('E' . $row, $this->fillEmpty($totals['nalitso_total']));
            $sheet->setCellValue('F' . $row, $this->fillEmpty($totals['nalitso_123']));
            $sheet->setCellValue('G' . $row, $this->fillEmpty($totals['nalitso_84']));
            $sheet->setCellValue('H' . $row, $this->fillEmpty($totals['nalitso_6']));
            $sheet->setCellValue('I' . $row, $this->fillEmpty($totals['nalitso_uc_it']));
            $sheet->setCellValue('J' . $row, $this->fillEmpty($totals['nalitso_dr']));
            $sheet->setCellValue('K' . $row, $this->fillEmpty($totals['naryad_v_akademii']));
            $sheet->setCellValue('L' . $row, $this->fillEmpty($totals['lazaret']));
            $sheet->setCellValue('M' . $row, $this->fillEmpty($totals['osvobozhdenie_po_bolezni']));
            $sheet->setCellValue('N' . $row, $this->fillEmpty($totals['otpusk']));
            $sheet->setCellValue('O' . $row, $this->fillEmpty($totals['garnizon']));
            $sheet->setCellValue('P' . $row, $this->fillEmpty($totals['gospital']));
            $sheet->setCellValue('Q' . $row, $this->fillEmpty($totals['komandirovka']));
            $sheet->setCellValue('R' . $row, $this->fillEmpty($totals['prochee']));

            $lastRow = $row;
            
            // Применяем шрифт Times New Roman ко всем ячейкам с данными
            $sheet->getStyle('A7:R' . $lastRow)->getFont()->setName('Times New Roman')->setSize(11);
            $sheet->getStyle('A7:R' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A7:R' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('B7:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
            // Применяем границы ТОЛЬКО к ячейкам с данными (от A7 до R lastRow)
            $sheet->getStyle('A7:R' . $lastRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            
            // Устанавливаем автоширину для всех колонок, но с небольшим отступом
            foreach (range('A', 'R') as $col) {
               $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // ==================== ЛИСТ 2: ОБОРОТНАЯ СТОРОНА ====================
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Оборотная сторона');
            
            // Применяем шрифт Times New Roman для второго листа
            $sheet2->getStyle('A1:K100')->getFont()->setName('Times New Roman')->setSize(11);
            
            // Заголовок
            $sheet2->setCellValue('A1', 'СПИСОК ЛИЧНОГО СОСТАВА');
            $sheet2->mergeCells('A1:E2');
            $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setName('Times New Roman');
            $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $sheet2->mergeCells('G1:K2');
            $sheet2->getStyle('G1')->getFont()->setBold(true)->setSize(14)->setName('Times New Roman');
            $sheet2->getStyle('G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Заголовки колонок
            $headers2 = ['№ п/п', 'Категория', 'Звание', 'ФИО', 'Причина отсутствия'];
            
            // --- РАСЧЕТ КОЛИЧЕСТВА СТРОК ДЛЯ КАЖДОЙ КОЛОНКИ ---
            $personnelList = $reverseData['personnel'];
            $totalCount = count($personnelList);
            
            // Оставляем 2 строки внизу первой колонки для "По списку:" и "На лицо:"
            // Всего строк для отображения людей = totalCount
            // Распределяем между двумя колонками так, чтобы первая была на 4 строки меньше
            // Но при этом все люди должны поместиться
            
            // Максимальное количество строк на странице (высота листа)
            $maxRows = 60;
            
            // Рассчитываем количество строк для второй колонки (она больше на 4 строки)
            // Общее количество строк для людей + 2 строки для информации
            $totalRowsNeeded = $totalCount + 2;
            
            // Вторая колонка получает большую часть
            $secondColumnRows = min(ceil($totalRowsNeeded / 2) + 2, $maxRows);
            // Первая колонка на 4 строки меньше
            $firstColumnRows = max($secondColumnRows - 4, 0);
            
            // Если людей слишком много, увеличиваем количество строк во второй колонке
            if ($totalCount > $firstColumnRows) {
                $remainingAfterFirst = $totalCount - $firstColumnRows;
                if ($remainingAfterFirst > $secondColumnRows) {
                    // Если не хватает места, добавляем строки
                    $secondColumnRows = min($remainingAfterFirst, $maxRows);
                }
            }
            
            // Позиции для колонок
            $col1StartRow = 3;
            $col2StartRow = 3;
            
            // Заголовки для первой колонки
            foreach ($headers2 as $i => $header) {
                $col = chr(65 + $i);
                $sheet2->setCellValue($col . $col1StartRow, $header);
                $sheet2->getStyle($col . $col1StartRow)->getFont()->setBold(true)->setName('Times New Roman')->setSize(11);
                $sheet2->getStyle($col . $col1StartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle($col . $col1StartRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet2->getColumnDimension($col)->setWidth(12);
            }
            
            // Заголовки для второй колонки
            foreach ($headers2 as $i => $header) {
                $col = chr(71 + $i); // G=71, H=72, I=73, J=74, K=75
                $sheet2->setCellValue($col . $col2StartRow, $header);
                $sheet2->getStyle($col . $col2StartRow)->getFont()->setBold(true)->setName('Times New Roman')->setSize(11);
                $sheet2->getStyle($col . $col2StartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle($col . $col2StartRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet2->getColumnDimension($col)->setWidth(12);
            }
            
            // Заполняем ПЕРВУЮ колонку
            $row1 = $col1StartRow + 1;
            $num1 = 1;
            $firstColumnDataCount = min($firstColumnRows, $totalCount);
            
            for ($i = 0; $i < $firstColumnDataCount; $i++) {
                $person = $personnelList[$i];
                $sheet2->setCellValue('A' . $row1, $num1++);
                $sheet2->setCellValue('B' . $row1, $person['category']);
                $sheet2->setCellValue('C' . $row1, $person['rank']);
                $sheet2->setCellValue('D' . $row1, $person['fio']);
                $sheet2->setCellValue('E' . $row1, $person['reason']);
                $sheet2->getStyle('A' . $row1 . ':E' . $row1)->getFont()->setName('Times New Roman')->setSize(11);
                $sheet2->getStyle('A' . $row1 . ':E' . $row1)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $row1++;
            }
            
            // Заполняем ВТОРУЮ колонку (продолжаем нумерацию)
            $row2 = $col2StartRow + 1;
            $secondColumnDataCount = min($secondColumnRows, $totalCount - $firstColumnDataCount);
            
            for ($i = $firstColumnDataCount; $i < $firstColumnDataCount + $secondColumnDataCount && $i < $totalCount; $i++) {
                $person = $personnelList[$i];
                $sheet2->setCellValue('G' . $row2, $num1++); // Продолжаем нумерацию
                $sheet2->setCellValue('H' . $row2, $person['category']);
                $sheet2->setCellValue('I' . $row2, $person['rank']);
                $sheet2->setCellValue('J' . $row2, $person['fio']);
                $sheet2->setCellValue('K' . $row2, $person['reason']);
                $sheet2->getStyle('G' . $row2 . ':K' . $row2)->getFont()->setName('Times New Roman')->setSize(11);
                $sheet2->getStyle('G' . $row2 . ':K' . $row2)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $row2++;
            }
            
            // Информация под первой колонкой
            $infoRow1 = $row1 + 1;
            $sheet2->setCellValue('A' . $infoRow1, 'По списку:');
            $sheet2->setCellValue('B' . $infoRow1, $reverseData['total']);
            $sheet2->getStyle('A' . $infoRow1)->getFont()->setBold(true)->setName('Times New Roman')->setSize(11);
            $sheet2->getStyle('B' . $infoRow1)->getFont()->setName('Times New Roman')->setSize(11);
            $sheet2->getStyle('A' . $infoRow1 . ':B' . $infoRow1)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            
            $infoRow2 = $infoRow1 + 1;
            $sheet2->setCellValue('A' . $infoRow2, 'На лицо:');
            $sheet2->setCellValue('B' . $infoRow2, $reverseData['present']);
            $sheet2->getStyle('A' . $infoRow2)->getFont()->setBold(true)->setName('Times New Roman')->setSize(11);
            $sheet2->getStyle('B' . $infoRow2)->getFont()->setName('Times New Roman')->setSize(11);
            $sheet2->getStyle('A' . $infoRow2 . ':B' . $infoRow2)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            
            // Применяем границы для оборотной стороны ТОЛЬКО к ячейкам с данными
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];
            
            // Границы для первой колонки (от данных после заголовка до последней строки с данными)
            if ($row1 > $col1StartRow + 1) {
                $sheet2->getStyle('A' . ($col1StartRow + 1) . ':E' . ($row1 - 1))->applyFromArray($borderStyle);
            }
            
            // Границы для второй колонки (от данных после заголовка до последней строки с данными)
            if ($row2 > $col2StartRow + 1) {
                $sheet2->getStyle('G' . ($col2StartRow + 1) . ':K' . ($row2 - 1))->applyFromArray($borderStyle);
            }
            
            // Границы для информации под первой колонкой
            if ($infoRow2 >= $infoRow1) {
                $sheet2->getStyle('A' . $infoRow1 . ':B' . $infoRow2)->applyFromArray($borderStyle);
            }
            
            // Центрирование номеров
            $sheet2->getStyle('A' . ($col1StartRow + 1) . ':A' . ($row1 - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle('G' . ($col2StartRow + 1) . ':G' . ($row2 - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Автоширина для всех колонок оборотной стороны с небольшим отступом
            foreach (range('A', 'K') as $col) {
                $sheet2->getColumnDimension($col)->setAutoSize(true);
                $sheet2->getColumnDimension($col)->setWidth($sheet2->getColumnDimension($col)->getWidth() + 2);
            }
            
            // ==================== СОХРАНЕНИЕ И ОТДАЧА ====================
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