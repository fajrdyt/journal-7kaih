<?php

namespace App\Exports\Sheets;

use App\Models\DailyCheckin;
use App\Models\Habit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentRecapSummarySheet implements
    FromArray,
    WithTitle,
    WithEvents
{
    private ?User $student = null;

    private int $activeHabitCount = 0;

    private int $periodDays = 0;

    private int $checkinDays = 0;

    private int $missedDays = 0;

    private int $pendingDays = 0;

    private int $completeDays = 0;

    private int $incompleteDays = 0;

    private int $doneItems = 0;

    private int $possibleItems = 0;

    private int $parentValidations = 0;

    private int $teacherValidations = 0;

    private float $checkinPercentage = 0;

    private float $habitCompletionPercentage = 0;

    private ?CarbonImmutable $effectiveStartDate = null;

    private ?CarbonImmutable $lastCheckinDate = null;

    public function __construct(
        private readonly int $studentId,
        private readonly CarbonImmutable $startDate,
        private readonly CarbonImmutable $endDate
    ) {
    }

    public function array(): array
    {
        $this->prepareData();

        return [[]];
    }

    public function title(): string
    {
        return 'Ringkasan Siswa';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (
                AfterSheet $event
            ): void {
                $sheet = $event->sheet->getDelegate();

                $this->writeHeader($sheet);
                $this->writeStudentIdentity($sheet);
                $this->writeSummary($sheet);
                $this->configurePrint($sheet);
            },
        ];
    }

    private function prepareData(): void
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
            return;
        }

        $this->periodDays = (int) (
            $this->effectiveStartDate
                ->diffInDays(
                    $this->endDate->startOfDay()
                ) + 1
        );

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

        $this->checkinDays =
            $checkinsByDate->count();

        $today = now()
            ->toImmutable()
            ->startOfDay();

        $currentDate =
            $this->effectiveStartDate;

        while (
            $currentDate->lessThanOrEqualTo(
                $this->endDate->startOfDay()
            )
        ) {
            $hasCheckin = $checkinsByDate
                ->has(
                    $currentDate->toDateString()
                );

            if (!$hasCheckin) {
                if ($currentDate->equalTo($today)) {
                    $this->pendingDays++;
                } elseif (
                    $currentDate->lessThan($today)
                ) {
                    $this->missedDays++;
                }
            }

            $currentDate = $currentDate->addDay();
        }

        foreach (
            $checkinsByDate as $checkin
        ) {
            $completedItems = $checkin
                ->items
                ->where('is_done', true)
                ->count();

            $this->doneItems += $completedItems;

            $isComplete =
                $this->activeHabitCount > 0
                && $completedItems
                    >= $this->activeHabitCount;

            if ($isComplete) {
                $this->completeDays++;
            } else {
                $this->incompleteDays++;
            }

            $this->parentValidations +=
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

            $this->teacherValidations +=
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
        }

        $this->possibleItems =
            $this->periodDays
            * $this->activeHabitCount;

        $this->checkinPercentage =
            $this->periodDays > 0
                ? round(
                    (
                        $this->checkinDays
                        / $this->periodDays
                    ) * 100,
                    2
                )
                : 0;

        $this->habitCompletionPercentage =
            $this->possibleItems > 0
                ? round(
                    (
                        $this->doneItems
                        / $this->possibleItems
                    ) * 100,
                    2
                )
                : 0;

        $lastCheckinDate = $checkinsByDate
            ->keys()
            ->sortDesc()
            ->first();

        if ($lastCheckinDate) {
            $this->lastCheckinDate =
                CarbonImmutable::parse(
                    $lastCheckinDate
                );
        }
    }

    private function writeHeader(
        Worksheet $sheet
    ): void {
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');

        $sheet->setCellValue(
            'A1',
            'REKAP INDIVIDU SISWA'
        );

        $sheet->setCellValue(
            'A2',
            'JURNAL 7 KEBIASAAN ANAK INDONESIA HEBAT'
        );

        $sheet->setCellValue(
            'A3',
            'SMA NEGERI 1 MIRIT'
        );

        $sheet
            ->getStyle('A1:F3')
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

        $sheet->getRowDimension(1)
            ->setRowHeight(25);

        $sheet->getRowDimension(2)
            ->setRowHeight(20);

        $sheet->getRowDimension(3)
            ->setRowHeight(19);
    }

    private function writeStudentIdentity(
        Worksheet $sheet
    ): void {
        $studentName =
            $this->student?->full_name
            ?? $this->student?->name
            ?? $this->student?->username
            ?? '-';

        $sheet->setCellValue(
            'A5',
            'Nama Siswa'
        );

        $sheet->setCellValue(
            'B5',
            $this->safeText($studentName)
        );

        $sheet->mergeCells('B5:C5');

        $sheet->setCellValue(
            'D5',
            'NISN'
        );

        $sheet->setCellValueExplicit(
            'E5',
            (string) (
                $this->student?->nisn ?? '-'
            ),
            DataType::TYPE_STRING
        );

        $sheet->mergeCells('E5:F5');

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
            'Tingkat'
        );

        $sheet->setCellValue(
            'E6',
            $this->safeText(
                $this->student
                    ?->classRoom
                    ?->grade_level
                ?? '-'
            )
        );

        $sheet->mergeCells('E6:F6');

        $sheet->setCellValue(
            'A7',
            'Guru Pengampu'
        );

        $sheet->setCellValue(
            'B7',
            $this->safeText(
                $this->teacherName()
            )
        );

        $sheet->mergeCells('B7:C7');

        $sheet->setCellValue(
            'D7',
            'Status Akun'
        );

        $sheet->setCellValue(
            'E7',
            $this->student?->is_active
                ? 'Aktif'
                : 'Nonaktif'
        );

        $sheet->mergeCells('E7:F7');

        $sheet->setCellValue(
            'A8',
            'Username'
        );

        $sheet->setCellValue(
            'B8',
            $this->safeText(
                $this->student?->username
                ?? '-'
            )
        );

        $sheet->mergeCells('B8:C8');

        $sheet->setCellValue(
            'D8',
            'Email'
        );

        $sheet->setCellValue(
            'E8',
            $this->safeText(
                $this->student?->email
                ?? '-'
            )
        );

        $sheet->mergeCells('E8:F8');

        $sheet->setCellValue(
            'A9',
            'Periode'
        );

        $sheet->setCellValue(
            'B9',
            $this->periodLabel()
        );

        $sheet->mergeCells('B9:C9');

        $sheet->setCellValue(
            'D9',
            'Tanggal Export'
        );

        $sheet->setCellValue(
            'E9',
            now()
                ->locale('id')
                ->translatedFormat(
                    'd F Y H:i'
                ).' WIB'
        );

        $sheet->mergeCells('E9:F9');

        foreach (
            [
                'A5',
                'D5',
                'A6',
                'D6',
                'A7',
                'D7',
                'A8',
                'D8',
                'A9',
                'D9',
            ]
            as $cell
        ) {
            $sheet
                ->getStyle($cell)
                ->getFont()
                ->setBold(true);
        }

        $sheet
            ->getStyle('A5:F9')
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            )
            ->getColor()
            ->setARGB('FFD6DEE8');

        $sheet
            ->getStyle('A5:F9')
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        for ($row = 5; $row <= 9; $row++) {
            $sheet
                ->getRowDimension($row)
                ->setRowHeight(21);
        }
    }

    private function writeSummary(
        Worksheet $sheet
    ): void {
        $sheet->mergeCells('A11:F11');

        $sheet->setCellValue(
            'A11',
            'RINGKASAN PERIODE'
        );

        $sheet
            ->getStyle('A11:F11')
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');

        $sheet
            ->getStyle('A11:F11')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF168AD3');

        $sheet
            ->getStyle('A11:F11')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet->setCellValue(
            'A12',
            'Hari Periode'
        );

        $sheet->setCellValue(
            'B12',
            $this->periodDays
        );

        $sheet->setCellValue(
            'C12',
            'Hari Check-in'
        );

        $sheet->setCellValue(
            'D12',
            $this->checkinDays
        );

        $sheet->setCellValue(
            'E12',
            'Hari Terlewat'
        );

        $sheet->setCellValue(
            'F12',
            $this->missedDays
        );

        $sheet->setCellValue(
            'A13',
            'Belum Check-in Hari Ini'
        );

        $sheet->setCellValue(
            'B13',
            $this->pendingDays
        );

        $sheet->setCellValue(
            'C13',
            'Hari Lengkap'
        );

        $sheet->setCellValue(
            'D13',
            $this->completeDays
        );

        $sheet->setCellValue(
            'E13',
            'Hari Belum Lengkap'
        );

        $sheet->setCellValue(
            'F13',
            $this->incompleteDays
        );

        $sheet->setCellValue(
            'A14',
            'Item Selesai'
        );

        $sheet->setCellValue(
            'B14',
            $this->doneItems
        );

        $sheet->setCellValue(
            'C14',
            'Total Peluang Item'
        );

        $sheet->setCellValue(
            'D14',
            $this->possibleItems
        );

        $sheet->setCellValue(
            'E14',
            'Persentase Kebiasaan'
        );

        $sheet->setCellValue(
            'F14',
            $this->habitCompletionPercentage
        );

        $sheet->setCellValue(
            'A15',
            'Validasi Orang Tua'
        );

        $sheet->setCellValue(
            'B15',
            $this->parentValidations
        );

        $sheet->setCellValue(
            'C15',
            'Validasi Guru'
        );

        $sheet->setCellValue(
            'D15',
            $this->teacherValidations
        );

        $sheet->setCellValue(
            'E15',
            'Persentase Check-in'
        );

        $sheet->setCellValue(
            'F15',
            $this->checkinPercentage
        );

        $sheet->setCellValue(
            'A16',
            'Check-in Terakhir'
        );

        $sheet->setCellValue(
            'B16',
            $this->lastCheckinLabel()
        );

        $sheet->mergeCells('B16:C16');

        $sheet->setCellValue(
            'D16',
            'Kebiasaan Aktif'
        );

        $sheet->setCellValue(
            'E16',
            $this->activeHabitCount
        );

        $sheet->mergeCells('E16:F16');

        foreach (
            [
                'A12',
                'C12',
                'E12',
                'A13',
                'C13',
                'E13',
                'A14',
                'C14',
                'E14',
                'A15',
                'C15',
                'E15',
                'A16',
                'D16',
            ]
            as $cell
        ) {
            $sheet
                ->getStyle($cell)
                ->getFont()
                ->setBold(true);

            $sheet
                ->getStyle($cell)
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                )
                ->getStartColor()
                ->setARGB('FFEAF4FB');
        }

        $sheet
            ->getStyle('A12:F16')
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            )
            ->getColor()
            ->setARGB('FFD6DEE8');

        $sheet
            ->getStyle('A12:F16')
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getStyle('B12:F16')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet
            ->getStyle('F14')
            ->getNumberFormat()
            ->setFormatCode('0.00"%"');

        $sheet
            ->getStyle('F15')
            ->getNumberFormat()
            ->setFormatCode('0.00"%"');

        for ($row = 12; $row <= 16; $row++) {
            $sheet
                ->getRowDimension($row)
                ->setRowHeight(23);
        }

        $sheet->mergeCells('A18:F18');

        $sheet->setCellValue(
            'A18',
            'Hari periode dihitung sejak tanggal akun siswa dibuat apabila akun dibuat di tengah periode.'
        );

        $sheet
            ->getStyle('A18:F18')
            ->getFont()
            ->setItalic(true)
            ->setSize(9);

        $sheet
            ->getStyle('A18:F18')
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getRowDimension(18)
            ->setRowHeight(30);

        $sheet->getColumnDimension('A')
            ->setWidth(23);

        $sheet->getColumnDimension('B')
            ->setWidth(17);

        $sheet->getColumnDimension('C')
            ->setWidth(23);

        $sheet->getColumnDimension('D')
            ->setWidth(17);

        $sheet->getColumnDimension('E')
            ->setWidth(23);

        $sheet->getColumnDimension('F')
            ->setWidth(18);
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
            ->setFitToHeight(1);

        $sheet
            ->getPageMargins()
            ->setTop(0.5)
            ->setBottom(0.5)
            ->setLeft(0.4)
            ->setRight(0.4);

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
        $periodStart = $this->startDate
            ->startOfDay();

        $periodEnd = $this->endDate
            ->startOfDay();

        if (!$this->student?->created_at) {
            return $periodStart;
        }

        $accountStart = CarbonImmutable::parse(
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

    private function teacherName(): string
    {
        return $this->student
            ?->classRoom
            ?->teacher
            ?->full_name
            ?? $this->student
                ?->classRoom
                ?->teacher
                ?->name
            ?? $this->student
                ?->classRoom
                ?->teacher
                ?->username
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

    private function lastCheckinLabel(): string
    {
        if ($this->lastCheckinDate === null) {
            return 'Belum ada check-in';
        }

        return $this->lastCheckinDate
            ->locale('id')
            ->translatedFormat('d F Y');
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