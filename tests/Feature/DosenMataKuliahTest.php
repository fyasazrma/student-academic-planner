<?php

namespace Tests\Feature;

use App\Enums\StatusPendaftaranKelas;
use App\Enums\StatusPeserta;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Kelas\KunciGabungKelasService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenMataKuliahTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

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

    public function test_akses_route_dosen_hanya_untuk_dosen_aktif(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->get(route('dosen.dashboard'))->assertRedirect(route('login'));

        foreach ([User::factory()->mahasiswa()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('dosen.dashboard'))->assertForbidden();
            $this->actingAs($user)->get(route('dosen.mata-kuliah.index'))->assertForbidden();
            $this->actingAs($user)->post(route('dosen.mata-kuliah.store'), [])->assertForbidden();
        }

        $nonaktif = $this->dosen();
        $nonaktif->forceFill(['is_active' => false])->save();

        $this->actingAs($nonaktif)
            ->get(route('dosen.dashboard'))
            ->assertRedirect(route('login'));

        $this->actingAs($dosen)
            ->get(route('dosen.dashboard'))
            ->assertOk()
            ->assertViewIs('dosen.dashboard');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.index'))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.index');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.create'))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.create');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.show', $kelas))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.show');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.edit', $kelas))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.edit');
    }

    public function test_kelas_dosen_lain_menghasilkan_404_pada_semua_aksi(): void
    {
        $dosen = $this->dosen();
        $dosenLain = $this->dosen();
        $kelasLain = $this->kelas($dosenLain, $this->tahun(), $this->mataKuliah(), 'B02');

        $routes = [
            ['get', route('dosen.mata-kuliah.show', $kelasLain), []],
            ['get', route('dosen.mata-kuliah.edit', $kelasLain), []],
            ['put', route('dosen.mata-kuliah.update', $kelasLain), ['nama_kelas' => 'Ubah', 'kapasitas' => 30]],
            ['post', route('dosen.mata-kuliah.status', $kelasLain), ['status_pendaftaran' => 'buka']],
            ['post', route('dosen.mata-kuliah.kunci', $kelasLain), []],
        ];

        foreach ($routes as [$method, $url, $payload]) {
            $this->actingAs($dosen)->{$method}($url, $payload)->assertNotFound();
        }

        $this->assertSame(
            StatusPendaftaranKelas::Tutup,
            $kelasLain->fresh()->status_pendaftaran,
        );
        $this->assertNull($kelasLain->fresh()->kode_gabung_hash);
    }

    public function test_store_menetapkan_pemilik_status_dan_kunci_dari_server(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mataKuliah = $this->mataKuliah();

        $respons = $this->actingAs($dosen)->post(route('dosen.mata-kuliah.store'), [
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'kode_kelas' => ' a01 ',
            'nama_kelas' => ' Kelas Percobaan ',
            'kapasitas' => '',
            'dosen_id' => User::factory()->dosen()->create()->id,
            'status_pendaftaran' => 'buka',
            'kode_gabung_hash' => str_repeat('a', 64),
        ]);

        $kelas = KelasMataKuliah::query()->where('kode_kelas', 'A01')->firstOrFail();

        $respons->assertRedirect(route('dosen.mata-kuliah.show', $kelas->id))
            ->assertSessionHas('status');

        $this->assertSame($dosen->id, $kelas->dosen_id);
        $this->assertSame($mataKuliah->id, $kelas->mata_kuliah_id);
        $this->assertSame($tahun->id, $kelas->tahun_akademik_id);
        $this->assertSame('Kelas Percobaan', $kelas->nama_kelas);
        $this->assertNull($kelas->kapasitas);
        $this->assertSame(StatusPendaftaranKelas::Tutup, $kelas->status_pendaftaran);
        $this->assertNull($kelas->kode_gabung_hash);
    }

    public function test_store_menolak_data_induk_nonaktif_dan_kode_duplikat(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mataKuliah = $this->mataKuliah();
        $nonaktif = $this->mataKuliah('MK-NONAKTIF', false);
        $tahunNonaktif = $this->tahun(2024, false);

        $this->actingAs($dosen)
            ->from(route('dosen.mata-kuliah.create'))
            ->post(route('dosen.mata-kuliah.store'), $this->dataValidDengan($nonaktif, $tahun))
            ->assertSessionHasErrors('mata_kuliah_id');

        $this->actingAs($dosen)
            ->from(route('dosen.mata-kuliah.create'))
            ->post(route('dosen.mata-kuliah.store'), $this->dataValidDengan($mataKuliah, $tahunNonaktif))
            ->assertSessionHasErrors('tahun_akademik_id');

        $data = $this->dataValidDengan($mataKuliah, $tahun);

        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.store'), $data);

        $this->actingAs($dosen)
            ->from(route('dosen.mata-kuliah.create'))
            ->post(route('dosen.mata-kuliah.store'), array_merge($data, ['kode_kelas' => 'a01']))
            ->assertSessionHasErrors('kode_kelas');

        $mataKuliahLain = $this->mataKuliah();

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.store'), $this->dataValidDengan($mataKuliahLain, $tahun))
            ->assertRedirect();

        $this->assertSame(2, KelasMataKuliah::query()->count());
        $this->assertTrue($mataKuliah->exists && $tahun->exists);
    }

    public function test_validasi_form_store(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mataKuliah = $this->mataKuliah();

        foreach ([
            ['mata_kuliah_id' => null],
            ['tahun_akademik_id' => null],
            ['kode_kelas' => null],
            ['kode_kelas' => 'A/01'],
            ['kode_kelas' => str_repeat('A', 11)],
            ['kapasitas' => 0],
            ['kapasitas' => 65536],
            ['kapasitas' => 'bukan-angka'],
            ['nama_kelas' => str_repeat('x', 101)],
        ] as $overrides) {
            $this->actingAs($dosen)
                ->from(route('dosen.mata-kuliah.create'))
                ->post(route('dosen.mata-kuliah.store'), $this->dataValidDengan($mataKuliah, $tahun, $overrides))
                ->assertSessionHasErrors(array_key_first($overrides));
        }

        $this->assertSame(0, KelasMataKuliah::query()->count());
        $this->assertTrue($mataKuliah->exists && $tahun->exists);
    }

    public function test_update_hanya_mengubah_nama_kelas_dan_kapasitas(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mataKuliah = $this->mataKuliah();
        $kelas = $this->kelas($dosen, $tahun, $mataKuliah, 'A01', 40, 'Nama lama');
        $mataKuliahLain = $this->mataKuliah();
        $tahunLain = $this->tahun(2027);
        $pemilikLain = $this->dosen();

        $this->actingAs($dosen)
            ->from(route('dosen.mata-kuliah.edit', $kelas))
            ->put(route('dosen.mata-kuliah.update', $kelas), [
                'nama_kelas' => ' Nama baru ',
                'kapasitas' => 25,
                'mata_kuliah_id' => $mataKuliahLain->id,
                'tahun_akademik_id' => $tahunLain->id,
                'kode_kelas' => 'Z99',
                'dosen_id' => $pemilikLain->id,
                'status_pendaftaran' => 'buka',
                'kode_gabung_hash' => str_repeat('b', 64),
            ])
            ->assertRedirect(route('dosen.mata-kuliah.show', $kelas->id))
            ->assertSessionHas('status');

        $fresh = $kelas->fresh();

        $this->assertSame('Nama baru', $fresh->nama_kelas);
        $this->assertSame(25, $fresh->kapasitas);
        $this->assertSame($mataKuliah->id, $fresh->mata_kuliah_id);
        $this->assertSame($tahun->id, $fresh->tahun_akademik_id);
        $this->assertSame('A01', $fresh->kode_kelas);
        $this->assertSame($dosen->id, $fresh->dosen_id);
        $this->assertSame(StatusPendaftaranKelas::Tutup, $fresh->status_pendaftaran);
        $this->assertNull($fresh->kode_gabung_hash);
    }

    public function test_kapasitas_kosong_tanpa_batas_dan_tidak_boleh_kurang_dari_peserta_aktif(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01', null);

        $this->assertNull($kelas->kapasitas);

        $mahasiswaSatu = User::factory()->mahasiswa()->create();
        $mahasiswaDua = User::factory()->mahasiswa()->create();
        $mahasiswaNonaktif = User::factory()->mahasiswa()->create();

        $this->enroll($mahasiswaSatu, $kelas);
        $this->enroll($mahasiswaDua, $kelas);
        $this->enroll($mahasiswaNonaktif, $kelas, StatusPeserta::Nonaktif->value);

        $this->assertSame(2, $kelas->jumlahPesertaAktif());

        $this->actingAs($dosen)
            ->from(route('dosen.mata-kuliah.edit', $kelas))
            ->put(route('dosen.mata-kuliah.update', $kelas), [
                'nama_kelas' => 'Kelas Uji',
                'kapasitas' => 1,
            ])
            ->assertSessionHasErrors('kapasitas');

        $this->assertNull($kelas->fresh()->kapasitas);

        $this->actingAs($dosen)
            ->put(route('dosen.mata-kuliah.update', $kelas), [
                'nama_kelas' => 'Kelas Uji',
                'kapasitas' => 2,
            ])
            ->assertRedirect(route('dosen.mata-kuliah.show', $kelas->id));

        $this->assertSame(2, $kelas->fresh()->kapasitas);

        $this->actingAs($dosen)
            ->put(route('dosen.mata-kuliah.update', $kelas), [
                'nama_kelas' => 'Kelas Uji',
                'kapasitas' => '',
            ])
            ->assertRedirect(route('dosen.mata-kuliah.show', $kelas->id));

        $this->assertNull($kelas->fresh()->kapasitas);
        $this->assertSame(3, PesertaMataKuliah::query()->where('kelas_mata_kuliah_id', $kelas->id)->count());
    }

    public function test_buka_pendaftaran_hanya_setelah_kunci_gabung_tersedia(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.status', $kelas), ['status_pendaftaran' => 'buka'])
            ->assertRedirect(route('dosen.mata-kuliah.show', $kelas->id))
            ->assertSessionHas('error');

        $this->assertSame(StatusPendaftaranKelas::Tutup, $kelas->fresh()->status_pendaftaran);

        $this->actingAs($dosen)
            ->from(route('dosen.mata-kuliah.show', $kelas))
            ->post(route('dosen.mata-kuliah.status', $kelas), ['status_pendaftaran' => 'tidak-dikenal'])
            ->assertSessionHasErrors('status_pendaftaran');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.kunci', $kelas), ['kunci' => 'kunci-palsu'])
            ->assertRedirect(route('dosen.mata-kuliah.show', $kelas->id))
            ->assertSessionHas('kode_gabung');

        $this->assertNotNull($kelas->fresh()->kode_gabung_hash);

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.status', $kelas), ['status_pendaftaran' => 'buka'])
            ->assertRedirect(route('dosen.mata-kuliah.show', $kelas->id))
            ->assertSessionHas('status');

        $this->assertSame(StatusPendaftaranKelas::Buka, $kelas->fresh()->status_pendaftaran);

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.status', $kelas), ['status_pendaftaran' => 'tutup'])
            ->assertRedirect(route('dosen.mata-kuliah.show', $kelas->id));

        $this->assertSame(StatusPendaftaranKelas::Tutup, $kelas->fresh()->status_pendaftaran);
    }

    public function test_kunci_mentah_hanya_tampil_satu_kali_dan_database_hanya_menyimpan_hash(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $kunciService = app(KunciGabungKelasService::class);

        $respons = $this->actingAs($dosen)->post(route('dosen.mata-kuliah.kunci', $kelas));

        $kunci = $respons->getSession()->get('kode_gabung');

        $this->assertIsString($kunci);
        $this->assertMatchesRegularExpression('/^[A-F0-9]{32}$/', $kunci);

        $baris = $kelas->fresh()->getRawOriginal('kode_gabung_hash');

        $this->assertSame($kunciService->hash($kunci), $baris);
        $this->assertStringNotContainsString($kunci, json_encode($baris));

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.show', $kelas))
            ->assertOk()
            ->assertSee('Kunci gabung dibuat')
            ->assertSee($kunci);

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.show', $kelas))
            ->assertOk()
            ->assertDontSee($kunci);
    }

    public function test_rotasi_kunci_membatalkan_kunci_lama_tanpa_menghapus_peserta(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $mahasiswa = User::factory()->mahasiswa()->create();
        $this->enroll($mahasiswa, $kelas);
        $kunciService = app(KunciGabungKelasService::class);

        $kunciLama = $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.kunci', $kelas))
            ->getSession()
            ->get('kode_gabung');

        $hashLama = $kelas->fresh()->getRawOriginal('kode_gabung_hash');

        $respons = $this->actingAs($dosen)->post(route('dosen.mata-kuliah.kunci', $kelas));
        $kunciBaru = $respons->getSession()->get('kode_gabung');

        $this->assertNotSame($kunciLama, $kunciBaru);

        $hashBaru = $kelas->fresh()->getRawOriginal('kode_gabung_hash');

        $this->assertNotSame($hashLama, $hashBaru);
        $this->assertSame($kunciService->hash($kunciBaru), $hashBaru);
        $this->assertFalse($kunciService->verifikasi($kelas->fresh(), $kunciLama));
        $this->assertTrue($kunciService->verifikasi($kelas->fresh(), $kunciBaru));
        $this->assertSame(1, PesertaMataKuliah::query()->where('kelas_mata_kuliah_id', $kelas->id)->count());

        $respons->assertRedirect(route('dosen.mata-kuliah.show', $kelas->id));

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.show', $kelas))
            ->assertOk()
            ->assertSee('Kunci gabung baru')
            ->assertSee($kunciBaru)
            ->assertDontSee($kunciLama)
            ->assertSee('Kunci lama sudah tidak berlaku');
    }

    public function test_kelas_historis_tetap_terlihat_dan_opsi_form_hanya_data_aktif(): void
    {
        $dosen = $this->dosen();
        $mkAktif = $this->mataKuliah('MK-AKTIF');
        $tahunAktif = $this->tahun(2026);
        $mkHistoris = $this->mataKuliah('MK-LAMA', false);
        $tahunHistoris = $this->tahun(2024, false);
        $kelasHistoris = $this->kelas($dosen, $tahunHistoris, $mkHistoris, 'L01');

        $daftar = $this->actingAs($dosen)->get(route('dosen.mata-kuliah.index'));

        $daftar->assertOk()
            ->assertSee('MK-LAMA')
            ->assertSee('MK historis')
            ->assertSee('Periode historis');

        $create = $this->actingAs($dosen)->get(route('dosen.mata-kuliah.create'));

        $labelMataKuliah = $create->viewData('mataKuliahOptions')->pluck('label')->implode('|');
        $labelTahun = $create->viewData('tahunAkademikOptions')->pluck('label')->implode('|');

        $this->assertStringContainsString('MK-AKTIF', $labelMataKuliah);
        $this->assertStringNotContainsString('MK-LAMA', $labelMataKuliah);
        $this->assertStringContainsString('2026/2027', $labelTahun);
        $this->assertStringNotContainsString('2024/2025', $labelTahun);
        $this->assertSame([$mkAktif->id], $create->viewData('mataKuliahOptions')->pluck('id')->all());
        $this->assertSame([$tahunAktif->id], $create->viewData('tahunAkademikOptions')->pluck('id')->all());

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.show', $kelasHistoris))
            ->assertOk()
            ->assertSee('MK-LAMA')
            ->assertSee('Periode historis');
    }

    public function test_pencarian_hanya_menampilkan_kelas_milik_dosen_sendiri(): void
    {
        $dosen = $this->dosen();
        $dosenLain = $this->dosen();
        $tahun = $this->tahun();
        $kelasSaya = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'A01', null, 'Kelas Saya');
        $kelasLain = $this->kelas($dosenLain, $tahun, $this->mataKuliah(), 'B02', null, 'Kelas Orang Lain');

        $respons = $this->actingAs($dosen)->get(route('dosen.mata-kuliah.index', ['q' => 'Kelas']));

        $respons->assertOk()
            ->assertSee('Kelas Saya')
            ->assertDontSee('Kelas Orang Lain');

        $ids = $respons->viewData('kelas')->pluck('id')->all();

        $this->assertContains($kelasSaya->id, $ids);
        $this->assertNotContains($kelasLain->id, $ids);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function dosen(): User
    {
        return User::factory()->dosen()->create();
    }

    private function tahun(int $mulai = 2026, bool $aktif = true): TahunAkademik
    {
        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => $mulai,
            'tahun_selesai' => $mulai + 1,
            'semester' => 'ganjil',
            'tanggal_mulai' => $mulai.'-09-01',
            'tanggal_selesai' => $mulai.'-12-31',
            'aktif' => $aktif,
        ])->save();

        return $tahun;
    }

    private function mataKuliah(?string $kode = null, bool $aktif = true): MataKuliah
    {
        $this->urutan++;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => $kode ?? 'MK-'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Uji '.$this->urutan,
            'sks' => 3,
            'aktif' => $aktif,
        ])->save();

        return $mataKuliah;
    }

    private function kelas(
        User $dosen,
        TahunAkademik $tahun,
        MataKuliah $mataKuliah,
        string $kode,
        ?int $kapasitas = 30,
        ?string $nama = null,
    ): KelasMataKuliah {
        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kode,
            'nama_kelas' => $nama,
            'kapasitas' => $kapasitas,
        ])->save();

        return $kelas;
    }

    private function enroll(
        User $mahasiswa,
        KelasMataKuliah $kelas,
        string $status = StatusPeserta::Aktif->value,
    ): PesertaMataKuliah {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status,
            'tanggal_daftar' => Carbon::now()->subWeek(),
        ])->save();

        return $peserta;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function dataValidDengan(
        MataKuliah $mataKuliah,
        TahunAkademik $tahun,
        array $overrides = [],
    ): array {
        return array_merge([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'kode_kelas' => 'A01',
            'nama_kelas' => 'Kelas A',
            'kapasitas' => 30,
        ], $overrides);
    }
}
