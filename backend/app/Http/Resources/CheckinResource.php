<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'student_id'            => $this->student_id,
            'checkin_date'          => $this->checkin_date->format('Y-m-d'),
            'notes'                 => $this->notes,
            'submitted_at'          => $this->submitted_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
            'total_habits_done'     => $this->items->where('is_done', true)->count(),
            'total_items_validated' => $this->items->filter(fn($item) => $item->validation !== null)->count(),
            'items' => $this->items->map(fn($item) => [
                'id'               => $item->id,
                'habit_id'         => $item->habit_id,
                'habit_code'       => $item->habit->code,
                'habit_name'       => $item->habit->name,
                'is_done'          => $item->is_done,
                'activity_context' => $item->activity_context,
                'validation'       => $item->validation ? [
                    'id'               => $item->validation->id,
                    'validator_id'     => $item->validation->validator_id,
                    'validator_name'   => $item->validation->validator->full_name,
                    'validator_role'   => $item->validation->validator_role,
                    'validation_source'=> $item->validation->validation_source,
                    'validated_at'     => $item->validation->validated_at?->toIso8601String(),
                ] : null,
            ]),
        ];
    }
}