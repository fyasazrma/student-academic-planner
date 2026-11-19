<?php

namespace Tests\Feature;

use App\Enums\StatusPeserta;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\PengumpulanTugas;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\Tugas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenPengumpulanTugasTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private ?TahunAkademik $tahun = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-11-19 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_route_pengumpulan_hanya_untuk_dosen(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'DP-A');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Akses');
        $peserta = $this->enroll($this->mahasiswa('Rani'), $kelas);
        $pengumpulan = $this->submission($tugas, $peserta);

        $rute = [
            route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelas, $tugas]),
            route('dosen.mata-kuliah.tugas.pengumpulan.show', [$kelas, $tugas, $pengumpulan]),
        ];

        foreach ($rute as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        foreach ($rute as $url) {
            $this->actingAs($this->mahasiswa('Penguji'))->get($url)->assertForbidden();
            $this->actingAs(User::factory()->admin()->create())->get($url)->assertForbidden();
        }

        $this->actingAs($dosen)->get($rute[0])->assertOk();
        $this->actingAs($dosen)->get($rute[1])->assertOk();
    }

    public function test_dosen_dapat_melihat_daftar_pengumpulan_kelas_sendiri(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'DP-B');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Pengumpulan', now()->subDays(2));
        $peserta = $this->enroll($this->mahasiswa('Rani'), $kelas);
        $this->submission($tugas, $peserta, [
            'file_name' => 'jawaban-rani.pdf',
            'catatan' => 'Catatan Rani',
            'dikumpulkan_pada' => now()->subHour(),
        ]);

        $response = $this->actingAs($dosen)->get(
            route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelas, $tugas]),
        );

        $response->assertOk()
            ->assertViewIs('dosen.mata-kuliah.tugas.pengumpulan.index')
            ->assertSee('Tugas Pengumpulan')
            ->assertSee('Rani')
            ->assertSee('jawaban-rani.pdf')
            ->assertSee('Daftar pengumpulan (tabel)')
            ->assertSee('Daftar pengumpulan (kartu)');

        $this->assertSame(1, $response->viewData('statistik')['jumlah_peserta']);
        $this->assertSame(1, $response->viewData('statistik')['sudah_mengumpulkan']);
    }

    public function test_kelas_dan_tugas_dosen_lain_tidak_dapat_diakses(): void
    {
        $dosen = $this->dosen();
        $dosenLain = $this->dosen();
        $kelasSaya = $this->kelas($dosen, 'DP-C');
        $tugasSaya = $this->tugas($kelasSaya, $dosen, 'Tugas Saya');
        $kelasLain = $this->kelas($dosenLain, 'DP-D');
        $tugasLain = $this->tugas($kelasLain, $dosenLain, 'Tugas Dosen Lain');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelasLain, $tugasLain]))
            ->assertNotFound();
        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelasSaya, $tugasLain]))
            ->assertNotFound();
        $this->assertDatabaseHas('tugas', ['id' => $tugasLain->id, 'judul' => 'Tugas Dosen Lain']);
    }

    public function test_pengumpulan_dari_tugas_atau_kelas_lain_tidak_dapat_diakses(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'DP-E');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Utama');
        $tugasLain = $this->tugas($kelas, $dosen, 'Tugas Lain');
        $peserta = $this->enroll($this->mahasiswa('Rani'), $kelas);
        $pengumpulanTugasLain = $this->submission($tugasLain, $peserta);

        $kelasLain = $this->kelas($dosen, 'DP-F');
        $tugasKelasLain = $this->tugas($kelasLain, $dosen, 'Tugas Kelas Lain');
        $pesertaKelasLain = $this->enroll($this->mahasiswa('Budi'), $kelasLain);
        $pengumpulanKelasLain = $this->submission($tugasKelasLain, $pesertaKelasLain);

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.tugas.pengumpulan.show', [$kelas, $tugas, $pengumpulanTugasLain]))
            ->assertNotFound();
        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.tugas.pengumpulan.show', [$kelas, $tugas, $pengumpulanKelasLain]))
            ->assertNotFound();
    }

    public function test_status_dan_statistik_sesuai_data_database(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'DP-G');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Statistik');

        $rani = $this->enroll($this->mahasiswa('Rani'), $kelas);
        $budi = $this->enroll($this->mahasiswa('Budi'), $kelas);
        $sari = $this->enroll($this->mahasiswa('Sari'), $kelas);
        $dimas = $this->enroll($this->mahasiswa('Dimas'), $kelas);

        $this->submission($tugas, $rani);
        $this->submission($tugas, $budi, [
            'terlambat' => true,
            'nilai' => '80.00',
            'umpan_balik' => 'Sudah baik.',
            'dinilai_oleh' => $dosen->id,
            'dinilai_pada' => now()->subMinutes(30),
        ]);
        $this->submission($tugas, $dimas);

        $response = $this->actingAs($dosen)->get(
            route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelas, $tugas]),
        );

        $response->assertOk()
            ->assertSee('Sudah dinilai')
            ->assertSee('Dikumpulkan terlambat')
            ->assertSee('Belum mengumpulkan');

        $statistik = $response->viewData('statistik');
        $this->assertSame([
            'jumlah_peserta' => 4,
            'sudah_mengumpulkan' => 3,
            'belum_mengumpulkan' => 1,
            'belum_dinilai' => 2,
            'sudah_dinilai' => 1,
            'terlambat' => 1,
        ], $statistik);

        $baris = $response->viewData('peserta');
        $this->assertSame('Sudah dinilai', $baris->firstWhere('nama', 'Budi')['status_label']);
        $this->assertTrue($baris->firstWhere('nama', 'Budi')['terlambat']);
        $this->assertSame('80.00 / 100', $baris->firstWhere('nama', 'Budi')['nilai_label']);
        $this->assertFalse($baris->firstWhere('nama', 'Sari')['sudah_mengumpulkan']);
        $this->assertNull($baris->firstWhere('nama', 'Sari')['detail_url']);
    }

    public function test_peserta_nonaktif_berpengumpulan_tetap_terlihat_sebagai_arsip(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'DP-H');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Arsip');

        $aktifTanpa = $this->enroll($this->mahasiswa('Aktif Tanpa'), $kelas);
        $nonaktifDengan = $this->enroll($this->mahasiswa('Arsip Nonaktif'), $kelas, StatusPeserta::Nonaktif);
        $mengulangTanpa = $this->enroll($this->mahasiswa('Mengulang Tanpa'), $kelas, StatusPeserta::Mengulang);

        $this->submission($tugas, $nonaktifDengan, ['catatan' => 'Arsip lama']);

        $response = $this->actingAs($dosen)->get(
            route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelas, $tugas]),
        );

        $response->assertOk()
            ->assertSee('Arsip Nonaktif')
            ->assertSee('Aktif Tanpa')
            ->assertDontSee('Mengulang Tanpa');

        $baris = $response->viewData('peserta');
        $arsip = $baris->firstWhere('nama', 'Arsip Nonaktif');
        $this->assertTrue($arsip['arsip']);
        $this->assertSame('Nonaktif', $arsip['status_peserta_label']);
        $this->assertTrue($arsip['sudah_mengumpulkan']);

        $this->assertTrue($baris->firstWhere('nama', 'Aktif Tanpa')['arsip'] === false);
        $this->assertFalse($baris->firstWhere('nama', 'Aktif Tanpa')['sudah_mengumpulkan']);
        $this->assertNull($baris->firstWhere('nama', 'Mengulang Tanpa'));

        $this->assertSame(2, $response->viewData('statistik')['jumlah_peserta']);
        $this->assertSame(1, $response->viewData('statistik')['sudah_mengumpulkan']);
        $this->assertSame(1, $response->viewData('statistik')['belum_mengumpulkan']);

        $this->assertDatabaseHas('peserta_mata_kuliah', ['id' => $mengulangTanpa->id]);
    }

    public function test_get_daftar_tidak_membuat_record_pengumpulan_baru(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'DP-I');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Tanpa Pengumpulan');
        $this->enroll($this->mahasiswa('Rani'), $kelas);

        $sebelum = PengumpulanTugas::count();

        $response = $this->actingAs($dosen)->get(
            route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelas, $tugas]),
        );

        $response->assertOk()
            ->assertSee('Belum mengumpulkan')
            ->assertSee('Belum ada pengumpulan');
        $this->assertSame($sebelum, PengumpulanTugas::count());
    }

    public function test_halaman_detail_menampilkan_data_pengumpulan_yang_sesuai(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'DP-J');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Detail');
        $peserta = $this->enroll($this->mahasiswa('Rani'), $kelas);
        $pengumpulan = $this->submission($tugas, $peserta, [
            'file_name' => 'jawaban.pdf',
            'file_size' => 2048,
            'catatan' => 'Catatan untuk dosen',
            'nilai' => '90.00',
            'umpan_balik' => 'Kerja bagus.',
            'dinilai_oleh' => $dosen->id,
            'dinilai_pada' => now()->subMinutes(15),
        ]);

        $response = $this->actingAs($dosen)->get(
            route('dosen.mata-kuliah.tugas.pengumpulan.show', [$kelas, $tugas, $pengumpulan]),
        );

        $response->assertOk()
            ->assertViewIs('dosen.mata-kuliah.tugas.pengumpulan.show')
            ->assertSee('Rani')
            ->assertSee('jawaban.pdf')
            ->assertSee('Catatan untuk dosen')
            ->assertSee('90.00 / 100')
            ->assertSee('Kerja bagus.');
    }

    public function test_daftar_tugas_menautkan_ke_halaman_pengumpulan(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, 'DP-K');
        $tugas = $this->tugas($kelas, $dosen, 'Tugas Dengan Pengumpulan');
        $peserta = $this->enroll($this->mahasiswa('Rani'), $kelas);
        $this->submission($tugas, $peserta);

        $response = $this->actingAs($dosen)->get(route('dosen.mata-kuliah.tugas.index', $kelas));

        $response->assertOk()
            ->assertSee('Lihat pengumpulan');

        $item = collect($response->viewData('tugas'))->firstWhere('id', $tugas->id);
        $this->assertSame(1, $item['jumlah_pengumpulan']);
        $this->assertSame(1, $item['jumlah_belum_dinilai']);
    }

    private function dosen(): User
    {
        return User::factory()->dosen()->create();
    }

    private function mahasiswa(string $nama): User
    {
        return User::factory()->mahasiswa()->create(['name' => $nama]);
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
            'kode' => 'MK-DP-'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Pengumpulan '.$this->urutan,
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

    private function enroll(User $mahasiswa, KelasMataKuliah $kelas, StatusPeserta $status = StatusPeserta::Aktif): PesertaMataKuliah
    {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status->value,
            'tanggal_daftar' => now()->subWeek(),
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
            'dipublikasikan_pada' => $dipublikasikan ?? now()->subDay(),
            'deadline' => Carbon::parse('2026-11-30 23:59:00'),
            'nilai_maksimal' => '100',
        ])->save();

        return $tugas;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function submission(Tugas $tugas, PesertaMataKuliah $peserta, array $overrides = []): PengumpulanTugas
    {
        $data = array_merge([
            'tugas_id' => $tugas->id,
            'peserta_mata_kuliah_id' => $peserta->id,
            'file_path' => null,
            'file_name' => null,
            'file_mime' => null,
            'file_size' => null,
            'catatan' => null,
            'dikumpulkan_pada' => now()->subHour(),
            'terlambat' => false,
        ], $overrides);

        $pengumpulan = new PengumpulanTugas();
        $pengumpulan->forceFill($data)->save();

        return $pengumpulan;
    }
}
