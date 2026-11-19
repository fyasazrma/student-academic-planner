<?php

namespace Tests\Feature;

use App\Enums\MetodeAbsensi;
use App\Enums\StatusAbsensi;
use App\Enums\StatusPeserta;
use App\Enums\StatusSesiAbsensi;
use App\Models\Absensi;
use App\Models\SesiAbsensi;
use App\Services\Absensi\AbsensiService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule as ScheduleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Concerns\MembuatDataAbsensi;
use Tests\TestCase;

class AbsensiOtomatisTest extends TestCase
{
    use MembuatDataAbsensi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow(Carbon::parse('2026-11-19 10:01:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_scheduler_terdaftar_setiap_menit_dengan_timezone_dan_lock(): void
    {
        $events = app(ScheduleManager::class)->events();

        $event = collect($events)->first(
            fn ($item): bool => str_contains((string) $item->command, 'absensi:tutup-otomatis'),
        );

        $this->assertNotNull($event, 'Scheduler absensi:tutup-otomatis tidak ditemukan.');
        $this->assertSame('* * * * *', $event->expression);
        $this->assertSame('Asia/Jakarta', $event->timezone);
        $this->assertTrue((bool) $event->withoutOverlapping);
    }

    public function test_command_menutup_sesi_jatuh_tempo_dan_mencatat_alfa_otomatis(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);

        $hadir = $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Hadir'));
        $mengulang = $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Mengulang'), StatusPeserta::Mengulang);
        $nonaktif = $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Nonaktif'), StatusPeserta::Nonaktif);

        $sesi = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '08:00', '10:00');
        $this->catatAbsensi($sesi, $hadir, StatusAbsensi::Hadir, MetodeAbsensi::Mandiri->value);

        $kode = Artisan::call('absensi:tutup-otomatis');
        $keluar = (string) Artisan::output();

        $this->assertSame(0, $kode);
        $this->assertStringContainsString('Sesi ditutup: 1', $keluar);
        $this->assertStringContainsString('alfa otomatis: 1', $keluar);
        $this->assertDatabaseHas('sesi_absensi', ['id' => $sesi->getKey(), 'status' => 'ditutup']);
        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $mengulang->getKey(),
            'status' => 'alfa',
            'metode' => 'otomatis',
            'dicatat_oleh' => null,
        ]);
        $this->assertDatabaseMissing('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $nonaktif->getKey(),
        ]);
        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $hadir->getKey(),
            'status' => 'hadir',
            'metode' => 'mandiri',
        ]);
        $this->assertSame(2, Absensi::query()->where('sesi_absensi_id', $sesi->getKey())->count());
    }

    public function test_command_tidak_mengubah_catatan_izin_sakit_dan_hadir(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);

        $izin = $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Izin'));
        $sakit = $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Sakit'));
        $belum = $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Belum'));

        $sesi = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '08:00', '10:00');
        $this->catatAbsensi($sesi, $izin, StatusAbsensi::Izin, MetodeAbsensi::Manual->value, $dosen);
        $this->catatAbsensi($sesi, $sakit, StatusAbsensi::Sakit, MetodeAbsensi::Manual->value, $dosen);

        Artisan::call('absensi:tutup-otomatis');

        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $izin->getKey(),
            'status' => 'izin',
            'metode' => 'manual',
        ]);
        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $sakit->getKey(),
            'status' => 'sakit',
            'metode' => 'manual',
        ]);
        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $belum->getKey(),
            'status' => 'alfa',
            'metode' => 'otomatis',
        ]);
    }

    public function test_command_tidak_menutup_sesi_yang_belum_jatuh_tempo_atau_sudah_ditutup(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi());

        $belum = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '10:00', '12:00');
        $ditutup = $this->sesiAbsensi($kelas, $dosen, 2, '2026-11-18', '08:00', '10:00', StatusSesiAbsensi::Ditutup);

        Artisan::call('absensi:tutup-otomatis');

        $this->assertStringContainsString('Sesi ditutup: 0', (string) Artisan::output());
        $this->assertDatabaseHas('sesi_absensi', ['id' => $belum->getKey(), 'status' => 'dibuka']);
        $this->assertDatabaseHas('sesi_absensi', ['id' => $ditutup->getKey(), 'status' => 'ditutup']);
        $this->assertSame(0, Absensi::query()->count());
    }

    public function test_command_tidak_memproses_sesi_tanpa_waktu_selesai(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi());

        $sesi = new SesiAbsensi();
        $sesi->forceFill([
            'kelas_mata_kuliah_id' => $kelas->getKey(),
            'dibuat_oleh' => $dosen->getKey(),
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-19',
            'topik' => 'Sesi tanpa waktu',
            'waktu_mulai' => null,
            'waktu_selesai' => null,
            'status' => StatusSesiAbsensi::Dibuka,
        ])->save();

        Artisan::call('absensi:tutup-otomatis');

        $this->assertDatabaseHas('sesi_absensi', ['id' => $sesi->getKey(), 'status' => 'dibuka']);
        $this->assertSame(0, Absensi::query()->count());
    }

    public function test_command_idempotent_pada_pemanggilan_ulang(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Satu'));
        $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Dua'));
        $sesi = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '08:00', '10:00');

        Artisan::call('absensi:tutup-otomatis');
        $this->assertSame(2, Absensi::query()->where('sesi_absensi_id', $sesi->getKey())->count());

        Artisan::call('absensi:tutup-otomatis');
        $keluar = (string) Artisan::output();

        $this->assertStringContainsString('Sesi ditutup: 0', $keluar);
        $this->assertStringContainsString('alfa otomatis: 0', $keluar);
        $this->assertStringContainsString('gagal: 0', $keluar);
        $this->assertSame(2, Absensi::query()->where('sesi_absensi_id', $sesi->getKey())->count());
        $this->assertSame(2, Absensi::query()
            ->where('sesi_absensi_id', $sesi->getKey())
            ->where('metode', MetodeAbsensi::Otomatis->value)
            ->count());
    }

    public function test_penutupan_sesi_bersamaan_tidak_membuat_catatan_duplikat(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Satu'));
        $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Dua'));
        $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Tiga'));
        $sesi = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '08:00', '10:00');

        $service = app(AbsensiService::class);

        $pertama = $service->tutupSesi($sesi);
        $kedua = $service->tutupSesi($sesi->fresh());

        $this->assertTrue($pertama['berhasil']);
        $this->assertSame(3, $pertama['alfa']);
        $this->assertTrue($kedua['berhasil']);
        $this->assertTrue($kedua['sudah_ditutup']);
        $this->assertSame(0, $kedua['alfa']);
        $this->assertSame(3, Absensi::query()->where('sesi_absensi_id', $sesi->getKey())->count());
        $this->assertDatabaseHas('sesi_absensi', ['id' => $sesi->getKey(), 'status' => 'ditutup']);
    }

    public function test_pengisian_mandiri_dan_penutupan_bersamaan_tidak_membuat_duplikat(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $mahasiswa = $this->mahasiswaAbsensi();
        $this->pesertaAbsensi($kelas, $mahasiswa);
        $sesi = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '08:00', '11:00');

        $service = app(AbsensiService::class);
        $id = $mahasiswa->getAuthIdentifier();

        $mandiri = $service->isiMandiri($id, $sesi->getKey());
        $tutup = $service->tutupSesi($sesi->fresh());

        $this->assertTrue($mandiri['berhasil']);
        $this->assertTrue($tutup['berhasil']);
        $this->assertSame(0, $tutup['alfa']);
        $this->assertSame(1, Absensi::query()->where('sesi_absensi_id', $sesi->getKey())->count());
        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'status' => 'hadir',
            'metode' => 'mandiri',
        ]);
    }
}
