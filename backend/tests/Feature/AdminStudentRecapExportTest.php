<?php

namespace Tests\Feature;

use App\Exports\StudentRecapExport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminStudentRecapExportTest extends TestCase
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

    public function test_admin_bisa_export_rekap_siswa_per_periode(): void
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

        $student = $this->createUser([
            'role_id' => $this->studentRole->id,
            'full_name' => 'Siswa Demo',
            'username' => 'siswademo',
            'email' => 'siswademo@example.com',
            'nisn' => '0012345678',
        ]);

        Excel::fake();
        Sanctum::actingAs($admin);

        $response = $this->get(
            '/api/v1/admin/exports/student-recap'
            .'?student_id='.$student->id
            .'&start_date=2026-06-01'
            .'&end_date=2026-06-30'
        );

        $response->assertOk();

        Excel::assertDownloaded(
            'rekap-siswa-siswa-demo-2026-06-01-sampai-2026-06-30.xlsx',
            function (StudentRecapExport $export): bool {
                return count($export->sheets()) === 3;
            }
        );
    }

    public function test_export_rekap_siswa_mewajibkan_parameter(): void
    {
        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'username' => 'adminrequired',
            'email' => 'adminrequired@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/student-recap'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'student_id',
                'start_date',
                'end_date',
            ]);
    }

    public function test_export_rekap_siswa_menolak_pengguna_tidak_ditemukan(): void
    {
        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'username' => 'adminnotfound',
            'email' => 'adminnotfound@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/student-recap'
            .'?student_id=999999'
            .'&start_date=2026-06-01'
            .'&end_date=2026-06-30'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'student_id',
            ]);
    }

    public function test_export_rekap_siswa_menolak_pengguna_bukan_siswa(): void
    {
        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'username' => 'adminrole',
            'email' => 'adminrole@example.com',
        ]);

        $teacher = $this->createUser([
            'role_id' => $this->teacherRole->id,
            'full_name' => 'Guru Bukan Siswa',
            'username' => 'gurubukansiswa',
            'email' => 'gurubukansiswa@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/student-recap'
            .'?student_id='.$teacher->id
            .'&start_date=2026-06-01'
            .'&end_date=2026-06-30'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'student_id',
            ]);
    }

    public function test_export_rekap_siswa_menolak_tanggal_akhir_sebelum_tanggal_awal(): void
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

        $student = $this->createUser([
            'role_id' => $this->studentRole->id,
            'username' => 'studentdateorder',
            'email' => 'studentdateorder@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/student-recap'
            .'?student_id='.$student->id
            .'&start_date=2026-06-30'
            .'&end_date=2026-06-01'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_date',
            ]);
    }

    public function test_export_rekap_siswa_menolak_tanggal_masa_depan(): void
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

        $student = $this->createUser([
            'role_id' => $this->studentRole->id,
            'username' => 'studentfuture',
            'email' => 'studentfuture@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/student-recap'
            .'?student_id='.$student->id
            .'&start_date=2026-06-01'
            .'&end_date=2026-07-02'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_date',
            ]);
    }

    public function test_export_rekap_siswa_menolak_periode_lebih_dari_366_hari(): void
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

        $student = $this->createUser([
            'role_id' => $this->studentRole->id,
            'username' => 'studentperiod',
            'email' => 'studentperiod@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/student-recap'
            .'?student_id='.$student->id
            .'&start_date=2025-06-30'
            .'&end_date=2026-07-01'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_date',
            ]);
    }

    public function test_siswa_tidak_bisa_export_rekap_siswa(): void
    {
        $student = $this->createUser([
            'role_id' => $this->studentRole->id,
            'username' => 'siswabiasa',
            'email' => 'siswabiasa@example.com',
        ]);

        Sanctum::actingAs($student);

        $response = $this->get(
            '/api/v1/admin/exports/student-recap'
            .'?student_id='.$student->id
            .'&start_date=2026-06-01'
            .'&end_date=2026-06-30'
        );

        $response->assertForbidden();
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
