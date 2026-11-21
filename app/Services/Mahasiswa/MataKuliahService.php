<?php

namespace App\Services\Mahasiswa;

use App\Enums\StatusPendaftaranKelas;
use App\Enums\StatusPeserta;
use App\Support\KelayakanPeserta;
use App\Models\KelasMataKuliah;
use App\Models\PesertaMataKuliah;
use App\Services\Kelas\KunciGabungKelasService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Data dan aksi Mata Kuliah untuk mahasiswa.
 *
 * Service ini sengaja tidak menerima atau mempercayai ID mahasiswa dari request.
 * Semua identitas pemilik data berasal dari parameter $userId yang diberikan
 * server (auth()->id() pada controller).
 */
class MataKuliahService
{
    public const PER_HALAMAN = 12;

    public const HASIL_BERHASIL = 'berhasil';

    public const HASIL_KUNCI_TIDAK_VALID = 'kunci_tidak_valid';

    public const HASIL_PENDAFTARAN_TUTUP = 'pendaftaran_tutup';

    public const HASIL_KELAS_TIDAK_TERSEDIA = 'kelas_tidak_tersedia';

    public const HASIL_KAPASITAS_PENUH = 'kapasitas_penuh';

    public const HASIL_SUDAH_TERDAFTAR = 'sudah_terdaftar';

    public const HASIL_RIWAYAT_NONAKTIF = 'riwayat_nonaktif';

    public function __construct(private readonly KunciGabungKelasService $kunci)
    {
    }

