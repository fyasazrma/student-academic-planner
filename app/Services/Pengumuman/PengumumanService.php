<?php

namespace App\Services\Pengumuman;

use App\Enums\JenisAgenda;
use App\Enums\JenisNotifikasi;
use App\Enums\StatusPublikasi;
use App\Enums\StatusPeserta;
use App\Support\KelayakanPeserta;
use App\Enums\UserRole;
use App\Models\AgendaAkademik;
use App\Models\KelasMataKuliah;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
use App\Services\Notifikasi\NotifikasiService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pengumuman memakai satu sumber data: agenda_akademik dengan jenis=pengumuman.
 * Kelas_mata_kuliah_id NULL berarti pengumuman akademik global; selain itu
 * pengumuman ditujukan untuk kelas tertentu. Status publikasi tercermin dari
 * dipublikasikan_pada (NULL = draft, waktu lampau = terbit, waktu depan =
 * terjadwal).
 */
class PengumumanService
{
    private const PER_HALAMAN = 10;

    public function __construct(private readonly NotifikasiService $notifikasi)
    {
    }

    /**
     * Daftar untuk admin (semua pengumuman) atau dosen (hanya yang dibuatnya
     * pada kelas yang diajar).
     *
     * @param  array<string, mixed>  $filters
     */
    public function daftar(string $role, int $userId, array $filters): LengthAwarePaginator
    {
        Carbon::setLocale('id');

        $query = $this->queryPengelola($role, $userId);
        $this->terapkanFilter($query, $filters);

        return $query->with(['pembuat', 'kelasMataKuliah.mataKuliah'])
            ->latest('dipublikasikan_pada')
            ->latest('created_at')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();
    }

    /**
     * Daftar pengumuman yang berhak dilihat mahasiswa: sudah dipublikasikan,
     * belum kedaluwarsa, dan ditujukan global atau ke kelas yang diikuti.
     *
     * @param  array<string, mixed>  $filters
     */
    public function daftarMahasiswa(int $userId, array $filters = []): LengthAwarePaginator
    {
        Carbon::setLocale('id');

        $query = $this->queryMahasiswa($userId);
        if (($filters['q'] ?? null) !== null && trim((string) $filters['q']) !== '') {
            $this->cari($query, (string) $filters['q']);
        }

        return $query->with(['pembuat', 'kelasMataKuliah.mataKuliah'])
            ->latest('dipublikasikan_pada')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();
    }

    public function temukan(string $role, int $userId, int $id): AgendaAkademik
    {
        $query = $role === UserRole::Admin->value
            ? AgendaAkademik::query()
            : $this->queryPengelola(UserRole::Dosen->value, $userId);

        return $query->with(['pembuat', 'kelasMataKuliah.mataKuliah'])
            ->where('jenis', JenisAgenda::Pengumuman->value)
            ->findOrFail($id);
    }

