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

class StudentHabitStatisticsSheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithCustomStartCell,
    WithEvents,
    WithColumnFormatting
{
    private int $rowNumber = 0;

    private int $totalHabits = 0;

    private int $periodDays = 0;

    private int $totalOpportunities = 0;

    private int $totalDoneDays = 0;

    private int $totalNotDoneDays = 0;

    private int $totalMissingDays = 0;

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

        $this->effectiveStartDate =
            $this->resolveEffectiveStartDate();

        if ($this->effectiveStartDate !== null) {
            $this->periodDays = (int) (
                $this->effectiveStartDate
                    ->diffInDays(
                        $this->endDate->startOfDay()
                    ) + 1
            );
        }

        $habits = Habit::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $this->totalHabits = $habits->count();

        $checkins = $this->effectiveStartDate === null
            ? collect()
            : DailyCheckin::query()
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

        $rows = $habits->map(function (
            Habit $habit
        ) use ($checkins): array {
            $habitItems = $checkins
                ->flatMap(
                    fn (DailyCheckin $checkin) =>
                        $checkin->items->filter(
                            fn ($item): bool =>
                                (int) $item->habit_id
                                === (int) $habit->id
                        )
                )
                ->values();

            $recordedDays = $habitItems
                ->count();

            $doneDays = $habitItems
                ->where('is_done', true)
                ->count();

            $notDoneDays = max(
                $recordedDays - $doneDays,
                0
            );

            $missingDays = max(
                $this->periodDays - $recordedDays,
                0
            );

            $parentValidations = $habitItems
                ->sum(
                    fn ($item): int =>
                        $item->validations
                            ->where(
                                'validator_role',
                                'orang_tua'
                            )
                            ->count()
                );

            $teacherValidations = $habitItems
                ->sum(
                    fn ($item): int =>
                        $item->validations
                            ->where(
                                'validator_role',
                                'guru'
                            )
                            ->count()
                );

            $completionPercentage =
                $this->periodDays > 0
                    ? round(
                        (
                            $doneDays
                            / $this->periodDays
                        ) * 100,
                        2
                    )
                    : 0;

            $this->totalOpportunities +=
                $this->periodDays;

            $this->totalDoneDays +=
                $doneDays;

            $this->totalNotDoneDays +=
                $notDoneDays;

            $this->totalMissingDays +=
                $missingDays;

            $this->totalParentValidations +=
                $parentValidations;

            $this->totalTeacherValidations +=
                $teacherValidations;

            return [
                'habit_name' =>
                    $habit->name
                    ?? $habit->title
                    ?? "Kebiasaan {$habit->id}",
                'period_days' =>
                    $this->periodDays,
                'done_days' =>
                    $doneDays,
                'not_done_days' =>
                    $notDoneDays,
                'missing_days' =>
                    $missingDays,
                'parent_validations' =>
                    $parentValidations,
                'teacher_validations' =>
                    $teacherValidations,
                'completion_percentage' =>
                    $completionPercentage,
            ];
        });

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
            'Kebiasaan',
            'Hari Periode',
            'Hari Dilakukan',
            'Hari Tidak Dilakukan',
            'Tanpa Data',
            'Validasi Orang Tua',
            'Validasi Guru',
            'Persentase Penyelesaian',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $this->safeText(
                $row['habit_name']
            ),
            $row['period_days'],
            $row['done_days'],
            $row['not_done_days'],
            $row['missing_days'],
            $row['parent_validations'],
            $row['teacher_validations'],
            $row['completion_percentage'],
        ];
    }

    public function title(): string
    {
        return 'Detail Kebiasaan';
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER,
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => '0.00"%"',
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

    private function writeHeader(
        Worksheet $sheet
    ): void {
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        $sheet->setCellValue(
            'A1',
            'STATISTIK KEBIASAAN SISWA'
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

        $sheet->mergeCells('F5:I5');

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

        $sheet->mergeCells('E6:G6');

        $sheet->setCellValue(
            'H6',
            'Tanggal Export'
        );

        $sheet->setCellValue(
            'I6',
            now()
                ->locale('id')
                ->translatedFormat(
                    'd F Y H:i'
                ).' WIB'
        );

        $sheet
            ->getStyle('A1:I3')
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
            ['A5', 'E5', 'A6', 'D6', 'H6']
            as $cell
        ) {
            $sheet
                ->getStyle($cell)
                ->getFont()
                ->setBold(true);
        }

        $sheet
            ->getStyle('A5:I6')
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            )
            ->getColor()
            ->setARGB('FFD6DEE8');

        $sheet
            ->getStyle('A5:I6')
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
            $this->totalHabits > 0
                ? $headerRow
                    + $this->totalHabits
                : $headerRow;

        $sheet->setAutoFilter(
            "A{$headerRow}:I{$lastDataRow}"
        );

        $sheet
            ->getStyle(
                "A{$headerRow}:I{$headerRow}"
            )
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');

        $sheet
            ->getStyle(
                "A{$headerRow}:I{$headerRow}"
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FF168AD3');

        $sheet
            ->getStyle(
                "A{$headerRow}:I{$headerRow}"
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
                "A{$headerRow}:I{$lastDataRow}"
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
                "A{$headerRow}:I{$lastDataRow}"
            )
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        if ($lastDataRow >= $firstDataRow) {
            $sheet
                ->getStyle(
                    "A{$firstDataRow}:A{$lastDataRow}"
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            $sheet
                ->getStyle(
                    "C{$firstDataRow}:I{$lastDataRow}"
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
                            "A{$row}:I{$row}"
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
            ->setRowHeight(38);

        $sheet->getColumnDimension('A')
            ->setWidth(7);

        $sheet->getColumnDimension('B')
            ->setWidth(34);

        $sheet->getColumnDimension('C')
            ->setWidth(14);

        $sheet->getColumnDimension('D')
            ->setWidth(16);

        $sheet->getColumnDimension('E')
            ->setWidth(20);

        $sheet->getColumnDimension('F')
            ->setWidth(14);

        $sheet->getColumnDimension('G')
            ->setWidth(20);

        $sheet->getColumnDimension('H')
            ->setWidth(16);

        $sheet->getColumnDimension('I')
            ->setWidth(23);
    }

    private function writeSummary(
        Worksheet $sheet
    ): void {
        $lastDataRow =
            8 + $this->totalHabits;

        $summaryRow =
            $lastDataRow + 2;

        $overallPercentage =
            $this->totalOpportunities > 0
                ? round(
                    (
                        $this->totalDoneDays
                        / $this->totalOpportunities
                    ) * 100,
                    2
                )
                : 0;

        $sheet->mergeCells(
            "A{$summaryRow}:B{$summaryRow}"
        );

        $sheet->setCellValue(
            "A{$summaryRow}",
            "TOTAL {$this->totalHabits} KEBIASAAN"
        );

        $sheet->setCellValue(
            "C{$summaryRow}",
            $this->totalOpportunities
        );

        $sheet->setCellValue(
            "D{$summaryRow}",
            $this->totalDoneDays
        );

        $sheet->setCellValue(
            "E{$summaryRow}",
            $this->totalNotDoneDays
        );

        $sheet->setCellValue(
            "F{$summaryRow}",
            $this->totalMissingDays
        );

        $sheet->setCellValue(
            "G{$summaryRow}",
            $this->totalParentValidations
        );

        $sheet->setCellValue(
            "H{$summaryRow}",
            $this->totalTeacherValidations
        );

        $sheet->setCellValue(
            "I{$summaryRow}",
            $overallPercentage
        );

        $sheet
            ->getStyle(
                "A{$summaryRow}:I{$summaryRow}"
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "A{$summaryRow}:I{$summaryRow}"
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFEAF4FB');

        $sheet
            ->getStyle(
                "A{$summaryRow}:I{$summaryRow}"
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
                "A{$summaryRow}:I{$summaryRow}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getStyle(
                "I{$summaryRow}"
            )
            ->getNumberFormat()
            ->setFormatCode(
                '0.00"%"'
            );

        $sheet
            ->getRowDimension($summaryRow)
            ->setRowHeight(23);
    }

    private function writeNote(
        Worksheet $sheet
    ): void {
        $noteRow =
            8 + $this->totalHabits + 4;

        $sheet->mergeCells(
            "A{$noteRow}:I{$noteRow}"
        );

        $sheet->setCellValue(
            "A{$noteRow}",
            'Tanpa Data berarti tidak ditemukan item kebiasaan pada tanggal tersebut, termasuk saat siswa tidak mengirim check-in.'
        );

        $sheet
            ->getStyle(
                "A{$noteRow}:I{$noteRow}"
            )
            ->getFont()
            ->setItalic(true)
            ->setSize(9);

        $sheet
            ->getStyle(
                "A{$noteRow}:I{$noteRow}"
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
