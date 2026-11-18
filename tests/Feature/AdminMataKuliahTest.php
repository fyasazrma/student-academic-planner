<?php

namespace Tests\Feature;

use App\Enums\StatusPendaftaranKelas;
use App\Http\Requests\Admin\PerbaruiMataKuliahRequest;
use App\Http\Requests\Admin\SimpanMataKuliahRequest;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Kelas\KunciGabungKelasService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminMataKuliahTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // ---------------------------------------------------------------------
    // Otorisasi
    // ---------------------------------------------------------------------

    public function test_admin_aktif_dapat_mengakses_semua_route_crud(): void
    {
        $admin = User::factory()->admin()->create();
        $mataKuliah = $this->buatMataKuliah();
        $yangAkanDihapus = $this->buatMataKuliah();

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.index'))
            ->assertOk()
            ->assertViewIs('admin.mata-kuliah.index');

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.create'))
            ->assertOk()
            ->assertViewIs('admin.mata-kuliah.create');

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), $this->dataValid(['kode' => 'MK-BARU']))
            ->assertRedirect(route('admin.mata-kuliah.index'));

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.edit', $mataKuliah))
            ->assertOk()
            ->assertViewIs('admin.mata-kuliah.edit');

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.update', $mataKuliah), $this->dataValid(['kode' => 'MK-EDIT']))
            ->assertRedirect(route('admin.mata-kuliah.index'));

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.status', $mataKuliah), ['aktif' => 0])
            ->assertRedirect(route('admin.mata-kuliah.index'));

        $this->actingAs($admin)
            ->delete(route('admin.mata-kuliah.destroy', $yangAkanDihapus))
            ->assertRedirect(route('admin.mata-kuliah.index'));
    }

    public function test_guest_ditolak_dari_semua_route_admin(): void
    {
        $mataKuliah = $this->buatMataKuliah();

        $this->get(route('admin.mata-kuliah.index'))->assertRedirect(route('login'));
        $this->get(route('admin.mata-kuliah.create'))->assertRedirect(route('login'));
        $this->get(route('admin.mata-kuliah.edit', $mataKuliah))->assertRedirect(route('login'));
        $this->post(route('admin.mata-kuliah.store'), [])->assertRedirect(route('login'));
        $this->put(route('admin.mata-kuliah.update', $mataKuliah), [])->assertRedirect(route('login'));
        $this->post(route('admin.mata-kuliah.status', $mataKuliah), ['aktif' => 0])->assertRedirect(route('login'));
        $this->delete(route('admin.mata-kuliah.destroy', $mataKuliah))->assertRedirect(route('login'));
    }

    public function test_mahasiswa_dan_dosen_ditolak_dari_semua_route_admin(): void
    {
        $mataKuliah = $this->buatMataKuliah();

        foreach ([User::factory()->mahasiswa(), User::factory()->dosen()] as $factory) {
            $user = $factory->create();

            $this->actingAs($user)->get(route('admin.mata-kuliah.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.mata-kuliah.create'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.mata-kuliah.edit', $mataKuliah))->assertForbidden();
            $this->actingAs($user)->post(route('admin.mata-kuliah.store'), [])->assertForbidden();
            $this->actingAs($user)->put(route('admin.mata-kuliah.update', $mataKuliah), [])->assertForbidden();
            $this->actingAs($user)->post(route('admin.mata-kuliah.status', $mataKuliah), ['aktif' => 0])->assertForbidden();
            $this->actingAs($user)->delete(route('admin.mata-kuliah.destroy', $mataKuliah))->assertForbidden();
        }
    }

    // ---------------------------------------------------------------------
    // Daftar, pencarian, dan pagination
    // ---------------------------------------------------------------------

    public function test_daftar_dan_pencarian_kode_serta_nama_berjalan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->buatMataKuliah(['kode' => 'IF101', 'nama' => 'Pemrograman Web']);
        $this->buatMataKuliah(['kode' => 'SI202', 'nama' => 'Basis Data']);

        $semua = $this->actingAs($admin)->get(route('admin.mata-kuliah.index'));
        $semua->assertOk()
            ->assertSee('Pemrograman Web')
            ->assertSee('Basis Data');
        $this->assertSame(2, $semua->viewData('mataKuliah')->total());

        $berdasarkanNama = $this->actingAs($admin)->get(route('admin.mata-kuliah.index', ['q' => 'Pemrograman']));
        $berdasarkanNama->assertOk()
            ->assertSee('Pemrograman Web')
            ->assertDontSee('Basis Data');
        $this->assertSame(1, $berdasarkanNama->viewData('mataKuliah')->total());
        $this->assertSame('Pemrograman', $berdasarkanNama->viewData('kataKunci'));

        $berdasarkanKode = $this->actingAs($admin)->get(route('admin.mata-kuliah.index', ['q' => 'SI202']));
        $berdasarkanKode->assertOk()
            ->assertSee('Basis Data')
            ->assertDontSee('Pemrograman Web');
    }

    public function test_pagination_daftar_mata_kuliah_berjalan(): void
    {
        $admin = User::factory()->admin()->create();

        for ($index = 0; $index < 12; $index++) {
            $this->buatMataKuliah(['nama' => 'Mata Kuliah Halaman '.($index + 1)]);
        }

        $halamanPertama = $this->actingAs($admin)->get(route('admin.mata-kuliah.index'));
        $paginator = $halamanPertama->viewData('mataKuliah');

        $this->assertSame(12, $paginator->total());
        $this->assertSame(10, $paginator->perPage());
        $this->assertSame(2, $paginator->lastPage());
        $this->assertCount(10, $paginator->items());

        $halamanKedua = $this->actingAs($admin)->get(route('admin.mata-kuliah.index', ['page' => 2]));
        $this->assertCount(2, $halamanKedua->viewData('mataKuliah')->items());
        $halamanPertama->assertSee('?page=2', false);
    }

    // ---------------------------------------------------------------------
    // Simpan dan perbarui
    // ---------------------------------------------------------------------

    public function test_store_berhasil_menyimpan_data_dengan_status_awal_aktif(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), $this->dataValid([
                'kode' => 'MK-STORE',
                'nama' => 'Pemrograman Laravel',
                'sks' => 4,
                'deskripsi' => 'Membangun aplikasi web dengan Laravel.',
                'program_studi' => 'Informatika',
                'semester' => 5,
            ]))
            ->assertRedirect(route('admin.mata-kuliah.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('mata_kuliah', [
            'kode' => 'MK-STORE',
            'nama' => 'Pemrograman Laravel',
            'sks' => 4,
            'deskripsi' => 'Membangun aplikasi web dengan Laravel.',
            'program_studi' => 'Informatika',
            'semester' => 5,
            'aktif' => 1,
        ]);
    }

    public function test_update_berhasil_tanpa_mengubah_status_atau_relasi_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $mataKuliah = $this->buatMataKuliah([
            'kode' => 'MK-LAMA',
            'nama' => 'Nama Lama',
            'sks' => 2,
            'semester' => 1,
        ]);
        $kelas = $this->buatKelas($mataKuliah);

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.update', $mataKuliah), $this->dataValid([
                'kode' => 'MK-BARU',
                'nama' => 'Nama Baru',
                'sks' => 3,
                'program_studi' => 'Sistem Informasi',
                'semester' => 6,
            ]))
            ->assertRedirect(route('admin.mata-kuliah.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('mata_kuliah', [
            'id' => $mataKuliah->id,
            'kode' => 'MK-BARU',
            'nama' => 'Nama Baru',
            'sks' => 3,
            'program_studi' => 'Sistem Informasi',
            'semester' => 6,
            'aktif' => 1,
        ]);
        $this->assertDatabaseMissing('mata_kuliah', ['kode' => 'MK-LAMA']);
        $this->assertDatabaseHas('kelas_mata_kuliah', ['id' => $kelas->id, 'mata_kuliah_id' => $mataKuliah->id]);
    }

    public function test_validasi_seluruh_field_berjalan(): void
    {
        $admin = User::factory()->admin()->create();

        $kasus = [
            ['kode', ['kode' => '']],
            ['kode', ['kode' => str_repeat('A', 21)]],
            ['nama', ['nama' => '']],
            ['nama', ['nama' => str_repeat('N', 151)]],
            ['sks', ['sks' => 0]],
            ['sks', ['sks' => 7]],
            ['sks', ['sks' => 'tiga']],
            ['deskripsi', ['deskripsi' => str_repeat('D', 1001)]],
            ['program_studi', ['program_studi' => str_repeat('P', 101)]],
            ['semester', ['semester' => 0]],
            ['semester', ['semester' => 9]],
            ['semester', ['semester' => 'lima']],
        ];

        foreach ($kasus as [$field, $overrides]) {
            $this->actingAs($admin)
                ->post(route('admin.mata-kuliah.store'), $this->dataValid($overrides))
                ->assertSessionHasErrors($field);
        }

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), $this->dataValid([
                'deskripsi' => '',
                'program_studi' => '',
                'semester' => '',
            ]))
            ->assertRedirect(route('admin.mata-kuliah.index'));

        $tersimpan = MataKuliah::query()->latest('id')->firstOrFail();
        $this->assertNull($tersimpan->deskripsi);
        $this->assertNull($tersimpan->program_studi);
        $this->assertNull($tersimpan->semester);
    }

    public function test_kode_unik_dan_normalisasi_uppercase_berjalan(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = $this->buatMataKuliah(['kode' => 'MK-EXIST']);

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), $this->dataValid(['kode' => '  mk-abc  ']))
            ->assertRedirect(route('admin.mata-kuliah.index'));
        $this->assertDatabaseHas('mata_kuliah', ['kode' => 'MK-ABC']);

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), $this->dataValid(['kode' => 'mk-abc']))
            ->assertSessionHasErrors('kode');

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.update', $existing), $this->dataValid(['kode' => 'mk-exist']))
            ->assertRedirect(route('admin.mata-kuliah.index'));
        $this->assertDatabaseHas('mata_kuliah', ['id' => $existing->id, 'kode' => 'MK-EXIST']);

        $lain = $this->buatMataKuliah(['kode' => 'MK-LAIN']);
        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.update', $existing), $this->dataValid(['kode' => 'mk-lain']))
            ->assertSessionHasErrors('kode');
    }

    // ---------------------------------------------------------------------
    // Status dan integritas data
    // ---------------------------------------------------------------------

    public function test_status_hanya_dapat_diubah_melalui_endpoint_status(): void
    {
        $admin = User::factory()->admin()->create();
        $mataKuliah = $this->buatMataKuliah(['aktif' => true]);

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.update', $mataKuliah), $this->dataValid(['aktif' => false]))
            ->assertRedirect(route('admin.mata-kuliah.index'));
        $this->assertDatabaseHas('mata_kuliah', ['id' => $mataKuliah->id, 'aktif' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.status', $mataKuliah), ['aktif' => 0])
            ->assertRedirect(route('admin.mata-kuliah.index'));
        $this->assertDatabaseHas('mata_kuliah', ['id' => $mataKuliah->id, 'aktif' => 0]);

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.status', $mataKuliah), ['aktif' => 1])
            ->assertRedirect(route('admin.mata-kuliah.index'));
        $this->assertDatabaseHas('mata_kuliah', ['id' => $mataKuliah->id, 'aktif' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.status', $mataKuliah), [])
            ->assertSessionHasErrors('aktif');
        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.status', $mataKuliah), ['aktif' => 'mungkin'])
            ->assertSessionHasErrors('aktif');
    }

    public function test_store_dan_update_tidak_menerima_field_aktif_dari_request(): void
    {
        $admin = User::factory()->admin()->create();
        $mataKuliah = $this->buatMataKuliah(['aktif' => true]);

        $this->assertArrayNotHasKey('aktif', (new SimpanMataKuliahRequest())->rules());
        $this->assertArrayNotHasKey('aktif', (new PerbaruiMataKuliahRequest())->rules());

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), $this->dataValid(['aktif' => false]))
            ->assertRedirect(route('admin.mata-kuliah.index'));
        $this->assertDatabaseHas('mata_kuliah', ['kode' => $this->kodeTerakhir(), 'aktif' => 1]);

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.update', $mataKuliah), $this->dataValid(['aktif' => false]))
            ->assertRedirect(route('admin.mata-kuliah.index'));
        $this->assertDatabaseHas('mata_kuliah', ['id' => $mataKuliah->id, 'aktif' => 1]);
    }

    public function test_mata_kuliah_nonaktif_tidak_muncul_sebagai_pilihan_kelas_baru(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $aktif = $this->buatMataKuliah(['kode' => 'MK-AKTIF', 'nama' => 'Mata Kuliah Aktif Tersedia']);
        $nonaktif = $this->buatMataKuliah([
            'kode' => 'MK-NONAKTIF',
            'nama' => 'Mata Kuliah Nonaktif Tersedia',
            'aktif' => false,
        ]);
        $kelasAktif = $this->buatKelas($aktif, true);
        $kelasNonaktif = $this->buatKelas($nonaktif, true);

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah.cari', ['q' => 'Tersedia']));

        $ids = collect($response->viewData('kelas')->items())->pluck('id')->all();

        $response->assertOk()
            ->assertSee('Mata Kuliah Aktif Tersedia')
            ->assertDontSee('Mata Kuliah Nonaktif Tersedia');
        $this->assertContains($kelasAktif->id, $ids);
        $this->assertNotContains($kelasNonaktif->id, $ids);
    }

    public function test_mata_kuliah_tanpa_kelas_dapat_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $mataKuliah = $this->buatMataKuliah(['kode' => 'MK-HAPUS']);

        $this->actingAs($admin)
            ->delete(route('admin.mata-kuliah.destroy', $mataKuliah))
            ->assertRedirect(route('admin.mata-kuliah.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('mata_kuliah', ['id' => $mataKuliah->id]);
    }

    public function test_mata_kuliah_dengan_kelas_tidak_dapat_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $mataKuliah = $this->buatMataKuliah();
        $kelas = $this->buatKelas($mataKuliah);

        $this->actingAs($admin)
            ->delete(route('admin.mata-kuliah.destroy', $mataKuliah))
            ->assertRedirect(route('admin.mata-kuliah.index'))
            ->assertSessionHas('error', 'Mata kuliah tidak dapat dihapus karena masih memiliki kelas.');

        $this->assertDatabaseHas('mata_kuliah', ['id' => $mataKuliah->id]);
        $this->assertDatabaseHas('kelas_mata_kuliah', ['id' => $kelas->id]);
    }

    public function test_foreign_key_tetap_menjaga_integritas_dan_detail_sql_tidak_tampil(): void
    {
        $admin = User::factory()->admin()->create();
        $mataKuliah = $this->buatMataKuliah();
        $this->buatKelas($mataKuliah);

        try {
            DB::table('mata_kuliah')->where('id', $mataKuliah->id)->delete();
            $this->fail('Foreign key seharusnya menolak penghapusan langsung.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('FOREIGN KEY', strtoupper($exception->getMessage()));
        }

        $this->assertDatabaseHas('mata_kuliah', ['id' => $mataKuliah->id]);

        $this->actingAs($admin)
            ->delete(route('admin.mata-kuliah.destroy', $mataKuliah))
            ->assertRedirect(route('admin.mata-kuliah.index'));

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.index'))
            ->assertSee('Mata kuliah tidak dapat dihapus karena masih memiliki kelas.')
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('FOREIGN KEY');
    }

    // ---------------------------------------------------------------------
    // Feedback dan navigasi
    // ---------------------------------------------------------------------

    public function test_formulir_menampilkan_validation_errors(): void
    {
        // Render partial formulir secara langsung dengan error bag ter-share.
        // Ini menguji tampilan error tanpa bergantung pada flash session
        // lintas request pada lingkungan test Laravel yang memakai ulang app.
        $this->withViewErrors([
            'nama' => ['Nama mata kuliah wajib diisi.'],
            'sks' => ['SKS wajib diisi.'],
        ]);

        $this->blade('@include("admin.mata-kuliah._form")', [
            'mode' => 'create',
            'mataKuliahId' => null,
            'data' => [
                'kode' => '',
                'nama' => '',
                'sks' => null,
                'deskripsi' => null,
                'program_studi' => null,
                'semester' => null,
            ],
        ])
            ->assertSee('Periksa kembali formulir berikut:')
            ->assertSee('Nama mata kuliah wajib diisi.')
            ->assertSee('SKS wajib diisi.')
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_flash_feedback_dan_validation_errors_tampil_benar(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), $this->dataValid(['nama' => 'Feedback Tersimpan']))
            ->assertRedirect(route('admin.mata-kuliah.index'));

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.index'))
            ->assertOk()
            ->assertSee('berhasil ditambahkan');
    }

    public function test_menu_mata_kuliah_admin_aktif_dan_tahun_akademik_tetap_segera(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.index'))
            ->assertOk()
            ->assertSee(route('admin.mata-kuliah.index'), false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Menu admin')
            ->assertSee('Dashboard Admin')
            ->assertSee('Tahun Akademik')
            ->assertSee('Segera')
            ->assertDontSee('Menu mahasiswa');
    }

    public function test_navigasi_dan_halaman_mahasiswa_tidak_mengalami_regresi(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertOk()
            ->assertSee('Menu mahasiswa')
            ->assertSee(route('mahasiswa.mata-kuliah'), false)
            ->assertDontSee('Menu admin');

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Mahasiswa')
            ->assertDontSee('Menu admin');

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.index'))
            ->assertOk()
            ->assertDontSee('Menu mahasiswa');
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function dataValid(array $overrides = []): array
    {
        $this->urutan++;
        $nomor = str_pad((string) $this->urutan, 4, '0', STR_PAD_LEFT);

        return array_merge([
            'kode' => 'MK'.$nomor,
            'nama' => 'Mata Kuliah '.$nomor,
            'sks' => 3,
            'deskripsi' => 'Deskripsi mata kuliah '.$nomor.'.',
            'program_studi' => 'Informatika',
            'semester' => 1,
        ], $overrides);
    }

    /**
     * Kode yang dipakai oleh dataValid() pada pemanggilan terakhir.
     */
    private function kodeTerakhir(): string
    {
        return 'MK'.str_pad((string) $this->urutan, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatMataKuliah(array $overrides = []): MataKuliah
    {
        $this->urutan++;
        $nomor = (string) $this->urutan;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill(array_merge([
            'kode' => 'MK'.str_pad($nomor, 4, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah '.$nomor,
            'sks' => 3,
            'deskripsi' => null,
            'program_studi' => 'Informatika',
            'semester' => 1,
            'aktif' => true,
        ], $overrides))->save();

        return $mataKuliah;
    }

    private function buatTahunAkademik(): TahunAkademik
    {
        $this->urutan++;
        $tahunMulai = 2020 + $this->urutan;

        $tahunAkademik = new TahunAkademik();
        $tahunAkademik->forceFill([
            'tahun_mulai' => $tahunMulai,
            'tahun_selesai' => $tahunMulai + 1,
            'semester' => 'ganjil',
            'tanggal_mulai' => $tahunMulai.'-09-01',
            'tanggal_selesai' => $tahunMulai.'-12-31',
            'aktif' => true,
        ])->save();

        return $tahunAkademik;
    }

    private function buatKelas(MataKuliah $mataKuliah, bool $denganKunci = false): KelasMataKuliah
    {
        $this->urutan++;
        $dosen = User::factory()->dosen()->create();

        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $this->buatTahunAkademik()->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => 'K'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama_kelas' => 'Kelas '.$this->urutan,
            'kapasitas' => 30,
            'status_pendaftaran' => StatusPendaftaranKelas::Buka->value,
        ])->save();

        if ($denganKunci) {
            app(KunciGabungKelasService::class)->terapkan($kelas);
        }

        return $kelas->fresh();
    }
}
