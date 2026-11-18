<?php

namespace Tests\Feature;

use App\Enums\JenisAgenda;
use App\Enums\StatusPeserta;
use App\Enums\StatusProgres;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\MateriPembelajaran;
use App\Models\Notifikasi;
use App\Models\PengumpulanTugas;
use App\Models\PesertaMataKuliah;
use App\Models\ProgresMateri;
use App\Models\TahunAkademik;
use App\Models\Tugas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MahasiswaDashboardTest extends TestCase
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

    public function test_mahasiswa_hanya_melihat_tugas_dan_notifikasi_miliknya(): void
    {
        $dosen = User::factory()->dosen()->create();
        $tahun = $this->createTahunAkademik();
        $class = $this->createClass($dosen, $tahun, 'A');
        $otherClass = $this->createClass($dosen, $tahun, 'B');

        $mahasiswa = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Utama',
            'nim' => '20260001',
        ]);
        $lain = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Lain',
            'nim' => '20260002',
        ]);

        $this->enroll($mahasiswa, $class);
        $this->enroll($lain, $otherClass);

        $this->createTask($class, 'Tugas Milik Saya', Carbon::now()->subDays(2), Carbon::now()->addDays(2));
        $this->createTask($otherClass, 'Tugas Mahasiswa Lain', Carbon::now()->subDays(2), Carbon::now()->addDays(3));
        $this->createAgenda($otherClass, 'Agenda Mahasiswa Lain', JenisAgenda::Akademik, Carbon::now());

        $jadwalLain = new JadwalKuliah();
        $jadwalLain->forceFill([
            'kelas_mata_kuliah_id' => $otherClass->id,
            'hari' => Carbon::now()->dayOfWeek,
            'jam_mulai' => '13:00:00',
            'jam_selesai' => '14:30:00',
            'ruang' => 'Ruang Rahasia',
            'jenis' => 'kuliah',
        ])->save();

        $this->createNotification($mahasiswa, 'Notifikasi Milik Saya');
        $this->createNotification($lain, 'Notifikasi Mahasiswa Lain');

        $response = $this->actingAs($mahasiswa)->get(route('mahasiswa.dashboard'));

        $response->assertOk()
            ->assertSee('Mahasiswa Utama')
            ->assertSee('20260001')
            ->assertSee('Tugas Milik Saya')
            ->assertSee('Notifikasi Milik Saya')
            ->assertDontSee('Tugas Mahasiswa Lain')
            ->assertDontSee('Agenda Mahasiswa Lain')
            ->assertDontSee('Ruang Rahasia')
            ->assertDontSee('Notifikasi Mahasiswa Lain');
    }

    public function test_mahasiswa_tanpa_kelas_melihat_empty_state_dan_angka_nol(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Kosong',
            'nim' => '20260003',
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Mahasiswa Kosong')
            ->assertSee('Tugas belum dikumpulkan')
            ->assertSee('Belum ada notifikasi')
            ->assertSee('Tidak ada deadline aktif')
            ->assertSee('Belum ada jadwal')
            ->assertSee('Tidak ada agenda');
    }

    public function test_timeline_menampilkan_tanggal_publikasi_dan_deadline_secara_terpisah(): void
    {
        $mahasiswa = $this->mahasiswaWithClass();
        $this->createTask(
            $mahasiswa['class'],
            'Tugas Rentang Waktu',
            Carbon::create(2026, 11, 10, 8, 0),
            Carbon::create(2026, 11, 17, 23, 59),
        );

        $response = $this->actingAs($mahasiswa['user'])
            ->get(route('mahasiswa.dashboard', ['tab' => 'timeline']));

        $response->assertOk()
            ->assertSee('Tugas Rentang Waktu')
            ->assertSee('10 Nov 2026 08:00')
            ->assertSee('17 Nov 2026 23:59')
            ->assertSee('Publikasi')
            ->assertSee('Deadline')
            ->assertSee('Schema saat ini menyimpan ujian')
            ->assertSee('id="timeline-events-data"', false);

        $this->assertCount(1, $response->viewData('timeline')['bars']);
    }

    public function test_filter_dan_navigasi_periode_kalender_berfungsi(): void
    {
        $mahasiswa = $this->mahasiswaWithClass();
        $this->createTask($mahasiswa['class'], 'Tugas Filter Kalender', Carbon::now()->subDay(), Carbon::now()->addDay());
        $this->createAgenda($mahasiswa['class'], 'Ujian Tengah Semester', JenisAgenda::Ujian, Carbon::now());

        $response = $this->actingAs($mahasiswa['user'])
            ->get(route('mahasiswa.dashboard', ['view' => 'month', 'date' => '2026-11-01']));

        $events = collect($response->viewData('calendar')['events']);
        $judul = $events->pluck('judul');

        $response->assertOk()
            ->assertSee('November 2026')
            ->assertSee('date=2026-10-01', false)
            ->assertSee('date=2026-12-01', false);

        $this->assertSame('November 2026', $response->viewData('calendar')['label']);
        $this->assertSame('2026-11', $response->viewData('calendar')['focusDate']);
        $this->assertTrue($judul->contains('Tugas Filter Kalender'));
        $this->assertTrue($judul->contains('Ujian Tengah Semester'));

        $filtered = $this->actingAs($mahasiswa['user'])
            ->get(route('mahasiswa.dashboard', [
                'view' => 'month',
                'date' => '2026-11-01',
                'jenis' => ['pengumuman'],
            ]));

        $filteredEvents = collect($filtered->viewData('calendar')['events']);

        $filtered->assertOk();
        $this->assertTrue($filteredEvents->isEmpty());
    }

    public function test_agenda_ujian_tidak_diberi_label_uts_atau_uas_dari_judul(): void
    {
        $mahasiswa = $this->mahasiswaWithClass();
        $this->createAgenda($mahasiswa['class'], 'UTS Pemrograman', JenisAgenda::Ujian, Carbon::now()->addDays(2));

        $response = $this->actingAs($mahasiswa['user'])
            ->get(route('mahasiswa.dashboard'));

        $event = collect($response->viewData('calendar')['events'])
            ->firstWhere('judul', 'UTS Pemrograman');

        $response->assertOk()->assertSee('UTS Pemrograman');

        $this->assertNotNull($event);
        $this->assertSame('ujian', $event['jenis']);
        $this->assertSame('Ujian', $event['label']);
        $this->assertFalse(in_array($event['jenis'], ['uts', 'uas'], true));
    }

    public function test_pengumuman_memiliki_tanggal_publikasi_terpisah_dari_deadline(): void
    {
        $mahasiswa = $this->mahasiswaWithClass();
        $agenda = $this->createAgenda(
            $mahasiswa['class'],
            'Pengumuman Libur Akademik',
            JenisAgenda::Pengumuman,
            Carbon::now()->addDay(),
        );

        $response = $this->actingAs($mahasiswa['user'])
            ->get(route('mahasiswa.dashboard'));

        $event = collect($response->viewData('calendar')['events'])
            ->firstWhere('judul', 'Pengumuman Libur Akademik');

        $response->assertOk()->assertSee('Dipublikasikan pada');

        $this->assertNotNull($event);
        $this->assertSame('pengumuman', $event['jenis']);
        $this->assertSame('Tanggal mulai', $event['awal_label_name']);
        $this->assertSame('Tanggal selesai', $event['akhir_label_name']);
        $this->assertStringNotContainsString('deadline', strtolower($event['akhir_label_name']));
        $this->assertSame(
            $agenda->dipublikasikan_pada->translatedFormat('d M Y H:i'),
            $event['publikasi_label'],
        );
    }

    public function test_mahasiswa_tidak_dapat_mengakses_dashboard_role_lain(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($mahasiswa)->get('/dosen/dashboard')->assertStatus(403);
    }

    public function test_progres_materi_dihitung_dari_data_mahasiswa_sendiri(): void
    {
        $mahasiswa = $this->mahasiswaWithClass();
        $materi = new MateriPembelajaran();
        $materi->forceFill([
            'kelas_mata_kuliah_id' => $mahasiswa['class']->id,
            'dibuat_oleh' => $mahasiswa['dosen']->id,
            'judul' => 'Materi Pertemuan 1',
            'file_path' => 'materi/pertemuan-1.pdf',
            'file_name' => 'pertemuan-1.pdf',
            'dipublikasikan_pada' => Carbon::now()->subDay(),
        ])->save();

        $progres = new ProgresMateri();
        $progres->forceFill([
            'materi_pembelajaran_id' => $materi->id,
            'peserta_mata_kuliah_id' => $mahasiswa['enrollment']->id,
            'status' => StatusProgres::Selesai->value,
            'progres' => 100,
        ])->save();

        $this->actingAs($mahasiswa['user'])
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Progres materi (1/1)')
            ->assertSee('100%');
    }

    public function test_tahap_satu_menu_dan_route_mahasiswa_tersedia(): void
    {
        $data = $this->mahasiswaWithClass();

        $this->actingAs($data['user'])->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('Segera')
            ->assertSee('Profil Saya')
            ->assertSee('Buka kalender penuh');

        foreach ([
            'mahasiswa.kalender' => 'Kalender Akademik',
            'mahasiswa.tugas' => 'Pengumpulan',
            'mahasiswa.notifikasi' => 'Notifikasi',
        ] as $routeName => $label) {
            $this->actingAs($data['user'])->get(route($routeName))
                ->assertOk()
                ->assertSee($label)
                ->assertSee('aria-current="page"', false)
                ->assertSee('Dashboard Mahasiswa');
        }
    }

    public function test_role_lain_tidak_dapat_mengakses_route_tahap_satu(): void
    {
        $dosen = User::factory()->dosen()->create();
        $admin = User::factory()->admin()->create();

        foreach (['mahasiswa.kalender', 'mahasiswa.tugas', 'mahasiswa.notifikasi'] as $routeName) {
            $this->actingAs($dosen)->get(route($routeName))->assertForbidden();
            $this->actingAs($admin)->get(route($routeName))->assertForbidden();
        }
    }

    public function test_halaman_kalender_memakai_route_kalender_sendiri(): void
    {
        $data = $this->mahasiswaWithClass();
        $this->createTask($data['class'], 'Tugas Di Kalender Khusus', Carbon::now()->subDay(), Carbon::now()->addDay());

        $response = $this->actingAs($data['user'])->get(route('mahasiswa.kalender', [
            'view' => 'week',
            'date' => '2026-11-18',
            'tab' => 'timeline',
        ]));

        $response->assertOk()
            ->assertSee('Kalender Akademik')
            ->assertSee('Tugas Di Kalender Khusus')
            ->assertSee('jenis = ujian', false)
            ->assertSee('/mahasiswa/kalender', false);

        $this->assertSame('week', $response->viewData('currentView'));
        $this->assertTrue($response->viewData('calendar')['events']->contains('judul', 'Tugas Di Kalender Khusus'));
    }

    public function test_halaman_tugas_hanya_menampilkan_tugas_mahasiswa_sendiri(): void
    {
        $data = $this->mahasiswaWithClass();
        $tahun = $data['class']->tahunAkademik;
        $otherClass = $this->createClass($data['dosen'], $tahun, 'B');
        $otherStudent = User::factory()->mahasiswa()->create();
        $this->enroll($otherStudent, $otherClass);

        $this->createTask($data['class'], 'Tugas Halaman Saya', Carbon::now()->subDays(2), Carbon::now()->addDay());
        $this->createTask($otherClass, 'Tugas Mahasiswa Lain', Carbon::now()->subDays(2), Carbon::now()->addDay());

        $this->actingAs($data['user'])->get(route('mahasiswa.tugas'))
            ->assertOk()
            ->assertSee('Tugas Halaman Saya')
            ->assertSee('Dipublikasikan')
            ->assertSee('Deadline')
            ->assertDontSee('Tugas Mahasiswa Lain');
    }

    public function test_halaman_notifikasi_memisahkan_data_dan_tandai_dibaca_hanya_milik_sendiri(): void
    {
        $data = $this->mahasiswaWithClass();
        $mine = $this->createNotification($data['user'], 'Notifikasi Saya');
        $otherStudent = User::factory()->mahasiswa()->create();
        $theirs = $this->createNotification($otherStudent, 'Notifikasi Orang Lain');

        $this->actingAs($data['user'])->get(route('mahasiswa.notifikasi'))
            ->assertOk()
            ->assertSee('Notifikasi Saya')
            ->assertDontSee('Notifikasi Orang Lain');

        $this->actingAs($data['user'])
            ->post(route('mahasiswa.notifikasi.baca', $theirs->id))
            ->assertNotFound();

        $this->actingAs($data['user'])
            ->post(route('mahasiswa.notifikasi.baca', $mine->id))
            ->assertRedirect(route('mahasiswa.notifikasi'));

        $this->assertNotNull($mine->fresh()->dibaca_pada);
        $this->assertNull($theirs->fresh()->dibaca_pada);

        $this->actingAs($data['user'])
            ->get(route('mahasiswa.notifikasi', ['status' => 'sudah_dibaca']))
            ->assertOk()
            ->assertSee('Notifikasi Saya')
            ->assertDontSee('Notifikasi Orang Lain');
    }

    public function test_role_lain_tidak_dapat_post_mark_read(): void
    {
        $data = $this->mahasiswaWithClass();
        $notification = $this->createNotification($data['user'], 'Notifikasi Mahasiswa');

        foreach ([
            User::factory()->dosen()->create(),
            User::factory()->admin()->create(),
        ] as $user) {
            $this->actingAs($user)
                ->post(route('mahasiswa.notifikasi.baca', $notification->id))
                ->assertForbidden();
        }

        $this->assertNull($notification->fresh()->dibaca_pada);
    }

    public function test_mahasiswa_tidak_dapat_memengaruhi_notifikasi_mahasiswa_lain(): void
    {
        $data = $this->mahasiswaWithClass();
        $otherStudent = User::factory()->mahasiswa()->create();
        $theirs = $this->createNotification($otherStudent, 'Notifikasi Rahasia Mahasiswa Lain');

        $this->actingAs($data['user'])
            ->post(route('mahasiswa.notifikasi.baca', $theirs->id), [
                'user_id' => $data['user']->id,
                'dibaca_pada' => Carbon::now()->toDateTimeString(),
            ])
            ->assertNotFound();

        $fresh = $theirs->fresh();
        $this->assertNull($fresh->dibaca_pada);
        $this->assertSame($otherStudent->id, $fresh->user_id);
    }

    public function test_halaman_kalender_tidak_menampilkan_tugas_atau_agenda_kelas_lain(): void
    {
        $data = $this->mahasiswaWithClass();
        $tahun = $data['class']->tahunAkademik;
        $otherClass = $this->createClass($data['dosen'], $tahun, 'B');
        $otherStudent = User::factory()->mahasiswa()->create();
        $this->enroll($otherStudent, $otherClass);

        $this->createTask($data['class'], 'Tugas Kalender Saya', Carbon::now()->subDays(2), Carbon::now()->addDays(2));
        $this->createTask($otherClass, 'Tugas Kalender Orang Lain', Carbon::now()->subDays(2), Carbon::now()->addDays(2));
        $this->createAgenda($otherClass, 'Agenda Kalender Orang Lain', JenisAgenda::Akademik, Carbon::now());

        $response = $this->actingAs($data['user'])->get(route('mahasiswa.kalender', [
            'view' => 'month',
            'date' => '2026-11-01',
        ]));

        $events = collect($response->viewData('calendar')['events'])->pluck('judul');
        $bars = collect($response->viewData('timeline')['bars'])->pluck('judul');

        $response->assertOk()
            ->assertSee('Tugas Kalender Saya')
            ->assertDontSee('Tugas Kalender Orang Lain')
            ->assertDontSee('Agenda Kalender Orang Lain');

        $this->assertTrue($events->contains('Tugas Kalender Saya'));
        $this->assertFalse($events->contains('Tugas Kalender Orang Lain'));
        $this->assertFalse($events->contains('Agenda Kalender Orang Lain'));
        $this->assertFalse($bars->contains('Tugas Kalender Orang Lain'));
    }

    public function test_filter_notifikasi_tetap_dipertahankan_setelah_mark_read(): void
    {
        $data = $this->mahasiswaWithClass();
        $pertama = $this->createNotification($data['user'], 'Notifikasi Pertama');
        $kedua = $this->createNotification($data['user'], 'Notifikasi Kedua');

        $this->actingAs($data['user'])
            ->from(route('mahasiswa.notifikasi', ['status' => 'belum_dibaca']))
            ->post(route('mahasiswa.notifikasi.baca', $pertama->id), [
                'status' => 'belum_dibaca',
            ])
            ->assertRedirect(route('mahasiswa.notifikasi', ['status' => 'belum_dibaca']));

        $this->assertNotNull($pertama->fresh()->dibaca_pada);
        $this->assertNull($kedua->fresh()->dibaca_pada);

        $this->actingAs($data['user'])
            ->post(route('mahasiswa.notifikasi.baca', $kedua->id), [
                'status' => 'status_tidak_valid',
            ])
            ->assertRedirect(route('mahasiswa.notifikasi'));

        $this->assertNotNull($kedua->fresh()->dibaca_pada);
    }

    public function test_tab_timeline_tetap_aktif_setelah_filter_diterapkan(): void
    {
        $data = $this->mahasiswaWithClass();
        $this->createTask($data['class'], 'Tugas Filter Timeline', Carbon::now()->subDay(), Carbon::now()->addDay());

        $calendar = $this->actingAs($data['user'])->get(route('mahasiswa.kalender', [
            'view' => 'month',
            'date' => '2026-11-01',
            'jenis' => ['tugas'],
        ]));

        $calendar->assertOk()
            ->assertSee('name="tab" value="calendar"', false)
            ->assertSee('name="jenis[]"', false)
            ->assertSee('value="tugas"', false);

        $timeline = $this->actingAs($data['user'])->get(route('mahasiswa.kalender', [
            'view' => 'month',
            'date' => '2026-11-01',
            'jenis' => ['tugas'],
            'tab' => 'timeline',
        ]));

        $timeline->assertOk()
            ->assertSee('Rentang pengerjaan tugas')
            ->assertSee('Tugas Filter Timeline')
            ->assertDontSee('name="tab"', false);

        $this->assertSame(1, substr_count($timeline->getContent(), 'aria-selected="true"'));
    }

    public function test_tautan_notifikasi_eksternal_atau_berbahaya_ditolak(): void
    {
        $data = $this->mahasiswaWithClass();

        $safe = $this->createNotification($data['user'], 'Tautan Aman', '/mahasiswa/tugas');
        $dangerous = [
            '//evil.example/path',
            '/\\evil.example/path',
            'https://evil.example/path',
            'javascript:alert(1)',
            '/mahasiswa/../admin/dashboard',
            '/%2f%2fevil.example/path',
            '/mahasiswa%5c..%5cevil.example',
            '/admin/dashboard',
        ];

        foreach ($dangerous as $index => $tautan) {
            $this->createNotification($data['user'], 'Tautan Berbahaya '.$index, $tautan);
        }

        $this->assertSame('/mahasiswa/tugas', $safe->fresh()->tautanInternal());

        foreach (Notifikasi::where('user_id', $data['user']->id)->get() as $notification) {
            if ($notification->id !== $safe->id) {
                $this->assertNull($notification->tautanInternal());
            }
        }

        $this->actingAs($data['user'])->get(route('mahasiswa.notifikasi'))
            ->assertOk()
            ->assertSee('href="/mahasiswa/tugas"', false)
            ->assertDontSee('evil.example');

        $this->actingAs($data['user'])->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertDontSee('evil.example');
    }

    /**
     * @return array{user: User, dosen: User, class: KelasMataKuliah, enrollment: PesertaMataKuliah}
     */
    private function mahasiswaWithClass(): array
    {
        $dosen = User::factory()->dosen()->create();
        $tahun = $this->createTahunAkademik();
        $class = $this->createClass($dosen, $tahun, 'A');
        $user = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Uji',
            'nim' => '20260010',
        ]);
        $enrollment = $this->enroll($user, $class);

        $jadwal = new JadwalKuliah();
        $jadwal->forceFill([
            'kelas_mata_kuliah_id' => $class->id,
            'hari' => Carbon::now()->dayOfWeek,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:30:00',
            'ruang' => 'R. 101',
            'jenis' => 'kuliah',
        ])->save();

        return [
            'user' => $user,
            'dosen' => $dosen,
            'class' => $class,
            'enrollment' => $enrollment,
        ];
    }

    private function createTahunAkademik(): TahunAkademik
    {
        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => 2026,
            'tahun_selesai' => 2027,
            'semester' => 'ganjil',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-12-31',
            'aktif' => true,
        ])->save();

        return $tahun;
    }

    private function createClass(User $dosen, TahunAkademik $tahun, string $kode): KelasMataKuliah
    {
        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MK-'.$kode,
            'nama' => 'Mata Kuliah '.$kode,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        $class = new KelasMataKuliah();
        $class->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kode,
            'nama_kelas' => 'Kelas '.$kode,
            'kapasitas' => 30,
        ])->save();

        return $class;
    }

    private function enroll(User $user, KelasMataKuliah $class): PesertaMataKuliah
    {
        $enrollment = new PesertaMataKuliah();
        $enrollment->forceFill([
            'kelas_mata_kuliah_id' => $class->id,
            'mahasiswa_id' => $user->id,
            'status' => StatusPeserta::Aktif->value,
            'tanggal_daftar' => Carbon::now()->subWeek(),
        ])->save();

        return $enrollment;
    }

    private function createTask(
        KelasMataKuliah $class,
        string $judul,
        Carbon $published,
        Carbon $deadline,
    ): Tugas {
        $task = new Tugas();
        $task->forceFill([
            'kelas_mata_kuliah_id' => $class->id,
            'dibuat_oleh' => $class->dosen_id,
            'judul' => $judul,
            'deskripsi' => 'Deskripsi '.$judul,
            'dipublikasikan_pada' => $published,
            'deadline' => $deadline,
        ])->save();

        return $task;
    }

    private function createAgenda(
        KelasMataKuliah $class,
        string $judul,
        JenisAgenda $jenis,
        Carbon $date,
    ): AgendaAkademik {
        $agenda = new AgendaAkademik();
        $agenda->forceFill([
            'dibuat_oleh' => $class->dosen_id,
            'kelas_mata_kuliah_id' => $class->id,
            'judul' => $judul,
            'deskripsi' => 'Deskripsi '.$judul,
            'jenis' => $jenis->value,
            'tanggal_mulai' => $date->toDateString(),
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'lokasi' => 'R. 202',
            'dipublikasikan_pada' => Carbon::now()->subDay(),
        ])->save();

        return $agenda;
    }

    private function createNotification(User $user, string $judul, ?string $tautan = null): Notifikasi
    {
        $notification = new Notifikasi();
        $notification->forceFill([
            'user_id' => $user->id,
            'tipe' => 'tugas',
            'judul' => $judul,
            'pesan' => 'Pesan '.$judul,
            'tautan' => $tautan,
            'dibaca_pada' => null,
        ])->save();

        return $notification;
    }
}
