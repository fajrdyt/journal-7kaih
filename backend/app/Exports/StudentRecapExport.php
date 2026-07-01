<?php

namespace App\Exports;

use App\Exports\Sheets\StudentDailyHistorySheet;
use App\Exports\Sheets\StudentHabitStatisticsSheet;
use App\Exports\Sheets\StudentRecapSummarySheet;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentRecapExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $studentId,
        private readonly CarbonImmutable $startDate,
        private readonly CarbonImmutable $endDate
    ) {
    }

    public function sheets(): array
    {
        return [
            new StudentRecapSummarySheet(
                studentId: $this->studentId,
                startDate: $this->startDate,
                endDate: $this->endDate
            ),

            new StudentHabitStatisticsSheet(
                studentId: $this->studentId,
                startDate: $this->startDate,
                endDate: $this->endDate
            ),

            new StudentDailyHistorySheet(
                studentId: $this->studentId,
                startDate: $this->startDate,
                endDate: $this->endDate
            ),
        ];
    }
}