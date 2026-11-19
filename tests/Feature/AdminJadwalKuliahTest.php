<?php

namespace Tests\Feature;

use App\Enums\JenisJadwal;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminJadwalKuliahTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_route_jadwal_kuliah_membutuhkan_admin_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = User::factory()->dosen()->create();
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah(), 'A01');
        $jadwal = $this->jadwal($kelas, 1, '08:00', '09:40');

        $routes = [
            ['get', route('admin.jadwal-kuliah.index'), []],
            ['get', route('admin.jadwal-kuliah.create'), []],
            ['post', route('admin.jadwal-kuliah.store'), $this->payload($kelas)],
            ['get', route('admin.jadwal-kuliah.edit', $jadwal), []],
            ['put', route('admin.jadwal-kuliah.update', $jadwal), $this->payload($kelas)],
            ['delete', route('admin.jadwal-kuliah.destroy', $jadwal), []],
        ];

        foreach ($routes as [$method, $url, $payload]) {
            $this->{$method}($url, $payload)->assertRedirect(route('login'));
        }

        foreach ([User::factory()->mahasiswa()->create(), $dosen] as $user) {
            foreach ($routes as [$method, $url, $payload]) {
                $this->actingAs($user)->{$method}($url, $payload)->assertForbidden();
            }
        }

        $nonaktif = User::factory()->admin()->create(['is_active' => false]);
        $this->actingAs($nonaktif)->get(route('admin.jadwal-kuliah.index'))->assertRedirect(route('login'));

        $this->actingAs($admin)->get(route('admin.jadwal-kuliah.index'))->assertOk();
    }

    public function test_admin_dapat_melihat_halaman_index_create_dan_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = User::factory()->dosen()->create(['name' => 'Dosen Contoh']);
        $kelas = $this->kelas($dosen, $this->tahun(), $this->mataKuliah('MK-PROG', 'Pemrograman Laravel'), 'A01');
        $jadwal = $this->jadwal($kelas, 1, '08:00', '09:40', 'R. 3.12');

        $this->actingAs($admin)
            ->get(route('admin.jadwal-kuliah.index'))
            ->assertOk()
            ->assertViewIs('admin.jadwal-kuliah.index')
            ->assertSee('Jadwal Kuliah')
            ->assertSee('Pemrograman Laravel')
            ->assertSee('Dosen Contoh')
            ->assertSee('Senin')
            ->assertSee('08:00', false)
            ->assertSee('09:40', false)
            ->assertSee('R. 3.12');

        $this->actingAs($admin)
            ->get(route('admin.jadwal-kuliah.create'))
            ->assertOk()
            ->assertViewIs('admin.jadwal-kuliah.create')
            ->assertSee('Tambah Jadwal Kuliah');

        $this->actingAs($admin)
            ->get(route('admin.jadwal-kuliah.edit', $jadwal))
            ->assertOk()
            ->assertViewIs('admin.jadwal-kuliah.edit')
            ->assertSee('Ubah Jadwal Kuliah');
    }

    public function test_empty_state_tampil_ketika_belum_ada_jadwal(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.jadwal-kuliah.index'))
            ->assertOk()
            ->assertSee('Belum ada jadwal kuliah');
    }

    public function test_admin_dapat_menambah_mengubah_dan_menghapus_jadwal(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = $this->kelas(
            User::factory()->dosen()->create(),
            $this->tahun(),
            $this->mataKuliah(),
            'A01',
        );

        $this->actingAs($admin)
            ->post(route('admin.jadwal-kuliah.store'), $this->payload($kelas))
            ->assertRedirect(route('admin.jadwal-kuliah.index'))
            ->assertSessionHas('status', 'Jadwal kuliah berhasil ditambahkan.');

        $jadwal = JadwalKuliah::query()->firstOrFail();
        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $jadwal->id,
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => 1,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:40:00',
            'ruang' => 'R. 3.12',
            'jenis' => JenisJadwal::Kuliah->value,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.jadwal-kuliah.update', $jadwal), $this->payload($kelas, [
                'hari' => 3,
                'jam_mulai' => '13:00',
                'jam_selesai' => '14:30',
                'ruang' => 'R. 4.01',
                'jenis' => JenisJadwal::Praktikum->value,
            ]))
            ->assertRedirect(route('admin.jadwal-kuliah.index'))
            ->assertSessionHas('status', 'Jadwal kuliah berhasil diperbarui.');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $jadwal->id,
            'hari' => 3,
            'jam_mulai' => '13:00:00',
            'jam_selesai' => '14:30:00',
            'ruang' => 'R. 4.01',
            'jenis' => JenisJadwal::Praktikum->value,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.jadwal-kuliah.destroy', $jadwal))
            ->assertRedirect(route('admin.jadwal-kuliah.index'))
            ->assertSessionHas('status', 'Jadwal kuliah berhasil dihapus.');

        $this->assertDatabaseMissing('jadwal_kuliah', ['id' => $jadwal->id]);
    }

    public function test_pencarian_dan_filter_jadwal_kuliah_berfungsi(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAktif = $this->tahun(2026, true);
        $tahunLain = $this->tahun(2025, false, 'genap');
        $dosenSatu = User::factory()->dosen()->create(['name' => 'Dosen Satu']);
        $dosenDua = User::factory()->dosen()->create(['name' => 'Dosen Dua']);

        $kelasSatu = $this->kelas($dosenSatu, $tahunAktif, $this->mataKuliah('MK-PROG', 'Pemrograman Laravel'), 'A01');
        $kelasDua = $this->kelas($dosenDua, $tahunLain, $this->mataKuliah('MK-DB', 'Database'), 'B02');

        $jadwalSatu = $this->jadwal($kelasSatu, 1, '08:00', '09:40');
        $jadwalDua = $this->jadwal($kelasDua, 2, '10:00', '11:40');

        $response = $this->actingAs($admin)->get(route('admin.jadwal-kuliah.index', [
            'q' => 'Pemrograman',
            'tahun_akademik' => $tahunAktif->id,
            'semester' => 'ganjil',
            'hari' => 1,
            'dosen' => $dosenSatu->id,
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('jadwal')->total());
        $this->assertSame($jadwalSatu->id, $response->viewData('jadwal')->first()['id']);
        $response->assertDontSee('Database');

        $response = $this->actingAs($admin)->get(route('admin.jadwal-kuliah.index', [
            'q' => 'Dosen Dua',
        ]));
        $this->assertSame(1, $response->viewData('jadwal')->total());
        $this->assertSame($jadwalDua->id, $response->viewData('jadwal')->first()['id']);

        $response = $this->actingAs($admin)->get(route('admin.jadwal-kuliah.index', [
            'jenis' => 'kuliah',
        ]));
        $this->assertSame(2, $response->viewData('jadwal')->total());
    }

    public function test_paginasi_jadwal_kuliah_berfungsi(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = $this->kelas(
            User::factory()->dosen()->create(),
            $this->tahun(),
            $this->mataKuliah(),
            'A01',
        );

        for ($i = 0; $i < 16; $i++) {
            $jam = sprintf('%02d:00', 7 + intdiv($i, 7));
            $this->jadwal($kelas, $i % 7, $jam, sprintf('%02d:30', 7 + intdiv($i, 7)));
        }

        $response = $this->actingAs($admin)->get(route('admin.jadwal-kuliah.index'));

        $response->assertOk();
        $this->assertSame(16, $response->viewData('jadwal')->total());
        $this->assertSame(15, $response->viewData('jadwal')->perPage());
        $response->assertSee('Menampilkan 1–15 dari 16 jadwal');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('formulirTidakValid')]
    public function test_validasi_formulir_jadwal_kuliah(array $overrides, string $field): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = $this->kelas(
            User::factory()->dosen()->create(),
            $this->tahun(),
            $this->mataKuliah(),
            'A01',
        );

        $this->actingAs($admin)
            ->post(route('admin.jadwal-kuliah.store'), $this->payload($kelas, $overrides))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('jadwal_kuliah', 0);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function formulirTidakValid(): array
    {
        return [
            'jam selesai sama dengan mulai' => [['jam_selesai' => '08:00'], 'jam_selesai'],
            'jam selesai sebelum mulai' => [['jam_mulai' => '10:00', 'jam_selesai' => '09:00'], 'jam_selesai'],
            'tengah malam mulai' => [['jam_mulai' => '00:00', 'jam_selesai' => '01:00'], 'jam_mulai'],
            'tengah malam selesai' => [['jam_mulai' => '23:00', 'jam_selesai' => '00:00'], 'jam_selesai'],
            'hari di luar rentang' => [['hari' => 7], 'hari'],
            'jenis tidak dikenal' => [['jenis' => 'uts'], 'jenis'],
            'kelas tidak ada' => [['kelas_mata_kuliah_id' => 999999], 'kelas_mata_kuliah_id'],
            'format jam salah' => [['jam_mulai' => '8:00'], 'jam_mulai'],
        ];
    }

    public function test_bentrok_kelas_dosen_dan_ruangan_ditolak(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();
        $dosenSatu = User::factory()->dosen()->create();
        $dosenDua = User::factory()->dosen()->create();
        $dosenTiga = User::factory()->dosen()->create();
        $kelasSatu = $this->kelas($dosenSatu, $tahun, $this->mataKuliah(), 'A01');
        $kelasDua = $this->kelas($dosenDua, $tahun, $this->mataKuliah(), 'B02');
        $kelasTiga = $this->kelas($dosenSatu, $tahun, $this->mataKuliah(), 'C03');
        $kelasEmpat = $this->kelas($dosenTiga, $tahun, $this->mataKuliah(), 'D04');

        $ada = $this->jadwal($kelasSatu, 1, '08:00', '10:00', 'R. satuan');
        $this->jadwal($kelasDua, 1, '08:00', '10:00', 'R. lain');

        // Kelas yang sama bentrok.
        $this->actingAs($admin)
            ->post(route('admin.jadwal-kuliah.store'), $this->payload($kelasSatu, [
                'jam_mulai' => '09:00',
                'jam_selesai' => '10:30',
                'ruang' => 'R. baru',
            ]))
            ->assertSessionHasErrors('jam_mulai');

        // Dosen pengampu yang sama bentrok walaupun kelasnya berbeda.
        $this->actingAs($admin)
            ->post(route('admin.jadwal-kuliah.store'), $this->payload($kelasTiga, [
                'jam_mulai' => '09:30',
                'jam_selesai' => '10:30',
                'ruang' => 'R. baru',
            ]))
            ->assertSessionHasErrors('jam_mulai');

        // Ruangan yang sama bentrok walaupun dosennya berbeda.
        $this->actingAs($admin)
            ->post(route('admin.jadwal-kuliah.store'), $this->payload($kelasEmpat, [
                'jam_mulai' => '09:30',
                'jam_selesai' => '10:30',
                'ruang' => 'R. satuan',
            ]))
            ->assertSessionHasErrors('ruang');

        // Hari berbeda tidak bentrok.
        $this->actingAs($admin)
            ->post(route('admin.jadwal-kuliah.store'), $this->payload($kelasSatu, [
                'hari' => 2,
                'jam_mulai' => '08:00',
                'jam_selesai' => '10:00',
                'ruang' => 'R. satuan',
            ]))
            ->assertSessionDoesntHaveErrors();

        // Mengubah jadwal tanpa mengubah jadwal itu sendiri tidak dianggap bentrok.
        $this->actingAs($admin)
            ->put(route('admin.jadwal-kuliah.update', $ada), $this->payload($kelasSatu, [
                'jam_mulai' => '08:00',
                'jam_selesai' => '10:00',
                'ruang' => 'R. satuan',
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(3, JadwalKuliah::query()->count());
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(KelasMataKuliah $kelas, array $overrides = []): array
    {
        return array_merge([
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => 1,
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
            'ruang' => 'R. 3.12',
            'jenis' => JenisJadwal::Kuliah->value,
        ], $overrides);
    }

    private function tahun(int $mulai = 2026, bool $aktif = true, string $semester = 'ganjil'): TahunAkademik
    {
        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => $mulai,
            'tahun_selesai' => $mulai + 1,
            'semester' => $semester,
            'tanggal_mulai' => $mulai.'-09-01',
            'tanggal_selesai' => $mulai.'-12-31',
            'aktif' => $aktif,
        ])->save();

        return $tahun;
    }

    private function mataKuliah(?string $kode = null, string $nama = 'Mata Kuliah Uji'): MataKuliah
    {
        $this->urutan++;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => $kode ?? 'MK-'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama' => $nama === 'Mata Kuliah Uji' ? $nama.' '.$this->urutan : $nama,
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
            'kapasitas' => 30,
            'status_pendaftaran' => 'tutup',
        ])->save();

        return $kelas;
    }

    private function jadwal(
        KelasMataKuliah $kelas,
        int $hari,
        string $jamMulai,
        string $jamSelesai,
        ?string $ruang = 'R. 3.12',
    ): JadwalKuliah {
        $jadwal = new JadwalKuliah();
        $jadwal->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => $hari,
            'jam_mulai' => strlen($jamMulai) === 5 ? $jamMulai.':00' : $jamMulai,
            'jam_selesai' => strlen($jamSelesai) === 5 ? $jamSelesai.':00' : $jamSelesai,
            'ruang' => $ruang,
            'jenis' => JenisJadwal::Kuliah->value,
        ])->save();

        return $jadwal;
    }
}
