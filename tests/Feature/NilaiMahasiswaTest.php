<?php

namespace Tests\Feature;

use App\Enums\KomponenNilai;
use App\Models\Nilai;
use App\Models\User;
use App\Services\Akademik\IndeksPrestasiService;
use App\Services\Akademik\PenilaianService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataNilai;
use Tests\TestCase;

class NilaiMahasiswaTest extends TestCase
{
    use MembuatDataNilai;
    use RefreshDatabase;

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

    public function test_halaman_nilai_hanya_untuk_mahasiswa(): void
    {
        $mahasiswa = $this->mahasiswaNilai();

        $this->get(route('mahasiswa.nilai'))->assertRedirect(route('login'));
        $this->actingAs($this->dosenNilai())->get(route('mahasiswa.nilai'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('mahasiswa.nilai'))->assertForbidden();
        $this->actingAs($mahasiswa)->get(route('mahasiswa.nilai'))->assertOk();
    }

    public function test_mahasiswa_hanya_melihat_mata_kuliah_yang_diikutinya(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $mahasiswa = $this->mahasiswaNilai();
        $mahasiswaLain = $this->mahasiswaNilai();

        $mkSendiri = $this->mataKuliahNilai('MK-SENDIRI');
        $mkLain = $this->mataKuliahNilai('MK-LAIN');
        $kelasSendiri = $this->kelasNilai($dosen, $mkSendiri, $tahun, 'AMS', true);
        $kelasLain = $this->kelasNilai($dosen, $mkLain, $tahun, 'AML', true);

        $pesertaSendiri = $this->pesertaNilai($kelasSendiri, $mahasiswa);
        $pesertaLain = $this->pesertaNilai($kelasLain, $mahasiswaLain);
        $tugasSendiri = $this->tugasNilai($kelasSendiri, $dosen);
        $tugasLain = $this->tugasNilai($kelasLain, $dosen);
        $this->kumpulkanNilai($tugasSendiri, $pesertaSendiri, '80', $dosen);
        $this->kumpulkanNilai($tugasLain, $pesertaLain, '100', $dosen);
        $this->nilaiKomponen($pesertaSendiri, KomponenNilai::Uts, '80', $dosen);
        $this->nilaiKomponen($pesertaSendiri, KomponenNilai::Uas, '80', $dosen);
        $this->nilaiKomponen($pesertaLain, KomponenNilai::Uts, '100', $dosen);
        $this->nilaiKomponen($pesertaLain, KomponenNilai::Uas, '100', $dosen);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.nilai'))
            ->assertOk()
            ->assertSee('MK-SENDIRI')
            ->assertDontSee('MK-LAIN');

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.nilai.show', $mkLain->getKey()))
            ->assertForbidden();

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.nilai.show', $mkSendiri->getKey()))
            ->assertOk()
            ->assertSee('Dihitung untuk IPK');
    }

    public function test_halaman_menampilkan_nilai_akhir_huruf_dan_bobot(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $mahasiswa = $this->mahasiswaNilai();
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai('MK-TAMPIL', 3), $tahun, 'AMT', false);
        $peserta = $this->pesertaNilai($kelas, $mahasiswa);
        $this->nilaiKomponen($peserta, KomponenNilai::Uts, '80', $dosen);
        $this->nilaiKomponen($peserta, KomponenNilai::Uas, '90', $dosen);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.nilai'))
            ->assertOk()
            ->assertSee('86.11')
            ->assertSee('4.00')
            ->assertSee('Lengkap');
    }

    public function test_ip_semester_memakai_nilai_pengambilan_dan_ipk_memakai_nilai_terbaik(): void
    {
        $dosen = $this->dosenNilai();
        $mahasiswa = $this->mahasiswaNilai();
        $mataKuliah = $this->mataKuliahNilai('MK-ULANG', 3);
        $tahunAwal = $this->tahunNilai(2025);
        $tahunAkhir = $this->tahunNilai(2026);
        $kelasAwal = $this->kelasNilai($dosen, $mataKuliah, $tahunAwal, 'AUA', false);
        $kelasAkhir = $this->kelasNilai($dosen, $mataKuliah, $tahunAkhir, 'AUB', false);

        $pesertaAwal = $this->pesertaNilai($kelasAwal, $mahasiswa);
        $pesertaAkhir = $this->pesertaNilai($kelasAkhir, $mahasiswa);
        $this->nilaiKomponen($pesertaAwal, KomponenNilai::Uts, '80', $dosen);
        $this->nilaiKomponen($pesertaAwal, KomponenNilai::Uas, '80', $dosen);
        $this->nilaiKomponen($pesertaAkhir, KomponenNilai::Uts, '60', $dosen);
        $this->nilaiKomponen($pesertaAkhir, KomponenNilai::Uas, '60', $dosen);

        /** @var IndeksPrestasiService $service */
        $service = app(IndeksPrestasiService::class);

        $this->assertSame('4.00', $service->semester($mahasiswa->getKey(), $tahunAwal->getKey())['ip']);
        $this->assertSame('2.00', $service->semester($mahasiswa->getKey(), $tahunAkhir->getKey())['ip']);

        $ipk = $service->ipk($mahasiswa->getKey());
        $this->assertSame('4.00', $ipk['ipk']);
        $this->assertSame(3, $ipk['total_sks']);
        $this->assertCount(1, $ipk['baris']);
        $this->assertSame($pesertaAwal->getKey(), $ipk['baris']->first()['peserta_id']);
    }

