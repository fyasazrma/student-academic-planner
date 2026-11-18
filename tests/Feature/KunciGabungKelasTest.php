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
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Mockery;
use PDOException;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;
use ValueError;

class KunciGabungKelasTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private ?TahunAkademik $tahun = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_kelas_baru_memiliki_status_tutup_dan_hash_kosong(): void
    {
        $kelas = $this->buatKelas();

        $this->assertSame(StatusPendaftaranKelas::Tutup, $kelas->status_pendaftaran);
        $this->assertSame('tutup', $kelas->getRawOriginal('status_pendaftaran'));
        $this->assertNull($kelas->kode_gabung_hash);
        $this->assertSame(0, KelasMataKuliah::query()->pendaftaranBuka()->count());
        $this->assertSame(1, KelasMataKuliah::query()->pendaftaranTutup()->count());
    }

    public function test_kelas_lama_tetap_aman_setelah_kolom_baru_ditambahkan(): void
    {
        $kelas = $this->buatKelas('A');

        foreach ([
            'id',
            'mata_kuliah_id',
            'tahun_akademik_id',
            'dosen_id',
            'kode_kelas',
            'nama_kelas',
            'kapasitas',
            'created_at',
            'updated_at',
            'kode_gabung_hash',
            'status_pendaftaran',
        ] as $kolom) {
            $this->assertContains($kolom, Schema::getColumnListing('kelas_mata_kuliah'));
        }

        $this->assertSame('A', $kelas->kode_kelas);
        $this->assertNotNull($kelas->mata_kuliah_id);
        $this->assertNotNull($kelas->tahun_akademik_id);
        $this->assertNotNull($kelas->dosen_id);
        $this->assertNull($kelas->fresh()->kode_gabung_hash);
        $this->assertSame(StatusPendaftaranKelas::Tutup, $kelas->fresh()->status_pendaftaran);
    }

    public function test_hash_key_harus_unik_jika_diisi(): void
    {
        $satu = $this->buatKelas('A');
        $dua = $this->buatKelas('B');
        $service = app(KunciGabungKelasService::class);

        $kunciSatu = $service->terapkan($satu);
        $kunciDua = $service->terapkan($dua);

        $this->assertNotSame($kunciSatu, $kunciDua);
        $this->assertNotSame($satu->kode_gabung_hash, $dua->kode_gabung_hash);

        $this->expectException(QueryException::class);
        $dua->forceFill(['kode_gabung_hash' => $satu->kode_gabung_hash])->save();
    }

    public function test_nilai_status_di_luar_enum_ditolak_oleh_model(): void
    {
        $this->assertNull(StatusPendaftaranKelas::tryFrom('tidak-valid'));

        $this->expectException(ValueError::class);
        new KelasMataKuliah(['status_pendaftaran' => 'tidak-valid']);
    }

    public function test_key_yang_dibuat_dapat_diverifikasi_dan_key_salah_ditolak(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        $kunci = $service->terapkan($kelas);

        $this->assertSame(32, strlen($kunci));
        $this->assertTrue($service->verifikasi($kelas, $kunci));
        $this->assertFalse($service->verifikasi($kelas, strrev($kunci)));
        $this->assertFalse($service->verifikasi($kelas, 'KUNCI-SALAH'));

        $kelasTanpaKey = $this->buatKelas('B');
        $this->assertFalse($service->verifikasi($kelasTanpaKey, $kunci));
    }

    public function test_normalisasi_key_konsisten_untuk_spasi_dan_huruf(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        $kunci = $service->terapkan($kelas);
        $input = '  '.strtolower($kunci).'  ';

        $this->assertSame($kunci, $service->normalisasi($input));
        $this->assertSame($service->hash($kunci), $service->hash($input));
        $this->assertTrue($service->verifikasi($kelas, $input));
    }

    public function test_rotasi_key_membuat_key_lama_tidak_berlaku(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        $kunciLama = $service->terapkan($kelas);
        $hashLama = $kelas->kode_gabung_hash;

        $kunciBaru = $service->rotasi($kelas);
        $hashBaru = $kelas->kode_gabung_hash;

        $this->assertNotSame($kunciLama, $kunciBaru);
        $this->assertNotSame($hashLama, $hashBaru);
        $this->assertTrue($service->verifikasi($kelas, $kunciBaru));
        $this->assertFalse($service->verifikasi($kelas, $kunciLama));
    }

    public function test_verifikasi_memakai_hash_terbaru_dari_database_pada_instance_stale(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        $kunciLama = $service->terapkan($kelas);
        $instanceStale = $kelas->fresh();

        $kunciBaru = $service->rotasi($kelas);

        $this->assertFalse($service->verifikasi($instanceStale, $kunciLama));
        $this->assertTrue($service->verifikasi($instanceStale, $kunciBaru));
        $this->assertTrue($service->verifikasi($kelas->fresh(), $kunciBaru));
        $this->assertSame(
            $service->hash($kunciBaru),
            DB::table('kelas_mata_kuliah')->where('id', $kelas->id)->value('kode_gabung_hash'),
        );
    }

    public function test_rotasi_berurutan_tidak_mengembalikan_key_yang_sudah_tidak_berlaku(): void
    {
        $service = app(KunciGabungKelasService::class);
        $kelas = $this->buatKelas();

        $kunciPertama = $service->terapkan($kelas);
        $instanceStale = $kelas->fresh();

        $kunciKedua = $service->rotasi($kelas);
        $kunciKetiga = $service->rotasi($instanceStale);

        $this->assertFalse($service->verifikasi($kelas, $kunciKedua));
        $this->assertTrue($service->verifikasi($kelas, $kunciKetiga));
        $this->assertNotSame($kunciPertama, $kunciKetiga);
        $this->assertSame(
            $service->hash($kunciKetiga),
            DB::table('kelas_mata_kuliah')->where('id', $kelas->id)->value('kode_gabung_hash'),
        );
    }

    public function test_pembuatan_key_ditolak_jika_kelas_sudah_memiliki_key(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        $service->terapkan($kelas);
        $hash = $kelas->kode_gabung_hash;

        try {
            $service->terapkan($kelas);
            $this->fail('Kelas yang sudah memiliki key tidak boleh dibuatkan key lagi.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('sudah memiliki kunci', $exception->getMessage());
        }

        $this->assertSame(
            $hash,
            DB::table('kelas_mata_kuliah')->where('id', $kelas->id)->value('kode_gabung_hash'),
        );
    }

    public function test_rotasi_ditolak_jika_kelas_belum_memiliki_key(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        try {
            $service->rotasi($kelas);
            $this->fail('Rotasi tanpa key existing tidak boleh diizinkan.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('belum memiliki kunci', $exception->getMessage());
        }

        $this->assertNull(DB::table('kelas_mata_kuliah')->where('id', $kelas->id)->value('kode_gabung_hash'));
    }

    public function test_kegagalan_rotasi_tidak_merusak_hash_lama(): void
    {
        $service = new class extends KunciGabungKelasService
        {
            public function buatKunci(): string
            {
                return 'KUNCI-TETAP-UNTUK-UJI';
            }
        };

        $kelas = $this->buatKelas();
        $kunciLama = $service->terapkan($kelas);
        $hashLama = $kelas->kode_gabung_hash;

        $this->assertNotNull($hashLama);

        try {
            $service->rotasi($kelas);
            $this->fail('Rotasi dengan hash yang sama harus gagal.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('KUNCI-TETAP-UNTUK-UJI', $exception->getMessage());
            $this->assertStringNotContainsString($hashLama, $exception->getMessage());
        }

        $this->assertSame(
            $hashLama,
            DB::table('kelas_mata_kuliah')->where('id', $kelas->id)->value('kode_gabung_hash'),
        );
        $this->assertTrue($service->verifikasi($kelas, $kunciLama));
    }

    public function test_deteksi_collision_hanya_untuk_pelanggaran_unik_kode_gabung_hash(): void
    {
        $service = app(KunciGabungKelasService::class);
        $method = new ReflectionMethod($service, 'bentrokKodeGabungUnik');
        $method->setAccessible(true);

        $sqlitePdo = new PDOException('UNIQUE constraint failed: kelas_mata_kuliah.kode_gabung_hash');
        $sqlitePdo->errorInfo = [
            '23000',
            19,
            'UNIQUE constraint failed: kelas_mata_kuliah.kode_gabung_hash',
        ];
        $sqlite = new QueryException('sqlite', 'update "kelas_mata_kuliah" set "kode_gabung_hash" = ?', [], $sqlitePdo);

        $mysqlPdo = new PDOException("Duplicate entry 'abc' for key 'kelas_mata_kuliah_kode_gabung_hash_unique'");
        $mysqlPdo->errorInfo = [
            '23000',
            1062,
            "Duplicate entry 'abc' for key 'kelas_mata_kuliah_kode_gabung_hash_unique'",
        ];
        $mysql = new QueryException('mysql', 'update `kelas_mata_kuliah` set `kode_gabung_hash` = ?', [], $mysqlPdo);

        $foreignPdo = new PDOException('FOREIGN KEY constraint failed');
        $foreignPdo->errorInfo = ['23000', 19, 'FOREIGN KEY constraint failed'];
        $foreign = new QueryException('sqlite', 'update "kelas_mata_kuliah" set "dosen_id" = ?', [], $foreignPdo);

        $this->assertTrue($method->invoke($service, $sqlite));
        $this->assertTrue($method->invoke($service, $mysql));
        $this->assertFalse($method->invoke($service, $foreign));
    }

    public function test_key_asli_tidak_disimpan_ke_database(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        $kunci = $service->terapkan($kelas);
        $baris = DB::table('kelas_mata_kuliah')->where('id', $kelas->id)->first();

        $this->assertNotNull($baris);
        $this->assertSame($service->hash($kunci), $baris->kode_gabung_hash);
        $this->assertSame(64, strlen($baris->kode_gabung_hash));
        $this->assertStringNotContainsString($kunci, json_encode($baris));
    }

    public function test_hash_tidak_tereksplos_melalui_serialisasi_model(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        $kunci = $service->terapkan($kelas);
        $hash = $kelas->kode_gabung_hash;
        $segaran = $kelas->fresh();

        $array = $segaran->toArray();
        $json = $segaran->toJson();

        $this->assertArrayNotHasKey('kode_gabung_hash', $array);
        $this->assertStringNotContainsString($hash, json_encode($array));
        $this->assertStringNotContainsString($hash, $json);
        $this->assertStringNotContainsString($kunci, $json);
    }

    public function test_service_tidak_menulis_key_asli_ke_log_atau_pesan_exception(): void
    {
        Log::spy();

        $service = new class extends KunciGabungKelasService
        {
            public function buatKunci(): string
            {
                return 'KUNCI-TETAP-UNTUK-UJI';
            }
        };

        $kelas = $this->buatKelas();
        $kunci = $service->terapkan($kelas);
        $hash = $kelas->kode_gabung_hash;

        $service->verifikasi($kelas, $kunci);
        $service->verifikasi($kelas, 'KUNCI-SALAH');

        try {
            $service->rotasi($kelas);
            $this->fail('Rotasi dengan hash bentrok seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('KUNCI-TETAP-UNTUK-UJI', $exception->getMessage());
            $this->assertStringNotContainsString($hash, $exception->getMessage());
        }

        foreach ([
            'write',
            'info',
            'debug',
            'notice',
            'warning',
            'error',
            'critical',
            'alert',
            'emergency',
            'log',
        ] as $method) {
            Log::shouldNotHaveReceived($method);
        }

        Mockery::close();
    }

    public function test_menutup_dan_membuka_pendaftaran_tidak_mengganti_key(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        $kunci = $service->terapkan($kelas);
        $hash = $kelas->kode_gabung_hash;

        $kelas->status_pendaftaran = StatusPendaftaranKelas::Tutup;
        $kelas->save();

        $this->assertSame(
            $hash,
            DB::table('kelas_mata_kuliah')->where('id', $kelas->id)->value('kode_gabung_hash'),
        );
        $this->assertTrue($service->verifikasi($kelas, $kunci));

        $kelas->status_pendaftaran = StatusPendaftaranKelas::Buka;
        $kelas->save();

        $this->assertSame(
            $hash,
            DB::table('kelas_mata_kuliah')->where('id', $kelas->id)->value('kode_gabung_hash'),
        );
        $this->assertTrue($service->verifikasi($kelas, $kunci));
        $this->assertSame(StatusPendaftaranKelas::Buka, $kelas->fresh()->status_pendaftaran);
    }

    public function test_rotasi_key_tidak_menghapus_peserta_terdaftar(): void
    {
        $kelas = $this->buatKelas();
        $service = app(KunciGabungKelasService::class);

        $kunciLama = $service->terapkan($kelas);
        $peserta = $this->tambahPeserta($kelas, StatusPeserta::Aktif);

        $kunciBaru = $service->rotasi($kelas);

        $this->assertSame(1, PesertaMataKuliah::query()->where('kelas_mata_kuliah_id', $kelas->id)->count());
        $this->assertTrue(PesertaMataKuliah::query()->whereKey($peserta->id)->exists());
        $this->assertFalse($service->verifikasi($kelas, $kunciLama));
        $this->assertTrue($service->verifikasi($kelas, $kunciBaru));
    }

    public function test_kapasitas_null_berarti_tidak_terbatas(): void
    {
        $kelas = $this->buatKelas();
        $kelas->kapasitas = null;
        $kelas->save();

        $this->assertTrue($kelas->masihMenerimaPeserta());

        $this->tambahPeserta($kelas, StatusPeserta::Aktif);
        $this->tambahPeserta($kelas, StatusPeserta::Aktif);
        $this->tambahPeserta($kelas, StatusPeserta::Aktif);

        $this->assertSame(3, $kelas->jumlahPesertaAktif());
        $this->assertTrue($kelas->masihMenerimaPeserta());
    }

    public function test_kapasitas_angka_membatasi_peserta_aktif_saja(): void
    {
        $kelas = $this->buatKelas();
        $kelas->kapasitas = 2;
        $kelas->save();

        $this->assertTrue($kelas->masihMenerimaPeserta());

        $this->tambahPeserta($kelas, StatusPeserta::Aktif);
        $this->tambahPeserta($kelas, StatusPeserta::Nonaktif);
        $this->tambahPeserta($kelas, StatusPeserta::Mengulang);

        $this->assertSame(1, $kelas->jumlahPesertaAktif());
        $this->assertTrue($kelas->masihMenerimaPeserta());

        $this->tambahPeserta($kelas, StatusPeserta::Aktif);

        $this->assertSame(2, $kelas->jumlahPesertaAktif());
        $this->assertFalse($kelas->masihMenerimaPeserta());
    }

    public function test_migration_up_dan_down_aman_pada_sqlite_terisolasi(): void
    {
        config()->set('database.connections.migrasi_isolasi', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        Schema::connection('migrasi_isolasi')->create('kelas_mata_kuliah', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('mata_kuliah_id');
            $table->unsignedBigInteger('tahun_akademik_id');
            $table->unsignedBigInteger('dosen_id');
            $table->string('kode_kelas', 10);
            $table->string('nama_kelas', 100)->nullable();
            $table->unsignedSmallInteger('kapasitas')->nullable();
            $table->timestamps();
        });

        DB::connection('migrasi_isolasi')->table('kelas_mata_kuliah')->insert([
            'id' => 1,
            'mata_kuliah_id' => 10,
            'tahun_akademik_id' => 20,
            'dosen_id' => 30,
            'kode_kelas' => 'A',
            'nama_kelas' => 'Kelas lama',
            'kapasitas' => null,
            'created_at' => '2026-11-17 00:00:00',
            'updated_at' => '2026-11-17 00:00:00',
        ]);

        $koneksiAwal = DB::getDefaultConnection();
        DB::setDefaultConnection('migrasi_isolasi');
        Facade::clearResolvedInstance('db.schema');

        try {
            $migration = require database_path('migrations/2026_11_18_000001_add_kode_gabung_hash_dan_status_pendaftaran_ke_kelas_mata_kuliah.php');
            $migration->up();

            $kolomSesudahUp = Schema::getColumnListing('kelas_mata_kuliah');
            $this->assertContains('kode_gabung_hash', $kolomSesudahUp);
            $this->assertContains('status_pendaftaran', $kolomSesudahUp);

            $barisSesudahUp = DB::table('kelas_mata_kuliah')->where('id', 1)->first();
            $this->assertNull($barisSesudahUp->kode_gabung_hash);
            $this->assertSame('tutup', $barisSesudahUp->status_pendaftaran);
            $this->assertSame('A', $barisSesudahUp->kode_kelas);

            $migration->down();

            $kolomSesudahDown = Schema::getColumnListing('kelas_mata_kuliah');
            $this->assertNotContains('kode_gabung_hash', $kolomSesudahDown);
            $this->assertNotContains('status_pendaftaran', $kolomSesudahDown);
            $this->assertContains('kode_kelas', $kolomSesudahDown);

            $barisSesudahDown = DB::table('kelas_mata_kuliah')->where('id', 1)->first();
            $this->assertSame('A', $barisSesudahDown->kode_kelas);
            $this->assertSame('Kelas lama', $barisSesudahDown->nama_kelas);
        } finally {
            DB::setDefaultConnection($koneksiAwal);
            Facade::clearResolvedInstance('db.schema');
        }
    }

    private function buatKelas(string $kodeKelas = 'A'): KelasMataKuliah
    {
        $this->urutan++;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MK'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Uji '.$this->urutan,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $this->tahun()->id,
            'dosen_id' => User::factory()->dosen()->create()->id,
            'kode_kelas' => $kodeKelas,
            'nama_kelas' => 'Kelas '.$kodeKelas,
            'kapasitas' => null,
        ])->save();

        return $kelas->fresh();
    }

    private function tahun(): TahunAkademik
    {
        if ($this->tahun !== null) {
            return $this->tahun;
        }

        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => 2026,
            'tahun_selesai' => 2027,
            'semester' => 'ganjil',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-12-31',
            'aktif' => true,
        ])->save();

        return $this->tahun = $tahun;
    }

    private function tambahPeserta(KelasMataKuliah $kelas, StatusPeserta $status): PesertaMataKuliah
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status->value,
            'tanggal_daftar' => now()->toDateString(),
        ])->save();

        return $peserta;
    }
}
