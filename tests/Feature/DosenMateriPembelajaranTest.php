<?php

namespace Tests\Feature;

use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\MateriPembelajaran;
use App\Models\PesertaMataKuliah;
use App\Models\ProgresMateri;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Dosen\MateriFileService;
use App\Services\Dosen\MateriPembelajaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DosenMateriPembelajaranTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_guest_tidak_dapat_mengakses_route_materi(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen);

        $routes = [
            ['get', route('dosen.mata-kuliah.materi.index', $kelas), []],
            ['get', route('dosen.mata-kuliah.materi.create', $kelas), []],
            ['post', route('dosen.mata-kuliah.materi.store', $kelas), $this->payload([], $this->pdf())],
            ['get', route('dosen.mata-kuliah.materi.edit', [$kelas, $materi]), []],
            ['put', route('dosen.mata-kuliah.materi.update', [$kelas, $materi]), $this->payload([], $this->pdf())],
            ['delete', route('dosen.mata-kuliah.materi.destroy', [$kelas, $materi]), []],
            ['get', route('dosen.mata-kuliah.materi.download', [$kelas, $materi]), []],
        ];

        foreach ($routes as [$method, $url, $data]) {
            $this->{$method}($url, $data)->assertRedirect(route('login'));
        }
    }

    public function test_mahasiswa_dan_admin_tidak_dapat_mengakses_route_materi(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen);

        $routes = [
            ['get', route('dosen.mata-kuliah.materi.index', $kelas), []],
            ['get', route('dosen.mata-kuliah.materi.create', $kelas), []],
            ['post', route('dosen.mata-kuliah.materi.store', $kelas), $this->payload([], $this->pdf())],
            ['get', route('dosen.mata-kuliah.materi.edit', [$kelas, $materi]), []],
            ['put', route('dosen.mata-kuliah.materi.update', [$kelas, $materi]), $this->payload([], $this->pdf())],
            ['delete', route('dosen.mata-kuliah.materi.destroy', [$kelas, $materi]), []],
            ['get', route('dosen.mata-kuliah.materi.download', [$kelas, $materi]), []],
        ];

        foreach ([User::factory()->mahasiswa()->create(), User::factory()->admin()->create()] as $user) {
            foreach ($routes as [$method, $url, $data]) {
                $this->actingAs($user)->{$method}($url, $data)->assertForbidden();
            }
        }

        $this->assertDatabaseHas('materi_pembelajaran', ['id' => $materi->id]);
    }

    public function test_dosen_pemilik_dapat_mengakses_halaman_dan_mengunduh_materinya(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen, 'Materi Minggu Pertama', null, "%PDF-1.4\nIsi materi.\n%%EOF\n");

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.materi.index', $kelas))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.materi.index')
            ->assertSee('Materi Minggu Pertama')
            ->assertSee('Draft');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.materi.create', $kelas))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.materi.create');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.materi.edit', [$kelas, $materi]))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.materi.edit')
            ->assertSee('Materi Minggu Pertama');

        $respons = $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.materi.download', [$kelas, $materi]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('no-store', (string) $respons->headers->get('Cache-Control'));
        $this->assertSame("%PDF-1.4\nIsi materi.\n%%EOF\n", $respons->streamedContent());
    }

    public function test_kelas_dosen_lain_menghasilkan_404_pada_semua_aksi_materi(): void
    {
        $dosen = $this->dosen();
        $dosenLain = $this->dosen();
        $kelasLain = $this->kelas($dosenLain, $this->tahun(), $this->mataKuliah(), 'B02');
        $materiLain = $this->materi($kelasLain, $dosenLain);

        $routes = [
            ['get', route('dosen.mata-kuliah.materi.index', $kelasLain), []],
            ['get', route('dosen.mata-kuliah.materi.create', $kelasLain), []],
            ['post', route('dosen.mata-kuliah.materi.store', $kelasLain), $this->payload([], $this->pdf())],
            ['get', route('dosen.mata-kuliah.materi.edit', [$kelasLain, $materiLain]), []],
            ['put', route('dosen.mata-kuliah.materi.update', [$kelasLain, $materiLain]), $this->payload([], $this->pdf())],
            ['delete', route('dosen.mata-kuliah.materi.destroy', [$kelasLain, $materiLain]), []],
            ['get', route('dosen.mata-kuliah.materi.download', [$kelasLain, $materiLain]), []],
        ];

        foreach ($routes as [$method, $url, $data]) {
            $this->actingAs($dosen)->{$method}($url, $data)->assertNotFound();
        }

        $this->assertDatabaseHas('materi_pembelajaran', ['id' => $materiLain->id]);
        $this->assertSame(1, MateriPembelajaran::query()->count());
    }

    public function test_materi_dari_kelas_lain_menghasilkan_404_walaupun_kelas_url_owned(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $kelasLain = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'B02');
        $materiLain = $this->materi($kelasLain, $dosen);

        $routes = [
            ['get', route('dosen.mata-kuliah.materi.edit', [$kelas, $materiLain]), []],
            ['put', route('dosen.mata-kuliah.materi.update', [$kelas, $materiLain]), $this->payload([], $this->pdf())],
            ['delete', route('dosen.mata-kuliah.materi.destroy', [$kelas, $materiLain]), []],
            ['get', route('dosen.mata-kuliah.materi.download', [$kelas, $materiLain]), []],
        ];

        foreach ($routes as [$method, $url, $data]) {
            $this->actingAs($dosen)->{$method}($url, $data)->assertNotFound();
        }

        $this->assertDatabaseHas('materi_pembelajaran', [
            'id' => $materiLain->id,
            'kelas_mata_kuliah_id' => $kelasLain->id,
        ]);
    }

    public function test_payload_manipulatif_tidak_dapat_mengubah_pemilik_kelas_status_dan_path(): void
    {
        $dosen = $this->dosen();
        $dosenLain = $this->dosen();
        $tahun = $this->tahun();
        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $kelasLain = $this->kelas($dosenLain, $tahun, $this->mataKuliah(), 'B02');

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload([
                'status' => 'dipublikasikan',
                'kelas_mata_kuliah_id' => $kelasLain->id,
                'dibuat_oleh' => $dosenLain->id,
                'file_path' => '../../rahasia.pdf',
                'file_name' => '../../etc/passwd',
                'file_mime' => 'application/x-dosexec',
                'file_size' => 999999,
                'dipublikasikan_pada' => '1999-01-01 00:00:00',
                'pertemuan_ke' => 99,
            ], $this->pdf('Laporan Asli.pdf')),
        )->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));

        $materi = MateriPembelajaran::query()->firstOrFail();

        $this->assertSame($kelas->id, (int) $materi->kelas_mata_kuliah_id);
        $this->assertSame($dosen->id, (int) $materi->dibuat_oleh);
        $this->assertSame('Laporan Asli.pdf', $materi->file_name);
        $this->assertSame('application/pdf', $materi->file_mime);
        $this->assertStringStartsWith('materi/'.$kelas->id.'/', $materi->file_path);
        $this->assertStringNotContainsString('..', $materi->file_path);
        $this->assertSame(strlen("%PDF-1.4\nMateri uji.\n%%EOF\n"), (int) $materi->file_size);
        $this->assertNull($materi->pertemuan_ke);
        $this->assertNotNull($materi->dipublikasikan_pada);
        $this->assertFalse($materi->dipublikasikan_pada->equalTo('1999-01-01 00:00:00'));
        $this->assertTrue(Storage::disk('local')->exists($materi->file_path));
    }

    public function test_format_yang_diizinkan_dapat_diunggah(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $format = [
            'pdf' => fn (): UploadedFile => $this->pdf(),
            'txt' => fn (): UploadedFile => UploadedFile::fake()->createWithContent('catatan.txt', "Catatan materi.\n"),
            'zip' => fn (): UploadedFile => UploadedFile::fake()->createWithContent(
                'arsip.zip',
                $this->zipBytes([['isi.txt', 'isi arsip']]),
            ),
            'docx' => fn (): UploadedFile => UploadedFile::fake()->createWithContent(
                'modul.docx',
                $this->zipBytes([['[Content_Types].xml', '<Types/>'], ['word/document.xml', '<w:document/>']]),
            ),
            'xlsx' => fn (): UploadedFile => UploadedFile::fake()->createWithContent(
                'tabel.xlsx',
                $this->zipBytes([['[Content_Types].xml', '<Types/>'], ['xl/workbook.xml', '<workbook/>']]),
            ),
            'pptx' => fn (): UploadedFile => UploadedFile::fake()->createWithContent(
                'slides.pptx',
                $this->zipBytes([['[Content_Types].xml', '<Types/>'], ['ppt/presentation.xml', '<p:presentation/>']]),
            ),
            'doc' => fn (): UploadedFile => UploadedFile::fake()->createWithContent(
                'modul.doc',
                $this->oleBytes('WordDocument'),
            ),
            'xls' => fn (): UploadedFile => UploadedFile::fake()->createWithContent(
                'tabel.xls',
                $this->oleBytes('Workbook'),
            ),
            'ppt' => fn (): UploadedFile => UploadedFile::fake()->createWithContent(
                'slides.ppt',
                $this->oleBytes('PowerPoint Document'),
            ),
        ];

        foreach ($format as $ekstensi => $pembuat) {
            $this->actingAs($dosen)->post(
                route('dosen.mata-kuliah.materi.store', $kelas),
                $this->payload(['judul' => 'Materi '.strtoupper($ekstensi)], $pembuat()),
            )->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));
        }

        $this->assertSame(count($format), MateriPembelajaran::query()->count());

        foreach (MateriPembelajaran::query()->get() as $materi) {
            $this->assertStringEndsWith('.'.strtolower(pathinfo($materi->file_name, PATHINFO_EXTENSION)), $materi->file_path);
            $this->assertStringStartsWith('materi/'.$kelas->id.'/', $materi->file_path);
            $this->assertTrue(Storage::disk('local')->exists($materi->file_path));
        }
    }

    public function test_executable_yang_disamarkan_sebagai_pdf_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $executable = UploadedFile::fake()->createWithContent(
            'berbahaya.pdf',
            "MZ\x90\0\x03\0\0\0".str_repeat("\0", 128),
        );

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload([], $executable),
        )->assertSessionHasErrors('file');

        $this->assertSame(0, MateriPembelajaran::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_mime_dan_ekstensi_tidak_konsisten_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $kasus = [
            UploadedFile::fake()->createWithContent('salah.txt', "%PDF-1.4\n%%EOF\n"),
            UploadedFile::fake()->createWithContent('salah.docx', $this->zipBytes([['isi.txt', 'bukan dokumen']])),
            UploadedFile::fake()->createWithContent('salah.pdf', $this->zipBytes([['isi.txt', 'bukan pdf']])),
        ];

        foreach ($kasus as $file) {
            $this->actingAs($dosen)->post(
                route('dosen.mata-kuliah.materi.store', $kelas),
                $this->payload([], $file),
            )->assertSessionHasErrors('file');
        }

        $this->assertSame(0, MateriPembelajaran::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_file_kosong_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload([], UploadedFile::fake()->createWithContent('kosong.pdf', '')),
        )->assertSessionHasErrors('file');

        $this->assertSame(0, MateriPembelajaran::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_file_melebihi_10_mb_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $besar = UploadedFile::fake()->createWithContent(
            'besar.pdf',
            "%PDF-1.4\n".str_repeat('a', 10240 * 1024),
        );

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload([], $besar),
        )->assertSessionHasErrors('file');

        $this->assertSame(0, MateriPembelajaran::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_draft_dan_dipublikasikan_tersimpan_dengan_benar(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload(['judul' => 'Draft Materi', 'status' => 'draft'], $this->pdf()),
        )->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload(['judul' => 'Materi Publik', 'status' => 'dipublikasikan'], $this->pdf()),
        )->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));

        $draft = MateriPembelajaran::query()->where('judul', 'Draft Materi')->firstOrFail();
        $publik = MateriPembelajaran::query()->where('judul', 'Materi Publik')->firstOrFail();

        $this->assertNull($draft->dipublikasikan_pada);
        $this->assertNotNull($publik->dipublikasikan_pada);
        $this->assertTrue($publik->dipublikasikan_pada->lessThanOrEqualTo(now()));
    }

    public function test_update_metadata_tanpa_file_tidak_mengubah_path_file(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen, 'Judul Lama', null, "%PDF-1.4\nLama\n%%EOF\n");
        $pathLama = $materi->file_path;
        $mimeLama = $materi->file_mime;
        $ukuranLama = (int) $materi->file_size;

        $this->actingAs($dosen)->put(
            route('dosen.mata-kuliah.materi.update', [$kelas, $materi]),
            $this->payload([
                'judul' => 'Judul Baru',
                'deskripsi' => 'Deskripsi diperbarui.',
                'status' => 'dipublikasikan',
            ]),
        )->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));

        $materi->refresh();

        $this->assertSame('Judul Baru', $materi->judul);
        $this->assertSame('Deskripsi diperbarui.', $materi->deskripsi);
        $this->assertSame($pathLama, $materi->file_path);
        $this->assertSame($mimeLama, $materi->file_mime);
        $this->assertSame($ukuranLama, (int) $materi->file_size);
        $this->assertNotNull($materi->dipublikasikan_pada);
        $this->assertTrue(Storage::disk('local')->exists($pathLama));
    }

    public function test_status_dapat_dikembalikan_ke_draft_tanpa_mengganti_file(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen, 'Materi Publik', '2026-11-18 08:00:00');
        $pathLama = $materi->file_path;

        $this->actingAs($dosen)->put(
            route('dosen.mata-kuliah.materi.update', [$kelas, $materi]),
            $this->payload(['status' => 'draft']),
        )->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));

        $materi->refresh();

        $this->assertNull($materi->dipublikasikan_pada);
        $this->assertSame($pathLama, $materi->file_path);
        $this->assertTrue(Storage::disk('local')->exists($pathLama));
    }

    public function test_replace_file_menghapus_file_lama_setelah_transaksi_berhasil(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen, 'Materi Lama', null, "%PDF-1.4\nVersi lama\n%%EOF\n");
        $pathLama = $materi->file_path;

        $this->actingAs($dosen)->put(
            route('dosen.mata-kuliah.materi.update', [$kelas, $materi]),
            $this->payload(
                ['judul' => 'Materi Baru'],
                $this->pdf('Versi Baru.pdf', "%PDF-1.4\nVersi baru\n%%EOF\n"),
            ),
        )->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));

        $materi->refresh();

        $this->assertNotSame($pathLama, $materi->file_path);
        $this->assertSame('Versi Baru.pdf', $materi->file_name);
        $this->assertStringStartsWith('materi/'.$kelas->id.'/', $materi->file_path);
        $this->assertFalse(Storage::disk('local')->exists($pathLama));
        $this->assertTrue(Storage::disk('local')->exists($materi->file_path));
        $this->assertSame("%PDF-1.4\nVersi baru\n%%EOF\n", Storage::disk('local')->get($materi->file_path));
    }

    public function test_kegagalan_transaksi_menghapus_file_baru_dan_mempertahankan_file_lama(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen, 'Materi Aman', null, "%PDF-1.4\nAman\n%%EOF\n");
        $pathLama = $materi->file_path;
        $judulLama = $materi->judul;

        MateriPembelajaran::updating(function (): never {
            throw new RuntimeException('Simulasi kegagalan database.');
        });

        $hasil = app(MateriPembelajaranService::class)->perbarui(
            $dosen->id,
            $kelas->id,
            $materi->id,
            $this->payload(['judul' => 'Judul Yang Gagal']),
            $this->pdf('Pengganti Gagal.pdf', "%PDF-1.4\nGagal\n%%EOF\n"),
        );

        $this->assertFalse($hasil['berhasil']);
        $this->assertSame('Materi gagal diperbarui. Silakan coba lagi.', $hasil['pesan']);

        $materi->refresh();

        $this->assertSame($judulLama, $materi->judul);
        $this->assertSame($pathLama, $materi->file_path);
        $this->assertTrue(Storage::disk('local')->exists($pathLama));
        $this->assertSame(["materi/{$kelas->id}/".now()->format('Y').'/'.now()->format('m').'/'.basename($pathLama)], Storage::disk('local')->allFiles());
    }

    public function test_hard_delete_menghapus_data_dan_file_materi(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen, 'Materi Akan Dihapus');
        $path = $materi->file_path;

        $this->actingAs($dosen)
            ->delete(route('dosen.mata-kuliah.materi.destroy', [$kelas, $materi]))
            ->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));

        $this->assertDatabaseMissing('materi_pembelajaran', ['id' => $materi->id]);
        $this->assertFalse(Storage::disk('local')->exists($path));
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_progres_terkait_terhapus_melalui_cascade_schema_existing(): void
    {
        $dosen = $this->dosen();
        $mahasiswa = User::factory()->mahasiswa()->create();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen);
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => 'aktif',
        ])->save();
        $progres = new ProgresMateri();
        $progres->forceFill([
            'materi_pembelajaran_id' => $materi->id,
            'peserta_mata_kuliah_id' => $peserta->id,
            'status' => 'sedang_dipelajari',
            'progres' => 40,
        ])->save();

        $this->assertDatabaseHas('progres_materi', ['id' => $progres->id]);

        $this->actingAs($dosen)
            ->delete(route('dosen.mata-kuliah.materi.destroy', [$kelas, $materi]))
            ->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));

        $this->assertDatabaseMissing('materi_pembelajaran', ['id' => $materi->id]);
        $this->assertDatabaseMissing('progres_materi', ['id' => $progres->id]);
    }

    public function test_kegagalan_hapus_file_lama_dicatat_tanpa_merusak_database(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen, 'Materi Lama', null, "%PDF-1.4\nLama\n%%EOF\n");
        $pathAsli = $materi->file_path;
        $pathLamaTidakValid = '../materi/path-lama-tidak-valid';
        $materi->forceFill(['file_path' => $pathLamaTidakValid])->save();
        Log::spy();

        $this->actingAs($dosen)->put(
            route('dosen.mata-kuliah.materi.update', [$kelas, $materi]),
            $this->payload(
                ['judul' => 'Materi Tetap Tersimpan'],
                $this->pdf('Pengganti.pdf', "%PDF-1.4\nBaru\n%%EOF\n"),
            ),
        )->assertRedirect(route('dosen.mata-kuliah.materi.index', $kelas));

        $materi->refresh();

        $this->assertSame('Materi Tetap Tersimpan', $materi->judul);
        $this->assertNotSame($pathLamaTidakValid, $materi->file_path);
        $this->assertTrue(Storage::disk('local')->exists($materi->file_path));
        $this->assertTrue(Storage::disk('local')->exists($pathAsli));
        Log::shouldHaveReceived('warning')
            ->with(
                'Gagal menghapus file materi lama setelah pembaruan',
                Mockery::on(fn (array $konteks): bool => ($konteks['path'] ?? null) === $pathLamaTidakValid),
            )
            ->once();
    }

    public function test_kegagalan_upload_tidak_menampilkan_detail_internal(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $fileService = Mockery::mock(MateriFileService::class)->makePartial();
        $fileService->shouldReceive('validasiBerkas')->andReturn(null);
        $fileService->shouldReceive('simpan')->andThrow(new RuntimeException('/private/app/path/rahasia'));
        $this->app->instance(MateriFileService::class, $fileService);

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload([], $this->pdf()),
        )
            ->assertRedirect()
            ->assertSessionHasErrors(['file' => 'File materi gagal disimpan. Silakan coba lagi.']);

        $pesan = implode(' ', session('errors')->get('file', []));

        $this->assertStringNotContainsString('/private/app/path/rahasia', $pesan);
        $this->assertStringNotContainsString(storage_path(), $pesan);
        $this->assertSame(0, MateriPembelajaran::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_download_memeriksa_kepemilikan_dan_path(): void
    {
        $dosen = $this->dosen();
        $dosenLain = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $materi = $this->materi($kelas, $dosen, 'Materi Draft', null, "%PDF-1.4\nRahasia draft\n%%EOF\n");

        $this->actingAs($dosenLain)
            ->get(route('dosen.mata-kuliah.materi.download', [$kelas, $materi]))
            ->assertNotFound();

        $respons = $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.materi.download', [$kelas, $materi]))
            ->assertOk();

        $this->assertSame("%PDF-1.4\nRahasia draft\n%%EOF\n", $respons->streamedContent());

        $materi->forceFill(['file_path' => 'materi/999/2026/11/asing.pdf'])->save();
        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.materi.download', [$kelas, $materi]))
            ->assertNotFound();

        $materi->forceFill(['file_path' => 'materi/'.$kelas->id.'/../../rahasia.pdf'])->save();
        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.materi.download', [$kelas, $materi]))
            ->assertNotFound();
    }

    public function test_validasi_metadata_materi_diterapkan(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload(['judul' => '', 'status' => 'publik'], $this->pdf()),
        )->assertSessionHasErrors(['judul', 'status']);

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload(['judul' => str_repeat('a', 201)], $this->pdf()),
        )->assertSessionHasErrors('judul');

        $this->actingAs($dosen)->post(
            route('dosen.mata-kuliah.materi.store', $kelas),
            $this->payload(['deskripsi' => str_repeat('a', 5001)], $this->pdf()),
        )->assertSessionHasErrors('deskripsi');

        $this->assertSame(0, MateriPembelajaran::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_index_hanya_menampilkan_materi_kelas_yang_dibuka(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $kelasLain = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'B02');
        $this->materi($kelas, $dosen, 'Materi Kelas A', '2026-11-18 08:00:00');
        $this->materi($kelasLain, $dosen, 'Materi Kelas B', '2026-11-18 08:00:00');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.materi.index', $kelas))
            ->assertOk()
            ->assertSee('Materi Kelas A')
            ->assertSee('Dipublikasikan')
            ->assertDontSee('Materi Kelas B');
    }

    public function test_disk_lokal_tidak_melayani_file_lewat_route_storage(): void
    {
        $this->assertFalse(config('filesystems.disks.local.serve'));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function payload(array $overrides = [], ?UploadedFile $file = null): array
    {
        $payload = array_merge([
            'judul' => 'Materi Pertemuan Pertama',
            'deskripsi' => 'Ringkasan materi kuliah.',
            'status' => 'draft',
        ], $overrides);

        if ($file !== null) {
            $payload['file'] = $file;
        }

        return $payload;
    }

    private function pdf(string $nama = 'Materi Asli.pdf', string $isi = "%PDF-1.4\nMateri uji.\n%%EOF\n"): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, $isi);
    }

    private function dosen(): User
    {
        return User::factory()->dosen()->create();
    }

    private function tahun(int $mulai = 2026, bool $aktif = true): TahunAkademik
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

    private function mataKuliah(?string $kode = null): MataKuliah
    {
        $this->urutan++;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => $kode ?? 'MK-'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Uji '.$this->urutan,
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
            'nama_kelas' => null,
            'kapasitas' => 30,
        ])->save();

        return $kelas;
    }

    private function materi(
        KelasMataKuliah $kelas,
        User $dosen,
        ?string $judul = null,
        ?string $dipublikasikanPada = null,
        string $isi = "%PDF-1.4\nMateri uji.\n%%EOF\n",
    ): MateriPembelajaran {
        $path = sprintf(
            'materi/%d/%s/%s/%s.pdf',
            $kelas->id,
            now()->format('Y'),
            now()->format('m'),
            (string) Str::uuid(),
        );
        Storage::disk('local')->put($path, $isi);

        $materi = new MateriPembelajaran();
        $materi->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'dibuat_oleh' => $dosen->id,
            'judul' => $judul ?? 'Materi Uji '.$kelas->id,
            'deskripsi' => 'Deskripsi materi uji.',
            'file_path' => $path,
            'file_name' => basename($path),
            'file_mime' => 'application/pdf',
            'file_size' => strlen($isi),
            'dipublikasikan_pada' => $dipublikasikanPada,
        ])->save();

        return $materi;
    }

    /**
     * @param array<int, array{0: string, 1: string}> $entries
     */
    private function zipBytes(array $entries): string
    {
        $lokal = '';
        $sentral = '';
        $offset = 0;

        foreach ($entries as [$nama, $isi]) {
            $crc = crc32($isi);
            $ukuran = strlen($isi);
            $panjangNama = strlen($nama);
            $lokal .= "PK\x03\x04".pack(
                'vvvvvVVVvv',
                20,
                0,
                0,
                0,
                0,
                $crc,
                $ukuran,
                $ukuran,
                $panjangNama,
                0,
            ).$nama.$isi;
            $sentral .= "PK\x01\x02".pack(
                'vvvvvvVVVvvvvvVV',
                20,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $ukuran,
                $ukuran,
                $panjangNama,
                0,
                0,
                0,
                0,
                0,
                $offset,
            ).$nama;
            $offset += 30 + $panjangNama + $ukuran;
        }

        $panjangSentral = strlen($sentral);

        return $lokal.$sentral."PK\x05\x06".pack(
            'vvvvVVv',
            0,
            0,
            count($entries),
            count($entries),
            $panjangSentral,
            $offset,
            0,
        );
    }

    private function oleBytes(string $namaStream): string
    {
        $header = pack('H*', 'd0cf11e0a1b11ae1');
        $header .= str_repeat("\0", 16);
        $header .= $this->u16(0x003E).$this->u16(0x0003).$this->u16(0xFFFE).$this->u16(0x0009).$this->u16(0x0006);
        $header .= str_repeat("\0", 6);
        $header .= $this->u32(0).$this->u32(1).$this->u32(1).$this->u32(0).$this->u32(0x1000);
        $header .= $this->u32(0xFFFFFFFE).$this->u32(0).$this->u32(0xFFFFFFFE).$this->u32(0).$this->u32(0);
        $header .= $this->u32(0);
        $header .= str_repeat($this->u32(0xFFFFFFFF), 108);
        $header = str_pad($header, 512, "\0");

        $fat = $this->u32(0xFFFFFFFD).$this->u32(0xFFFFFFFE).str_repeat($this->u32(0xFFFFFFFF), 126);
        $direktori = $this->entriDirektori('Root Entry', 5, 0xFFFFFFFE, 0);
        $direktori .= $this->entriDirektori($namaStream, 2, 2, 16);
        $direktori .= str_repeat(str_repeat("\0", 128), 2);
        $data = str_repeat('A', 512);

        return $header.$fat.$direktori.$data;
    }

    private function entriDirektori(string $nama, int $jenis, int $mulai, int $ukuran): string
    {
        $utf16 = mb_convert_encoding($nama, 'UTF-16LE', 'UTF-8')."\0\0";
        $entri = str_pad($utf16, 64, "\0");
        $entri .= $this->u16(strlen($utf16));
        $entri .= chr($jenis).chr(1);
        $entri .= $this->u32(0xFFFFFFFF).$this->u32(0xFFFFFFFF).$this->u32(0xFFFFFFFF);
        $entri .= str_repeat("\0", 16);
        $entri .= $this->u32(0);
        $entri .= str_repeat("\0", 16);
        $entri .= $this->u32($mulai);
        $entri .= $this->u32($ukuran).$this->u32(0);

        return str_pad($entri, 128, "\0");
    }

    private function u16(int $nilai): string
    {
        return pack('v', $nilai);
    }

    private function u32(int $nilai): string
    {
        return pack('V', $nilai);
    }
}
