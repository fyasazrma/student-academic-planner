<?php

namespace Tests\Feature;

use App\Enums\JenisNotifikasi;
use App\Enums\StatusPeserta;
use App\Enums\StatusTargetBelajar;
use App\Enums\StatusTugasMahasiswa;
use App\Models\Notifikasi;
use App\Models\PengumpulanTugas;
use App\Models\TargetBelajar;
use App\Models\User;
use App\Policies\NotifikasiPolicy;
use App\Services\Mahasiswa\TargetBelajarService;
use App\Services\Notifikasi\NotifikasiService;
use App\Support\KelayakanPeserta;
use App\Support\StatusTargetResolver;
use App\Support\StatusTugasResolver;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use ReflectionProperty;
use Tests\Concerns\BatchDData;
use Tests\TestCase;

/**
 * Regression test untuk enam keputusan konsistensi Batch E.1.
 */
class BatchE1KonsistensiTest extends TestCase
{
    use BatchDData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-11-21 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_satu_aturan_kelayakan_memberi_hak_sama_ke_aktif_dan_mengulang(): void
    {
        $this->assertTrue(KelayakanPeserta::berhak(StatusPeserta::Aktif));
        $this->assertTrue(KelayakanPeserta::berhak(StatusPeserta::Mengulang));
        $this->assertFalse(KelayakanPeserta::berhak(StatusPeserta::Nonaktif));
        $this->assertSame(['aktif', 'mengulang'], KelayakanPeserta::nilaiStatusBerhak());

        $dosen = $this->dosenBatchD();
        $mahasiswa = $this->mahasiswaBatchD();
        $kelasAktif = $this->kelasBatchD($dosen);
        $kelasMengulang = $this->kelasBatchD($dosen);
        $kelasNonaktif = $this->kelasBatchD($dosen);

        $ini = $this->pesertaBatchD($mahasiswa, $kelasAktif, StatusPeserta::Aktif);
        $itu = $this->pesertaBatchD($mahasiswa, $kelasMengulang, StatusPeserta::Mengulang);
        $riwayat = $this->pesertaBatchD($mahasiswa, $kelasNonaktif, StatusPeserta::Nonaktif);

        $opsi = app(TargetBelajarService::class)->mataKuliahOptions($mahasiswa->id)
            ->pluck('id')
            ->all();

        $this->assertContains($kelasAktif->mata_kuliah_id, $opsi);
        $this->assertContains($kelasMengulang->mata_kuliah_id, $opsi);
        $this->assertNotContains($kelasNonaktif->mata_kuliah_id, $opsi);
        $this->assertDatabaseHas('peserta_mata_kuliah', ['id' => $riwayat->id]);
        $this->assertSame(StatusPeserta::Aktif, $ini->fresh()->status);
        $this->assertSame(StatusPeserta::Mengulang, $itu->fresh()->status);
    }

