<?php

namespace App\Services\Dosen;

use App\Enums\StatusPendaftaranKelas;
use App\Enums\StatusPeserta;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use App\Services\Kelas\KunciGabungKelasService;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

/**
 * Data dan aksi Mata Kuliah Saya untuk dosen.
 *
 * Service ini tidak pernah mempercayai identitas pemilik kelas dari request.
 * Setiap aksi menerima $dosenId dari server dan memakai scoped query sehingga
 * kelas dosen lain tidak dapat disentuh lewat manipulasi ID URL.
 */
class MataKuliahService
{
    public const PER_HALAMAN = 12;

    public const HASIL_BERHASIL = 'berhasil';

    public const HASIL_TIDAK_DITEMUKAN = 'tidak_ditemukan';

    public const HASIL_DATA_TIDAK_TERSEDIA = 'data_tidak_tersedia';

    public const HASIL_KODE_DUPLIKAT = 'kode_duplikat';

    public const HASIL_KAPASITAS_KURANG = 'kapasitas_kurang';

    public const HASIL_KUNCI_BELUM_ADA = 'kunci_belum_ada';

    public const HASIL_KUNCI_GAGAL = 'kunci_gagal';

    public function __construct(private readonly KunciGabungKelasService $kunci)
    {
    }

    /**
     * Daftar kelas milik dosen. Kelas historis tetap ditampilkan walaupun
     * mata kuliah atau tahun akademiknya sudah dinonaktifkan admin.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, array<string, mixed>>
     */
    public function daftar(int $dosenId, mixed $kataKunci): LengthAwarePaginator
    {
        $kataKunci = is_string($kataKunci) ? trim($kataKunci) : '';

        $query = KelasMataKuliah::query()
            ->where('dosen_id', $dosenId)
            ->with(['mataKuliah', 'tahunAkademik'])
            ->withCount([
                'peserta as jumlah_peserta_aktif' => fn ($peserta) => $peserta
                    ->where('status', StatusPeserta::Aktif->value),
            ])
            ->when($kataKunci !== '', function ($cari) use ($kataKunci): void {
                $cari->where(function ($pencarian) use ($kataKunci): void {
                    $pencarian
                        ->whereHas('mataKuliah', function ($mataKuliah) use ($kataKunci): void {
                            $mataKuliah
                                ->where('nama', 'like', '%'.$kataKunci.'%')
                                ->orWhere('kode', 'like', '%'.$kataKunci.'%');
                        })
                        ->orWhere('kode_kelas', 'like', '%'.$kataKunci.'%')
                        ->orWhere('nama_kelas', 'like', '%'.$kataKunci.'%');
                });
            })
            ->orderBy('id');

        $paginator = $query->paginate(self::PER_HALAMAN);
        $paginator->appends($kataKunci === '' ? [] : ['q' => $kataKunci]);

        return $paginator->through(function (KelasMataKuliah $kelas): array {
            $jumlahAktif = (int) ($kelas->jumlah_peserta_aktif ?? 0);
            $kapasitas = $kelas->kapasitas;

            return [
                'id' => $kelas->id,
                'kode_mata_kuliah' => $kelas->mataKuliah?->kode ?? '-',
                'nama_mata_kuliah' => $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                'mata_kuliah_aktif' => $kelas->mataKuliah?->aktif === true,
                'kode_kelas' => $kelas->kode_kelas,
                'nama_kelas' => $kelas->nama_kelas,
                'tahun_akademik' => $kelas->tahunAkademik?->label() ?? 'Tidak tersedia',
                'tahun_akademik_aktif' => $kelas->tahunAkademik?->aktif === true,
                'status_pendaftaran' => $kelas->status_pendaftaran?->value ?? StatusPendaftaranKelas::Tutup->value,
                'status_label' => $kelas->status_pendaftaran === StatusPendaftaranKelas::Buka ? 'Buka' : 'Tutup',
                'jumlah_peserta_aktif' => $jumlahAktif,
                'kapasitas' => $kapasitas,
                'kapasitas_label' => $kapasitas === null ? 'Tanpa batas' : $jumlahAktif.'/'.$kapasitas,
                'memiliki_kode_gabung' => $this->memilikiKunci($kelas),
            ];
        });
    }

