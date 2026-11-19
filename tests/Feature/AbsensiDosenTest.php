<?php

namespace Tests\Feature;

use App\Enums\MetodeAbsensi;
use App\Enums\StatusAbsensi;
use App\Enums\StatusPeserta;
use App\Enums\StatusSesiAbsensi;
use App\Models\Absensi;
use App\Models\RiwayatPerubahanAbsensi;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Services\Absensi\RekapAbsensiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataAbsensi;
use Tests\TestCase;

class AbsensiDosenTest extends TestCase
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

    public function test_halaman_absensi_hanya_untuk_dosen(): void
    {
        $dosen = $this->dosenAbsensi();
        $mahasiswa = $this->mahasiswaAbsensi();
        $admin = User::factory()->admin()->create();

        $this->get(route('dosen.absensi.index'))->assertRedirect(route('login'));

        $this->actingAs($mahasiswa)->get(route('dosen.absensi.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('dosen.absensi.index'))->assertForbidden();
        $this->actingAs($dosen)->get(route('dosen.absensi.index'))->assertOk();
    }

    public function test_dosen_hanya_dapat_mengakses_kelas_dan_sesinya_sendiri(): void
    {
        $dosen = $this->dosenAbsensi('Dosen Satu');
        $dosenLain = $this->dosenAbsensi('Dosen Dua');
        $kelas = $this->kelasAbsensi($dosen);
        $kelasLain = $this->kelasAbsensi($dosenLain);
        $sesiLain = $this->sesiAbsensi($kelasLain, $dosenLain);

        $this->actingAs($dosen)
            ->get(route('dosen.absensi.kelas', $kelasLain->getKey()))
            ->assertNotFound();
        $this->actingAs($dosen)
            ->get(route('dosen.absensi.sesi.show', [$kelasLain->getKey(), $sesiLain->getKey()]))
            ->assertNotFound();
        $this->actingAs($dosen)
            ->post(route('dosen.absensi.sesi.store', $kelasLain->getKey()), $this->dataSesi(9))
            ->assertNotFound();
        $this->actingAs($dosen)
            ->get(route('dosen.absensi.kelas', $kelas->getKey()))
            ->assertOk();
    }

    public function test_crud_sesi_absensi(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);

        $this->actingAs($dosen)
            ->get(route('dosen.absensi.sesi.create', $kelas->getKey()))
            ->assertOk();

        $this->actingAs($dosen)
            ->post(route('dosen.absensi.sesi.store', $kelas->getKey()), $this->dataSesi(1))
            ->assertRedirect(route('dosen.absensi.kelas', $kelas->getKey()))
            ->assertSessionHas('status');

        $sesi = SesiAbsensi::query()->firstOrFail();
        $this->assertSame(1, $sesi->pertemuan_ke);
        $this->assertSame('08:00', substr((string) $sesi->waktu_mulai, 0, 5));
        $this->assertSame(StatusSesiAbsensi::Dibuka, $sesi->status);
        $this->assertSame($dosen->getKey(), (int) $sesi->dibuat_oleh);

        $this->actingAs($dosen)
            ->get(route('dosen.absensi.sesi.edit', [$kelas->getKey(), $sesi->getKey()]))
            ->assertOk();

        $this->actingAs($dosen)
            ->put(route('dosen.absensi.sesi.update', [$kelas->getKey(), $sesi->getKey()]), $this->dataSesi(2, 'Topik diubah'))
            ->assertRedirect(route('dosen.absensi.sesi.show', [$kelas->getKey(), $sesi->getKey()]));

        $this->assertDatabaseHas('sesi_absensi', [
            'id' => $sesi->getKey(),
            'pertemuan_ke' => 2,
            'topik' => 'Topik diubah',
        ]);

        $this->actingAs($dosen)
            ->delete(route('dosen.absensi.sesi.destroy', [$kelas->getKey(), $sesi->getKey()]))
            ->assertRedirect(route('dosen.absensi.kelas', $kelas->getKey()));
        $this->assertDatabaseMissing('sesi_absensi', ['id' => $sesi->getKey()]);
    }

    public function test_validasi_waktu_dan_duplikat_pertemuan(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->sesiAbsensi($kelas, $dosen, 1);

        $data = $this->dataSesi(2);
        $data['waktu_selesai'] = '07:00';

        $this->actingAs($dosen)
            ->post(route('dosen.absensi.sesi.store', $kelas->getKey()), $data)
            ->assertSessionHasErrors('waktu_selesai');
        $this->assertDatabaseMissing('sesi_absensi', ['kelas_mata_kuliah_id' => $kelas->getKey(), 'pertemuan_ke' => 2]);

        $this->actingAs($dosen)
            ->post(route('dosen.absensi.sesi.store', $kelas->getKey()), $this->dataSesi(1))
            ->assertSessionHas('error');
        $this->assertSame(1, SesiAbsensi::query()->count());
    }

    public function test_buka_tutup_sesi_dan_sesi_tertutup_tidak_dapat_dibuka_lagi(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $mahasiswa = $this->mahasiswaAbsensi();
        $mahasiswaLain = $this->mahasiswaAbsensi();
        $peserta = $this->pesertaAbsensi($kelas, $mahasiswa);
        $pesertaLain = $this->pesertaAbsensi($kelas, $mahasiswaLain);
        $sesi = $this->sesiAbsensi($kelas, $dosen);
        $this->catatAbsensi($sesi, $peserta, StatusAbsensi::Hadir, MetodeAbsensi::Mandiri->value);

        $this->actingAs($dosen)
            ->post(route('dosen.absensi.sesi.tutup', [$kelas->getKey(), $sesi->getKey()]))
            ->assertRedirect(route('dosen.absensi.sesi.show', [$kelas->getKey(), $sesi->getKey()]));

        $this->assertDatabaseHas('sesi_absensi', ['id' => $sesi->getKey(), 'status' => 'ditutup']);
        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $pesertaLain->getKey(),
            'status' => 'alfa',
            'metode' => 'otomatis',
            'dicatat_oleh' => null,
        ]);
        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'status' => 'hadir',
        ]);

        $this->actingAs($dosen)
            ->post(route('dosen.absensi.sesi.buka', [$kelas->getKey(), $sesi->getKey()]))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('sesi_absensi', ['id' => $sesi->getKey(), 'status' => 'ditutup']);
    }

    public function test_daftar_peserta_menampilkan_status_absensi(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $mahasiswa = $this->mahasiswaAbsensi('Andi Absensi', 'ANDI01');
        $peserta = $this->pesertaAbsensi($kelas, $mahasiswa, StatusPeserta::Mengulang);
        $sesi = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '08:00', '10:00', StatusSesiAbsensi::Ditutup);
        $this->catatAbsensi($sesi, $peserta, StatusAbsensi::Sakit);

        $this->actingAs($dosen)
            ->get(route('dosen.absensi.sesi.show', [$kelas->getKey(), $sesi->getKey()]))
            ->assertOk()
            ->assertSee('Andi Absensi')
            ->assertSee('Sakit')
            ->assertSee('Mengulang');
    }

    public function test_koreksi_hanya_setelah_sesi_ditutup_dan_menyimpan_audit(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $mahasiswa = $this->mahasiswaAbsensi();
        $peserta = $this->pesertaAbsensi($kelas, $mahasiswa);
        $sesi = $this->sesiAbsensi($kelas, $dosen);
        $this->catatAbsensi($sesi, $peserta, StatusAbsensi::Hadir, MetodeAbsensi::Mandiri->value);

        $url = route('dosen.absensi.koreksi', [$kelas->getKey(), $sesi->getKey(), $peserta->getKey()]);
        $this->actingAs($dosen)
            ->post($url, ['status' => 'alfa', 'alasan' => 'Tidak hadir tanpa keterangan'])
            ->assertSessionHas('error');

        $this->actingAs($dosen)
            ->post(route('dosen.absensi.sesi.tutup', [$kelas->getKey(), $sesi->getKey()]));
        $this->assertSame(0, RiwayatPerubahanAbsensi::query()
            ->where('sesi_absensi_id', $sesi->getKey())
            ->count());

        $this->actingAs($dosen)
            ->post($url, ['status' => 'alfa', 'alasan' => 'Alasan koreksi uji'])
            ->assertRedirect(route('dosen.absensi.sesi.show', [$kelas->getKey(), $sesi->getKey()]));

        $this->assertDatabaseHas('absensi', [
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'status' => 'alfa',
            'metode' => 'manual',
            'dicatat_oleh' => $dosen->getKey(),
        ]);

        $riwayat = RiwayatPerubahanAbsensi::query()
            ->where('sesi_absensi_id', $sesi->getKey())
            ->where('alasan', 'Alasan koreksi uji')
            ->firstOrFail();
        $this->assertSame('hadir', $riwayat->status_sebelum);
        $this->assertSame('alfa', $riwayat->status_sesudah);
        $this->assertSame($dosen->getKey(), (int) $riwayat->actor_id);
        $this->assertSame($peserta->getKey(), (int) $riwayat->peserta_mata_kuliah_id);

        $this->actingAs($dosen)
            ->get(route('dosen.absensi.riwayat', [$kelas->getKey(), $sesi->getKey()]))
            ->assertOk()
            ->assertSee('Alasan koreksi uji');
    }

    public function test_koreksi_menolak_alasan_kosong(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $mahasiswa = $this->mahasiswaAbsensi();
        $peserta = $this->pesertaAbsensi($kelas, $mahasiswa);
        $sesi = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '08:00', '10:00', StatusSesiAbsensi::Ditutup);

        $this->actingAs($dosen)
            ->post(route('dosen.absensi.koreksi', [$kelas->getKey(), $sesi->getKey(), $peserta->getKey()]), [
                'status' => 'hadir',
                'alasan' => '',
            ])
            ->assertSessionHasErrors('alasan');
        $this->assertSame(0, Absensi::query()->count());
    }

    public function test_sesi_bernilai_absensi_tidak_dihapus(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $mahasiswa = $this->mahasiswaAbsensi();
        $peserta = $this->pesertaAbsensi($kelas, $mahasiswa);
        $sesi = $this->sesiAbsensi($kelas, $dosen);
        $this->catatAbsensi($sesi, $peserta, StatusAbsensi::Hadir);

        $this->actingAs($dosen)
            ->delete(route('dosen.absensi.sesi.destroy', [$kelas->getKey(), $sesi->getKey()]))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('sesi_absensi', ['id' => $sesi->getKey()]);
    }

    public function test_rekap_kelas_akurat(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $mahasiswaSatu = $this->mahasiswaAbsensi('Rani');
        $mahasiswaDua = $this->mahasiswaAbsensi('Budi');
        $pesertaSatu = $this->pesertaAbsensi($kelas, $mahasiswaSatu);
        $pesertaDua = $this->pesertaAbsensi($kelas, $mahasiswaDua);
        $nonaktif = $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi('Nonaktif'), StatusPeserta::Nonaktif);

        $sesiSatu = $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-17', '08:00', '10:00', StatusSesiAbsensi::Ditutup);
        $sesiDua = $this->sesiAbsensi($kelas, $dosen, 2, '2026-11-18', '08:00', '10:00', StatusSesiAbsensi::Ditutup);
        $sesiTiga = $this->sesiAbsensi($kelas, $dosen, 3, '2026-11-19', '08:00', '10:00', StatusSesiAbsensi::Ditutup);
        $sesiBuka = $this->sesiAbsensi($kelas, $dosen, 4, '2026-11-20', '08:00', '10:00');

        foreach ([$sesiSatu, $sesiDua, $sesiTiga, $sesiBuka] as $sesi) {
            $this->catatAbsensi($sesi, $pesertaSatu, StatusAbsensi::Hadir);
            $this->catatAbsensi($sesi, $nonaktif, StatusAbsensi::Hadir);
        }

        $this->catatAbsensi($sesiSatu, $pesertaDua, StatusAbsensi::Hadir);
        $this->catatAbsensi($sesiDua, $pesertaDua, StatusAbsensi::Sakit);
        $this->catatAbsensi($sesiTiga, $pesertaDua, StatusAbsensi::Alfa);

        $rekap = app(RekapAbsensiService::class)->rekapKelas($kelas);

        $this->assertSame(3, $rekap['total_sesi_ditutup']);
        $this->assertSame(2, $rekap['jumlah_peserta']);
        $this->assertSame(4, $rekap['hadir']);
        $this->assertSame(1, $rekap['sakit']);
        $this->assertSame(1, $rekap['alfa']);

        $rani = $rekap['baris']->first(fn (array $item): bool => $item['nama'] === 'Rani');
        $budi = $rekap['baris']->first(fn (array $item): bool => $item['nama'] === 'Budi');

        $this->assertSame(3, $rani['hadir']);
        $this->assertSame(100.0, $rani['persentase']);
        $this->assertSame(1, $budi['hadir']);
        $this->assertSame(33.33, $budi['persentase']);
        $this->assertSame('33.33%', $budi['persentase_label']);

        $this->actingAs($dosen)
            ->get(route('dosen.absensi.rekap', $kelas->getKey()))
            ->assertOk()
            ->assertSee('33.33%');
    }

    public function test_rekap_menangani_kelas_tanpa_sesi_ditutup(): void
    {
        $dosen = $this->dosenAbsensi();
        $kelas = $this->kelasAbsensi($dosen);
        $this->pesertaAbsensi($kelas, $this->mahasiswaAbsensi());
        $this->sesiAbsensi($kelas, $dosen, 1, '2026-11-19', '08:00', '10:00');

        $rekap = app(RekapAbsensiService::class)->rekapKelas($kelas);

        $this->assertSame(0, $rekap['total_sesi_ditutup']);
        $this->assertSame(0.0, $rekap['baris']->first()['persentase']);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataSesi(int $pertemuan, string $topik = 'Topik uji'): array
    {
        return [
            'pertemuan_ke' => $pertemuan,
            'tanggal' => '2026-11-19',
            'topik' => $topik,
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
        ];
    }
}
