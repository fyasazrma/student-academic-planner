<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_mahasiswa_dapat_melihat_dan_mengubah_profil_sendiri(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Profil',
            'email' => 'mahasiswa.profil@example.com',
            'nim' => '77777777',
            'angkatan' => 2025,
            'program_studi' => 'Informatika',
        ]);

        $show = $this->actingAs($mahasiswa)->get(route('mahasiswa.profil'));
        $show->assertOk()
            ->assertViewIs('mahasiswa.profil.show')
            ->assertSee('Mahasiswa Profil')
            ->assertSee('77777777')
            ->assertSee('Informatika');

        $edit = $this->actingAs($mahasiswa)->get(route('mahasiswa.profil.edit'));
        $edit->assertOk()->assertViewIs('mahasiswa.profil.edit');

        $this->actingAs($mahasiswa)->put(route('mahasiswa.profil.update'), [
            'name' => 'Mahasiswa Diubah',
            'email' => 'mahasiswa.diubah@example.com',
            'no_telepon' => '081222222222',
        ])->assertRedirect(route('mahasiswa.profil'))->assertSessionHas('status');

        $mahasiswa->refresh();
        $this->assertSame('Mahasiswa Diubah', $mahasiswa->name);
        $this->assertSame('mahasiswa.diubah@example.com', $mahasiswa->email);
        $this->assertSame('081222222222', $mahasiswa->no_telepon);
    }

    public function test_dosen_dapat_melihat_dan_mengubah_profil_sendiri(): void
    {
        $dosen = User::factory()->dosen()->create([
            'name' => 'Dosen Profil',
            'email' => 'dosen.profil@example.com',
            'nidn' => '1988000001',
            'program_studi' => 'Matematika',
        ]);

        $show = $this->actingAs($dosen)->get(route('dosen.profil'));
        $show->assertOk()
            ->assertViewIs('dosen.profil.show')
            ->assertSee('Dosen Profil')
            ->assertSee('1988000001')
            ->assertSee('Matematika');

        $this->actingAs($dosen)->get(route('dosen.profil.edit'))->assertOk()->assertViewIs('dosen.profil.edit');

        $this->actingAs($dosen)->put(route('dosen.profil.update'), [
            'name' => 'Dosen Diubah',
            'email' => 'dosen.diubah@example.com',
            'no_telepon' => '081333333333',
        ])->assertRedirect(route('dosen.profil'));

        $dosen->refresh();
        $this->assertSame('Dosen Diubah', $dosen->name);
        $this->assertSame('081333333333', $dosen->no_telepon);
    }

    public function test_akses_profil_dibatasi_berdasarkan_role_dan_akun_login(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $dosen = User::factory()->dosen()->create();
        $mahasiswaLain = User::factory()->mahasiswa()->create();

        $this->get(route('mahasiswa.profil'))->assertRedirect(route('login'));
        $this->get(route('dosen.profil'))->assertRedirect(route('login'));

        $this->actingAs($mahasiswa)->get(route('dosen.profil'))->assertForbidden();
        $this->actingAs($dosen)->get(route('mahasiswa.profil'))->assertForbidden();
        $this->actingAs($mahasiswa)->get('/mahasiswa/profil/'.$mahasiswaLain->getKey())->assertNotFound();
        $this->actingAs($dosen)->get('/dosen/profil/'.$mahasiswaLain->getKey())->assertNotFound();
    }

    public function test_mahasiswa_tidak_dapat_mengubah_role_nim_status_atau_akun_lain(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create([
            'nim' => '88888888',
            'angkatan' => 2023,
            'role' => UserRole::Mahasiswa,
        ]);
        $lain = User::factory()->mahasiswa()->create(['email' => 'mahasiswa.lain@example.com']);

        $this->actingAs($mahasiswa)->put(route('mahasiswa.profil.update'), [
            'name' => 'Profil Aman',
            'email' => $mahasiswa->email,
            'no_telepon' => '081444444444',
            'role' => 'admin',
            'nim' => '99999999',
            'angkatan' => 2099,
            'is_active' => 0,
            'id' => $lain->getKey(),
            'user_id' => $lain->getKey(),
        ])->assertRedirect(route('mahasiswa.profil'));

        $mahasiswa->refresh();
        $this->assertSame(UserRole::Mahasiswa, $mahasiswa->role);
        $this->assertSame('88888888', $mahasiswa->nim);
        $this->assertSame(2023, $mahasiswa->angkatan);
        $this->assertTrue($mahasiswa->is_active);
        $this->assertDatabaseHas('users', ['id' => $lain->id, 'email' => 'mahasiswa.lain@example.com']);
    }

    public function test_dosen_tidak_dapat_mengubah_identitas_atau_penugasan_akun_lain(): void
    {
        $dosen = User::factory()->dosen()->create(['nidn' => '1977000001']);
        $dosenLain = User::factory()->dosen()->create(['nidn' => '1977000002']);

        $this->actingAs($dosen)->put(route('dosen.profil.update'), [
            'name' => $dosen->name,
            'email' => $dosen->email,
            'no_telepon' => '081555555555',
            'role' => 'admin',
            'nidn' => '1977000099',
            'id' => $dosenLain->getKey(),
            'is_active' => 0,
        ])->assertRedirect(route('dosen.profil'));

        $dosen->refresh();
        $this->assertSame(UserRole::Dosen, $dosen->role);
        $this->assertSame('1977000001', $dosen->nidn);
        $this->assertTrue($dosen->is_active);
        $this->assertDatabaseHas('users', ['id' => $dosenLain->id, 'nidn' => '1977000002']);
    }

    public function test_email_duplikat_ditolak_saat_mengubah_profil(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $lain = User::factory()->mahasiswa()->create(['email' => 'email.pakai@example.com']);

        $this->actingAs($mahasiswa)->put(route('mahasiswa.profil.update'), [
            'name' => $mahasiswa->name,
            'email' => $lain->email,
            'no_telepon' => null,
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('users', ['id' => $mahasiswa->id, 'email' => $mahasiswa->email]);
    }

    public function test_perubahan_password_berjalan_dengan_password_saat_ini_yang_benar(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $hashLama = $mahasiswa->password;

        $this->actingAs($mahasiswa)->post(route('mahasiswa.profil.password'), [
            'current_password' => 'password',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect(route('mahasiswa.profil'))->assertSessionHas('status');

        $mahasiswa->refresh();
        $this->assertTrue(Hash::check('password-baru-123', $mahasiswa->password));
        $this->assertNotSame($hashLama, $mahasiswa->password);
    }

    public function test_perubahan_password_ditolak_jika_password_saat_ini_salah_atau_konfirmasi_tidak_cocok(): void
    {
        $dosen = User::factory()->dosen()->create();
        $hashLama = $dosen->password;

        $this->actingAs($dosen)->post(route('dosen.profil.password'), [
            'current_password' => 'salah',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($dosen)->post(route('dosen.profil.password'), [
            'current_password' => 'password',
            'password' => 'password-baru-123',
            'password_confirmation' => 'tidak-sama',
        ])->assertSessionHasErrors('password');

        $dosen->refresh();
        $this->assertSame($hashLama, $dosen->password);
        $this->assertTrue(Hash::check('password', $dosen->password));
    }
}
