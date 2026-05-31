<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'siswa',
            'guru',
            'orang_tua',
            'admin',
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['name' => $role],
                ['name' => $role]
            );
        }
    }
}