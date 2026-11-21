<?php

namespace Tests\Feature;

use App\Enums\StatusPendaftaranKelas;
use App\Enums\StatusPeserta;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Kelas\KunciGabungKelasService;
use App\Services\Mahasiswa\MataKuliahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MataKuliahServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private ?User $dosen = null;

    private MataKuliahService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->service = app(MataKuliahService::class);
    }

    public function test_cari_kelas_hanya_menampilkan_kelas_yang_memenuhi_syarat(): void
    {
        [$layak, $kunciLayak] = $this->buatKelas(['nama_mk' => 'Pemrograman Laravel']);
        $this->buatKelas(['status' => StatusPendaftaranKelas::Tutup]);
        $this->buatKelas(['mata_kuliah' => $this->mataKuliah(false)]);
        $this->buatKelas(['tahun_akademik' => $this->tahun(false)]);
        $this->buatKelas(['dengan_kunci' => false]);

        $hasil = $this->service->cariKelas([]);
        $items = $hasil->items();

        $this->assertCount(1, $items);
        $this->assertSame($layak->id, $items[0]['id']);
        $this->assertSame(1, $hasil->total());
        $this->assertSame(MataKuliahService::PER_HALAMAN, $hasil->perPage());

        $this->assertSame('Pemrograman Laravel', $items[0]['nama_mata_kuliah']);
        $this->assertSame(3, $items[0]['sks']);
        $this->assertArrayHasKey('kode_mata_kuliah', $items[0]);
        $this->assertArrayHasKey('kode_kelas', $items[0]);
        $this->assertArrayHasKey('dosen', $items[0]);
        $this->assertArrayHasKey('tahun_akademik', $items[0]);
        $this->assertArrayHasKey('semester', $items[0]);
        $this->assertArrayHasKey('kapasitas_label', $items[0]);

        foreach (['peserta', 'materi', 'tugas', 'nilai', 'absensi', 'kode_gabung_hash'] as $kunciTerlarang) {
            $this->assertArrayNotHasKey($kunciTerlarang, $items[0]);
        }

        $hash = app(KunciGabungKelasService::class)->hash($kunciLayak);
        $this->assertStringNotContainsString($hash, json_encode($items));
        $this->assertStringNotContainsString($kunciLayak, json_encode($items));
    }

    public function test_cari_kelas_mendukung_pencarian_nama_kode_mata_kuliah_dan_kode_kelas(): void
    {
        [$web] = $this->buatKelas([
            'nama_mk' => 'Pemrograman Web',
            'kode_kelas' => 'XYZ',
        ]);
        [$basisData] = $this->buatKelas([
            'nama_mk' => 'Basis Data',
            'kode_kelas' => 'ABC',
        ]);

        $kodeMkWeb = MataKuliah::query()->whereKey($web->mata_kuliah_id)->value('kode');

        $this->assertSame(
            [$web->id],
            collect($this->service->cariKelas(['q' => 'Pemrograman'])->items())->pluck('id')->all(),
        );
        $this->assertSame(
            [$web->id],
            collect($this->service->cariKelas(['q' => $kodeMkWeb])->items())->pluck('id')->all(),
        );
        $this->assertSame(
            [$web->id],
            collect($this->service->cariKelas(['q' => 'XYZ'])->items())->pluck('id')->all(),
        );
        $this->assertSame(
            [$basisData->id],
            collect($this->service->cariKelas(['q' => 'Basis'])->items())->pluck('id')->all(),
        );
    }

    public function test_cari_kelas_menggunakan_paginasi_12(): void
    {
        for ($index = 0; $index < 14; $index++) {
            $this->buatKelas();
        }

        $hasil = $this->service->cariKelas([]);

        $this->assertSame(14, $hasil->total());
        $this->assertSame(12, $hasil->perPage());
        $this->assertCount(12, $hasil->items());
        $this->assertSame(1, $hasil->currentPage());
    }

    public function test_pencarian_tidak_menambah_query_sejumlah_jumlah_kelas(): void
    {
        $this->buatKelas();
        $this->buatKelas();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->service->cariKelas([]);
        $jumlahQueryDuaKelas = count(DB::getQueryLog());
        DB::disableQueryLog();

        for ($index = 0; $index < 8; $index++) {
            $this->buatKelas();
        }

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->service->cariKelas([]);
        $jumlahQuerySepuluhKelas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($jumlahQueryDuaKelas, $jumlahQuerySepuluhKelas);
    }

    public function test_kelas_tertutup_tidak_dapat_digabung(): void
    {
        [$kelas, $kunci] = $this->buatKelas(['status' => StatusPendaftaranKelas::Tutup]);
        $mahasiswa = User::factory()->mahasiswa()->create();

        $hasil = $this->service->gabung($mahasiswa->id, $kunci);

        $this->assertFalse($hasil['berhasil']);
        $this->assertSame(MataKuliahService::HASIL_PENDAFTARAN_TUTUP, $hasil['kode']);
        $this->assertSame($kelas->id, $hasil['kelas']?->id);
        $this->assertSame(0, PesertaMataKuliah::query()->count());
    }

    public function test_mata_kuliah_tidak_aktif_tidak_dapat_digabung(): void
    {
        [, $kunci] = $this->buatKelas(['mata_kuliah' => $this->mataKuliah(false)]);
        $mahasiswa = User::factory()->mahasiswa()->create();

        $hasil = $this->service->gabung($mahasiswa->id, $kunci);

        $this->assertFalse($hasil['berhasil']);
        $this->assertSame(MataKuliahService::HASIL_KELAS_TIDAK_TERSEDIA, $hasil['kode']);
        $this->assertSame(0, PesertaMataKuliah::query()->count());
    }

    public function test_tahun_akademik_tidak_aktif_tidak_dapat_digabung(): void
    {
        [, $kunci] = $this->buatKelas(['tahun_akademik' => $this->tahun(false)]);
        $mahasiswa = User::factory()->mahasiswa()->create();

        $hasil = $this->service->gabung($mahasiswa->id, $kunci);

        $this->assertFalse($hasil['berhasil']);
        $this->assertSame(MataKuliahService::HASIL_KELAS_TIDAK_TERSEDIA, $hasil['kode']);
        $this->assertSame(0, PesertaMataKuliah::query()->count());
    }

    public function test_kunci_salah_ditolak(): void
    {
        [, $kunci] = $this->buatKelas();
        $mahasiswa = User::factory()->mahasiswa()->create();

        $salah = $this->service->gabung($mahasiswa->id, strrev($kunci));
        $kosong = $this->service->gabung($mahasiswa->id, '');

        $this->assertSame(MataKuliahService::HASIL_KUNCI_TIDAK_VALID, $salah['kode']);
        $this->assertSame(MataKuliahService::HASIL_KUNCI_TIDAK_VALID, $kosong['kode']);
        $this->assertStringContainsString('tidak valid', $salah['pesan']);
        $this->assertSame(0, PesertaMataKuliah::query()->count());
    }

    public function test_kunci_benar_berhasil_menggabungkan_mahasiswa(): void
    {
        [$kelas, $kunci] = $this->buatKelas();
        $mahasiswa = User::factory()->mahasiswa()->create();

        $hasil = $this->service->gabung($mahasiswa->id, $kunci);

        $this->assertTrue($hasil['berhasil']);
        $this->assertSame(MataKuliahService::HASIL_BERHASIL, $hasil['kode']);
        $this->assertSame($kelas->id, $hasil['kelas']?->id);
        $this->assertSame($mahasiswa->id, $hasil['peserta']?->mahasiswa_id);

        $this->assertDatabaseHas('peserta_mata_kuliah', [
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => StatusPeserta::Aktif->value,
            'tanggal_daftar' => today()->format('Y-m-d 00:00:00'),
        ]);
    }

    public function test_kapasitas_penuh_menolak_penggabungan(): void
    {
        [$kelas, $kunci] = $this->buatKelas(['kapasitas' => 2]);
        $this->tambahPeserta($kelas, User::factory()->mahasiswa()->create());
        $this->tambahPeserta($kelas, User::factory()->mahasiswa()->create());
        $mahasiswa = User::factory()->mahasiswa()->create();

        $hasil = $this->service->gabung($mahasiswa->id, $kunci);

        $this->assertFalse($hasil['berhasil']);
        $this->assertSame(MataKuliahService::HASIL_KAPASITAS_PENUH, $hasil['kode']);
        $this->assertSame(2, PesertaMataKuliah::query()->count());
    }

    public function test_kapasitas_hanya_menghitung_peserta_aktif(): void
    {
        [$kelas, $kunci] = $this->buatKelas(['kapasitas' => 1]);
        $this->tambahPeserta($kelas, User::factory()->mahasiswa()->create(), StatusPeserta::Nonaktif);
        $this->tambahPeserta($kelas, User::factory()->mahasiswa()->create(), StatusPeserta::Mengulang);
        $mahasiswa = User::factory()->mahasiswa()->create();

        $hasil = $this->service->gabung($mahasiswa->id, $kunci);

        $this->assertTrue($hasil['berhasil']);
        $this->assertSame(3, PesertaMataKuliah::query()->count());
    }

    public function test_kapasitas_null_tetap_mengizinkan_penggabungan(): void
    {
        [$kelas, $kunci] = $this->buatKelas(['kapasitas' => null]);
        $this->tambahPeserta($kelas, User::factory()->mahasiswa()->create());
        $this->tambahPeserta($kelas, User::factory()->mahasiswa()->create());
        $this->tambahPeserta($kelas, User::factory()->mahasiswa()->create());
        $mahasiswa = User::factory()->mahasiswa()->create();

        $hasil = $this->service->gabung($mahasiswa->id, $kunci);

        $this->assertTrue($hasil['berhasil']);
        $this->assertSame(4, PesertaMataKuliah::query()->where('kelas_mata_kuliah_id', $kelas->id)->count());
    }

    public function test_penggabungan_ganda_ditolak(): void
    {
        [$kelas, $kunci] = $this->buatKelas();
        $mahasiswa = User::factory()->mahasiswa()->create();

        $pertama = $this->service->gabung($mahasiswa->id, $kunci);
        $kedua = $this->service->gabung($mahasiswa->id, $kunci);

        $this->assertTrue($pertama['berhasil']);
        $this->assertFalse($kedua['berhasil']);
        $this->assertSame(MataKuliahService::HASIL_SUDAH_TERDAFTAR, $kedua['kode']);
        $this->assertSame(1, PesertaMataKuliah::query()->count());
    }

    public function test_riwayat_nonaktif_atau_mengulang_tidak_diaktifkan_kembali(): void
    {
        $kode = [
            StatusPeserta::Nonaktif->value => MataKuliahService::HASIL_RIWAYAT_NONAKTIF,
            StatusPeserta::Mengulang->value => MataKuliahService::HASIL_SUDAH_TERDAFTAR,
        ];

        foreach ([StatusPeserta::Nonaktif, StatusPeserta::Mengulang] as $status) {
            [$kelas, $kunci] = $this->buatKelas();
            $mahasiswa = User::factory()->mahasiswa()->create();
            $lama = $this->tambahPeserta($kelas, $mahasiswa, $status);

            $hasil = $this->service->gabung($mahasiswa->id, $kunci);

            $this->assertFalse($hasil['berhasil']);
            $this->assertSame($kode[$status->value], $hasil['kode']);
            $this->assertSame($status === StatusPeserta::Nonaktif, str_contains($hasil['pesan'], 'Hubungi dosen'));
            $this->assertSame($status->value, $lama->fresh()->status->value);
            $this->assertSame(
                1,
                PesertaMataKuliah::query()->where('kelas_mata_kuliah_id', $kelas->id)->count(),
            );
        }
    }

    public function test_identitas_mahasiswa_selalu_berasal_dari_parameter_user_id(): void
    {
        [$kelas, $kunci] = $this->buatKelas();
        $pemilik = User::factory()->mahasiswa()->create();
        $lain = User::factory()->mahasiswa()->create();

        $hasil = $this->service->gabung($pemilik->id, $kunci);

        $this->assertTrue($hasil['berhasil']);
        $this->assertDatabaseHas('peserta_mata_kuliah', [
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $pemilik->id,
        ]);
        $this->assertDatabaseMissing('peserta_mata_kuliah', [
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $lain->id,
        ]);
    }

    public function test_rotasi_kunci_tidak_menghapus_peserta_lama(): void
    {
        [$kelas, $kunciLama] = $this->buatKelas();
        $pesertaLama = User::factory()->mahasiswa()->create();
        $pendaftaranLama = $this->tambahPeserta($kelas, $pesertaLama);
        $kunciBaru = app(KunciGabungKelasService::class)->rotasi($kelas);
        $mahasiswaBaru = User::factory()->mahasiswa()->create();

        $denganKunciLama = $this->service->gabung($mahasiswaBaru->id, $kunciLama);
        $denganKunciBaru = $this->service->gabung($mahasiswaBaru->id, $kunciBaru);

        $this->assertSame(MataKuliahService::HASIL_KUNCI_TIDAK_VALID, $denganKunciLama['kode']);
        $this->assertTrue($denganKunciBaru['berhasil']);
        $this->assertTrue(PesertaMataKuliah::query()->whereKey($pendaftaranLama->id)->exists());
        $this->assertSame(2, PesertaMataKuliah::query()->count());
    }

    public function test_kelas_saya_hanya_memuat_pendaftaran_aktif_milik_user(): void
    {
        [$aktif] = $this->buatKelas();
        [$nonaktif] = $this->buatKelas();
        [$lain] = $this->buatKelas();

        $mahasiswa = User::factory()->mahasiswa()->create();
        $lainnya = User::factory()->mahasiswa()->create();

        $milikAktif = $this->tambahPeserta($aktif, $mahasiswa);
        $this->tambahPeserta($nonaktif, $mahasiswa, StatusPeserta::Nonaktif);
        $this->tambahPeserta($lain, $lainnya);

        $kelas = $this->service->kelasSaya($mahasiswa->id);

        $this->assertCount(1, $kelas);
        $this->assertSame($milikAktif->id, $kelas->first()->id);
        $this->assertSame($aktif->id, $kelas->first()->kelasMataKuliah->id);
        $this->assertTrue($kelas->first()->relationLoaded('kelasMataKuliah'));
        $this->assertTrue($kelas->first()->kelasMataKuliah->relationLoaded('mataKuliah'));
        $this->assertTrue($kelas->first()->kelasMataKuliah->relationLoaded('tahunAkademik'));
        $this->assertTrue($kelas->first()->kelasMataKuliah->relationLoaded('dosen'));
    }

    /**
     * @param  array<string, mixed>  $opsi
     * @return array{0: KelasMataKuliah, 1: ?string}
     */
    private function buatKelas(array $opsi = []): array
    {
        $this->urutan++;
        $nomor = $this->urutan;

        $mataKuliah = $opsi['mata_kuliah'] ?? $this->mataKuliah(true, $opsi['nama_mk'] ?? null);
        $tahun = $opsi['tahun_akademik'] ?? $this->tahun(true);

        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'dosen_id' => $this->dosen()->id,
            'kode_kelas' => $opsi['kode_kelas'] ?? 'K'.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT),
            'nama_kelas' => $opsi['nama_kelas'] ?? 'Kelas '.$nomor,
            'kapasitas' => $opsi['kapasitas'] ?? null,
            'status_pendaftaran' => $opsi['status'] ?? StatusPendaftaranKelas::Buka,
        ])->save();

        $kunci = null;
        if (($opsi['dengan_kunci'] ?? true) === true) {
            $kunci = app(KunciGabungKelasService::class)->terapkan($kelas);
        }

        return [$kelas->fresh(), $kunci];
    }

    private function mataKuliah(bool $aktif = true, ?string $nama = null): MataKuliah
    {
        $this->urutan++;
        $nomor = $this->urutan;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MK'.str_pad((string) $nomor, 4, '0', STR_PAD_LEFT),
            'nama' => $nama ?? 'Mata Kuliah '.$nomor,
            'sks' => 3,
            'program_studi' => 'Informatika',
            'semester' => 1,
            'aktif' => $aktif,
        ])->save();

        return $mataKuliah;
    }

    private function tahun(bool $aktif = true): TahunAkademik
    {
        $this->urutan++;
        $nomor = $this->urutan;

        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => 2020 + $nomor,
            'tahun_selesai' => 2021 + $nomor,
            'semester' => 'ganjil',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-12-31',
            'aktif' => $aktif,
        ])->save();

        return $tahun;
    }

    private function dosen(): User
    {
        if ($this->dosen === null) {
            $this->dosen = User::factory()->dosen()->create();
        }

        return $this->dosen;
    }

    private function tambahPeserta(
        KelasMataKuliah $kelas,
        User $mahasiswa,
        StatusPeserta|string $status = StatusPeserta::Aktif,
    ): PesertaMataKuliah {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status instanceof StatusPeserta ? $status->value : $status,
            'tanggal_daftar' => today()->toDateString(),
        ])->save();

        return $peserta;
    }
}
