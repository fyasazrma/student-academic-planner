<?php

namespace Tests\Unit;

use App\Enums\NilaiHuruf;
use App\Enums\StatusNilaiAkhir;
use App\Services\Akademik\NilaiAkhirService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NilaiAkhirServiceTest extends TestCase
{
    private NilaiAkhirService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new NilaiAkhirService();
    }

    public function test_nilai_akhir_memakai_bobot_35_55_10(): void
    {
        $hasil = $this->service->hitung(80, 90, 100, true);

        $this->assertSame(StatusNilaiAkhir::Lengkap, $hasil['status']);
        $this->assertSame('87.50', $hasil['nilai_akhir']);
        $this->assertSame(NilaiHuruf::A, $hasil['nilai_huruf']);
        $this->assertSame(4.0, $hasil['bobot_mutu']);
    }

    public function test_tanpa_tugas_memakai_rasio_eksak_35_90_dan_55_90(): void
    {
        $hasil = $this->service->hitung(80, 90, null, false);

        $this->assertSame('86.11', $hasil['nilai_akhir']);
        $this->assertNotSame('86.67', $hasil['nilai_akhir']);
    }

    public function test_pembulatan_setengah_ke_atas_deterministik(): void
    {
        $hasil = $this->service->hitung(50, 80.15, null, false);

        $this->assertSame('68.43', $hasil['nilai_akhir']);
    }

    public function test_konfigurasi_belum_ditentukan_menghalangi_nilai_akhir(): void
    {
        $hasil = $this->service->hitung(80, 90, 90, null);

        $this->assertSame(StatusNilaiAkhir::BelumLengkap, $hasil['status']);
        $this->assertNull($hasil['nilai_akhir']);
        $this->assertContains('konfigurasi_belum_ditentukan', $hasil['alasan']);
    }

    public function test_komponen_wajib_belum_tersedia_menghalangi_nilai_akhir(): void
    {
        $hasil = $this->service->hitung(null, 90, 80, true);

        $this->assertSame(StatusNilaiAkhir::BelumLengkap, $hasil['status']);
        $this->assertContains('uts_belum_tersedia', $hasil['alasan']);
    }

    public function test_tugas_belum_lengkap_menghalangi_nilai_akhir_saat_dipakai(): void
    {
        $hasil = $this->service->hitung(80, 90, null, true);

        $this->assertSame(StatusNilaiAkhir::BelumLengkap, $hasil['status']);
        $this->assertContains('tugas_belum_lengkap', $hasil['alasan']);
    }

    public function test_nilai_nol_adalah_nilai_yang_valid(): void
    {
        $hasil = $this->service->hitung(0, 0, 0, true);

        $this->assertSame('0.00', $hasil['nilai_akhir']);
        $this->assertSame(NilaiHuruf::E, $hasil['nilai_huruf']);
        $this->assertSame(0.0, $hasil['bobot_mutu']);
    }

    public function test_nilai_di_luar_rentang_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->hitung(-1, 80, null, false);
    }

    public function test_nilai_melebih_seratus_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->hitung(80, 100.01, null, false);
    }

    public function test_konversi_huruf_pada_setiap_batas(): void
    {
        $kasus = [
            [100, NilaiHuruf::A],
            [80, NilaiHuruf::A],
            [79.99, NilaiHuruf::B],
            [70, NilaiHuruf::B],
            [69.99, NilaiHuruf::C],
            [60, NilaiHuruf::C],
            [59.99, NilaiHuruf::D],
            [50, NilaiHuruf::D],
            [49.99, NilaiHuruf::E],
            [0, NilaiHuruf::E],
        ];

        foreach ($kasus as [$angka, $huruf]) {
            $this->assertSame($huruf, NilaiHuruf::dariAngka($angka));
        }
    }

    public function test_bobot_mutu_sesuai_huruf(): void
    {
        $this->assertSame(4.0, NilaiHuruf::A->bobotMutu());
        $this->assertSame(3.0, NilaiHuruf::B->bobotMutu());
        $this->assertSame(2.0, NilaiHuruf::C->bobotMutu());
        $this->assertSame(1.0, NilaiHuruf::D->bobotMutu());
        $this->assertSame(0.0, NilaiHuruf::E->bobotMutu());
    }

    public function test_agregasi_tugas_hanya_memakai_pengumpulan_sudah_dinilai(): void
    {
        $dinilai = static fn (?string $nilai): object => (object) [
            'nilai' => $nilai,
            'dinilai_pada' => $nilai === null ? null : now(),
        ];

        $hasil = $this->service->agregasiNilaiTugas([
            $dinilai('80'),
            $dinilai('90'),
            $dinilai('0'),
            $dinilai(null),
        ]);

        $this->assertSame('56.67', $hasil);
    }

    public function test_agregasi_tugas_kosong_mengembalikan_null(): void
    {
        $this->assertNull($this->service->agregasiNilaiTugas([]));
    }
}
