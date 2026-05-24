<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentParentRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id'    => ['nullable', 'integer', 'exists:users,id'],
            'parent_id'     => ['nullable', 'integer', 'exists:users,id'],
            'relation_type' => ['nullable', 'string', 'max:50'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }
}