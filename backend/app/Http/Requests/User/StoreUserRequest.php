<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id'   => ['required', 'integer', 'exists:roles,id'],
            'class_id'  => ['nullable', 'integer', 'exists:classes,id'],
            'name'      => ['nullable', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'password'  => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}