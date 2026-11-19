<?php

namespace Tests\Feature;

use App\Enums\MetodeAbsensi;
use App\Enums\StatusAbsensi;
use App\Enums\StatusPeserta;
use App\Enums\StatusSesiAbsensi;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataAbsensi;
use Tests\TestCase;

class AbsensiMahasiswaTest extends TestCase
{
    use MembuatDataAbsensi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow(Carbon::parse('2026-11-19 09:30:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_halaman_absensi_mahasiswa_membutuhkan_role(): void
    {
        $mahasiswa = $this->mahasiswaAbsensi();
        $dosen = $this->dosenAbsensi();
        $admin = User::factory()->admin()->create();

        $this->get(route('mahasiswa.absensi.index'))->assertRedirect(route('login'));
        $this->actingAs($dosen)->get(route('mahasiswa.absensi.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('mahasiswa.absensi.index'))->assertForbidden();
        $this->actingAs($mahasiswa)->get(route('mahasiswa.absensi.index'))->assertOk();
        $this->actingAs($mahasiswa)->get(route('mahasiswa.absensi.riwayat'))->assertOk();
        $this->actingAs($mahasiswa)->get(route('mahasiswa.absensi.rekap'))->assertOk();
    }

    public function test_mahasiswa_hanya_melihat_dan_mengisi_sesi_kelasnya(): void
    {
        $dosen = $this->dosenAbsensi();
        $mahasiswa = $this->mahasiswaAbsensi();
        $kelas = $this->kelasAbsensi($dosen, $this->mataKuliahAbsensi('MKA001'), null, 'KELASA');
        $kelasLain = $this->kelasAbsensi($dosen, $this->mataKuliahAbsensi('MKB001'), null, 'KELASB');
        $sesi = $this->sesiAbsensi($kelas, $dosen);
        $sesiLain = $this->sesiAbsensi($kelasLain, $dosen, 1, '2026-11-19', '08:00', '10:00');
        $this->pesertaAbsensi($kelas, $mahasiswa);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.absensi.index'))
            ->assertOk()
            ->assertSee('KELASA')
            ->assertDontSee('KELASB');

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.absensi.simpan', $sesiLain->getKey()))
            ->assertForbidden();
        $this->assertDatabaseMissing('absensi', ['sesi_absensi_id' => $sesiLain->getKey()]);

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.absensi.simpan', $sesi->getKey()))
            ->assertRedirect(route('mahasiswa.absensi.index'));
        $this->assertDatabaseHas('absensi', ['sesi_absensi_id' => $sesi->getKey()]);
    }

    public function test_pengisian_mandiri_mencatat_status_hadir(): void
    {
        $dosen = $this->dosenAbsensi();
        $mahasiswa = $this->mahasiswaAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $peserta = $this->pesertaAbsensi($kelas, $mahasiswa);
        $sesi = $this->sesiAbsensi($kelas, $dosen);

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.absensi.simpan', $sesi->getKey()))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'status' => 'hadir',
            'metode' => 'mandiri',
            'dicatat_oleh' => null,
        ]);
    }

    public function test_pengisian_ditolak_di_luar_rentang_waktu(): void
    {
        $dosen = $this->dosenAbsensi();
        $mahasiswa = $this->mahasiswaAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->pesertaAbsensi($kelas, $mahasiswa);
        $sesi = $this->sesiAbsensi($kelas, $dosen);

        Carbon::setTestNow(Carbon::parse('2026-11-19 07:59:00', 'Asia/Jakarta'));
        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.absensi.simpan', $sesi->getKey()))
            ->assertSessionHas('error');

        Carbon::setTestNow(Carbon::parse('2026-11-19 10:01:00', 'Asia/Jakarta'));
        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.absensi.simpan', $sesi->getKey()))
            ->assertSessionHas('error');

        $this->assertSame(0, Absensi::query()->count());
    }

    public function test_pengisian_ditolak_saat_sesi_sudah_ditutup(): void
    {
        $dosen = $this->dosenAbsensi();
        $mahasiswa = $this->mahasiswaAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->pesertaAbsensi($kelas, $mahasiswa);
        $sesi = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '08:00', '10:00', StatusSesiAbsensi::Ditutup);

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.absensi.simpan', $sesi->getKey()))
            ->assertSessionHas('error');
        $this->assertSame(0, Absensi::query()->count());
    }

    public function test_peserta_nonaktif_tidak_dapat_mengisi_mengulang_dapat(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $nonaktif = $this->mahasiswaAbsensi();
        $mengulang = $this->mahasiswaAbsensi();
        $this->pesertaAbsensi($kelas, $nonaktif, StatusPeserta::Nonaktif);
        $this->pesertaAbsensi($kelas, $mengulang, StatusPeserta::Mengulang);
        $sesi = $this->sesiAbsensi($kelas, $dosen);

        $this->actingAs($nonaktif)
            ->post(route('mahasiswa.absensi.simpan', $sesi->getKey()))
            ->assertSessionHas('error');

        $this->actingAs($mengulang)
            ->post(route('mahasiswa.absensi.simpan', $sesi->getKey()))
            ->assertSessionHas('status');

        $this->assertSame(1, Absensi::query()->count());
    }

    public function test_absensi_ganda_dicegah(): void
    {
        $dosen = $this->dosenAbsensi();
        $mahasiswa = $this->mahasiswaAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->pesertaAbsensi($kelas, $mahasiswa);
        $sesi = $this->sesiAbsensi($kelas, $dosen);

        $url = route('mahasiswa.absensi.simpan', $sesi->getKey());
        $this->actingAs($mahasiswa)->post($url)->assertSessionHas('status');
        $this->actingAs($mahasiswa)->post($url)->assertSessionHas('error');

        $this->assertSame(1, Absensi::query()->count());
    }

    public function test_mahasiswa_tidak_dapat_mengirim_status_selain_hadir(): void
    {
        $dosen = $this->dosenAbsensi();
        $mahasiswa = $this->mahasiswaAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->pesertaAbsensi($kelas, $mahasiswa);
        $sesi = $this->sesiAbsensi($kelas, $dosen);
        $url = route('mahasiswa.absensi.simpan', $sesi->getKey());

        $this->actingAs($mahasiswa)
            ->post($url, ['status' => 'alfa'])
            ->assertSessionHasErrors('status');
        $this->actingAs($mahasiswa)
            ->post($url, ['status' => 'sakit'])
            ->assertSessionHasErrors('status');
        $this->assertSame(0, Absensi::query()->count());

        $this->actingAs($mahasiswa)
            ->post($url, ['status' => 'hadir'])
            ->assertSessionHas('status');
        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'status' => 'hadir',
            'metode' => MetodeAbsensi::Mandiri->value,
        ]);
    }

    public function test_riwayat_dan_rekap_hanya_data_mahasiswa_sendiri(): void
    {
        $dosen = $this->dosenAbsensi();
        $mahasiswa = $this->mahasiswaAbsensi('Saya', 'SAYA01');
        $mahasiswaLain = $this->mahasiswaAbsensi('Orang Lain', 'LAIN01');
        $kelas = $this->kelasAbsensi($dosen, null, null, 'KELASSAYA');
        $kelasLain = $this->kelasAbsensi($dosen, null, null, 'KELASLAIN');
        $peserta = $this->pesertaAbsensi($kelas, $mahasiswa);
        $pesertaLain = $this->pesertaAbsensi($kelasLain, $mahasiswaLain);

        $sesiSatu = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-17', '08:00', '10:00', StatusSesiAbsensi::Ditutup);
        $sesiDua = $this->sesiAbsensi($kelas, $dosen, 2, '2026-11-18', '08:00', '10:00', StatusSesiAbsensi::Ditutup);
        $sesiTiga = $this->sesiAbsensi($kelas, $dosen, 3, '2026-11-19', '08:00', '10:00');
        $sesiOrangLain = $this->sesiAbsensi($kelasLain, $dosen, 1, '2026-11-17', '08:00', '10:00', StatusSesiAbsensi::Ditutup);

        $this->catatAbsensi($sesiSatu, $peserta, StatusAbsensi::Hadir, MetodeAbsensi::Mandiri->value);
        $this->catatAbsensi($sesiDua, $peserta, StatusAbsensi::Alfa, MetodeAbsensi::Otomatis->value);
        $this->catatAbsensi($sesiOrangLain, $pesertaLain, StatusAbsensi::Hadir);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.absensi.riwayat'))
            ->assertOk()
            ->assertSee('KELASSAYA')
            ->assertDontSee('KELASLAIN');

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.absensi.rekap'))
            ->assertOk()
            ->assertSee('50.00%');

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.absensi.index'))
            ->assertOk()
            ->assertSee('Isi absensi hadir');
    }
}
