<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'full_name' => $this->full_name,
            'username'  => $this->username,
            'email'     => $this->email,
            'phone'     => $this->phone,
            'is_active' => $this->is_active,

            'role' => $this->whenLoaded('role', function () {
                return $this->role ? [
                    'id'   => $this->role->id,
                    'name' => $this->role->name,
                ] : null;
            }),

            'class' => $this->whenLoaded('classRoom', function () {
                return $this->classRoom ? [
                    'id'          => $this->classRoom->id,
                    'name'        => $this->classRoom->name,
                    'grade_level' => $this->classRoom->grade_level,
                ] : null;
            }),
        ];
    }
}