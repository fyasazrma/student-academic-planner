<?php

namespace App\Services\Absensi;

use App\Enums\StatusSesiAbsensi;
use App\Models\KelasMataKuliah;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Services\Dosen\MataKuliahService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pengelolaan sesi absensi yang selalu terikat pada kelas milik dosen.
 * ID kelas dan ID sesi dari URL divalidasi ulang oleh server.
 */
class SesiAbsensiService
{
    public function __construct(
        private readonly MataKuliahService $mataKuliah,
        private readonly AbsensiService $absensi,
    ) {
    }

    public function kelasMilikDosen(int $dosenId, mixed $kelasId): KelasMataKuliah
    {
        return $this->mataKuliah->milikDosen($dosenId, $kelasId);
    }

    public function sesiMilikKelas(KelasMataKuliah $kelas, mixed $sesiId): SesiAbsensi
    {
        return SesiAbsensi::query()
            ->whereKey(is_numeric($sesiId) ? (int) $sesiId : 0)
            ->where('kelas_mata_kuliah_id', $kelas->getKey())
            ->with('kelasMataKuliah.mataKuliah')
            ->firstOrFail();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function daftarKelas(int $dosenId): Collection
    {
        return KelasMataKuliah::query()
            ->where('dosen_id', $dosenId)
            ->with(['mataKuliah', 'tahunAkademik'])
            ->withCount([
                'sesiAbsensi as total_sesi',
                'sesiAbsensi as sesi_dibuka' => fn ($query) => $query
                    ->where('status', StatusSesiAbsensi::Dibuka->value),
                'sesiAbsensi as sesi_ditutup' => fn ($query) => $query
                    ->where('status', StatusSesiAbsensi::Ditutup->value),
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (KelasMataKuliah $kelas): array => [
                'id' => $kelas->getKey(),
                'kode_kelas' => $kelas->kode_kelas,
                'nama_mata_kuliah' => $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                'kode_mata_kuliah' => $kelas->mataKuliah?->kode ?? '-',
                'periode' => $kelas->tahunAkademik?->label() ?? '-',
                'total_sesi' => (int) $kelas->total_sesi,
                'sesi_dibuka' => (int) $kelas->sesi_dibuka,
                'sesi_ditutup' => (int) $kelas->sesi_ditutup,
                'url' => route('dosen.absensi.kelas', $kelas->getKey()),
                'rekap_url' => route('dosen.absensi.rekap', $kelas->getKey()),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function daftarSesi(KelasMataKuliah $kelas): Collection
    {
        return $kelas->sesiAbsensi()
            ->withCount('absensi')
            ->orderByDesc('tanggal')
            ->orderByDesc('pertemuan_ke')
            ->get()
            ->map(fn (SesiAbsensi $sesi): array => [
                'id' => $sesi->getKey(),
                'pertemuan_ke' => $sesi->pertemuan_ke,
                'tanggal_label' => $sesi->tanggal?->translatedFormat('D, d F Y') ?? '-',
                'topik' => $sesi->topik ?: 'Topik belum diisi',
                'waktu_label' => $this->absensi->rentangWaktu($sesi),
                'status' => $sesi->status?->value ?? '-',
                'status_label' => $sesi->status === StatusSesiAbsensi::Dibuka ? 'Dibuka' : 'Ditutup',
                'jumlah_absensi' => (int) $sesi->absensi_count,
                'detail_url' => route('dosen.absensi.sesi.show', [$kelas->getKey(), $sesi->getKey()]),
                'edit_url' => route('dosen.absensi.sesi.edit', [$kelas->getKey(), $sesi->getKey()]),
                'buka_url' => route('dosen.absensi.sesi.buka', [$kelas->getKey(), $sesi->getKey()]),
                'tutup_url' => route('dosen.absensi.sesi.tutup', [$kelas->getKey(), $sesi->getKey()]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, sesi: ?SesiAbsensi}
     */
    public function buat(int $dosenId, mixed $kelasId, array $data, User $actor): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $validasi = $this->validasiData($data);

        if ($validasi !== null) {
            return $this->gagal($validasi);
        }

        if ($this->pertemuanDipakai($kelas, (int) $data['pertemuan_ke'])) {
            return $this->gagal('Nomor pertemuan sudah digunakan pada kelas ini.');
        }

        $sesi = new SesiAbsensi();
        $sesi->forceFill([
            'pertemuan_ke' => (int) $data['pertemuan_ke'],
            'tanggal' => $data['tanggal'],
            'topik' => $this->bersihkanTopik($data['topik'] ?? null),
            'waktu_mulai' => $data['waktu_mulai'],
            'waktu_selesai' => $data['waktu_selesai'],
            'status' => StatusSesiAbsensi::Dibuka,
        ]);
        $sesi->kelasMataKuliah()->associate($kelas);
        $sesi->pembuat()->associate($actor);

        try {
            $sesi->save();
        } catch (QueryException $exception) {
            if ($this->pelanggaranUnik($exception)) {
                return $this->gagal('Nomor pertemuan sudah digunakan pada kelas ini.');
            }

            return $this->simpanGagal($exception, 'Sesi gagal dibuat. Silakan coba lagi.');
        } catch (Throwable $exception) {
            return $this->simpanGagal($exception, 'Sesi gagal dibuat. Silakan coba lagi.');
        }

        return [
            'berhasil' => true,
            'pesan' => 'Sesi absensi berhasil dibuat.',
            'sesi' => $sesi,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, sesi: ?SesiAbsensi}
     */
    public function ubah(int $dosenId, mixed $kelasId, mixed $sesiId, array $data): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $sesi = $this->sesiMilikKelas($kelas, $sesiId);
        $validasi = $this->validasiData($data);

        if ($validasi !== null) {
            return $this->gagal($validasi);
        }

        if ($this->pertemuanDipakai($kelas, (int) $data['pertemuan_ke'], $sesi->getKey())) {
            return $this->gagal('Nomor pertemuan sudah digunakan pada kelas ini.');
        }

        $sesi->forceFill([
            'pertemuan_ke' => (int) $data['pertemuan_ke'],
            'tanggal' => $data['tanggal'],
            'topik' => $this->bersihkanTopik($data['topik'] ?? null),
            'waktu_mulai' => $data['waktu_mulai'],
            'waktu_selesai' => $data['waktu_selesai'],
        ]);

        try {
            $sesi->save();
        } catch (QueryException $exception) {
            if ($this->pelanggaranUnik($exception)) {
                return $this->gagal('Nomor pertemuan sudah digunakan pada kelas ini.');
            }

            return $this->simpanGagal($exception, 'Sesi gagal diperbarui. Silakan coba lagi.');
        } catch (Throwable $exception) {
            return $this->simpanGagal($exception, 'Sesi gagal diperbarui. Silakan coba lagi.');
        }

        return [
            'berhasil' => true,
            'pesan' => 'Sesi absensi berhasil diperbarui.',
            'sesi' => $sesi,
        ];
    }

    /**
     * Sesi ditutup tidak dapat dibuka kembali.
     *
     * @return array{berhasil: bool, pesan: string}
     */
    public function buka(int $dosenId, mixed $kelasId, mixed $sesiId): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $sesi = $this->sesiMilikKelas($kelas, $sesiId);

        if ($sesi->status === StatusSesiAbsensi::Ditutup) {
            return ['berhasil' => false, 'pesan' => 'Sesi yang sudah ditutup tidak dapat dibuka kembali.'];
        }

        return ['berhasil' => true, 'pesan' => 'Sesi memang sudah dalam kondisi terbuka.'];
    }

    /**
     * @return array{berhasil: bool, pesan: string}
     */
    public function tutup(int $dosenId, mixed $kelasId, mixed $sesiId): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $sesi = $this->sesiMilikKelas($kelas, $sesiId);

        return $this->absensi->tutupSesi($sesi);
    }

    /**
     * Sesi yang sudah memiliki catatan absensi tidak dihapus.
     *
     * @return array{berhasil: bool, pesan: string}
     */
    public function hapus(int $dosenId, mixed $kelasId, mixed $sesiId): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $sesi = $this->sesiMilikKelas($kelas, $sesiId);

        if ($sesi->absensi()->exists()) {
            return ['berhasil' => false, 'pesan' => 'Sesi yang sudah memiliki catatan absensi tidak dapat dihapus.'];
        }

        try {
            $sesi->delete();
        } catch (Throwable $exception) {
            return $this->simpanGagal($exception, 'Sesi gagal dihapus. Silakan coba lagi.');
        }

        return ['berhasil' => true, 'pesan' => 'Sesi absensi berhasil dihapus.'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validasiData(array $data): ?string
    {
        $pertemuan = $data['pertemuan_ke'] ?? null;
        $tanggal = $data['tanggal'] ?? null;
        $mulai = $data['waktu_mulai'] ?? null;
        $selesai = $data['waktu_selesai'] ?? null;

        if (! is_numeric($pertemuan) || (int) $pertemuan < 1 || (int) $pertemuan > 65535) {
            return 'Nomor pertemuan tidak valid.';
        }

        try {
            $tanggalCarbon = Carbon::createFromFormat('!Y-m-d', (string) $tanggal);
        } catch (Throwable) {
            return 'Tanggal sesi tidak valid.';
        }

        if (! $tanggalCarbon instanceof Carbon || $tanggalCarbon->toDateString() !== (string) $tanggal) {
            return 'Tanggal sesi tidak valid.';
        }

        $mulaiCarbon = $this->waktu($tanggal, $mulai);
        $selesaiCarbon = $this->waktu($tanggal, $selesai);

        if ($mulaiCarbon === null || $selesaiCarbon === null) {
            return 'Waktu mulai dan waktu selesai wajib diisi.';
        }

        if (! $selesaiCarbon->greaterThan($mulaiCarbon)) {
            return 'Waktu selesai harus lebih besar daripada waktu mulai.';
        }

        return null;
    }

    private function waktu(mixed $tanggal, mixed $jam): ?Carbon
    {
        if (! is_string($jam) || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $jam) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat(
                'Y-m-d H:i',
                $tanggal.' '.substr($jam, 0, 5),
                AbsensiService::ZONA_WAKTU,
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function pertemuanDipakai(KelasMataKuliah $kelas, int $pertemuan, ?int $kecualiId = null): bool
    {
        $query = $kelas->sesiAbsensi()
            ->where('pertemuan_ke', $pertemuan);

        if ($kecualiId !== null) {
            $query->whereKeyNot($kecualiId);
        }

        return $query->exists();
    }

    private function bersihkanTopik(mixed $topik): ?string
    {
        if (! is_string($topik)) {
            return null;
        }

        $topik = trim($topik);

        return $topik === '' ? null : $topik;
    }

    private function pelanggaranUnik(QueryException $exception): bool
    {
        $pesan = strtoupper($exception->getMessage());

        return $exception->getCode() === '23000'
            || str_contains($pesan, 'UNIQUE CONSTRAINT FAILED')
            || str_contains($pesan, 'DUPLICATE ENTRY');
    }

    /**
     * @return array{berhasil: bool, pesan: string, sesi: ?SesiAbsensi}
     */
    private function gagal(string $pesan): array
    {
        return ['berhasil' => false, 'pesan' => $pesan, 'sesi' => null];
    }

    /**
     * @return array{berhasil: bool, pesan: string, sesi: ?SesiAbsensi}
     */
    private function simpanGagal(Throwable $exception, string $pesan): array
    {
        Log::error('Gagal menyimpan sesi absensi', ['error' => $exception->getMessage()]);

        return $this->gagal($pesan);
    }
}