    /**
     * Opsi mata kuliah untuk form create. Hanya data induk aktif milik admin.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function opsiMataKuliah(): Collection
    {
        return MataKuliah::query()
            ->aktif()
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama', 'sks'])
            ->map(fn (MataKuliah $mataKuliah): array => [
                'id' => $mataKuliah->id,
                'label' => $mataKuliah->kode.' — '.$mataKuliah->nama.' ('.$mataKuliah->sks.' SKS)',
            ]);
    }

    /**
     * Opsi tahun akademik untuk form create. Hanya periode aktif milik admin.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function opsiTahunAkademik(): Collection
    {
        return TahunAkademik::query()
            ->aktif()
            ->orderByDesc('tahun_mulai')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TahunAkademik $tahunAkademik): array => [
                'id' => $tahunAkademik->id,
                'label' => $tahunAkademik->label(),
            ]);
    }

    /**
     * Resolver kelas milik dosen. ID dari URL tidak pernah dipakai tanpa
     * pembatasan dosen_id, sehingga kelas dosen lain menghasilkan 404.
     */
    public function milikDosen(int $dosenId, mixed $id): KelasMataKuliah
    {
        return KelasMataKuliah::query()
            ->whereKey(is_numeric($id) ? (int) $id : 0)
            ->where('dosen_id', $dosenId)
            ->with(['mataKuliah', 'tahunAkademik', 'dosen'])
            ->firstOrFail();
    }

