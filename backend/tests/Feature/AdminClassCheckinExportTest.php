<?php

namespace Tests\Feature;

use App\Exports\ClassCheckinsExport;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminClassCheckinExportTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $teacherRole;

    private Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::query()->create([
            'name' => 'admin',
        ]);

        $this->teacherRole = Role::query()->create([
            'name' => 'guru',
        ]);

        $this->studentRole = Role::query()->create([
            'name' => 'siswa',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_bisa_export_checkin_kelas_per_periode(): void
    {
        Carbon::setTestNow(
            Carbon::create(
                2026,
                7,
                1,
                9,
                30,
                0,
                'Asia/Jakarta'
            )
        );

        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'full_name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $teacher = $this->createUser([
            'role_id' => $this->teacherRole->id,
            'full_name' => 'Ibu Sari',
            'username' => 'ibusari',
            'email' => 'ibusari@example.com',
        ]);

        $class = $this->createClass($teacher, [
            'name' => 'X-A',
            'grade_level' => 'X',
        ]);

        Excel::fake();
        Sanctum::actingAs($admin);

        $response = $this->get(
            '/api/v1/admin/exports/class-checkins'
            .'?class_id='.$class->id
            .'&start_date=2026-06-01'
            .'&end_date=2026-06-30'
        );

        $response->assertOk();

        Excel::assertDownloaded(
            'rekap-checkin-x-a-2026-06-01-sampai-2026-06-30.xlsx',
            function (ClassCheckinsExport $export): bool {
                return count($export->sheets()) === 2;
            }
        );
    }

    public function test_export_checkin_kelas_mewajibkan_parameter(): void
    {
        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'username' => 'adminrequired',
            'email' => 'adminrequired@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/class-checkins'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'class_id',
                'start_date',
                'end_date',
            ]);
    }

    public function test_export_checkin_kelas_menolak_kelas_tidak_ditemukan(): void
    {
        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'username' => 'adminclass',
            'email' => 'adminclass@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/class-checkins'
            .'?class_id=999999'
            .'&start_date=2026-06-01'
            .'&end_date=2026-06-30'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'class_id',
            ]);
    }

    public function test_export_checkin_kelas_menolak_tanggal_akhir_sebelum_tanggal_awal(): void
    {
        Carbon::setTestNow(
            Carbon::create(
                2026,
                7,
                1,
                9,
                30,
                0,
                'Asia/Jakarta'
            )
        );

        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'username' => 'admindateorder',
            'email' => 'admindateorder@example.com',
        ]);

        $teacher = $this->createUser([
            'role_id' => $this->teacherRole->id,
            'username' => 'teacherdateorder',
            'email' => 'teacherdateorder@example.com',
        ]);

        $class = $this->createClass($teacher);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/class-checkins'
            .'?class_id='.$class->id
            .'&start_date=2026-06-30'
            .'&end_date=2026-06-01'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_date',
            ]);
    }

    public function test_export_checkin_kelas_menolak_tanggal_masa_depan(): void
    {
        Carbon::setTestNow(
            Carbon::create(
                2026,
                7,
                1,
                9,
                30,
                0,
                'Asia/Jakarta'
            )
        );

        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'username' => 'adminfuture',
            'email' => 'adminfuture@example.com',
        ]);

        $teacher = $this->createUser([
            'role_id' => $this->teacherRole->id,
            'username' => 'teacherfuture',
            'email' => 'teacherfuture@example.com',
        ]);

        $class = $this->createClass($teacher);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/class-checkins'
            .'?class_id='.$class->id
            .'&start_date=2026-06-01'
            .'&end_date=2026-07-02'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_date',
            ]);
    }

    public function test_export_checkin_kelas_menolak_periode_lebih_dari_366_hari(): void
    {
        Carbon::setTestNow(
            Carbon::create(
                2026,
                7,
                1,
                9,
                30,
                0,
                'Asia/Jakarta'
            )
        );

        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'username' => 'adminperiod',
            'email' => 'adminperiod@example.com',
        ]);

        $teacher = $this->createUser([
            'role_id' => $this->teacherRole->id,
            'username' => 'teacherperiod',
            'email' => 'teacherperiod@example.com',
        ]);

        $class = $this->createClass($teacher);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/class-checkins'
            .'?class_id='.$class->id
            .'&start_date=2025-06-30'
            .'&end_date=2026-07-01'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_date',
            ]);
    }

    public function test_siswa_tidak_bisa_export_checkin_kelas(): void
    {
        $student = $this->createUser([
            'role_id' => $this->studentRole->id,
            'username' => 'siswabiasa',
            'email' => 'siswabiasa@example.com',
        ]);

        Sanctum::actingAs($student);

        $response = $this->get(
            '/api/v1/admin/exports/class-checkins'
            .'?class_id=1'
            .'&start_date=2026-06-01'
            .'&end_date=2026-06-30'
        );

        $response->assertForbidden();
    }

    private function createClass(
        User $teacher,
        array $attributes = []
    ): ClassRoom {
        return ClassRoom::query()->create(
            array_merge(
                [
                    'name' => 'X-A',
                    'grade_level' => 'X',
                    'teacher_id' => $teacher->id,
                    'is_active' => true,
                ],
                $attributes
            )
        );
    }

    private function createUser(
        array $attributes = []
    ): User {
        return User::query()->create(
            array_merge(
                [
                    'name' => 'User Test',
                    'full_name' => 'User Test',
                    'username' => fake()
                        ->unique()
                        ->userName(),
                    'email' => fake()
                        ->unique()
                        ->safeEmail(),
                    'phone' => null,
                    'password' => 'password123',
                    'role_id' => $this->studentRole->id,
                    'class_id' => null,
                    'is_active' => true,
                ],
                $attributes
            )
        );
    }
}
