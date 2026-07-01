<?php

namespace App\Exports\Sheets;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassStudentsSheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly array $filters = []
    ) {
    }

    public function collection(): Collection
    {
        $query = User::query()
            ->select([
                'id',
                'class_id',
                'full_name',
                'name',
                'username',
                'nisn',
                'email',
                'phone',
                'is_active',
                'role_id',
            ])
            ->with([
                'classRoom:id,name,grade_level,is_active,teacher_id',
            ])
            ->whereHas('role', function (Builder $roleQuery) {
                $roleQuery->where('name', 'siswa');
            })
            ->whereNotNull('class_id');

        $classId = $this->selectedClassId();

        if ($classId !== null) {
            $query->where(
                'users.class_id',
                $classId
            );
        }

        $query->whereHas(
            'classRoom',
            function (Builder $classQuery) {
                $this->applyClassFilters($classQuery);
            }
        );

        return $query
            ->join(
                'classes',
                'users.class_id',
                '=',
                'classes.id'
            )
            ->orderBy('classes.grade_level')
            ->orderBy('classes.name')
            ->orderBy('users.full_name')
            ->select('users.*')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Kelas',
            'Tingkat',
            'NISN',
            'Nama Siswa',
            'Username',
            'Email',
            'Nomor Telepon',
            'Status Akun',
        ];
    }

    public function map($student): array
    {
        $studentName = $student->full_name
            ?? $student->name
            ?? $student->username
            ?? '-';

        return [
            ++$this->rowNumber,
            $this->safeText(
                $student->classRoom?->name
            ),
            $this->safeText(
                $student->classRoom?->grade_level
            ),
            $this->safeText($student->nisn),
            $this->safeText($studentName),
            $this->safeText($student->username),
            $this->safeText($student->email),
            $this->safeText($student->phone),
            $student->is_active
                ? 'Aktif'
                : 'Nonaktif',
        ];
    }

    public function title(): string
    {
        return 'Anggota Kelas';
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = max(
            $sheet->getHighestRow(),
            1
        );

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:I1');

        $sheet
            ->getStyle("A1:I{$highestRow}")
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet
            ->getStyle("A2:A{$highestRow}")
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet
            ->getStyle("C2:D{$highestRow}")
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet
            ->getStyle("I2:I{$highestRow}")
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet
            ->getRowDimension(1)
            ->setRowHeight(24);

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => [
                        'argb' => 'FFFFFFFF',
                    ],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF168AD3',
                    ],
                ],
                'alignment' => [
                    'horizontal' =>
                        Alignment::HORIZONTAL_CENTER,
                    'vertical' =>
                        Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    private function applyClassFilters(
        Builder $query
    ): void {
        $search = trim(
            (string) (
                $this->filters['search'] ?? ''
            )
        );

        if ($search !== '') {
            $query->where(
                function (
                    Builder $searchQuery
                ) use ($search) {
                    $searchQuery
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
                            function (
                                Builder $teacherQuery
                            ) use ($search) {
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
                }
            );
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
