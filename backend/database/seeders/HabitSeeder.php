<?php

namespace Database\Seeders;

use App\Models\Habit;
use Illuminate\Database\Seeder;

class HabitSeeder extends Seeder
{
    public function run(): void
    {
        $habits = [
            [
                'code'       => 'BANGUN_PAGI',
                'name'       => 'Bangun Pagi',
                'sort_order' => 1,
            ],
            [
                'code'       => 'BERIBADAH',
                'name'       => 'Beribadah',
                'sort_order' => 2,
            ],
            [
                'code'       => 'BEROLAHRAGA',
                'name'       => 'Berolahraga',
                'sort_order' => 3,
            ],
            [
                'code'       => 'MAKAN_SEHAT_BERGIZI',
                'name'       => 'Makan Sehat dan Bergizi',
                'sort_order' => 4,
            ],
            [
                'code'       => 'GEMAR_BELAJAR',
                'name'       => 'Gemar Belajar',
                'sort_order' => 5,
            ],
            [
                'code'       => 'BERMASYARAKAT',
                'name'       => 'Bermasyarakat',
                'sort_order' => 6,
            ],
            [
                'code'       => 'TIDUR_CEPAT',
                'name'       => 'Tidur Cepat',
                'sort_order' => 7,
            ],
        ];

        foreach ($habits as $habit) {
            Habit::query()->updateOrCreate(
                ['code' => $habit['code']],
                [
                    'name'       => $habit['name'],
                    'sort_order' => $habit['sort_order'],
                    'is_active'  => true,
                ]
            );
        }
    }
}