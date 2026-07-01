<?php

namespace App\Http\Controllers\API;

use App\Exports\ClassesExport;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $fileName = $this->buildFileName(
            $filters['class_id']
        );

        return Excel::download(
            new ClassesExport($filters),
            $fileName,
            ExcelWriter::XLSX,
            [
                'Cache-Control' =>
                    'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }

    private function buildFileName(
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
}