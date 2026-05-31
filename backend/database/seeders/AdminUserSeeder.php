<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()
            ->where('name', 'admin')
            ->first();

        if (!$adminRole) {
            return;
        }

        User::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'role_id'   => $adminRole->id,
                'class_id'  => null,
                'name'      => 'Administrator',
                'full_name' => 'Administrator',
                'email'     => 'admin@7kaih.local',
                'phone'     => null,
                'password'  => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'password123')),
                'is_active' => true,
            ]
        );
    }
}