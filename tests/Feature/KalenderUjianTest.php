<?php

namespace Tests\Feature;

use App\Enums\JenisAgenda;
use App\Enums\JenisUjian;
use App\Enums\StatusPeserta;
use App\Models\AgendaAkademik;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Calendar\CalendarCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KalenderUjianTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $dosenSatu;
    private User $dosenDua;
    private User $mahasiswaSatu;
    private User $mahasiswaDua;
    private TahunAkademik $tahunAktif;
    private TahunAkademik $tahunLama;
    private KelasMataKuliah $kelasAktif;
    private KelasMataKuliah $kelasLama;
    private AgendaAkademik $utsInstitusional;
    private AgendaAkademik $uasInstitusional;
    private AgendaAkademik $utsLamaKelas;
    private AgendaAkademik $ujianTanpaPenanda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-11-18 09:00:00');

        $this->admin = User::factory()->admin()->create();
        $this->dosenSatu = User::factory()->dosen()->create();
        $this->dosenDua = User::factory()->dosen()->create();
        $this->mahasiswaSatu = User::factory()->mahasiswa()->create();
        $this->mahasiswaDua = User::factory()->mahasiswa()->create();

        $this->tahunAktif = $this->tahun(2026, true);
        $this->tahunLama = $this->tahun(2025, false, 'genap');

        $this->kelasAktif = $this->kelas(
            $this->dosenSatu,
            $this->tahunAktif,
            $this->mataKuliah('MK-PROG', 'Pemrograman Laravel'),
            'A01',
        );
        $this->kelasLama = $this->kelas(
            $this->dosenDua,
            $this->tahunLama,
            $this->mataKuliah('MK-DB', 'Database'),
            'B02',
        );

        $this->daftar($this->kelasAktif, $this->mahasiswaSatu);
        $this->daftar($this->kelasLama, $this->mahasiswaDua);

        $this->utsInstitusional = $this->ujianInstitusional($this->tahunAktif, JenisUjian::Uts, '2026-12-01');
        $this->uasInstitusional = $this->ujianInstitusional($this->tahunAktif, JenisUjian::Uas, '2026-12-10');
        $this->utsLamaKelas = $this->ujianKelas(
            $this->dosenDua,
            $this->kelasLama,
            JenisUjian::Uts,
            'UTS Database',
        );
        $this->ujianTanpaPenanda = $this->ujianKelas(
            $this->dosenSatu,
            $this->kelasAktif,
            null,
            'UTS Pemrograman',
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_kalender_mahasiswa_memakai_label_uts_uas_dan_id_kanonik_tanpa_duplikat(): void
    {
        $response = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.dashboard', [
            'view' => 'month',
            'date' => '2026-12-01',
        ]));

        $response->assertOk();
        $events = $response->viewData('calendar')['events'];

        $uts = $events->firstWhere('id', 'agenda-'.$this->utsInstitusional->id);
        $uas = $events->firstWhere('id', 'agenda-'.$this->uasInstitusional->id);
        $lama = $events->firstWhere('id', 'agenda-'.$this->ujianTanpaPenanda->id);

        $this->assertNotNull($uts);
        $this->assertSame(CalendarCategory::UTS, $uts['jenis']);
        $this->assertSame('UTS', $uts['label']);
        $this->assertSame('Agenda institusi', $uts['mata_kuliah']);
        $this->assertSame(1, $events->where('source_id', $this->utsInstitusional->id)->count());

        $this->assertNotNull($uas);
        $this->assertSame(CalendarCategory::UAS, $uas['jenis']);
        $this->assertSame('UAS', $uas['label']);
        $this->assertSame(1, $events->where('source_id', $this->uasInstitusional->id)->count());

        $this->assertNotNull($lama);
        $this->assertSame(CalendarCategory::UJIAN, $lama['jenis']);
        $this->assertSame('Ujian', $lama['label']);

        $this->assertFalse($events->contains('id', 'agenda-'.$this->utsLamaKelas->id));

        $detail = $response->viewData('calendar')['detailEvents'];
        $this->assertArrayHasKey('agenda-'.$this->utsInstitusional->id, $detail);
        $this->assertArrayHasKey('agenda-'.$this->uasInstitusional->id, $detail);
        $this->assertSame('UTS', $detail['agenda-'.$this->utsInstitusional->id]['label']);
        $this->assertSame('UAS', $detail['agenda-'.$this->uasInstitusional->id]['label']);
        $this->assertSame('Ujian', $detail['agenda-'.$this->ujianTanpaPenanda->id]['label']);
    }

    public function test_komponen_kalender_memakai_warna_tersendiri_untuk_uts_dan_uas(): void
    {
        $response = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.dashboard', [
            'view' => 'month',
            'date' => '2026-12-01',
        ]));
        $response->assertOk();

        $html = $response->getContent();
        $utsId = 'agenda-'.$this->utsInstitusional->id;
        $uasId = 'agenda-'.$this->uasInstitusional->id;

        $this->assertSame(2, preg_match_all(
            '/data-agenda-trigger="'.preg_quote($utsId, '/').'"[\s\S]{0,600}?bg-agenda-uts/',
            $html,
        ));
        $this->assertSame(2, preg_match_all(
            '/data-agenda-trigger="'.preg_quote($uasId, '/').'"[\s\S]{0,600}?bg-agenda-uas/',
            $html,
        ));
        $this->assertMatchesRegularExpression('/value="uas"[\s\S]{0,300}?bg-agenda-uas/', $html);
        $this->assertMatchesRegularExpression('/value="uts"[\s\S]{0,300}?bg-agenda-uts/', $html);
    }

    public function test_semua_mahasiswa_dari_kelas_berbeda_melihat_agenda_institusional_yang_sama(): void
    {
        $pertama = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.kalender', [
            'view' => 'month',
            'date' => '2026-12-01',
        ]));
        $kedua = $this->actingAs($this->mahasiswaDua)->get(route('mahasiswa.kalender', [
            'view' => 'month',
            'date' => '2026-12-01',
        ]));

        $pertama->assertOk();
        $kedua->assertOk();

        $idInstitusional = [
            'agenda-'.$this->utsInstitusional->id,
            'agenda-'.$this->uasInstitusional->id,
        ];

        foreach ([$pertama, $kedua] as $response) {
            $events = $response->viewData('calendar')['events'];
            $this->assertTrue($events->contains('id', $idInstitusional[0]));
            $this->assertTrue($events->contains('id', $idInstitusional[1]));
            $this->assertSame(1, $events->where('id', $idInstitusional[0])->count());
            $this->assertSame(1, $events->where('id', $idInstitusional[1])->count());
        }

        $eventsKedua = $kedua->viewData('calendar')['events'];
        $this->assertTrue($eventsKedua->contains('id', 'agenda-'.$this->utsLamaKelas->id));
        $this->assertFalse($eventsKedua->contains('id', 'agenda-'.$this->ujianTanpaPenanda->id));
    }

    public function test_filter_kategori_uts_uas_dan_ujian_berfungsi(): void
    {
        $response = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.kalender', [
            'view' => 'month',
            'date' => '2026-12-01',
            'jenis' => [CalendarCategory::UTS],
        ]));

        $response->assertOk();
        $events = $response->viewData('calendar')['events'];
        $this->assertNotCount(0, $events);
        $this->assertTrue($events->every(fn (array $event): bool => $event['jenis'] === CalendarCategory::UTS));
        $this->assertTrue($events->contains('id', 'agenda-'.$this->utsInstitusional->id));
        $this->assertFalse($events->contains('id', 'agenda-'.$this->uasInstitusional->id));
        $this->assertFalse($events->contains('id', 'agenda-'.$this->ujianTanpaPenanda->id));

        $response = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.kalender', [
            'view' => 'month',
            'date' => '2026-12-01',
            'jenis' => [CalendarCategory::UAS],
        ]));
        $events = $response->viewData('calendar')['events'];
        $this->assertNotCount(0, $events);
        $this->assertTrue($events->every(fn (array $event): bool => $event['jenis'] === CalendarCategory::UAS));
        $this->assertTrue($events->contains('id', 'agenda-'.$this->uasInstitusional->id));

        $response = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.kalender', [
            'view' => 'month',
            'date' => '2026-12-01',
            'jenis' => [CalendarCategory::UJIAN],
        ]));
        $events = $response->viewData('calendar')['events'];
        $this->assertNotCount(0, $events);
        $this->assertTrue($events->every(fn (array $event): bool => $event['jenis'] === CalendarCategory::UJIAN));
        $this->assertTrue($events->contains('id', 'agenda-'.$this->ujianTanpaPenanda->id));

        $label = collect($response->viewData('filterOptions'))->pluck('label');
        $this->assertTrue($label->contains('UTS'));
        $this->assertTrue($label->contains('UAS'));
        $this->assertTrue($label->contains('Ujian'));
    }

    public function test_timeline_menampilkan_penanda_uts_dan_uas_dengan_event_id_kanonik(): void
    {
        $response = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.dashboard', [
            'view' => 'month',
            'date' => '2026-12-01',
        ]));

        $response->assertOk();
        $markers = collect($response->viewData('timeline')['markers']);

        $uts = $markers->firstWhere('id', 'ujian-'.$this->utsInstitusional->id);
        $uas = $markers->firstWhere('id', 'ujian-'.$this->uasInstitusional->id);

        $this->assertNotNull($uts);
        $this->assertSame('agenda-'.$this->utsInstitusional->id, $uts['event_id']);
        $this->assertSame(CalendarCategory::UTS, $uts['jenis']);
        $this->assertSame('UTS', $uts['label']);

        $this->assertNotNull($uas);
        $this->assertSame('agenda-'.$this->uasInstitusional->id, $uas['event_id']);
        $this->assertSame(CalendarCategory::UAS, $uas['jenis']);
        $this->assertSame('UAS', $uas['label']);

        $this->assertSame(1, $markers->where('event_id', 'agenda-'.$this->utsInstitusional->id)->count());
        $this->assertSame(1, $markers->where('event_id', 'agenda-'.$this->uasInstitusional->id)->count());
    }

    public function test_kalender_dosen_dari_kelas_berbeda_melihat_agenda_institusional_yang_sama(): void
    {
        $pertama = $this->actingAs($this->dosenSatu)->get(route('dosen.dashboard', [
            'view' => 'month',
            'date' => '2026-12-01',
        ]));
        $kedua = $this->actingAs($this->dosenDua)->get(route('dosen.dashboard', [
            'view' => 'month',
            'date' => '2026-12-01',
        ]));

        $pertama->assertOk();
        $kedua->assertOk();

        foreach ([$pertama, $kedua] as $response) {
            $events = $response->viewData('calendar')['events'];
            $this->assertTrue($events->contains('id', 'agenda-'.$this->utsInstitusional->id));
            $this->assertTrue($events->contains('id', 'agenda-'.$this->uasInstitusional->id));
            $this->assertSame(1, $events->where('source_id', $this->utsInstitusional->id)->count());
            $this->assertSame(1, $events->where('source_id', $this->uasInstitusional->id)->count());
        }

        $eventsSatu = $pertama->viewData('calendar')['events'];
        $eventsDua = $kedua->viewData('calendar')['events'];

        $this->assertTrue($eventsSatu->contains('id', 'agenda-'.$this->ujianTanpaPenanda->id));
        $this->assertFalse($eventsSatu->contains('id', 'agenda-'.$this->utsLamaKelas->id));
        $this->assertTrue($eventsDua->contains('id', 'agenda-'.$this->utsLamaKelas->id));
        $this->assertFalse($eventsDua->contains('id', 'agenda-'.$this->ujianTanpaPenanda->id));
    }

    public function test_kalender_admin_melihat_seluruh_agenda_institusional_tanpa_duplikat(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'view' => 'month',
            'date' => '2026-12-01',
        ]));

        $response->assertOk();
        $events = $response->viewData('calendar')['events'];

        $this->assertTrue($events->contains('id', 'agenda-'.$this->utsInstitusional->id));
        $this->assertTrue($events->contains('id', 'agenda-'.$this->uasInstitusional->id));
        $this->assertSame(1, $events->where('source_id', $this->utsInstitusional->id)->count());
        $this->assertSame(1, $events->where('source_id', $this->uasInstitusional->id)->count());

        $this->assertFalse($events->contains('id', 'agenda-'.$this->utsLamaKelas->id));
        $this->assertFalse($events->contains('id', 'agenda-'.$this->ujianTanpaPenanda->id));

        $uts = $events->firstWhere('id', 'agenda-'.$this->utsInstitusional->id);
        $uas = $events->firstWhere('id', 'agenda-'.$this->uasInstitusional->id);
        $this->assertSame(CalendarCategory::UTS, $uts['jenis']);
        $this->assertSame('UTS', $uts['label']);
        $this->assertSame(CalendarCategory::UAS, $uas['jenis']);
        $this->assertSame('UAS', $uas['label']);

        $options = collect($response->viewData('filterOptions'))->keys();
        $this->assertTrue($options->contains(CalendarCategory::UTS));
        $this->assertTrue($options->contains(CalendarCategory::UAS));
    }

    public function test_halaman_jadwal_ujian_mahasiswa_hanya_memuat_agenda_institusional_dan_kelas_diikuti(): void
    {
        $response = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.jadwal-ujian'));

        $response->assertOk();
        $response->assertSee('Jadwal Ujian UTS & UAS');
        $response->assertSee('Hanya baca');

        $ids = collect($response->viewData('ujian'))->pluck('id');
        $this->assertTrue($ids->contains($this->utsInstitusional->id));
        $this->assertTrue($ids->contains($this->uasInstitusional->id));
        $this->assertTrue($ids->contains($this->ujianTanpaPenanda->id));
        $this->assertFalse($ids->contains($this->utsLamaKelas->id));

        $events = $response->viewData('calendar')['events'];
        $this->assertTrue($events->contains('id', 'agenda-'.$this->utsInstitusional->id));
        $this->assertTrue($events->contains('id', 'agenda-'.$this->ujianTanpaPenanda->id));
        $this->assertFalse($events->contains('id', 'agenda-'.$this->utsLamaKelas->id));

        $baris = collect($response->viewData('ujian'))->firstWhere('id', $this->utsInstitusional->id);
        $this->assertSame('UTS', $baris['label']);
        $this->assertSame(CalendarCategory::UTS, $baris['kategori']);
        $this->assertSame('Agenda institusi', $baris['konteks']);
    }

    public function test_halaman_jadwal_ujian_dosen_hanya_memuat_agenda_institusional_dan_kelas_diampu(): void
    {
        $response = $this->actingAs($this->dosenSatu)->get(route('dosen.jadwal-ujian'));

        $response->assertOk();
        $response->assertSee('Jadwal Ujian UTS & UAS');
        $response->assertSee('Hanya baca');

        $ids = collect($response->viewData('ujian'))->pluck('id');
        $this->assertTrue($ids->contains($this->utsInstitusional->id));
        $this->assertTrue($ids->contains($this->uasInstitusional->id));
        $this->assertTrue($ids->contains($this->ujianTanpaPenanda->id));
        $this->assertFalse($ids->contains($this->utsLamaKelas->id));

        $events = $response->viewData('calendar')['events'];
        $this->assertTrue($events->contains('id', 'agenda-'.$this->utsInstitusional->id));
        $this->assertFalse($events->contains('id', 'agenda-'.$this->utsLamaKelas->id));
    }

    public function test_filter_jenis_ujian_dan_tahun_akademik_pada_halaman_read_only(): void
    {
        $uts = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.jadwal-ujian', [
            'jenis_ujian' => 'uts',
        ]));
        $uts->assertOk();
        $utsIds = collect($uts->viewData('ujian'))->pluck('id');
        $this->assertTrue($utsIds->contains($this->utsInstitusional->id));
        $this->assertFalse($utsIds->contains($this->ujianTanpaPenanda->id));
        $this->assertFalse($utsIds->contains($this->uasInstitusional->id));

        $periodeAktif = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.jadwal-ujian', [
            'tahun_akademik' => $this->tahunAktif->id,
        ]));
        $periodeAktif->assertOk();
        $aktifIds = collect($periodeAktif->viewData('ujian'))->pluck('id');
        $this->assertTrue($aktifIds->contains($this->utsInstitusional->id));
        $this->assertFalse($aktifIds->contains($this->utsLamaKelas->id));

        $periodeKosong = $this->actingAs($this->mahasiswaSatu)->get(route('mahasiswa.jadwal-ujian', [
            'tahun_akademik' => $this->tahunLama->id,
        ]));
        $periodeKosong->assertOk();
        $this->assertSame([], $periodeKosong->viewData('ujian'));
        $periodeKosong->assertSee('Tidak ada jadwal ditemukan');
    }

    public function test_halaman_jadwal_ujian_dosen_dan_mahasiswa_read_only_tanpa_route_tulis(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dosen.jadwal-ujian.store'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dosen.jadwal-ujian.update'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dosen.jadwal-ujian.destroy'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('mahasiswa.jadwal-ujian.store'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('mahasiswa.jadwal-ujian.update'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('mahasiswa.jadwal-ujian.destroy'));

        foreach ([
            [$this->dosenSatu, 'dosen.jadwal-ujian'],
            [$this->mahasiswaSatu, 'mahasiswa.jadwal-ujian'],
        ] as [$user, $route]) {
            foreach (['post', 'put', 'patch', 'delete'] as $method) {
                $this->actingAs($user)->{$method}(route($route))->assertStatus(405);
            }

            $this->actingAs($user)
                ->post(route('admin.jadwal-ujian.store'))
                ->assertForbidden();
        }
    }

    private function daftar(KelasMataKuliah $kelas, User $mahasiswa): void
    {
        $pendaftaran = new PesertaMataKuliah();
        $pendaftaran->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => StatusPeserta::Aktif->value,
            'tanggal_daftar' => Carbon::now()->subWeek(),
        ])->save();
    }

    private function ujianInstitusional(
        TahunAkademik $tahun,
        JenisUjian $jenis,
        string $tanggal,
    ): AgendaAkademik {
        $periode = ucfirst($tahun->semester->value).' '.$tahun->tahun_mulai.'/'.$tahun->tahun_selesai;
        $judul = match ($jenis) {
            JenisUjian::Uts => 'Jadwal Ujian Tengah Semester (UTS) — '.$periode,
            JenisUjian::Uas => 'Jadwal Ujian Akhir Semester (UAS) — '.$periode,
        };

        $agenda = new AgendaAkademik();
        $agenda->forceFill([
            'dibuat_oleh' => $this->admin->id,
            'kelas_mata_kuliah_id' => null,
            'tahun_akademik_id' => $tahun->id,
            'judul' => $judul,
            'jenis' => JenisAgenda::Ujian->value,
            'jenis_ujian' => $jenis->value,
            'tanggal_mulai' => $tanggal,
            'tanggal_selesai' => $tanggal,
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'lokasi' => 'R. 202',
            'dipublikasikan_pada' => Carbon::now()->subDay(),
        ])->save();

        return $agenda;
    }

    private function ujianKelas(
        User $pembuat,
        KelasMataKuliah $kelas,
        ?JenisUjian $jenis,
        string $judul,
    ): AgendaAkademik {
        $agenda = new AgendaAkademik();
        $agenda->forceFill([
            'dibuat_oleh' => $pembuat->id,
            'kelas_mata_kuliah_id' => $kelas->id,
            'judul' => $judul,
            'jenis' => JenisAgenda::Ujian->value,
            'jenis_ujian' => $jenis?->value,
            'tanggal_mulai' => '2026-12-05',
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'lokasi' => 'R. 202',
            'dipublikasikan_pada' => Carbon::now()->subDay(),
        ])->save();

        return $agenda;
    }

    private function tahun(int $mulai, bool $aktif, string $semester = 'ganjil'): TahunAkademik
    {
        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => $mulai,
            'tahun_selesai' => $mulai + 1,
            'semester' => $semester,
            'tanggal_mulai' => $mulai.'-09-01',
            'tanggal_selesai' => $mulai.'-12-31',
            'aktif' => $aktif,
        ])->save();

        return $tahun;
    }

    private function mataKuliah(string $kode, string $nama): MataKuliah
    {
        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => $kode,
            'nama' => $nama,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        return $mataKuliah;
    }

    private function kelas(
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
            'kapasitas' => 30,
            'status_pendaftaran' => 'tutup',
        ])->save();

        return $kelas;
    }
}
