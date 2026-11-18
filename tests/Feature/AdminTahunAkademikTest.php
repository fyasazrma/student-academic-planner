<?php

namespace Tests\Feature;

use App\Enums\StatusPendaftaranKelas;
use App\Http\Requests\Admin\PerbaruiTahunAkademikRequest;
use App\Http\Requests\Admin\SimpanTahunAkademikRequest;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Admin\AdminTahunAkademikService;
use App\Services\Kelas\KunciGabungKelasService;
use App\Services\Mahasiswa\MataKuliahService;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AdminTahunAkademikTest extends TestCase
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
        $tahunAkademik = $this->tahun();
        $yangAkanDihapus = $this->tahun();

        $this->actingAs($admin)
            ->get(route('admin.tahun-akademik.index'))
            ->assertOk()
            ->assertViewIs('admin.tahun-akademik.index');

        $this->actingAs($admin)
            ->get(route('admin.tahun-akademik.create'))
            ->assertOk()
            ->assertViewIs('admin.tahun-akademik.create');

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.store'), $this->dataValid())
            ->assertRedirect(route('admin.tahun-akademik.index'));

        $this->actingAs($admin)
            ->get(route('admin.tahun-akademik.edit', $tahunAkademik))
            ->assertOk()
            ->assertViewIs('admin.tahun-akademik.edit');

        $this->actingAs($admin)
            ->put(route('admin.tahun-akademik.update', $tahunAkademik), $this->dataValid())
            ->assertRedirect(route('admin.tahun-akademik.index'));

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.status', $tahunAkademik), ['aktif' => 1])
            ->assertRedirect(route('admin.tahun-akademik.index'));

        $this->actingAs($admin)
            ->delete(route('admin.tahun-akademik.destroy', $yangAkanDihapus))
            ->assertRedirect(route('admin.tahun-akademik.index'));
    }

    public function test_guest_ditolak_dari_semua_route_admin(): void
    {
        $tahunAkademik = $this->tahun();

        $this->get(route('admin.tahun-akademik.index'))->assertRedirect(route('login'));
        $this->get(route('admin.tahun-akademik.create'))->assertRedirect(route('login'));
        $this->get(route('admin.tahun-akademik.edit', $tahunAkademik))->assertRedirect(route('login'));
        $this->post(route('admin.tahun-akademik.store'), [])->assertRedirect(route('login'));
        $this->put(route('admin.tahun-akademik.update', $tahunAkademik), [])->assertRedirect(route('login'));
        $this->post(route('admin.tahun-akademik.status', $tahunAkademik), ['aktif' => 1])->assertRedirect(route('login'));
        $this->delete(route('admin.tahun-akademik.destroy', $tahunAkademik))->assertRedirect(route('login'));
    }

    public function test_mahasiswa_dan_dosen_ditolak_dari_semua_route_admin(): void
    {
        $tahunAkademik = $this->tahun();

        foreach ([User::factory()->mahasiswa(), User::factory()->dosen()] as $factory) {
            $user = $factory->create();

            $this->actingAs($user)->get(route('admin.tahun-akademik.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.tahun-akademik.create'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.tahun-akademik.edit', $tahunAkademik))->assertForbidden();
            $this->actingAs($user)->post(route('admin.tahun-akademik.store'), [])->assertForbidden();
            $this->actingAs($user)->put(route('admin.tahun-akademik.update', $tahunAkademik), [])->assertForbidden();
            $this->actingAs($user)->post(route('admin.tahun-akademik.status', $tahunAkademik), ['aktif' => 1])->assertForbidden();
            $this->actingAs($user)->delete(route('admin.tahun-akademik.destroy', $tahunAkademik))->assertForbidden();
        }
    }

    // ---------------------------------------------------------------------
    // Daftar dan feedback
    // ---------------------------------------------------------------------

    public function test_daftar_menampilkan_periode_dan_jumlah_kelas_dari_database(): void
    {
        $admin = User::factory()->admin()->create();
        $pertama = $this->tahun(true);
        $kedua = $this->tahun(false);
        $this->buatKelas(['tahun_akademik' => $kedua]);

        $response = $this->actingAs($admin)->get(route('admin.tahun-akademik.index'));

        $response->assertOk()
            ->assertSee($pertama->label())
            ->assertSee($kedua->label())
            ->assertSee('Aktif')
            ->assertSee('Nonaktif');
        $this->assertSame(2, $response->viewData('tahunAkademik')->total());
        $items = collect($response->viewData('tahunAkademik')->items())->keyBy('id');
        $this->assertSame(0, $items->get($pertama->id)['jumlah_kelas']);
        $this->assertSame(1, $items->get($kedua->id)['jumlah_kelas']);
    }

    public function test_menu_tahun_akademik_admin_aktif(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.tahun-akademik.index'))
            ->assertOk()
            ->assertSee(route('admin.tahun-akademik.index'), false)
            ->assertSee(route('admin.tahun-akademik.create'), false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Menu admin')
            ->assertDontSee('Segera')
            ->assertDontSee('Menu mahasiswa');
    }

    public function test_formulir_menampilkan_validation_errors(): void
    {
        $this->withViewErrors([
            'tahun_mulai' => ['Tahun mulai wajib diisi.'],
            'semester' => ['Semester harus ganjil atau genap.'],
        ]);

        $this->blade('@include("admin.tahun-akademik._form")', [
            'mode' => 'create',
            'tahunAkademikId' => null,
            'memilikiKelas' => false,
            'data' => [
                'tahun_mulai' => '',
                'tahun_selesai' => '',
                'semester' => '',
                'tanggal_mulai' => '',
                'tanggal_selesai' => '',
            ],
        ])
            ->assertSee('Periksa kembali formulir berikut:')
            ->assertSee('Tahun mulai wajib diisi.')
            ->assertSee('Semester harus ganjil atau genap.')
            ->assertSee('aria-invalid="true"', false);
    }

    // ---------------------------------------------------------------------
    // Simpan dan perbarui
    // ---------------------------------------------------------------------

    public function test_store_berhasil_dengan_status_awal_nonaktif(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->dataValid([
            'tahun_mulai' => 2026,
            'tahun_selesai' => 2027,
            'semester' => 'ganjil',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-12-31',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.store'), $data)
            ->assertRedirect(route('admin.tahun-akademik.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tahun_akademik', [
            'tahun_mulai' => 2026,
            'tahun_selesai' => 2027,
            'semester' => 'ganjil',
            'aktif' => 0,
        ]);
        $tersimpan = TahunAkademik::query()->where('tahun_mulai', 2026)->firstOrFail();
        $this->assertSame('2026-09-01', $tersimpan->tanggal_mulai->toDateString());
        $this->assertSame('2026-12-31', $tersimpan->tanggal_selesai->toDateString());
    }

    public function test_store_dan_update_tidak_menerima_field_aktif_dari_request(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAkademik = $this->tahun(false);

        $this->assertArrayNotHasKey('aktif', (new SimpanTahunAkademikRequest())->rules());
        $this->assertArrayNotHasKey('aktif', (new PerbaruiTahunAkademikRequest())->rules());

        $data = $this->dataValid(['aktif' => true]);
        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.store'), $data)
            ->assertRedirect(route('admin.tahun-akademik.index'));
        $this->assertDatabaseHas('tahun_akademik', [
            'tahun_mulai' => $data['tahun_mulai'],
            'aktif' => 0,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.tahun-akademik.update', $tahunAkademik), $this->dataValid(['aktif' => true]))
            ->assertRedirect(route('admin.tahun-akademik.index'));
        $this->assertDatabaseHas('tahun_akademik', [
            'id' => $tahunAkademik->id,
            'aktif' => 0,
        ]);
    }

    public function test_validasi_seluruh_field_berjalan(): void
    {
        $admin = User::factory()->admin()->create();

        $kasus = [
            ['tahun_mulai', ['tahun_mulai' => '']],
            ['tahun_mulai', ['tahun_mulai' => 'dua ribu']],
            ['tahun_mulai', ['tahun_mulai' => 1999]],
            ['tahun_mulai', ['tahun_mulai' => 2201]],
            ['tahun_selesai', ['tahun_selesai' => '']],
            ['tahun_selesai', ['tahun_selesai' => 2020]],
            ['semester', ['semester' => '']],
            ['semester', ['semester' => 'pendek']],
            ['tanggal_mulai', ['tanggal_mulai' => '']],
            ['tanggal_mulai', ['tanggal_mulai' => '01-09-2026']],
            ['tanggal_selesai', ['tanggal_selesai' => '']],
            ['tanggal_selesai', ['tanggal_selesai' => '2026-01-01']],
        ];

        foreach ($kasus as [$field, $overrides]) {
            $this->actingAs($admin)
                ->post(route('admin.tahun-akademik.store'), $this->dataValid($overrides))
                ->assertSessionHasErrors($field);
        }
    }

    public function test_periode_duplikat_ditolak_saat_simpan_dan_perbarui(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = $this->tahun(false);
        $target = $this->tahun(false);
        $periode = [
            'tahun_mulai' => (int) $existing->tahun_mulai,
            'tahun_selesai' => (int) $existing->tahun_selesai,
            'semester' => $existing->semester->value,
        ];

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.store'), $this->dataValid($periode))
            ->assertSessionHasErrors('tahun_mulai');

        $this->actingAs($admin)
            ->put(route('admin.tahun-akademik.update', $target), $this->dataValid($periode))
            ->assertSessionHasErrors('tahun_mulai');

        $this->assertSame(2, TahunAkademik::query()->count());
    }

    public function test_update_tanggal_periode_berhasil_walaupun_sudah_memiliki_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAkademik = $this->tahun(false);
        [$kelas] = $this->buatKelas(['tahun_akademik' => $tahunAkademik]);

        $this->actingAs($admin)
            ->put(route('admin.tahun-akademik.update', $tahunAkademik), $this->dataValid([
                'tahun_mulai' => (int) $tahunAkademik->tahun_mulai,
                'tahun_selesai' => (int) $tahunAkademik->tahun_selesai,
                'semester' => $tahunAkademik->semester->value,
                'tanggal_mulai' => '2025-02-01',
                'tanggal_selesai' => '2025-06-30',
                'aktif' => true,
            ]))
            ->assertRedirect(route('admin.tahun-akademik.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tahun_akademik', [
            'id' => $tahunAkademik->id,
            'tahun_mulai' => (int) $tahunAkademik->tahun_mulai,
            'tahun_selesai' => (int) $tahunAkademik->tahun_selesai,
            'semester' => $tahunAkademik->semester->value,
            'aktif' => 0,
        ]);
        $tersimpan = $tahunAkademik->fresh();
        $this->assertSame('2025-02-01', $tersimpan->tanggal_mulai->toDateString());
        $this->assertSame('2025-06-30', $tersimpan->tanggal_selesai->toDateString());
        $this->assertDatabaseHas('kelas_mata_kuliah', [
            'id' => $kelas->id,
            'tahun_akademik_id' => $tahunAkademik->id,
        ]);
    }

    public function test_identitas_periode_tidak_dapat_diubah_saat_memiliki_kelas(): void
    {
        $admin = User::factory()->admin()->create();

        $kasus = [
            [
                'tahun_mulai',
                fn (TahunAkademik $tahun): array => [
                    'tahun_mulai' => (int) $tahun->tahun_mulai + 1,
                    'tahun_selesai' => (int) $tahun->tahun_selesai + 1,
                ],
            ],
            ['tahun_selesai', fn (TahunAkademik $tahun): array => ['tahun_selesai' => (int) $tahun->tahun_selesai + 1]],
            ['semester', fn (TahunAkademik $tahun): array => ['semester' => $tahun->semester->value === 'ganjil' ? 'genap' : 'ganjil']],
        ];

        foreach ($kasus as [$field, $overrides]) {
            $tahunAkademik = $this->tahun(false);
            $this->buatKelas(['tahun_akademik' => $tahunAkademik]);

            $data = $this->dataValid(array_merge([
                'tahun_mulai' => (int) $tahunAkademik->tahun_mulai,
                'tahun_selesai' => (int) $tahunAkademik->tahun_selesai,
                'semester' => $tahunAkademik->semester->value,
            ], $overrides($tahunAkademik)));

            $this->actingAs($admin)
                ->put(route('admin.tahun-akademik.update', $tahunAkademik), $data)
                ->assertSessionHasErrors($field);

            $this->assertDatabaseHas('tahun_akademik', [
                'id' => $tahunAkademik->id,
                'tahun_mulai' => (int) $tahunAkademik->tahun_mulai,
                'tahun_selesai' => (int) $tahunAkademik->tahun_selesai,
                'semester' => $tahunAkademik->semester->value,
            ]);
        }
    }

    public function test_formulir_edit_menandai_identitas_terkunci_saat_memiliki_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAkademik = $this->tahun(false);
        $this->buatKelas(['tahun_akademik' => $tahunAkademik]);

        $this->actingAs($admin)
            ->get(route('admin.tahun-akademik.edit', $tahunAkademik))
            ->assertOk()
            ->assertSee('tidak dapat diubah', false)
            ->assertSee('readonly', false)
            ->assertSee('disabled', false);
    }

    // ---------------------------------------------------------------------
    // Status, single-active, dan locking
    // ---------------------------------------------------------------------

    public function test_mengaktifkan_periode_baru_menonaktifkan_periode_aktif_sebelumnya(): void
    {
        $admin = User::factory()->admin()->create();
        $lama = $this->tahun(true);
        $baru = $this->tahun(false);
        $lain = $this->tahun(false);

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.status', $baru), ['aktif' => 1])
            ->assertRedirect(route('admin.tahun-akademik.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tahun_akademik', ['id' => $lama->id, 'aktif' => 0]);
        $this->assertDatabaseHas('tahun_akademik', ['id' => $baru->id, 'aktif' => 1]);
        $this->assertDatabaseHas('tahun_akademik', ['id' => $lain->id, 'aktif' => 0]);
        $this->assertSame(1, TahunAkademik::query()->aktif()->count());

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.status', $lain), ['aktif' => 1])
            ->assertRedirect(route('admin.tahun-akademik.index'));

        $this->assertSame(1, TahunAkademik::query()->aktif()->count());
        $this->assertTrue(TahunAkademik::query()->aktif()->firstOrFail()->is($lain));

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.status', $lain), ['aktif' => 0])
            ->assertRedirect(route('admin.tahun-akademik.index'));

        $this->assertSame(0, TahunAkademik::query()->aktif()->count());
    }

    public function test_status_hanya_dapat_diubah_melalui_endpoint_status(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAkademik = $this->tahun(false);

        $this->actingAs($admin)
            ->put(route('admin.tahun-akademik.update', $tahunAkademik), $this->dataValid(['aktif' => true]))
            ->assertRedirect(route('admin.tahun-akademik.index'));
        $this->assertDatabaseHas('tahun_akademik', ['id' => $tahunAkademik->id, 'aktif' => 0]);

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.status', $tahunAkademik), ['aktif' => 1])
            ->assertRedirect(route('admin.tahun-akademik.index'));
        $this->assertDatabaseHas('tahun_akademik', ['id' => $tahunAkademik->id, 'aktif' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.status', $tahunAkademik), [])
            ->assertSessionHasErrors('aktif');
        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.status', $tahunAkademik), ['aktif' => 'mungkin'])
            ->assertSessionHasErrors('aktif');
    }

    public function test_layanan_mengunci_seluruh_periode_saat_mengaktifkan(): void
    {
        $lama = $this->tahun(true);
        $baru = $this->tahun(false);
        $koneksi = DB::connection();
        $grammar = $koneksi->getQueryGrammar();

        // SQLite tidak menghasilkan klausa FOR UPDATE. Gunakan grammar MySQL
        // dalam mode pretend hanya untuk memeriksa SQL yang dibangun service.
        $koneksi->setQueryGrammar(new MySqlGrammar($koneksi));
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            DB::pretend(function () use ($baru): void {
                app(AdminTahunAkademikService::class)->ubahStatus($baru, true);
            });
        } finally {
            DB::disableQueryLog();
            $koneksi->setQueryGrammar($grammar);
        }

        $sql = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains(strtolower($query), 'for update'))
            ->values();

        $this->assertCount(1, $sql);
        $lockSql = preg_replace('/\s+/', ' ', str_replace(['"', '`'], '', strtolower($sql->first())));
        $this->assertStringContainsString('from tahun_akademik', $lockSql);
        $this->assertStringContainsString('order by id asc', $lockSql);
        $this->assertStringNotContainsString('where id', $lockSql);

        app(AdminTahunAkademikService::class)->ubahStatus($baru, true);
        $this->assertDatabaseHas('tahun_akademik', ['id' => $lama->id, 'aktif' => 0]);
        $this->assertDatabaseHas('tahun_akademik', ['id' => $baru->id, 'aktif' => 1]);
        $this->assertSame(1, TahunAkademik::query()->aktif()->count());
    }

    public function test_kegagalan_di_tengah_transaksi_tidak_menyisakan_perubahan_status(): void
    {
        $lama = $this->tahun(true);
        $baru = $this->tahun(false);
        $gagal = false;

        DB::listen(function ($query) use (&$gagal): void {
            $sql = strtolower($query->sql);

            if (! $gagal && str_contains($sql, 'update') && str_contains($sql, 'tahun_akademik')) {
                $gagal = true;
                throw new RuntimeException('Gagal disengaja untuk menguji rollback.');
            }
        });

        try {
            app(AdminTahunAkademikService::class)->ubahStatus($baru, true);
            $this->fail('Transaksi seharusnya melempar exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Gagal disengaja untuk menguji rollback.', $exception->getMessage());
        }

        $this->assertTrue($gagal);
        $this->assertDatabaseHas('tahun_akademik', ['id' => $lama->id, 'aktif' => 1]);
        $this->assertDatabaseHas('tahun_akademik', ['id' => $baru->id, 'aktif' => 0]);
    }

    // ---------------------------------------------------------------------
    // Integritas data dan pendaftaran mahasiswa
    // ---------------------------------------------------------------------

    public function test_tahun_akademik_tanpa_kelas_dapat_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAkademik = $this->tahun(false);

        $this->actingAs($admin)
            ->delete(route('admin.tahun-akademik.destroy', $tahunAkademik))
            ->assertRedirect(route('admin.tahun-akademik.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('tahun_akademik', ['id' => $tahunAkademik->id]);
    }

    public function test_tahun_akademik_dengan_kelas_tidak_dapat_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAkademik = $this->tahun(false);
        [$kelas] = $this->buatKelas(['tahun_akademik' => $tahunAkademik]);

        $this->actingAs($admin)
            ->delete(route('admin.tahun-akademik.destroy', $tahunAkademik))
            ->assertRedirect(route('admin.tahun-akademik.index'))
            ->assertSessionHas('error', 'Tahun akademik tidak dapat dihapus karena masih memiliki kelas.');

        $this->assertDatabaseHas('tahun_akademik', ['id' => $tahunAkademik->id]);
        $this->assertDatabaseHas('kelas_mata_kuliah', ['id' => $kelas->id]);
    }

    public function test_periode_nonaktif_tidak_dipakai_pendaftaran_baru_dan_riwayat_tetap_ada(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAkademik = $this->tahun(true);
        $mahasiswaLama = User::factory()->mahasiswa()->create();
        $mahasiswaBaru = User::factory()->mahasiswa()->create();
        [$kelas, $kunci] = $this->buatKelas(['tahun_akademik' => $tahunAkademik]);

        $layanan = app(MataKuliahService::class);
        $hasilLama = $layanan->gabung($mahasiswaLama->id, $kunci);
        $this->assertTrue($hasilLama['berhasil']);

        $this->actingAs($admin)
            ->post(route('admin.tahun-akademik.status', $tahunAkademik), ['aktif' => 0])
            ->assertRedirect(route('admin.tahun-akademik.index'));

        $this->assertDatabaseHas('peserta_mata_kuliah', [
            'id' => $hasilLama['peserta']->id,
            'mahasiswa_id' => $mahasiswaLama->id,
            'kelas_mata_kuliah_id' => $kelas->id,
        ]);

        $hasilBaru = $layanan->gabung($mahasiswaBaru->id, $kunci);
        $this->assertFalse($hasilBaru['berhasil']);
        $this->assertSame('Kelas ini tidak tersedia untuk pendaftaran.', $hasilBaru['pesan']);
        $this->assertDatabaseMissing('peserta_mata_kuliah', [
            'mahasiswa_id' => $mahasiswaBaru->id,
            'kelas_mata_kuliah_id' => $kelas->id,
        ]);
        $this->assertCount(0, $layanan->cariKelas([])->items());
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
        $tahunMulai = 2100 + $this->urutan;

        return array_merge([
            'tahun_mulai' => $tahunMulai,
            'tahun_selesai' => $tahunMulai + 1,
            'semester' => 'ganjil',
            'tanggal_mulai' => $tahunMulai.'-09-01',
            'tanggal_selesai' => ($tahunMulai + 1).'-12-31',
        ], $overrides);
    }

    private function tahun(bool $aktif = false): TahunAkademik
    {
        $this->urutan++;
        $tahunMulai = 2030 + $this->urutan;

        $tahunAkademik = new TahunAkademik();
        $tahunAkademik->forceFill([
            'tahun_mulai' => $tahunMulai,
            'tahun_selesai' => $tahunMulai + 1,
            'semester' => 'ganjil',
            'tanggal_mulai' => $tahunMulai.'-09-01',
            'tanggal_selesai' => $tahunMulai.'-12-31',
            'aktif' => $aktif,
        ])->save();

        return $tahunAkademik;
    }

    private function mataKuliah(bool $aktif = true): MataKuliah
    {
        $this->urutan++;
        $nomor = str_pad((string) $this->urutan, 4, '0', STR_PAD_LEFT);

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MK'.$nomor,
            'nama' => 'Mata Kuliah '.$nomor,
            'sks' => 3,
            'program_studi' => 'Informatika',
            'semester' => 1,
            'aktif' => $aktif,
        ])->save();

        return $mataKuliah;
    }

    /**
     * @param  array<string, mixed>  $opsi
     * @return array{0: KelasMataKuliah, 1: ?string}
     */
    private function buatKelas(array $opsi = []): array
    {
        $this->urutan++;
        $nomor = $this->urutan;

        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => ($opsi['mata_kuliah'] ?? $this->mataKuliah())->id,
            'tahun_akademik_id' => ($opsi['tahun_akademik'] ?? $this->tahun())->id,
            'dosen_id' => User::factory()->dosen()->create()->id,
            'kode_kelas' => 'K'.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT),
            'nama_kelas' => 'Kelas '.$nomor,
            'kapasitas' => null,
            'status_pendaftaran' => StatusPendaftaranKelas::Buka,
        ])->save();

        $kunci = ($opsi['dengan_kunci'] ?? true) === true
            ? app(KunciGabungKelasService::class)->terapkan($kelas)
            : null;

        return [$kelas->fresh(), $kunci];
    }
}