    /**
     * Data eksplisit untuk halaman detail. Hash kunci sengaja tidak disertakan.
     *
     * @return array<string, mixed>
     */
    public function detail(KelasMataKuliah $kelas): array
    {
        $jumlahAktif = $kelas->jumlahPesertaAktif();
        $kapasitas = $kelas->kapasitas;

        return [
            'id' => $kelas->id,
            'kode_mata_kuliah' => $kelas->mataKuliah?->kode ?? '-',
            'nama_mata_kuliah' => $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
            'sks' => $kelas->mataKuliah?->sks,
            'mata_kuliah_aktif' => $kelas->mataKuliah?->aktif === true,
            'kode_kelas' => $kelas->kode_kelas,
            'nama_kelas' => $kelas->nama_kelas,
            'tahun_akademik' => $kelas->tahunAkademik?->label() ?? 'Tidak tersedia',
            'tahun_akademik_aktif' => $kelas->tahunAkademik?->aktif === true,
            'nama_dosen' => $kelas->dosen?->name ?? 'Dosen tidak tersedia',
            'status_pendaftaran' => $kelas->status_pendaftaran?->value ?? StatusPendaftaranKelas::Tutup->value,
            'status_label' => $kelas->status_pendaftaran === StatusPendaftaranKelas::Buka ? 'Buka' : 'Tutup',
            'jumlah_peserta_aktif' => $jumlahAktif,
            'kapasitas' => $kapasitas,
            'kapasitas_label' => $kapasitas === null ? 'Tanpa batas' : $jumlahAktif.'/'.$kapasitas,
            'memiliki_kode_gabung' => $this->memilikiKunci($kelas),
            'kode_gabung_status_label' => $this->memilikiKunci($kelas) ? 'Sudah dibuat' : 'Belum dibuat',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataFormulir(?KelasMataKuliah $kelas = null): array
    {
        return [
            'mata_kuliah_id' => $kelas?->mata_kuliah_id ?? '',
            'tahun_akademik_id' => $kelas?->tahun_akademik_id ?? '',
            'kode_kelas' => $kelas?->kode_kelas ?? '',
            'nama_kelas' => $kelas?->nama_kelas ?? '',
            'kapasitas' => $kelas?->kapasitas ?? '',
        ];
    }

    /**
     * Buat kelas pengampu. Status awal selalu tutup dan kunci belum dibuat.
     *
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, kode: string, pesan: string, field: ?string, kelas: ?KelasMataKuliah}
     */
    public function simpan(int $dosenId, array $data): array
    {
        return DB::transaction(function () use ($dosenId, $data): array {
            $mataKuliah = MataKuliah::query()
                ->whereKey((int) ($data['mata_kuliah_id'] ?? 0))
                ->where('aktif', true)
                ->lockForUpdate()
                ->first();

            $tahunAkademik = TahunAkademik::query()
                ->whereKey((int) ($data['tahun_akademik_id'] ?? 0))
                ->where('aktif', true)
                ->lockForUpdate()
                ->first();

            if ($mataKuliah === null) {
                return $this->hasil(
                    self::HASIL_DATA_TIDAK_TERSEDIA,
                    'Mata kuliah tidak tersedia atau sudah dinonaktifkan.',
                    'mata_kuliah_id',
                );
            }

            if ($tahunAkademik === null) {
                return $this->hasil(
                    self::HASIL_DATA_TIDAK_TERSEDIA,
                    'Tahun akademik tidak tersedia atau sudah dinonaktifkan.',
                    'tahun_akademik_id',
                );
            }

            $kelas = new KelasMataKuliah();
            $kelas->forceFill([
                'mata_kuliah_id' => $mataKuliah->id,
                'tahun_akademik_id' => $tahunAkademik->id,
                'dosen_id' => $dosenId,
                'kode_kelas' => $this->normalisasiKodeKelas((string) ($data['kode_kelas'] ?? '')),
                'nama_kelas' => $this->teksNullable($data['nama_kelas'] ?? null),
                'kapasitas' => $this->kapasitas($data['kapasitas'] ?? null),
                'status_pendaftaran' => StatusPendaftaranKelas::Tutup->value,
            ]);

            try {
                $kelas->save();
            } catch (QueryException $exception) {
                if ($this->adalahDuplikatKelas($exception)) {
                    return $this->hasil(
                        self::HASIL_KODE_DUPLIKAT,
                        'Kode kelas sudah digunakan pada mata kuliah dan tahun akademik ini.',
                        'kode_kelas',
                    );
                }

                throw $exception;
            }

            return $this->hasil(
                self::HASIL_BERHASIL,
                'Kelas '.$kelas->kode_kelas.' berhasil dibuat. Pendaftaran masih ditutup dan kunci gabung belum dibuat.',
                null,
                $kelas,
            );
        });
    }

    /**
     * Perbarui hanya nama kelas dan kapasitas. Identitas dan kepemilikan tidak
     * dapat diubah lewat request.
     *
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, kode: string, pesan: string, field: ?string, kelas: ?KelasMataKuliah}
     */
    public function perbarui(int $dosenId, KelasMataKuliah $kelas, array $data): array
    {
        return DB::transaction(function () use ($dosenId, $kelas, $data): array {
            $terkunci = $this->kunciBaris($dosenId, $kelas);

            if ($terkunci === null) {
                return $this->hasil(
                    self::HASIL_TIDAK_DITEMUKAN,
                    'Kelas tidak ditemukan.',
                    'nama_kelas',
                );
            }

            $kapasitas = $this->kapasitas($data['kapasitas'] ?? null);

            if ($kapasitas !== null && $kapasitas < $terkunci->jumlahPesertaAktif()) {
                return $this->hasil(
                    self::HASIL_KAPASITAS_KURANG,
                    'Kapasitas tidak boleh lebih kecil dari jumlah peserta aktif saat ini.',
                    'kapasitas',
                );
            }

            $terkunci->forceFill([
                'nama_kelas' => $this->teksNullable($data['nama_kelas'] ?? null),
                'kapasitas' => $kapasitas,
            ])->save();

            return $this->hasil(
                self::HASIL_BERHASIL,
                'Data kelas '.$terkunci->kode_kelas.' berhasil diperbarui.',
                null,
                $terkunci,
            );
        });
    }

    /**
     * Buka atau tutup pendaftaran. Membuka mensyaratkan kunci gabung agar
     * mahasiswa benar-benar dapat menemukan dan mengikuti kelas.
     *
     * @return array{berhasil: bool, kode: string, pesan: string, field: ?string, kelas: ?KelasMataKuliah}
     */
    public function ubahStatusPendaftaran(
        int $dosenId,
        KelasMataKuliah $kelas,
        StatusPendaftaranKelas $status,
    ): array {
        return DB::transaction(function () use ($dosenId, $kelas, $status): array {
            $terkunci = $this->kunciBaris($dosenId, $kelas);

            if ($terkunci === null) {
                return $this->hasil(
                    self::HASIL_TIDAK_DITEMUKAN,
                    'Kelas tidak ditemukan.',
                    'status_pendaftaran',
                );
            }

            if ($status === StatusPendaftaranKelas::Buka && ! $this->memilikiKunci($terkunci)) {
                return $this->hasil(
                    self::HASIL_KUNCI_BELUM_ADA,
                    'Buat kunci gabung terlebih dahulu sebelum membuka pendaftaran.',
                    'status_pendaftaran',
                );
            }

            $terkunci->forceFill(['status_pendaftaran' => $status->value])->save();

            return $this->hasil(
                self::HASIL_BERHASIL,
                'Pendaftaran kelas '.$terkunci->kode_kelas.' berhasil '.($status === StatusPendaftaranKelas::Buka ? 'dibuka' : 'ditutup').'.',
                null,
                $terkunci,
            );
        });
    }

    /**
     * Buat kunci pertama kali atau rotasi kunci existing. Kunci asli hanya
     * dikembalikan sekali untuk ditampilkan oleh controller.
     *
     * @return array{berhasil: bool, kode: string, pesan: string, field: ?string, kelas: ?KelasMataKuliah, kode_gabung: ?string, jenis: ?string}
     */
    public function kelolaKodeGabung(int $dosenId, KelasMataKuliah $kelas): array
    {
        $terkunci = KelasMataKuliah::query()
            ->whereKey($kelas->getKey())
            ->where('dosen_id', $dosenId)
            ->first();

        if ($terkunci === null) {
            return [
                'berhasil' => false,
                'kode' => self::HASIL_TIDAK_DITEMUKAN,
                'pesan' => 'Kelas tidak ditemukan.',
                'field' => null,
                'kelas' => null,
                'kode_gabung' => null,
                'jenis' => null,
            ];
        }

        $jenis = $this->memilikiKunci($terkunci) ? 'rotasi' : 'buat';

        try {
            $kunci = $jenis === 'rotasi'
                ? $this->kunci->rotasi($terkunci)
                : $this->kunci->terapkan($terkunci);
        } catch (LogicException|RuntimeException) {
            return [
                'berhasil' => false,
                'kode' => self::HASIL_KUNCI_GAGAL,
                'pesan' => 'Kunci gabung tidak dapat dibuat. Silakan coba lagi.',
                'field' => null,
                'kelas' => null,
                'kode_gabung' => null,
                'jenis' => $jenis,
            ];
        }

        return [
            'berhasil' => true,
            'kode' => self::HASIL_BERHASIL,
            'pesan' => $jenis === 'rotasi'
                ? 'Kunci gabung berhasil dirotasi. Kunci lama sudah tidak berlaku; peserta tetap tersimpan.'
                : 'Kunci gabung berhasil dibuat. Simpan kunci berikut sekarang karena tidak dapat ditampilkan lagi.',
            'field' => null,
            'kelas' => $terkunci,
            'kode_gabung' => $kunci,
            'jenis' => $jenis,
        ];
    }

    private function kunciBaris(int $dosenId, KelasMataKuliah $kelas): ?KelasMataKuliah
    {
        return KelasMataKuliah::query()
            ->whereKey($kelas->getKey())
            ->where('dosen_id', $dosenId)
            ->lockForUpdate()
            ->first();
    }

    private function memilikiKunci(KelasMataKuliah $kelas): bool
    {
        $hash = $kelas->kode_gabung_hash;

        return is_string($hash) && $hash !== '';
    }

    private function normalisasiKodeKelas(string $kode): string
    {
        return Str::upper(trim($kode));
    }

    private function teksNullable(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $teks = trim((string) $nilai);

        return $teks === '' ? null : $teks;
    }

    private function kapasitas(mixed $nilai): ?int
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (int) $nilai;
    }

    /**
     * @return array{berhasil: bool, kode: string, pesan: string, field: ?string, kelas: ?KelasMataKuliah}
     */
    private function hasil(
        string $kode,
        string $pesan,
        ?string $field = null,
        ?KelasMataKuliah $kelas = null,
    ): array {
        return [
            'berhasil' => $kode === self::HASIL_BERHASIL,
            'kode' => $kode,
            'pesan' => $pesan,
            'field' => $field,
            'kelas' => $kelas,
        ];
    }

    private function adalahDuplikatKelas(QueryException $exception): bool
    {
        if (($exception->errorInfo[0] ?? null) !== '23000') {
            return false;
        }

        $pesan = strtolower($exception->getMessage());

        return str_contains($pesan, 'kelas_mata_kuliah')
            && str_contains($pesan, 'kode_kelas')
            && (str_contains($pesan, 'unique') || str_contains($pesan, 'duplicate'));
    }
}
