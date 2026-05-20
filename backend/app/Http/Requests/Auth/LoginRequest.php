<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [

            'identifier' => ['required'],

            'password' => ['required']
        ];
    }

    /**
     * Custom messages
     */
    public function messages(): array
    {
        return [

            'identifier.required' => 'Username wajib diisi',

            'password.required' => 'Password wajib diisi',
        ];
    }
}