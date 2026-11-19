<?php

namespace Tests\Feature;

use App\Enums\StatusPeserta;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\Notifikasi;
use App\Models\PengumpulanTugas;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\Tugas;
use App\Models\User;
use App\Services\Mahasiswa\PengumpulanFileService;
use App\Services\Mahasiswa\PengumpulanTugasService;
use Carbon\Carbon;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use PDOException;
use ReflectionMethod;
use Tests\TestCase;
use Throwable;

class MahasiswaPengumpulanTugasTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private int $nimUrutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        Carbon::setTestNow('2026-11-18 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_pengumpulan_pertama_berhasil_disimpan_dengan_nilai_server(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());
        $jumlahNotifikasi = Notifikasi::count();

        $response = $this->actingAs($data['user'])
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                'lampiran' => $this->jawaban('jawaban-akhir.pdf'),
                'catatan' => '  Mohon diperiksa.  ',
                'tugas_id' => 9999,
                'peserta_mata_kuliah_id' => 9999,
                'terlambat' => true,
                'nilai' => 100,
                'status' => 'dinilai',
            ]);

        $response->assertRedirect(route('mahasiswa.tugas'))
            ->assertSessionHas('success');

        $pengumpulan = PengumpulanTugas::query()->firstOrFail();

        $this->assertSame($tugas->id, (int) $pengumpulan->tugas_id);
        $this->assertSame($data['enrollment']->id, (int) $pengumpulan->peserta_mata_kuliah_id);
        $this->assertSame('jawaban-akhir.pdf', $pengumpulan->file_name);
        $this->assertSame('Mohon diperiksa.', $pengumpulan->catatan);
        $this->assertFalse((bool) $pengumpulan->terlambat);
        $this->assertNull($pengumpulan->nilai);
        $this->assertNull($pengumpulan->dinilai_pada);
        $this->assertStringStartsWith('pengumpulan/'.$data['class']->id.'/', $pengumpulan->file_path);
        Storage::disk('local')->assertExists($pengumpulan->file_path);
        $this->assertSame(1, PengumpulanTugas::count());
        $this->assertSame($jumlahNotifikasi, Notifikasi::count());
    }

    public function test_resubmit_mengganti_file_dan_catatan_dalam_satu_baris(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());
        $lama = $this->buatPengumpulan($tugas, $data['enrollment'], 'awal.pdf');
        $pathLama = $lama->file_path;

        $response = $this->actingAs($data['user'])
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                'lampiran' => $this->jawaban('revisi.pdf'),
                'catatan' => 'Versi revisi',
            ]);

        $response->assertRedirect(route('mahasiswa.tugas'));
        $this->assertSame(1, PengumpulanTugas::count());

        $baru = $lama->fresh();

        $this->assertSame($lama->id, $baru->id);
        $this->assertNotSame($pathLama, $baru->file_path);
        $this->assertSame('revisi.pdf', $baru->file_name);
        $this->assertSame('Versi revisi', $baru->catatan);
        Storage::disk('local')->assertMissing($pathLama);
        Storage::disk('local')->assertExists($baru->file_path);
    }

    public function test_peserta_nonaktif_dan_mengulang_ditolak(): void
    {
        foreach ([StatusPeserta::Nonaktif, StatusPeserta::Mengulang] as $status) {
            $data = $this->mahasiswaWithClass();
            $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());

            $data['enrollment']->status = $status->value;
            $data['enrollment']->save();

            $this->actingAs($data['user'])
                ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                    'lampiran' => $this->jawaban(),
                ])
                ->assertNotFound();

            $this->actingAs($data['user'])
                ->get(route('mahasiswa.tugas.pengumpulan', $tugas->id))
                ->assertNotFound();
        }

        $this->assertSame(0, PengumpulanTugas::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_tugas_draft_dan_publikasi_mendatang_ditolak(): void
    {
        $publikasiList = [null, now()->addDay()];

        foreach ($publikasiList as $publikasi) {
            $data = $this->mahasiswaWithClass();
            $tugas = $this->createTask($data['class'], $publikasi, now()->addDays(3));

            $this->actingAs($data['user'])
                ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                    'lampiran' => $this->jawaban(),
                ])
                ->assertNotFound();

            $this->actingAs($data['user'])
                ->get(route('mahasiswa.tugas.pengumpulan', $tugas->id))
                ->assertNotFound();
        }

        $this->assertSame(0, PengumpulanTugas::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_deadline_terlewat_ditolak_ketika_keterlambatan_tidak_diizinkan(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDays(2), now()->subHour(), false);

        $response = $this->actingAs($data['user'])
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                'lampiran' => $this->jawaban(),
            ]);

        $response->assertSessionHasErrors('lampiran');
        $this->assertSame(0, PengumpulanTugas::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_keterlambatan_diizinkan_diterima_dengan_status_terlambat(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDays(2), now()->subHour(), true);

        $response = $this->actingAs($data['user'])
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                'lampiran' => $this->jawaban(),
            ]);

        $response->assertRedirect(route('mahasiswa.tugas'));

        $pengumpulan = PengumpulanTugas::query()->firstOrFail();

        $this->assertTrue((bool) $pengumpulan->terlambat);
        $this->assertSame('terlambat', $pengumpulan->status);
        Storage::disk('local')->assertExists($pengumpulan->file_path);
    }

    public function test_resubmit_setelah_dinilai_ditolak(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());
        $pengumpulan = $this->buatPengumpulan($tugas, $data['enrollment'], 'dinilai.pdf');
        $pengumpulan->forceFill([
            'nilai' => 85,
            'dinilai_oleh' => $data['dosen']->id,
            'dinilai_pada' => now()->subHour(),
        ])->save();
        $pathSebelumnya = $pengumpulan->file_path;

        $response = $this->actingAs($data['user'])
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                'lampiran' => $this->jawaban('tidak-boleh.pdf'),
            ]);

        $response->assertForbidden();

        $this->assertSame(1, PengumpulanTugas::count());
        $this->assertSame($pathSebelumnya, $pengumpulan->fresh()->file_path);
        $this->assertSame(1, count(Storage::disk('local')->allFiles()));
        Storage::disk('local')->assertExists($pathSebelumnya);
    }

    public function test_validasi_lampiran_wajib_dan_format_ditegakkan(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());

        $this->actingAs($data['user'])
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                'catatan' => 'Tanpa lampiran',
            ])
            ->assertSessionHasErrors('lampiran');

        $this->actingAs($data['user'])
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id), [
                'lampiran' => UploadedFile::fake()->create('virus.exe', 10),
            ])
            ->assertSessionHasErrors('lampiran');

        $this->assertSame(0, PengumpulanTugas::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_unduh_lampiran_diotorisasi_dan_ditolak_untuk_pihak_lain(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());
        $pengumpulan = $this->buatPengumpulan($tugas, $data['enrollment'], 'jawaban.pdf');

        $unduhUrl = route('mahasiswa.tugas.pengumpulan.unduh', $tugas->id);

        $response = $this->actingAs($data['user'])->get($unduhUrl);

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control'),
        );

        $pemilikLain = User::factory()->mahasiswa()->create();

        $this->actingAs($pemilikLain)->get($unduhUrl)->assertNotFound();
        $this->actingAs($data['dosen'])->get($unduhUrl)->assertForbidden();

        $this->assertSame($pengumpulan->file_path, $pengumpulan->fresh()->file_path);
    }

    public function test_isolasi_pengguna_dan_kelas_ditegakkan(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugasSaya = $this->createTask($data['class'], now()->subDay(), now()->addDay());

        $mahasiswaLain = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswaLain)
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugasSaya->id), [
                'lampiran' => $this->jawaban(),
            ])
            ->assertNotFound();

        $kelasLain = $this->createClass($data['dosen'], $data['tahun'], 'B');
        $tugasKelasLain = $this->createTask($kelasLain, now()->subDay(), now()->addDay());

        $this->actingAs($data['user'])
            ->post(route('mahasiswa.tugas.pengumpulan.simpan', $tugasKelasLain->id), [
                'lampiran' => $this->jawaban(),
            ])
            ->assertNotFound();

        $this->assertSame(0, PengumpulanTugas::count());
        $this->assertSame([], Storage::disk('local')->allFiles());

        $pengumpulanSaya = $this->buatPengumpulan($tugasSaya, $data['enrollment'], 'milik-saya.pdf');

        $this->actingAs($mahasiswaLain)
            ->get(route('mahasiswa.tugas.pengumpulan.unduh', $tugasSaya->id))
            ->assertNotFound();

        Storage::disk('local')->assertExists($pengumpulanSaya->file_path);
    }

    public function test_file_yang_masih_direferensikan_tidak_dihapus_saat_cleanup(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());
        $pengumpulan = $this->buatPengumpulan($tugas, $data['enrollment'], 'referensi.pdf');
        $path = $pengumpulan->file_path;

        $service = app(PengumpulanTugasService::class);
        $method = new ReflectionMethod(PengumpulanTugasService::class, 'hapusFileJikaAman');
        $method->setAccessible(true);

        $result = $method->invoke($service, $path, $data['class']->id, 'simulasi ambiguous commit');

        $this->assertFalse($result);
        Storage::disk('local')->assertExists($path);
    }

    public function test_cleanup_tidak_menghapus_file_ketika_database_tidak_dapat_diperiksa(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());
        $pengumpulan = $this->buatPengumpulan($tugas, $data['enrollment'], 'db-down.pdf');
        $path = $pengumpulan->file_path;

        $resolverAwal = PengumpulanTugas::getConnectionResolver();
        $resolver = Mockery::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('connection')
            ->andThrow(new \RuntimeException('Database tidak dapat dihubungi.'));

        PengumpulanTugas::setConnectionResolver($resolver);

        try {
            $service = app(PengumpulanTugasService::class);
            $method = new ReflectionMethod(PengumpulanTugasService::class, 'hapusFileJikaAman');
            $method->setAccessible(true);

            $result = $method->invoke($service, $path, $data['class']->id, 'database tidak dapat dihubungi');

            $this->assertFalse($result);
            Storage::disk('local')->assertExists($path);
        } finally {
            PengumpulanTugas::setConnectionResolver($resolverAwal);
        }
    }

    public function test_cleanup_setelah_duplicate_key_tetap_melanjutkan_retry(): void
    {
        $this->assertCleanupSetelahKegagalan(
            $this->queryException('23000', 1062, 'Duplicate entry for key pengumpulan_tugas_unik'),
            true,
        );
    }

    public function test_cleanup_setelah_deadlock_tetap_melanjutkan_retry(): void
    {
        $this->assertCleanupSetelahKegagalan(
            $this->queryException('40001', 1213, 'Deadlock found when trying to get lock'),
            true,
        );
    }

    public function test_cleanup_setelah_kegagalan_database_tidak_di_retry(): void
    {
        $this->assertCleanupSetelahKegagalan(
            new \RuntimeException('Koneksi database terputus.'),
            false,
        );
    }

    public function test_regresi_halaman_tugas_dashboard_dan_kalender_tetap_berfungsi(): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());
        $pengumpulan = $this->buatPengumpulan($tugas, $data['enrollment'], 'regresi.pdf');

        $tasksPage = $this->actingAs($data['user'])->get(route('mahasiswa.tugas'));

        $tasksPage->assertOk()
            ->assertSee('Kirim ulang')
            ->assertSee($tugas->judul)
            ->assertSee('Unduh lampiran saya');

        $taskData = collect($tasksPage->viewData('tasks'))->firstWhere('id', $tugas->id);

        $this->assertNotNull($taskData);
        $this->assertSame($pengumpulan->id, $taskData['pengumpulan']['id']);
        $this->assertTrue($taskData['pengumpulan']['bisa_resubmit']);
        $this->assertSame(
            route('mahasiswa.tugas.pengumpulan.unduh', $tugas->id),
            $taskData['pengumpulan']['unduh_url'],
        );

        $dashboard = $this->actingAs($data['user'])->get(route('mahasiswa.dashboard'));

        $dashboard->assertOk()->assertSee($tugas->judul);
        $this->assertNotEmpty($dashboard->viewData('timeline')['bars']);
    }

    /**
     * @return array{user: User, dosen: User, class: KelasMataKuliah, tahun: TahunAkademik, enrollment: PesertaMataKuliah}
     */
    private function mahasiswaWithClass(): array
    {
        $dosen = User::factory()->dosen()->create();
        $tahun = $this->createTahunAkademik();
        $class = $this->createClass($dosen, $tahun, 'A');
        $this->nimUrutan++;
        $user = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Pengumpulan',
            'nim' => '2026'.str_pad((string) $this->nimUrutan, 4, '0', STR_PAD_LEFT),
        ]);
        $enrollment = $this->enroll($user, $class);

        return [
            'user' => $user,
            'dosen' => $dosen,
            'class' => $class,
            'tahun' => $tahun,
            'enrollment' => $enrollment,
        ];
    }

    private function createTahunAkademik(): TahunAkademik
    {
        $ada = TahunAkademik::query()
            ->where('tahun_mulai', 2026)
            ->where('tahun_selesai', 2027)
            ->where('semester', 'ganjil')
            ->first();

        if ($ada !== null) {
            return $ada;
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

        return $tahun;
    }

    private function createClass(User $dosen, TahunAkademik $tahun, string $kode): KelasMataKuliah
    {
        $this->urutan++;
        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MK-PG-'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Pengumpulan '.$this->urutan,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        $class = new KelasMataKuliah();
        $class->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kode.$this->urutan,
            'nama_kelas' => 'Kelas '.$kode.$this->urutan,
            'kapasitas' => 30,
        ])->save();

        return $class;
    }

    private function enroll(User $user, KelasMataKuliah $class): PesertaMataKuliah
    {
        $enrollment = new PesertaMataKuliah();
        $enrollment->forceFill([
            'kelas_mata_kuliah_id' => $class->id,
            'mahasiswa_id' => $user->id,
            'status' => StatusPeserta::Aktif->value,
            'tanggal_daftar' => now()->subWeek(),
        ])->save();

        return $enrollment;
    }

    private function createTask(
        KelasMataKuliah $class,
        ?Carbon $dipublikasikan,
        Carbon $deadline,
        bool $izinkanTerlambat = false,
    ): Tugas {
        $tugas = new Tugas();
        $tugas->forceFill([
            'kelas_mata_kuliah_id' => $class->id,
            'dibuat_oleh' => $class->dosen_id,
            'judul' => 'Tugas '.$class->kode_kelas,
            'deskripsi' => 'Deskripsi tugas pengumpulan.',
            'dipublikasikan_pada' => $dipublikasikan,
            'deadline' => $deadline,
            'izinkan_terlambat' => $izinkanTerlambat,
        ])->save();

        return $tugas;
    }

    private function jawaban(string $nama = 'jawaban.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $nama,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF\n",
        );
    }

    private function pathBaru(int $kelasId, string $nama): string
    {
        return sprintf(
            'pengumpulan/%d/2026/11/%s.%s',
            $kelasId,
            (string) Str::uuid(),
            pathinfo($nama, PATHINFO_EXTENSION),
        );
    }

    private function buatPengumpulan(
        Tugas $tugas,
        PesertaMataKuliah $enrollment,
        string $nama,
    ): PengumpulanTugas {
        $path = $this->pathBaru((int) $enrollment->kelas_mata_kuliah_id, $nama);
        Storage::disk('local')->put($path, '%PDF-1.4 isi awal', 'private');

        $pengumpulan = new PengumpulanTugas();
        $pengumpulan->forceFill([
            'tugas_id' => $tugas->id,
            'peserta_mata_kuliah_id' => $enrollment->id,
            'file_path' => $path,
            'file_name' => $nama,
            'file_mime' => 'application/pdf',
            'file_size' => 18,
            'catatan' => 'Catatan awal',
            'dikumpulkan_pada' => now()->subHour(),
            'terlambat' => false,
        ])->save();

        return $pengumpulan;
    }

    private function queryException(string $sqlState, int $kodeDriver, string $pesan): QueryException
    {
        $pdo = new PDOException($pesan, $kodeDriver);
        $pdo->errorInfo = [$sqlState, $kodeDriver, $pesan];

        return new QueryException('sqlite', 'insert into pengumpulan_tugas', [], $pdo);
    }

    private function assertCleanupSetelahKegagalan(Throwable $exception, bool $harusRetry): void
    {
        $data = $this->mahasiswaWithClass();
        $tugas = $this->createTask($data['class'], now()->subDay(), now()->addDay());
        $pathPertama = $this->pathBaru((int) $data['class']->id, 'pertama.pdf');
        $pathKedua = $this->pathBaru((int) $data['class']->id, 'kedua.pdf');

        Storage::disk('local')->put($pathPertama, '%PDF-1.4 pertama', 'private');
        Storage::disk('local')->put($pathKedua, '%PDF-1.4 kedua', 'private');

        $paths = [$pathPertama, $pathKedua];
        $file = Mockery::mock(PengumpulanFileService::class)->makePartial();
        $file->shouldReceive('simpan')
            ->times($harusRetry ? 2 : 1)
            ->andReturnUsing(function () use (&$paths): array {
                $path = array_shift($paths);

                return [
                    'path' => $path,
                    'file_name' => basename($path),
                    'file_mime' => 'application/pdf',
                    'file_size' => 15,
                ];
            });

        $service = new PengumpulanTugasService($file);
        $sudahGagal = false;
        $event = 'eloquent.saved: '.PengumpulanTugas::class;
        $listener = function () use (&$sudahGagal, $exception): void {
            if (! $sudahGagal) {
                $sudahGagal = true;
                throw $exception;
            }
        };

        Event::listen($event, $listener);

        try {
            if ($harusRetry) {
                $pengumpulan = $service->simpan($data['user']->id, $tugas->id, $this->jawaban(), null);

                $this->assertSame($pathKedua, $pengumpulan->file_path);
                $this->assertSame(1, PengumpulanTugas::count());
                Storage::disk('local')->assertMissing($pathPertama);
                Storage::disk('local')->assertExists($pathKedua);
            } else {
                try {
                    $service->simpan($data['user']->id, $tugas->id, $this->jawaban(), null);
                    $this->fail('Exception transaksi utama harus dilempar kembali.');
                } catch (Throwable $thrown) {
                    $this->assertSame($exception, $thrown);
                }

                $this->assertSame(0, PengumpulanTugas::count());
                Storage::disk('local')->assertMissing($pathPertama);
                Storage::disk('local')->assertExists($pathKedua);
            }
        } finally {
            Event::forget($event);
        }
    }
}
