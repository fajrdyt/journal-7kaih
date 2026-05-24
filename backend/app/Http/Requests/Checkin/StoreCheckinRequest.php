<?php

namespace App\Http\Requests\Checkin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->name === 'siswa';
    }

    public function rules(): array
    {
        return [
            'checkin_date' => [
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
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.habit_id'         => ['required', 'integer', 'exists:habits,id'],
            'items.*.is_done'          => ['required', 'boolean'],
            'items.*.activity_context' => [
                'nullable',
                'string',
                'in:rumah,sekolah',
                function ($attribute, $value, $fail) {
                    preg_match('/items\.(\d+)\.activity_context/', $attribute, $matches);
                    $index  = $matches[1] ?? null;
                    $isDone = $this->input("items.$index.is_done");

                    if ($isDone && !$value) {
                        $fail('Konteks aktivitas wajib diisi jika kebiasaan dilakukan.');
                    }

                    if (!$isDone && $value) {
                        $fail('Konteks aktivitas harus kosong jika kebiasaan tidak dilakukan.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                => 'Data kebiasaan wajib diisi.',
            'items.min'                     => 'Minimal 1 kebiasaan harus diisi.',
            'items.*.habit_id.required'     => 'Habit ID wajib diisi.',
            'items.*.habit_id.exists'       => 'Habit tidak ditemukan.',
            'items.*.is_done.required'      => 'Status kebiasaan wajib diisi.',
            'items.*.is_done.boolean'       => 'Status kebiasaan harus true atau false.',
            'items.*.activity_context.in'   => 'Konteks aktivitas harus rumah atau sekolah.',
        ];
    }
}