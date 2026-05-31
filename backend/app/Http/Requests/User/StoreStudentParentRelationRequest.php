<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentParentRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id'    => ['required', 'integer', 'exists:users,id'],
            'parent_id'     => ['required', 'integer', 'exists:users,id'],
            'relation_type' => ['required', 'string', 'max:50'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }
}