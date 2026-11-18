<?php

namespace App\Services\Admin;

use App\Models\TahunAkademik;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bisnis dan operasi database untuk tahun akademik admin.
 *
 * Status awal selalu nonaktif. Perubahan status hanya terjadi melalui
 * ubahStatus(), yang mengunci seluruh periode agar hanya ada satu periode
 * aktif setelah transaksi selesai.
 */
class AdminTahunAkademikService
{
    public const PER_HALAMAN = 10;

    public const HASIL_BERHASIL = 'berhasil';

    public const HASIL_PERIODE_DUPLIKAT = 'periode_duplikat';

    public const HASIL_IDENTITAS_TERKUNCI = 'identitas_terkunci';

    public const HASIL_MEMILIKI_KELAS = 'memiliki_kelas';

    public const HASIL_FOREIGN_KEY = 'foreign_key';

    public const HASIL_TIDAK_DITEMUKAN = 'tidak_ditemukan';

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, array<string, mixed>>
     */
    public function daftar(): LengthAwarePaginator
    {
        return TahunAkademik::query()
            ->withCount('kelasMataKuliah as jumlah_kelas')
            ->orderByDesc('tahun_mulai')
            ->orderByDesc('id')
            ->paginate(self::PER_HALAMAN)
            ->through(function (TahunAkademik $tahunAkademik): array {
                return [
                    'id' => $tahunAkademik->id,
                    'label' => $tahunAkademik->label(),
                    'tahun_mulai' => (int) $tahunAkademik->tahun_mulai,
                    'tahun_selesai' => (int) $tahunAkademik->tahun_selesai,
                    'semester' => $tahunAkademik->semester?->value ?? '',
                    'semester_label' => ucfirst($tahunAkademik->semester?->value ?? ''),
                    'tanggal_mulai_label' => $tahunAkademik->tanggal_mulai?->translatedFormat('d M Y') ?? '-',
                    'tanggal_selesai_label' => $tahunAkademik->tanggal_selesai?->translatedFormat('d M Y') ?? '-',
                    'aktif' => $tahunAkademik->aktif,
                    'status_label' => $tahunAkademik->aktif ? 'Aktif' : 'Nonaktif',
                    'jumlah_kelas' => (int) ($tahunAkademik->jumlah_kelas ?? 0),
                ];
            });
    }

    /**
     * Data eksplisit untuk form tambah/edit. Tidak menyertakan `aktif`.
     *
     * @return array<string, mixed>
     */
    public function dataFormulir(?TahunAkademik $tahunAkademik = null): array
    {
        return [
            'tahun_mulai' => $tahunAkademik?->tahun_mulai ?? '',
            'tahun_selesai' => $tahunAkademik?->tahun_selesai ?? '',
            'semester' => $tahunAkademik?->semester?->value ?? '',
            'tanggal_mulai' => $tahunAkademik?->tanggal_mulai?->toDateString() ?? '',
            'tanggal_selesai' => $tahunAkademik?->tanggal_selesai?->toDateString() ?? '',
        ];
    }

    /**
     * Periode baru selalu dibuat nonaktif, terlepas dari input request.
     *
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, kode: string, pesan: string, tahun_akademik: ?TahunAkademik, field: ?string}
     */
    public function simpan(array $data): array
    {
        $tahunAkademik = new TahunAkademik();
        $tahunAkademik->forceFill(array_merge($this->atributData($data), ['aktif' => false]));

        try {
            $tahunAkademik->save();
        } catch (QueryException $exception) {
            if ($this->adalahDuplikatPeriode($exception)) {
                return $this->hasil(
                    self::HASIL_PERIODE_DUPLIKAT,
                    'Periode tahun akademik ini sudah ada.',
                    null,
                    'tahun_mulai',
                );
            }

            throw $exception;
        }

        return $this->hasil(
            self::HASIL_BERHASIL,
            'Tahun akademik '.$tahunAkademik->label().' berhasil ditambahkan.',
            $tahunAkademik,
        );
    }

    /**
     * Identitas periode tidak boleh diubah bila sudah dipakai kelas.
     * Tanggal mulai/selesai tetap dapat diperbarui.
     *
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, kode: string, pesan: string, tahun_akademik: ?TahunAkademik, field: ?string}
     */
    public function perbarui(TahunAkademik $tahunAkademik, array $data): array
    {
        return DB::transaction(function () use ($tahunAkademik, $data): array {
            $terkunci = TahunAkademik::query()
                ->whereKey($tahunAkademik->getKey())
                ->lockForUpdate()
                ->first();

            if ($terkunci === null) {
                return $this->hasil(
                    self::HASIL_TIDAK_DITEMUKAN,
                    'Tahun akademik tidak ditemukan.',
                    null,
                    'tahun_mulai',
                );
            }

            if ($terkunci->kelasMataKuliah()->exists()) {
                $field = $this->fieldIdentitasBerubah($terkunci, $data);

                if ($field !== null) {
                    return $this->hasil(
                        self::HASIL_IDENTITAS_TERKUNCI,
                        'Identitas periode tidak dapat diubah karena tahun akademik ini sudah digunakan kelas.',
                        null,
                        $field,
                    );
                }
            }

            $terkunci->forceFill($this->atributData($data));

            try {
                $terkunci->save();
            } catch (QueryException $exception) {
                if ($this->adalahDuplikatPeriode($exception)) {
                    return $this->hasil(
                        self::HASIL_PERIODE_DUPLIKAT,
                        'Periode tahun akademik ini sudah ada.',
                        null,
                        'tahun_mulai',
                    );
                }

                throw $exception;
            }

            return $this->hasil(
                self::HASIL_BERHASIL,
                'Tahun akademik '.$terkunci->label().' berhasil diperbarui.',
                $terkunci,
            );
        });
    }

