<?php

namespace App\Exports\Sheets;

use App\Models\ClassRoom;
use App\Models\DailyCheckin;
use App\Models\Habit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassCheckinDailySheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithCustomStartCell,
    WithEvents,
    WithColumnFormatting
{
    private int $rowNumber = 0;
    private int $totalRows = 0;
    private int $activeHabitCount = 0;
    private int $totalComplete = 0;
    private int $totalIncomplete = 0;
    private int $totalMissed = 0;
    private int $totalPending = 0;
    private ?ClassRoom $classRoom = null;

    public function __construct(
        private readonly int $classId,
        private readonly CarbonImmutable $startDate,
        private readonly CarbonImmutable $endDate
    ) {
    }

    public function collection(): Collection
    {
        $this->classRoom = ClassRoom::query()
            ->with('teacher:id,full_name,name,username')
            ->findOrFail($this->classId);

        $this->activeHabitCount = Habit::query()
            ->where('is_active', true)
            ->count();

        $students = User::query()
            ->select([
                'id',
                'class_id',
                'full_name',
                'name',
                'username',
                'nisn',
                'is_active',
                'created_at',
            ])
            ->where('class_id', $this->classId)
            ->whereHas('role', fn ($query) => $query->where('name', 'siswa'))
            ->orderBy('full_name')
            ->orderBy('username')
            ->get();

        $studentIds = $students->pluck('id');

        $checkins = $studentIds->isEmpty()
            ? collect()
            : DailyCheckin::query()
                ->with('items.validations')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('checkin_date', [
                    $this->startDate->toDateString(),
                    $this->endDate->toDateString(),
                ])
                ->get();

        $checkinsByStudentAndDate = $checkins->keyBy(
            fn (DailyCheckin $checkin): string =>
                $checkin->student_id.'|'.
                CarbonImmutable::parse($checkin->checkin_date)->toDateString()
        );

        $rows = collect();
        $periodEnd = $this->endDate->startOfDay();

        foreach ($students as $student) {
            $currentDate = $this->resolveEffectiveStartDate($student);

            if ($currentDate === null) {
                continue;
            }

            while ($currentDate->lessThanOrEqualTo($periodEnd)) {
                $key = $student->id.'|'.$currentDate->toDateString();

                $rows->push(
                    $this->buildDailyRow(
                        $student,
                        $currentDate,
                        $checkinsByStudentAndDate->get($key)
                    )
                );

                $currentDate = $currentDate->addDay();
            }
        }

        $this->totalRows = $rows->count();

        return $rows;
    }

    public function startCell(): string
    {
        return 'A8';
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Hari',
            'NISN',
            'Nama Siswa',
            'Status Akun',
            'Status Check-in',
            'Total Kebiasaan',
            'Selesai',
            'Belum Selesai',
            'Validasi Orang Tua',
            'Validasi Guru',
            'Catatan',
            'Waktu Kirim',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row['date'],
            $row['day_name'],
            $this->safeText($row['nisn']),
            $this->safeText($row['student_name']),
            $row['account_status'],
            $row['checkin_status'],
            $row['total_habits'],
            $row['done_items'],
            $row['not_done_items'],
            $row['parent_validations'],
            $row['teacher_validations'],
            $this->safeText($row['notes']),
            $row['submitted_at'],
        ];
    }

    public function title(): string
    {
        return 'Detail Harian';
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
            'N' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $this->writeHeader($sheet);
                $this->styleTable($sheet);
                $this->writeSummary($sheet);
                $this->configurePrint($sheet);
            },
        ];
    }

    private function buildDailyRow(
        User $student,
        CarbonImmutable $date,
        ?DailyCheckin $checkin
    ): array {
        $studentName = $student->full_name
            ?? $student->name
            ?? $student->username
            ?? '-';

        if ($checkin === null) {
            $status = $this->missingStatus($date);

            if ($status === 'Terlewat') {
                $this->totalMissed++;
            } else {
                $this->totalPending++;
            }

            return [
                'date' => $date->format('d/m/Y'),
                'day_name' => $date->locale('id')->translatedFormat('l'),
                'nisn' => $student->nisn,
                'student_name' => $studentName,
                'account_status' => $student->is_active ? 'Aktif' : 'Nonaktif',
                'checkin_status' => $status,
                'total_habits' => $this->activeHabitCount,
                'done_items' => 0,
                'not_done_items' => $this->activeHabitCount,
                'parent_validations' => 0,
                'teacher_validations' => 0,
                'notes' => '-',
                'submitted_at' => '-',
            ];
        }

        $doneItems = $checkin->items
            ->where('is_done', true)
            ->count();

        $parentValidations = $checkin->items->sum(
            fn ($item): int => $item->validations
                ->where('validator_role', 'orang_tua')
                ->count()
        );

        $teacherValidations = $checkin->items->sum(
            fn ($item): int => $item->validations
                ->where('validator_role', 'guru')
                ->count()
        );

        $isComplete = $this->activeHabitCount > 0
            && $doneItems >= $this->activeHabitCount;

        if ($isComplete) {
            $this->totalComplete++;
        } else {
            $this->totalIncomplete++;
        }

        return [
            'date' => $date->format('d/m/Y'),
            'day_name' => $date->locale('id')->translatedFormat('l'),
            'nisn' => $student->nisn,
            'student_name' => $studentName,
            'account_status' => $student->is_active ? 'Aktif' : 'Nonaktif',
            'checkin_status' => $isComplete ? 'Lengkap' : 'Belum Lengkap',
            'total_habits' => $this->activeHabitCount,
            'done_items' => $doneItems,
            'not_done_items' => max($this->activeHabitCount - $doneItems, 0),
            'parent_validations' => $parentValidations,
            'teacher_validations' => $teacherValidations,
            'notes' => filled($checkin->notes) ? $checkin->notes : '-',
            'submitted_at' => $this->formatSubmittedAt($checkin->submitted_at),
        ];
    }

    private function writeHeader(Worksheet $sheet): void
    {
        foreach (['A1:N1', 'A2:N2', 'A3:N3'] as $range) {
            $sheet->mergeCells($range);
        }

        $sheet->setCellValue('A1', 'DETAIL HARIAN CHECK-IN KELAS');
        $sheet->setCellValue('A2', 'JURNAL 7 KEBIASAAN ANAK INDONESIA HEBAT');
        $sheet->setCellValue('A3', 'SMA NEGERI 1 MIRIT');

        $sheet->setCellValue('A5', 'Kelas');
        $sheet->setCellValue('B5', $this->classRoom?->name ?? '-');
        $sheet->mergeCells('B5:C5');

        $sheet->setCellValue('D5', 'Tingkat');
        $sheet->setCellValue('E5', $this->classRoom?->grade_level ?? '-');
        $sheet->mergeCells('E5:F5');

        $sheet->setCellValue('G5', 'Guru');
        $sheet->setCellValue('H5', $this->teacherName());
        $sheet->mergeCells('H5:N5');

        $sheet->setCellValue('A6', 'Periode');
        $sheet->setCellValue('B6', $this->periodLabel());
        $sheet->mergeCells('B6:E6');

        $sheet->setCellValue('F6', 'Tanggal Export');
        $sheet->setCellValue(
            'G6',
            now()->locale('id')->translatedFormat('d F Y H:i').' WIB'
        );
        $sheet->mergeCells('G6:J6');

        $sheet->setCellValue('K6', 'Kebiasaan Aktif');
        $sheet->setCellValue('L6', $this->activeHabitCount);
        $sheet->mergeCells('L6:N6');

        $sheet->getStyle('A1:N3')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11);

        foreach (['A5', 'D5', 'G5', 'A6', 'F6', 'K6'] as $cell) {
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        $sheet->getStyle('A5:N6')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(19);
        $sheet->getRowDimension(5)->setRowHeight(20);
        $sheet->getRowDimension(6)->setRowHeight(20);
    }

    private function styleTable(Worksheet $sheet): void
    {
        $headerRow = 8;
        $firstDataRow = 9;
        $lastDataRow = $this->totalRows > 0
            ? $headerRow + $this->totalRows
            : $headerRow;

        $sheet->setAutoFilter("A{$headerRow}:N{$lastDataRow}");

        $sheet->getStyle("A{$headerRow}:N{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF168AD3'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:N{$lastDataRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB('FFD6DEE8');

        $sheet->getStyle("A{$headerRow}:N{$lastDataRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        if ($lastDataRow >= $firstDataRow) {
            $sheet->getStyle("A{$firstDataRow}:D{$lastDataRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("F{$firstDataRow}:L{$lastDataRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("M{$firstDataRow}:M{$lastDataRow}")
                ->getAlignment()
                ->setWrapText(true);

            $sheet->getStyle("N{$firstDataRow}:N{$lastDataRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $previousStudentKey = null;
            $studentRowIndex = 0;

            for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(24);

                $studentKey = trim(
                    (string) $sheet->getCell("D{$row}")->getValue()
                ).'|'.trim(
                    (string) $sheet->getCell("E{$row}")->getValue()
                );

                if (
                    $previousStudentKey === null
                    || $studentKey !== $previousStudentKey
                ) {
                    if ($previousStudentKey !== null) {
                        $sheet->getStyle("A{$row}:N{$row}")
                            ->getBorders()
                            ->getTop()
                            ->setBorderStyle(Border::BORDER_MEDIUM)
                            ->getColor()
                            ->setARGB('FF168AD3');
                    }

                    $studentRowIndex = 0;
                } else {
                    $studentRowIndex++;
                }

                if ($studentRowIndex % 2 === 1) {
                    $sheet->getStyle("A{$row}:N{$row}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB('FFF4F8FC');
                }

                $this->styleStatusCell($sheet, $row);
                $previousStudentKey = $studentKey;
            }
        }

        $sheet->getRowDimension($headerRow)->setRowHeight(38);

        $widths = [
            'A' => 7,
            'B' => 14,
            'C' => 13,
            'D' => 16,
            'E' => 28,
            'F' => 14,
            'G' => 18,
            'H' => 16,
            'I' => 11,
            'J' => 16,
            'K' => 19,
            'L' => 16,
            'M' => 36,
            'N' => 19,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function styleStatusCell(Worksheet $sheet, int $row): void
    {
        $status = (string) $sheet->getCell("G{$row}")->getValue();

        $color = match ($status) {
            'Lengkap' => 'FFE2F0D9',
            'Belum Lengkap' => 'FFFFF2CC',
            'Terlewat' => 'FFFCE4D6',
            'Belum Check-in' => 'FFDDEBF7',
            default => 'FFFFFFFF',
        };

        $sheet->getStyle("G{$row}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($color);

        $sheet->getStyle("G{$row}")->getFont()->setBold(true);
    }

    private function writeSummary(Worksheet $sheet): void
    {
        $summaryRow = 8 + $this->totalRows + 2;

        $sheet->mergeCells("A{$summaryRow}:D{$summaryRow}");
        $sheet->setCellValue("A{$summaryRow}", "TOTAL {$this->totalRows} BARIS");
        $sheet->setCellValue("E{$summaryRow}", 'Lengkap');
        $sheet->setCellValue("F{$summaryRow}", $this->totalComplete);
        $sheet->setCellValue("G{$summaryRow}", 'Belum Lengkap');
        $sheet->setCellValue("H{$summaryRow}", $this->totalIncomplete);
        $sheet->setCellValue("I{$summaryRow}", 'Terlewat');
        $sheet->setCellValue("J{$summaryRow}", $this->totalMissed);
        $sheet->setCellValue("K{$summaryRow}", 'Belum Check-in');
        $sheet->setCellValue("L{$summaryRow}", $this->totalPending);
        $sheet->mergeCells("L{$summaryRow}:N{$summaryRow}");

        $sheet->getStyle("A{$summaryRow}:N{$summaryRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEAF4FB'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD6DEE8'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension($summaryRow)->setRowHeight(22);
    }

    private function configurePrint(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd(8, 8);

        $sheet->getPageMargins()
            ->setTop(0.4)
            ->setBottom(0.4)
            ->setLeft(0.2)
            ->setRight(0.2);

        $sheet->getHeaderFooter()->setOddFooter(
            '&LJurnal 7KAIH'.
            '&CHalaman &P dari &N'.
            '&RSMA Negeri 1 Mirit'
        );
    }

    private function resolveEffectiveStartDate(User $student): ?CarbonImmutable
    {
        $periodStart = $this->startDate->startOfDay();
        $periodEnd = $this->endDate->startOfDay();

        if (!$student->created_at) {
            return $periodStart;
        }

        $accountStart = CarbonImmutable::parse(
            $student->created_at
        )->startOfDay();

        $effectiveStart = $accountStart->greaterThan($periodStart)
            ? $accountStart
            : $periodStart;

        return $effectiveStart->greaterThan($periodEnd)
            ? null
            : $effectiveStart;
    }

    private function missingStatus(CarbonImmutable $date): string
    {
        return $date->equalTo(now()->toImmutable()->startOfDay())
            ? 'Belum Check-in'
            : 'Terlewat';
    }

    private function formatSubmittedAt(mixed $submittedAt): string
    {
        if (!$submittedAt) {
            return '-';
        }

        return CarbonImmutable::parse($submittedAt)
            ->timezone(config('app.timezone'))
            ->format('d/m/Y H:i');
    }

    private function teacherName(): string
    {
        return $this->classRoom?->teacher?->full_name
            ?? $this->classRoom?->teacher?->name
            ?? $this->classRoom?->teacher?->username
            ?? 'Belum ditentukan';
    }

    private function periodLabel(): string
    {
        $start = $this->startDate
            ->locale('id')
            ->translatedFormat('d F Y');

        $end = $this->endDate
            ->locale('id')
            ->translatedFormat('d F Y');

        return "{$start} - {$end}";
    }

    private function safeText(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        if (
            $text !== ''
            && in_array($text[0], ['=', '+', '-', '@'], true)
        ) {
            return "'".$text;
        }

        return $text;
    }
}
