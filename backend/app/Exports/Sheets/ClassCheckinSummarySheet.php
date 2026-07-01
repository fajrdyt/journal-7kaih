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

class ClassCheckinSummarySheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithCustomStartCell,
    WithEvents,
    WithColumnFormatting
{
    private int $rowNumber = 0;

    private int $totalStudents = 0;

    private int $totalOpportunityDays = 0;

    private int $totalCheckinDays = 0;

    private int $totalMissedDays = 0;

    private int $totalCompleteDays = 0;

    private int $totalDoneItems = 0;

    private int $totalParentValidations = 0;

    private int $totalTeacherValidations = 0;

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
            ->with([
                'teacher:id,full_name,name,username',
            ])
            ->findOrFail($this->classId);

        $activeHabitCount = Habit::query()
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
            ->whereHas('role', function ($query) {
                $query->where('name', 'siswa');
            })
            ->orderBy('full_name')
            ->orderBy('username')
            ->get();

        $studentIds = $students
            ->pluck('id')
            ->values();

        $checkinsByStudent = $studentIds->isEmpty()
            ? collect()
            : DailyCheckin::query()
                ->with([
                    'items.validations',
                ])
                ->whereIn('student_id', $studentIds)
                ->whereDate(
                    'checkin_date',
                    '>=',
                    $this->startDate->toDateString()
                )
                ->whereDate(
                    'checkin_date',
                    '<=',
                    $this->endDate->toDateString()
                )
                ->orderBy('checkin_date')
                ->get()
                ->groupBy('student_id');

        $rows = $students->map(function (
            User $student
        ) use (
            $checkinsByStudent,
            $activeHabitCount
        ): array {
            $effectiveStartDate = $this->resolveEffectiveStartDate(
                $student
            );

            $periodDays = $this->calculatePeriodDays(
                $effectiveStartDate
            );

            $studentCheckins = collect(
                $checkinsByStudent->get(
                    $student->id,
                    collect()
                )
            )
                ->filter(function (
                    DailyCheckin $checkin
                ) use ($effectiveStartDate): bool {
                    if ($effectiveStartDate === null) {
                        return false;
                    }

                    $checkinDate = CarbonImmutable::parse(
                        $checkin->checkin_date
                    )->startOfDay();

                    return $checkinDate
                        ->greaterThanOrEqualTo(
                            $effectiveStartDate
                        );
                })
                ->values();

            $checkinDays = $studentCheckins
                ->pluck('checkin_date')
                ->map(
                    fn ($date) => CarbonImmutable::parse(
                        $date
                    )->toDateString()
                )
                ->unique()
                ->count();

            $missedDays = max(
                $periodDays - $checkinDays,
                0
            );

            $completeDays = $studentCheckins
                ->filter(function (
                    DailyCheckin $checkin
                ) use ($activeHabitCount): bool {
                    if ($activeHabitCount <= 0) {
                        return false;
                    }

                    $doneItems = $checkin
                        ->items
                        ->where('is_done', true)
                        ->count();

                    return $doneItems
                        >= $activeHabitCount;
                })
                ->count();

            $doneItems = $studentCheckins
                ->sum(
                    fn (DailyCheckin $checkin): int =>
                        $checkin
                            ->items
                            ->where('is_done', true)
                            ->count()
                );

            $parentValidations = $studentCheckins
                ->sum(
                    fn (DailyCheckin $checkin): int =>
                        $checkin->items->sum(
                            fn ($item): int =>
                                $item
                                    ->validations
                                    ->where(
                                        'validator_role',
                                        'orang_tua'
                                    )
                                    ->count()
                        )
                );

            $teacherValidations = $studentCheckins
                ->sum(
                    fn (DailyCheckin $checkin): int =>
                        $checkin->items->sum(
                            fn ($item): int =>
                                $item
                                    ->validations
                                    ->where(
                                        'validator_role',
                                        'guru'
                                    )
                                    ->count()
                        )
                );

            $checkinPercentage = $periodDays > 0
                ? round(
                    ($checkinDays / $periodDays) * 100,
                    2
                )
                : 0;

            $this->totalOpportunityDays += $periodDays;
            $this->totalCheckinDays += $checkinDays;
            $this->totalMissedDays += $missedDays;
            $this->totalCompleteDays += $completeDays;
            $this->totalDoneItems += $doneItems;
            $this->totalParentValidations +=
                $parentValidations;
            $this->totalTeacherValidations +=
                $teacherValidations;

            return [
                'nisn' => $student->nisn,
                'student_name' =>
                    $student->full_name
                    ?? $student->name
                    ?? $student->username
                    ?? '-',
                'account_status' => $student->is_active
                    ? 'Aktif'
                    : 'Nonaktif',
                'period_days' => $periodDays,
                'checkin_days' => $checkinDays,
                'missed_days' => $missedDays,
                'complete_days' => $completeDays,
                'done_items' => $doneItems,
                'parent_validations' =>
                    $parentValidations,
                'teacher_validations' =>
                    $teacherValidations,
                'checkin_percentage' =>
                    $checkinPercentage,
            ];
        });

        $this->totalStudents = $rows->count();

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
            'NISN',
            'Nama Siswa',
            'Status Akun',
            'Hari Periode',
            'Hari Check-in',
            'Hari Terlewat',
            'Hari Lengkap',
            'Item Selesai',
            'Validasi Orang Tua',
            'Validasi Guru',
            'Persentase Check-in',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $this->safeText($row['nisn']),
            $this->safeText($row['student_name']),
            $row['account_status'],
            $row['period_days'],
            $row['checkin_days'],
            $row['missed_days'],
            $row['complete_days'],
            $row['done_items'],
            $row['parent_validations'],
            $row['teacher_validations'],
            $row['checkin_percentage'],
        ];
    }

    public function title(): string
    {
        return 'Ringkasan Siswa';
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
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => '0.00"%"',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (
                AfterSheet $event
            ): void {
                $sheet = $event->sheet->getDelegate();

                $this->writeReportHeader($sheet);
                $this->styleTable($sheet);
                $this->writeSummary($sheet);
                $this->configurePrintLayout($sheet);
            },
        ];
    }

    private function writeReportHeader(
        Worksheet $sheet
    ): void {
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->mergeCells('A3:L3');

        $sheet->setCellValue(
            'A1',
            'REKAP CHECK-IN KELAS'
        );

        $sheet->setCellValue(
            'A2',
            'JURNAL 7 KEBIASAAN ANAK INDONESIA HEBAT'
        );

        $sheet->setCellValue(
            'A3',
            'SMA NEGERI 1 MIRIT'
        );

        $sheet->setCellValue('A5', 'Kelas');
        $sheet->setCellValue(
            'B5',
            $this->classRoom?->name ?? '-'
        );
        $sheet->mergeCells('B5:C5');

        $sheet->setCellValue('D5', 'Tingkat');
        $sheet->setCellValue(
            'E5',
            $this->classRoom?->grade_level ?? '-'
        );
        $sheet->mergeCells('E5:F5');

        $sheet->setCellValue('G5', 'Guru');
        $sheet->setCellValue(
            'H5',
            $this->teacherName()
        );
        $sheet->mergeCells('H5:L5');

        $sheet->setCellValue('A6', 'Periode');
        $sheet->setCellValue(
            'B6',
            $this->periodLabel()
        );
        $sheet->mergeCells('B6:D6');

        $sheet->setCellValue(
            'E6',
            'Tanggal Export'
        );

        $sheet->setCellValue(
            'F6',
            now()
                ->locale('id')
                ->translatedFormat(
                    'd F Y H:i'
                ).' WIB'
        );
        $sheet->mergeCells('F6:H6');

        $sheet->setCellValue(
            'I6',
            'Kebiasaan Aktif'
        );

        $sheet->setCellValue(
            'J6',
            Habit::query()
                ->where('is_active', true)
                ->count()
        );
        $sheet->mergeCells('J6:L6');

        $sheet
            ->getStyle('A1:L3')
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

        $sheet
            ->getStyle('A5:L6')
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        foreach (
            ['A5', 'D5', 'G5', 'A6', 'E6', 'I6']
            as $cell
        ) {
            $sheet
                ->getStyle($cell)
                ->getFont()
                ->setBold(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(19);
        $sheet->getRowDimension(5)->setRowHeight(20);
        $sheet->getRowDimension(6)->setRowHeight(20);
    }

    private function styleTable(
        Worksheet $sheet
    ): void {
        $headerRow = 8;
        $firstDataRow = 9;

        $lastDataRow = $this->totalStudents > 0
            ? $headerRow + $this->totalStudents
            : $headerRow;

        $sheet->setAutoFilter(
            "A{$headerRow}:L{$lastDataRow}"
        );

        $sheet
            ->getStyle(
                "A{$headerRow}:L{$headerRow}"
            )
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');

        $sheet
            ->getStyle(
                "A{$headerRow}:L{$headerRow}"
            )
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF168AD3');

        $sheet
            ->getStyle(
                "A{$headerRow}:L{$headerRow}"
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
                "A{$headerRow}:L{$lastDataRow}"
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
                "A{$headerRow}:L{$lastDataRow}"
            )
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        if ($lastDataRow >= $firstDataRow) {
            $sheet
                ->getStyle(
                    "A{$firstDataRow}:B{$lastDataRow}"
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            $sheet
                ->getStyle(
                    "D{$firstDataRow}:L{$lastDataRow}"
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
                    ->setRowHeight(20);

                if (
                    ($row - $firstDataRow) % 2 === 1
                ) {
                    $sheet
                        ->getStyle(
                            "A{$row}:L{$row}"
                        )
                        ->getFill()
                        ->setFillType(
                            Fill::FILL_SOLID
                        )
                        ->getStartColor()
                        ->setARGB('FFF4F8FC');
                }
            }
        }

        $sheet
            ->getRowDimension($headerRow)
            ->setRowHeight(36);

        $sheet->getColumnDimension('A')->setWidth(7);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(19);
        $sheet->getColumnDimension('K')->setWidth(16);
        $sheet->getColumnDimension('L')->setWidth(19);
    }

    private function writeSummary(
        Worksheet $sheet
    ): void {
        $lastDataRow = 8 + $this->totalStudents;
        $summaryRow = $lastDataRow + 2;

        $averageCheckinPercentage =
            $this->totalOpportunityDays > 0
                ? round(
                    (
                        $this->totalCheckinDays
                        / $this->totalOpportunityDays
                    ) * 100,
                    2
                )
                : 0;

        $sheet->mergeCells(
            "A{$summaryRow}:D{$summaryRow}"
        );

        $sheet->setCellValue(
            "A{$summaryRow}",
            "TOTAL {$this->totalStudents} SISWA"
        );

        $sheet->setCellValue(
            "E{$summaryRow}",
            $this->totalOpportunityDays
        );

        $sheet->setCellValue(
            "F{$summaryRow}",
            $this->totalCheckinDays
        );

        $sheet->setCellValue(
            "G{$summaryRow}",
            $this->totalMissedDays
        );

        $sheet->setCellValue(
            "H{$summaryRow}",
            $this->totalCompleteDays
        );

        $sheet->setCellValue(
            "I{$summaryRow}",
            $this->totalDoneItems
        );

        $sheet->setCellValue(
            "J{$summaryRow}",
            $this->totalParentValidations
        );

        $sheet->setCellValue(
            "K{$summaryRow}",
            $this->totalTeacherValidations
        );

        $sheet->setCellValue(
            "L{$summaryRow}",
            $averageCheckinPercentage
        );

        $sheet
            ->getStyle(
                "A{$summaryRow}:L{$summaryRow}"
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "A{$summaryRow}:L{$summaryRow}"
            )
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFEAF4FB');

        $sheet
            ->getStyle(
                "A{$summaryRow}:L{$summaryRow}"
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
                "A{$summaryRow}:L{$summaryRow}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getStyle("L{$summaryRow}")
            ->getNumberFormat()
            ->setFormatCode('0.00"%"');

        $sheet
            ->getRowDimension($summaryRow)
            ->setRowHeight(22);
    }

    private function configurePrintLayout(
        Worksheet $sheet
    ): void {
        $sheet
            ->getPageSetup()
            ->setOrientation(
                PageSetup::ORIENTATION_LANDSCAPE
            );

        $sheet
            ->getPageSetup()
            ->setPaperSize(
                PageSetup::PAPERSIZE_A4
            );

        $sheet
            ->getPageSetup()
            ->setFitToWidth(1);

        $sheet
            ->getPageSetup()
            ->setFitToHeight(0);

        $sheet
            ->getPageSetup()
            ->setRowsToRepeatAtTopByStartAndEnd(
                8,
                8
            );

        $sheet
            ->getPageMargins()
            ->setTop(0.5);

        $sheet
            ->getPageMargins()
            ->setBottom(0.5);

        $sheet
            ->getPageMargins()
            ->setLeft(0.3);

        $sheet
            ->getPageMargins()
            ->setRight(0.3);

        $sheet
            ->getHeaderFooter()
            ->setOddFooter(
                '&LJurnal 7KAIH'.
                '&CHalaman &P dari &N'.
                '&RSMA Negeri 1 Mirit'
            );
    }

    private function resolveEffectiveStartDate(
        User $student
    ): ?CarbonImmutable {
        $periodStart = $this->startDate
            ->startOfDay();

        $periodEnd = $this->endDate
            ->startOfDay();

        if (!$student->created_at) {
            return $periodStart;
        }

        $accountStart = CarbonImmutable::parse(
            $student->created_at
        )->startOfDay();

        $effectiveStart = $accountStart
            ->greaterThan($periodStart)
                ? $accountStart
                : $periodStart;

        if ($effectiveStart->greaterThan($periodEnd)) {
            return null;
        }

        return $effectiveStart;
    }

    private function calculatePeriodDays(
        ?CarbonImmutable $effectiveStartDate
    ): int {
        if ($effectiveStartDate === null) {
            return 0;
        }

        return $effectiveStartDate
            ->diffInDays(
                $this->endDate->startOfDay()
            ) + 1;
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