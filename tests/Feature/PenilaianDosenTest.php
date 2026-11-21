<?php

namespace Tests\Feature;

use App\Enums\KomponenNilai;
use App\Models\Nilai;
use App\Models\RiwayatPerubahanNilai;
use App\Models\User;
use App\Services\Akademik\PenilaianService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataNilai;
use Tests\TestCase;

class PenilaianDosenTest extends TestCase
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

    public function test_halaman_nilai_hanya_untuk_dosen(): void
    {
        $dosen = $this->dosenNilai();
        $mahasiswa = $this->mahasiswaNilai();
        $admin = User::factory()->admin()->create();

        $this->get(route('dosen.nilai.index'))->assertRedirect(route('login'));

        $this->actingAs($mahasiswa)
            ->get(route('dosen.nilai.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('dosen.nilai.index'))
            ->assertForbidden();

        $this->actingAs($dosen)
            ->get(route('dosen.nilai.index'))
            ->assertOk();
    }

    public function test_dosen_hanya_dapat_melihat_kelasnya_sendiri(): void
    {
        $dosen = $this->dosenNilai();
        $dosenLain = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelasSendiri = $this->kelasNilai($dosen, $this->mataKuliahNilai(), $tahun, 'AVA');
        $kelasLain = $this->kelasNilai($dosenLain, $this->mataKuliahNilai(), $tahun, 'AVB');

        $this->actingAs($dosen)
            ->get(route('dosen.nilai.kelas', $kelasLain->getKey()))
            ->assertNotFound();

        $this->actingAs($dosen)
            ->get(route('dosen.nilai.kelas', $kelasSendiri->getKey()))
            ->assertOk()
            ->assertSee('AVA');
    }

    public function test_simpan_uts_membuat_nilai_dan_audit_lalu_update_tanpa_duplikat(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai(), $tahun, 'AVC', false);
        $peserta = $this->pesertaNilai($kelas, $this->mahasiswaNilai());
        $url = route('dosen.nilai.simpan', [$kelas->getKey(), $peserta->getKey(), 'uts']);

        $this->actingAs($dosen)
            ->post($url, ['nilai' => '85', 'alasan' => 'Nilai awal'])
            ->assertRedirect(route('dosen.nilai.kelas', $kelas->getKey()))
            ->assertSessionHas('status', 'Nilai berhasil ditambahkan.');

        $this->assertDatabaseHas('nilai', [
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'komponen' => 'uts',
            'nilai' => '85.00',
            'dinilai_oleh' => $dosen->getKey(),
        ]);
        $this->assertDatabaseHas('riwayat_perubahan_nilai', [
            'komponen' => 'uts',
            'nilai_sebelum' => null,
            'nilai_sesudah' => '85.00',
            'actor_id' => $dosen->getKey(),
            'alasan' => 'Nilai awal',
        ]);

        $this->actingAs($dosen)
            ->post($url, ['nilai' => '90.50', 'alasan' => 'Koreksi'])
            ->assertSessionHas('status', 'Nilai berhasil diperbarui.');

        $this->assertSame(
            1,
            Nilai::query()->where('peserta_mata_kuliah_id', $peserta->getKey())->where('komponen', 'uts')->count(),
        );
        $this->assertDatabaseHas('nilai', [
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'komponen' => 'uts',
            'nilai' => '90.50',
        ]);
        $this->assertDatabaseHas('riwayat_perubahan_nilai', [
            'komponen' => 'uts',
            'nilai_sebelum' => '85.00',
            'nilai_sesudah' => '90.50',
            'alasan' => 'Koreksi',
        ]);
        $this->assertSame(2, $peserta->refresh()->nilai()->where('komponen', 'uts')->first()->riwayatPerubahan()->count());
    }

    public function test_nilai_di_luar_rentang_ditolak(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai(), $tahun, 'AVD', false);
        $peserta = $this->pesertaNilai($kelas, $this->mahasiswaNilai());

        $this->actingAs($dosen)
            ->post(route('dosen.nilai.simpan', [$kelas->getKey(), $peserta->getKey(), 'uas']), ['nilai' => '101'])
            ->assertSessionHasErrors('nilai');

        $this->assertDatabaseMissing('nilai', [
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'komponen' => 'uas',
        ]);
    }

    public function test_komponen_tugas_tidak_bisa_ditulis_melalui_route(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai(), $tahun, 'AVE', true);
        $peserta = $this->pesertaNilai($kelas, $this->mahasiswaNilai());

        $this->actingAs($dosen)
            ->post(route('dosen.nilai.simpan', [$kelas->getKey(), $peserta->getKey(), 'tugas']), ['nilai' => '90'])
            ->assertNotFound();

        $hasil = app(PenilaianService::class)->simpan(
            $dosen->getKey(),
            $kelas->getKey(),
            $peserta->getKey(),
            KomponenNilai::Tugas,
            '90',
            null,
            $dosen,
        );

        $this->assertFalse($hasil['berhasil']);
        $this->assertDatabaseMissing('nilai', [
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'komponen' => 'tugas',
        ]);
    }

    public function test_perubahan_konfigurasi_tugas_dicatat_di_audit(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai(), $tahun, 'AVF', null);
        $url = route('dosen.nilai.konfigurasi', $kelas->getKey());

        $this->actingAs($dosen)
            ->post($url, ['gunakan_tugas' => '1', 'alasan' => 'Tugas dipakai'])
            ->assertSessionHas('status');

        $this->assertTrue($kelas->refresh()->gunakanTugas());
        $this->assertDatabaseHas('riwayat_perubahan_nilai', [
            'komponen' => 'konfigurasi_tugas',
            'nilai_sebelum' => null,
            'nilai_sesudah' => '1.00',
            'actor_id' => $dosen->getKey(),
            'alasan' => 'Tugas dipakai',
        ]);

        $this->actingAs($dosen)
            ->post($url, ['gunakan_tugas' => '0'])
            ->assertSessionHas('status');

        $this->assertFalse($kelas->refresh()->gunakanTugas());
        $this->assertSame(
            2,
            RiwayatPerubahanNilai::query()->where('komponen', 'konfigurasi_tugas')->count(),
        );
        $this->assertDatabaseHas('riwayat_perubahan_nilai', [
            'komponen' => 'konfigurasi_tugas',
            'nilai_sebelum' => '1.00',
            'nilai_sesudah' => '0.00',
        ]);
    }

    public function test_perubahan_konfigurasi_tugas_juga_tercatat_per_peserta_dan_tampil_di_riwayat(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai(), $tahun, 'AVH', null);
        $peserta = $this->pesertaNilai($kelas, $this->mahasiswaNilai('Mahasiswa Konfigurasi', 'NIM-KON'));

        $this->actingAs($dosen)
            ->post(route('dosen.nilai.konfigurasi', $kelas->getKey()), [
                'gunakan_tugas' => '1',
                'alasan' => 'Tugas diperhitungkan',
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('riwayat_perubahan_nilai', [
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'komponen' => 'konfigurasi_tugas',
            'nilai_sebelum' => null,
            'nilai_sesudah' => '1.00',
            'alasan' => 'Tugas diperhitungkan',
        ]);

        $this->actingAs($dosen)
            ->get(route('dosen.nilai.riwayat', [$kelas->getKey(), $peserta->getKey()]))
            ->assertOk()
            ->assertSee('Konfigurasi tugas')
            ->assertSee('Tugas diperhitungkan');
    }

    public function test_halaman_riwayat_menampilkan_perubahan_nilai(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai(), $tahun, 'AVG', false);
        $mahasiswa = $this->mahasiswaNilai('Mahasiswa Riwayat', 'NIM-RW');
        $peserta = $this->pesertaNilai($kelas, $mahasiswa);
        $url = route('dosen.nilai.simpan', [$kelas->getKey(), $peserta->getKey(), 'uts']);

        $this->actingAs($dosen)->post($url, ['nilai' => '85', 'alasan' => 'Nilai awal']);
        $this->actingAs($dosen)->post($url, ['nilai' => '90', 'alasan' => 'Revisi']);

        $this->actingAs($dosen)
            ->get(route('dosen.nilai.riwayat', [$kelas->getKey(), $peserta->getKey()]))
            ->assertOk()
            ->assertSee('Mahasiswa Riwayat')
            ->assertSee('85.00')
            ->assertSee('90.00')
            ->assertSee('Revisi');
    }

    public function test_dosen_tidak_dapat_menyimpan_nilai_peserta_kelas_lain(): void
    {
        $dosen = $this->dosenNilai();
        $dosenLain = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelasSendiri = $this->kelasNilai($dosen, $this->mataKuliahNilai(), $tahun, 'AVH', false);
        $kelasLain = $this->kelasNilai($dosenLain, $this->mataKuliahNilai(), $tahun, 'AVI', false);
        $pesertaLain = $this->pesertaNilai($kelasLain, $this->mahasiswaNilai());

        $this->actingAs($dosen)
            ->post(route('dosen.nilai.simpan', [$kelasSendiri->getKey(), $pesertaLain->getKey(), 'uts']), ['nilai' => '90'])
            ->assertNotFound();

        $this->assertDatabaseMissing('nilai', [
            'peserta_mata_kuliah_id' => $pesertaLain->getKey(),
        ]);
    }

    public function test_rekap_menandai_nilai_belum_lengkap_saat_konfigurasi_belum_dipilih(): void
    {
        $dosen = $this->dosenNilai();
        $tahun = $this->tahunNilai(2026);
        $kelas = $this->kelasNilai($dosen, $this->mataKuliahNilai(), $tahun, 'AVJ', null);
        $peserta = $this->pesertaNilai($kelas, $this->mahasiswaNilai());
        $this->nilaiKomponen($peserta, KomponenNilai::Uts, '80', $dosen);

        $this->actingAs($dosen)
            ->get(route('dosen.nilai.kelas', $kelas->getKey()))
            ->assertOk()
            ->assertSee('Belum lengkap')
            ->assertSee('Belum ditentukan');
    }
}
