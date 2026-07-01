<?php

namespace Tests\Feature;

use App\Exports\ClassesExport;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminExportTest extends TestCase
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

    public function test_admin_bisa_export_seluruh_data_kelas(): void
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

        $class = ClassRoom::query()->create([
            'name' => 'X-A',
            'grade_level' => 'X',
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        $this->createUser([
            'role_id' => $this->studentRole->id,
            'class_id' => $class->id,
            'full_name' => 'Siswa Satu',
            'username' => 'siswa1',
            'nisn' => '0012345678',
            'email' => 'siswa1@example.com',
        ]);

        Excel::fake();
        Sanctum::actingAs($admin);

        $response = $this->get(
            '/api/v1/admin/exports/classes'
        );

        $response->assertOk();

        Excel::assertDownloaded(
            'data-seluruh-kelas-7kaih-2026-07-01_09-30-00.xlsx',
            function (ClassesExport $export): bool {
                return count($export->sheets()) === 2;
            }
        );
    }

    public function test_admin_bisa_export_satu_kelas_tertentu(): void
    {
        Carbon::setTestNow(
            Carbon::create(
                2026,
                7,
                1,
                10,
                0,
                0,
                'Asia/Jakarta'
            )
        );

        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'full_name' => 'Administrator',
            'username' => 'adminsingle',
            'email' => 'adminsingle@example.com',
        ]);

        $teacher = $this->createUser([
            'role_id' => $this->teacherRole->id,
            'full_name' => 'Ibu Sari',
            'username' => 'ibusarisingle',
            'email' => 'ibusarisingle@example.com',
        ]);

        $class = ClassRoom::query()->create([
            'name' => 'X-A',
            'grade_level' => 'X',
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        Excel::fake();
        Sanctum::actingAs($admin);

        $response = $this->get(
            '/api/v1/admin/exports/classes'.
            '?class_id='.$class->id
        );

        $response->assertOk();

        Excel::assertDownloaded(
            'data-kelas-x-a-7kaih-2026-07-01_10-00-00.xlsx',
            function (ClassesExport $export): bool {
                return count($export->sheets()) === 2;
            }
        );
    }

    public function test_export_kelas_menerima_filter(): void
    {
        Carbon::setTestNow(
            Carbon::create(
                2026,
                7,
                1,
                10,
                30,
                0,
                'Asia/Jakarta'
            )
        );

        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'full_name' => 'Administrator',
            'username' => 'adminfilter',
            'email' => 'adminfilter@example.com',
        ]);

        Excel::fake();
        Sanctum::actingAs($admin);

        $response = $this->get(
            '/api/v1/admin/exports/classes'.
            '?search=X-A'.
            '&grade_level=X'.
            '&is_active=1'
        );

        $response->assertOk();

        Excel::assertDownloaded(
            'data-seluruh-kelas-7kaih-2026-07-01_10-30-00.xlsx',
            function (ClassesExport $export): bool {
                return count($export->sheets()) === 2;
            }
        );
    }

    public function test_siswa_tidak_bisa_export_data_kelas(): void
    {
        $student = $this->createUser([
            'role_id' => $this->studentRole->id,
            'full_name' => 'Siswa Biasa',
            'username' => 'siswabiasa',
            'email' => 'siswabiasa@example.com',
        ]);

        Sanctum::actingAs($student);

        $response = $this->get(
            '/api/v1/admin/exports/classes'
        );

        $response->assertForbidden();
    }

    public function test_export_kelas_menolak_filter_tidak_valid(): void
    {
        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'full_name' => 'Administrator',
            'username' => 'adminvalidation',
            'email' => 'adminvalidation@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/classes'.
            '?is_active=bukan-boolean'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'is_active',
            ]);
    }

    public function test_export_kelas_menolak_class_id_tidak_ditemukan(): void
    {
        $admin = $this->createUser([
            'role_id' => $this->adminRole->id,
            'full_name' => 'Administrator',
            'username' => 'adminclassvalidation',
            'email' => 'adminclassvalidation@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            '/api/v1/admin/exports/classes'.
            '?class_id=999999'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'class_id',
            ]);
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
