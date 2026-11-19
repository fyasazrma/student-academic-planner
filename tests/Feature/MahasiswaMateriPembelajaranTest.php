<?php

namespace Tests\Feature;

use App\Enums\StatusPeserta;
use App\Enums\StatusProgres;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\MateriPembelajaran;
use App\Models\PesertaMataKuliah;
use App\Models\ProgresMateri;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MahasiswaMateriPembelajaranTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get(route('mahasiswa.materi.index'))
            ->assertRedirect(route('login'));

        $this->get(route('mahasiswa.materi.unduh', [1, 1]))
            ->assertRedirect(route('login'));
    }

    public function test_dosen_dan_admin_dilarang_mengakses_materi_mahasiswa(): void
    {
        $dosen = $this->dosen();
        $admin = User::factory()->admin()->create();

        $this->actingAs($dosen)
            ->get(route('mahasiswa.materi.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('mahasiswa.materi.index'))
            ->assertForbidden();

        $this->actingAs($dosen)
            ->get(route('mahasiswa.materi.unduh', [1, 1]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('mahasiswa.materi.unduh', [1, 1]))
            ->assertForbidden();
    }

    public function test_mahasiswa_melihat_materi_terpublikasi_dari_kelas_aktif(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mk = $this->mataKuliah('Pemrograman Web');

        $kelas = $this->kelas($dosen, $tahun, $mk, 'A01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

        $materi1 = $this->materi($kelas, $dosen, [
            'judul' => 'Pengenalan PHP',
            'deskripsi' => 'Pengantar bahasa PHP modern.',
            'pertemuan_ke' => 1,
            'dipublikasikan_pada' => now()->subDay(),
        ]);

        $materi2 = $this->materi($kelas, $dosen, [
            'judul' => 'Dasar Routing Laravel',
            'deskripsi' => 'Mempelajari routing dan middleware.',
            'pertemuan_ke' => 2,
            'dipublikasikan_pada' => now()->subHours(2),
        ]);

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index'));

        $response->assertOk()
            ->assertSee('Materi Pembelajaran')
            ->assertSee('Pengenalan PHP')
            ->assertSee('Dasar Routing Laravel')
            ->assertSee('Pengantar bahasa PHP modern.')
            ->assertSee('P1')
            ->assertSee('P2')
            ->assertSee('dokumen.pdf')
            ->assertSee($dosen->name);
    }

    public function test_materi_draft_dan_jadwal_masa_depan_tidak_ditampilkan(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mk = $this->mataKuliah();

        $kelas = $this->kelas($dosen, $tahun, $mk, 'A01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

        // Terpublikasi
        $materiPublik = $this->materi($kelas, $dosen, [
            'judul' => 'Materi Terpublikasi',
            'dipublikasikan_pada' => now()->subDay(),
        ]);

        // Draft
        $materiDraft = $this->materi($kelas, $dosen, [
            'judul' => 'Materi Draft Rahasia',
            'dipublikasikan_pada' => null,
        ]);

        // Terjadwal masa depan
        $materiMasaDepan = $this->materi($kelas, $dosen, [
            'judul' => 'Materi Terjadwal Besok',
            'dipublikasikan_pada' => now()->addDay(),
        ]);

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index'));

        $response->assertOk()
            ->assertSee('Materi Terpublikasi')
            ->assertDontSee('Materi Draft Rahasia')
            ->assertDontSee('Materi Terjadwal Besok');
    }

    public function test_filter_kelas_menampilkan_hanya_materi_kelas_terpilih(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $mk1 = $this->mataKuliah('Algoritma');
        $mk2 = $this->mataKuliah('Basis Data');

        $kelas1 = $this->kelas($dosen, $tahun, $mk1, 'K01');
        $kelas2 = $this->kelas($dosen, $tahun, $mk2, 'K02');

        $this->peserta($kelas1, $mahasiswa, StatusPeserta::Aktif);
        $this->peserta($kelas2, $mahasiswa, StatusPeserta::Aktif);

        $materiKelas1 = $this->materi($kelas1, $dosen, ['judul' => 'Sorting Algoritma']);
        $materiKelas2 = $this->materi($kelas2, $dosen, ['judul' => 'Normalisasi Database']);

        $responseSemua = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index'));
        $responseSemua->assertOk()
            ->assertSee('Sorting Algoritma')
            ->assertSee('Normalisasi Database');

        $responseFilter1 = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index', ['kelas' => $kelas1->id]));
        $responseFilter1->assertOk()
            ->assertSee('Sorting Algoritma')
            ->assertDontSee('Normalisasi Database');

        $responseFilter2 = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index', ['kelas' => $kelas2->id]));
        $responseFilter2->assertOk()
            ->assertDontSee('Sorting Algoritma')
            ->assertSee('Normalisasi Database');
    }

    public function test_filter_kelas_yang_tidak_diikuti_menghasilkan_404(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelasAsing = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'X99');

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index', ['kelas' => $kelasAsing->id]))
            ->assertNotFound();
    }

    public function test_filter_kelas_dengan_status_peserta_nonaktif_menghasilkan_404(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'N01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Nonaktif);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index', ['kelas' => $kelas->id]))
            ->assertNotFound();
    }

    public function test_peserta_nonaktif_tidak_dapat_mengunduh_materi(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'N01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Nonaktif);

        $path = 'materi/'.$kelas->id.'/2026/11/nonaktif.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 nonaktif');

        $materi = $this->materi($kelas, $dosen, [
            'file_path' => $path,
            'dipublikasikan_pada' => now()->subDay(),
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.unduh', [$kelas->id, $materi->id]))
            ->assertNotFound();
    }

    public function test_menu_dan_tombol_akses_materi_terhubung(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertOk()
            ->assertSee(route('mahasiswa.materi.index'))
            ->assertSee(route('mahasiswa.materi.index', ['kelas' => $kelas->id]))
            ->assertSee('Lihat Materi');
    }

    public function test_mahasiswa_dapat_mengunduh_berkas_materi_terpublikasi(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

        $isiFile = '%PDF-1.4 konten materi perkuliahan penting';
        $pathFile = 'materi/'.$kelas->id.'/2026/11/contoh-materi.pdf';
        Storage::disk('local')->put($pathFile, $isiFile);

        $materi = $this->materi($kelas, $dosen, [
            'judul' => 'Slide Pertemuan 1',
            'file_path' => $pathFile,
            'file_name' => 'slide-pertemuan-1.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => strlen($isiFile),
            'dipublikasikan_pada' => now()->subHour(),
        ]);

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.unduh', [$kelas->id, $materi->id]));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString('attachment; filename=slide-pertemuan-1.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame($isiFile, $response->streamedContent());
    }

    public function test_mengunduh_materi_draft_menghasilkan_404(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

        $path = 'materi/'.$kelas->id.'/2026/11/draft.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 draft');

        $materiDraft = $this->materi($kelas, $dosen, [
            'file_path' => $path,
            'dipublikasikan_pada' => null,
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.unduh', [$kelas->id, $materiDraft->id]))
            ->assertNotFound();
    }

    public function test_mengunduh_materi_masa_depan_menghasilkan_404(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

        $path = 'materi/'.$kelas->id.'/2026/11/besok.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 besok');

        $materiBesok = $this->materi($kelas, $dosen, [
            'file_path' => $path,
            'dipublikasikan_pada' => now()->addDay(),
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.unduh', [$kelas->id, $materiBesok->id]))
            ->assertNotFound();
    }

    public function test_mengunduh_materi_dari_kelas_yang_tidak_diikuti_menghasilkan_404(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelasAsing = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'X01');

        $path = 'materi/'.$kelasAsing->id.'/2026/11/materi.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 materi');

        $materi = $this->materi($kelasAsing, $dosen, [
            'file_path' => $path,
            'dipublikasikan_pada' => now()->subDay(),
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.unduh', [$kelasAsing->id, $materi->id]))
            ->assertNotFound();
    }

    public function test_mengunduh_materi_dengan_kelas_tidak_cocok_menghasilkan_404(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas1 = $this->kelas($dosen, $tahun, $this->mataKuliah('MK1'), 'A01');
        $kelas2 = $this->kelas($dosen, $tahun, $this->mataKuliah('MK2'), 'A02');

        $this->peserta($kelas1, $mahasiswa, StatusPeserta::Aktif);
        $this->peserta($kelas2, $mahasiswa, StatusPeserta::Aktif);

        $path = 'materi/'.$kelas1->id.'/2026/11/materi1.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');

        $materiKelas1 = $this->materi($kelas1, $dosen, [
            'file_path' => $path,
            'dipublikasikan_pada' => now()->subDay(),
        ]);

        // Coba unduh materi kelas 1 memakai ID kelas 2 pada URL
        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.unduh', [$kelas2->id, $materiKelas1->id]))
            ->assertNotFound();
    }

    public function test_tidak_ada_kebocoran_path_storage_lokal_pada_tampilan(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

        $pathRahasia = 'materi/'.$kelas->id.'/2026/11/secret-uuid-path-name.pdf';
        Storage::disk('local')->put($pathRahasia, '%PDF-1.4');

        $this->materi($kelas, $dosen, [
            'judul' => 'Keamanan Web',
            'file_path' => $pathRahasia,
            'file_name' => 'keamanan-web.pdf',
            'dipublikasikan_pada' => now()->subDay(),
        ]);

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index'));

        $response->assertOk()
            ->assertSee('keamanan-web.pdf')
            ->assertDontSee($pathRahasia)
            ->assertDontSee('storage/app')
            ->assertDontSee('private');
    }

    public function test_progres_materi_terbaca_dengan_benar(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $peserta = $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

        $materi1 = $this->materi($kelas, $dosen, ['judul' => 'Materi Bab 1']);
        $materi2 = $this->materi($kelas, $dosen, ['judul' => 'Materi Bab 2']);

        // Set status progres materi1 menjadi selesai
        $progres = new ProgresMateri();
        $progres->forceFill([
            'materi_pembelajaran_id' => $materi1->id,
            'peserta_mata_kuliah_id' => $peserta->id,
            'status' => StatusProgres::Selesai->value,
            'progres' => 100,
            'selesai_pada' => now(),
        ])->save();

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index'));

        $response->assertOk()
            ->assertSee('Materi Selesai')
            ->assertSee('50% materi telah dipelajari')
            ->assertSee('Belum dipelajari')
            ->assertSee('Selesai');
    }

    public function test_daftar_materi_tidak_menimbulkan_n_plus_one(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mk = $this->mataKuliah();

        $kelasPertama = $this->kelas($dosen, $tahun, $mk, 'A01');
        $this->peserta($kelasPertama, $mahasiswa, StatusPeserta::Aktif);
        $this->materi($kelasPertama, $dosen, ['judul' => 'Materi A1']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index'))
            ->assertOk();
        $querySatuKelas = count(DB::getQueryLog());
        DB::disableQueryLog();

        for ($i = 2; $i <= 4; $i++) {
            $kelas = $this->kelas($dosen, $tahun, $mk, 'A0'.$i);
            $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

            for ($j = 1; $j <= 4; $j++) {
                $this->materi($kelas, $dosen, ['judul' => 'Materi '.$i.'-'.$j]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index'))
            ->assertOk();
        $queryBanyakKelas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            $querySatuKelas + 2,
            $queryBanyakKelas,
            'Jumlah query tidak boleh bertambah seiring banyaknya kelas dan materi.'
        );
    }

    public function test_tampilan_empty_state_saat_belum_ada_materi(): void
    {
        $mahasiswa = $this->mahasiswa();
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01');
        $this->peserta($kelas, $mahasiswa, StatusPeserta::Aktif);

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index'));

        $response->assertOk()
            ->assertSee('Belum ada materi pembelajaran');
    }

    public function test_tampilan_empty_state_saat_belum_mengikuti_kelas(): void
    {
        $mahasiswa = $this->mahasiswa();

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.index'));

        $response->assertOk()
            ->assertSee('Belum terdaftar di kelas manapun')
            ->assertSeeText('Cari & Gabung Kelas');
    }

    private function mahasiswa(): User
    {
        return User::factory()->mahasiswa()->create();
    }

    private function dosen(): User
    {
        return User::factory()->dosen()->create();
    }

    private function tahun(int $mulai = 2026, bool $aktif = true): TahunAkademik
    {
        $this->urutan++;

        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => $mulai + $this->urutan,
            'tahun_selesai' => $mulai + $this->urutan + 1,
            'semester' => 'ganjil',
            'tanggal_mulai' => ($mulai + $this->urutan).'-09-01',
            'tanggal_selesai' => ($mulai + $this->urutan).'-12-31',
            'aktif' => $aktif,
        ])->save();

        return $tahun;
    }

    private function mataKuliah(?string $nama = null): MataKuliah
    {
        $this->urutan++;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MK-'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama' => $nama ?? 'Mata Kuliah '.$this->urutan,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        return $mataKuliah;
    }

    private function kelas(User $dosen, TahunAkademik $tahun, MataKuliah $mk, string $kodeKelas): KelasMataKuliah
    {
        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mk->id,
            'tahun_akademik_id' => $tahun->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kodeKelas,
            'nama_kelas' => 'Kelas '.$kodeKelas,
            'kapasitas' => 30,
        ])->save();

        return $kelas;
    }

    private function peserta(KelasMataKuliah $kelas, User $mahasiswa, StatusPeserta $status): PesertaMataKuliah
    {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status->value,
            'tanggal_daftar' => now(),
        ])->save();

        return $peserta;
    }

    /**
     * @param array<string, mixed> $override
     */
    private function materi(KelasMataKuliah $kelas, User $pembuat, array $override = []): MateriPembelajaran
    {
        $path = $override['file_path'] ?? ('materi/'.$kelas->id.'/2026/11/file-'.uniqid().'.pdf');

        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, '%PDF-1.4 dummy');
        }

        $materi = new MateriPembelajaran();
        $materi->forceFill(array_merge([
            'kelas_mata_kuliah_id' => $kelas->id,
            'dibuat_oleh' => $pembuat->id,
            'judul' => 'Materi Pertemuan',
            'deskripsi' => 'Deskripsi materi perkuliahan.',
            'file_path' => $path,
            'file_name' => 'dokumen.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => 1024,
            'pertemuan_ke' => 1,
            'dipublikasikan_pada' => now()->subDay(),
        ], $override))->save();

        return $materi;
    }
}
