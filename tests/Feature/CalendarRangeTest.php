<?php

namespace Tests\Feature;

use App\Enums\JenisAgenda;
use App\Enums\StatusPeserta;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\MateriPembelajaran;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\Tugas;
use App\Models\User;
use App\Services\Calendar\CalendarCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarRangeTest extends TestCase
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

    public function test_tugas_memiliki_satu_event_logis_dengan_penanda_publikasi_dan_deadline_terpisah(): void
    {
        $student = $this->studentWithClass();
        $task = $this->task(
            $student['class'],
            'Tugas Satu Event',
            Carbon::parse('2026-11-14 08:00:00'),
            Carbon::parse('2026-11-20 18:00:00'),
        );

        $response = $this->dashboard('mahasiswa', $student['user'], [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $events = $response->viewData('calendar')['events']->where('id', 'tugas-'.$task->id)->values();

        $this->assertCount(1, $events);
        $this->assertSame('range', $events->first()['kind']);
        $this->assertSame(['publikasi', 'deadline'], collect($events->first()['markers'])->pluck('type')->all());
        $this->assertNotSame($events->first()['awal_label'], $events->first()['akhir_label']);
        $this->assertSame('Tanggal dipublikasikan', $events->first()['awal_label_name']);
        $this->assertSame('Deadline', $events->first()['akhir_label_name']);
    }

    public function test_tugas_tanpa_tanggal_publikasi_hanya_menjadi_penanda_deadline(): void
    {
        $student = $this->studentWithClass();
        $task = $this->task(
            $student['class'],
            'Tugas Tanpa Publikasi',
            null,
            Carbon::parse('2026-11-21 12:00:00'),
        );

        $response = $this->dashboard('mahasiswa', $student['user'], [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $calendar = $response->viewData('calendar');
        $event = $calendar['events']->firstWhere('id', 'tugas-'.$task->id);

        $this->assertNotNull($event);
        $this->assertSame(CalendarCategory::DEADLINE, $event['jenis']);
        $this->assertSame('point', $event['kind']);
        $this->assertSame(['deadline'], collect($event['markers'])->pluck('type')->all());
        $this->assertSame('2026-11-21', $event['start']->toDateString());
        $this->assertArrayHasKey('tugas-'.$task->id, $calendar['detailEvents']);
        $this->assertFalse(collect($response->viewData('tasks'))->contains('id', $task->id));
    }

    public function test_agenda_rangkap_menjadi_satu_event_logis_dan_tidak_digandakan_per_hari(): void
    {
        $admin = User::factory()->admin()->create();
        $agenda = $this->agenda(
            null,
            'Agenda Rentang Institusi',
            JenisAgenda::Akademik,
            Carbon::parse('2026-11-12'),
            Carbon::parse('2026-11-25'),
            true,
            $admin,
        );

        $response = $this->dashboard('admin', $admin, [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $calendar = $response->viewData('calendar');
        $events = $calendar['events']->where('id', 'agenda-'.$agenda->id)->values();
        $segments = $calendar['eventsByDate']->flatten(1)->where('event_id', 'agenda-'.$agenda->id)->values();

        $this->assertCount(1, $events);
        $this->assertSame('range', $events->first()['kind']);
        $this->assertSame('2026-11-12', $events->first()['start']->toDateString());
        $this->assertSame('2026-11-25', $events->first()['end']->toDateString());
        $this->assertCount(3, $segments);
        $this->assertCount(3, $segments->pluck('segment_key')->unique());
    }

    public function test_agenda_tanpa_tanggal_selesai_menjadi_event_satu_hari(): void
    {
        $admin = User::factory()->admin()->create();
        $agenda = $this->agenda(
            null,
            'Agenda Satu Hari',
            JenisAgenda::Pengumuman,
            Carbon::parse('2026-11-12'),
            null,
            true,
            $admin,
        );

        $response = $this->dashboard('admin', $admin, [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $calendar = $response->viewData('calendar');
        $event = $calendar['events']->firstWhere('id', 'agenda-'.$agenda->id);
        $segments = $calendar['eventsByDate']->flatten(1)->where('event_id', 'agenda-'.$agenda->id)->values();

        $this->assertNotNull($event);
        $this->assertSame('point', $event['kind']);
        $this->assertNull($event['end']);
        $this->assertSame('Tanggal agenda', $event['akhir_label_name']);
        $this->assertCount(1, $segments);
        $this->assertSame('2026-11-12', $segments->first()['tanggal']);
    }

    public function test_jadwal_kuliah_berulang_hanya_muncul_pada_hari_dan_jendela_tahun_akademiknya(): void
    {
        $student = $this->studentWithClass();
        $wednesday = $this->schedule($student['class'], 3);
        $thursday = $this->schedule($student['class'], 4);

        $expiredYear = $this->academicYear(true, '2026-08-01', '2026-11-15', 2025);
        $expiredClass = $this->courseClass($student['dosen'], $expiredYear, 'EX');
        $this->enroll($student['user'], $expiredClass);
        $expiredSchedule = $this->schedule($expiredClass, 3);

        $response = $this->dashboard('mahasiswa', $student['user'], [
            'view' => 'week',
            'date' => '2026-11-18',
        ]);

        $calendar = $response->viewData('calendar');
        $segments = $calendar['eventsByDate']->flatten(1);

        $this->assertSame(
            ['2026-11-18'],
            $segments->where('event_id', 'jadwal-'.$wednesday->id)->pluck('tanggal')->values()->all(),
        );
        $this->assertSame(
            ['2026-11-19'],
            $segments->where('event_id', 'jadwal-'.$thursday->id)->pluck('tanggal')->values()->all(),
        );
        $this->assertTrue($segments->where('event_id', 'jadwal-'.$expiredSchedule->id)->isEmpty());

        $event = $calendar['events']->firstWhere('id', 'jadwal-'.$wednesday->id);
        $this->assertSame('weekly', $event['recurrence']['type']);
        $this->assertSame('2026-08-01', $event['recurrence']['window_start']->toDateString());
        $this->assertSame('2026-12-31', $event['recurrence']['window_end']->toDateString());
    }

    public function test_materi_hanya_muncul_sebagai_titik_jika_sudah_dipublikasikan_dan_waktunya_tercapai(): void
    {
        $student = $this->studentWithClass();
        $published = $this->material($student['class'], 'Materi Sudah Dipublikasikan', Carbon::parse('2026-11-10 10:00:00'));
        $future = $this->material($student['class'], 'Materi Masa Depan', Carbon::parse('2026-11-25 10:00:00'));
        $unpublished = $this->material($student['class'], 'Materi Belum Dipublikasikan', null);

        $response = $this->dashboard('mahasiswa', $student['user'], [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $events = $response->viewData('calendar')['events'];
        $event = $events->firstWhere('id', 'materi-'.$published->id);

        $this->assertNotNull($event);
        $this->assertSame(CalendarCategory::MATERI, $event['jenis']);
        $this->assertSame('point', $event['kind']);
        $this->assertSame('2026-11-10', $event['start']->toDateString());
        $this->assertTrue($events->doesntContain('id', 'materi-'.$future->id));
        $this->assertTrue($events->doesntContain('id', 'materi-'.$unpublished->id));
    }

    public function test_filter_kategori_diterapkan_pada_event_logis_sebelum_segmen_dibuat(): void
    {
        $student = $this->studentWithClass();
        $task = $this->task($student['class'], 'Tugas Terfilter', Carbon::parse('2026-11-17 08:00:00'), Carbon::parse('2026-11-20 18:00:00'));
        $exam = $this->agenda($student['class'], 'Ujian Terfilter', JenisAgenda::Ujian, Carbon::parse('2026-11-19'));
        $material = $this->material($student['class'], 'Materi Terfilter', Carbon::parse('2026-11-16 10:00:00'));
        $schedule = $this->schedule($student['class'], 3);

        $response = $this->dashboard('mahasiswa', $student['user'], [
            'view' => 'month',
            'date' => '2026-11-01',
            'jenis' => ['tugas'],
        ]);

        $calendar = $response->viewData('calendar');
        $events = $calendar['events'];

        $this->assertSame(['tugas'], $response->viewData('activeFilters'));
        $this->assertCount(1, $events);
        $this->assertSame('tugas-'.$task->id, $events->first()['id']);
        $this->assertTrue($events->doesntContain('id', 'agenda-'.$exam->id));
        $this->assertTrue($events->doesntContain('id', 'materi-'.$material->id));
        $this->assertTrue($events->doesntContain('id', 'jadwal-'.$schedule->id));
        $this->assertArrayHasKey('agenda-'.$exam->id, $calendar['detailEvents']);
    }

    public function test_legenda_dan_filter_menggunakan_token_warna_dan_bentuk_terpusat(): void
    {
        $response = $this->dashboard('mahasiswa', $this->studentWithClass()['user']);
        $options = $response->viewData('filterOptions');

        $this->assertSame(CalendarCategory::options(CalendarCategory::MAHASISWA), $options);
        $this->assertSame('rentang', $options['tugas']['shape']);
        $this->assertSame('berulang', $options['kuliah']['shape']);
        $this->assertSame('titik', $options['ujian']['shape']);
        $this->assertSame('agenda-materi', $options['materi']['token']);
        $this->assertSame('Materi pembelajaran', $options['materi']['label']);
        $response->assertOk()
            ->assertSee('Materi pembelajaran')
            ->assertSee('bg-agenda-materi', false);
    }

    public function test_segmen_lintas_minggu_memiliki_ujung_membulat_hanya_di_batas_asli(): void
    {
        $admin = User::factory()->admin()->create();
        $agenda = $this->agenda(
            null,
            'Agenda Lintas Minggu',
            JenisAgenda::Akademik,
            Carbon::parse('2026-11-12'),
            Carbon::parse('2026-11-25'),
            true,
            $admin,
        );

        $response = $this->dashboard('admin', $admin, [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $segments = $response->viewData('calendar')['weeks']
            ->pluck('segments')
            ->flatten(1)
            ->where('event_id', 'agenda-'.$agenda->id)
            ->values();

        $this->assertCount(3, $segments);
        $this->assertSame('2026-11-12', $segments[0]['tanggal']);
        $this->assertSame('2026-11-25', $segments[2]['tanggal_akhir']);
        $this->assertFalse($segments[0]['continues_left']);
        $this->assertTrue($segments[0]['continues_right']);
        $this->assertTrue($segments[1]['continues_left']);
        $this->assertTrue($segments[1]['continues_right']);
        $this->assertTrue($segments[2]['continues_left']);
        $this->assertFalse($segments[2]['continues_right']);
    }

    public function test_segmen_lintas_bulan_mempertahankan_satu_event_id_dan_detail_yang_sama(): void
    {
        $student = $this->studentWithClass();
        $task = $this->task(
            $student['class'],
            'Tugas Lintas Bulan',
            Carbon::parse('2026-10-28 08:00:00'),
            Carbon::parse('2026-11-05 18:00:00'),
        );

        $response = $this->dashboard('mahasiswa', $student['user'], [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $calendar = $response->viewData('calendar');
        $segments = $calendar['eventsByDate']->flatten(1)->where('event_id', 'tugas-'.$task->id)->values();

        $this->assertCount(2, $segments);
        $this->assertSame('2026-10-28', $segments[0]['tanggal']);
        $this->assertSame('2026-11-05', $segments[1]['tanggal_akhir']);
        $this->assertNotSame($segments[0]['segment_key'], $segments[1]['segment_key']);
        $this->assertSame(
            $calendar['detailEvents']['tugas-'.$task->id],
            $calendar['detailEvents'][$segments[1]['event_id']],
        );
    }

    public function test_detail_modal_menggunakan_event_id_kanonik_yang_sama_di_kalender_dan_timeline(): void
    {
        $student = $this->studentWithClass();
        $task = $this->task($student['class'], 'Tugas Detail', Carbon::parse('2026-11-15 08:00:00'), Carbon::parse('2026-11-21 18:00:00'));
        $exam = $this->agenda($student['class'], 'Ujian Detail', JenisAgenda::Ujian, Carbon::parse('2026-11-20'));

        $response = $this->dashboard('mahasiswa', $student['user'], [
            'view' => 'month',
            'date' => '2026-11-01',
            'tab' => 'timeline',
        ]);

        $timeline = $response->viewData('timeline');
        $detailEvents = $response->viewData('calendar')['detailEvents'];

        $this->assertSame('tugas-'.$task->id, collect($timeline['bars'])->firstWhere('id', $task->id)['event_id']);
        $this->assertSame('agenda-'.$exam->id, collect($timeline['markers'])->first()['event_id']);
        $this->assertArrayHasKey('tugas-'.$task->id, $detailEvents);
        $this->assertArrayHasKey('agenda-'.$exam->id, $detailEvents);
        $response->assertOk()
            ->assertSee('data-agenda-trigger="tugas-'.$task->id.'"', false)
            ->assertSee('data-agenda-trigger="agenda-'.$exam->id.'"', false)
            ->assertDontSee('timeline-events-data', false);
    }

    public function test_mahasiswa_hanya_melihat_event_dari_kelas_yang_diikutinya(): void
    {
        $student = $this->studentWithClass();
        $otherClass = $this->courseClass($student['dosen'], $student['tahun'], 'B');
        $this->task($otherClass, 'Tugas Kelas Lain', Carbon::parse('2026-11-16 08:00:00'), Carbon::parse('2026-11-22 18:00:00'));
        $this->agenda($otherClass, 'Ujian Kelas Lain', JenisAgenda::Ujian, Carbon::parse('2026-11-19'));
        $this->material($otherClass, 'Materi Kelas Lain', Carbon::parse('2026-11-17 10:00:00'));

        $response = $this->dashboard('mahasiswa', $student['user'], [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $calendar = $response->viewData('calendar');
        $judul = $calendar['events']->pluck('judul');

        $this->assertFalse($judul->contains('Tugas Kelas Lain'));
        $this->assertFalse($judul->contains('Ujian Kelas Lain'));
        $this->assertFalse($judul->contains('Materi Kelas Lain'));
        $response->assertOk()
            ->assertDontSee('Tugas Kelas Lain')
            ->assertDontSee('Ujian Kelas Lain')
            ->assertDontSee('Materi Kelas Lain');
    }

    public function test_dosen_hanya_melihat_event_dari_kelas_yang_dia_ampu(): void
    {
        $dosen = User::factory()->dosen()->create();
        $tahun = $this->academicYear();
        $ownClass = $this->courseClass($dosen, $tahun, 'A');
        $this->task($ownClass, 'Tugas Dosen Sendiri', Carbon::parse('2026-11-16 08:00:00'), Carbon::parse('2026-11-22 18:00:00'));

        $otherDosen = User::factory()->dosen()->create();
        $otherClass = $this->courseClass($otherDosen, $tahun, 'B');
        $this->task($otherClass, 'Tugas Dosen Lain', Carbon::parse('2026-11-16 08:00:00'), Carbon::parse('2026-11-22 18:00:00'));
        $this->agenda($otherClass, 'Ujian Dosen Lain', JenisAgenda::Ujian, Carbon::parse('2026-11-19'));
        $this->material($otherClass, 'Materi Dosen Lain', Carbon::parse('2026-11-17 10:00:00'));

        $response = $this->dashboard('dosen', $dosen, [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $judul = $response->viewData('calendar')['events']->pluck('judul');

        $this->assertTrue($judul->contains('Tugas Dosen Sendiri'));
        $this->assertFalse($judul->contains('Tugas Dosen Lain'));
        $this->assertFalse($judul->contains('Ujian Dosen Lain'));
        $this->assertFalse($judul->contains('Materi Dosen Lain'));
        $response->assertOk()
            ->assertDontSee('Tugas Dosen Lain')
            ->assertDontSee('Ujian Dosen Lain')
            ->assertDontSee('Materi Dosen Lain');
    }

    public function test_admin_hanya_melihat_agenda_institusi_terpublikasi_serta_data_periode_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $activeYear = $this->academicYear();
        $inactiveYear = $this->academicYear(false, '2025-09-01', '2025-12-31', 2025);
        $dosen = User::factory()->dosen()->create();

        $this->agenda(null, 'Agenda Institusi Aktif', JenisAgenda::Akademik, Carbon::parse('2026-11-12'), null, true, $admin);
        $this->agenda(null, 'Agenda Institusi Belum Terbit', JenisAgenda::Pengumuman, Carbon::parse('2026-11-13'), null, false, $admin);
        $activeClass = $this->courseClass($dosen, $activeYear, 'A');
        $inactiveClass = $this->courseClass($dosen, $inactiveYear, 'B');
        $this->agenda($activeClass, 'Agenda Khusus Kelas', JenisAgenda::Ujian, Carbon::parse('2026-11-19'));

        $this->schedule($activeClass, 3);
        $this->schedule($inactiveClass, 3);
        $this->material($activeClass, 'Materi Periode Aktif', Carbon::parse('2026-11-16 10:00:00'));
        $this->material($inactiveClass, 'Materi Periode Lain', Carbon::parse('2026-11-16 10:00:00'));

        $response = $this->dashboard('admin', $admin, [
            'view' => 'month',
            'date' => '2026-11-01',
        ]);

        $calendar = $response->viewData('calendar');
        $judul = $calendar['events']->pluck('judul');

        $this->assertTrue($judul->contains('Agenda Institusi Aktif'));
        $this->assertTrue($judul->contains('Materi Periode Aktif'));
        $this->assertFalse($judul->contains('Agenda Institusi Belum Terbit'));
        $this->assertFalse($judul->contains('Agenda Khusus Kelas'));
        $this->assertFalse($judul->contains('Materi Periode Lain'));
        $this->assertCount(1, $calendar['events']->where('jenis', CalendarCategory::KULIAH));
        $response->assertOk()
            ->assertDontSee('Agenda Institusi Belum Terbit')
            ->assertDontSee('Agenda Khusus Kelas')
            ->assertDontSee('Materi Periode Lain');
    }

    private function dashboard(string $role, User $user, array $query = [])
    {
        return $this->actingAs($user)->get(route($role.'.dashboard', $query));
    }

    /**
     * @return array{user: User, dosen: User, tahun: TahunAkademik, class: KelasMataKuliah, enrollment: PesertaMataKuliah}
     */
    private function studentWithClass(): array
    {
        $dosen = User::factory()->dosen()->create();
        $tahun = $this->academicYear();
        $class = $this->courseClass($dosen, $tahun, 'A');
        $user = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Kalender',
            'nim' => '20260099',
        ]);

        return [
            'user' => $user,
            'dosen' => $dosen,
            'tahun' => $tahun,
            'class' => $class,
            'enrollment' => $this->enroll($user, $class),
        ];
    }

    private function academicYear(
        bool $active = true,
        string $start = '2026-08-01',
        string $end = '2026-12-31',
        int $tahunMulai = 2026,
    ): TahunAkademik {
        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => $tahunMulai,
            'tahun_selesai' => $tahunMulai + 1,
            'semester' => 'ganjil',
            'tanggal_mulai' => $start,
            'tanggal_selesai' => $end,
            'aktif' => $active,
        ])->save();

        return $tahun;
    }

    private function courseClass(User $dosen, TahunAkademik $tahun, string $kode): KelasMataKuliah
    {
        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MK-RANGE-'.$kode,
            'nama' => 'Mata Kuliah Range '.$kode,
            'sks' => 3,
            'aktif' => true,
        ])->save();

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

    private function enroll(User $user, KelasMataKuliah $kelas): PesertaMataKuliah
    {
        $enrollment = new PesertaMataKuliah();
        $enrollment->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $user->id,
            'status' => StatusPeserta::Aktif->value,
            'tanggal_daftar' => Carbon::now()->subWeek(),
        ])->save();

        return $enrollment;
    }

    private function task(KelasMataKuliah $kelas, string $judul, ?Carbon $published, Carbon $deadline): Tugas
    {
        $task = new Tugas();
        $task->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'dibuat_oleh' => $kelas->dosen_id,
            'judul' => $judul,
            'deskripsi' => 'Deskripsi '.$judul,
            'dipublikasikan_pada' => $published,
            'deadline' => $deadline,
        ])->save();

        return $task;
    }

    private function agenda(
        ?KelasMataKuliah $kelas,
        string $judul,
        JenisAgenda $jenis,
        Carbon $start,
        ?Carbon $end = null,
        bool $published = true,
        ?User $creator = null,
    ): AgendaAkademik {
        $agenda = new AgendaAkademik();
        $agenda->forceFill([
            'dibuat_oleh' => $creator?->id ?? $kelas?->dosen_id,
            'kelas_mata_kuliah_id' => $kelas?->id,
            'judul' => $judul,
            'deskripsi' => 'Deskripsi '.$judul,
            'jenis' => $jenis->value,
            'tanggal_mulai' => $start->toDateString(),
            'tanggal_selesai' => $end?->toDateString(),
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'lokasi' => 'R. Kalender',
            'dipublikasikan_pada' => $published ? Carbon::now()->subDay() : null,
        ])->save();

        return $agenda;
    }

    private function schedule(KelasMataKuliah $kelas, int $hari): JadwalKuliah
    {
        return JadwalKuliah::query()->create([
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => $hari,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:30:00',
            'ruang' => 'R. '.$kelas->kode_kelas,
            'jenis' => 'kuliah',
        ]);
    }

    private function material(KelasMataKuliah $kelas, string $judul, ?Carbon $published): MateriPembelajaran
    {
        $material = new MateriPembelajaran();
        $material->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'dibuat_oleh' => $kelas->dosen_id,
            'judul' => $judul,
            'deskripsi' => 'Deskripsi '.$judul,
            'file_path' => 'materi/'.str($judul)->slug().'.pdf',
            'file_name' => str($judul)->slug().'.pdf',
            'dipublikasikan_pada' => $published,
        ])->save();

        return $material;
    }
}
