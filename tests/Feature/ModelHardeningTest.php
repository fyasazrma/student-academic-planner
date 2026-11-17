<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\KelasMataKuliah;
use App\Models\Nilai;
use App\Models\Notifikasi;
use App\Models\PengumpulanTugas;
use App\Models\PesertaMataKuliah;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModelHardeningTest extends TestCase
{
    public function test_lingkungan_pengujian_memakai_sqlite_in_memory(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertFalse(Schema::hasTable('notifications'));
    }

    public function test_user_dapat_mengirim_reset_password_via_email_tanpa_tabel_notifications(): void
    {
        Notification::fake();

        $user = new User();
        $user->forceFill([
            'name' => 'Pengguna Uji',
            'email' => 'pengguna@example.test',
        ]);
        $user->id = 1;

        $this->assertTrue(in_array(Notifiable::class, class_uses_recursive(User::class), true));
        $this->assertTrue(method_exists($user, 'notify'));
        $this->assertTrue(method_exists($user, 'sendPasswordResetNotification'));

        $resetPassword = new ResetPassword('token-uji');

        $this->assertSame(['mail'], $resetPassword->via($user));

        $user->sendPasswordResetNotification('token-uji');

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            fn (ResetPassword $notification, array $channels): bool => $notification->token === 'token-uji'
                && in_array('mail', $channels, true)
                && ! in_array('database', $channels, true),
        );

        $this->assertFalse(Schema::hasTable('notifications'));
    }

    public function test_relasi_notifikasi_mengarah_ke_model_dan_tabel_khusus(): void
    {
        $user = new User();
        $relation = $user->notifikasi();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame(Notifikasi::class, $relation->getRelated()::class);
        $this->assertSame('notifikasi', $relation->getRelated()->getTable());
        $this->assertSame('user_id', $relation->getForeignKeyName());
        $this->assertNotSame('notifications', $relation->getRelated()->getTable());
    }

    public function test_notifikasi_akademik_tidak_bergantung_pada_tabel_notifications(): void
    {
        $appSource = collect(File::allFiles(app_path()))
            ->filter(fn ($file): bool => $file->getExtension() === 'php')
            ->map(fn ($file): string => file_get_contents($file->getPathname()))
            ->implode("\n");

        $migrationSource = collect(File::files(database_path('migrations')))
            ->map(fn ($file): string => file_get_contents($file->getPathname()))
            ->implode("\n");

        $this->assertStringNotContainsString('DatabaseNotification', $appSource);
        $this->assertStringNotContainsString("Schema::create('notifications'", $appSource);
        $this->assertStringNotContainsString("Schema::create('notifications'", $migrationSource);
        $this->assertFalse(is_subclass_of(Notifikasi::class, DatabaseNotification::class));
    }

    public function test_kolom_sensitif_tetap_terlindungi_dari_mass_assignment(): void
    {
        $user = new User([
            'name' => 'Admin Uji',
            'email' => 'admin@example.test',
            'role' => UserRole::Admin,
            'is_active' => false,
        ]);

        $this->assertSame('Admin Uji', $user->name);
        $this->assertArrayNotHasKey('role', $user->getAttributes());
        $this->assertArrayNotHasKey('is_active', $user->getAttributes());

        $kelas = new KelasMataKuliah(['dosen_id' => 1, 'kode_kelas' => 'A']);
        $this->assertArrayNotHasKey('dosen_id', $kelas->getAttributes());
        $this->assertSame('A', $kelas->kode_kelas);

        $peserta = new PesertaMataKuliah(['mahasiswa_id' => 1, 'status' => 'aktif']);
        $this->assertArrayNotHasKey('mahasiswa_id', $peserta->getAttributes());

        $pengumpulan = new PengumpulanTugas([
            'peserta_mata_kuliah_id' => 1,
            'terlambat' => true,
        ]);
        $this->assertArrayNotHasKey('peserta_mata_kuliah_id', $pengumpulan->getAttributes());
        $this->assertArrayNotHasKey('terlambat', $pengumpulan->getAttributes());

        $nilai = new Nilai([
            'peserta_mata_kuliah_id' => 1,
            'komponen' => 'uts',
            'nilai' => 90,
            'dinilai_oleh' => 1,
            'dinilai_pada' => now(),
        ]);
        $this->assertArrayNotHasKey('peserta_mata_kuliah_id', $nilai->getAttributes());
        $this->assertArrayNotHasKey('nilai', $nilai->getAttributes());
        $this->assertArrayNotHasKey('dinilai_oleh', $nilai->getAttributes());
        $this->assertArrayNotHasKey('dinilai_pada', $nilai->getAttributes());

        $notifikasi = new Notifikasi(['user_id' => 1, 'judul' => 'Notifikasi']);
        $this->assertArrayNotHasKey('user_id', $notifikasi->getAttributes());

        $nilaiInternal = new Nilai();
        $nilaiInternal->forceFill([
            'peserta_mata_kuliah_id' => 1,
            'komponen' => 'uas',
            'nilai' => 88,
            'dinilai_oleh' => 2,
            'dinilai_pada' => now(),
        ]);
        $this->assertSame(1, $nilaiInternal->peserta_mata_kuliah_id);
        $this->assertNotNull($nilaiInternal->nilai);
        $this->assertSame(2, $nilaiInternal->dinilai_oleh);
    }

    public function test_factory_dapat_membuat_mahasiswa_dosen_dan_admin(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->make();
        $dosen = User::factory()->dosen()->make();
        $admin = User::factory()->admin()->make();

        $this->assertSame(UserRole::Mahasiswa, $mahasiswa->role);
        $this->assertSame(UserRole::Dosen, $dosen->role);
        $this->assertSame(UserRole::Admin, $admin->role);
    }

    public function test_status_pengumpulan_memprioritaskan_dinilai_dibandingkan_terlambat(): void
    {
        $dinilai = new PengumpulanTugas();
        $dinilai->forceFill(['terlambat' => true, 'nilai' => 90, 'dinilai_pada' => now()]);
        $this->assertTrue($dinilai->sudahDinilai());
        $this->assertSame('dinilai', $dinilai->status);

        $terlambat = new PengumpulanTugas();
        $terlambat->forceFill(['terlambat' => true]);
        $this->assertFalse($terlambat->sudahDinilai());
        $this->assertSame('terlambat', $terlambat->status);

        $terkumpul = new PengumpulanTugas();
        $terkumpul->forceFill(['terlambat' => false]);
        $this->assertSame('terkumpul', $terkumpul->status);

        $nilaiTanpaTanggal = new PengumpulanTugas();
        $nilaiTanpaTanggal->forceFill(['terlambat' => false, 'nilai' => 80]);
        $this->assertFalse($nilaiTanpaTanggal->sudahDinilai());
        $this->assertSame('terkumpul', $nilaiTanpaTanggal->status);
    }
}
