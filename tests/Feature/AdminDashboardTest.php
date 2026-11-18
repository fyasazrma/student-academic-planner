<?php

namespace Tests\Feature;

use App\Enums\JenisAgenda;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_melihat_statistik_yang_berasal_dari_query_nyata(): void
    {
        $admin = User::factory()->admin()->create();

        User::factory()->mahasiswa()->count(2)->create(['is_active' => true]);
        User::factory()->mahasiswa()->create(['is_active' => false]);
        User::factory()->dosen()->create(['is_active' => true]);
        User::factory()->dosen()->create(['is_active' => false]);

        $this->createMataKuliah('MK-AKTIF', 'Mata Kuliah Aktif');
        $this->createMataKuliah('MK-NONAKTIF', 'Mata Kuliah Nonaktif', false);

        $periodeAktif = $this->createTahunAkademik(2026, true);
        $periodeLain = $this->createTahunAkademik(2025, false, 'genap');
        $dosen = User::factory()->dosen()->create();

        $this->createKelas($this->createMataKuliah('MK-1', 'Mata Kuliah Satu'), $periodeAktif, $dosen, 'A');
        $this->createKelas($this->createMataKuliah('MK-2', 'Mata Kuliah Dua'), $periodeAktif, $dosen, 'B');
        $this->createKelas($this->createMataKuliah('MK-3', 'Mata Kuliah Tiga'), $periodeLain, $dosen, 'A');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('Mahasiswa aktif')
            ->assertSee('Dosen aktif')
            ->assertSee('Mata kuliah aktif')
            ->assertSee('Kelas periode aktif')
            ->assertSee('2026/2027 Ganjil');

        $this->assertSame([
            'mahasiswa_aktif' => 2,
            'dosen_aktif' => 2,
            'mata_kuliah_aktif' => 4,
            'kelas_periode_aktif' => 2,
        ], $response->viewData('statistik'));
    }

    public function test_tanpa_periode_aktif_menampilkan_empty_state_dan_nol_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $this->createMataKuliah('MK-AKTIF', 'Mata Kuliah Aktif');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('Tahun akademik belum ditetapkan')
            ->assertSee('Belum ada agenda mendatang')
            ->assertSee('Tidak ada periode aktif')
            ->assertDontSee('2026/2027 Ganjil');

        $this->assertNull($response->viewData('tahunAkademikAktif'));
        $this->assertSame(0, $response->viewData('statistik')['kelas_periode_aktif']);
        $this->assertSame([], $response->viewData('agendaMendatang'));
    }

    public function test_kalender_hanya_memakai_agenda_institusi_terpublikasi_dan_jadwal_periode_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $periodeAktif = $this->createTahunAkademik(2026, true);
        $periodeLain = $this->createTahunAkademik(2025, false, 'genap');
        $dosen = User::factory()->dosen()->create();

        $mkAktif = $this->createMataKuliah('MK-AKTIF', 'Pemrograman Laravel');
        $mkLain = $this->createMataKuliah('MK-LAIN', 'Mata Kuliah Periode Lain');
        $kelasAktif = $this->createKelas($mkAktif, $periodeAktif, $dosen, 'A');
        $kelasLain = $this->createKelas($mkLain, $periodeLain, $dosen, 'A');

        $this->createAgenda($admin, null, 'Rapat Akademik Institusi', JenisAgenda::Akademik, Carbon::now());
        $this->createAgenda($admin, null, 'Agenda Belum Dipublikasikan', JenisAgenda::Pengumuman, Carbon::now(), false);
        $this->createAgenda($admin, $kelasAktif, 'Agenda Khusus Kelas', JenisAgenda::Ujian, Carbon::now());

        $this->createJadwal($kelasAktif, Carbon::now()->dayOfWeek, 'Ruang Aktif');
        $this->createJadwal($kelasLain, Carbon::now()->dayOfWeek, 'Ruang Nonaktif');

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'view' => 'month',
            'date' => '2026-11-01',
        ]));

        $response->assertOk()
            ->assertSee('Rapat Akademik Institusi')
            ->assertDontSee('Agenda Belum Dipublikasikan')
            ->assertDontSee('Agenda Khusus Kelas');

        $events = $response->viewData('calendar')['events'];

        $this->assertTrue($events->contains('judul', 'Rapat Akademik Institusi'));
        $this->assertTrue($events->contains('lokasi', 'Ruang Aktif'));
        $this->assertFalse($events->contains('lokasi', 'Ruang Nonaktif'));
        $this->assertFalse($events->contains('judul', 'Agenda Khusus Kelas'));
        $this->assertFalse($events->contains('judul', 'Agenda Belum Dipublikasikan'));
    }

    public function test_navigasi_periode_dan_filter_jenis_kalender_berfungsi(): void
    {
        $admin = User::factory()->admin()->create();

        $this->createAgenda($admin, null, 'Agenda Akademik Minggu Ini', JenisAgenda::Akademik, Carbon::now());
        $this->createAgenda($admin, null, 'Ujian Minggu Ini', JenisAgenda::Ujian, Carbon::now());
        $this->createAgenda($admin, null, 'Pengumuman Minggu Ini', JenisAgenda::Pengumuman, Carbon::now());

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'view' => 'week',
            'date' => '2026-11-18',
            'jenis' => ['ujian', 'jenis-tidak-valid'],
        ]));

        $response->assertOk()
            ->assertSee('Ujian Minggu Ini')
            ->assertSee('name="jenis[]"', false)
            ->assertSee('value="ujian"', false);

        $this->assertSame('week', $response->viewData('currentView'));
        $this->assertSame(['ujian'], $response->viewData('activeFilters'));
        $events = $response->viewData('calendar')['events'];
        $judul = $events->pluck('judul');

        $this->assertTrue($events->every(fn (array $event): bool => $event['jenis'] === 'ujian'));
        $this->assertFalse($judul->contains('Agenda Akademik Minggu Ini'));
        $this->assertFalse($judul->contains('Pengumuman Minggu Ini'));
        $this->assertSame('2026-11-11', $response->viewData('calendar')['previous']);
        $this->assertSame('2026-11-25', $response->viewData('calendar')['next']);
    }

    public function test_shell_admin_tidak_mengubah_shell_mahasiswa(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
        $mahasiswa = User::factory()->mahasiswa()->create(['name' => 'Mahasiswa Utama']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Menu admin')
            ->assertSee('Navigasi utama admin')
            ->assertSee('Dashboard Admin')
            ->assertSee('Administrator')
            ->assertDontSee('Menu mahasiswa')
            ->assertDontSee('Navigasi utama mahasiswa')
            ->assertDontSee(route('mahasiswa.notifikasi'), false);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Menu mahasiswa')
            ->assertSee('Navigasi utama mahasiswa')
            ->assertSee(route('mahasiswa.notifikasi'), false)
            ->assertDontSee('Menu admin')
            ->assertDontSee('Navigasi utama admin');
    }

    public function test_dashboard_admin_menolak_tamu_role_lain_dan_akun_nonaktif(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        foreach ([User::factory()->dosen()->create(), User::factory()->mahasiswa()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        }

        $nonaktif = User::factory()->admin()->create(['is_active' => false]);

        $this->actingAs($nonaktif)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    private function createTahunAkademik(int $tahunMulai, bool $aktif, string $semester = 'ganjil'): TahunAkademik
    {
        return TahunAkademik::query()->create([
            'tahun_mulai' => $tahunMulai,
            'tahun_selesai' => $tahunMulai + 1,
            'semester' => $semester,
            'tanggal_mulai' => $tahunMulai.'-09-01',
            'tanggal_selesai' => $tahunMulai.'-12-31',
            'aktif' => $aktif,
        ]);
    }

    private function createMataKuliah(string $kode, string $nama, bool $aktif = true): MataKuliah
    {
        return MataKuliah::query()->create([
            'kode' => $kode,
            'nama' => $nama,
            'sks' => 3,
            'semester' => 5,
            'aktif' => $aktif,
        ]);
    }

    private function createKelas(
        MataKuliah $mataKuliah,
        TahunAkademik $tahunAkademik,
        User $dosen,
        string $kodeKelas,
    ): KelasMataKuliah {
        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahunAkademik->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kodeKelas,
            'kapasitas' => 30,
            'status_pendaftaran' => 'tutup',
        ])->save();

        return $kelas;
    }

    private function createAgenda(
        User $admin,
        ?KelasMataKuliah $kelas,
        string $judul,
        JenisAgenda $jenis,
        Carbon $tanggal,
        bool $dipublikasikan = true,
    ): AgendaAkademik {
        $agenda = new AgendaAkademik();
        $agenda->forceFill([
            'dibuat_oleh' => $admin->id,
            'kelas_mata_kuliah_id' => $kelas?->id,
            'judul' => $judul,
            'jenis' => $jenis->value,
            'tanggal_mulai' => $tanggal->toDateString(),
            'waktu_mulai' => '09:00:00',
            'dipublikasikan_pada' => $dipublikasikan ? now()->subDay() : null,
        ])->save();

        return $agenda;
    }

    private function createJadwal(KelasMataKuliah $kelas, int $hari, string $ruang): JadwalKuliah
    {
        return JadwalKuliah::query()->create([
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => $hari,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:30:00',
            'ruang' => $ruang,
            'jenis' => 'kuliah',
        ]);
    }
}
