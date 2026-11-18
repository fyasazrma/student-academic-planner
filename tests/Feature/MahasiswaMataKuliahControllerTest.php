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
use App\Services\Mahasiswa\MataKuliahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MahasiswaMataKuliahControllerTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private ?User $dosen = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // ---------------------------------------------------------------------
    // Otorisasi
    // ---------------------------------------------------------------------

    public function test_pengunjung_tanpa_login_tidak_dapat_mengakses_endpoint(): void
    {
        $this->get(route('mahasiswa.mata-kuliah'))->assertRedirect(route('login'));
        $this->get(route('mahasiswa.mata-kuliah.cari'))->assertRedirect(route('login'));
        $this->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => 'ABC'])
            ->assertRedirect(route('login'));
    }

    public function test_user_nonaktif_ditolak(): void
    {
        $user = User::factory()
            ->mahasiswa()
            ->state(fn (): array => ['is_active' => false])
            ->create();

        $this->actingAs($user)
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertRedirect(route('login'));

        $this->actingAs($user)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => 'ABC'])
            ->assertRedirect(route('login'));
    }

    public function test_dosen_dan_admin_ditolak(): void
    {
        foreach ([User::factory()->dosen(), User::factory()->admin()] as $factory) {
            $user = $factory->create();

            $this->actingAs($user)->get(route('mahasiswa.mata-kuliah'))->assertForbidden();
            $this->actingAs($user)->get(route('mahasiswa.mata-kuliah.cari'))->assertForbidden();
            $this->actingAs($user)
                ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => 'ABC'])
                ->assertForbidden();
        }
    }

    public function test_mahasiswa_aktif_dapat_mengakses_endpoint(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertOk()
            ->assertViewIs('mahasiswa.mata-kuliah.index')
            ->assertViewHasAll(['user', 'unreadCount', 'kelasSaya', 'kataKunci', 'kelas', 'paginasi'])
            ->assertSee('Kelas Saya')
            ->assertSee('Cari Kelas');

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah.cari'))
            ->assertOk()
            ->assertViewIs('mahasiswa.mata-kuliah.index')
            ->assertSee('Cari Kelas');
    }

    // ---------------------------------------------------------------------
    // Endpoint kelas saya dan pencarian
    // ---------------------------------------------------------------------

    public function test_index_hanya_menampilkan_pendaftaran_aktif_milik_mahasiswa_yang_login(): void
    {
        [$kelasSaya] = $this->buatKelas(['nama_mk' => 'Pemrograman Web']);
        [$kelasLain] = $this->buatKelas(['nama_mk' => 'Basis Data']);
        [$kelasNonaktif] = $this->buatKelas(['nama_mk' => 'Jaringan Komputer']);

        $mahasiswa = User::factory()->mahasiswa()->create();
        $lain = User::factory()->mahasiswa()->create();

        $this->tambahPeserta($kelasSaya, $mahasiswa);
        $this->tambahPeserta($kelasLain, $lain);
        $this->tambahPeserta($kelasNonaktif, $mahasiswa, StatusPeserta::Nonaktif);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertOk()
            ->assertViewIs('mahasiswa.mata-kuliah.index')
            ->assertViewHas('kelasSaya', function (array $kelasSaya): bool {
                return count($kelasSaya) === 1
                    && $kelasSaya[0]['nama_mata_kuliah'] === 'Pemrograman Web'
                    && $kelasSaya[0]['status_peserta'] === StatusPeserta::Aktif->value;
            })
            ->assertSee('Pemrograman Web')
            ->assertSee('Aktif')
            ->assertDontSee('Basis Data')
            ->assertDontSee('Jaringan Komputer');
    }

    public function test_pencarian_memakai_service_serta_menjaga_paginasi_dan_query_string(): void
    {
        for ($index = 0; $index < 14; $index++) {
            $this->buatKelas(['nama_mk' => 'Pemrograman Laravel']);
        }
        $this->buatKelas(['nama_mk' => 'Basis Data']);

        $mahasiswa = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah.cari', [
                'q' => 'Pemrograman',
                'page' => 2,
            ]))
            ->assertOk()
            ->assertViewIs('mahasiswa.mata-kuliah.index')
            ->assertViewHas('kataKunci', 'Pemrograman')
            ->assertViewHas('paginasi', function (array $paginasi): bool {
                return $paginasi['total'] === 14
                    && $paginasi['per_halaman'] === MataKuliahService::PER_HALAMAN
                    && $paginasi['halaman_saat_ini'] === 2
                    && $paginasi['halaman_terakhir'] === 2;
            })
            ->assertSee('Menampilkan 13–14 dari 14 kelas')
            ->assertSee('value="Pemrograman"', false);

        $this->assertStringContainsString('q=Pemrograman', (string) $response->getContent());
    }

    public function test_respons_tidak_mengandung_atribut_privat_akun_dosen(): void
    {
        $dosen = User::factory()->dosen()->create([
            'name' => 'Dosen Publik',
            'email' => 'dosen.rahasia@example.test',
        ]);
        [$kelas] = $this->buatKelas([
            'dosen' => $dosen,
            'nama_mk' => 'Pemrograman Web',
        ]);

        $mahasiswa = User::factory()->mahasiswa()->create();
        $this->tambahPeserta($kelas, $mahasiswa);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertOk()
            ->assertSee('Dosen Publik')
            ->assertDontSee($dosen->email)
            ->assertDontSee((string) $dosen->nidn);

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah.cari'))
            ->assertOk()
            ->assertSee('Dosen Publik')
            ->assertDontSee($dosen->email)
            ->assertDontSee((string) $dosen->nidn);
    }

    // ---------------------------------------------------------------------
    // Penggabungan kelas
    // ---------------------------------------------------------------------

    public function test_kunci_valid_menghasilkan_redirect_sukses(): void
    {
        [$kelas, $kunci] = $this->buatKelas();
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunci])
            ->assertRedirect(route('mahasiswa.mata-kuliah'))
            ->assertSessionHas('status', 'Anda berhasil bergabung dengan kelas ini.');

        $this->assertDatabaseHas('peserta_mata_kuliah', [
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => StatusPeserta::Aktif->value,
        ]);
    }

    public function test_kunci_salah_menghasilkan_error_pada_field_kode_gabung(): void
    {
        [, $kunci] = $this->buatKelas();
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => strrev($kunci)])
            ->assertRedirect()
            ->assertSessionHasErrors(['kode_gabung' => 'Kunci gabung tidak valid atau kelas tidak tersedia.']);

        $this->assertSame(0, PesertaMataKuliah::query()->count());
    }

    public function test_kelas_penuh_ditolak(): void
    {
        [$kelas, $kunci] = $this->buatKelas(['kapasitas' => 2]);
        $this->tambahPeserta($kelas, User::factory()->mahasiswa()->create());
        $this->tambahPeserta($kelas, User::factory()->mahasiswa()->create());
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunci])
            ->assertRedirect()
            ->assertSessionHas('status', 'Kapasitas kelas sudah penuh.');

        $this->assertSame(2, PesertaMataKuliah::query()->count());
    }

    public function test_pendaftaran_tertutup_ditolak(): void
    {
        [, $kunci] = $this->buatKelas(['status' => StatusPendaftaranKelas::Tutup]);
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunci])
            ->assertRedirect()
            ->assertSessionHas('status', 'Pendaftaran kelas ini sedang ditutup.');

        $this->assertSame(0, PesertaMataKuliah::query()->count());
    }

    public function test_mata_kuliah_dan_tahun_akademik_nonaktif_ditolak(): void
    {
        $mataKuliah = $this->mataKuliah(false);
        [, $kunciMk] = $this->buatKelas(['mata_kuliah' => $mataKuliah]);
        [, $kunciTa] = $this->buatKelas(['tahun_akademik' => $this->tahun(false)]);
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunciMk])
            ->assertRedirect()
            ->assertSessionHas('status', 'Kelas ini tidak tersedia untuk pendaftaran.');

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunciTa])
            ->assertRedirect()
            ->assertSessionHas('status', 'Kelas ini tidak tersedia untuk pendaftaran.');

        $this->assertSame(0, PesertaMataKuliah::query()->count());
    }

    public function test_mahasiswa_yang_sudah_terdaftar_ditolak(): void
    {
        [$kelas, $kunci] = $this->buatKelas();
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunci])
            ->assertSessionHas('status');

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunci])
            ->assertRedirect()
            ->assertSessionHas('status', 'Anda sudah terdaftar pada kelas ini.');

        $this->assertSame(
            1,
            PesertaMataKuliah::query()->where('kelas_mata_kuliah_id', $kelas->id)->count(),
        );
    }

    public function test_riwayat_nonaktif_dan_mengulang_minta_hubungi_dosen(): void
    {
        foreach ([StatusPeserta::Nonaktif, StatusPeserta::Mengulang] as $status) {
            [$kelas, $kunci] = $this->buatKelas();
            $mahasiswa = User::factory()->mahasiswa()->create();
            $lama = $this->tambahPeserta($kelas, $mahasiswa, $status);

            $this->actingAs($mahasiswa)
                ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunci])
                ->assertRedirect()
                ->assertSessionHas('status', 'Anda memiliki riwayat pendaftaran pada kelas ini. Hubungi dosen untuk informasi lebih lanjut.');

            $this->assertSame($status->value, $lama->fresh()->status->value);
        }
    }

    public function test_identitas_dan_status_tidak_diambil_dari_request(): void
    {
        [$kelas, $kunci] = $this->buatKelas();
        $pemilik = User::factory()->mahasiswa()->create();
        $lain = User::factory()->mahasiswa()->create();

        $this->actingAs($pemilik)
            ->post(route('mahasiswa.mata-kuliah.gabung'), [
                'kode_gabung' => $kunci,
                'mahasiswa_id' => $lain->id,
                'kelas_mata_kuliah_id' => 999999,
                'status' => StatusPeserta::Nonaktif->value,
            ])
            ->assertRedirect(route('mahasiswa.mata-kuliah'));

        $this->assertDatabaseHas('peserta_mata_kuliah', [
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $pemilik->id,
            'status' => StatusPeserta::Aktif->value,
        ]);
        $this->assertDatabaseMissing('peserta_mata_kuliah', [
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $lain->id,
        ]);
    }

    public function test_kunci_gabung_tidak_masuk_flash_session_atau_old_input(): void
    {
        [, $kunci] = $this->buatKelas();
        $mahasiswa = User::factory()->mahasiswa()->create();

        $salahKunci = $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => strrev($kunci)])
            ->assertRedirect()
            ->assertSessionHasErrors('kode_gabung');

        $salahKunci
            ->assertSessionMissing('kode_gabung')
            ->assertSessionMissingInput('kode_gabung');

        $validasiGagal = $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => str_repeat('A', 33)])
            ->assertRedirect()
            ->assertSessionHasErrors('kode_gabung');

        $validasiGagal
            ->assertSessionMissing('kode_gabung')
            ->assertSessionMissingInput('kode_gabung');
    }

    // ---------------------------------------------------------------------
    // Rate limiting
    // ---------------------------------------------------------------------

    public function test_gabung_dibatasi_lima_kali_per_menit(): void
    {
        [, $kunci] = $this->buatKelas();
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa);

        for ($percobaan = 0; $percobaan < 5; $percobaan++) {
            $this->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => 'SALAH'.$percobaan])
                ->assertSessionHasErrors('kode_gabung');
        }

        $this->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunci])
            ->assertRedirect()
            ->assertSessionHasErrors('kode_gabung', 'Terlalu banyak percobaan bergabung');

        $this->assertSame(0, PesertaMataKuliah::query()->count());
    }

    public function test_user_berbeda_tidak_saling_menghabiskan_limit(): void
    {
        [, $kunci] = $this->buatKelas();
        $pertama = User::factory()->mahasiswa()->create();
        $kedua = User::factory()->mahasiswa()->create();

        for ($percobaan = 0; $percobaan < 5; $percobaan++) {
            $this->actingAs($pertama)
                ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => 'SALAH'])
                ->assertSessionHasErrors('kode_gabung');
        }

        $this->actingAs($kedua)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunci])
            ->assertRedirect(route('mahasiswa.mata-kuliah'))
            ->assertSessionHas('status');
    }

    public function test_ip_berbeda_memiliki_limit_terpisah(): void
    {
        [, $kunci] = $this->buatKelas();
        $mahasiswa = User::factory()->mahasiswa()->create();

        for ($percobaan = 0; $percobaan < 5; $percobaan++) {
            $this->actingAs($mahasiswa)
                ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => 'SALAH'])
                ->assertSessionHasErrors('kode_gabung');
        }

        $this->actingAs($mahasiswa)
            ->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => $kunci])
            ->assertRedirect(route('mahasiswa.mata-kuliah'))
            ->assertSessionHas('status');
    }

    public function test_pencarian_tidak_terkena_rate_limiter_gabung(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        for ($percobaan = 0; $percobaan < 5; $percobaan++) {
            $this->actingAs($mahasiswa)
                ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => 'SALAH'])
                ->assertSessionHasErrors('kode_gabung');
        }

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah.cari'))
            ->assertOk()
            ->assertViewIs('mahasiswa.mata-kuliah.index');
    }

    // ---------------------------------------------------------------------
    // Tampilan dan interaksi UI
    // ---------------------------------------------------------------------

    public function test_halaman_menampilkan_empty_state_bila_belum_ada_kelas(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertOk()
            ->assertSee('Belum ada kelas diikuti');
    }

    public function test_form_gabung_mengirim_post_ke_route_yang_benar(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertOk()
            ->assertSee('action="'.route('mahasiswa.mata-kuliah.gabung').'"', false)
            ->assertSee('name="kode_gabung"', false)
            ->assertSee('required', false)
            ->assertDontSee('kode_gabung_hash', false);
    }

    public function test_pesan_flash_dan_error_validasi_gabung_tampil_di_halaman(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->withSession(['status' => 'Pendaftaran kelas ini sedang ditutup.'])
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertOk()
            ->assertSee('Pendaftaran kelas ini sedang ditutup.');

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.mata-kuliah.gabung'), ['kode_gabung' => ''])
            ->assertRedirect();

        $this->actingAs($mahasiswa)
            ->get(route('mahasiswa.mata-kuliah'))
            ->assertOk()
            ->assertSee('Kunci gabung wajib diisi.');
    }

    public function test_menu_sidebar_mata_kuliah_aktif_pada_halaman_ini(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        foreach ([route('mahasiswa.mata-kuliah'), route('mahasiswa.mata-kuliah.cari')] as $url) {
            $html = (string) $this->actingAs($mahasiswa)
                ->get($url)
                ->assertOk()
                ->getContent();

            $this->assertMatchesRegularExpression(
                '/<a\b[^>]*href="'.preg_quote(route('mahasiswa.mata-kuliah'), '/').'"[^>]*aria-current="page"/',
                $html,
            );
        }
    }

    public function test_breadcrumb_menampilkan_label_mata_kuliah(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        foreach ([route('mahasiswa.mata-kuliah'), route('mahasiswa.mata-kuliah.cari')] as $url) {
            $this->actingAs($mahasiswa)
                ->get($url)
                ->assertOk()
                ->assertSee('Mata Kuliah');
        }
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $opsi
     * @return array{0: KelasMataKuliah, 1: ?string}
     */
    private function buatKelas(array $opsi = []): array
    {
        $this->urutan++;
        $nomor = $this->urutan;

        $mataKuliah = $opsi['mata_kuliah'] ?? $this->mataKuliah(true, $opsi['nama_mk'] ?? null);
        $tahun = $opsi['tahun_akademik'] ?? $this->tahun(true);
        $dosen = $opsi['dosen'] ?? $this->dosen();

        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $opsi['kode_kelas'] ?? 'K'.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT),
            'nama_kelas' => $opsi['nama_kelas'] ?? 'Kelas '.$nomor,
            'kapasitas' => $opsi['kapasitas'] ?? null,
            'status_pendaftaran' => $opsi['status'] ?? StatusPendaftaranKelas::Buka,
        ])->save();

        $kunci = null;
        if (($opsi['dengan_kunci'] ?? true) === true) {
            $kunci = app(KunciGabungKelasService::class)->terapkan($kelas);
        }

        return [$kelas->fresh(), $kunci];
    }

    private function mataKuliah(bool $aktif = true, ?string $nama = null): MataKuliah
    {
        $this->urutan++;
        $nomor = $this->urutan;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MK'.str_pad((string) $nomor, 4, '0', STR_PAD_LEFT),
            'nama' => $nama ?? 'Mata Kuliah '.$nomor,
            'sks' => 3,
            'program_studi' => 'Informatika',
            'semester' => 1,
            'aktif' => $aktif,
        ])->save();

        return $mataKuliah;
    }

    private function tahun(bool $aktif = true): TahunAkademik
    {
        $this->urutan++;
        $nomor = $this->urutan;

        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => 2020 + $nomor,
            'tahun_selesai' => 2021 + $nomor,
            'semester' => 'ganjil',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-12-31',
            'aktif' => $aktif,
        ])->save();

        return $tahun;
    }

    private function dosen(): User
    {
        if ($this->dosen === null) {
            $this->dosen = User::factory()->dosen()->create();
        }

        return $this->dosen;
    }

    private function tambahPeserta(
        KelasMataKuliah $kelas,
        User $mahasiswa,
        StatusPeserta|string $status = StatusPeserta::Aktif,
    ): PesertaMataKuliah {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status instanceof StatusPeserta ? $status->value : $status,
            'tanggal_daftar' => today()->toDateString(),
        ])->save();

        return $peserta;
    }
}
