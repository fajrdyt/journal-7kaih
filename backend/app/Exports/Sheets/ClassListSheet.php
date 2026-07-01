<?php

namespace App\Exports\Sheets;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
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

class ClassListSheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    ShouldAutoSize,
    WithCustomStartCell,
    WithEvents
{
    private int $rowNumber = 0;

    private int $totalClasses = 0;

    private int $totalStudents = 0;

    public function __construct(
        private readonly array $filters = []
    ) {
    }

    public function collection(): Collection
    {
        $studentCountQuery = User::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn(
                'users.class_id',
                'classes.id'
            )
            ->where('users.is_active', true)
            ->whereHas('role', function ($query) {
                $query->where('name', 'siswa');
            });

        $query = ClassRoom::query()
            ->select('classes.*')
            ->selectSub(
                $studentCountQuery,
                'active_students_count'
            )
            ->with([
                'teacher:id,full_name,name,username',
            ])
            ->orderBy('grade_level')
            ->orderBy('name');

        $classId = $this->selectedClassId();

        if ($classId !== null) {
            $query->whereKey($classId);
        }

        $search = trim(
            (string) ($this->filters['search'] ?? '')
        );

        if ($search !== '') {
            $query->where(function ($classQuery) use ($search) {
                $classQuery
                    ->where(
                        'classes.name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'classes.grade_level',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'teacher',
                        function ($teacherQuery) use ($search) {
                            $teacherQuery
                                ->where(
                                    'full_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'username',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
            });
        }

        $gradeLevel = trim(
            (string) (
                $this->filters['grade_level'] ?? ''
            )
        );

        if ($gradeLevel !== '') {
            $query->where(
                'classes.grade_level',
                $gradeLevel
            );
        }

        if ($this->hasActiveFilter()) {
            $query->where(
                'classes.is_active',
                $this->activeFilterValue()
            );
        }

        $classes = $query->get();

        $this->totalClasses = $classes->count();

        $this->totalStudents = $classes->sum(
            fn ($class) => (int) (
                $class->active_students_count ?? 0
            )
        );

        return $classes;
    }

    public function startCell(): string
    {
        return 'A7';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Kelas',
            'Tingkat',
            'Guru Pengampu',
            'Jumlah Siswa Aktif',
            'Status',
        ];
    }

    public function map($class): array
    {
        $teacherName = $class->teacher?->full_name
            ?? $class->teacher?->name
            ?? $class->teacher?->username
            ?? 'Belum ditentukan';

        return [
            ++$this->rowNumber,
            $this->safeText($class->name),
            $this->safeText($class->grade_level),
            $this->safeText($teacherName),
            max(
                0,
                (int) (
                    $class->active_students_count ?? 0
                )
            ),
            $class->is_active
                ? 'Aktif'
                : 'Nonaktif',
        ];
    }

    public function title(): string
    {
        return 'Daftar Kelas';
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
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');

        $sheet->setCellValue(
            'A1',
            'DATA KELAS'
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
            'Tanggal Export'
        );

        $sheet->setCellValue(
            'B5',
            now()->format('d/m/Y H:i').' WIB'
        );

        $sheet->setCellValue(
            'D5',
            'Filter'
        );

        $sheet->setCellValue(
            'E5',
            $this->filterDescription()
        );

        $sheet->mergeCells('E5:F5');

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

        $sheet
            ->getStyle('A5')
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle('D5')
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle('A5:F5')
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(19);
        $sheet->getRowDimension(5)->setRowHeight(20);
    }

    private function styleTable(
        Worksheet $sheet
    ): void {
        $headerRow = 7;
        $firstDataRow = 8;

        $lastDataRow = $this->totalClasses > 0
            ? $headerRow + $this->totalClasses
            : $headerRow;

        $sheet->setAutoFilter(
            "A{$headerRow}:F{$lastDataRow}"
        );

        $sheet
            ->getStyle("A{$headerRow}:F{$headerRow}")
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');

        $sheet
            ->getStyle("A{$headerRow}:F{$headerRow}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF168AD3');

        $sheet
            ->getStyle("A{$headerRow}:F{$headerRow}")
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getStyle(
                "A{$headerRow}:F{$lastDataRow}"
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
                "A{$headerRow}:F{$lastDataRow}"
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
                    "C{$firstDataRow}:C{$lastDataRow}"
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            $sheet
                ->getStyle(
                    "E{$firstDataRow}:F{$lastDataRow}"
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            $sheet
                ->getStyle(
                    "E{$firstDataRow}:E{$lastDataRow}"
                )
                ->getNumberFormat()
                ->setFormatCode(
                    NumberFormat::FORMAT_NUMBER
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
                        ->getStyle("A{$row}:F{$row}")
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
            ->setRowHeight(24);

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(16);
    }

    private function writeSummary(
        Worksheet $sheet
    ): void {
        $lastDataRow = 7 + $this->totalClasses;
        $summaryStartRow = $lastDataRow + 2;

        $sheet->setCellValue(
            "D{$summaryStartRow}",
            'Total Kelas'
        );

        $sheet->setCellValue(
            "E{$summaryStartRow}",
            $this->totalClasses
        );

        $sheet->setCellValue(
            'D'.($summaryStartRow + 1),
            'Total Siswa Aktif'
        );

        $sheet->setCellValue(
            'E'.($summaryStartRow + 1),
            $this->totalStudents
        );

        $sheet
            ->getStyle(
                "D{$summaryStartRow}:E".
                ($summaryStartRow + 1)
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
                "D{$summaryStartRow}:D".
                ($summaryStartRow + 1)
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "D{$summaryStartRow}:D".
                ($summaryStartRow + 1)
            )
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFEAF4FB');

        $sheet
            ->getStyle(
                "E{$summaryStartRow}:E".
                ($summaryStartRow + 1)
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet
            ->getStyle(
                "E{$summaryStartRow}:E".
                ($summaryStartRow + 1)
            )
            ->getNumberFormat()
            ->setFormatCode(
                NumberFormat::FORMAT_NUMBER
            );
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
                7,
                7
            );

        $sheet
            ->getPageMargins()
            ->setTop(0.5);

        $sheet
            ->getPageMargins()
            ->setBottom(0.5);

        $sheet
            ->getPageMargins()
            ->setLeft(0.4);

        $sheet
            ->getPageMargins()
            ->setRight(0.4);

        $sheet
            ->getHeaderFooter()
            ->setOddFooter(
                '&LJurnal 7KAIH'.
                '&CHalaman &P dari &N'.
                '&RSMA Negeri 1 Mirit'
            );
    }

    private function filterDescription(): string
    {
        $filters = [];

        $classId = $this->selectedClassId();

        if ($classId !== null) {
            $className = ClassRoom::query()
                ->whereKey($classId)
                ->value('name');

            $filters[] = 'Kelas: '.(
                $className ?: "ID {$classId}"
            );
        }

        $search = trim(
            (string) ($this->filters['search'] ?? '')
        );

        if ($search !== '') {
            $filters[] = "Pencarian: {$search}";
        }

        $gradeLevel = trim(
            (string) (
                $this->filters['grade_level'] ?? ''
            )
        );

        if ($gradeLevel !== '') {
            $filters[] = "Tingkat: {$gradeLevel}";
        }

        if ($this->hasActiveFilter()) {
            $filters[] = $this->activeFilterValue()
                ? 'Status: Aktif'
                : 'Status: Nonaktif';
        }

        return $filters !== []
            ? implode(' | ', $filters)
            : 'Semua kelas';
    }

    private function selectedClassId(): ?int
    {
        $classId = $this->filters['class_id'] ?? null;

        if (
            !is_numeric($classId) ||
            (int) $classId <= 0
        ) {
            return null;
        }

        return (int) $classId;
    }

    private function hasActiveFilter(): bool
    {
        return array_key_exists(
            'is_active',
            $this->filters
        ) &&
            $this->filters['is_active'] !== null &&
            $this->filters['is_active'] !== '';
    }

    private function activeFilterValue(): bool
    {
        return filter_var(
            $this->filters['is_active'],
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function safeText(
        mixed $value
    ): string {
        $text = trim(
            (string) ($value ?? '')
        );

        if (
            $text !== '' &&
            in_array(
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
