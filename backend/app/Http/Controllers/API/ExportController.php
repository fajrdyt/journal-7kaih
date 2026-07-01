<?php

namespace App\Http\Controllers\API;

use App\Exports\ClassesExport;
use App\Exports\ClassCheckinsExport;
use App\Exports\StudentRecapExport;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function exportClasses(
        Request $request
    ): BinaryFileResponse {
        $validated = $request->validate([
            'class_id' => [
                'nullable',
                'integer',
                'exists:classes,id',
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'grade_level' => [
                'nullable',
                'string',
                'max:50',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $filters = [
            'class_id' =>
                $validated['class_id'] ?? null,
            'search' =>
                $validated['search'] ?? null,
            'grade_level' =>
                $validated['grade_level'] ?? null,
            'is_active' =>
                $validated['is_active'] ?? null,
        ];

        return Excel::download(
            new ClassesExport($filters),
            $this->buildClassFileName(
                $filters['class_id']
            ),
            ExcelWriter::XLSX,
            $this->downloadHeaders()
        );
    }

    public function exportClassCheckins(
        Request $request
    ): BinaryFileResponse {
        $validated = $request->validate(
            array_merge(
                [
                    'class_id' => [
                        'required',
                        'integer',
                        'exists:classes,id',
                    ],
                ],
                $this->dateRangeRules()
            )
        );

        [$startDate, $endDate] =
            $this->resolveDateRange(
                $validated['start_date'],
                $validated['end_date']
            );

        $classRoom = ClassRoom::query()
            ->findOrFail(
                $validated['class_id']
            );

        return Excel::download(
            new ClassCheckinsExport(
                classId: $classRoom->id,
                startDate: $startDate,
                endDate: $endDate
            ),
            $this->buildClassCheckinFileName(
                classRoom: $classRoom,
                startDate: $startDate,
                endDate: $endDate
            ),
            ExcelWriter::XLSX,
            $this->downloadHeaders()
        );
    }

    public function exportStudentRecap(
        Request $request
    ): BinaryFileResponse {
        $validated = $request->validate(
            array_merge(
                [
                    'student_id' => [
                        'required',
                        'integer',
                        'exists:users,id',
                    ],
                ],
                $this->dateRangeRules()
            )
        );

        [$startDate, $endDate] =
            $this->resolveDateRange(
                $validated['start_date'],
                $validated['end_date']
            );

        $student = User::query()
            ->whereHas(
                'role',
                function ($query) {
                    $query->where(
                        'name',
                        'siswa'
                    );
                }
            )
            ->find(
                $validated['student_id']
            );

        if (!$student) {
            throw ValidationException::withMessages([
                'student_id' => [
                    'Pengguna yang dipilih harus memiliki peran siswa.',
                ],
            ]);
        }

        return Excel::download(
            new StudentRecapExport(
                studentId: $student->id,
                startDate: $startDate,
                endDate: $endDate
            ),
            $this->buildStudentRecapFileName(
                student: $student,
                startDate: $startDate,
                endDate: $endDate
            ),
            ExcelWriter::XLSX,
            $this->downloadHeaders()
        );
    }

    private function dateRangeRules(): array
    {
        return [
            'start_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                'before_or_equal:today',
            ],
        ];
    }

    private function resolveDateRange(
        string $startDateValue,
        string $endDateValue
    ): array {
        $startDate = CarbonImmutable::createFromFormat(
            'Y-m-d',
            $startDateValue,
            config('app.timezone')
        )->startOfDay();

        $endDate = CarbonImmutable::createFromFormat(
            'Y-m-d',
            $endDateValue,
            config('app.timezone')
        )->startOfDay();

        if (
            $startDate->diffInDays($endDate) > 365
        ) {
            throw ValidationException::withMessages([
                'end_date' => [
                    'Periode export maksimal 366 hari.',
                ],
            ]);
        }

        return [
            $startDate,
            $endDate,
        ];
    }

    private function buildClassFileName(
        ?int $classId
    ): string {
        if (!$classId) {
            return sprintf(
                'data-seluruh-kelas-7kaih-%s.xlsx',
                now()->format('Y-m-d_H-i-s')
            );
        }

        $className = ClassRoom::query()
            ->whereKey($classId)
            ->value('name');

        $safeClassName = Str::slug(
            $className ?: "kelas-{$classId}"
        );

        return sprintf(
            'data-kelas-%s-7kaih-%s.xlsx',
            $safeClassName,
            now()->format('Y-m-d_H-i-s')
        );
    }

    private function buildClassCheckinFileName(
        ClassRoom $classRoom,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate
    ): string {
        $safeClassName = Str::slug(
            $classRoom->name
                ?: "kelas-{$classRoom->id}"
        );

        return sprintf(
            'rekap-checkin-%s-%s-sampai-%s.xlsx',
            $safeClassName,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    private function buildStudentRecapFileName(
        User $student,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate
    ): string {
        $studentName =
            $student->full_name
            ?? $student->name
            ?? $student->username
            ?? "siswa-{$student->id}";

        $safeStudentName = Str::slug(
            $studentName
        );

        if ($safeStudentName === '') {
            $safeStudentName =
                "siswa-{$student->id}";
        }

        return sprintf(
            'rekap-siswa-%s-%s-sampai-%s.xlsx',
            $safeStudentName,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    private function downloadHeaders(): array
    {
        return [
            'Cache-Control' =>
                'no-store, no-cache, must-revalidate',
            'Pragma' =>
                'no-cache',
            'Expires' =>
                '0',
        ];
    }
}
