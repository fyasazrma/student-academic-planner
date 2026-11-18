<?php

namespace App\Services\Admin;

use App\Models\MataKuliah;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Aturan bisnis dan operasi database untuk data induk mata kuliah admin.
 *
 * Service ini tidak pernah menerima status dari request. Status awal selalu
 * aktif, dan perubahan status hanya terjadi melalui ubahStatus().
 */
class AdminMataKuliahService
{
    public const PER_HALAMAN = 10;

    public const HASIL_BERHASIL = 'berhasil';

    public const HASIL_KODE_DUPLIKAT = 'kode_duplikat';

    public const HASIL_MEMILIKI_KELAS = 'memiliki_kelas';

    public const HASIL_FOREIGN_KEY = 'foreign_key';

    public const HASIL_TIDAK_DITEMUKAN = 'tidak_ditemukan';

    /**
     * @param  mixed  $kataKunci
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, array<string, mixed>>
     */
    public function daftar(mixed $kataKunci): LengthAwarePaginator
    {
        $kataKunci = is_string($kataKunci) ? trim($kataKunci) : '';

        $query = MataKuliah::query()
            ->withCount('kelasMataKuliah as jumlah_kelas')
            ->when($kataKunci !== '', function ($cari) use ($kataKunci): void {
                $cari->where(function ($pencarian) use ($kataKunci): void {
                    $pencarian
                        ->where('kode', 'like', '%'.$kataKunci.'%')
                        ->orWhere('nama', 'like', '%'.$kataKunci.'%');
                });
            })
            ->orderBy('kode');

        $paginator = $query->paginate(self::PER_HALAMAN);
        $paginator->appends($kataKunci === '' ? [] : ['q' => $kataKunci]);

        return $paginator->through(function (MataKuliah $mataKuliah): array {
            return [
                'id' => $mataKuliah->id,
                'kode' => $mataKuliah->kode,
                'nama' => $mataKuliah->nama,
                'sks' => $mataKuliah->sks,
                'program_studi' => $mataKuliah->program_studi,
                'semester' => $mataKuliah->semester,
                'aktif' => $mataKuliah->aktif,
                'status_label' => $mataKuliah->aktif ? 'Aktif' : 'Nonaktif',
                'jumlah_kelas' => (int) ($mataKuliah->jumlah_kelas ?? 0),
            ];
        });
    }

