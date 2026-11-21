<?php

namespace Tests\Feature;

use App\Enums\JenisNotifikasi;
use App\Enums\JenisUjian;
use App\Enums\StatusPeserta;
use App\Models\AgendaAkademik;
use App\Models\MateriPembelajaran;
use App\Models\Notifikasi;
use App\Models\Tugas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BatchDData;
use Tests\TestCase;

class NotifikasiOtomatisBatchDTest extends TestCase
{
    use BatchDData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        Carbon::setTestNow('2026-11-18 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_notifikasi_tugas_hanya_untuk_peserta_aktif_dan_dua_pemicu_deadline(): void
    {
        $dosen = $this->dosenBatchD();
        $kelas = $this->kelasBatchD($dosen);
        $kelasLain = $this->kelasBatchD($dosen);
        $aktif = $this->mahasiswaBatchD();
        $nonaktif = $this->mahasiswaBatchD();
        $diKelasLain = $this->mahasiswaBatchD();
        $this->pesertaBatchD($aktif, $kelas);
        $this->pesertaBatchD($nonaktif, $kelas, StatusPeserta::Nonaktif);
        $this->pesertaBatchD($diKelasLain, $kelasLain);

        $url = route('dosen.mata-kuliah.tugas.store', $kelas);
        $this->actingAs($dosen)
            ->post($url, $this->payloadTugas('Tugas Notifikasi', 'draft'))
            ->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));
        $this->assertSame(0, Notifikasi::count());

        $tugas = Tugas::query()->firstOrFail();
        $url = route('dosen.mata-kuliah.tugas.update', [$kelas, $tugas]);
        $dipublikasikan = $this->payloadTugas('Tugas Notifikasi', 'dipublikasikan');

