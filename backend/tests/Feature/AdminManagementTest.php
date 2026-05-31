<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\StudentParentRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $admin = $this->createUserWithRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/admin/users/{$admin->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'data' => null,
            ]);
    }

    public function test_admin_bisa_membuat_user_guru_tanpa_class_id(): void
    {
        $admin = $this->createUserWithRole('admin');
        $roleGuru = $this->getOrCreateRole('guru');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/users', [
            'role_id' => $roleGuru->id,
            'full_name' => 'Guru Baru Test',
            'username' => 'guru_baru_test',
            'email' => 'guru.baru.test@example.com',
            'password' => 'password123',
            'phone' => '081111111111',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'username' => 'guru_baru_test',
                    'class' => null,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'guru_baru_test',
            'email' => 'guru.baru.test@example.com',
        ]);
    }

    public function test_update_guru_menjadi_siswa_tanpa_class_id_ditolak(): void
    {
        $admin = $this->createUserWithRole('admin');
        $guru = $this->createUserWithRole('guru');
        $roleSiswa = $this->getOrCreateRole('siswa');

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/admin/users/{$guru->id}", [
            'role_id' => $roleSiswa->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'class_id wajib diisi untuk user dengan role siswa.',
                'data' => null,
            ]);
    }

    public function test_update_guru_menjadi_siswa_dengan_class_id_aktif_berhasil(): void
    {
        $admin = $this->createUserWithRole('admin');
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-ADMIN-USER-TEST');
        $targetUser = $this->createUserWithRole('guru');
        $roleSiswa = $this->getOrCreateRole('siswa');

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}", [
            'role_id' => $roleSiswa->id,
            'class_id' => $kelas->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $targetUser->id,
                    'role' => [
                        'name' => 'siswa',
                    ],
                    'class' => [
                        'id' => $kelas->id,
                    ],
                ],
            ]);
    }

    public function test_admin_tidak_bisa_membuat_kelas_dengan_teacher_id_bukan_guru(): void
    {
        $admin = $this->createUserWithRole('admin');
        $siswa = $this->createUserWithRole('siswa');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/classes', [
            'name' => 'X-SALAH-GURU',
            'grade_level' => 'X',
            'teacher_id' => $siswa->id,
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'teacher_id harus merupakan user dengan role guru.',
                'data' => null,
            ]);
    }

    public function test_admin_bisa_membuat_kelas_dengan_teacher_id_guru(): void
    {
        $admin = $this->createUserWithRole('admin');
        $guru = $this->createUserWithRole('guru');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/classes', [
            'name' => 'X-KELAS-ADMIN',
            'grade_level' => 'X',
            'teacher_id' => $guru->id,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'X-KELAS-ADMIN',
                    'teacher' => [
                        'id' => $guru->id,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('classes', [
            'name' => 'X-KELAS-ADMIN',
            'teacher_id' => $guru->id,
        ]);
    }

    public function test_kelas_yang_masih_punya_siswa_tidak_bisa_dihapus(): void
    {
        $admin = $this->createUserWithRole('admin');
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-KELAS-PUNYA-SISWA');

        $this->createUserWithRole('siswa', $kelas->id);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/admin/classes/{$kelas->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa.',
                'data' => null,
            ]);
    }

    public function test_admin_bisa_membuat_relasi_siswa_dan_orang_tua_yang_valid(): void
    {
        $admin = $this->createUserWithRole('admin');
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-RELASI-VALID');

        $siswa = $this->createUserWithRole('siswa', $kelas->id);
        $parent = $this->createUserWithRole('orang_tua');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/student-parent-relations', [
            'student_id' => $siswa->id,
            'parent_id' => $parent->id,
            'relation_type' => 'ayah',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'relation_type' => 'ayah',
                    'student' => [
                        'id' => $siswa->id,
                    ],
                    'parent' => [
                        'id' => $parent->id,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('student_parent_relations', [
            'student_id' => $siswa->id,
            'parent_id' => $parent->id,
            'relation_type' => 'ayah',
            'is_active' => true,
        ]);
    }

    public function test_admin_tidak_bisa_membuat_relasi_jika_student_id_bukan_siswa(): void
    {
        $admin = $this->createUserWithRole('admin');
        $guru = $this->createUserWithRole('guru');
        $parent = $this->createUserWithRole('orang_tua');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/student-parent-relations', [
            'student_id' => $guru->id,
            'parent_id' => $parent->id,
            'relation_type' => 'ayah',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'student_id harus merupakan user dengan role siswa.',
                'data' => null,
            ]);
    }

    public function test_admin_tidak_bisa_membuat_relasi_duplicate(): void
    {
        $admin = $this->createUserWithRole('admin');
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-RELASI-DUPLICATE');

        $siswa = $this->createUserWithRole('siswa', $kelas->id);
        $parent = $this->createUserWithRole('orang_tua');

        StudentParentRelation::query()->create([
            'student_id' => $siswa->id,
            'parent_id' => $parent->id,
            'relation_type' => 'ayah',
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/student-parent-relations', [
            'student_id' => $siswa->id,
            'parent_id' => $parent->id,
            'relation_type' => 'ayah',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Relasi siswa dan orang tua sudah ada.',
                'data' => null,
            ]);
    }

    public function test_admin_bisa_reset_password_user_dan_token_lama_dihapus(): void
    {
        $admin = $this->createUserWithRole('admin');
        $targetUser = $this->createUserWithRole('guru');

        $targetUser->createToken('old_token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $targetUser->id,
            'tokenable_type' => User::class,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/reset-password", [
            'password' => 'passwordbaru123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Password user berhasil direset.',
            ]);

        $this->assertTrue(Hash::check('passwordbaru123', $targetUser->fresh()->password));

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $targetUser->id,
            'tokenable_type' => User::class,
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

        return Role::query()->create([
            'name' => $name,
        ]);
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