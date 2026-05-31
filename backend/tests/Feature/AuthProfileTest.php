<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class AuthProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_bisa_login_dengan_username(): void
    {
        $user = $this->createUserWithRole('admin', null, [
            'username' => 'admin_login_test',
            'email' => 'admin.login.test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin_login_test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Login berhasil.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'user',
                ],
            ])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_user_bisa_login_dengan_email(): void
    {
        $user = $this->createUserWithRole('guru', null, [
            'username' => 'guru_login_email',
            'email' => 'guru.login.email@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'guru.login.email@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Login berhasil.',
            ])
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_login_ditolak_jika_password_salah(): void
    {
        $this->createUserWithRole('admin', null, [
            'username' => 'admin_password_salah',
            'email' => 'admin.password.salah@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin_password_salah',
            'password' => 'password_salah',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Password salah.',
                'data' => null,
            ]);
    }

    public function test_login_ditolak_jika_user_tidak_aktif(): void
    {
        $this->createUserWithRole('admin', null, [
            'username' => 'admin_nonaktif',
            'email' => 'admin.nonaktif@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin_nonaktif',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Akun tidak aktif.',
                'data' => null,
            ]);
    }

    public function test_auth_me_mengembalikan_user_login(): void
    {
        $user = $this->createUserWithRole('admin');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data user berhasil diambil.',
            ])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.username', $user->username);
    }

    public function test_logout_menghapus_token_yang_sedang_dipakai(): void
    {
    $user = $this->createUserWithRole('admin', null, [
        'username' => 'admin_logout_test',
        'email' => 'admin.logout.test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'identifier' => 'admin_logout_test',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(200);

    $token = $loginResponse->json('data.access_token');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
    ]);

    $logoutResponse = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout');

    $logoutResponse->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Logout berhasil.',
            'data' => null,
        ]);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
    ]);

    Auth::forgetGuards();

    $profileResponse = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/profile');

    $profileResponse->assertStatus(401)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unauthenticated.',
            'data' => null,
        ]);
    }

    public function test_user_bisa_mengambil_profile(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-PROFILE-TEST');
        $siswa = $this->createUserWithRole('siswa', $kelas->id);

        Sanctum::actingAs($siswa);

        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Profil berhasil diambil.',
            ])
            ->assertJsonPath('data.id', $siswa->id)
            ->assertJsonPath('data.class.id', $kelas->id)
            ->assertJsonPath('data.role.name', 'siswa');
    }

    public function test_user_bisa_update_profile(): void
    {
        $user = $this->createUserWithRole('admin');

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', [
            'full_name' => 'Nama Baru Profile',
            'username' => 'username_baru_profile',
            'email' => 'profile.baru@example.com',
            'phone' => '089999999999',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Profil berhasil diperbarui.',
            ])
            ->assertJsonPath('data.full_name', 'Nama Baru Profile')
            ->assertJsonPath('data.username', 'username_baru_profile')
            ->assertJsonPath('data.email', 'profile.baru@example.com')
            ->assertJsonPath('data.phone', '089999999999');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'full_name' => 'Nama Baru Profile',
            'name' => 'Nama Baru Profile',
            'username' => 'username_baru_profile',
            'email' => 'profile.baru@example.com',
            'phone' => '089999999999',
        ]);
    }

    public function test_update_profile_menolak_username_yang_sudah_dipakai(): void
    {
        $user = $this->createUserWithRole('admin');
        $userLain = $this->createUserWithRole('guru', null, [
            'username' => 'username_sudah_dipakai',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', [
            'username' => $userLain->username,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validasi gagal.',
            ]);
    }

    public function test_update_password_ditolak_jika_password_lama_salah(): void
    {
        $user = $this->createUserWithRole('admin', null, [
            'password' => Hash::make('password123'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'password_salah',
            'new_password' => 'passwordbaru123',
            'new_password_confirmation' => 'passwordbaru123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Password lama tidak sesuai.',
                'data' => null,
            ]);
    }

    public function test_user_bisa_update_password_dengan_password_lama_yang_benar(): void
    {
        $user = $this->createUserWithRole('admin', null, [
            'password' => Hash::make('password123'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'password123',
            'new_password' => 'passwordbaru123',
            'new_password_confirmation' => 'passwordbaru123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Password berhasil diperbarui.',
                'data' => null,
            ]);

        $this->assertTrue(Hash::check('passwordbaru123', $user->fresh()->password));
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

    private function createUserWithRole(string $roleName, ?int $classId = null, array $overrides = []): User
    {
        $role = $this->getOrCreateRole($roleName);
        $unique = str_replace('.', '', uniqid('', true));

        return User::query()->create(array_merge([
            'role_id' => $role->id,
            'class_id' => $classId,
            'name' => "User {$roleName} {$unique}",
            'full_name' => "User {$roleName} {$unique}",
            'username' => "{$roleName}_{$unique}",
            'email' => "{$roleName}_{$unique}@example.com",
            'phone' => '081234567890',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ], $overrides));
    }

    private function createClassForTeacher(User $teacher, string $name): ClassRoom
    {
        return ClassRoom::query()->create([
            'name' => $name,
            'grade_level' => 'X',
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);
    }
}