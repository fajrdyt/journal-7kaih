<?php

namespace App\Exports;

use App\Exports\Sheets\ClassListSheet;
use App\Exports\Sheets\ClassStudentsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassesExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $filters = []
    ) {
    }

    public function sheets(): array
    {
        return [
            new ClassListSheet($this->filters),
            new ClassStudentsSheet($this->filters),
        ];
    }
}