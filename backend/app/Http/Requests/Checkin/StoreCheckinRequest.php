<?php

namespace App\Http\Requests\Checkin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes'             => ['nullable', 'string', 'max:1000'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.habit_id'  => ['required', 'integer', 'exists:habits,id'],
            'items.*.is_done'   => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'            => 'Item check-in wajib diisi.',
            'items.array'               => 'Item check-in harus berupa array.',
            'items.*.habit_id.required' => 'Habit wajib dipilih.',
            'items.*.habit_id.exists'   => 'Habit tidak ditemukan.',
            'items.*.is_done.required'  => 'Status kebiasaan wajib diisi.',
            'items.*.is_done.boolean'   => 'Status kebiasaan harus true atau false.',
        ];
    }
}