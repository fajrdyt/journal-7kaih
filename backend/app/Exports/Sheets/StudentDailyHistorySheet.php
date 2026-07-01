<?php

namespace App\Exports\Sheets;

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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentDailyHistorySheet implements
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

    private int $totalDoneItems = 0;

    private int $totalParentValidations = 0;

    private int $totalTeacherValidations = 0;

    private ?User $student = null;

    private ?CarbonImmutable $effectiveStartDate = null;

    public function __construct(
        private readonly int $studentId,
        private readonly CarbonImmutable $startDate,
        private readonly CarbonImmutable $endDate
    ) {
    }

    public function collection(): Collection
    {
        $this->student = User::query()
            ->with([
                'classRoom:id,name,grade_level,teacher_id',
                'classRoom.teacher:id,full_name,name,username',
            ])
            ->whereHas('role', function ($query) {
                $query->where('name', 'siswa');
            })
            ->findOrFail($this->studentId);

        $this->activeHabitCount = Habit::query()
            ->where('is_active', true)
            ->count();

        $this->effectiveStartDate =
            $this->resolveEffectiveStartDate();

        if ($this->effectiveStartDate === null) {
            return collect();
        }

        $checkins = DailyCheckin::query()
            ->with([
                'items.validations',
            ])
            ->where(
                'student_id',
                $this->studentId
            )
            ->whereBetween(
                'checkin_date',
                [
                    $this->effectiveStartDate
                        ->toDateString(),
                    $this->endDate
                        ->toDateString(),
                ]
            )
            ->orderBy('checkin_date')
            ->get();

        $checkinsByDate = $checkins->keyBy(
            function (
                DailyCheckin $checkin
            ): string {
                return CarbonImmutable::parse(
                    $checkin->checkin_date
                )->toDateString();
            }
        );

        $rows = collect();

        $currentDate =
            $this->effectiveStartDate;

        $periodEnd =
            $this->endDate->startOfDay();

        while (
            $currentDate->lessThanOrEqualTo(
                $periodEnd
            )
        ) {
            $checkin = $checkinsByDate->get(
                $currentDate->toDateString()
            );

            $rows->push(
                $this->buildDailyRow(
                    date: $currentDate,
                    checkin: $checkin
                )
            );

            $currentDate =
                $currentDate->addDay();
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
            $row['checkin_status'],
            $row['total_habits'],
            $row['done_items'],
            $row['not_done_items'],
            $row['parent_validations'],
            $row['teacher_validations'],
            $this->safeText(
                $row['notes']
            ),
            $row['submitted_at'],
        ];
    }

    public function title(): string
    {
        return 'Riwayat Harian';
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (
                AfterSheet $event
            ): void {
                $sheet = $event->sheet
                    ->getDelegate();

                $this->writeHeader($sheet);
                $this->styleTable($sheet);
                $this->writeSummary($sheet);
                $this->writeNote($sheet);
                $this->configurePrint($sheet);
            },
        ];
    }

    private function buildDailyRow(
        CarbonImmutable $date,
        ?DailyCheckin $checkin
    ): array {
        if ($checkin === null) {
            $status =
                $this->missingStatus($date);

            if ($status === 'Terlewat') {
                $this->totalMissed++;
            } else {
                $this->totalPending++;
            }

            return [
                'date' =>
                    $date->format('d/m/Y'),
                'day_name' =>
                    $date
                        ->locale('id')
                        ->translatedFormat('l'),
                'checkin_status' =>
                    $status,
                'total_habits' =>
                    $this->activeHabitCount,
                'done_items' =>
                    0,
                'not_done_items' =>
                    $this->activeHabitCount,
                'parent_validations' =>
                    0,
                'teacher_validations' =>
                    0,
                'notes' =>
                    '-',
                'submitted_at' =>
                    '-',
            ];
        }

        $doneItems = $checkin
            ->items
            ->where('is_done', true)
            ->count();

        $notDoneItems = max(
            $this->activeHabitCount
                - $doneItems,
            0
        );

        $parentValidations =
            $checkin->items->sum(
                fn ($item): int =>
                    $item
                        ->validations
                        ->where(
                            'validator_role',
                            'orang_tua'
                        )
                        ->count()
            );

        $teacherValidations =
            $checkin->items->sum(
                fn ($item): int =>
                    $item
                        ->validations
                        ->where(
                            'validator_role',
                            'guru'
                        )
                        ->count()
            );

        $isComplete =
            $this->activeHabitCount > 0
            && $doneItems
                >= $this->activeHabitCount;

        if ($isComplete) {
            $this->totalComplete++;
        } else {
            $this->totalIncomplete++;
        }

        $this->totalDoneItems +=
            $doneItems;

        $this->totalParentValidations +=
            $parentValidations;

        $this->totalTeacherValidations +=
            $teacherValidations;

        return [
            'date' =>
                $date->format('d/m/Y'),
            'day_name' =>
                $date
                    ->locale('id')
                    ->translatedFormat('l'),
            'checkin_status' =>
                $isComplete
                    ? 'Lengkap'
                    : 'Belum Lengkap',
            'total_habits' =>
                $this->activeHabitCount,
            'done_items' =>
                $doneItems,
            'not_done_items' =>
                $notDoneItems,
            'parent_validations' =>
                $parentValidations,
            'teacher_validations' =>
                $teacherValidations,
            'notes' =>
                filled($checkin->notes)
                    ? $checkin->notes
                    : '-',
            'submitted_at' =>
                $this->formatSubmittedAt(
                    $checkin->submitted_at
                ),
        ];
    }

    private function writeHeader(
        Worksheet $sheet
    ): void {
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');

        $sheet->setCellValue(
            'A1',
            'RIWAYAT HARIAN SISWA'
        );

        $sheet->setCellValue(
            'A2',
            'JURNAL 7 KEBIASAAN ANAK INDONESIA HEBAT'
        );

        $sheet->setCellValue(
            'A3',
            'SMA NEGERI 1 MIRIT'
        );

        $sheet->setCellValue(
            'A5',
            'Nama Siswa'
        );

        $sheet->setCellValue(
            'B5',
            $this->safeText(
                $this->studentName()
            )
        );

        $sheet->mergeCells('B5:D5');

        $sheet->setCellValue(
            'E5',
            'NISN'
        );

        $sheet->setCellValueExplicit(
            'F5',
            (string) (
                $this->student?->nisn
                ?? '-'
            ),
            DataType::TYPE_STRING
        );

        $sheet->mergeCells('F5:H5');

        $sheet->setCellValue(
            'I5',
            'Status Akun'
        );

        $sheet->setCellValue(
            'J5',
            $this->student?->is_active
                ? 'Aktif'
                : 'Nonaktif'
        );

        $sheet->mergeCells('J5:K5');

        $sheet->setCellValue(
            'A6',
            'Kelas'
        );

        $sheet->setCellValue(
            'B6',
            $this->safeText(
                $this->student
                    ?->classRoom
                    ?->name
                ?? '-'
            )
        );

        $sheet->mergeCells('B6:C6');

        $sheet->setCellValue(
            'D6',
            'Periode'
        );

        $sheet->setCellValue(
            'E6',
            $this->periodLabel()
        );

        $sheet->mergeCells('E6:H6');

        $sheet->setCellValue(
            'I6',
            'Tanggal Export'
        );

        $sheet->setCellValue(
            'J6',
            now()
                ->locale('id')
                ->translatedFormat(
                    'd F Y H:i'
                ).' WIB'
        );

        $sheet->mergeCells('J6:K6');

        $sheet
            ->getStyle('A1:K3')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getStyle('A1')
            ->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet
            ->getStyle('A2')
            ->getFont()
            ->setBold(true)
            ->setSize(12);

        $sheet
            ->getStyle('A3')
            ->getFont()
            ->setBold(true)
            ->setSize(11);

        foreach (
            ['A5', 'E5', 'I5', 'A6', 'D6', 'I6']
            as $cell
        ) {
            $sheet
                ->getStyle($cell)
                ->getFont()
                ->setBold(true);
        }

        $sheet
            ->getStyle('A5:K6')
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            )
            ->getColor()
            ->setARGB('FFD6DEE8');

        $sheet
            ->getStyle('A5:K6')
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet->getRowDimension(1)
            ->setRowHeight(25);

        $sheet->getRowDimension(2)
            ->setRowHeight(20);

        $sheet->getRowDimension(3)
            ->setRowHeight(19);

        $sheet->getRowDimension(5)
            ->setRowHeight(21);

        $sheet->getRowDimension(6)
            ->setRowHeight(21);
    }

    private function styleTable(
        Worksheet $sheet
    ): void {
        $headerRow = 8;
        $firstDataRow = 9;

        $lastDataRow =
            $this->totalRows > 0
                ? $headerRow
                    + $this->totalRows
                : $headerRow;

        $sheet->setAutoFilter(
            "A{$headerRow}:K{$lastDataRow}"
        );

        $sheet
            ->getStyle(
                "A{$headerRow}:K{$headerRow}"
            )
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');

        $sheet
            ->getStyle(
                "A{$headerRow}:K{$headerRow}"
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FF168AD3');

        $sheet
            ->getStyle(
                "A{$headerRow}:K{$headerRow}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            )
            ->setWrapText(true);

        $sheet
            ->getStyle(
                "A{$headerRow}:K{$lastDataRow}"
            )
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            )
            ->getColor()
            ->setARGB('FFD6DEE8');

        $sheet
            ->getStyle(
                "A{$headerRow}:K{$lastDataRow}"
            )
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        if ($lastDataRow >= $firstDataRow) {
            $sheet
                ->getStyle(
                    "A{$firstDataRow}:I{$lastDataRow}"
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            $sheet
                ->getStyle(
                    "J{$firstDataRow}:J{$lastDataRow}"
                )
                ->getAlignment()
                ->setWrapText(true);

            $sheet
                ->getStyle(
                    "K{$firstDataRow}:K{$lastDataRow}"
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            for (
                $row = $firstDataRow;
                $row <= $lastDataRow;
                $row++
            ) {
                $sheet
                    ->getRowDimension($row)
                    ->setRowHeight(24);

                if (
                    ($row - $firstDataRow)
                    % 2 === 1
                ) {
                    $sheet
                        ->getStyle(
                            "A{$row}:K{$row}"
                        )
                        ->getFill()
                        ->setFillType(
                            Fill::FILL_SOLID
                        )
                        ->getStartColor()
                        ->setARGB('FFF4F8FC');
                }

                $this->styleStatusCell(
                    sheet: $sheet,
                    row: $row
                );
            }
        }

        $sheet
            ->getRowDimension($headerRow)
            ->setRowHeight(38);

        $sheet->getColumnDimension('A')
            ->setWidth(7);

        $sheet->getColumnDimension('B')
            ->setWidth(14);

        $sheet->getColumnDimension('C')
            ->setWidth(13);

        $sheet->getColumnDimension('D')
            ->setWidth(18);

        $sheet->getColumnDimension('E')
            ->setWidth(16);

        $sheet->getColumnDimension('F')
            ->setWidth(11);

        $sheet->getColumnDimension('G')
            ->setWidth(16);

        $sheet->getColumnDimension('H')
            ->setWidth(19);

        $sheet->getColumnDimension('I')
            ->setWidth(16);

        $sheet->getColumnDimension('J')
            ->setWidth(38);

        $sheet->getColumnDimension('K')
            ->setWidth(19);
    }

    private function styleStatusCell(
        Worksheet $sheet,
        int $row
    ): void {
        $status = (string) $sheet
            ->getCell("D{$row}")
            ->getValue();

        $color = match ($status) {
            'Lengkap' =>
                'FFE2F0D9',
            'Belum Lengkap' =>
                'FFFFF2CC',
            'Terlewat' =>
                'FFFCE4D6',
            'Belum Check-in' =>
                'FFDDEBF7',
            default =>
                'FFFFFFFF',
        };

        $sheet
            ->getStyle("D{$row}")
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB($color);

        $sheet
            ->getStyle("D{$row}")
            ->getFont()
            ->setBold(true);
    }

    private function writeSummary(
        Worksheet $sheet
    ): void {
        $summaryRow =
            8 + $this->totalRows + 2;

        $sheet->mergeCells(
            "A{$summaryRow}:C{$summaryRow}"
        );

        $sheet->setCellValue(
            "A{$summaryRow}",
            "TOTAL {$this->totalRows} HARI"
        );

        $sheet->setCellValue(
            "D{$summaryRow}",
            'Lengkap'
        );

        $sheet->setCellValue(
            "E{$summaryRow}",
            $this->totalComplete
        );

        $sheet->setCellValue(
            "F{$summaryRow}",
            'Belum Lengkap'
        );

        $sheet->setCellValue(
            "G{$summaryRow}",
            $this->totalIncomplete
        );

        $sheet->setCellValue(
            "H{$summaryRow}",
            'Terlewat'
        );

        $sheet->setCellValue(
            "I{$summaryRow}",
            $this->totalMissed
        );

        $sheet->setCellValue(
            "J{$summaryRow}",
            'Belum Check-in'
        );

        $sheet->setCellValue(
            "K{$summaryRow}",
            $this->totalPending
        );

        $sheet
            ->getStyle(
                "A{$summaryRow}:K{$summaryRow}"
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "A{$summaryRow}:K{$summaryRow}"
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFEAF4FB');

        $sheet
            ->getStyle(
                "A{$summaryRow}:K{$summaryRow}"
            )
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            )
            ->getColor()
            ->setARGB('FFD6DEE8');

        $sheet
            ->getStyle(
                "A{$summaryRow}:K{$summaryRow}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getRowDimension($summaryRow)
            ->setRowHeight(23);

        $metricsRow =
            $summaryRow + 1;

        $sheet->mergeCells(
            "A{$metricsRow}:C{$metricsRow}"
        );

        $sheet->setCellValue(
            "A{$metricsRow}",
            'TOTAL AKTIVITAS'
        );

        $sheet->setCellValue(
            "D{$metricsRow}",
            'Item Selesai'
        );

        $sheet->setCellValue(
            "E{$metricsRow}",
            $this->totalDoneItems
        );

        $sheet->setCellValue(
            "F{$metricsRow}",
            'Validasi Orang Tua'
        );

        $sheet->setCellValue(
            "G{$metricsRow}",
            $this->totalParentValidations
        );

        $sheet->setCellValue(
            "H{$metricsRow}",
            'Validasi Guru'
        );

        $sheet->setCellValue(
            "I{$metricsRow}",
            $this->totalTeacherValidations
        );

        $sheet->mergeCells(
            "J{$metricsRow}:K{$metricsRow}"
        );

        $sheet
            ->getStyle(
                "A{$metricsRow}:K{$metricsRow}"
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "A{$metricsRow}:K{$metricsRow}"
            )
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            )
            ->getColor()
            ->setARGB('FFD6DEE8');

        $sheet
            ->getStyle(
                "A{$metricsRow}:K{$metricsRow}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getRowDimension($metricsRow)
            ->setRowHeight(23);
    }

    private function writeNote(
        Worksheet $sheet
    ): void {
        $noteRow =
            8 + $this->totalRows + 5;

        $sheet->mergeCells(
            "A{$noteRow}:K{$noteRow}"
        );

        $sheet->setCellValue(
            "A{$noteRow}",
            'Terlewat berarti tidak ada check-in pada tanggal yang sudah berlalu. Tanggal hari ini yang belum diisi ditandai Belum Check-in.'
        );

        $sheet
            ->getStyle(
                "A{$noteRow}:K{$noteRow}"
            )
            ->getFont()
            ->setItalic(true)
            ->setSize(9);

        $sheet
            ->getStyle(
                "A{$noteRow}:K{$noteRow}"
            )
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getRowDimension($noteRow)
            ->setRowHeight(30);
    }

    private function configurePrint(
        Worksheet $sheet
    ): void {
        $sheet
            ->getPageSetup()
            ->setOrientation(
                PageSetup::ORIENTATION_LANDSCAPE
            )
            ->setPaperSize(
                PageSetup::PAPERSIZE_A4
            )
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd(
                8,
                8
            );

        $sheet
            ->getPageMargins()
            ->setTop(0.5)
            ->setBottom(0.5)
            ->setLeft(0.3)
            ->setRight(0.3);

        $sheet
            ->getHeaderFooter()
            ->setOddFooter(
                '&LJurnal 7KAIH'
                .'&CHalaman &P dari &N'
                .'&RSMA Negeri 1 Mirit'
            );
    }

    private function resolveEffectiveStartDate():
        ?CarbonImmutable
    {
        $periodStart =
            $this->startDate->startOfDay();

        $periodEnd =
            $this->endDate->startOfDay();

        if (!$this->student?->created_at) {
            return $periodStart;
        }

        $accountStart =
            CarbonImmutable::parse(
                $this->student->created_at
            )->startOfDay();

        $effectiveStart =
            $accountStart->greaterThan(
                $periodStart
            )
                ? $accountStart
                : $periodStart;

        return $effectiveStart->greaterThan(
            $periodEnd
        )
            ? null
            : $effectiveStart;
    }

    private function missingStatus(
        CarbonImmutable $date
    ): string {
        return $date->equalTo(
            now()
                ->toImmutable()
                ->startOfDay()
        )
            ? 'Belum Check-in'
            : 'Terlewat';
    }

    private function formatSubmittedAt(
        mixed $submittedAt
    ): string {
        if (!$submittedAt) {
            return '-';
        }

        return CarbonImmutable::parse(
            $submittedAt
        )
            ->timezone(
                config('app.timezone')
            )
            ->format('d/m/Y H:i');
    }

    private function studentName(): string
    {
        return $this->student?->full_name
            ?? $this->student?->name
            ?? $this->student?->username
            ?? '-';
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

    private function safeText(
        mixed $value
    ): string {
        $text = trim(
            (string) ($value ?? '')
        );

        if (
            $text !== ''
            && in_array(
                $text[0],
                ['=', '+', '-', '@'],
                true
            )
        ) {
            return "'".$text;
        }

        return $text;
    }
}
