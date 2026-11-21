<?php

namespace Tests\Feature;

use App\Enums\JenisAgenda;
use App\Enums\JenisUjian;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminJadwalUjianTest extends TestCase
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

    public function test_route_jadwal_ujian_membutuhkan_admin_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();
        $ujian = $this->ujian($admin, $tahun, JenisUjian::Uts, '2026-12-01');

        $routes = [
            ['get', route('admin.jadwal-ujian.index'), []],
            ['get', route('admin.jadwal-ujian.create'), []],
            ['post', route('admin.jadwal-ujian.store'), $this->payload($tahun)],
            ['get', route('admin.jadwal-ujian.edit', $ujian), []],
            ['put', route('admin.jadwal-ujian.update', $ujian), $this->payload($tahun)],
            ['delete', route('admin.jadwal-ujian.destroy', $ujian), []],
        ];

        foreach ($routes as [$method, $url, $payload]) {
            $this->{$method}($url, $payload)->assertRedirect(route('login'));
        }

        foreach ([User::factory()->mahasiswa()->create(), User::factory()->dosen()->create()] as $user) {
            foreach ($routes as [$method, $url, $payload]) {
                $this->actingAs($user)->{$method}($url, $payload)->assertForbidden();
            }
        }

        $nonaktif = User::factory()->admin()->create(['is_active' => false]);
        $this->actingAs($nonaktif)->get(route('admin.jadwal-ujian.index'))->assertRedirect(route('login'));

        $this->actingAs($admin)->get(route('admin.jadwal-ujian.index'))->assertOk();
    }

    public function test_admin_dapat_melihat_halaman_index_create_dan_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();
        $ujian = $this->ujian($admin, $tahun, JenisUjian::Uts, '2026-12-01');

        $this->actingAs($admin)
            ->get(route('admin.jadwal-ujian.index'))
            ->assertOk()
            ->assertViewIs('admin.jadwal-ujian.index')
            ->assertSee('Jadwal Ujian UTS & UAS')
            ->assertSee('Agenda institusional')
            ->assertSee($this->judulOtomatis(JenisUjian::Uts, $tahun))
            ->assertSee('Ganjil 2026/2027')
            ->assertSee('01 Des 2026');

        $this->actingAs($admin)
            ->get(route('admin.jadwal-ujian.create'))
            ->assertOk()
            ->assertViewIs('admin.jadwal-ujian.create')
            ->assertSee('Tambah Jadwal Ujian')
            ->assertDontSee('Kelas mata kuliah');

        $this->actingAs($admin)
            ->get(route('admin.jadwal-ujian.edit', $ujian))
            ->assertOk()
            ->assertViewIs('admin.jadwal-ujian.edit')
            ->assertSee('Ubah Jadwal Ujian')
            ->assertSee($this->judulOtomatis(JenisUjian::Uts, $tahun));
    }

    public function test_empty_state_tampil_ketika_belum_ada_jadwal_ujian(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.jadwal-ujian.index'))
            ->assertOk()
            ->assertSee('Belum ada jadwal ujian');
    }

    public function test_admin_dapat_menambah_uts_dan_uas_institusional_tanpa_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();

        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun))
            ->assertRedirect(route('admin.jadwal-ujian.index'))
            ->assertSessionHas('status', 'Jadwal uts berhasil ditambahkan.');

        $uts = AgendaAkademik::query()->where('jenis_ujian', JenisUjian::Uts->value)->firstOrFail();
        $this->assertDatabaseHas('agenda_akademik', [
            'id' => $uts->id,
            'dibuat_oleh' => $admin->id,
            'kelas_mata_kuliah_id' => null,
            'judul' => 'Jadwal Ujian Tengah Semester (UTS) — Ganjil 2026/2027',
            'jenis' => JenisAgenda::Ujian->value,
            'jenis_ujian' => JenisUjian::Uts->value,
            'tanggal_selesai' => '2026-12-01 00:00:00',
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'lokasi' => 'Aula Utama',
        ]);
        $this->assertSame('2026-12-01', $uts->tanggal_mulai?->toDateString());
        $this->assertNotNull($uts->dipublikasikan_pada);

        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, [
                'jenis_ujian' => JenisUjian::Uas->value,
                'judul' => $this->judulOtomatis(JenisUjian::Uas, $tahun),
                'tanggal_mulai' => '2026-12-15',
                'tanggal_selesai' => '2026-12-16',
                'waktu_mulai' => '13:00',
                'waktu_selesai' => '15:00',
                'lokasi' => null,
            ]))
            ->assertRedirect(route('admin.jadwal-ujian.index'))
            ->assertSessionHas('status', 'Jadwal uas berhasil ditambahkan.');

        $uas = AgendaAkademik::query()->where('jenis_ujian', JenisUjian::Uas->value)->firstOrFail();
        $this->assertDatabaseHas('agenda_akademik', [
            'id' => $uas->id,
            'dibuat_oleh' => $admin->id,
            'kelas_mata_kuliah_id' => null,
            'judul' => 'Jadwal Ujian Akhir Semester (UAS) — Ganjil 2026/2027',
            'jenis_ujian' => JenisUjian::Uas->value,
            'tanggal_selesai' => '2026-12-16 00:00:00',
            'waktu_mulai' => '13:00:00',
            'waktu_selesai' => '15:00:00',
            'lokasi' => null,
        ]);
        $this->assertSame(2, AgendaAkademik::query()->count());
    }

    public function test_kelas_dari_request_tidak_dapat_mengubah_cakupan_agenda(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();
        $kelas = $this->kelas(User::factory()->dosen()->create(), $tahun, $this->mataKuliah(), 'A01');

        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, [
                'kelas_mata_kuliah_id' => $kelas->id,
            ]))
            ->assertRedirect(route('admin.jadwal-ujian.index'))
            ->assertSessionDoesntHaveErrors();

        $agenda = AgendaAkademik::query()->firstOrFail();
        $this->assertNull($agenda->kelas_mata_kuliah_id);
        $this->assertDatabaseHas('agenda_akademik', [
            'id' => $agenda->id,
            'kelas_mata_kuliah_id' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.jadwal-ujian.update', $agenda), $this->payload($tahun, [
                'kelas_mata_kuliah_id' => $kelas->id,
            ]))
            ->assertRedirect(route('admin.jadwal-ujian.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull($agenda->fresh()?->kelas_mata_kuliah_id);
        $this->assertSame(1, AgendaAkademik::query()->count());
    }

    public function test_admin_dapat_mengubah_dan_menghapus_agenda_institusional(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();
        $ujian = $this->ujian($admin, $tahun, JenisUjian::Uts, '2026-12-01');

        $this->actingAs($admin)
            ->put(route('admin.jadwal-ujian.update', $ujian), $this->payload($tahun, [
                'jenis_ujian' => JenisUjian::Uas->value,
                'judul' => $this->judulOtomatis(JenisUjian::Uas, $tahun),
                'tanggal_mulai' => '2026-12-15',
                'tanggal_selesai' => '2026-12-16',
                'waktu_mulai' => null,
                'waktu_selesai' => null,
                'lokasi' => 'R. 4.01',
                'deskripsi' => 'Informasi ujian akhir semester.',
                'dipublikasikan' => 0,
            ]))
            ->assertRedirect(route('admin.jadwal-ujian.index'))
            ->assertSessionHas('status', 'Jadwal uas berhasil diperbarui.');

        $this->assertDatabaseHas('agenda_akademik', [
            'id' => $ujian->id,
            'kelas_mata_kuliah_id' => null,
            'judul' => 'Jadwal Ujian Akhir Semester (UAS) — Ganjil 2026/2027',
            'jenis_ujian' => JenisUjian::Uas->value,
            'tanggal_mulai' => '2026-12-15 00:00:00',
            'tanggal_selesai' => '2026-12-16 00:00:00',
            'waktu_mulai' => null,
            'waktu_selesai' => null,
            'lokasi' => 'R. 4.01',
            'dipublikasikan_pada' => null,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.jadwal-ujian.destroy', $ujian))
            ->assertRedirect(route('admin.jadwal-ujian.index'))
            ->assertSessionHas('status', 'Jadwal ujian berhasil dihapus.');

        $this->assertDatabaseMissing('agenda_akademik', ['id' => $ujian->id]);
    }

    public function test_pencarian_dan_filter_jadwal_ujian_berfungsi(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAktif = $this->tahun(2026, true, 'ganjil');
        $tahunLain = $this->tahun(2025, false, 'genap');

        $uts = $this->ujian($admin, $tahunAktif, JenisUjian::Uts, '2026-12-01');
        $uas = $this->ujian($admin, $tahunLain, JenisUjian::Uas, '2026-12-05');

        $response = $this->actingAs($admin)->get(route('admin.jadwal-ujian.index', [
            'q' => 'tengah semester',
            'jenis_ujian' => JenisUjian::Uts->value,
            'tahun_akademik' => $tahunAktif->id,
            'semester' => 'ganjil',
            'tanggal' => '2026-12-01',
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('ujian')->total());
        $this->assertSame($uts->id, $response->viewData('ujian')->first()['id']);
        $response->assertDontSee('Jadwal Ujian Akhir Semester (UAS)');

        $response = $this->actingAs($admin)->get(route('admin.jadwal-ujian.index', [
            'jenis_ujian' => JenisUjian::Uas->value,
        ]));
        $this->assertSame(1, $response->viewData('ujian')->total());
        $this->assertSame($uas->id, $response->viewData('ujian')->first()['id']);

        $response = $this->actingAs($admin)->get(route('admin.jadwal-ujian.index', [
            'tahun_akademik' => $tahunLain->id,
            'semester' => 'genap',
        ]));
        $this->assertSame(1, $response->viewData('ujian')->total());
        $this->assertSame($uas->id, $response->viewData('ujian')->first()['id']);

        $response = $this->actingAs($admin)->get(route('admin.jadwal-ujian.index', [
            'tanggal' => '2026-12-06',
        ]));
        $this->assertSame(0, $response->viewData('ujian')->total());
    }

    public function test_paginasi_jadwal_ujian_berfungsi(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();

        for ($i = 1; $i <= 16; $i++) {
            $this->ujian($admin, $tahun, JenisUjian::Uts, sprintf('2026-12-%02d', $i));
        }

        $response = $this->actingAs($admin)->get(route('admin.jadwal-ujian.index'));

        $response->assertOk();
        $this->assertSame(16, $response->viewData('ujian')->total());
        $this->assertSame(15, $response->viewData('ujian')->perPage());
        $response->assertSee('Menampilkan 1–15 dari 16 jadwal ujian');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('formulirTidakValid')]
    public function test_validasi_formulir_jadwal_ujian(array $overrides, string $field): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();

        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, $overrides))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('agenda_akademik', 0);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function formulirTidakValid(): array
    {
        return [
            'jenis ujian tidak dikenal' => [['jenis_ujian' => 'ujian'], 'jenis_ujian'],
            'judul tidak sesuai jenis ujian' => [['judul' => 'Jadwal Ujian Akhir Semester (UAS) — Ganjil 2026/2027'], 'judul'],
            'tahun akademik tidak ada' => [['tahun_akademik_id' => 999999], 'tahun_akademik_id'],
            'tanggal mulai salah format' => [['tanggal_mulai' => '01-12-2026'], 'tanggal_mulai'],
            'tanggal selesai sebelum mulai' => [['tanggal_selesai' => '2026-11-30'], 'tanggal_selesai'],
            'jam selesai sama dengan mulai' => [['waktu_selesai' => '09:00'], 'waktu_selesai'],
            'jam selesai sebelum mulai' => [['waktu_mulai' => '14:00', 'waktu_selesai' => '13:00'], 'waktu_selesai'],
            'tengah malam mulai' => [['waktu_mulai' => '00:00', 'waktu_selesai' => '01:00'], 'waktu_mulai'],
            'deskripsi terlalu panjang' => [['deskripsi' => str_repeat('a', 1001)], 'deskripsi'],
        ];
    }

    public function test_semester_harus_sesuai_dengan_tahun_akademik(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun(2026, true, 'ganjil');

        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, ['semester' => 'genap']))
            ->assertSessionHasErrors('semester');

        $this->assertDatabaseCount('agenda_akademik', 0);
    }

    public function test_bentrok_lokasi_ujian_ditolak_dan_jadwal_kuliah_tidak_menghalangi(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();
        $kelas = $this->kelas(User::factory()->dosen()->create(), $tahun, $this->mataKuliah(), 'A01');

        // Jadwal kuliah bukan sumber bentrok untuk pengumuman ujian institusional.
        JadwalKuliah::query()->create([
            'kelas_mata_kuliah_id' => $kelas->id,
            'hari' => Carbon::parse('2026-12-01')->dayOfWeek,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'ruang' => 'Aula Utama',
            'jenis' => 'kuliah',
        ]);

        $this->ujian($admin, $tahun, JenisUjian::Uts, '2026-12-01', '2026-12-01', true, null, 'Aula Utama');

        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, [
                'waktu_mulai' => '09:30',
                'waktu_selesai' => '11:30',
                'lokasi' => 'Aula Utama',
            ]))
            ->assertSessionHasErrors('lokasi');

        $this->assertSame(1, AgendaAkademik::query()->count());

        // Waktu berbeda pada lokasi sama diperbolehkan.
        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, [
                'waktu_mulai' => '12:00',
                'waktu_selesai' => '13:00',
                'lokasi' => 'Aula Utama',
            ]))
            ->assertSessionDoesntHaveErrors();

        // Lokasi berbeda pada waktu sama diperbolehkan.
        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, [
                'lokasi' => 'Aula Timur',
            ]))
            ->assertSessionDoesntHaveErrors();

        // Lokasi kosong tidak dapat dibuktikan sama, jadi tidak diblokir.
        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, [
                'lokasi' => null,
                'judul' => 'Jadwal Ujian Tengah Semester (UTS) — Ganjil 2026/2027',
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(4, AgendaAkademik::query()->count());
    }

    public function test_bentrok_sebagian_pada_agenda_multi_hari_ditolak_dan_record_sendiri_dikecualikan(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();
        $ujian = $this->ujian(
            $admin,
            $tahun,
            JenisUjian::Uts,
            '2026-12-01',
            '2026-12-05',
            true,
            null,
            'Aula Utama',
        );

        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, [
                'tanggal_mulai' => '2026-12-05',
                'tanggal_selesai' => '2026-12-05',
                'waktu_mulai' => '09:30',
                'waktu_selesai' => '11:30',
                'lokasi' => 'aula utama',
            ]))
            ->assertSessionHasErrors('lokasi');

        $this->actingAs($admin)
            ->put(route('admin.jadwal-ujian.update', $ujian), $this->payload($tahun, [
                'tanggal_mulai' => '2026-12-01',
                'tanggal_selesai' => '2026-12-05',
                'waktu_mulai' => '09:00',
                'waktu_selesai' => '11:00',
                'lokasi' => 'Aula Utama',
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, AgendaAkademik::query()->count());
    }

    public function test_agenda_tanpa_jam_pada_lokasi_sama_tetap_dianggap_bentrok(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();
        $tanpaJam = $this->ujian($admin, $tahun, JenisUjian::Uts, '2026-12-03', '2026-12-03', true, null, 'Aula Utama');

        $tanpaJam->fill(['waktu_mulai' => null, 'waktu_selesai' => null])->save();

        $this->actingAs($admin)
            ->post(route('admin.jadwal-ujian.store'), $this->payload($tahun, [
                'tanggal_mulai' => '2026-12-03',
                'tanggal_selesai' => '2026-12-03',
                'lokasi' => $tanpaJam->lokasi,
            ]))
            ->assertSessionHasErrors('lokasi');
    }

    public function test_agenda_ujian_lama_tidak_diubah_dan_tidak_bisa_disunting_di_halaman_ini(): void
    {
        $admin = User::factory()->admin()->create();
        $tahun = $this->tahun();
        $kelas = $this->kelas(User::factory()->dosen()->create(), $tahun, $this->mataKuliah(), 'A01');

        $tanpaPenanda = $this->ujianKelasLama($admin, $kelas, null, 'Ujian Lama');
        $berpenanda = $this->ujianKelasLama($admin, $kelas, JenisUjian::Uts, 'UTS Lama per Kelas');

        $response = $this->actingAs($admin)->get(route('admin.jadwal-ujian.index'));
        $response->assertOk();
        $this->assertSame(0, $response->viewData('ujian')->total());
        $response->assertDontSee('Ujian Lama');
        $response->assertDontSee('UTS Lama per Kelas');

        foreach ([$tanpaPenanda, $berpenanda] as $lama) {
            $this->actingAs($admin)->get(route('admin.jadwal-ujian.edit', $lama))->assertNotFound();
            $this->actingAs($admin)
                ->put(route('admin.jadwal-ujian.update', $lama), $this->payload($tahun))
                ->assertNotFound();
            $this->actingAs($admin)
                ->delete(route('admin.jadwal-ujian.destroy', $lama))
                ->assertNotFound();
        }

        $this->assertDatabaseCount('agenda_akademik', 2);
        $this->assertSame($kelas->id, (int) $tanpaPenanda->fresh()->kelas_mata_kuliah_id);
        $this->assertSame($kelas->id, (int) $berpenanda->fresh()->kelas_mata_kuliah_id);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(TahunAkademik $tahun, array $overrides = []): array
    {
        $jenisUjian = JenisUjian::tryFrom((string) ($overrides['jenis_ujian'] ?? JenisUjian::Uts->value)) ?? JenisUjian::Uts;
        $tahunId = (int) ($overrides['tahun_akademik_id'] ?? $tahun->id);
        $tahunPayload = $tahunId === $tahun->id ? $tahun : (TahunAkademik::query()->find($tahunId) ?? $tahun);

        return array_merge([
            'jenis_ujian' => $jenisUjian->value,
            'tahun_akademik_id' => $tahun->id,
            'semester' => $tahun->semester->value,
            'judul' => $this->judulOtomatis($jenisUjian, $tahunPayload),
            'tanggal_mulai' => '2026-12-01',
            'tanggal_selesai' => '2026-12-01',
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '11:00',
            'lokasi' => 'Aula Utama',
            'deskripsi' => 'Ujian tengah semester.',
            'dipublikasikan' => 1,
        ], $overrides);
    }

    private function judulOtomatis(JenisUjian $jenisUjian, TahunAkademik $tahun): string
    {
        $periode = ucfirst($tahun->semester->value).' '.$tahun->tahun_mulai.'/'.$tahun->tahun_selesai;

        return match ($jenisUjian) {
            JenisUjian::Uts => 'Jadwal Ujian Tengah Semester (UTS) — '.$periode,
            JenisUjian::Uas => 'Jadwal Ujian Akhir Semester (UAS) — '.$periode,
        };
    }

    private function ujian(
        User $admin,
        TahunAkademik $tahun,
        JenisUjian $jenis,
        string $tanggalMulai,
        ?string $tanggalSelesai = null,
        bool $dipublikasikan = true,
        ?string $judul = null,
        ?string $lokasi = null,
    ): AgendaAkademik {
        $agenda = new AgendaAkademik();
        $agenda->forceFill([
            'dibuat_oleh' => $admin->id,
            'kelas_mata_kuliah_id' => null,
            'tahun_akademik_id' => $tahun->id,
            'judul' => $judul ?? $this->judulOtomatis($jenis, $tahun),
            'jenis' => JenisAgenda::Ujian->value,
            'jenis_ujian' => $jenis->value,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai ?? $tanggalMulai,
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'lokasi' => $lokasi,
            'dipublikasikan_pada' => $dipublikasikan ? now()->subDay() : null,
        ])->save();

        return $agenda;
    }

    private function ujianKelasLama(
        User $pembuat,
        KelasMataKuliah $kelas,
        ?JenisUjian $jenis,
        string $judul,
    ): AgendaAkademik {
        $agenda = new AgendaAkademik();
        $agenda->forceFill([
            'dibuat_oleh' => $pembuat->id,
            'kelas_mata_kuliah_id' => $kelas->id,
            'judul' => $judul,
            'jenis' => JenisAgenda::Ujian->value,
            'jenis_ujian' => $jenis?->value,
            'tanggal_mulai' => '2026-12-01',
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'dipublikasikan_pada' => now()->subDay(),
        ])->save();

        return $agenda;
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
}
