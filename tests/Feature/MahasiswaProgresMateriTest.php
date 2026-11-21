<?php

namespace Tests\Feature;

use App\Enums\JenisAktivitasMateri;
use App\Enums\StatusPeserta;
use App\Enums\StatusProgres;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AktivitasMateri;
use App\Models\ProgresMateri;
use App\Services\Mahasiswa\ProgresMateriService;
use App\Services\Mahasiswa\TargetBelajarService;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataBatchC;
use Tests\TestCase;

class MahasiswaProgresMateriTest extends TestCase
{
    use MembuatDataBatchC;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('mahasiswa.progres-materi.index'))->assertRedirect(route('login'));
        $this->get(route('mahasiswa.materi.show', [1, 1]))->assertRedirect(route('login'));
        $this->post(route('mahasiswa.materi.progres.selesai', [1, 1]))->assertRedirect(route('login'));
    }

    public function test_membuka_materi_mencatat_aktivitas_tidak_menandai_selesai(): void
    {
        [$mahasiswa, $kelas, $materi] = $this->siapkanMateri();

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.show', [$kelas->id, $materi->id]))
            ->assertOk()
            ->assertSee('Tandai Sudah Dipelajari');

        $this->assertSame(1, AktivitasMateri::query()
            ->where('materi_pembelajaran_id', $materi->id)
            ->where('jenis', JenisAktivitasMateri::Dibuka->value)
            ->count());
        $this->assertSame(0, ProgresMateri::query()->where('materi_pembelajaran_id', $materi->id)->count());
    }

    public function test_mengunduh_materi_mencatat_aktivitas_tidak_menandai_selesai(): void
    {
        [$mahasiswa, $kelas, $materi] = $this->siapkanMateri();

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.unduh', [$kelas->id, $materi->id]))
            ->assertOk();

        $this->assertSame(1, AktivitasMateri::query()
            ->where('materi_pembelajaran_id', $materi->id)
            ->where('jenis', JenisAktivitasMateri::Diunduh->value)
            ->count());
        $this->assertSame(0, ProgresMateri::query()->where('materi_pembelajaran_id', $materi->id)->count());
    }

    public function test_akses_gagal_tidak_mencatat_aktivitas(): void
    {
        [, $kelas, $materi] = $this->siapkanMateri();
        $mahasiswaLain = $this->buatMahasiswa();

        $this->actingAs($mahasiswaLain)
            ->get(route('mahasiswa.materi.show', [$kelas->id, $materi->id]))
            ->assertNotFound();

        $this->assertSame(0, AktivitasMateri::query()->where('materi_pembelajaran_id', $materi->id)->count());
    }

    public function test_materi_draft_tidak_dapat_dibuka(): void
    {
        [$mahasiswa, $kelas] = $this->siapkanMateri();
        $draft = $this->buatMateri($kelas, $this->buatDosen(), [
            'judul' => 'Materi draft',
            'dipublikasikan_pada' => null,
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.show', [$kelas->id, $draft->id]))
            ->assertNotFound();

        $this->assertSame(0, AktivitasMateri::query()->where('materi_pembelajaran_id', $draft->id)->count());
    }

    public function test_menandai_selesai_dan_membuka_ulang_tidak_menghilangkan_status(): void
    {
        [$mahasiswa, $kelas, $materi] = $this->siapkanMateri();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.materi.progres.selesai', [$kelas->id, $materi->id]))
            ->assertSessionHas('status', 'Materi ditandai sudah dipelajari.');

        $progres = ProgresMateri::query()->where('materi_pembelajaran_id', $materi->id)->firstOrFail();
        $this->assertSame(StatusProgres::Selesai, $progres->status);
        $this->assertSame(100, (int) $progres->progres);
        $this->assertNotNull($progres->selesai_pada);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.materi.show', [$kelas->id, $materi->id]))
            ->assertOk();

        $progres->refresh();
        $this->assertSame(StatusProgres::Selesai, $progres->status);
    }

    public function test_penyelesaian_dapat_dibatalkan_tanpa_menghapus_baris_progres(): void
    {
        [$mahasiswa, $kelas, $materi] = $this->siapkanMateri();
        $progres = app(ProgresMateriService::class)->tandaiSelesai($mahasiswa->id, $kelas->id, $materi->id);

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.materi.progres.batalkan', [$kelas->id, $materi->id]))
            ->assertSessionHas('status', 'Status penyelesaian materi dibatalkan.');

        $progres->refresh();
        $this->assertSame(StatusProgres::BelumDimulai, $progres->status);
        $this->assertSame(0, (int) $progres->progres);
        $this->assertNull($progres->selesai_pada);
        $this->assertSame(1, ProgresMateri::query()->where('materi_pembelajaran_id', $materi->id)->count());
    }

    public function test_permintaan_selesai_berulang_tidak_membuat_progres_ganda(): void
    {
        [$mahasiswa, $kelas, $materi] = $this->siapkanMateri();

        $this->actingAs($mahasiswa)->post(route('mahasiswa.materi.progres.selesai', [$kelas->id, $materi->id]));
        $this->actingAs($mahasiswa)->post(route('mahasiswa.materi.progres.selesai', [$kelas->id, $materi->id]));

        $this->assertSame(1, ProgresMateri::query()->where('materi_pembelajaran_id', $materi->id)->count());
    }

    public function test_mahasiswa_lain_tidak_dapat_mengubah_progres(): void
    {
        [$pemilik, $kelas, $materi] = $this->siapkanMateri();
        $mahasiswaLain = $this->buatMahasiswa();

        $this->actingAs($mahasiswaLain)
            ->post(route('mahasiswa.materi.progres.selesai', [$kelas->id, $materi->id]))
            ->assertNotFound();

        $this->assertSame(0, ProgresMateri::query()
            ->where('materi_pembelajaran_id', $materi->id)
            ->whereHas('pesertaMataKuliah', fn ($query) => $query->where('mahasiswa_id', $mahasiswaLain->id))
            ->count());
        $this->assertTrue($pemilik->isMahasiswa());
    }

    public function test_peserta_nonaktif_tidak_dapat_mengubah_progres(): void
    {
        [$mahasiswa, $kelas, $materi] = $this->siapkanMateri();
        $pendaftaran = $mahasiswa->pendaftaranKelas()
            ->where('kelas_mata_kuliah_id', $kelas->id)
            ->firstOrFail();
        $pendaftaran->status = StatusPeserta::Nonaktif;
        $pendaftaran->save();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.materi.progres.selesai', [$kelas->id, $materi->id]))
            ->assertNotFound();

        $this->assertSame(0, ProgresMateri::query()->where('materi_pembelajaran_id', $materi->id)->count());
    }

    public function test_target_otomatis_hanya_menghitung_materi_publikasi(): void
    {
        [$mahasiswa, $kelas] = $this->siapkanMateri();
        $this->buatMateri($kelas, $this->buatDosen(), ['judul' => 'Materi publikasi kedua']);
        $this->buatMateri($kelas, $this->buatDosen(), [
            'judul' => 'Materi draft',
            'dipublikasikan_pada' => null,
        ]);
        $this->buatMateri($kelas, $this->buatDosen(), [
            'judul' => 'Materi terjadwal',
            'dipublikasikan_pada' => now()->addDay(),
        ]);

        $ringkasan = app(ProgresMateriService::class)->ringkasanOtomatis($mahasiswa->id);

        $this->assertSame(2, $ringkasan['total']);
        $this->assertSame(0, $ringkasan['selesai']);
        $this->assertEquals(0.0, $ringkasan['persentase']);
    }

    public function test_target_otomatis_berubah_saat_progres_berubah(): void
    {
        [$mahasiswa, $kelas, $materiPertama] = $this->siapkanMateri();
        $materiKedua = $this->buatMateri($kelas, $this->buatDosen(), ['judul' => 'Materi kedua']);
        $service = app(ProgresMateriService::class);

        $this->assertEquals(0.0, $service->ringkasanOtomatis($mahasiswa->id)['persentase']);

        $service->tandaiSelesai($mahasiswa->id, $kelas->id, $materiPertama->id);

        $ringkasan = $service->ringkasanOtomatis($mahasiswa->id);
        $this->assertSame(2, $ringkasan['total']);
        $this->assertSame(1, $ringkasan['selesai']);
        $this->assertEquals(50.0, $ringkasan['persentase']);

        $service->batalkanSelesai($mahasiswa->id, $kelas->id, $materiPertama->id);
        $this->assertEquals(0.0, $service->ringkasanOtomatis($mahasiswa->id)['persentase']);
        $this->assertSame(0, ProgresMateri::query()->where('materi_pembelajaran_id', $materiKedua->id)->count());
    }

    public function test_dashboard_menampilkan_ringkasan_target_dan_progres(): void
    {
        [$mahasiswa, $kelas, $materi] = $this->siapkanMateri();
        app(TargetBelajarService::class)->simpan($mahasiswa->id, $this->dataTarget([
            'judul' => 'Target di dashboard',
        ]));
        app(ProgresMateriService::class)->tandaiSelesai($mahasiswa->id, $kelas->id, $materi->id);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Target di dashboard')
            ->assertSee('Progres materi otomatis')
            ->assertSee('Target belajar');
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\KelasMataKuliah, 2: \App\Models\MateriPembelajaran}
     */
    private function siapkanMateri(): array
    {
        $mahasiswa = $this->buatMahasiswa();
        $dosen = $this->buatDosen();
        $kelas = $this->buatKelas($dosen, $this->buatTahun(), $this->buatMataKuliah());
        $this->buatPeserta($kelas, $mahasiswa, StatusPeserta::Aktif);
        $materi = $this->buatMateri($kelas, $dosen);

        return [$mahasiswa, $kelas, $materi];
    }
}