    public function temukanUntukMahasiswa(int $userId, int $id): AgendaAkademik
    {
        return $this->queryMahasiswa($userId)
            ->with(['pembuat', 'kelasMataKuliah.mataKuliah'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpan(string $role, int $userId, array $data, ?int $id = null): AgendaAkademik
    {
        $kelasId = $this->kelasTersedia($role, $userId, $data);
        $tanggalMulai = Carbon::createFromFormat('!Y-m-d', (string) ($data['tanggal_mulai'] ?? ''));
        if (! $tanggalMulai instanceof Carbon) {
            throw new \InvalidArgumentException('Tanggal mulai tidak valid.');
        }

        $tanggalSelesai = null;
        if (! empty($data['tanggal_selesai'])) {
            $tanggalSelesai = Carbon::createFromFormat('!Y-m-d', (string) $data['tanggal_selesai']);
            if (! $tanggalSelesai instanceof Carbon || $tanggalSelesai->lessThan($tanggalMulai)) {
                throw new \InvalidArgumentException('Tanggal berakhir tidak boleh sebelum tanggal mulai.');
            }
        }

        $publikasi = $this->waktuPublikasi($data);

        $result = DB::transaction(function () use ($role, $userId, $data, $kelasId, $tanggalMulai, $tanggalSelesai, $publikasi, $id): AgendaAkademik {
            $record = $id === null ? null : $this->temukan($role, $userId, $id);
            $baru = $record === null;
            $duluPublikasi = $record?->dipublikasikan_pada?->copy();

            $atribut = [
                'judul' => trim((string) $data['judul']),
                'deskripsi' => trim((string) $data['isi']),
                'jenis' => JenisAgenda::Pengumuman->value,
                'jenis_ujian' => null,
                'tahun_akademik_id' => null,
                'kelas_mata_kuliah_id' => $kelasId,
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_selesai' => $tanggalSelesai?->toDateString(),
                'waktu_mulai' => null,
                'waktu_selesai' => null,
                'lokasi' => null,
                'dipublikasikan_pada' => $publikasi?->toDateTimeString(),
            ];

            if ($record === null) {
                $record = new AgendaAkademik();
                $record->forceFill($atribut + ['dibuat_oleh' => $userId])->save();
            } else {
                $record->fill($atribut)->save();
            }

            if ($publikasi !== null && $publikasi->lessThanOrEqualTo(now()) && ($baru || $duluPublikasi === null)) {
                DB::afterCommit(fn () => $this->kirimNotifikasi($record, $userId));
            }

            return $record;
        });

        return $result;
    }

    public function hapus(string $role, int $userId, int $id): void
    {
        DB::transaction(function () use ($role, $userId, $id): void {
            $record = $this->temukan($role, $userId, $id);
            $record->delete();
            DB::afterCommit(fn (): int => $this->notifikasi->hapusBersumber($this->kunciSumber($id)));
        });
    }

    /**
     * Publikasi otomatis pengumuman terjadwal yang sudah mencapai waktunya.
     *
     * Tidak ada kolom status baru: penanda "sudah diberitahu" memakai dedup
     * notifikasi pada kunci sumber `pengumuman:{id}`. Karena itu proses aman
     * dijalankan berulang kali dan tidak perlu migration baru.
     *
     * @return array{diproses: int, notifikasi: int, gagal: int}
     */
    public function terbitkanYangJatuhTempo(): array
    {
        $kandidat = AgendaAkademik::query()
            ->where('jenis', JenisAgenda::Pengumuman->value)
            ->whereNotNull('dipublikasikan_pada')
            ->where('dipublikasikan_pada', '<=', now())
            ->orderBy('dipublikasikan_pada')
            ->get();

        $diproses = 0;
        $notifikasi = 0;
        $gagal = 0;

        foreach ($kandidat as $item) {
            try {
                $baru = DB::transaction(function () use ($item): bool {
                    $record = AgendaAkademik::query()
                        ->whereKey($item->getKey())
                        ->lockForUpdate()
                        ->first();

                    if ($record === null || $this->sudahDiberitahu($record)) {
                        return false;
                    }

                    DB::afterCommit(function () use ($record): void {
                        $this->kirimNotifikasi($record, (int) ($record->dibuat_oleh ?? 0));
                    });

                    return true;
                });
            } catch (Throwable $exception) {
                report($exception);
                $gagal++;

                continue;
            }

            if (! $baru) {
                continue;
            }

            $diproses++;
            $notifikasi += Notifikasi::query()
                ->where('tipe', JenisNotifikasi::Pengumuman->value)
                ->where('data->sumber', $this->kunciSumber((int) $item->getKey()))
                ->count();
        }

        return [
            'diproses' => $diproses,
            'notifikasi' => $notifikasi,
            'gagal' => $gagal,
        ];
    }

    private function sudahDiberitahu(AgendaAkademik $record): bool
    {
        return Notifikasi::query()
            ->where('tipe', JenisNotifikasi::Pengumuman->value)
            ->where('data->sumber', $this->kunciSumber((int) $record->getKey()))
            ->exists();
    }

    private function kirimNotifikasi(AgendaAkademik $record, ?int $aktorId = null): void
    {
        $judul = 'Pengumuman baru: '.$record->judul;
        $ringkasan = Str::limit(strip_tags($record->deskripsi ?? ''), 180);
        $id = (int) $record->getKey();
        $sumber = $this->kunciSumber($id);

        if ($record->kelas_mata_kuliah_id === null) {
            $this->notifikasi->kirimKeSemuaMahasiswa(
                JenisNotifikasi::Pengumuman,
                $judul,
                $ringkasan,
                '/mahasiswa/pengumuman/'.$id,
                $sumber,
                $aktorId,
            );
            $this->notifikasi->kirimKeSemuaAdmin(
                JenisNotifikasi::Pengumuman,
                $judul,
                $ringkasan,
                '/admin/pengumuman/'.$id,
                $sumber,
                $aktorId,
            );
            $this->notifikasi->kirimKeSemuaDosen(
                JenisNotifikasi::Pengumuman,
                $judul,
                $ringkasan,
                '/dosen/pengumuman/'.$id,
                $sumber,
                $aktorId,
            );

            return;
        }

        $this->notifikasi->kirimKeKelas(
            (int) $record->kelas_mata_kuliah_id,
            JenisNotifikasi::Pengumuman,
            $judul,
            $ringkasan,
            '/mahasiswa/pengumuman/'.$id,
            $sumber,
            $aktorId,
        );
        $this->notifikasi->kirimKeDosenPengampu(
            (int) $record->kelas_mata_kuliah_id,
            JenisNotifikasi::Pengumuman,
            $judul,
            $ringkasan,
            '/dosen/pengumuman/'.$id,
            $sumber,
            $aktorId,
        );
    }

    private function kunciSumber(int $id): string
    {
        return 'pengumuman:'.$id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function kelasTersedia(string $role, int $userId, array $data): ?int
    {
        $kelasId = ! empty($data['kelas_mata_kuliah_id']) ? (int) $data['kelas_mata_kuliah_id'] : null;

        if ($role === UserRole::Dosen->value) {
            if ($kelasId === null) {
                throw new \InvalidArgumentException('Dosen harus memilih kelas yang diajar.');
            }

            $dimiliki = KelasMataKuliah::query()
                ->whereKey($kelasId)
                ->where('dosen_id', $userId)
                ->exists();

            if (! $dimiliki) {
                throw new \InvalidArgumentException('Kelas tidak ditemukan atau bukan kelas yang Anda ampu.');
            }

            return $kelasId;
        }

        if ($kelasId !== null && ! KelasMataKuliah::query()->whereKey($kelasId)->exists()) {
            throw new \InvalidArgumentException('Kelas tidak valid.');
        }

        return $kelasId;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function waktuPublikasi(array $data): ?Carbon
    {
        $status = StatusPublikasi::tryFrom((string) ($data['status'] ?? ''));
        if ($status === StatusPublikasi::Draft) {
            return null;
        }

        if ($status === StatusPublikasi::Terjadwal && ! empty($data['tanggal_publikasi'])) {
            $waktu = Carbon::createFromFormat('Y-m-d\TH:i', (string) $data['tanggal_publikasi']);

            return $waktu instanceof Carbon ? $waktu : null;
        }

        return now();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function terapkanFilter(Builder $query, array $filters): void
    {
        $status = $filters['status'] ?? 'semua';
        if ($status === 'draft') {
            $query->whereNull('dipublikasikan_pada');
        } elseif ($status === 'terbit') {
            $query->whereNotNull('dipublikasikan_pada')
                ->where('dipublikasikan_pada', '<=', now());
        } elseif ($status === 'terjadwal') {
            $query->whereNotNull('dipublikasikan_pada')
                ->where('dipublikasikan_pada', '>', now());
        }

        if (($filters['q'] ?? null) !== null && trim((string) $filters['q']) !== '') {
            $this->cari($query, (string) $filters['q']);
        }
    }

    private function cari(Builder $query, string $kataKunci): void
    {
        $kata = '%'.trim($kataKunci).'%';
        $query->where(function (Builder $query) use ($kata): void {
            $query->where('judul', 'like', $kata)
                ->orWhere('deskripsi', 'like', $kata);
        });
    }

    private function queryPengelola(string $role, int $userId): Builder
    {
        $query = AgendaAkademik::query()->where('jenis', JenisAgenda::Pengumuman->value);

        if ($role === UserRole::Dosen->value) {
            $query->where('dibuat_oleh', $userId)
                ->whereHas('kelasMataKuliah', fn (Builder $query): Builder => $query->where('dosen_id', $userId));
        }

        return $query;
    }

    private function queryMahasiswa(int $userId): Builder
    {
        $kelasIds = PesertaMataKuliah::query()
            ->where('mahasiswa_id', $userId)
            ->whereIn('status', \App\Support\KelayakanPeserta::nilaiStatusBerhak())
            ->pluck('kelas_mata_kuliah_id');

        return AgendaAkademik::query()
            ->where('jenis', JenisAgenda::Pengumuman->value)
            ->whereNotNull('dipublikasikan_pada')
            ->where('dipublikasikan_pada', '<=', now())
            ->where(function (Builder $query): void {
                $query->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            })
            ->where(function (Builder $query) use ($kelasIds): void {
                $query->whereNull('kelas_mata_kuliah_id');
                if ($kelasIds->isNotEmpty()) {
                    $query->orWhereIn('kelas_mata_kuliah_id', $kelasIds->all());
                }
            });
    }
}
