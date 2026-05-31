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
            'items' => $this->items->map(fn ($item) => [
            'id'       => $item->id,
            'habit_id' => $item->habit_id,
            'habit'    => $item->habit ? [
                'id'         => $item->habit->id,
                'code'       => $item->habit->code,
                'name'       => $item->habit->name,
                'sort_order' => $item->habit->sort_order,
            ] : null,
            'is_done' => $item->is_done,
            'validations' => $item->validations
                ? $item->validations->map(fn ($validation) => [
                    'id'                => $validation->id,
                    'validator_id'      => $validation->validator_id,
                    'validator_role'    => $validation->validator_role,
                    'validation_source' => $validation->validation_source,
                    'validated_at'      => $validation->validated_at,
                ])->values()
                : [],
        ])->values(),
        ];
    }
}