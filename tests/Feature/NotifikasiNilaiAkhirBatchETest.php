<?php

namespace Tests\Feature;

use App\Enums\JenisNotifikasi;
use App\Models\Notifikasi;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BatchDData;
use Tests\TestCase;

class NotifikasiNilaiAkhirBatchETest extends TestCase
{
    use BatchDData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-11-18 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_notifikasi_nilai_akhir_hanya_ketika_transisi_menjadi_lengkap(): void
    {
        $dosen = $this->dosenBatchD();
        $kelas = $this->kelasBatchD($dosen);
        $mahasiswa = $this->mahasiswaBatchD();
        $lain = $this->mahasiswaBatchD();
        $peserta = $this->pesertaBatchD($mahasiswa, $kelas);
        $this->pesertaBatchD($lain, $kelas);

        $urlKonfigurasi = route('dosen.nilai.konfigurasi', $kelas);
        $this->actingAs($dosen)
            ->post($urlKonfigurasi, ['gunakan_tugas' => '0', 'alasan' => 'Tugas tidak dipakai'])
            ->assertSessionHas('status', 'Konfigurasi komponen tugas berhasil disimpan.');
        $this->assertSame(0, Notifikasi::count());

        $urlUts = route('dosen.nilai.simpan', [$kelas->id, $peserta->id, 'uts']);
        $this->actingAs($dosen)
            ->post($urlUts, ['nilai' => '80'])
            ->assertSessionHas('status', 'Nilai berhasil ditambahkan.');
        $this->assertSame(1, Notifikasi::count());
        $this->assertSame(0, $this->notifikasiAkhir()->count());

        Carbon::setTestNow('2026-11-18 09:00:01');
        $urlUas = route('dosen.nilai.simpan', [$kelas->id, $peserta->id, 'uas']);
        $this->actingAs($dosen)
            ->post($urlUas, ['nilai' => '90'])
            ->assertSessionHas('status', 'Nilai berhasil ditambahkan.');

        $notifikasi = $this->notifikasiAkhir();
        $this->assertSame(1, $notifikasi->count());
        $item = $notifikasi->firstOrFail();
        $this->assertSame($mahasiswa->id, (int) $item->user_id);
        $this->assertSame(JenisNotifikasi::Nilai->value, $item->tipe);
        $this->assertSame('Nilai akhir tersedia', $item->judul);
        $this->assertSame('/mahasiswa/nilai', $item->tautan);
        $this->assertSame('nilai-akhir:'.$peserta->id, $item->data['sumber']);
        $this->assertSame(0, $this->notifikasiAkhir()->where('user_id', $lain->id)->count());

        // Koreksi nilai tidak membuat notifikasi nilai akhir kedua.
        Carbon::setTestNow('2026-11-18 09:00:02');
        $this->actingAs($dosen)
            ->post($urlUas, ['nilai' => '95'])
            ->assertSessionHas('status', 'Nilai berhasil diperbarui.');
        $this->assertSame(1, $this->notifikasiAkhir()->count());
    }

    public function test_perubahan_konfigurasi_yang_melengkapkan_nilai_akhir_tidak_duplikat(): void
    {
        $dosen = $this->dosenBatchD();
        $kelas = $this->kelasBatchD($dosen);
        $lengkap = $this->mahasiswaBatchD();
        $belum = $this->mahasiswaBatchD();
        $pesertaLengkap = $this->pesertaBatchD($lengkap, $kelas);
        $this->pesertaBatchD($belum, $kelas);

        $this->actingAs($dosen)
            ->post(route('dosen.nilai.simpan', [$kelas->id, $pesertaLengkap->id, 'uts']), ['nilai' => '80'])
            ->assertSessionHas('status', 'Nilai berhasil ditambahkan.');
        $this->actingAs($dosen)
            ->post(route('dosen.nilai.simpan', [$kelas->id, $pesertaLengkap->id, 'uas']), ['nilai' => '90'])
            ->assertSessionHas('status', 'Nilai berhasil ditambahkan.');
        $this->assertSame(0, $this->notifikasiAkhir()->count());

        $urlKonfigurasi = route('dosen.nilai.konfigurasi', $kelas);
        $this->actingAs($dosen)
            ->post($urlKonfigurasi, ['gunakan_tugas' => '0'])
            ->assertSessionHas('status', 'Konfigurasi komponen tugas berhasil disimpan.');
        $this->assertSame(1, $this->notifikasiAkhir()->count());
        $this->assertSame($lengkap->id, (int) $this->notifikasiAkhir()->firstOrFail()->user_id);

        // Tugas dipakai tanpa nilai tugas membuat status menjadi belum lengkap.
        $this->actingAs($dosen)
            ->post($urlKonfigurasi, ['gunakan_tugas' => '1'])
            ->assertSessionHas('status', 'Konfigurasi komponen tugas berhasil disimpan.');
        $this->assertSame(1, $this->notifikasiAkhir()->count());

        // Kembali ke konfigurasi awal tidak mengirim notifikasi kedua.
        $this->actingAs($dosen)
            ->post($urlKonfigurasi, ['gunakan_tugas' => '0'])
            ->assertSessionHas('status', 'Konfigurasi komponen tugas berhasil disimpan.');
        $this->assertSame(1, $this->notifikasiAkhir()->count());
    }

    public function test_nilai_tidak_valid_dan_komponen_terlarang_tidak_menimbulkan_notifikasi_akhir(): void
    {
        $dosen = $this->dosenBatchD();
        $kelas = $this->kelasBatchD($dosen);
        $mahasiswa = $this->mahasiswaBatchD();
        $peserta = $this->pesertaBatchD($mahasiswa, $kelas);

        $this->actingAs($dosen)
            ->post(route('dosen.nilai.konfigurasi', $kelas), ['gunakan_tugas' => '0'])
            ->assertSessionHas('status', 'Konfigurasi komponen tugas berhasil disimpan.');

        $this->actingAs($dosen)
            ->post(route('dosen.nilai.simpan', [$kelas->id, $peserta->id, 'uts']), ['nilai' => '101'])
            ->assertSessionHasErrors('nilai');
        $this->assertSame(0, Notifikasi::count());

        $this->actingAs($dosen)
            ->post(route('dosen.nilai.simpan', [$kelas->id, $peserta->id, 'tugas']), ['nilai' => '90'])
            ->assertNotFound();
        $this->assertSame(0, Notifikasi::count());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Notifikasi>
     */
    private function notifikasiAkhir(): \Illuminate\Database\Eloquent\Collection
    {
        return Notifikasi::query()
            ->where('tipe', JenisNotifikasi::Nilai->value)
            ->where('data->sumber', 'like', 'nilai-akhir:%')
            ->get();
    }
}
