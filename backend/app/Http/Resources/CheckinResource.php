<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'checkin_date' => $this->checkin_date->format('Y-m-d'),
            'notes'        => $this->notes,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'done_count'   => $this->done_count,          // dari accessor di model
            'total_habits' => $this->items->count(),
            'student'      => [
                'id'        => $this->student->id,
                'full_name' => $this->student->full_name,
            ],
            'items' => $this->items->map(fn($item) => [
                'id'               => $item->id,
                'habit_id'         => $item->habit_id,
                'habit_code'       => $item->habit->code,
                'habit_name'       => $item->habit->name,
                'is_done'          => $item->is_done,
                'activity_context' => $item->activity_context,
            ]),
        ];
    }
}