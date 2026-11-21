<?php

namespace Tests\Feature;

use App\Enums\StatusTargetBelajar;
use App\Models\TargetBelajar;
use App\Services\Mahasiswa\TargetBelajarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataBatchC;
use Tests\TestCase;

class MahasiswaTargetBelajarTest extends TestCase
{
    use MembuatDataBatchC;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('mahasiswa.target.index'))->assertRedirect(route('login'));
        $this->get(route('mahasiswa.target.create'))->assertRedirect(route('login'));
        $this->post(route('mahasiswa.target.store'), $this->dataTarget())->assertRedirect(route('login'));
    }

    public function test_mahasiswa_dapat_membuat_target(): void
    {
        $mahasiswa = $this->buatMahasiswa();

        $response = $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.store'), $this->dataTarget([
                'judul' => 'Menyelesaikan 10 latihan algoritma',
            ]));

        $target = TargetBelajar::query()->where('mahasiswa_id', $mahasiswa->id)->firstOrFail();

        $response->assertRedirect(route('mahasiswa.target.show', $target->id));
        $response->assertSessionHas('status', 'Target belajar berhasil dibuat.');
        $this->assertDatabaseHas('target_belajar', [
            'id' => $target->id,
            'mahasiswa_id' => $mahasiswa->id,
            'judul' => 'Menyelesaikan 10 latihan algoritma',
            'status' => StatusTargetBelajar::Berlangsung->value,
        ]);
        $this->assertSame(0, (int) $target->progres);
    }

    public function test_mahasiswa_dapat_mengubah_target(): void
    {
        $mahasiswa = $this->buatMahasiswa();
        $target = app(TargetBelajarService::class)->simpan($mahasiswa->id, $this->dataTarget());

        $response = $this->actingAs($mahasiswa)->patch(
            route('mahasiswa.target.update', $target->id),
            $this->dataTarget(['judul' => 'Target sudah diubah', 'target_nilai' => 20]),
        );

        $response->assertRedirect(route('mahasiswa.target.show', $target->id));
        $this->assertDatabaseHas('target_belajar', [
            'id' => $target->id,
            'judul' => 'Target sudah diubah',
        ]);
    }

    public function test_mahasiswa_dapat_menghapus_target(): void
    {
        $mahasiswa = $this->buatMahasiswa();
        $target = app(TargetBelajarService::class)->simpan($mahasiswa->id, $this->dataTarget());

        $response = $this->actingAs($mahasiswa)->delete(route('mahasiswa.target.destroy', $target->id));

        $response->assertRedirect(route('mahasiswa.target.index'));
        $this->assertDatabaseMissing('target_belajar', ['id' => $target->id]);
    }

    public function test_mahasiswa_tidak_dapat_melihat_target_orang_lain(): void
    {
        $pemilik = $this->buatMahasiswa();
        $lain = $this->buatMahasiswa();
        $target = app(TargetBelajarService::class)->simpan($pemilik->id, $this->dataTarget());

        $this->actingAs($lain)
            ->get(route('mahasiswa.target.show', $target->id))
            ->assertNotFound();
    }

    public function test_mahasiswa_tidak_dapat_mengubah_capaian_target_orang_lain(): void
    {
        $pemilik = $this->buatMahasiswa();
        $lain = $this->buatMahasiswa();
        $target = app(TargetBelajarService::class)->simpan($pemilik->id, $this->dataTarget());

        $this->actingAs($lain)
            ->patch(route('mahasiswa.target.capaian', $target->id), ['capaian' => 5])
            ->assertForbidden();
    }

    public function test_deadline_tidak_boleh_mendahului_tanggal_mulai(): void
    {
        $mahasiswa = $this->buatMahasiswa();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.store'), $this->dataTarget([
                'tanggal_mulai' => now()->addDays(3)->toDateString(),
                'deadline' => now()->toDateString(),
            ]))
            ->assertSessionHasErrors('deadline');
    }

    public function test_nilai_target_harus_positif(): void
    {
        $mahasiswa = $this->buatMahasiswa();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.store'), $this->dataTarget(['target_nilai' => -1]))
            ->assertSessionHasErrors('target_nilai');
    }

    public function test_capaian_tidak_boleh_negatif_atau_melebihi_target(): void
    {
        $mahasiswa = $this->buatMahasiswa();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.store'), $this->dataTarget(['capaian' => -1]))
            ->assertSessionHasErrors('capaian');

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.store'), $this->dataTarget(['capaian' => 11]))
            ->assertSessionHasErrors('capaian');
    }

    public function test_status_terlambat_dihitung_dari_deadline(): void
    {
        $mahasiswa = $this->buatMahasiswa();
        $target = app(TargetBelajarService::class)->simpan($mahasiswa->id, $this->dataTarget([
            'tanggal_mulai' => now()->subDays(10)->toDateString(),
            'deadline' => now()->subDay()->toDateString(),
        ]));

        $target->refresh();

        $this->assertSame(StatusTargetBelajar::Terlambat, $target->status);
        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.target.show', $target->id))
            ->assertOk()
            ->assertSee('Terlambat');
    }

    public function test_status_selesai_dihitung_saat_capaian_mencapai_target(): void
    {
        $mahasiswa = $this->buatMahasiswa();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.store'), $this->dataTarget(['capaian' => 10]))
            ->assertSessionHasNoErrors();

        $target = TargetBelajar::query()->where('mahasiswa_id', $mahasiswa->id)->firstOrFail();
        $target->refresh();

        $this->assertSame(StatusTargetBelajar::Selesai, $target->status);
        $this->assertSame(100, (int) $target->progres);
        $this->assertNotNull($target->selesai_pada);
    }

    public function test_mata_kuliah_target_harus_diikuti_mahasiswa(): void
    {
        $mahasiswa = $this->buatMahasiswa();
        $dosen = $this->buatDosen();
        $mataKuliah = $this->buatMataKuliah();
        $this->buatKelas($dosen, $this->buatTahun(), $mataKuliah, 'B01');

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.store'), $this->dataTarget([
                'mata_kuliah_id' => $mataKuliah->id,
            ]))
            ->assertSessionHasErrors('mata_kuliah_id');
    }

    public function test_target_dapat_ditandai_selesai_lalu_dibatalkan(): void
    {
        $mahasiswa = $this->buatMahasiswa();
        $target = app(TargetBelajarService::class)->simpan($mahasiswa->id, $this->dataTarget(['capaian' => 2]));

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.selesai', $target->id))
            ->assertSessionHas('status', 'Target ditandai selesai.');

        $target->refresh();
        $this->assertSame(StatusTargetBelajar::Selesai, $target->status);
        $this->assertSame(2.0, (float) $target->capaian);

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.batalkan-selesai', $target->id))
            ->assertSessionHas('status', 'Status selesai dibatalkan.');

        $target->refresh();
        $this->assertNotSame(StatusTargetBelajar::Selesai, $target->status);
        $this->assertNull($target->selesai_pada);
    }

    public function test_target_dapat_dibatalkan(): void
    {
        $mahasiswa = $this->buatMahasiswa();
        $target = app(TargetBelajarService::class)->simpan($mahasiswa->id, $this->dataTarget());

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.target.batalkan', $target->id))
            ->assertSessionHas('status', 'Target dibatalkan.');

        $target->refresh();
        $this->assertSame(StatusTargetBelajar::Dibatalkan, $target->status);
    }

    public function test_halaman_daftar_target_menampilkan_data_dan_menu_aktif(): void
    {
        $mahasiswa = $this->buatMahasiswa();
        app(TargetBelajarService::class)->simpan($mahasiswa->id, $this->dataTarget([
            'judul' => 'Target yang terlihat di daftar',
        ]));

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.target.index'))
            ->assertOk()
            ->assertSee('Target yang terlihat di daftar')
            ->assertSee('Tambah Target');
    }
}
