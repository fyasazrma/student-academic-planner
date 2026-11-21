<?php

namespace Tests\Feature;

use App\Enums\StatusPeserta;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\Notifikasi;
use App\Models\PengumpulanTugas;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\Tugas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DosenTugasTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private ?TahunAkademik $tahun = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        Carbon::setTestNow('2026-11-19 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_route_manajemen_tugas_hanya_untuk_dosen(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-A');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas A');
        $mahasiswa = User::factory()->mahasiswa()->create();
        $admin = User::factory()->admin()->create();

        $rute = [
            ['get', route('dosen.mata-kuliah.tugas.index', $kelas)],
            ['get', route('dosen.mata-kuliah.tugas.create', $kelas)],
            ['post', route('dosen.mata-kuliah.tugas.store', $kelas)],
            ['get', route('dosen.mata-kuliah.tugas.edit', [$kelas, $tugas])],
            ['put', route('dosen.mata-kuliah.tugas.update', [$kelas, $tugas])],
            ['delete', route('dosen.mata-kuliah.tugas.destroy', [$kelas, $tugas])],
            ['get', route('dosen.mata-kuliah.tugas.download', [$kelas, $tugas])],
        ];

        foreach ($rute as [$metode, $url]) {
            $this->{$metode}($url)->assertRedirect(route('login'));
        }

        foreach ($rute as [$metode, $url]) {
            $this->actingAs($mahasiswa)->{$metode}($url)->assertForbidden();
            $this->actingAs($admin)->{$metode}($url)->assertForbidden();
        }
    }

    public function test_dosen_tidak_dapat_mengakses_kelas_dan_tugas_dosen_lain(): void
    {
        $dosen = $this->dosen();
        $dosenLain = $this->dosen();
        $kelasLain = $this->kelas($dosenLain, 'TG-C');
        $tugasLain = $this->tugas($kelasLain, $dosenLain, 'Tugas Dosen Lain');
        $payload = $this->payload();

        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.index', $kelasLain))->assertNotFound();
        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.create', $kelasLain))->assertNotFound();
        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelasLain), $payload)->assertNotFound();
        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.edit', [$kelasLain, $tugasLain]))->assertNotFound();
        $this->actingAs($dosen)->put(route('dosen.mata-kuliah.tugas.update', [$kelasLain, $tugasLain]), $payload)->assertNotFound();
        $this->actingAs($dosen)->delete(route('dosen.mata-kuliah.tugas.destroy', [$kelasLain, $tugasLain]))->assertNotFound();
        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.download', [$kelasLain, $tugasLain]))->assertNotFound();

        $this->assertDatabaseCount('tugas', 1);
    }

    public function test_tugas_kelas_lain_tidak_dapat_diakses_lewat_url_kelas_sendiri(): void
    {
        $dosen = $this->dosen();
        $dosenLain = $this->dosen();
        $kelasSaya = $this->kelas($dosen, 'TG-D');
        $kelasLain = $this->kelas($dosenLain, 'TG-E');
        $tugasLain = $this->tugas($kelasLain, $dosenLain, 'Tugas Kelas Lain');
        $payload = $this->payload();

        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.edit', [$kelasSaya, $tugasLain]))->assertNotFound();
        $this->actingAs($dosen)->put(route('dosen.mata-kuliah.tugas.update', [$kelasSaya, $tugasLain]), $payload)->assertNotFound();
        $this->actingAs($dosen)->delete(route('dosen.mata-kuliah.tugas.destroy', [$kelasSaya, $tugasLain]))->assertNotFound();
        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.download', [$kelasSaya, $tugasLain]))->assertNotFound();

        $this->assertDatabaseHas('tugas', ['id' => $tugasLain->id, 'judul' => 'Tugas Kelas Lain']);
    }

    public function test_dosen_dapat_membuat_draft_then_mempublikasikan_dan_mengembalikan_ke_draft(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-F');
        $publikasi = '2026-11-20 08:00:00';
        $deadline = '2026-11-27 23:59:00';

        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
            'judul' => 'Tugas Draft',
            'status' => 'draft',
            'dipublikasikan_pada' => '2026-11-01 08:00:00',
            'deadline' => $deadline,
        ]))->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas))
            ->assertSessionHas('status', 'Tugas berhasil ditambahkan.');

        $tugas = Tugas::query()->firstOrFail();
        $this->assertSame('Tugas Draft', $tugas->judul);
        $this->assertSame($dosen->id, $tugas->dibuat_oleh);
        $this->assertSame($kelas->id, $tugas->kelas_mata_kuliah_id);
        $this->assertNull($tugas->dipublikasikan_pada);
        $this->assertSame($deadline, $tugas->deadline->toDateTimeString());
        $this->assertSame('75.00', (string) $tugas->nilai_maksimal);
        $this->assertTrue($tugas->izinkan_terlambat);

        $this->actingAs($dosen)->put(route('dosen.mata-kuliah.tugas.update', [$kelas, $tugas]), $this->payload([
            'judul' => 'Tugas Terpublikasi',
            'status' => 'dipublikasikan',
            'dipublikasikan_pada' => str_replace(' ', 'T', substr($publikasi, 0, 16)),
            'deadline' => $deadline,
        ]))->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        $tugas->refresh();
        $this->assertSame('Tugas Terpublikasi', $tugas->judul);
        $this->assertSame($publikasi, $tugas->dipublikasikan_pada->toDateTimeString());

        $this->actingAs($dosen)->put(route('dosen.mata-kuliah.tugas.update', [$kelas, $tugas]), $this->payload([
            'judul' => 'Tugas Kembali Draft',
            'status' => 'draft',
            'deadline' => $deadline,
        ]))->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        $tugas->refresh();
        $this->assertNull($tugas->dipublikasikan_pada);
        $this->assertSame('Tugas Kembali Draft', $tugas->judul);
    }

    public function test_notifikasi_publikasi_tugas_dihapus_saat_tugas_kembali_draft(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-NOTIF');
        $this->enroll(User::factory()->mahasiswa()->create(), $kelas);

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
                'judul' => 'Tugas Notifikasi Draft',
                'status' => 'dipublikasikan',
                'dipublikasikan_pada' => '2026-11-18T08:00',
                'deadline' => '2026-11-27T23:59',
            ]))
            ->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        $tugas = Tugas::query()->firstOrFail();
        $sumber = 'tugas:'.$tugas->getKey().':dipublikasikan';

        $this->assertSame(1, Notifikasi::query()->where('data->sumber', $sumber)->count());

        $this->actingAs($dosen)
            ->put(route('dosen.mata-kuliah.tugas.update', [$kelas, $tugas]), $this->payload([
                'judul' => 'Tugas Notifikasi Draft',
                'status' => 'draft',
                'deadline' => '2026-11-27T23:59',
            ]))
            ->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        $this->assertNull($tugas->refresh()->dipublikasikan_pada);
        $this->assertSame(0, Notifikasi::query()->where('data->sumber', $sumber)->count());
    }

    public function test_validasi_formulir_tugas_dan_manipulasi_field_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-G');
        $kelasLain = $this->kelas($this->dosen(), 'TG-H');

        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
            'judul' => '',
        ]))->assertSessionHasErrors('judul');
        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
            'deadline' => 'bukan-tanggal',
        ]))->assertSessionHasErrors('deadline');
        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
            'status' => 'dipublikasikan',
            'dipublikasikan_pada' => '2026-11-28T08:00',
            'deadline' => '2026-11-27 23:59:00',
        ]))->assertSessionHasErrors('dipublikasikan_pada');
        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
            'status' => 'dipublikasikan',
            'dipublikasikan_pada' => '',
        ]))->assertSessionHasErrors('dipublikasikan_pada');
        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
            'nilai_maksimal' => '101',
        ]))->assertSessionHasErrors('nilai_maksimal');
        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
            'nilai_maksimal' => '-1',
        ]))->assertSessionHasErrors('nilai_maksimal');
        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
            'status' => 'tidak-valid',
        ]))->assertSessionHasErrors('status');

        $this->assertDatabaseCount('tugas', 0);

        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.tugas.store', $kelas), $this->payload([
            'judul' => 'Tugas Aman',
            'kelas_mata_kuliah_id' => $kelasLain->id,
            'dibuat_oleh' => $kelasLain->dosen_id,
            'dipublikasikan_pada' => '2020-01-01 08:00:00',
        ]))->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        $tugas = Tugas::query()->firstOrFail();
        $this->assertSame($kelas->id, $tugas->kelas_mata_kuliah_id);
        $this->assertSame($dosen->id, $tugas->dibuat_oleh);
        $this->assertNull($tugas->dipublikasikan_pada);
    }

    public function test_upload_penggantian_penghapusan_dan_unduhan_lampiran_aman(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-I');

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.tugas.store', $kelas),
            $this->payload([], $this->pdf('Petunjuk.pdf', "%PDF-1.4\nVersi satu\n%%EOF\n")),
        )->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        $tugas = Tugas::query()->firstOrFail();
        $pathSatu = $tugas->lampiran_path;

        $this->assertNotNull($pathSatu);
        $this->assertStringStartsWith('tugas/'.$kelas->id.'/', $pathSatu);
        $this->assertSame('Petunjuk.pdf', $tugas->lampiran_nama);
        Storage::disk('local')->assertExists($pathSatu);

        $unduh = $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.download', [$kelas, $tugas]));
        $unduh->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString('Versi satu', $unduh->streamedContent());

        $this->actingAs($dosen)->put(
            route('dosen.mata-kuliah.tugas.update', [$kelas, $tugas]),
            $this->payload([], $this->pdf('Petunjuk Baru.pdf', "%PDF-1.4\nVersi dua\n%%EOF\n")),
        )->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        $tugas->refresh();
        $pathDua = $tugas->lampiran_path;

        $this->assertNotSame($pathSatu, $pathDua);
        $this->assertSame('Petunjuk Baru.pdf', $tugas->lampiran_nama);
        Storage::disk('local')->assertMissing($pathSatu);
        Storage::disk('local')->assertExists($pathDua);

        $this->actingAs($dosen)->put(
            route('dosen.mata-kuliah.tugas.update', [$kelas, $tugas]),
            $this->payload(['hapus_lampiran' => '1']),
        )->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        $tugas->refresh();
        $this->assertNull($tugas->lampiran_path);
        $this->assertNull($tugas->lampiran_nama);
        $this->assertNull($tugas->lampiran_mime);
        $this->assertNull($tugas->lampiran_size);
        Storage::disk('local')->assertMissing($pathDua);
        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.download', [$kelas, $tugas]))->assertNotFound();
    }

    public function test_lampiran_dengan_format_atau_ukuran_invalid_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-J');

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.tugas.store', $kelas),
            $this->payload([], UploadedFile::fake()->createWithContent('berkas.exe', 'MZ executable')),
        )->assertSessionHasErrors('lampiran');

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.tugas.store', $kelas),
            $this->payload([], UploadedFile::fake()->createWithContent('laporan.txt', "%PDF-1.4\n.pdf palsu\n%%EOF\n")),
        )->assertSessionHasErrors('lampiran');

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.tugas.store', $kelas),
            $this->payload([], UploadedFile::fake()->create('besar.pdf', 10241)),
        )->assertSessionHasErrors('lampiran');

        $this->assertDatabaseCount('tugas', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_unduh_lampiran_menolak_path_lintas_kelas(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-K');
        $kelasLain = $this->kelas($dosen, 'TG-L');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Dengan Path Lain');
        $path = 'tugas/'.$kelasLain->id.'/2026/11/'.'lampiran.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\nLampiran kelas lain\n%%EOF\n");
        $tugas->forceFill([
            'lampiran_path' => $path,
            'lampiran_nama' => 'lampiran.pdf',
            'lampiran_mime' => 'application/pdf',
            'lampiran_size' => 30,
        ])->save();

        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.download', [$kelas, $tugas]))->assertNotFound();
    }

    public function test_proses_tugas_tidak_menghapus_lampiran_path_kelas_lain(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-AMAN');
        $kelasLain = $this->kelas($dosen, 'TG-LAIN-AMAN');

        $pathSatu = 'tugas/'.$kelasLain->id.'/2026/11/lampiran-satu.pdf';
        $pathDua = 'tugas/'.$kelasLain->id.'/2026/11/lampiran-dua.pdf';
        Storage::disk('local')->put($pathSatu, "%PDF-1.4\nLampiran kelas lain satu\n%%EOF\n");
        Storage::disk('local')->put($pathDua, "%PDF-1.4\nLampiran kelas lain dua\n%%EOF\n");

        $tugasSatu = $this->tugas($kelas, $dosen, 'Tugas Ganti Lampiran');
        $tugasSatu->forceFill([
            'lampiran_path' => $pathSatu,
            'lampiran_nama' => 'lampiran-satu.pdf',
            'lampiran_mime' => 'application/pdf',
            'lampiran_size' => 40,
        ])->save();

        $tugasDua = $this->tugas($kelas, $dosen, 'Tugas Hapus Lampiran');
        $tugasDua->forceFill([
            'lampiran_path' => $pathDua,
            'lampiran_nama' => 'lampiran-dua.pdf',
            'lampiran_mime' => 'application/pdf',
            'lampiran_size' => 40,
        ])->save();

        $this->actingAs($dosen)
            ->put(route('dosen.mata-kuliah.tugas.update', [$kelas, $tugasSatu]), $this->payload([
                'judul' => 'Tugas Ganti Lampiran Aman',
            ], $this->pdf('Pengganti.pdf', "%PDF-1.4\nPengganti\n%%EOF\n")))
            ->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        Storage::disk('local')->assertExists($pathSatu);
        $tugasSatu->refresh();
        $this->assertNotSame($pathSatu, $tugasSatu->lampiran_path);
        $this->assertStringStartsWith('tugas/'.$kelas->id.'/', (string) $tugasSatu->lampiran_path);
        Storage::disk('local')->assertExists((string) $tugasSatu->lampiran_path);

        $this->actingAs($dosen)
            ->delete(route('dosen.mata-kuliah.tugas.destroy', [$kelas, $tugasDua]))
            ->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas));

        Storage::disk('local')->assertExists($pathDua);
        $this->assertDatabaseMissing('tugas', ['id' => $tugasDua->id]);
    }

    public function test_halaman_tugas_dosen_menampilkan_data_kelas_sendiri(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-M');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Kelas Saya', Carbon::parse('2026-11-18 08:00:00'));
        $terjadwal = $this->tugas($kelas, $dosen, 'Tugas Terjadwal', Carbon::parse('2026-11-25 08:00:00'));

        $response = $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.index', $kelas));

        $response->assertOk()
            ->assertViewIs('dosen.mata-kuliah.tugas.index')
            ->assertSee('Tugas Kelas Saya')
            ->assertSee('Tugas Terjadwal')
            ->assertSee('Draft')
            ->assertSee('Terjadwal');
        $this->assertTrue(collect($response->viewData('tugas'))->contains('id', $tugas->id));
        $this->assertTrue(collect($response->viewData('tugas'))->contains('id', $terjadwal->id));
        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.tugas.edit', [$kelas, $tugas]))
            ->assertOk()
            ->assertSee('18 Nov 2026 08:00');
        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.show', $kelas))->assertSee('Kelola tugas');
    }

    public function test_tugas_tidak_dapat_dihapus_setelah_memiliki_pengumpulan(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-N');
        $mahasiswa = User::factory()->mahasiswa()->create();
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => StatusPeserta::Aktif->value,
            'tanggal_daftar' => Carbon::now()->subWeek(),
        ])->save();
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Sudah Dikumpulkan');

        $pengumpulan = new PengumpulanTugas();
        $pengumpulan->forceFill([
            'tugas_id' => $tugas->id,
            'peserta_mata_kuliah_id' => $peserta->id,
            'dikumpulkan_pada' => Carbon::now(),
            'terlambat' => false,
        ])->save();

        $this->actingAs($dosen)->delete(route('dosen.mata-kuliah.tugas.destroy', [$kelas, $tugas]))
            ->assertRedirect(route('dosen.mata-kuliah.tugas.index', $kelas))
            ->assertSessionHas('error', 'Tugas tidak dapat dihapus karena sudah memiliki pengumpulan.');
        $this->assertDatabaseHas('tugas', ['id' => $tugas->id]);
    }

    public function test_mahasiswa_hanya_melihat_tugas_terpublikasi_dari_kelas_yang_diikuti(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'TG-O');
        $kelasLain = $this->kelas($this->dosen(), 'TG-P');
        $mahasiswa = User::factory()->mahasiswa()->create();
        $mahasiswaLain = User::factory()->mahasiswa()->create();
        $this->enroll($mahasiswa, $kelas);

        $draft = $this->tugas($kelas, $dosen, 'Tugas Draft Mahasiswa', null);
        $publik = $this->tugas(
            $kelas,
            $dosen,
            'Tugas Terpublikasi Mahasiswa',
            Carbon::parse('2026-11-18 08:00:00'),
        );
        $publikLain = $this->tugas(
            $kelasLain,
            $dosen,
            'Tugas Kelas Tidak Diikuti',
            Carbon::parse('2026-11-18 08:00:00'),
        );

        $dashboard = $this->actingAs($mahasiswa)->get(route('mahasiswa.dashboard', [
            'view' => 'month',
            'date' => '2026-11-01',
        ]));
        $dashboard->assertOk()
            ->assertSee('Tugas Terpublikasi Mahasiswa')
            ->assertDontSee('Tugas Draft Mahasiswa')
            ->assertDontSee('Tugas Kelas Tidak Diikuti');

        $calendar = $dashboard->viewData('calendar');
        $this->assertNotNull($calendar['events']->firstWhere('id', 'tugas-'.$publik->id));
        $this->assertNull($calendar['events']->firstWhere('id', 'tugas-'.$draft->id));
        $this->assertNull($calendar['events']->firstWhere('id', 'tugas-'.$publikLain->id));
        $this->assertArrayNotHasKey('tugas-'.$draft->id, $calendar['detailEvents']);

        $kalender = $this->actingAs($mahasiswa)->get(route('mahasiswa.kalender', [
            'view' => 'month',
            'date' => '2026-11-01',
        ]));
        $kalender->assertOk();
        $this->assertNull($kalender->viewData('calendar')['events']->firstWhere('id', 'tugas-'.$draft->id));

        $tugasPage = $this->actingAs($mahasiswa)->get(route('mahasiswa.tugas'));
        $tugasPage->assertOk()->assertSee('Tugas Terpublikasi Mahasiswa')->assertDontSee('Tugas Draft Mahasiswa');
        $this->assertTrue(collect($tugasPage->viewData('tasks'))->contains('id', $publik->id));
        $this->assertFalse(collect($tugasPage->viewData('tasks'))->contains('id', $draft->id));
        $this->assertFalse(collect($tugasPage->viewData('tasks'))->contains('id', $publikLain->id));

        $mahasiswaLainPage = $this->actingAs($mahasiswaLain)->get(route('mahasiswa.tugas'));
        $mahasiswaLainPage->assertOk()->assertDontSee('Tugas Terpublikasi Mahasiswa');
    }

    private function dosen(): User
    {
        return User::factory()->dosen()->create();
    }

    private function tahun(): TahunAkademik
    {
        if ($this->tahun !== null) {
            return $this->tahun;
        }

        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => 2026,
            'tahun_selesai' => 2027,
            'semester' => 'ganjil',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-12-31',
            'aktif' => true,
        ])->save();

        return $this->tahun = $tahun;
    }

    private function kelas(User $dosen, string $kode): KelasMataKuliah
    {
        $this->urutan++;
        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MK-TGS-'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Tugas '.$this->urutan,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $this->tahun()->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kode,
            'nama_kelas' => 'Kelas '.$kode,
            'kapasitas' => 30,
        ])->save();

        return $kelas;
    }

    private function enroll(User $mahasiswa, KelasMataKuliah $kelas): PesertaMataKuliah
    {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => StatusPeserta::Aktif->value,
            'tanggal_daftar' => Carbon::now()->subWeek(),
        ])->save();

        return $peserta;
    }

    private function tugas(
        KelasMataKuliah $kelas,
        User $dosen,
        string $judul,
        ?Carbon $dipublikasikan = null,
    ): Tugas {
        $tugas = new Tugas();
        $tugas->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'dibuat_oleh' => $dosen->id,
            'judul' => $judul,
            'deskripsi' => 'Deskripsi '.$judul,
            'dipublikasikan_pada' => $dipublikasikan,
            'deadline' => Carbon::parse('2026-11-30 23:59:00'),
        ])->save();

        return $tugas;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function payload(array $overrides = [], ?UploadedFile $lampiran = null): array
    {
        $payload = array_merge([
            'judul' => 'Tugas Uji',
            'deskripsi' => 'Deskripsi tugas uji.',
            'status' => 'draft',
            'dipublikasikan_pada' => '2026-11-20T08:00',
            'deadline' => '2026-11-27T23:59',
            'nilai_maksimal' => '75',
            'izinkan_terlambat' => '1',
        ], $overrides);

        if ($lampiran !== null) {
            $payload['lampiran'] = $lampiran;
        }

        return $payload;
    }

    private function pdf(string $nama, string $isi): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, $isi);
    }
}
