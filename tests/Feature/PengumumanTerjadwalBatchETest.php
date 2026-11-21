<?php

namespace Tests\Feature;

use App\Enums\JenisNotifikasi;
use App\Enums\StatusPeserta;
use App\Models\AgendaAkademik;
use App\Models\Notifikasi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Concerns\BatchDData;
use Tests\TestCase;

class PengumumanTerjadwalBatchETest extends TestCase
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

    public function test_pengumuman_terjadwal_tidak_terlihat_dan_tidak_notifikasi_sebelum_waktunya(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = $this->mahasiswaBatchD();

        $this->actingAs($admin)
            ->post(route('admin.pengumuman.store'), $this->payloadPengumumanBatchD([
                'judul' => 'Pengumuman terjadwal uji',
                'status' => 'terjadwal',
                'tanggal_publikasi' => '2026-11-18T10:00',
            ]))
            ->assertRedirect(route('admin.pengumuman.index'));

        $pengumuman = AgendaAkademik::query()
            ->where('judul', 'Pengumuman terjadwal uji')
            ->firstOrFail();

        $this->assertSame(0, Notifikasi::count());
        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.pengumuman.index'))
            ->assertOk()
            ->assertDontSee('Pengumuman terjadwal uji');
        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.pengumuman.show', $pengumuman))
            ->assertNotFound();
    }

    public function test_command_menerbitkan_terjadwal_dan_notifikasi_tidak_duplikat(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = $this->mahasiswaBatchD();

        $this->actingAs($admin)
            ->post(route('admin.pengumuman.store'), $this->payloadPengumumanBatchD([
                'judul' => 'Pengumuman otomatis terbit',
                'status' => 'terjadwal',
                'tanggal_publikasi' => '2026-11-18T10:00',
            ]))
            ->assertRedirect(route('admin.pengumuman.index'));

        $pengumuman = AgendaAkademik::query()
            ->where('judul', 'Pengumuman otomatis terbit')
            ->firstOrFail();

        Artisan::call('pengumuman:terbitkan-terjadwal');
        $this->assertSame(0, Notifikasi::count());

        Carbon::setTestNow('2026-11-18 10:01:00');
        $this->assertSame(0, Artisan::call('pengumuman:terbitkan-terjadwal'));

        $notifikasi = Notifikasi::query()
            ->where('data->sumber', 'pengumuman:'.$pengumuman->id)
            ->get();
        $this->assertSame(1, $notifikasi->count());
        $item = $notifikasi->firstOrFail();
        $this->assertSame($mahasiswa->id, (int) $item->user_id);
        $this->assertSame(JenisNotifikasi::Pengumuman->value, $item->tipe);
        $this->assertSame('Pengumuman baru: Pengumuman otomatis terbit', $item->judul);
        $this->assertSame('/mahasiswa/pengumuman/'.$pengumuman->id, $item->tautan);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.pengumuman.show', $pengumuman))
            ->assertOk()
            ->assertSee('Pengumuman otomatis terbit');

        Artisan::call('pengumuman:terbitkan-terjadwal');
        Artisan::call('pengumuman:terbitkan-terjadwal');
        $this->assertSame(1, Notifikasi::count());
    }

    public function test_command_hanya_notifikasi_peserta_aktif_kelas_yang_dituju(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = $this->dosenBatchD();
        $kelas = $this->kelasBatchD($dosen);
        $kelasLain = $this->kelasBatchD($dosen);
        $aktif = $this->mahasiswaBatchD();
        $nonaktif = $this->mahasiswaBatchD();
        $diKelasLain = $this->mahasiswaBatchD();
        $this->pesertaBatchD($aktif, $kelas);
        $this->pesertaBatchD($nonaktif, $kelas, StatusPeserta::Nonaktif);
        $this->pesertaBatchD($diKelasLain, $kelasLain);

        $this->actingAs($admin)
            ->post(route('admin.pengumuman.store'), $this->payloadPengumumanBatchD([
                'judul' => 'Pengumuman kelas terjadwal',
                'kelas_mata_kuliah_id' => $kelas->id,
                'status' => 'terjadwal',
                'tanggal_publikasi' => '2026-11-18T10:00',
            ]))
            ->assertRedirect(route('admin.pengumuman.index'));

        Carbon::setTestNow('2026-11-18 10:01:00');
        Artisan::call('pengumuman:terbitkan-terjadwal');

        $this->assertSame(2, Notifikasi::count());
        $this->assertSame($aktif->id, (int) Notifikasi::firstOrFail()->user_id);
        $notifikasiDosen = Notifikasi::untukUser($dosen->id)->firstOrFail();
        $this->assertStringStartsWith('/dosen/pengumuman/', (string) $notifikasiDosen->tautan);
        $this->assertSame('dosen', $notifikasiDosen->data['peran']);
        $this->assertSame(0, Notifikasi::untukUser($nonaktif->id)->count());
        $this->assertSame(0, Notifikasi::untukUser($diKelasLain->id)->count());

        $this->actingAs($nonaktif)
            ->get(route('mahasiswa.pengumuman.show', AgendaAkademik::firstOrFail()))
            ->assertNotFound();
    }

    public function test_command_tidak_mengirim_ulang_pengumuman_yang_langsung_terbit(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = $this->mahasiswaBatchD();

        $this->actingAs($admin)
            ->post(route('admin.pengumuman.store'), $this->payloadPengumumanBatchD([
                'judul' => 'Pengumuman langsung terbit',
            ]))
            ->assertRedirect(route('admin.pengumuman.index'));

        $this->assertSame(1, Notifikasi::count());
        Artisan::call('pengumuman:terbitkan-terjadwal');
        Artisan::call('pengumuman:terbitkan-terjadwal');
        $this->assertSame(1, Notifikasi::count());
        $this->assertSame($mahasiswa->id, (int) Notifikasi::firstOrFail()->user_id);
    }
}