    /**
     * Kelas yang benar-benar diikuti mahasiswa (peserta aktif).
     *
     * @return \Illuminate\Support\Collection<int, PesertaMataKuliah>
     */
    public function kelasSaya(int $userId): Collection
    {
        return PesertaMataKuliah::query()
            ->where('mahasiswa_id', $userId)
            ->whereIn('status', KelayakanPeserta::nilaiStatusBerhak())
            ->with([
                'kelasMataKuliah.mataKuliah',
                'kelasMataKuliah.tahunAkademik',
                'kelasMataKuliah.dosen',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * Cari kelas yang memperbolehkan pendaftaran.
     *
     * Hanya metadata publik yang dikembalikan. Relasi privat sengaja tidak
     * dimuat dan hash kunci tidak pernah dimasukkan ke hasil.
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, array<string, mixed>>
     */
    public function cariKelas(array $filters): LengthAwarePaginator
    {
        $kataKunci = $filters['q'] ?? null;
        $kataKunci = is_string($kataKunci) ? trim($kataKunci) : '';

        $query = KelasMataKuliah::query()
            ->pendaftaranBuka()
            ->whereNotNull('kode_gabung_hash')
            ->whereHas('mataKuliah', fn ($mataKuliah) => $mataKuliah->where('aktif', true))
            ->whereHas('tahunAkademik', fn ($tahun) => $tahun->where('aktif', true))
            ->with(['mataKuliah', 'tahunAkademik', 'dosen'])
            ->withCount([
                'peserta as jumlah_peserta_aktif' => fn ($peserta) => $peserta
                    ->where('status', StatusPeserta::Aktif->value),
            ]);

        if ($kataKunci !== '') {
            $query->where(function ($pencarian) use ($kataKunci): void {
                $pencarian->whereHas('mataKuliah', function ($mataKuliah) use ($kataKunci): void {
                    $mataKuliah
                        ->where('nama', 'like', '%'.$kataKunci.'%')
                        ->orWhere('kode', 'like', '%'.$kataKunci.'%');
                })
                    ->orWhere('kode_kelas', 'like', '%'.$kataKunci.'%')
                    ->orWhere('nama_kelas', 'like', '%'.$kataKunci.'%');
            });
        }

        $paginator = $query->orderBy('id')->paginate(self::PER_HALAMAN);
        $paginator->appends($kataKunci === '' ? [] : ['q' => $kataKunci]);

        return $paginator->through(function (KelasMataKuliah $kelas): array {
            $mataKuliah = $kelas->mataKuliah;
            $tahun = $kelas->tahunAkademik;
            $jumlahAktif = (int) ($kelas->jumlah_peserta_aktif ?? 0);
            $kapasitas = $kelas->kapasitas;

            return [
                'id' => $kelas->id,
                'kode_mata_kuliah' => $mataKuliah?->kode ?? '',
                'nama_mata_kuliah' => $mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                'sks' => $mataKuliah?->sks,
                'kode_kelas' => $kelas->kode_kelas,
                'nama_kelas' => $kelas->nama_kelas,
                'dosen' => $kelas->dosen?->name ?? 'Dosen tidak tersedia',
                'tahun_akademik' => $tahun?->label() ?? 'Tidak tersedia',
                'semester' => $tahun?->semester?->value,
                'status_pendaftaran' => $kelas->status_pendaftaran?->value
                    ?? StatusPendaftaranKelas::Buka->value,
                'kapasitas' => $kapasitas,
                'jumlah_peserta_aktif' => $jumlahAktif,
                'kapasitas_label' => $kapasitas === null
                    ? 'Tanpa batas'
                    : $jumlahAktif.'/'.$kapasitas,
                'masih_menerima_peserta' => $kapasitas === null || $jumlahAktif < $kapasitas,
            ];
        });
    }

    /**
     * Gabungkan mahasiswa ke kelas memakai kunci gabung.
     *
     * @return array{berhasil: bool, kode: string, pesan: string, kelas: ?KelasMataKuliah, peserta: ?PesertaMataKuliah}
     */
    public function gabung(int $userId, string $kodeGabung): array
    {
        $hash = $this->kunci->hash($kodeGabung);

        $kelas = KelasMataKuliah::query()
            ->where('kode_gabung_hash', $hash)
            ->first();

        if ($kelas === null) {
            return $this->hasil(self::HASIL_KUNCI_TIDAK_VALID);
        }

        return DB::transaction(function () use ($kelas, $kodeGabung, $userId): array {
            $terkunci = KelasMataKuliah::query()
                ->whereKey($kelas->getKey())
                ->lockForUpdate()
                ->first();

            if ($terkunci === null || ! $this->kunci->verifikasi($terkunci, $kodeGabung)) {
                return $this->hasil(self::HASIL_KUNCI_TIDAK_VALID);
            }

            $terkunci->loadMissing(['mataKuliah', 'tahunAkademik', 'dosen']);

            if ($terkunci->status_pendaftaran !== StatusPendaftaranKelas::Buka) {
                return $this->hasil(self::HASIL_PENDAFTARAN_TUTUP, $terkunci);
            }

            if ($terkunci->mataKuliah?->aktif !== true || $terkunci->tahunAkademik?->aktif !== true) {
                return $this->hasil(self::HASIL_KELAS_TIDAK_TERSEDIA, $terkunci);
            }

            if (! $terkunci->masihMenerimaPeserta()) {
                return $this->hasil(self::HASIL_KAPASITAS_PENUH, $terkunci);
            }

            $riwayat = PesertaMataKuliah::query()
                ->where('kelas_mata_kuliah_id', $terkunci->getKey())
                ->where('mahasiswa_id', $userId)
                ->first();

            if ($riwayat !== null) {
                $kode = KelayakanPeserta::berhak($riwayat->status)
                    ? self::HASIL_SUDAH_TERDAFTAR
                    : self::HASIL_RIWAYAT_NONAKTIF;

                return $this->hasil($kode, $terkunci, $riwayat);
            }

            $peserta = new PesertaMataKuliah();
            $peserta->forceFill([
                'kelas_mata_kuliah_id' => $terkunci->getKey(),
                'mahasiswa_id' => $userId,
                'status' => StatusPeserta::Aktif->value,
                'tanggal_daftar' => today()->toDateString(),
            ]);

            try {
                $peserta->save();
            } catch (QueryException $exception) {
                if ($this->bentrokPendaftaranUnik($exception)) {
                    return $this->hasil(self::HASIL_SUDAH_TERDAFTAR, $terkunci);
                }

                throw $exception;
            }

            return $this->hasil(self::HASIL_BERHASIL, $terkunci, $peserta);
        });
    }

    /**
     * Format hasil yang konsisten untuk dipetakan controller ke flash/validasi.
     *
     * @return array{berhasil: bool, kode: string, pesan: string, kelas: ?KelasMataKuliah, peserta: ?PesertaMataKuliah}
     */
    private function hasil(
        string $kode,
        ?KelasMataKuliah $kelas = null,
        ?PesertaMataKuliah $peserta = null,
    ): array {
        $pesan = match ($kode) {
            self::HASIL_BERHASIL => 'Anda berhasil bergabung dengan kelas ini.',
            self::HASIL_PENDAFTARAN_TUTUP => 'Pendaftaran kelas ini sedang ditutup.',
            self::HASIL_KELAS_TIDAK_TERSEDIA => 'Kelas ini tidak tersedia untuk pendaftaran.',
            self::HASIL_KAPASITAS_PENUH => 'Kapasitas kelas sudah penuh.',
            self::HASIL_SUDAH_TERDAFTAR => 'Anda sudah terdaftar pada kelas ini.',
            self::HASIL_RIWAYAT_NONAKTIF => 'Anda memiliki riwayat pendaftaran pada kelas ini. Hubungi dosen untuk informasi lebih lanjut.',
            default => 'Kunci gabung tidak valid atau kelas tidak tersedia.',
        };

        return [
            'berhasil' => $kode === self::HASIL_BERHASIL,
            'kode' => $kode,
            'pesan' => $pesan,
            'kelas' => $kelas,
            'peserta' => $peserta,
        ];
    }

    /**
     * Hanya anggap sebagai duplikat jika pelanggaran unik memang terjadi pada
     * pasangan kelas + mahasiswa di tabel peserta_mata_kuliah.
     */
    private function bentrokPendaftaranUnik(QueryException $exception): bool
    {
        if (($exception->errorInfo[0] ?? null) !== '23000') {
            return false;
        }

        $pesan = strtolower($exception->getMessage());

        if (! str_contains($pesan, 'peserta_mata_kuliah')) {
            return false;
        }

        return str_contains($pesan, 'unique') || str_contains($pesan, 'duplicate');
    }
}
