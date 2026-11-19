<?php

namespace Tests\Feature;

use App\Enums\JenisAgenda;
use App\Enums\StatusPendaftaranKelas;
use App\Enums\StatusPeserta;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\Tugas;
use App\Models\User;
use App\Services\Kelas\KunciGabungKelasService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenDashboardTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-11-18 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_hanya_memakai_data_kelas_dan_notifikasi_dosen_sendiri(): void
    {
        $dosen = User::factory()->dosen()->create(['name' => 'Dosen Utama']);
        $tahun = $this->createTahunAkademik();
        $kelas = $this->createKelas($dosen, $tahun, $this->createMataKuliah('MK-AKTIF'), 'A01');
        app(KunciGabungKelasService::class)->terapkan($kelas);
        $kelas->forceFill(['status_pendaftaran' => StatusPendaftaranKelas::Buka->value])->save();

        $mahasiswaAktifSatu = User::factory()->mahasiswa()->create();
        $mahasiswaAktifDua = User::factory()->mahasiswa()->create();
        $mahasiswaNonaktif = User::factory()->mahasiswa()->create();
        $this->enroll($mahasiswaAktifSatu, $kelas);
        $this->enroll($mahasiswaAktifDua, $kelas);
        $this->enroll($mahasiswaNonaktif, $kelas, StatusPeserta::Nonaktif->value);

        $this->createSchedule($kelas, 3, '07:00:00', '08:30:00');
        $this->createTask($kelas, 'Tugas Kelas Saya', Carbon::parse('2026-11-17 08:00:00'), Carbon::parse('2026-11-20 23:59:00'));
        $this->createAgenda($kelas, 'Ujian Kelas Saya', JenisAgenda::Ujian, Carbon::parse('2026-11-25 09:00:00'));
        $this->createNotification($dosen, 'Notifikasi Dosen Saya');

        $dosenLain = User::factory()->dosen()->create(['name' => 'Dosen Lain']);
        $kelasLain = $this->createKelas($dosenLain, $tahun, $this->createMataKuliah('MK-LAIN'), 'B02');
        $this->createTask($kelasLain, 'Tugas Dosen Lain', Carbon::parse('2026-11-17 08:00:00'), Carbon::parse('2026-11-21 23:59:00'));
        $this->createAgenda($kelasLain, 'Ujian Dosen Lain', JenisAgenda::Ujian, Carbon::parse('2026-11-26 09:00:00'));
        $this->createNotification($dosenLain, 'Notifikasi Dosen Lain');

        $respons = $this->actingAs($dosen)->get(route('dosen.dashboard'));

        $respons->assertOk()
            ->assertViewIs('dosen.dashboard')
            ->assertSee('Dosen Utama')
            ->assertSee('Menu dosen')
            ->assertSee('Dashboard Dosen')
            ->assertSee('Tugas Kelas Saya')
            ->assertSee('Ujian Kelas Saya')
            ->assertSee('Notifikasi Dosen Saya')
            ->assertDontSee('Tugas Dosen Lain')
            ->assertDontSee('Ujian Dosen Lain')
            ->assertDontSee('Notifikasi Dosen Lain')
            ->assertDontSee('Menu mahasiswa')
            ->assertDontSee('Menu admin');

        $this->assertSame([
            'kelas_diampu' => 1,
            'pendaftaran_buka' => 1,
            'peserta_aktif' => 2,
            'jadwal_kuliah' => 1,
        ], $respons->viewData('stats'));

        $this->assertSame([$kelas->id], collect($respons->viewData('ringkasanKelas'))->pluck('id')->all());
        $this->assertContains('Tugas Kelas Saya', collect($respons->viewData('deadlines'))->pluck('judul')->all());
        $this->assertContains('Ujian Kelas Saya', collect($respons->viewData('nextSchedules'))->pluck('judul')->all());
        $this->assertContains('Notifikasi Dosen Saya', $respons->viewData('notifications')->pluck('judul')->all());
    }

    public function test_dashboard_kosong_memakai_empty_state_dan_angka_nyata_nol(): void
    {
        $dosen = User::factory()->dosen()->create();

        $respons = $this->actingAs($dosen)->get(route('dosen.dashboard'));

        $respons->assertOk()
            ->assertSee('Belum ada kelas')
            ->assertSee('Belum ada notifikasi untuk akun dosen ini.')
            ->assertSee('Tidak ada deadline tugas mendatang pada kelas yang Anda ampu.')
            ->assertSee('Belum ada jadwal kuliah atau ujian yang dapat ditampilkan.')
            ->assertSee('Tidak ada agenda')
            ->assertDontSee('Menu mahasiswa')
            ->assertDontSee('Menu admin');

        $this->assertSame([
            'kelas_diampu' => 0,
            'pendaftaran_buka' => 0,
            'peserta_aktif' => 0,
            'jadwal_kuliah' => 0,
        ], $respons->viewData('stats'));
        $this->assertSame([], $respons->viewData('ringkasanKelas'));
        $this->assertTrue($respons->viewData('notifications')->isEmpty());
        $this->assertSame(0, $respons->viewData('unreadCount'));
    }

    public function test_kalender_dan_timeline_dosen_memisahkan_publikasi_deadline_dan_titik_ujian(): void
    {
        $dosen = User::factory()->dosen()->create();
        $kelas = $this->createKelas($dosen, $this->createTahunAkademik(), $this->createMataKuliah('MK-KALENDER'), 'A01');
        $tugas = $this->createTask($kelas, 'Tugas Publikasi Deadline', Carbon::parse('2026-11-17 08:00:00'), Carbon::parse('2026-11-20 23:59:00'));
        $ujian = $this->createAgenda($kelas, 'Ujian Timeline', JenisAgenda::Ujian, Carbon::parse('2026-11-19 09:00:00'));
        $jadwal = $this->createSchedule($kelas, 3, '07:00:00', '08:30:00');

        $kalender = $this->actingAs($dosen)->get(route('dosen.dashboard', [
            'view' => 'week',
            'date' => '2026-11-18',
        ]));

        $events = collect($kalender->viewData('calendar')['events']);
        $ids = $events->pluck('id')->all();

        $kalender->assertOk()
            ->assertSee('Tugas Publikasi Deadline')
            ->assertSee('Ujian Timeline')
            ->assertSee('Kalender akademik');

        $this->assertContains('tugas-'.$tugas->id, $ids);
        $this->assertContains('agenda-'.$ujian->id, $ids);
        $this->assertContains('jadwal-'.$jadwal->id, $ids);
        $this->assertContains('Tugas', $events->pluck('label')->all());
        $this->assertContains('Ujian', $events->pluck('label')->all());

        $timeline = $this->actingAs($dosen)->get(route('dosen.dashboard', [
            'view' => 'week',
            'date' => '2026-11-18',
            'tab' => 'timeline',
        ]));

        $bars = $timeline->viewData('timeline')['bars'];
        $markers = $timeline->viewData('timeline')['markers'];

        $timeline->assertOk()
            ->assertSee('Rentang pengerjaan tugas')
            ->assertSee('Tugas Publikasi Deadline')
            ->assertSee('Dipublikasikan')
            ->assertSee('Deadline')
            ->assertSee('Penanda penting')
            ->assertSee('jenis = ujian', false);

        $this->assertCount(1, $bars);
        $this->assertSame($tugas->id, $bars[0]['id']);
        $this->assertNotSame($bars[0]['dipublikasikan_label'], $bars[0]['deadline_label']);
        $this->assertSame([$ujian->id], collect($markers)->pluck('id')->map(fn (string $id): int => (int) str_replace('ujian-', '', $id))->all());
    }

    public function test_filter_jenis_kalender_dosen_memisahkan_tugas_dan_deadline(): void
    {
        $dosen = User::factory()->dosen()->create();
        $kelas = $this->createKelas($dosen, $this->createTahunAkademik(), $this->createMataKuliah('MK-FILTER'), 'A01');
        $this->createTask($kelas, 'Tugas Terfilter', Carbon::parse('2026-11-17 08:00:00'), Carbon::parse('2026-11-20 23:59:00'));

        $respons = $this->actingAs($dosen)->get(route('dosen.dashboard', [
            'view' => 'week',
            'date' => '2026-11-18',
            'jenis' => ['tugas'],
        ]));

        $respons->assertOk();

        $events = collect($respons->viewData('calendar')['events']);

        $this->assertSame(['tugas'], $respons->viewData('activeFilters'));
        $this->assertCount(1, $events);
        $this->assertSame(['tugas'], $events->pluck('jenis')->all());
        $this->assertSame('Tugas', $events->first()['label']);
    }

    public function test_kalender_timeline_dan_deadline_dosen_hanya_memakai_tugas_terpublikasi(): void
    {
        $dosen = User::factory()->dosen()->create();
        $kelas = $this->createKelas($dosen, $this->createTahunAkademik(), $this->createMataKuliah('MK-PUBLIKASI'), 'A01');

        $draft = $this->createTask(
            $kelas,
            'Tugas Draft Dashboard',
            null,
            Carbon::parse('2026-11-25 23:59:00'),
        );
        $masaDepan = $this->createTask(
            $kelas,
            'Tugas Publikasi Masa Depan Dashboard',
            Carbon::parse('2026-11-20 08:00:00'),
            Carbon::parse('2026-11-27 23:59:00'),
        );
        $published = $this->createTask(
            $kelas,
            'Tugas Published Dashboard',
            Carbon::parse('2026-11-17 08:00:00'),
            Carbon::parse('2026-11-24 23:59:00'),
        );

        $respons = $this->actingAs($dosen)->get(route('dosen.dashboard', [
            'view' => 'week',
            'date' => '2026-11-19',
        ]));

        $events = collect($respons->viewData('calendar')['events']);
        $eventIds = $events->pluck('id')->all();

        $respons->assertOk()
            ->assertSee('Tugas Published Dashboard')
            ->assertDontSee('Tugas Draft Dashboard')
            ->assertDontSee('Tugas Publikasi Masa Depan Dashboard');

        $this->assertContains('tugas-'.$published->id, $eventIds);
        $this->assertNotContains('tugas-'.$draft->id, $eventIds);
        $this->assertNotContains('tugas-'.$masaDepan->id, $eventIds);

        $timeline = $this->actingAs($dosen)->get(route('dosen.dashboard', [
            'view' => 'week',
            'date' => '2026-11-19',
            'tab' => 'timeline',
        ]));

        $bars = collect($timeline->viewData('timeline')['bars']);
        $markerIds = collect($timeline->viewData('timeline')['markers'])->pluck('id')->all();

        $this->assertSame([$published->id], $bars->pluck('id')->all());
        $this->assertSame(['Tugas Published Dashboard'], $bars->pluck('judul')->all());
        $this->assertNotContains('deadline-'.$draft->id, $markerIds);
        $this->assertNotContains('deadline-'.$masaDepan->id, $markerIds);

        $this->assertSame(
            ['Tugas Published Dashboard'],
            collect($respons->viewData('deadlines'))->pluck('judul')->all(),
        );
    }

    public function test_shell_dosen_tidak_menampilkan_tautan_notifikasi_mahasiswa(): void
    {
        $dosen = User::factory()->dosen()->create();
        $this->createNotification($dosen, 'Notifikasi Tanpa Tautan Dosen');

        $respons = $this->actingAs($dosen)->get(route('dosen.dashboard'));

        $respons->assertOk()
            ->assertSee('Notifikasi Tanpa Tautan Dosen')
            ->assertSee('Mata Kuliah Saya')
            ->assertDontSee('href="'.route('mahasiswa.notifikasi').'"', false)
            ->assertDontSee('Tugas & Pengumpulan');
    }

    public function test_role_lain_tidak_dapat_mengakses_dashboard_dosen(): void
    {
        $this->get(route('dosen.dashboard'))->assertRedirect(route('login'));

        foreach ([User::factory()->mahasiswa()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('dosen.dashboard'))->assertForbidden();
        }
    }

    public function test_kelas_historis_tetap_muncul_di_dashboard_dosen(): void
    {
        $dosen = User::factory()->dosen()->create();
        $mkHistoris = $this->createMataKuliah('MK-HISTORIS', false);
        $tahunHistoris = $this->createTahunAkademik(2024, false);
        $kelas = $this->createKelas($dosen, $tahunHistoris, $mkHistoris, 'L01');

        $respons = $this->actingAs($dosen)->get(route('dosen.dashboard'));

        $respons->assertOk()
            ->assertSee('MK-HISTORIS')
            ->assertSee('Historis')
            ->assertSee('L01');

        $this->assertSame(1, $respons->viewData('stats')['kelas_diampu']);
        $this->assertSame('Historis', $respons->viewData('ringkasanKelas')[0]['periode_label']);
        $this->assertSame($kelas->id, $respons->viewData('ringkasanKelas')[0]['id']);
    }

    public function test_shell_mahasiswa_dan_admin_tidak_mengalami_regresi(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Menu mahasiswa')
            ->assertSee('Dashboard Mahasiswa')
            ->assertDontSee('Menu dosen')
            ->assertDontSee('Menu admin')
            ->assertDontSee(route('dosen.dashboard'), false);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.kalender'))
            ->assertOk()
            ->assertSee('Kalender Akademik');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Menu admin')
            ->assertDontSee('Menu dosen')
            ->assertDontSee('Menu mahasiswa');
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function createTahunAkademik(int $mulai = 2026, bool $aktif = true): TahunAkademik
    {
        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => $mulai,
            'tahun_selesai' => $mulai + 1,
            'semester' => 'ganjil',
            'tanggal_mulai' => $mulai.'-09-01',
            'tanggal_selesai' => $mulai.'-12-31',
            'aktif' => $aktif,
        ])->save();

        return $tahun;
    }

    private function createMataKuliah(string $kode, bool $aktif = true): MataKuliah
    {
        $this->urutan++;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => $kode,
            'nama' => 'Mata Kuliah '.$kode,
            'sks' => 3,
            'aktif' => $aktif,
        ])->save();

        return $mataKuliah;
    }

    private function createKelas(
        User $dosen,
        TahunAkademik $tahun,
        MataKuliah $mataKuliah,
        string $kode,
    ): KelasMataKuliah {
        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kode,
            'nama_kelas' => 'Kelas '.$kode,
            'kapasitas' => 30,
        ])->save();

        return $kelas;
    }

    private function enroll(
        User $mahasiswa,
        KelasMataKuliah $kelas,
        string $status = StatusPeserta::Aktif->value,
    ): PesertaMataKuliah {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status,
            'tanggal_daftar' => Carbon::now()->subWeek(),
        ])->save();

        return $peserta;
    }

    private function createTask(
        KelasMataKuliah $kelas,
        string $judul,
        ?Carbon $dipublikasikan,
        Carbon $deadline,
    ): Tugas {
        $tugas = new Tugas();
        $tugas->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'dibuat_oleh' => $kelas->dosen_id,
            'judul' => $judul,
            'deskripsi' => 'Deskripsi '.$judul,
            'dipublikasikan_pada' => $dipublikasikan,
            'deadline' => $deadline,
        ])->save();

        return $tugas;
    }

    private function createAgenda(
        KelasMataKuliah $kelas,
        string $judul,
        JenisAgenda $jenis,
        Carbon $tanggal,
    ): AgendaAkademik {
        $agenda = new AgendaAkademik();
        $agenda->forceFill([
            'dibuat_oleh' => $kelas->dosen_id,
            'kelas_mata_kuliah_id' => $kelas->id,
            'judul' => $judul,
            'deskripsi' => 'Deskripsi '.$judul,
            'jenis' => $jenis->value,
            'tanggal_mulai' => $tanggal->toDateString(),
            'waktu_mulai' => $tanggal->format('H:i:s'),
            'waktu_selesai' => $tanggal->copy()->addHour()->format('H:i:s'),
            'lokasi' => 'R. 202',
            'dipublikasikan_pada' => Carbon::now()->subDay(),
        ])->save();

        return $agenda;
    }

    private function createSchedule(
        KelasMataKuliah $kelas,
        int $hari,
        string $mulai,
        string $selesai,
    ): JadwalKuliah {
        $jadwal = new JadwalKuliah();
        $jadwal->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => $hari,
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'ruang' => 'R. 101',
            'jenis' => 'kuliah',
        ])->save();

        return $jadwal;
    }

    private function createNotification(User $user, string $judul): Notifikasi
    {
        $notifikasi = new Notifikasi();
        $notifikasi->forceFill([
            'user_id' => $user->id,
            'tipe' => 'tugas',
            'judul' => $judul,
            'pesan' => 'Pesan '.$judul,
            'tautan' => null,
            'dibaca_pada' => null,
        ])->save();

        return $notifikasi;
    }
}
