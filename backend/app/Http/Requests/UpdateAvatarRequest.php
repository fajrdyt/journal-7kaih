<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'bail',
                'required',
                File::image()
                    ->extensions([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('2mb')
                    ->dimensions(
                        Rule::dimensions()
                            ->minWidth(128)
                            ->minHeight(128)
                            ->maxWidth(4096)
                            ->maxHeight(4096)
                    ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Foto profil wajib dipilih.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.extensions' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'avatar.max' => 'Ukuran foto maksimal 2 MB.',
            'avatar.dimensions' => 'Dimensi foto minimal 128 × 128 piksel dan maksimal 4096 × 4096 piksel.',
        ];
    }

    public function attributes(): array
    {
        return [
            'avatar' => 'foto profil',
        ];
    }
}