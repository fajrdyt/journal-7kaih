<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_dibatasi_maksimal_10_request_per_menit(): void
    {
        $this->createUserWithRole('admin', [
            'username' => 'admin_rate_limit',
            'email' => 'admin.rate.limit@example.com',
            'password' => Hash::make('password123'),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'identifier' => 'admin_rate_limit',
                'password' => 'password_salah',
            ]);

            $response->assertStatus(401);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin_rate_limit',
            'password' => 'password_salah',
        ]);

        $response->assertStatus(429);
    }

    public function test_event_tracking_dibatasi_maksimal_30_request_per_menit(): void
    {
        $admin = $this->createUserWithRole('admin');

        Sanctum::actingAs($admin);

        for ($i = 1; $i <= 30; $i++) {
            $response = $this->postJson('/api/v1/events/track', [
                'event_name' => 'dashboard_open',
                'properties' => [
                    'index' => $i,
                ],
            ]);

            $response->assertStatus(201);
        }

        $response = $this->postJson('/api/v1/events/track', [
            'event_name' => 'dashboard_open',
            'properties' => [
                'index' => 31,
            ],
        ]);

        $response->assertStatus(429);
    }

    private function getOrCreateRole(string $name): Role
    {
        $role = Role::query()
            ->where('name', $name)
            ->first();

        if ($role) {
            return $role;
        }

        return Role::query()->create([
            'name' => $name,
        ]);
    }

    private function createUserWithRole(string $roleName, array $overrides = []): User
    {
        $role = $this->getOrCreateRole($roleName);
        $unique = str_replace('.', '', uniqid('', true));

        return User::query()->create(array_merge([
            'role_id' => $role->id,
            'class_id' => null,
            'name' => "User {$roleName} {$unique}",
            'full_name' => "User {$roleName} {$unique}",
            'username' => "{$roleName}_{$unique}",
            'email' => "{$roleName}_{$unique}@example.com",
            'phone' => '081234567890',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ], $overrides));
    }
}