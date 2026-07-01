<?php

namespace App\Http\Controllers\API;

use App\Exports\ClassesExport;
use App\Exports\ClassCheckinsExport;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
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
            'class_id' => $validated['class_id'] ?? null,
            'search' => $validated['search'] ?? null,
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
        $validated = $request->validate([
            'class_id' => [
                'required',
                'integer',
                'exists:classes,id',
            ],
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
        ]);

        $startDate = CarbonImmutable::parse(
            $validated['start_date']
        )->startOfDay();

        $endDate = CarbonImmutable::parse(
            $validated['end_date']
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

        $classRoom = ClassRoom::query()
            ->findOrFail($validated['class_id']);

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

    private function downloadHeaders(): array
    {
        return [
            'Cache-Control' =>
                'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}