    /**
     * Data eksplisit untuk form tambah/edit. Tidak menyertakan `aktif`.
     *
     * @return array<string, mixed>
     */
    public function dataFormulir(?MataKuliah $mataKuliah = null): array
    {
        return [
            'kode' => $mataKuliah?->kode ?? '',
            'nama' => $mataKuliah?->nama ?? '',
            'sks' => $mataKuliah?->sks,
            'deskripsi' => $mataKuliah?->deskripsi,
            'program_studi' => $mataKuliah?->program_studi,
            'semester' => $mataKuliah?->semester,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, kode: string, pesan: string, mata_kuliah: ?MataKuliah}
     */
    public function simpan(array $data): array
    {
        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill(array_merge($this->atributData($data), ['aktif' => true]));

        try {
            $mataKuliah->save();
        } catch (QueryException $exception) {
            if ($this->adalahDuplikatKode($exception)) {
                return $this->hasil(
                    self::HASIL_KODE_DUPLIKAT,
                    'Kode mata kuliah sudah digunakan. Gunakan kode lain.',
                );
            }

            throw $exception;
        }

        return $this->hasil(
            self::HASIL_BERHASIL,
            'Mata kuliah '.$mataKuliah->kode.' berhasil ditambahkan.',
            $mataKuliah,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, kode: string, pesan: string, mata_kuliah: ?MataKuliah}
     */
    public function perbarui(MataKuliah $mataKuliah, array $data): array
    {
        $mataKuliah->forceFill($this->atributData($data));

        try {
            $mataKuliah->save();
        } catch (QueryException $exception) {
            if ($this->adalahDuplikatKode($exception)) {
                return $this->hasil(
                    self::HASIL_KODE_DUPLIKAT,
                    'Kode mata kuliah sudah digunakan. Gunakan kode lain.',
                );
            }

            throw $exception;
        }

        return $this->hasil(
            self::HASIL_BERHASIL,
            'Mata kuliah '.$mataKuliah->kode.' berhasil diperbarui.',
            $mataKuliah,
        );
    }

    /**
     * Status hanya dapat diubah melalui endpoint status.
     *
     * @return array{berhasil: bool, kode: string, pesan: string, mata_kuliah: ?MataKuliah}
     */
    public function ubahStatus(MataKuliah $mataKuliah, bool $aktif): array
    {
        return DB::transaction(function () use ($mataKuliah, $aktif): array {
            $terkunci = MataKuliah::query()
                ->whereKey($mataKuliah->getKey())
                ->lockForUpdate()
                ->first();

            if ($terkunci === null) {
                return $this->hasil(
                    self::HASIL_TIDAK_DITEMUKAN,
                    'Mata kuliah tidak ditemukan.',
                );
            }

            $terkunci->forceFill(['aktif' => $aktif])->save();

            return $this->hasil(
                self::HASIL_BERHASIL,
                'Mata kuliah '.$terkunci->kode.' berhasil '.($aktif ? 'diaktifkan' : 'dinonaktifkan').'.',
                $terkunci,
            );
        });
    }

    /**
     * @return array{berhasil: bool, kode: string, pesan: string, mata_kuliah: ?MataKuliah}
     */
    public function hapus(MataKuliah $mataKuliah): array
    {
        return DB::transaction(function () use ($mataKuliah): array {
            $terkunci = MataKuliah::query()
                ->whereKey($mataKuliah->getKey())
                ->lockForUpdate()
                ->first();

            if ($terkunci === null) {
                return $this->hasil(
                    self::HASIL_TIDAK_DITEMUKAN,
                    'Mata kuliah tidak ditemukan.',
                );
            }

            if ($terkunci->kelasMataKuliah()->exists()) {
                return $this->hasil(
                    self::HASIL_MEMILIKI_KELAS,
                    'Mata kuliah tidak dapat dihapus karena masih memiliki kelas.',
                );
            }

            $kode = $terkunci->kode;

            try {
                $terkunci->delete();
            } catch (QueryException $exception) {
                if ($this->adalahPelanggaranForeignKey($exception)) {
                    return $this->hasil(
                        self::HASIL_FOREIGN_KEY,
                        'Mata kuliah tidak dapat dihapus karena masih digunakan oleh data lain.',
                    );
                }

                throw $exception;
            }

            return $this->hasil(
                self::HASIL_BERHASIL,
                'Mata kuliah '.$kode.' berhasil dihapus.',
            );
        });
    }

    /**
     * Hanya field yang diizinkan yang diteruskan ke model.
     * Status sengaja tidak termasuk di sini.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function atributData(array $data): array
    {
        return [
            'kode' => Str::upper(trim((string) ($data['kode'] ?? ''))),
            'nama' => trim((string) ($data['nama'] ?? '')),
            'sks' => (int) ($data['sks'] ?? 0),
            'deskripsi' => $this->teksNullable($data['deskripsi'] ?? null),
            'program_studi' => $this->teksNullable($data['program_studi'] ?? null),
            'semester' => isset($data['semester']) ? (int) $data['semester'] : null,
        ];
    }

    private function teksNullable(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $teks = trim((string) $nilai);

        return $teks === '' ? null : $teks;
    }

    /**
     * @return array{berhasil: bool, kode: string, pesan: string, mata_kuliah: ?MataKuliah}
     */
    private function hasil(string $kode, string $pesan, ?MataKuliah $mataKuliah = null): array
    {
        return [
            'berhasil' => $kode === self::HASIL_BERHASIL,
            'kode' => $kode,
            'pesan' => $pesan,
            'mata_kuliah' => $mataKuliah,
        ];
    }

    private function adalahDuplikatKode(QueryException $exception): bool
    {
        if (($exception->errorInfo[0] ?? null) !== '23000') {
            return false;
        }

        $pesan = strtolower($exception->getMessage());

        return str_contains($pesan, 'mata_kuliah')
            && str_contains($pesan, 'kode')
            && (str_contains($pesan, 'unique') || str_contains($pesan, 'duplicate'));
    }

    private function adalahPelanggaranForeignKey(QueryException $exception): bool
    {
        if (($exception->errorInfo[1] ?? null) === 1451) {
            return true;
        }

        if (($exception->errorInfo[0] ?? null) !== '23000') {
            return false;
        }

        $pesan = strtolower($exception->getMessage());

        return str_contains($pesan, 'foreign key')
            || str_contains($pesan, 'kelas_mata_kuliah');
    }
}
