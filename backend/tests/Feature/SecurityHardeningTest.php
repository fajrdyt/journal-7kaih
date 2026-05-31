<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanpa_token_tidak_bisa_akses_profile(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthenticated.',
                'data' => null,
            ]);
    }

    public function test_siswa_tidak_bisa_akses_admin_users(): void
    {
        $siswa = $this->createUserWithRole('siswa');

        Sanctum::actingAs($siswa);

        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_guru_tidak_bisa_akses_endpoint_parent(): void
    {
        $guru = $this->createUserWithRole('guru');

        Sanctum::actingAs($guru);

        $response = $this->getJson('/api/v1/parent/children');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_orang_tua_tidak_bisa_akses_endpoint_teacher(): void
    {
        $parent = $this->createUserWithRole('orang_tua');

        Sanctum::actingAs($parent);

        $response = $this->getJson('/api/v1/teacher/classes');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_guru_tidak_bisa_mengakses_kelas_guru_lain(): void
    {
        $guruLama = $this->createUserWithRole('guru');
        $guruLain = $this->createUserWithRole('guru');

        $kelasGuruLain = $this->createClassForTeacher($guruLain, 'X-DUMMY');

        Sanctum::actingAs($guruLama);

        $response = $this->getJson(
            "/api/v1/teacher/classes/{$kelasGuruLain->id}/checkins?date=2026-05-28"
        );

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'data' => null,
            ]);
    }

    public function test_admin_tidak_bisa_membuat_siswa_tanpa_class_id(): void
    {
        $admin = $this->createUserWithRole('admin');
        $roleSiswa = $this->getOrCreateRole('siswa');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/users', [
            'role_id' => $roleSiswa->id,
            'full_name' => 'Siswa Tanpa Kelas',
            'username' => 'siswa_tanpa_kelas',
            'email' => 'siswa.tanpa.kelas@example.com',
            'password' => 'password123',
            'phone' => '081111111111',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'class_id wajib diisi untuk user dengan role siswa.',
                'data' => null,
            ]);
    }

    public function test_admin_bisa_membuat_siswa_dengan_class_id_aktif(): void
    {
        $admin = $this->createUserWithRole('admin');
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-AUTO-TEST');
        $roleSiswa = $this->getOrCreateRole('siswa');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/users', [
            'role_id' => $roleSiswa->id,
            'class_id' => $kelas->id,
            'full_name' => 'Siswa Dengan Kelas',
            'username' => 'siswa_dengan_kelas',
            'email' => 'siswa.dengan.kelas@example.com',
            'password' => 'password123',
            'phone' => '081111111112',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'class' => [
                        'id' => $kelas->id,
                    ],
                ],
            ]);
    }

    public function test_class_id_orang_tua_otomatis_null_saat_dibuat_admin(): void
    {
        $admin = $this->createUserWithRole('admin');
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-PARENT-TEST');
        $roleParent = $this->getOrCreateRole('orang_tua');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/users', [
            'role_id' => $roleParent->id,
            'class_id' => $kelas->id,
            'full_name' => 'Orang Tua Test',
            'username' => 'orang_tua_test',
            'email' => 'orang.tua.test@example.com',
            'password' => 'password123',
            'phone' => '081111111113',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'class' => null,
                ],
            ]);
    }

    public function test_filter_kelas_menolak_per_page_lebih_dari_100(): void
    {
        $admin = $this->createUserWithRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/classes?per_page=200');

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validasi gagal.',
            ]);
    }

    public function test_event_tracking_menolak_event_yang_tidak_diizinkan(): void
    {
        $admin = $this->createUserWithRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/events/track', [
            'event_name' => 'event_ngasal',
            'properties' => [
                'page' => 'test',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validasi gagal.',
            ]);
    }

    public function test_event_tracking_menerima_event_yang_diizinkan(): void
    {
        $admin = $this->createUserWithRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/events/track', [
            'event_name' => 'dashboard_open',
            'properties' => [
                'page' => 'admin_dashboard',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Event berhasil dicatat.',
            ]);

        $this->assertDatabaseHas('analytics_events', [
            'user_id' => $admin->id,
            'event_name' => 'dashboard_open',
        ]);
    }

    private function getOrCreateRole(string $name): Role
    {
        $role = Role::query()
            ->where('name', $name)
            ->first();

        if ($role) {
            return $role;
        }

        $role = new Role();
        $role->name = $name;
        $role->save();

        return $role;
    }

    private function createUserWithRole(string $roleName, ?int $classId = null): User
    {
        $role = $this->getOrCreateRole($roleName);
        $unique = str_replace('.', '', uniqid('', true));

        return User::query()->create([
            'role_id' => $role->id,
            'class_id' => $classId,
            'name' => "User {$roleName} {$unique}",
            'full_name' => "User {$roleName} {$unique}",
            'username' => "{$roleName}_{$unique}",
            'email' => "{$roleName}_{$unique}@example.com",
            'phone' => '081234567890',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
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