    public function test_zona_waktu_aplikasi_dan_scheduler_memakai_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());

        $events = collect(app(Schedule::class)->events())
            ->filter(fn (object $event): bool => str_contains((string) $event->command, 'absensi:tutup-otomatis')
                || str_contains((string) $event->command, 'pengumuman:terbitkan-terjadwal'))
            ->values();

        $this->assertCount(2, $events);

        foreach ($events as $event) {
            $properti = new ReflectionProperty($event, 'timezone');
            $this->assertSame('Asia/Jakarta', $properti->getValue($event));
        }
    }

    public function test_status_tugas_dihitung_dinamis_dan_tepat_pada_deadline_tidak_terlambat(): void
    {
        $deadline = Carbon::parse('2026-11-21 12:00:00', 'Asia/Jakarta');

        $this->assertSame(
            StatusTugasMahasiswa::BelumDikumpulkan,
            StatusTugasResolver::tentukan(null, $deadline, $deadline->copy()->subSecond()),
        );
        $this->assertSame(
            StatusTugasMahasiswa::BelumDikumpulkan,
            StatusTugasResolver::tentukan(null, $deadline, $deadline->copy()),
        );
        $this->assertSame(
            StatusTugasMahasiswa::Terlambat,
            StatusTugasResolver::tentukan(null, $deadline, $deadline->copy()->addSecond()),
        );

        $kumpulan = new PengumpulanTugas();
        $kumpulan->forceFill(['nilai' => null, 'dinilai_pada' => null]);
        $this->assertSame(
            StatusTugasMahasiswa::Dikumpulkan,
            StatusTugasResolver::tentukan($kumpulan, $deadline, $deadline->copy()->addDay()),
        );

        $dinilai = new PengumpulanTugas();
        $dinilai->forceFill(['nilai' => '85.00', 'dinilai_pada' => now()]);
        $this->assertSame(
            StatusTugasMahasiswa::SudahDinilai,
            StatusTugasResolver::tentukan($dinilai, $deadline, $deadline->copy()->addDay()),
        );
    }

    public function test_status_target_efektif_mengikuti_prioritas_yang_disepakati(): void
    {
        $hariIni = now()->copy()->startOfDay();
        $besok = $hariIni->copy()->addDay();
        $kemarin = $hariIni->copy()->subDay();

        $dibatalkan = $this->target('Dibatalkan', StatusTargetBelajar::Dibatalkan, $besok, $kemarin, 100, 100);
        $selesaiManual = $this->target('Selesai manual', StatusTargetBelajar::Selesai, $besok, $kemarin, 100, 0);
        $selesaiCapaian = $this->target('Selesai capaian', StatusTargetBelajar::Berlangsung, null, $kemarin, 80, 80);
        $belumMulai = $this->target('Belum mulai', StatusTargetBelajar::Berlangsung, $besok, $kemarin, 100, 0);
        $terlambat = $this->target('Terlambat', StatusTargetBelajar::Berlangsung, null, $kemarin, 100, 0);
        $berlangsung = $this->target('Berlangsung', StatusTargetBelajar::Berlangsung, null, $hariIni, 100, 0);

        $this->assertSame(StatusTargetBelajar::Dibatalkan, StatusTargetResolver::hitung($dibatalkan));
        $this->assertSame(StatusTargetBelajar::Selesai, StatusTargetResolver::hitung($selesaiManual));
        $this->assertSame(StatusTargetBelajar::Selesai, StatusTargetResolver::hitung($selesaiCapaian));
        $this->assertSame(StatusTargetBelajar::BelumDimulai, StatusTargetResolver::hitung($belumMulai));
        $this->assertSame(StatusTargetBelajar::Terlambat, StatusTargetResolver::hitung($terlambat));
        $this->assertSame(StatusTargetBelajar::Berlangsung, StatusTargetResolver::hitung($berlangsung));
    }

    public function test_filter_daftar_target_memakai_status_efektif_bukan_kolom_mentah(): void
    {
        $mahasiswa = $this->mahasiswaBatchD();

        $this->simpanTarget($mahasiswa, 'Efektif terlambat', StatusTargetBelajar::Berlangsung, null, now()->subDay()->toDateString());
        $this->simpanTarget($mahasiswa, 'Efektif berlangsung', StatusTargetBelajar::Terlambat, null, now()->addDay()->toDateString());
        $this->simpanTarget($mahasiswa, 'Efektif selesai', StatusTargetBelajar::Selesai, null, now()->subDay()->toDateString());
        $this->simpanTarget($mahasiswa, 'Efektif dibatalkan', StatusTargetBelajar::Dibatalkan, now()->addDays(2)->toDateString(), now()->subDay()->toDateString());

        $service = app(TargetBelajarService::class);
        $judul = fn (string $status): array => $service->daftar($mahasiswa->id, $status)['targets']
            ->pluck('judul')
            ->all();

        $this->assertSame(['Efektif terlambat'], $judul('terlambat'));
        $this->assertSame(['Efektif berlangsung'], $judul('berlangsung'));
        $this->assertSame(['Efektif selesai'], $judul('selesai'));
        $this->assertSame(['Efektif dibatalkan'], $judul('dibatalkan'));
    }

    public function test_pengumuman_institusi_menjangkau_peran_yang_tepat_dan_dedup_berdasarkan_sumber(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = $this->mahasiswaBatchD();
        $dosen = $this->dosenBatchD();
        $dosenNonaktif = User::factory()->dosen()->create(['is_active' => false]);

        $payload = $this->payloadPengumumanBatchD([
            'judul' => 'Pengumuman institusi E1',
            'isi' => 'Diuji untuk seluruh peran.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pengumuman.store'), $payload)
            ->assertRedirect(route('admin.pengumuman.index'));

        $this->assertSame(2, Notifikasi::count());
        $notifikasiMahasiswa = Notifikasi::untukUser($mahasiswa->id)->firstOrFail();
        $notifikasiDosen = Notifikasi::untukUser($dosen->id)->firstOrFail();
        $this->assertStringStartsWith('/mahasiswa/pengumuman/', (string) $notifikasiMahasiswa->tautanInternal());
        $this->assertStringStartsWith('/dosen/pengumuman/', (string) $notifikasiDosen->tautanInternal());
        $this->assertSame('mahasiswa', $notifikasiMahasiswa->data['peran']);
        $this->assertSame('dosen', $notifikasiDosen->data['peran']);
        $this->assertSame(0, Notifikasi::untukUser($admin->id)->count());
        $this->assertSame(0, Notifikasi::untukUser($dosenNonaktif->id)->count());

        $pengumuman = $notifikasiMahasiswa->data['sumber'];
        $this->actingAs($admin)
            ->post(route('admin.pengumuman.store'), $payload)
            ->assertRedirect(route('admin.pengumuman.index'));
        $this->assertSame(4, Notifikasi::count());
        $this->assertSame(2, Notifikasi::query()->where('data->sumber', $pengumuman)->count());

        $layanan = app(NotifikasiService::class);
        $sumberDedup = 'uji:e1:dedup';
        for ($i = 0; $i < 2; $i++) {
            $layanan->kirimKeAdmin(
                [$admin->id],
                JenisNotifikasi::Pengumuman,
                'Notifikasi administratif',
                'Dedup diuji.',
                '/admin/pengumuman',
                $sumberDedup,
            );
        }
        $this->assertSame(1, Notifikasi::untukUser($admin->id)->count());
        $this->assertSame('/admin/pengumuman', Notifikasi::untukUser($admin->id)->firstOrFail()->tautanInternal());
    }

    public function test_policy_menjaga_kepemilikan_dan_middleware_menjaga_peran(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = $this->dosenBatchD();
        $mahasiswa = $this->mahasiswaBatchD();

        $this->assertInstanceOf(NotifikasiPolicy::class, Gate::getPolicyFor(Notifikasi::class));

        $this->actingAs($mahasiswa)->get(route('admin.notifikasi'))->assertForbidden();
        $this->actingAs($dosen)->get(route('admin.notifikasi'))->assertForbidden();
        $this->actingAs($mahasiswa)->get(route('dosen.notifikasi'))->assertForbidden();
        $this->actingAs($admin)->get(route('dosen.notifikasi'))->assertForbidden();

        $milikDosen = new Notifikasi();
        $milikDosen->forceFill([
            'user_id' => $dosen->id,
            'tipe' => JenisNotifikasi::Pengumuman->value,
            'judul' => 'Notifikasi milik dosen',
            'pesan' => 'Tidak boleh dibaca peran lain.',
            'data' => ['sumber' => 'uji:e1:policy', 'peran' => 'dosen'],
            'tautan' => '/dosen/pengumuman',
        ])->save();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.notifikasi.baca', $milikDosen->id))
            ->assertNotFound();
        $this->actingAs($admin)
            ->post(route('admin.notifikasi.baca', $milikDosen->id))
            ->assertNotFound();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.notifikasi.baca-semua'))
            ->assertRedirect();
        $this->assertNull($milikDosen->fresh()->dibaca_pada);

        $this->actingAs($dosen)
            ->post(route('dosen.notifikasi.baca', $milikDosen->id))
            ->assertRedirect();
        $this->assertNotNull($milikDosen->fresh()->dibaca_pada);
    }

    /**
     * @return TargetBelajar
     */
    private function target(
        string $judul,
        StatusTargetBelajar $status,
        ?Carbon $mulai,
        ?Carbon $deadline,
        float $targetNilai,
        float $capaian,
    ): TargetBelajar {
        return new TargetBelajar([
            'judul' => $judul,
            'status' => $status,
            'tanggal_mulai' => $mulai,
            'deadline' => $deadline,
            'target_nilai' => $targetNilai,
            'capaian' => $capaian,
        ]);
    }

    private function simpanTarget(
        User $mahasiswa,
        string $judul,
        StatusTargetBelajar $status,
        ?string $mulai,
        ?string $deadline,
    ): TargetBelajar {
        $target = new TargetBelajar();
        $target->forceFill([
            'mahasiswa_id' => $mahasiswa->id,
            'judul' => $judul,
            'status' => $status,
            'tanggal_mulai' => $mulai,
            'deadline' => $deadline,
            'target_nilai' => 100,
            'capaian' => 0,
            'progres' => 0,
        ])->save();

        return $target;
    }
}
