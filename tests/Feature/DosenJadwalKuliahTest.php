<?php

namespace Tests\Feature;

use App\Enums\JenisJadwal;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Dosen\JadwalKuliahService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenJadwalKuliahTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_tidak_dapat_mengakses_route_jadwal_dosen(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $jadwal = $this->jadwal($kelas);

        $routes = [
            ['get', route('dosen.mata-kuliah.jadwal.index', $kelas)],
            ['get', route('dosen.mata-kuliah.jadwal.create', $kelas)],
            ['post', route('dosen.mata-kuliah.jadwal.store', $kelas)],
            ['get', route('dosen.mata-kuliah.jadwal.edit', [$kelas, $jadwal])],
            ['put', route('dosen.mata-kuliah.jadwal.update', [$kelas, $jadwal])],
            ['delete', route('dosen.mata-kuliah.jadwal.destroy', [$kelas, $jadwal])],
        ];

        foreach ($routes as [$method, $url]) {
            $this->{$method}($url)->assertRedirect(route('login'));
        }
    }

    public function test_mahasiswa_dan_admin_tidak_dapat_mengakses_route_jadwal_dosen(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $jadwal = $this->jadwal($kelas);

        $routes = [
            ['get', route('dosen.mata-kuliah.jadwal.index', $kelas), []],
            ['get', route('dosen.mata-kuliah.jadwal.create', $kelas), []],
            ['post', route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload()],
            ['get', route('dosen.mata-kuliah.jadwal.edit', [$kelas, $jadwal]), []],
            ['put', route('dosen.mata-kuliah.jadwal.update', [$kelas, $jadwal]), $this->payload()],
            ['delete', route('dosen.mata-kuliah.jadwal.destroy', [$kelas, $jadwal]), []],
        ];

        foreach ([User::factory()->mahasiswa()->create(), User::factory()->admin()->create()] as $user) {
            foreach ($routes as [$method, $url, $payload]) {
                $this->actingAs($user)->{$method}($url, $payload)->assertForbidden();
            }
        }

        $this->assertDatabaseHas('jadwal_kuliah', ['id' => $jadwal->id]);
    }

    public function test_dosen_dapat_mengakses_halaman_jadwal_kelasnya_sendiri(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $jadwal = $this->jadwal($kelas);

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.jadwal.index', $kelas))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.jadwal.index')
            ->assertSee('Senin')
            ->assertSee('08:00–09:40');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.jadwal.create', $kelas))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.jadwal.create');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.jadwal.edit', [$kelas, $jadwal]))
            ->assertOk()
            ->assertViewIs('dosen.mata-kuliah.jadwal.edit');
    }

    public function test_empty_state_tampil_ketika_kelas_belum_memiliki_jadwal(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)
            ->get(route('dosen.mata-kuliah.jadwal.index', $kelas))
            ->assertOk()
            ->assertSee('Belum ada jadwal');
    }

    public function test_kelas_dosen_lain_menghasilkan_404_pada_semua_aksi_jadwal(): void
    {
        $dosen = $this->dosen();
        $dosenLain = $this->dosen();
        $kelasLain = $this->kelas($dosenLain, $this->tahun(), $this->mataKuliah(), 'B02');
        $jadwalLain = $this->jadwal($kelasLain);

        $routes = [
            ['get', route('dosen.mata-kuliah.jadwal.index', $kelasLain), []],
            ['get', route('dosen.mata-kuliah.jadwal.create', $kelasLain), []],
            ['post', route('dosen.mata-kuliah.jadwal.store', $kelasLain), $this->payload()],
            ['get', route('dosen.mata-kuliah.jadwal.edit', [$kelasLain, $jadwalLain]), []],
            ['put', route('dosen.mata-kuliah.jadwal.update', [$kelasLain, $jadwalLain]), $this->payload()],
            ['delete', route('dosen.mata-kuliah.jadwal.destroy', [$kelasLain, $jadwalLain]), []],
        ];

        foreach ($routes as [$method, $url, $payload]) {
            $this->actingAs($dosen)->{$method}($url, $payload)->assertNotFound();
        }

        $this->assertDatabaseHas('jadwal_kuliah', ['id' => $jadwalLain->id]);
        $this->assertSame(1, JadwalKuliah::query()->count());
    }

    public function test_jadwal_dari_kelas_lain_menghasilkan_404_walaupun_kelas_url_milik_dosen(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mataKuliah = $this->mataKuliah();
        $kelasA = $this->kelas($dosen, $tahun, $mataKuliah, 'A01');
        $kelasB = $this->kelas($dosen, $tahun, $mataKuliah, 'A02');
        $jadwalA = $this->jadwal($kelasA);

        foreach ([
            ['get', route('dosen.mata-kuliah.jadwal.edit', [$kelasB, $jadwalA]), []],
            ['put', route('dosen.mata-kuliah.jadwal.update', [$kelasB, $jadwalA]), $this->payload()],
            ['delete', route('dosen.mata-kuliah.jadwal.destroy', [$kelasB, $jadwalA]), []],
        ] as [$method, $url, $payload]) {
            $this->actingAs($dosen)->{$method}($url, $payload)->assertNotFound();
        }

        $this->assertDatabaseHas('jadwal_kuliah', ['id' => $jadwalA->id]);
    }

    public function test_store_menambahkan_jadwal_dengan_field_dari_server(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mataKuliah = $this->mataKuliah();
        $kelas = $this->kelas($dosen, $tahun, $mataKuliah, 'A01');
        $kelasLain = $this->kelas($dosen, $tahun, $mataKuliah, 'A02');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload([
                'kelas_mata_kuliah_id' => $kelasLain->id,
                'dosen_id' => 9999,
                'id' => 9999,
            ]))
            ->assertRedirect(route('dosen.mata-kuliah.jadwal.index', $kelas))
            ->assertSessionHas('status', 'Jadwal berhasil ditambahkan.');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => 1,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:40:00',
            'ruang' => 'R. 3.12',
            'jenis' => JenisJadwal::Kuliah->value,
        ]);

        $this->assertDatabaseMissing('jadwal_kuliah', [
            'kelas_mata_kuliah_id' => $kelasLain->id,
            'jam_mulai' => '08:00:00',
        ]);

        $this->assertSame(1, JadwalKuliah::query()->count());
    }

    public function test_ruang_kosong_diterima_dan_disimpan_null(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload(['ruang' => '   ']))
            ->assertRedirect(route('dosen.mata-kuliah.jadwal.index', $kelas))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'kelas_mata_kuliah_id' => $kelas->id,
            'ruang' => null,
        ]);
    }

    public function test_update_mengubah_jadwal_tanpa_memindahkan_kelas(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mataKuliah = $this->mataKuliah();
        $kelas = $this->kelas($dosen, $tahun, $mataKuliah, 'A01');
        $kelasLain = $this->kelas($dosen, $tahun, $mataKuliah, 'A02');
        $jadwal = $this->jadwal($kelas);

        $this->actingAs($dosen)
            ->put(route('dosen.mata-kuliah.jadwal.update', [$kelas, $jadwal]), $this->payload([
                'hari' => 3,
                'jam_mulai' => '13:00',
                'jam_selesai' => '14:30',
                'ruang' => 'Lab. 2',
                'jenis' => JenisJadwal::Praktikum->value,
                'kelas_mata_kuliah_id' => $kelasLain->id,
                'dosen_id' => 9999,
            ]))
            ->assertRedirect(route('dosen.mata-kuliah.jadwal.index', $kelas))
            ->assertSessionHas('status', 'Jadwal berhasil diperbarui.');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $jadwal->id,
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => 3,
            'jam_mulai' => '13:00:00',
            'jam_selesai' => '14:30:00',
            'ruang' => 'Lab. 2',
            'jenis' => JenisJadwal::Praktikum->value,
        ]);

        $this->assertDatabaseMissing('jadwal_kuliah', [
            'kelas_mata_kuliah_id' => $kelasLain->id,
            'jam_mulai' => '13:00:00',
        ]);
    }

    public function test_destroy_menghapus_jadwal_dan_menampilkan_pesan(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $jadwal = $this->jadwal($kelas);

        $this->actingAs($dosen)
            ->delete(route('dosen.mata-kuliah.jadwal.destroy', [$kelas, $jadwal]))
            ->assertRedirect(route('dosen.mata-kuliah.jadwal.index', $kelas))
            ->assertSessionHas('status', 'Jadwal berhasil dihapus.');

        $this->assertDatabaseMissing('jadwal_kuliah', ['id' => $jadwal->id]);
    }

    public function test_hari_di_luar_rentang_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        foreach ([7, -1, '7', '-1'] as $hari) {
            $this->actingAs($dosen)
                ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload(['hari' => $hari]))
                ->assertSessionHasErrors('hari');
        }

        $this->assertSame(0, JadwalKuliah::query()->count());
    }

    public function test_jam_selesai_harus_lebih_besar_dari_jam_mulai(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        foreach (['08:00', '07:30'] as $selesai) {
            $this->actingAs($dosen)
                ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload([
                    'jam_mulai' => '08:00',
                    'jam_selesai' => $selesai,
                ]))
                ->assertSessionHasErrors('jam_selesai');
        }

        $this->assertSame(0, JadwalKuliah::query()->count());
    }

    public function test_jenis_jadwal_tidak_valid_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload(['jenis' => 'uts']))
            ->assertSessionHasErrors('jenis');

        $this->assertSame(0, JadwalKuliah::query()->count());
    }

    public function test_duplikasi_hari_dan_jam_mulai_ditolak(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();
        $mataKuliah = $this->mataKuliah();
        $kelas = $this->kelas($dosen, $tahun, $mataKuliah, 'A01');
        $kelasLain = $this->kelas($dosen, $tahun, $mataKuliah, 'A02');
        $this->jadwal($kelas, 1, '08:00', '09:40');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload([
                'jam_mulai' => '08:00',
                'jam_selesai' => '10:00',
            ]))
            ->assertSessionHasErrors('jam_mulai');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelasLain), $this->payload([
                'jam_mulai' => '08:00',
            ]))
            ->assertSessionHas('status');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload([
                'hari' => 2,
                'jam_mulai' => '08:00',
            ]))
            ->assertSessionHas('status');

        $this->assertSame(3, JadwalKuliah::query()->count());
    }

    public function test_duplikasi_juga_ditolak_saat_update_jadwal_lain(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $pertama = $this->jadwal($kelas, 1, '08:00', '09:40');
        $kedua = $this->jadwal($kelas, 2, '10:00', '11:40');

        $this->actingAs($dosen)
            ->put(route('dosen.mata-kuliah.jadwal.update', [$kelas, $kedua]), $this->payload([
                'jam_mulai' => '08:00',
            ]))
            ->assertSessionHasErrors('jam_mulai');

        $this->actingAs($dosen)
            ->put(route('dosen.mata-kuliah.jadwal.update', [$kelas, $pertama]), $this->payload())
            ->assertSessionHas('status', 'Jadwal berhasil diperbarui.');

        $this->assertDatabaseHas('jadwal_kuliah', ['id' => $kedua->id, 'jam_mulai' => '10:00:00']);
    }

    public function test_hari_valid_0_dan_6_diterima(): void
    {
        $dosen = $this->dosen();
        $tahun = $this->tahun();

        foreach ([0, 6, '0', '6'] as $indeks => $hari) {
            $kelas = $this->kelas($dosen, $tahun, $this->mataKuliah(), 'H'.($indeks + 1));

            $this->actingAs($dosen)
                ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload(['hari' => $hari]))
                ->assertRedirect(route('dosen.mata-kuliah.jadwal.index', $kelas))
                ->assertSessionHas('status', 'Jadwal berhasil ditambahkan.');

            $this->assertDatabaseHas('jadwal_kuliah', [
                'kelas_mata_kuliah_id' => $kelas->id,
                'hari' => (int) $hari,
            ]);
        }
    }

    public function test_hari_desimal_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        foreach ([1.5, 2.7, '1.5', '2.7'] as $hari) {
            $this->actingAs($dosen)
                ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload(['hari' => $hari]))
                ->assertSessionHasErrors('hari');
        }

        $this->assertSame(0, JadwalKuliah::query()->count());
    }

    public function test_hari_kosong_dan_tidak_valid_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        foreach (['', 'abc', null] as $hari) {
            $this->actingAs($dosen)
                ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload(['hari' => $hari]))
                ->assertSessionHasErrors('hari');
        }

        $this->assertSame(0, JadwalKuliah::query()->count());
    }

    public function test_format_jam_tidak_valid_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        foreach (['8:00', '08:00:00', 'abc', ''] as $jamMulai) {
            $this->actingAs($dosen)
                ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload(['jam_mulai' => $jamMulai]))
                ->assertSessionHasErrors('jam_mulai');
        }

        $this->assertSame(0, JadwalKuliah::query()->count());
    }

    public function test_ruang_51_karakter_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload([
                'ruang' => str_repeat('a', 51),
            ]))
            ->assertSessionHasErrors('ruang');

        $this->assertSame(0, JadwalKuliah::query()->count());
    }

    public function test_jenis_lainnya_diterima(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload([
                'jenis' => JenisJadwal::Lainnya->value,
            ]))
            ->assertRedirect(route('dosen.mata-kuliah.jadwal.index', $kelas))
            ->assertSessionHas('status', 'Jadwal berhasil ditambahkan.');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'kelas_mata_kuliah_id' => $kelas->id,
            'jenis' => JenisJadwal::Lainnya->value,
        ]);
    }

    public function test_update_tidak_menganggap_jadwal_sendiri_sebagai_duplikat(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $jadwal = $this->jadwal($kelas, 0, '08:00', '09:40');

        $this->actingAs($dosen)
            ->put(route('dosen.mata-kuliah.jadwal.update', [$kelas, $jadwal]), $this->payload([
                'hari' => 0,
            ]))
            ->assertRedirect(route('dosen.mata-kuliah.jadwal.index', $kelas))
            ->assertSessionHas('status', 'Jadwal berhasil diperbarui.');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $jadwal->id,
            'hari' => 0,
            'jam_mulai' => '08:00:00',
        ]);
        $this->assertSame(1, JadwalKuliah::query()->count());
    }

    public function test_duplikat_dengan_representasi_jam_tanpa_detik_tetap_ditolak(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $jadwal = new JadwalKuliah();
        $jadwal->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => 1,
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
            'ruang' => 'R. 3.12',
            'jenis' => JenisJadwal::Kuliah->value,
        ])->save();

        $this->actingAs($dosen)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), $this->payload())
            ->assertSessionHasErrors('jam_mulai');

        $this->assertSame(1, JadwalKuliah::query()->count());
    }

    public function test_old_input_tetap_dipertahankan_saat_validasi_gagal(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $url = route('dosen.mata-kuliah.jadwal.create', $kelas);

        $this->actingAs($dosen)
            ->from($url)
            ->post(route('dosen.mata-kuliah.jadwal.store', $kelas), [
                'hari' => '1.5',
                'jam_mulai' => '10:15',
                'jam_selesai' => '11:30',
                'ruang' => 'Ruang Uji',
                'jenis' => JenisJadwal::Praktikum->value,
            ])
            ->assertSessionHasErrors('hari')
            ->assertSessionHasInput('hari', '1.5')
            ->assertSessionHasInput('jam_mulai', '10:15')
            ->assertSessionHasInput('jam_selesai', '11:30')
            ->assertSessionHasInput('ruang', 'Ruang Uji')
            ->assertSessionHasInput('jenis', JenisJadwal::Praktikum->value);

        $this->actingAs($dosen)
            ->get($url)
            ->assertOk()
            ->assertSee('10:15')
            ->assertSee('11:30')
            ->assertSee('Ruang Uji');
    }

    public function test_service_menerjemahkan_unique_constraint_jadwal_sebagai_duplikat(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $this->jadwal($kelas, 1, '08:00', '09:40');

        $hasil = $this->app->make(JadwalKuliahService::class)
            ->simpan($dosen->id, $kelas->id, $this->payload());

        $this->assertFalse($hasil['berhasil']);
        $this->assertSame('jam_mulai', $hasil['field']);
        $this->assertSame('Kombinasi hari dan jam mulai sudah digunakan pada kelas ini.', $hasil['pesan']);
        $this->assertStringNotContainsString('UNIQUE', $hasil['pesan']);
        $this->assertStringNotContainsString('SQL', $hasil['pesan']);
        $this->assertSame(1, JadwalKuliah::query()->count());
    }

    public function test_query_exception_non_duplikat_tidak_diubah_menjadi_duplikat(): void
    {
        $service = $this->app->make(JadwalKuliahService::class);
        $metode = new \ReflectionMethod($service, 'adalahDuplikatJadwal');
        $metode->setAccessible(true);

        $duplikat = new QueryException(
            'sqlite',
            'UNIQUE constraint failed: jadwal_kuliah.kelas_mata_kuliah_id, jadwal_kuliah.hari, jadwal_kuliah.jam_mulai',
            [],
            new \PDOException('duplicate', 23000),
        );
        $foreignKey = new QueryException(
            'sqlite',
            'FOREIGN KEY constraint failed',
            [],
            new \PDOException('foreign key', 23000),
        );
        $uniqueLain = new QueryException(
            'sqlite',
            'UNIQUE constraint failed: kelas_mata_kuliah.kode_gabung_hash',
            [],
            new \PDOException('unique', 23000),
        );

        $this->assertTrue($metode->invoke($service, $duplikat));
        $this->assertFalse($metode->invoke($service, $foreignKey));
        $this->assertFalse($metode->invoke($service, $uniqueLain));
    }

    public function test_regresi_halaman_dosen_mahasiswa_dan_admin(): void
    {
        $dosen = $this->dosen();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');

        $this->actingAs($dosen)->get(route('dosen.dashboard'))->assertOk();
        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.index'))->assertOk();
        $this->actingAs($dosen)->get(route('dosen.mata-kuliah.show', $kelas))->assertOk();
        $this->actingAs($dosen)->post(route('dosen.mata-kuliah.kunci', $kelas))->assertSessionHas('kode_gabung');

        $this->actingAs(User::factory()->mahasiswa()->create())
            ->get(route('mahasiswa.dashboard'))
            ->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'hari' => 1,
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
            'ruang' => 'R. 3.12',
            'jenis' => JenisJadwal::Kuliah->value,
        ], $overrides);
    }

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

    private function mataKuliah(?string $kode = null): MataKuliah
    {
        $this->urutan++;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => $kode ?? 'MK-'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Uji '.$this->urutan,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        return $mataKuliah;
    }

    private function kelas(
        User $dosen,
        TahunAkademik $tahun,
        MataKuliah $mataKuliah,
        string $kode,
    ): KelasMataKuliah {
        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kode,
            'nama_kelas' => null,
            'kapasitas' => 30,
        ])->save();

        return $kelas;
    }

    private function jadwal(
        KelasMataKuliah $kelas,
        int $hari = 1,
        string $jamMulai = '08:00',
        string $jamSelesai = '09:40',
        ?string $ruang = 'R. 3.12',
        string $jenis = 'kuliah',
    ): JadwalKuliah {
        $jadwal = new JadwalKuliah();
        $jadwal->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => $hari,
            'jam_mulai' => strlen($jamMulai) === 5 ? $jamMulai.':00' : $jamMulai,
            'jam_selesai' => strlen($jamSelesai) === 5 ? $jamSelesai.':00' : $jamSelesai,
            'ruang' => $ruang,
            'jenis' => $jenis,
        ])->save();

        return $jadwal;
    }
}
