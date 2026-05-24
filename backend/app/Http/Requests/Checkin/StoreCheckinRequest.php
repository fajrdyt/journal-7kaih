<?php

namespace App\Http\Requests\Checkin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya role student yang boleh submit checkin
        return $this->user()->role->name === 'student';
    }

    public function rules(): array
    {
        return [
            'checkin_date'               => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes'                      => ['nullable', 'string', 'max:1000'],

            // items harus array berisi tepat 7 kebiasaan
            'items'                      => ['required', 'array', 'min:7', 'max:7'],
            'items.*.habit_id'           => ['required', 'integer', 'exists:habits,id'],
            'items.*.is_done'            => ['required', 'boolean'],
            'items.*.activity_context'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'checkin_date.required'            => 'Tanggal check-in wajib diisi.',
            'checkin_date.before_or_equal'     => 'Tidak bisa check-in untuk tanggal yang akan datang.',
            'items.required'                   => 'Data kebiasaan wajib diisi.',
            'items.min'                        => 'Semua 7 kebiasaan harus diisi.',
            'items.max'                        => 'Jumlah kebiasaan tidak boleh lebih dari 7.',
            'items.*.habit_id.required'        => 'Habit ID wajib diisi.',
            'items.*.habit_id.exists'          => 'Habit tidak ditemukan.',
            'items.*.is_done.required'         => 'Status kebiasaan wajib diisi.',
            'items.*.is_done.boolean'          => 'Status kebiasaan harus berupa true atau false.',
        ];
    }
}