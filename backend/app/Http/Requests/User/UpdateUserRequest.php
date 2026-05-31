<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'role_id'   => ['nullable', 'integer', 'exists:roles,id'],
            'class_id'  => ['nullable', 'integer', 'exists:classes,id'],
            'name'      => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'username'  => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($id),
            ],
            'email'     => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'phone'     => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}