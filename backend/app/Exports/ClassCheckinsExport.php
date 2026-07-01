<?php

namespace App\Exports;

use App\Exports\Sheets\ClassCheckinDailySheet;
use App\Exports\Sheets\ClassCheckinSummarySheet;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassCheckinsExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $classId,
        private readonly CarbonImmutable $startDate,
        private readonly CarbonImmutable $endDate
    ) {
    }

    public function sheets(): array
    {
        return [
            new ClassCheckinSummarySheet(
                classId: $this->classId,
                startDate: $this->startDate,
                endDate: $this->endDate
            ),

            new ClassCheckinDailySheet(
                classId: $this->classId,
                startDate: $this->startDate,
                endDate: $this->endDate
            ),
        ];
    }
}