        $this->actingAs($dosen)->put($url, $dipublikasikan)->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));
        $this->assertSame(1, Notifikasi::count());
        $notifikasi = Notifikasi::firstOrFail();
        $this->assertSame($aktif->id, (int) $notifikasi->user_id);
        $this->assertSame(JenisNotifikasi::Tugas->value, $notifikasi->tipe);
        $this->assertSame('Tugas baru: Tugas Notifikasi', $notifikasi->judul);
        $this->assertSame('/mahasiswa/tugas', $notifikasi->tautan);
        $this->assertSame('tugas:'.$tugas->id.':dipublikasikan', $notifikasi->data['sumber']);

        // Simpan ulang tanpa perubahan tidak membuat duplikat.
        $this->actingAs($dosen)->put($url, $dipublikasikan)->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));
        $this->assertSame(1, Notifikasi::count());

        // Perubahan judul saja bukan pemicu notifikasi.
        $revisiJudul = $this->payloadTugas('Tugas Notifikasi Revisi', 'dipublikasikan');
        $this->actingAs($dosen)->put($url, $revisiJudul)->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));
        $this->assertSame(1, Notifikasi::count());

        $deadlineBaru = '2026-11-26T23:59';
        $this->actingAs($dosen)
            ->put($url, $this->payloadTugas('Tugas Notifikasi Revisi', 'dipublikasikan', [
                'deadline' => $deadlineBaru,
            ]))
            ->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        $this->assertSame(2, Notifikasi::count());
        $perubahanDeadline = Notifikasi::query()
            ->where('data->sumber', 'tugas:'.$tugas->id.':deadline:'.Carbon::parse($deadlineBaru)->toDateTimeString())
            ->firstOrFail();
        $this->assertSame($aktif->id, (int) $perubahanDeadline->user_id);
        $this->assertSame('Deadline tugas diubah: Tugas Notifikasi Revisi', $perubahanDeadline->judul);
        $this->assertSame(0, Notifikasi::untukUser($nonaktif->id)->count());
        $this->assertSame(0, Notifikasi::untukUser($diKelasLain->id)->count());
    }

    public function test_notifikasi_jadwal_ujian_uts_dan_uas_menjaga_deduplikasi(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahunBatchD();
        $aktif = $this->mahasiswaBatchD();
        $aktifLain = $this->mahasiswaBatchD();
        $nonaktif = $this->mahasiswaBatchD(['is_active' => false]);

        $uts = $this->payloadUjianBatchD($tahun, [
            'judul' => 'Jadwal Ujian Tengah Semester (UTS) — Ganjil 2026/2027',
        ]);
        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $uts)
            ->assertRedirect(route('admin.jadwal-ujian.index'));
        $this->assertSame(2, Notifikasi::count());
        $notifikasiUts = Notifikasi::firstOrFail();
        $this->assertSame($aktif->id, (int) $notifikasiUts->user_id);
        $this->assertSame(JenisNotifikasi::Ujian->value, $notifikasiUts->tipe);
        $this->assertSame('Jadwal ujian ditambahkan: Jadwal Ujian Tengah Semester (UTS) — Ganjil 2026/2027', $notifikasiUts->judul);
        $this->assertSame('/mahasiswa/jadwal-ujian', $notifikasiUts->tautan);
        $this->assertSame(0, Notifikasi::untukUser($nonaktif->id)->count());

        $uas = $this->payloadUjianBatchD($tahun, [
            'jenis_ujian' => JenisUjian::Uas->value,
            'judul' => 'Jadwal Ujian Akhir Semester (UAS) — Ganjil 2026/2027',
            'tanggal_mulai' => '2026-12-03',
            'tanggal_selesai' => '2026-12-03',
            'lokasi' => 'Aula Batch D UAS',
        ]);
        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $uas)
            ->assertRedirect(route('admin.jadwal-ujian.index'));
        $this->assertSame(4, Notifikasi::count());
        $this->assertSame(2, Notifikasi::query()->where('judul', 'like', 'Jadwal ujian ditambahkan: %UAS%')->count());

        $ujianUts = AgendaAkademik::query()->where('jenis_ujian', JenisUjian::Uts->value)->firstOrFail();
        $url = route('admin.jadwal-ujian.update', $ujianUts);

        $this->actingAs($admin)->put($url, $uts)->assertRedirect(route('admin.jadwal-ujian.index'));
        $this->assertSame(4, Notifikasi::count());

        $this->actingAs($admin)
            ->put($url, array_merge($uts, [
                'tanggal_mulai' => '2026-12-02',
                'tanggal_selesai' => '2026-12-02',
                'lokasi' => 'Aula Batch D Revisi',
            ]))
            ->assertRedirect(route('admin.jadwal-ujian.index'));
        $this->assertSame(6, Notifikasi::count());
        $this->assertSame(2, Notifikasi::query()
            ->where('judul', 'Jadwal ujian diperbarui: Jadwal Ujian Tengah Semester (UTS) — Ganjil 2026/2027')
            ->whereIn('user_id', [$aktif->id, $aktifLain->id])
            ->count());
    }

    public function test_notifikasi_nilai_uts_dan_uas_hanya_untuk_pemilik_dan_tidak_duplikat(): void
    {
        $dosen = $this->dosenBatchD();
        $kelas = $this->kelasBatchD($dosen);
        $mahasiswa = $this->mahasiswaBatchD();
        $lain = $this->mahasiswaBatchD();
        $peserta = $this->pesertaBatchD($mahasiswa, $kelas);
        $this->pesertaBatchD($lain, $kelas);

        $urlUts = route('dosen.nilai.simpan', [$kelas->id, $peserta->id, 'uts']);
        $this->actingAs($dosen)
            ->post($urlUts, ['nilai' => '85', 'alasan' => 'Nilai awal UTS'])
            ->assertSessionHas('status', 'Nilai berhasil ditambahkan.');

        $this->assertSame(1, Notifikasi::count());
        $notifikasi = Notifikasi::firstOrFail();
        $this->assertSame($mahasiswa->id, (int) $notifikasi->user_id);
        $this->assertSame(JenisNotifikasi::Nilai->value, $notifikasi->tipe);
        $this->assertSame('Nilai UTS tersedia', $notifikasi->judul);
        $this->assertSame('/mahasiswa/nilai', $notifikasi->tautan);
        $this->assertStringStartsWith('nilai:', (string) $notifikasi->data['sumber']);

        $this->actingAs($dosen)
            ->post($urlUts, ['nilai' => '85', 'alasan' => 'Tanpa perubahan nilai'])
            ->assertSessionHas('status', 'Nilai berhasil diperbarui.');
        $this->assertSame(1, Notifikasi::count());

        Carbon::setTestNow('2026-11-18 09:00:01');
        $this->actingAs($dosen)
            ->post($urlUts, ['nilai' => '90', 'alasan' => 'Koreksi UTS'])
            ->assertSessionHas('status', 'Nilai berhasil diperbarui.');
        $this->assertSame(2, Notifikasi::count());

        Carbon::setTestNow('2026-11-18 09:00:02');
        $urlUas = route('dosen.nilai.simpan', [$kelas->id, $peserta->id, 'uas']);
        $this->actingAs($dosen)
            ->post($urlUas, ['nilai' => '88', 'alasan' => 'Nilai UAS'])
            ->assertSessionHas('status', 'Nilai berhasil ditambahkan.');
        $this->assertSame(3, Notifikasi::count());
        $this->assertSame(1, Notifikasi::query()->where('judul', 'Nilai UAS tersedia')->count());
        $this->assertSame(0, Notifikasi::untukUser($lain->id)->count());
    }

    public function test_aktivitas_baca_submit_dan_unduh_tidak_menambah_notifikasi(): void
    {
        $dosen = $this->dosenBatchD();
        $kelas = $this->kelasBatchD($dosen);
        $mahasiswa = $this->mahasiswaBatchD();
        $this->pesertaBatchD($mahasiswa, $kelas);

        $notifikasi = new Notifikasi();
        $notifikasi->forceFill([
            'user_id' => $mahasiswa->id,
            'tipe' => JenisNotifikasi::Pengumuman->value,
            'judul' => 'Notifikasi awal',
            'pesan' => 'Pesan awal',
            'data' => ['sumber' => 'uji:negatif'],
            'tautan' => '/mahasiswa/pengumuman',
        ])->save();

        $tugas = new Tugas();
        $tugas->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'dibuat_oleh' => $dosen->id,
            'judul' => 'Tugas untuk diunduh',
            'dipublikasikan_pada' => now()->subDay(),
            'deadline' => now()->addDays(3),
        ])->save();

        $pathMateri = 'materi/'.$kelas->id.'/2026/11/uji.pdf';
        Storage::disk('local')->put($pathMateri, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $materi = new MateriPembelajaran();
        $materi->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'dibuat_oleh' => $dosen->id,
            'judul' => 'Materi untuk diunduh',
            'file_path' => $pathMateri,
            'file_name' => 'uji.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => 32,
            'dipublikasikan_pada' => now()->subDay(),
        ])->save();

        $this->actingAs($mahasiswa)->get(route('mahasiswa.notifikasi'))->assertOk();
        $this->actingAs($mahasiswa)->get(route('mahasiswa.tugas'))->assertOk();
        $this->actingAs($mahasiswa)->get(route('mahasiswa.materi.index'))->assertOk();
        $this->actingAs($mahasiswa)->get(route('mahasiswa.materi.show', [$kelas->id, $materi->id]))->assertOk();
        $this->actingAs($mahasiswa)->get(route('mahasiswa.materi.unduh', [$kelas->id, $materi->id]))->assertOk();
        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.materi.progres.selesai', [$kelas->id, $materi->id]))
            ->assertSessionHas('status', 'Materi ditandai sudah dipelajari.');
        $this->actingAs($mahasiswa)->get(route('mahasiswa.tugas.pengumpulan', $tugas->id))->assertOk();
        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                'lampiran' => UploadedFile::fake()->createWithContent(
                    'jawaban.pdf',
                    "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF",
                ),
                'catatan' => 'Jawaban uji',
            ])
            ->assertRedirect(route('mahasiswa.tugas'));
        $this->actingAs($mahasiswa)->get(route('mahasiswa.tugas.pengumpulan.unduh', $tugas->id))->assertOk();

        $this->assertSame(2, Notifikasi::count());
        $notifikasiDosen = Notifikasi::untukUser($dosen->id)->firstOrFail();
        $this->assertStringStartsWith('Pengumpulan tugas baru:', $notifikasiDosen->judul);
        $this->assertStringStartsWith('/dosen/', (string) $notifikasiDosen->tautan);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadTugas(string $judul, string $status, array $overrides = []): array
    {
        return array_merge([
            'judul' => $judul,
            'deskripsi' => 'Deskripsi tugas notifikasi.',
            'status' => $status,
            'dipublikasikan_pada' => '2026-11-18T08:00',
            'deadline' => '2026-11-25T23:59',
            'nilai_maksimal' => '100',
            'izinkan_terlambat' => '0',
        ], $overrides);
    }
}
