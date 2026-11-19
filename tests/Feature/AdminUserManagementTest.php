<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_dapat_mengakses_seluruh_halaman_manajemen_pengguna(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = User::factory()->mahasiswa()->create(['nim' => '11111111']);
        $dosen = User::factory()->dosen()->create();

        $this->actingAs($admin)->get(route('admin.pengguna.index'))->assertOk()->assertViewIs('admin.pengguna.index');
        $this->actingAs($admin)->get(route('admin.pengguna.create'))->assertOk()->assertViewIs('admin.pengguna.create');
        $this->actingAs($admin)->get(route('admin.pengguna.show', $mahasiswa))->assertOk()->assertViewIs('admin.pengguna.show');
        $this->actingAs($admin)->get(route('admin.pengguna.edit', $mahasiswa))->assertOk()->assertViewIs('admin.pengguna.edit');

        $this->actingAs($admin)->get(route('admin.mahasiswa.index'))->assertOk()->assertViewIs('admin.mahasiswa.index');
        $this->actingAs($admin)->get(route('admin.mahasiswa.create'))->assertOk()->assertViewIs('admin.mahasiswa.create');
        $this->actingAs($admin)->get(route('admin.mahasiswa.show', $mahasiswa))->assertOk();
        $this->actingAs($admin)->get(route('admin.mahasiswa.edit', $mahasiswa))->assertOk();

        $this->actingAs($admin)->get(route('admin.dosen.index'))->assertOk()->assertViewIs('admin.dosen.index');
        $this->actingAs($admin)->get(route('admin.dosen.create'))->assertOk()->assertViewIs('admin.dosen.create');
        $this->actingAs($admin)->get(route('admin.dosen.show', $dosen))->assertOk();
        $this->actingAs($admin)->get(route('admin.dosen.edit', $dosen))->assertOk();
    }

    public function test_guest_dan_role_lain_ditolak_dari_manajemen_pengguna(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $routes = [
            ['get', route('admin.pengguna.index')],
            ['get', route('admin.pengguna.create')],
            ['post', route('admin.pengguna.store')],
            ['get', route('admin.pengguna.show', $mahasiswa)],
            ['get', route('admin.pengguna.edit', $mahasiswa)],
            ['put', route('admin.pengguna.update', $mahasiswa)],
            ['post', route('admin.pengguna.status', $mahasiswa)],
            ['get', route('admin.mahasiswa.index')],
            ['get', route('admin.dosen.index')],
        ];

        foreach ($routes as [$method, $url]) {
            $this->{$method}($url)->assertRedirect(route('login'));
        }

        foreach ([User::factory()->mahasiswa(), User::factory()->dosen()] as $factory) {
            $user = $factory->create();

            foreach ($routes as [$method, $url]) {
                $this->actingAs($user)->{$method}($url)->assertForbidden();
            }
        }
    }

    public function test_pencarian_filter_role_dan_status_berjalan(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->mahasiswa()->create(['name' => 'Andi Unik', 'nim' => '22222222']);
        User::factory()->mahasiswa()->create(['name' => 'Budi Biasa', 'nim' => '33333333', 'is_active' => false]);
        User::factory()->dosen()->create(['name' => 'Sari Unik']);

        $pencarian = $this->actingAs($admin)->get(route('admin.pengguna.index', ['q' => 'Unik']));
        $pencarian->assertOk()->assertSee('Andi Unik')->assertSee('Sari Unik')->assertDontSee('Budi Biasa');
        $this->assertSame(2, $pencarian->viewData('pengguna')->total());

        $berdasarkanRole = $this->actingAs($admin)->get(route('admin.pengguna.index', ['role' => 'dosen']));
        $berdasarkanRole->assertOk()->assertSee('Sari Unik')->assertDontSee('Andi Unik');
        $this->assertSame(1, $berdasarkanRole->viewData('pengguna')->total());

        $berdasarkanStatus = $this->actingAs($admin)->get(route('admin.pengguna.index', ['status' => 'nonaktif']));
        $berdasarkanStatus->assertOk()->assertSee('Budi Biasa')->assertDontSee('Andi Unik');
        $this->assertSame(1, $berdasarkanStatus->viewData('pengguna')->total());
    }

    public function test_daftar_pengguna_menggunakan_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(12)->mahasiswa()->create();

        $response = $this->actingAs($admin)->get(route('admin.pengguna.index'));

        $response->assertOk();
        $this->assertSame(10, $response->viewData('pengguna')->perPage());
        $this->assertSame(13, $response->viewData('pengguna')->total());
    }

    public function test_admin_membuat_akun_mahasiswa_dengan_password_hash_dan_profil_konsisten(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.pengguna.store'), $this->dataMahasiswa());

        $response->assertRedirect(route('admin.pengguna.index'))->assertSessionHas('status');

        $akun = User::query()->where('email', 'mahasiswa.baru@example.com')->firstOrFail();
        $this->assertSame(UserRole::Mahasiswa, $akun->role);
        $this->assertTrue($akun->is_active);
        $this->assertSame('12345678', $akun->nim);
        $this->assertSame(2026, $akun->angkatan);
        $this->assertSame('Informatika', $akun->program_studi);
        $this->assertNull($akun->nidn);
        $this->assertTrue(Hash::check('rahasia123', $akun->password));
        $this->assertNotSame('rahasia123', $akun->password);
    }

    public function test_halaman_mahasiswa_memaksa_role_dan_menolak_role_dari_request(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->dataMahasiswa([
            'email' => 'paksa.mahasiswa@example.com',
            'nim' => '44444444',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->post(route('admin.mahasiswa.store'), $data)
            ->assertRedirect(route('admin.mahasiswa.index'));

        $akun = User::query()->where('email', 'paksa.mahasiswa@example.com')->firstOrFail();
        $this->assertSame(UserRole::Mahasiswa, $akun->role);
        $this->assertSame('44444444', $akun->nim);
        $this->assertNull($akun->nidn);
    }

    public function test_halaman_dosen_memaksa_role_dan_menolak_role_dari_request(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.dosen.store'), [
            'name' => 'Dosen Baru',
            'email' => 'dosen.baru@example.com',
            'role' => 'admin',
            'nim' => '',
            'nidn' => '1987654321',
            'angkatan' => '',
            'program_studi' => 'Informatika',
            'no_telepon' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('admin.dosen.index'));

        $akun = User::query()->where('email', 'dosen.baru@example.com')->firstOrFail();
        $this->assertSame(UserRole::Dosen, $akun->role);
        $this->assertSame('1987654321', $akun->nidn);
        $this->assertNull($akun->nim);
        $this->assertNull($akun->angkatan);
    }

    public function test_email_duplikat_dan_data_wajib_divalidasi(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->mahasiswa()->create(['email' => 'sudah.ada@example.com']);

        $this->actingAs($admin)->post(route('admin.pengguna.store'), $this->dataMahasiswa([
            'email' => $existing->email,
        ]))->assertSessionHasErrors('email');

        $this->actingAs($admin)->post(route('admin.pengguna.store'), $this->dataMahasiswa([
            'email' => 'tanpa.nim@example.com',
            'nim' => '',
        ]))->assertSessionHasErrors('nim');

        $this->actingAs($admin)->post(route('admin.pengguna.store'), $this->dataMahasiswa([
            'email' => 'password.kosong@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]))->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'tanpa.nim@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'password.kosong@example.com']);
    }

    public function test_admin_mengubah_data_tanpa_mengubah_role_status_atau_password(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Lama',
            'email' => 'mahasiswa.lama@example.com',
            'nim' => '55555555',
            'angkatan' => 2024,
        ]);
        $hashLama = $mahasiswa->password;

        $this->actingAs($admin)->put(route('admin.pengguna.update', $mahasiswa), [
            'name' => 'Mahasiswa Baru',
            'email' => 'mahasiswa.baru2@example.com',
            'nim' => '55555555',
            'angkatan' => '2024',
            'program_studi' => 'Sistem Informasi',
            'no_telepon' => '081111111111',
            'role' => 'admin',
            'is_active' => 0,
            'password' => 'password-baru',
        ])->assertRedirect(route('admin.pengguna.index'));

        $mahasiswa->refresh();
        $this->assertSame('Mahasiswa Baru', $mahasiswa->name);
        $this->assertSame('mahasiswa.baru2@example.com', $mahasiswa->email);
        $this->assertSame('Sistem Informasi', $mahasiswa->program_studi);
        $this->assertSame(UserRole::Mahasiswa, $mahasiswa->role);
        $this->assertTrue($mahasiswa->is_active);
        $this->assertSame($hashLama, $mahasiswa->password);
    }

    public function test_admin_tidak_dapat_mengubah_data_admin_lain(): void
    {
        $admin = User::factory()->admin()->create();
        $adminLain = User::factory()->admin()->create(['name' => 'Admin Lain']);

        $this->actingAs($admin)->get(route('admin.pengguna.edit', $adminLain))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.pengguna.update', $adminLain), [
            'name' => 'Admin Diubah',
            'email' => $adminLain->email,
        ])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $adminLain->id, 'name' => 'Admin Lain']);
    }

    public function test_admin_tidak_dapat_menonaktifkan_akun_sendiri_atau_admin_lain(): void
    {
        $admin = User::factory()->admin()->create();
        $adminLain = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.pengguna.status', $admin), ['aktif' => 0])
            ->assertForbidden();
        $this->actingAs($admin)->post(route('admin.pengguna.status', $adminLain), ['aktif' => 0])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
        $this->assertDatabaseHas('users', ['id' => $adminLain->id, 'is_active' => true]);
    }

    public function test_penonaktifan_tidak_menghapus_data_dan_mencegah_akses(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = User::factory()->mahasiswa()->create(['nim' => '66666666']);

        $this->actingAs($admin)->post(route('admin.mahasiswa.status', $mahasiswa), ['aktif' => 0])
            ->assertRedirect(route('admin.mahasiswa.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $mahasiswa->id,
            'nim' => '66666666',
            'is_active' => false,
        ]);

        $mahasiswa->refresh();

        $this->actingAs($mahasiswa)->get(route('mahasiswa.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($admin)->post(route('admin.mahasiswa.status', $mahasiswa), ['aktif' => 1])
            ->assertRedirect(route('admin.mahasiswa.index'));

        $this->assertDatabaseHas('users', ['id' => $mahasiswa->id, 'is_active' => true]);
    }

    public function test_halaman_role_tetap_tidak_bisa_mengakses_akun_role_lain(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = User::factory()->mahasiswa()->create();
        $dosen = User::factory()->dosen()->create();

        $this->actingAs($admin)->get(route('admin.mahasiswa.show', $dosen))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.dosen.show', $mahasiswa))->assertNotFound();
        $this->actingAs($admin)->put(route('admin.mahasiswa.update', $dosen), [
            'name' => 'Dosen Diubah',
            'email' => $dosen->email,
        ])->assertNotFound();
        $this->actingAs($admin)->post(route('admin.dosen.status', $mahasiswa), ['aktif' => 0])->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $dosen->id, 'name' => $dosen->name, 'is_active' => true]);
        $this->assertDatabaseHas('users', ['id' => $mahasiswa->id, 'is_active' => true]);
    }

    public function test_detail_pengguna_menampilkan_profil_tanpa_mengekspos_password(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = User::factory()->dosen()->create([
            'name' => 'Dosen Detail',
            'nidn' => '1999000001',
            'program_studi' => 'Matematika',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dosen.show', $dosen));

        $response->assertOk()
            ->assertSee('Dosen Detail')
            ->assertSee('1999000001')
            ->assertSee('Matematika');

        $this->assertArrayNotHasKey('password', $response->viewData('detail'));
        $this->assertArrayNotHasKey('remember_token', $response->viewData('detail'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function dataMahasiswa(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mahasiswa Baru',
            'email' => 'mahasiswa.baru@example.com',
            'role' => 'mahasiswa',
            'nim' => '12345678',
            'angkatan' => '2026',
            'program_studi' => 'Informatika',
            'no_telepon' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }
}
