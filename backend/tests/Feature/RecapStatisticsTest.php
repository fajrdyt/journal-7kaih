<?php

namespace Tests\Feature;

use App\Models\CheckinItemValidation;
use App\Models\ClassRoom;
use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use App\Models\Habit;
use App\Models\Role;
use App\Models\StudentParentRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecapStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_bisa_melihat_rekap_personal_dengan_hitungan_benar(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-RECAP-1');
        $siswa = $this->createUserWithRole('siswa', $kelas->id);
        $parent = $this->createUserWithRole('orang_tua');

        $habit1 = $this->createHabit('H1', 'Bangun Pagi', 1);
        $habit2 = $this->createHabit('H2', 'Beribadah', 2);

        $checkin = $this->createCheckin($siswa, '2026-05-20', 'Rekap test siswa');

        $itemDone = $this->createCheckinItem($checkin, $habit1, true);
        $this->createCheckinItem($checkin, $habit2, false);

        $this->createValidation($itemDone, $parent, 'orang_tua', 'rumah');
        $this->createValidation($itemDone, $guru, 'guru', 'sekolah');

        Sanctum::actingAs($siswa);

        $response = $this->getJson('/api/v1/student/recap?start_date=2026-05-01&end_date=2026-05-31');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Rekap personal berhasil diambil.',
            ])
            ->assertJsonPath('data.student.id', $siswa->id)
            ->assertJsonPath('data.summary.total_checkins', 1)
            ->assertJsonPath('data.summary.total_items', 2)
            ->assertJsonPath('data.summary.total_done_items', 1)
            ->assertJsonPath('data.summary.total_not_done_items', 1)
            ->assertJsonPath('data.summary.parent_validated_items', 1)
            ->assertJsonPath('data.summary.teacher_validated_items', 1)
            ->assertJsonPath('data.summary.total_validated_items', 2);
    }

    public function test_parent_bisa_melihat_rekap_anak_yang_punya_relasi(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-RECAP-2');
        $siswa = $this->createUserWithRole('siswa', $kelas->id);
        $parent = $this->createUserWithRole('orang_tua');

        $this->createParentRelation($siswa, $parent);

        $habit = $this->createHabit('H1', 'Bangun Pagi', 1);
        $checkin = $this->createCheckin($siswa, '2026-05-20', 'Rekap parent');
        $this->createCheckinItem($checkin, $habit, true);

        Sanctum::actingAs($parent);

        $response = $this->getJson("/api/v1/parent/children/{$siswa->id}/recap?start_date=2026-05-01&end_date=2026-05-31");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Rekap anak berhasil diambil.',
            ])
            ->assertJsonPath('data.student.id', $siswa->id)
            ->assertJsonPath('data.summary.total_checkins', 1);
    }

    public function test_parent_tidak_bisa_melihat_rekap_anak_tanpa_relasi(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-RECAP-3');
        $siswa = $this->createUserWithRole('siswa', $kelas->id);
        $parentTanpaRelasi = $this->createUserWithRole('orang_tua');

        Sanctum::actingAs($parentTanpaRelasi);

        $response = $this->getJson("/api/v1/parent/children/{$siswa->id}/recap?start_date=2026-05-01&end_date=2026-05-31");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'data' => null,
            ]);
    }

    public function test_guru_bisa_melihat_rekap_siswa_di_kelasnya(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-RECAP-4');
        $siswa = $this->createUserWithRole('siswa', $kelas->id);

        $habit = $this->createHabit('H1', 'Bangun Pagi', 1);
        $checkin = $this->createCheckin($siswa, '2026-05-20', 'Rekap guru');
        $this->createCheckinItem($checkin, $habit, true);

        Sanctum::actingAs($guru);

        $response = $this->getJson("/api/v1/teacher/students/{$siswa->id}/recap?start_date=2026-05-01&end_date=2026-05-31");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Rekap siswa berhasil diambil.',
            ])
            ->assertJsonPath('data.student.id', $siswa->id)
            ->assertJsonPath('data.summary.total_checkins', 1);
    }

    public function test_guru_tidak_bisa_melihat_rekap_siswa_kelas_lain(): void
    {
        $guruA = $this->createUserWithRole('guru');
        $guruB = $this->createUserWithRole('guru');

        $kelasA = $this->createClassForTeacher($guruA, 'X-RECAP-5A');
        $kelasB = $this->createClassForTeacher($guruB, 'X-RECAP-5B');

        $siswaKelasB = $this->createUserWithRole('siswa', $kelasB->id);

        Sanctum::actingAs($guruA);

        $response = $this->getJson("/api/v1/teacher/students/{$siswaKelasB->id}/recap?start_date=2026-05-01&end_date=2026-05-31");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'data' => null,
            ]);

        $this->assertNotNull($kelasA);
    }

    public function test_guru_bisa_melihat_rekap_mingguan_kelas_dengan_ringkasan_benar(): void
    {
        $guru = $this->createUserWithRole('guru');
        $kelas = $this->createClassForTeacher($guru, 'X-RECAP-6');

        $siswa1 = $this->createUserWithRole('siswa', $kelas->id);
        $siswa2 = $this->createUserWithRole('siswa', $kelas->id);

        $habit1 = $this->createHabit('H1', 'Bangun Pagi', 1);
        $habit2 = $this->createHabit('H2', 'Beribadah', 2);

        $checkin1 = $this->createCheckin($siswa1, '2026-05-20', 'Siswa 1');
        $this->createCheckinItem($checkin1, $habit1, true);
        $this->createCheckinItem($checkin1, $habit2, false);

        $checkin2 = $this->createCheckin($siswa2, '2026-05-20', 'Siswa 2');
        $this->createCheckinItem($checkin2, $habit1, true);

        Sanctum::actingAs($guru);

        $response = $this->getJson("/api/v1/teacher/classes/{$kelas->id}/weekly-recap?week=2026-05-20");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Rekap mingguan kelas berhasil diambil.',
            ])
            ->assertJsonPath('data.class.id', $kelas->id)
            ->assertJsonPath('data.summary.total_students', 2)
            ->assertJsonPath('data.summary.total_checkins', 2)
            ->assertJsonPath('data.summary.total_done_items', 2);
    }

    public function test_guru_tidak_bisa_melihat_rekap_kelas_guru_lain(): void
    {
        $guruA = $this->createUserWithRole('guru');
        $guruB = $this->createUserWithRole('guru');

        $kelasB = $this->createClassForTeacher($guruB, 'X-RECAP-7');

        Sanctum::actingAs($guruA);

        $response = $this->getJson("/api/v1/teacher/classes/{$kelasB->id}/weekly-recap?week=2026-05-20");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'data' => null,
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

    private function createCheckin(User $student, string $date, ?string $notes = null): DailyCheckin
    {
        return DailyCheckin::query()->create([
            'student_id' => $student->id,
            'checkin_date' => $date,
            'notes' => $notes,
            'submitted_at' => now(),
        ]);
    }

    private function createCheckinItem(DailyCheckin $checkin, Habit $habit, bool $isDone): DailyCheckinItem
    {
        return DailyCheckinItem::query()->create([
            'daily_checkin_id' => $checkin->id,
            'habit_id' => $habit->id,
            'is_done' => $isDone,
        ]);
    }

    private function createValidation(
        DailyCheckinItem $item,
        User $validator,
        string $validatorRole,
        string $validationSource
    ): CheckinItemValidation {
        return CheckinItemValidation::query()->create([
            'daily_checkin_item_id' => $item->id,
            'validator_id' => $validator->id,
            'validator_role' => $validatorRole,
            'validation_source' => $validationSource,
            'validated_at' => now(),
        ]);
    }
}