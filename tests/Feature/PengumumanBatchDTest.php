<?php

namespace Tests\Feature;

use App\Enums\JenisNotifikasi;
use App\Enums\StatusPeserta;
use App\Models\AgendaAkademik;
use App\Models\Notifikasi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BatchDData;
use Tests\TestCase;

class PengumumanBatchDTest extends TestCase
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

    public function test_matriks_akses_pengumuman_ditegakkan_di_backend(): void
    {
        $admin = User::factory()->admin()->create();
        $dosenA = $this->dosenBatchD();
        $dosenB = $this->dosenBatchD();
        $mahasiswa = $this->mahasiswaBatchD();
        $kelasA = $this->kelasBatchD($dosenA);
        $kelasB = $this->kelasBatchD($dosenB);
        $this->pesertaBatchD($mahasiswa, $kelasA);

        foreach ([
            route('admin.pengumuman.index'),
            route('dosen.pengumuman.index'),
            route('mahasiswa.pengumuman.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $this->actingAs($mahasiswa)
            ->post(route('admin.pengumuman.store'), $this->payloadPengumumanBatchD())
            ->assertForbidden();
        $this->actingAs($dosenA)
            ->post(route('admin.pengumuman.store'), $this->payloadPengumumanBatchD())
            ->assertForbidden();
        $this->actingAs($admin)
            ->get(route('admin.pengumuman.index'))
            ->assertOk()
            ->assertViewIs('admin.pengumuman.index');

        $this->actingAs($dosenA)
            ->post(route('dosen.pengumuman.store'), $this->payloadPengumumanBatchD([
                'kelas_mata_kuliah_id' => $kelasA->id,
            ]))
            ->assertRedirect(route('dosen.pengumuman.index'));

        // Kelas dosen lain tidak boleh dijadikan target, meski ID dikirim dari browser.
        $this->actingAs($dosenA)
            ->post(route('dosen.pengumuman.store'), $this->payloadPengumumanBatchD([
                'kelas_mata_kuliah_id' => $kelasB->id,
            ]))
            ->assertNotFound();

        $milikB = $this->pengumumanBatchD($dosenB, $kelasB, now()->subDay(), 'Pengumuman milik dosen B');
        foreach ([
            ['get', route('dosen.pengumuman.show', $milikB)],
            ['get', route('dosen.pengumuman.edit', $milikB)],
            ['put', route('dosen.pengumuman.update', $milikB)],
            ['delete', route('dosen.pengumuman.destroy', $milikB)],
        ] as [$method, $url]) {
            $this->actingAs($dosenA)->{$method}($url, $this->payloadPengumumanBatchD([
                'kelas_mata_kuliah_id' => $kelasA->id,
            ]))->assertNotFound();
        }

        $global = $this->pengumumanBatchD($admin, null, now()->subDay(), 'Pengumuman global terbit');
        $draft = $this->pengumumanBatchD($admin, null, null, 'Pengumuman draft');
        $milikKelasB = $this->pengumumanBatchD($dosenB, $kelasB, now()->subDay(), 'Pengumuman kelas B');

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.pengumuman.show', $global))
            ->assertOk()
            ->assertViewIs('mahasiswa.pengumuman.show');
        $this->actingAs($mahasiswa)->get(route('mahasiswa.pengumuman.show', $draft))->assertNotFound();
        $this->actingAs($mahasiswa)->get(route('mahasiswa.pengumuman.show', $milikKelasB))->assertNotFound();
    }

    public function test_pengumuman_global_terbit_menimbulkan_notifikasi_setelah_commit_dan_tidak_duplikat(): void
    {
        $admin = User::factory()->admin()->create();
        $aktif = $this->mahasiswaBatchD();
        $nonaktif = $this->mahasiswaBatchD(['is_active' => false]);

        $response = $this->actingAs($admin)
            ->post(route('admin.pengumuman.store'), $this->payloadPengumumanBatchD([
                'judul' => 'Pengumuman penting',
                'isi' => 'Harap hadir tepat waktu.',
            ]))
            ->assertRedirect(route('admin.pengumuman.index'));

        $pengumuman = AgendaAkademik::query()->where('judul', 'Pengumuman penting')->firstOrFail();

        $this->assertSame(1, Notifikasi::count());
        $notifikasi = Notifikasi::query()->firstOrFail();
        $this->assertSame($aktif->id, (int) $notifikasi->user_id);
        $this->assertSame(JenisNotifikasi::Pengumuman->value, $notifikasi->tipe);
        $this->assertSame('Pengumuman baru: Pengumuman penting', $notifikasi->judul);
        $this->assertSame('/mahasiswa/pengumuman/'.$pengumuman->id, $notifikasi->tautan);
        $this->assertSame('pengumuman:'.$pengumuman->id, $notifikasi->data['sumber']);

        $this->actingAs($aktif)
            ->get(route('mahasiswa.notifikasi'))
            ->assertOk()
            ->assertViewHas('unreadCount', 1);

        $this->actingAs($admin)
            ->put(route('admin.pengumuman.update', $pengumuman), $this->payloadPengumumanBatchD([
                'judul' => 'Pengumuman penting',
                'isi' => 'Harap hadir tepat waktu.',
            ]))
            ->assertRedirect(route('admin.pengumuman.index'));
        $this->actingAs($aktif)->get(route('mahasiswa.pengumuman.show', $pengumuman))->assertOk();

        $this->assertSame(1, Notifikasi::count());
        $this->assertSame(0, Notifikasi::untukUser($nonaktif->id)->count());
    }

    public function test_pengumuman_kelas_hanya_menjangkau_peserta_aktif_dan_draft_tidak_terlihat(): void
    {
        $dosen = $this->dosenBatchD();
        $kelas = $this->kelasBatchD($dosen);
        $kelasLain = $this->kelasBatchD($dosen);
        $pesertaAktif = $this->mahasiswaBatchD();
        $pesertaNonaktif = $this->mahasiswaBatchD();
        $pesertaKelasLain = $this->mahasiswaBatchD();
        $this->pesertaBatchD($pesertaAktif, $kelas);
        $this->pesertaBatchD($pesertaNonaktif, $kelas, StatusPeserta::Nonaktif);
        $this->pesertaBatchD($pesertaKelasLain, $kelasLain);

        $this->actingAs($dosen)
            ->post(route('dosen.pengumuman.store'), $this->payloadPengumumanBatchD([
                'judul' => 'Pengumuman kelas aktif',
                'kelas_mata_kuliah_id' => $kelas->id,
            ]))
            ->assertRedirect(route('dosen.pengumuman.index'));

        $this->assertSame(1, Notifikasi::count());
        $this->assertSame(1, Notifikasi::untukUser($pesertaAktif->id)->count());
        $this->assertSame(0, Notifikasi::untukUser($pesertaNonaktif->id)->count());
        $this->assertSame(0, Notifikasi::untukUser($pesertaKelasLain->id)->count());

        $this->actingAs($dosen)
            ->post(route('dosen.pengumuman.store'), $this->payloadPengumumanBatchD([
                'judul' => 'Pengumuman draft kelas',
                'kelas_mata_kuliah_id' => $kelas->id,
                'status' => 'draft',
            ]))
            ->assertRedirect(route('dosen.pengumuman.index'));

        $this->assertSame(1, Notifikasi::count());
        $this->actingAs($pesertaAktif)
            ->get(route('mahasiswa.pengumuman.index'))
            ->assertOk()
            ->assertDontSee('Pengumuman draft kelas');
    }

    public function test_daftar_pengumuman_mahasiswa_memiliki_filter_pagination_dan_empty_state(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = $this->dosenBatchD();
        $kelas = $this->kelasBatchD($dosen);
        $mahasiswa = $this->mahasiswaBatchD();
        $this->pesertaBatchD($mahasiswa, $kelas);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.pengumuman.index'))
            ->assertOk()
            ->assertSee('Belum ada pengumuman');

        for ($i = 1; $i <= 12; $i++) {
            $this->pengumumanBatchD($admin, null, now()->subDay(), 'Pengumuman global '.$i);
        }
        $this->pengumumanBatchD($admin, null, now()->subDay(), 'Pengumuman Unik');
        $this->pengumumanBatchD($admin, null, null, 'Pengumuman draft tersembunyi');
        $this->pengumumanBatchD($admin, null, now()->addDay(), 'Pengumuman terjadwal');
        $this->pengumumanBatchD($dosen, $this->kelasBatchD($dosen), now()->subDay(), 'Pengumuman kelas lain');

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.pengumuman.index'))
            ->assertOk();
        $this->assertSame(13, $response->viewData('pengumuman')->total());
        $this->assertCount(10, $response->viewData('pengumuman')->items());
        $response->assertDontSee('Pengumuman draft tersembunyi')
            ->assertDontSee('Pengumuman kelas lain');

        $halamanDua = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.pengumuman.index', ['page' => 2]))
            ->assertOk();
        $this->assertCount(3, $halamanDua->viewData('pengumuman')->items());

        $cari = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.pengumuman.index', ['q' => 'Unik']))
            ->assertOk();
        $this->assertSame(1, $cari->viewData('pengumuman')->total());
    }

    public function test_tandai_dibaca_dan_tandai_semua_dibaca_hanya_untuk_pemiliknya(): void
    {
        $pemilik = $this->mahasiswaBatchD();
        $lain = $this->mahasiswaBatchD();

        foreach ([$pemilik, $pemilik, $lain] as $user) {
            $notifikasi = new Notifikasi();
            $notifikasi->forceFill([
                'user_id' => $user->id,
                'tipe' => JenisNotifikasi::Pengumuman->value,
                'judul' => 'Notifikasi uji',
                'pesan' => 'Pesan uji',
                'data' => ['sumber' => 'uji:'.$user->id.':'.$notifikasi->id],
                'tautan' => '/mahasiswa/pengumuman',
            ])->save();
        }

        $milikPemilik = Notifikasi::untukUser($pemilik->id)->firstOrFail();
        $milikLain = Notifikasi::untukUser($lain->id)->firstOrFail();

        $this->actingAs($pemilik)
            ->get(route('mahasiswa.notifikasi'))
            ->assertOk()
            ->assertViewHas('unreadCount', 2)
            ->assertSee('Tandai semua dibaca');

        $this->actingAs($pemilik)
            ->post(route('mahasiswa.notifikasi.baca', $milikLain->id))
            ->assertNotFound();

        $this->actingAs($pemilik)
            ->post(route('mahasiswa.notifikasi.baca', $milikPemilik->id))
            ->assertRedirect(route('mahasiswa.notifikasi'));
        $this->assertSame(1, Notifikasi::untukUser($pemilik->id)->belumDibaca()->count());

        $this->actingAs($pemilik)
            ->post(route('mahasiswa.notifikasi.baca-semua'))
            ->assertRedirect(route('mahasiswa.notifikasi'));
        $this->assertSame(0, Notifikasi::untukUser($pemilik->id)->belumDibaca()->count());
        $this->assertSame(1, Notifikasi::untukUser($lain->id)->belumDibaca()->count());
    }
}