    /**
     * Satu-satunya endpoint yang boleh mengubah status aktif.
     *
     * Seluruh baris dikunci dengan urutan deterministik sebelum status
     * diubah. Dengan demikian dua aktivasi bersamaan diproses berurutan dan
     * tidak mungkin menghasilkan lebih dari satu periode aktif.
     *
     * @return array{berhasil: bool, kode: string, pesan: string, tahun_akademik: ?TahunAkademik, field: ?string}
     */
    public function ubahStatus(TahunAkademik $tahunAkademik, bool $aktif): array
    {
        return DB::transaction(function () use ($tahunAkademik, $aktif): array {
            $semua = TahunAkademik::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $terkunci = $semua->first(
                fn (TahunAkademik $item): bool => $item->getKey() === $tahunAkademik->getKey(),
            );

            if ($terkunci === null) {
                return $this->hasil(
                    self::HASIL_TIDAK_DITEMUKAN,
                    'Tahun akademik tidak ditemukan.',
                );
            }

            $dinonaktifkan = 0;

            if ($aktif) {
                foreach ($semua as $item) {
                    if ($item->getKey() === $terkunci->getKey() || ! $item->aktif) {
                        continue;
                    }

                    $item->forceFill(['aktif' => false])->save();
                    $dinonaktifkan++;
                }
            }

            $terkunci->forceFill(['aktif' => $aktif])->save();

            $pesan = $aktif
                ? 'Tahun akademik '.$terkunci->label().' berhasil diaktifkan.'
                    .($dinonaktifkan > 0 ? ' Periode aktif sebelumnya telah dinonaktifkan.' : '')
                : 'Tahun akademik '.$terkunci->label().' berhasil dinonaktifkan.';

            return $this->hasil(
                self::HASIL_BERHASIL,
                $pesan,
                $terkunci,
            );
        });
    }

    /**
     * @return array{berhasil: bool, kode: string, pesan: string, tahun_akademik: ?TahunAkademik, field: ?string}
     */
    public function hapus(TahunAkademik $tahunAkademik): array
    {
        return DB::transaction(function () use ($tahunAkademik): array {
            $terkunci = TahunAkademik::query()
                ->whereKey($tahunAkademik->getKey())
                ->lockForUpdate()
                ->first();

            if ($terkunci === null) {
                return $this->hasil(
                    self::HASIL_TIDAK_DITEMUKAN,
                    'Tahun akademik tidak ditemukan.',
                );
            }

            if ($terkunci->kelasMataKuliah()->exists()) {
                return $this->hasil(
                    self::HASIL_MEMILIKI_KELAS,
                    'Tahun akademik tidak dapat dihapus karena masih memiliki kelas.',
                );
            }

            $label = $terkunci->label();

            try {
                $terkunci->delete();
            } catch (QueryException $exception) {
                if ($this->adalahPelanggaranForeignKey($exception)) {
                    return $this->hasil(
                        self::HASIL_FOREIGN_KEY,
                        'Tahun akademik tidak dapat dihapus karena masih digunakan oleh data lain.',
                    );
                }

                throw $exception;
            }

            return $this->hasil(
                self::HASIL_BERHASIL,
                'Tahun akademik '.$label.' berhasil dihapus.',
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
            'tahun_mulai' => (int) ($data['tahun_mulai'] ?? 0),
            'tahun_selesai' => (int) ($data['tahun_selesai'] ?? 0),
            'semester' => trim((string) ($data['semester'] ?? '')),
            'tanggal_mulai' => trim((string) ($data['tanggal_mulai'] ?? '')),
            'tanggal_selesai' => trim((string) ($data['tanggal_selesai'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fieldIdentitasBerubah(TahunAkademik $tahunAkademik, array $data): ?string
    {
        if ((int) $tahunAkademik->tahun_mulai !== (int) ($data['tahun_mulai'] ?? 0)) {
            return 'tahun_mulai';
        }

        if ((int) $tahunAkademik->tahun_selesai !== (int) ($data['tahun_selesai'] ?? 0)) {
            return 'tahun_selesai';
        }

        if (($tahunAkademik->semester?->value ?? '') !== trim((string) ($data['semester'] ?? ''))) {
            return 'semester';
        }

        return null;
    }

    /**
     * @return array{berhasil: bool, kode: string, pesan: string, tahun_akademik: ?TahunAkademik, field: ?string}
     */
    private function hasil(
        string $kode,
        string $pesan,
        ?TahunAkademik $tahunAkademik = null,
        ?string $field = null,
    ): array {
        return [
            'berhasil' => $kode === self::HASIL_BERHASIL,
            'kode' => $kode,
            'pesan' => $pesan,
            'tahun_akademik' => $tahunAkademik,
            'field' => $field,
        ];
    }

    private function adalahDuplikatPeriode(QueryException $exception): bool
    {
        if (($exception->errorInfo[0] ?? null) !== '23000') {
            return false;
        }

        $pesan = strtolower($exception->getMessage());

        if (str_contains($pesan, 'tahun_akademik_periode_unique')) {
            return true;
        }

        return str_contains($pesan, 'tahun_akademik')
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
