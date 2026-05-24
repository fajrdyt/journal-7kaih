<?php

namespace App\Http\Requests\Checkin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->name === 'student';
    }

    public function rules(): array
    {
        return [
            // Jika kosong → pakai hari ini (di service)
            // Jika diisi → wajib hari ini
            'checkin_date'             => [
                'nullable',
                'date',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) {
                    if ($value && $value !== today()->format('Y-m-d')) {
                        $fail('Check-in hanya bisa dilakukan untuk hari ini.');
                    }
                },
            ],
            'notes'                    => ['nullable', 'string', 'max:1000'],
            'items'                    => ['required', 'array', 'min:7', 'max:7'],
            'items.*.habit_id'         => ['required', 'integer', 'exists:habits,id'],
            'items.*.is_done'          => ['required', 'boolean'],
            'items.*.activity_context' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'            => 'Data kebiasaan wajib diisi.',
            'items.min'                 => 'Semua 7 kebiasaan harus diisi.',
            'items.max'                 => 'Jumlah kebiasaan tidak boleh lebih dari 7.',
            'items.*.habit_id.required' => 'Habit ID wajib diisi.',
            'items.*.habit_id.exists'   => 'Habit tidak ditemukan.',
            'items.*.is_done.required'  => 'Status kebiasaan wajib diisi.',
            'items.*.is_done.boolean'   => 'Status kebiasaan harus true atau false.',
        ];
    }
}