    public function test_nilai_e_tetap_masuk_denominator_ipk(): void
    {
        $dosen = $this->dosenNilai();
        $mahasiswa = $this->mahasiswaNilai();
        $tahun = $this->tahunNilai(2026);
        $kelasA = $this->kelasNilai($dosen, $this->mataKuliahNilai('MK-A', 3), $tahun, 'AEA', false);
        $kelasE = $this->kelasNilai($dosen, $this->mataKuliahNilai('MK-E', 2), $tahun, 'AEB', false);

        foreach ([[$kelasA, '80'], [$kelasE, '0']] as [$kelas, $angka]) {
            $peserta = $this->pesertaNilai($kelas, $mahasiswa);
            $this->nilaiKomponen($peserta, KomponenNilai::Uts, $angka, $dosen);
            $this->nilaiKomponen($peserta, KomponenNilai::Uas, $angka, $dosen);
        }

        /** @var IndeksPrestasiService $service */
        $service = app(IndeksPrestasiService::class);
        $ipk = $service->ipk($mahasiswa->getKey());

        $this->assertSame('2.40', $ipk['ipk']);
        $this->assertSame(5, $ipk['total_sks']);
    }

    public function test_tie_break_ipk_memilih_periode_paling_awal(): void
    {
        $dosen = $this->dosenNilai();
        $mahasiswa = $this->mahasiswaNilai();
        $mataKuliah = $this->mataKuliahNilai('MK-TIE', 3);
        $tahunAwal = $this->tahunNilai(2025);
        $tahunAkhir = $this->tahunNilai(2026);

        $pesertaAwal = $this->pesertaNilai(
            $this->kelasNilai($dosen, $mataKuliah, $tahunAwal, 'TIEA', false),
            $mahasiswa,
        );
        $this->pesertaNilai(
            $this->kelasNilai($dosen, $mataKuliah, $tahunAkhir, 'TIEB', false),
            $mahasiswa,
        );

        foreach ([$pesertaAwal] as $peserta) {
            $this->nilaiKomponen($peserta, KomponenNilai::Uts, '80', $dosen);
            $this->nilaiKomponen($peserta, KomponenNilai::Uas, '80', $dosen);
        }
        $pesertaAkhir = $mahasiswa->pendaftaranKelas()
            ->whereHas('kelasMataKuliah', fn ($query) => $query->where('kode_kelas', 'TIEB'))
            ->firstOrFail();
        $this->nilaiKomponen($pesertaAkhir, KomponenNilai::Uts, '80', $dosen);
        $this->nilaiKomponen($pesertaAkhir, KomponenNilai::Uas, '80', $dosen);

        /** @var IndeksPrestasiService $service */
        $service = app(IndeksPrestasiService::class);
        $riwayat = $service->riwayatMataKuliah($mahasiswa->getKey(), $mataKuliah->getKey());

        $this->assertSame($pesertaAwal->getKey(), $riwayat['terbaik_peserta_id']);
    }

    public function test_perubahan_nilai_mengubah_ip_dan_ipk_secara_dinamis(): void
    {
        $dosen = $this->dosenNilai();
        $mahasiswa = $this->mahasiswaNilai();
        $tahun = $this->tahunNilai(2026);
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai('MK-DIN', 3), $tahun, 'ADN', false);
        $peserta = $this->pesertaNilai($kelas, $mahasiswa);
        $this->nilaiKomponen($peserta, KomponenNilai::Uts, '80', $dosen);
        $uas = $this->nilaiKomponen($peserta, KomponenNilai::Uas, '80', $dosen);

        /** @var IndeksPrestasiService $service */
        $service = app(IndeksPrestasiService::class);
        $this->assertSame('4.00', $service->semester($mahasiswa->getKey(), $tahun->getKey())['ip']);

        $uas->forceFill(['nilai' => '55.00'])->save();

        $this->assertSame('2.00', $service->semester($mahasiswa->getKey(), $tahun->getKey())['ip']);
        $this->assertSame('2.00', $service->ipk($mahasiswa->getKey())['ipk']);
    }

    public function test_nilai_belum_lengkap_tidak_dimasukkan_dalam_ipk(): void
    {
        $dosen = $this->dosenNilai();
        $mahasiswa = $this->mahasiswaNilai();
        $tahun = $this->tahunNilai(2026);
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai('MK-BLM', 3), $tahun, 'ABL', false);
        $peserta = $this->pesertaNilai($kelas, $mahasiswa);
        $this->nilaiKomponen($peserta, KomponenNilai::Uts, '80', $dosen);

        /** @var IndeksPrestasiService $service */
        $service = app(IndeksPrestasiService::class);
        $ipk = $service->ipk($mahasiswa->getKey());

        $this->assertNull($ipk['ipk']);
        $this->assertSame(0, $ipk['total_sks']);
    }

    public function test_nilai_tugas_tidak_mengikutsertakan_pengumpulan_kelas_lain(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelasSendiri = $this->kelasNilai($dosen, $this->mataKuliahNilai('MK-TS', 3), $tahun, 'TSA', true);
        $kelasLain = $this->kelasNilai($dosen, $this->mataKuliahNilai('MK-TL', 3), $tahun, 'TSB', true);
        $mahasiswa = $this->mahasiswaNilai();
        $pesertaSendiri = $this->pesertaNilai($kelasSendiri, $mahasiswa);
        $tugasSendiri = $this->tugasNilai($kelasSendiri, $dosen);
        $tugasLain = $this->tugasNilai($kelasLain, $dosen);
        $this->kumpulkanNilai($tugasSendiri, $pesertaSendiri, '80', $dosen);
        $this->kumpulkanNilai($tugasLain, $pesertaSendiri, '100', $dosen);

        /** @var PenilaianService $service */
        $service = app(PenilaianService::class);

        $this->assertSame('80.00', $service->nilaiTugas($pesertaSendiri));
        $this->assertNull(Nilai::query()->where('komponen', 'tugas')->first());
    }
}
