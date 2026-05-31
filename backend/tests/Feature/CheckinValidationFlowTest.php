<?php

namespace Tests\Feature;

use App\Models\CheckinItemValidation;
use App\Models\ClassRoom;
use App\Models\Habit;
use App\Models\Role;
use App\Models\StudentParentRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckinValidationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_bisa_submit_checkin_dan_submitted_at_terisi(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-CHECKIN-1');
        $siswa = $this->createUserWithRole('siswa', $kelas->id);

        $habit1 = $this->createHabit('H1', 'Bangun Pagi', 1);
        $habit2 = $this->createHabit('H2', 'Beribadah', 2);

        Sanctum::actingAs($siswa);

        $response = $this->postJson('/api/v1/student/checkins', [
            'notes' => 'Hari ini semangat',
            'items' => [
                [
                    'habit_id' => $habit1->id,
                    'is_done' => true,
                ],
                [
                    'habit_id' => $habit2->id,
                    'is_done' => false,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Check-in berhasil disimpan.',
            ])
            ->assertJsonPath('data.notes', 'Hari ini semangat');

        $this->assertNotNull($response->json('data.submitted_at'));

        $this->assertDatabaseHas('daily_checkins', [
            'student_id' => $siswa->id,
            'notes' => 'Hari ini semangat',
        ]);

        $this->assertDatabaseHas('daily_checkin_items', [
            'habit_id' => $habit1->id,
            'is_done' => true,
        ]);

        $this->assertDatabaseHas('daily_checkin_items', [
            'habit_id' => $habit2->id,
            'is_done' => false,
        ]);
    }

    public function test_parent_hanya_bisa_validasi_anak_yang_punya_relasi(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-CHECKIN-2');

        $siswa = $this->createUserWithRole('siswa', $kelas->id);
        $parentTanpaRelasi = $this->createUserWithRole('orang_tua');

        $habit = $this->createHabit('H1', 'Bangun Pagi', 1);

        $checkinId = $this->submitCheckinAndGetId($siswa, [
            [
                'habit_id' => $habit->id,
                'is_done' => true,
            ],
        ]);

        Sanctum::actingAs($parentTanpaRelasi);

        $response = $this->postJson("/api/v1/parent/checkins/{$checkinId}/validate-home");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'data' => null,
            ]);
    }

    public function test_parent_batch_validation_hanya_memvalidasi_item_yang_dipilih(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-CHECKIN-3');

        $siswa = $this->createUserWithRole('siswa', $kelas->id);
        $parent = $this->createUserWithRole('orang_tua');

        $this->createParentRelation($siswa, $parent);

        $habit1 = $this->createHabit('H1', 'Bangun Pagi', 1);
        $habit2 = $this->createHabit('H2', 'Beribadah', 2);

        $checkinResponse = $this->submitCheckin($siswa, [
            [
                'habit_id' => $habit1->id,
                'is_done' => true,
            ],
            [
                'habit_id' => $habit2->id,
                'is_done' => true,
            ],
        ]);

        $checkinId = $checkinResponse->json('data.id');
        $firstItemId = $checkinResponse->json('data.items.0.id');
        $secondItemId = $checkinResponse->json('data.items.1.id');

        Sanctum::actingAs($parent);

        $response = $this->postJson("/api/v1/parent/checkins/{$checkinId}/validate-home", [
            'item_ids' => [$firstItemId],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('data.summary.requested_count', 1)
            ->assertJsonPath('data.summary.validated_count', 1);

        $this->assertDatabaseHas('checkin_item_validations', [
            'daily_checkin_item_id' => $firstItemId,
            'validator_id' => $parent->id,
            'validator_role' => 'orang_tua',
            'validation_source' => 'rumah',
        ]);

        $this->assertDatabaseMissing('checkin_item_validations', [
            'daily_checkin_item_id' => $secondItemId,
            'validator_id' => $parent->id,
            'validator_role' => 'orang_tua',
        ]);
    }

    public function test_item_yang_belum_dilakukan_tidak_bisa_divalidasi_parent(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-CHECKIN-4');

        $siswa = $this->createUserWithRole('siswa', $kelas->id);
        $parent = $this->createUserWithRole('orang_tua');

        $this->createParentRelation($siswa, $parent);

        $habit = $this->createHabit('H1', 'Bangun Pagi', 1);

        $checkinResponse = $this->submitCheckin($siswa, [
            [
                'habit_id' => $habit->id,
                'is_done' => false,
            ],
        ]);

        $itemId = $checkinResponse->json('data.items.0.id');

        Sanctum::actingAs($parent);

        $response = $this->postJson("/api/v1/parent/checkin-items/{$itemId}/validate");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Item yang belum dilakukan tidak dapat divalidasi.',
                'data' => null,
            ]);
    }

    public function test_guru_hanya_bisa_validasi_siswa_di_kelasnya(): void
    {
        $guruPemilik = $this->createUserWithRole('guru');
        $guruLain = $this->createUserWithRole('guru');

        $kelasPemilik = $this->createClassForTeacher($guruPemilik, 'X-CHECKIN-5');
        $kelasLain = $this->createClassForTeacher($guruLain, 'X-CHECKIN-6');

        $siswaKelasLain = $this->createUserWithRole('siswa', $kelasLain->id);

        $habit = $this->createHabit('H1', 'Bangun Pagi', 1);

        $checkinId = $this->submitCheckinAndGetId($siswaKelasLain, [
            [
                'habit_id' => $habit->id,
                'is_done' => true,
            ],
        ]);

        Sanctum::actingAs($guruPemilik);

        $response = $this->postJson("/api/v1/teacher/checkins/{$checkinId}/validate-school");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'data' => null,
            ]);

        $this->assertNotNull($kelasPemilik);
    }

    public function test_guru_batch_validation_hanya_memvalidasi_item_yang_dipilih(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-CHECKIN-7');

        $siswa = $this->createUserWithRole('siswa', $kelas->id);

        $habit1 = $this->createHabit('H1', 'Bangun Pagi', 1);
        $habit2 = $this->createHabit('H2', 'Beribadah', 2);

        $checkinResponse = $this->submitCheckin($siswa, [
            [
                'habit_id' => $habit1->id,
                'is_done' => true,
            ],
            [
                'habit_id' => $habit2->id,
                'is_done' => true,
            ],
        ]);

        $checkinId = $checkinResponse->json('data.id');
        $firstItemId = $checkinResponse->json('data.items.0.id');
        $secondItemId = $checkinResponse->json('data.items.1.id');

        Sanctum::actingAs($guru);

        $response = $this->postJson("/api/v1/teacher/checkins/{$checkinId}/validate-school", [
            'item_ids' => [$firstItemId],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('data.summary.requested_count', 1)
            ->assertJsonPath('data.summary.validated_count', 1);

        $this->assertDatabaseHas('checkin_item_validations', [
            'daily_checkin_item_id' => $firstItemId,
            'validator_id' => $guru->id,
            'validator_role' => 'guru',
            'validation_source' => 'sekolah',
        ]);

        $this->assertDatabaseMissing('checkin_item_validations', [
            'daily_checkin_item_id' => $secondItemId,
            'validator_id' => $guru->id,
            'validator_role' => 'guru',
        ]);
    }

    public function test_validasi_lama_dihapus_saat_siswa_mengubah_kebiasaan_menjadi_false(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-CHECKIN-8');

        $siswa = $this->createUserWithRole('siswa', $kelas->id);
        $parent = $this->createUserWithRole('orang_tua');

        $this->createParentRelation($siswa, $parent);

        $habit = $this->createHabit('H1', 'Bangun Pagi', 1);

        $checkinResponse = $this->submitCheckin($siswa, [
            [
                'habit_id' => $habit->id,
                'is_done' => true,
            ],
        ]);

        $checkinId = $checkinResponse->json('data.id');
        $itemId = $checkinResponse->json('data.items.0.id');

        Sanctum::actingAs($parent);

        $this->postJson("/api/v1/parent/checkins/{$checkinId}/validate-home", [
            'item_ids' => [$itemId],
        ])->assertStatus(200);

        $this->assertDatabaseHas('checkin_item_validations', [
            'daily_checkin_item_id' => $itemId,
            'validator_role' => 'orang_tua',
        ]);

        Sanctum::actingAs($siswa);

        $response = $this->postJson('/api/v1/student/checkins', [
            'notes' => 'Mengubah kebiasaan menjadi belum dilakukan',
            'items' => [
                [
                    'habit_id' => $habit->id,
                    'is_done' => false,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.is_done', false);

        $this->assertDatabaseMissing('checkin_item_validations', [
            'daily_checkin_item_id' => $itemId,
            'validator_role' => 'orang_tua',
        ]);
    }

    private function submitCheckin(User $student, array $items)
    {
        Sanctum::actingAs($student);

        return $this->postJson('/api/v1/student/checkins', [
            'notes' => 'Check-in automated test',
            'items' => $items,
        ])->assertStatus(200);
    }

    private function submitCheckinAndGetId(User $student, array $items): int
    {
        return (int) $this->submitCheckin($student, $items)->json('data.id');
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

    private function createHabit(string $code, string $name, int $sortOrder): Habit
    {
        return Habit::query()->create([
            'code' => $code,
            'name' => $name,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function createParentRelation(User $student, User $parent): StudentParentRelation
    {
        return StudentParentRelation::query()->create([
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'relation_type' => 'ayah',
            'is_active' => true,
        ]);
    }
}