<?php

namespace App\Services\Dosen;

use App\Models\KelasMataKuliah;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Pengelolaan tugas milik dosen.
 *
 * Setiap aksi diawali scoped lookup kelas berdasarkan dosen_id, lalu tugas
 * diverifikasi masih berada di kelas tersebut. Kelas, pembuat, path lampiran,
 * dan waktu publikasi tidak pernah diambil dari input formulir secara bebas.
 */
class TugasService
{
    public function __construct(
        private readonly MataKuliahService $mataKuliah,
        private readonly TugasFileService $file,
    ) {
    }

    /**
     * Resolver kelas milik dosen. Kelas dosen lain menghasilkan 404.
     */
    public function kelasMilikDosen(int $dosenId, mixed $kelasId): KelasMataKuliah
    {
        return $this->mataKuliah->milikDosen($dosenId, $kelasId);
    }

    /**
     * Pastikan tugas benar-benar berada pada kelas yang diberikan.
     * Tugas dari kelas lain menghasilkan 404.
     */
    public function tugasMilikKelas(KelasMataKuliah $kelas, mixed $tugasId): Tugas
    {
        return Tugas::query()
            ->whereKey(is_numeric($tugasId) ? (int) $tugasId : 0)
            ->where('kelas_mata_kuliah_id', $kelas->getKey())
            ->firstOrFail();
    }

    /**
     * @return array{kelas: KelasMataKuliah, tugas: Collection<int, array<string, mixed>>}
     */
    public function daftar(int $dosenId, mixed $kelasId): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);

        $tugas = $kelas->tugas()
            ->withCount([
                'pengumpulan as jumlah_pengumpulan',
                'pengumpulan as jumlah_belum_dinilai' => fn ($query) => $query->where(function ($query): void {
                    $query->whereNull('nilai')->orWhereNull('dinilai_pada');
                }),
            ])
            ->orderByDesc('deadline')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Tugas $item): array => [
                'id' => $item->getKey(),
                'judul' => $item->judul,
                'deskripsi_ringkas' => Str::limit((string) $item->deskripsi, 120),
                'status' => match (true) {
                    $item->dipublikasikan_pada === null => 'draft',
                    ! $item->sudahDipublikasikan() => 'terjadwal',
                    $item->sudahMelewatiDeadline() => 'ditutup',
                    default => 'dipublikasikan',
                },
                'status_label' => match (true) {
                    $item->dipublikasikan_pada === null => 'Draft',
                    ! $item->sudahDipublikasikan() => 'Terjadwal',
                    $item->sudahMelewatiDeadline() => 'Ditutup',
                    default => 'Dipublikasikan',
                },
                'dipublikasikan_label' => $item->dipublikasikan_pada?->format('d M Y H:i') ?? 'Belum dipublikasikan',
                'deadline_label' => $item->deadline->translatedFormat('d M Y H:i'),
                'nilai_maksimal' => $item->nilai_maksimal,
                'jumlah_pengumpulan' => (int) ($item->jumlah_pengumpulan ?? 0),
                'jumlah_belum_dinilai' => (int) ($item->jumlah_belum_dinilai ?? 0),
                'izinkan_terlambat' => $item->izinkan_terlambat,
                'lampiran_nama' => $item->lampiran_nama,
                'lampiran_size_label' => $this->labelUkuran((int) $item->lampiran_size),
                'lampiran_mime' => $item->lampiran_mime,
            ]);

        return ['kelas' => $kelas, 'tugas' => $tugas];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataFormulir(?Tugas $tugas = null): array
    {
        return [
            'judul' => $tugas?->judul ?? '',
            'deskripsi' => $tugas?->deskripsi ?? '',
            'status' => $tugas !== null && $tugas->dipublikasikan_pada !== null
                ? 'dipublikasikan'
                : 'draft',
            'dipublikasikan_pada' => $tugas?->dipublikasikan_pada?->format('Y-m-d\TH:i')
                ?? now()->format('Y-m-d\TH:i'),
            'deadline' => $tugas?->deadline?->format('Y-m-d\TH:i')
                ?? now()->addDays(7)->format('Y-m-d\TH:i'),
            'nilai_maksimal' => $tugas?->nilai_maksimal ?? '100',
            'izinkan_terlambat' => $tugas?->izinkan_terlambat ?? false,
            'dipublikasikan_label' => $tugas?->dipublikasikan_pada?->translatedFormat('d M Y H:i') ?? 'Belum dipublikasikan',
            'lampiran_nama' => $tugas?->lampiran_nama,
            'lampiran_size_label' => $tugas === null ? null : $this->labelUkuran((int) $tugas->lampiran_size),
            'lampiran_mime' => $tugas?->lampiran_mime,
            'ukuran_maksimal_kb' => MateriFileService::UKURAN_MAKSIMAL_KB,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah, tugas: ?Tugas}
     */
    public function simpan(
        int $dosenId,
        mixed $kelasId,
        array $data,
        ?UploadedFile $file,
        User $pembuat,
    ): array {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $info = null;

        if ($file !== null) {
            $pesanValidasi = $this->file->validasiBerkas($file);

            if ($pesanValidasi !== null) {
                return $this->gagal($pesanValidasi, 'lampiran', $kelas);
            }

            try {
                $info = $this->file->simpan($file, $kelas->getKey());
            } catch (Throwable) {
                return $this->gagal('Lampiran tugas gagal disimpan. Silakan coba lagi.', 'lampiran', $kelas);
            }
        }

        try {
            $tugas = DB::transaction(function () use ($kelas, $data, $info, $pembuat): Tugas {
                $tugas = $kelas->tugas()->make([
                    'judul' => $data['judul'],
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'deadline' => $data['deadline'],
                    'dipublikasikan_pada' => $data['status'] === 'dipublikasikan'
                        ? $data['dipublikasikan_pada']
                        : null,
                    'nilai_maksimal' => $data['nilai_maksimal'],
                    'izinkan_terlambat' => $data['izinkan_terlambat'],
                    'lampiran_path' => $info['path'] ?? null,
                    'lampiran_nama' => $info['file_name'] ?? null,
                    'lampiran_mime' => $info['file_mime'] ?? null,
                    'lampiran_size' => $info['file_size'] ?? null,
                ]);

                $tugas->pembuat()->associate($pembuat);
                $tugas->save();

                return $tugas;
            });
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan data tugas', ['error' => $exception->getMessage()]);

            if ($info !== null) {
                $this->file->hapus($info['path'], $kelas->getKey());
            }

            return $this->gagal('Tugas gagal disimpan. Silakan coba lagi.', null, $kelas);
        }

        return [
            'berhasil' => true,
            'pesan' => 'Tugas berhasil ditambahkan.',
            'field' => null,
            'kelas' => $kelas,
            'tugas' => $tugas,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah, tugas: ?Tugas}
     */
    public function perbarui(
        int $dosenId,
        mixed $kelasId,
        mixed $tugasId,
        array $data,
        ?UploadedFile $file,
    ): array {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $tugas = $this->tugasMilikKelas($kelas, $tugasId);
        $infoBaru = null;

        if ($file !== null) {
            $pesanValidasi = $this->file->validasiBerkas($file);

            if ($pesanValidasi !== null) {
                return $this->gagal($pesanValidasi, 'lampiran', $kelas, $tugas);
            }

            try {
                $infoBaru = $this->file->simpan($file, $kelas->getKey());
            } catch (Throwable) {
                return $this->gagal('Lampiran tugas gagal disimpan. Silakan coba lagi.', 'lampiran', $kelas, $tugas);
            }
        }

        $pathLama = $tugas->lampiran_path;
        $hapusLampiran = $infoBaru === null && (bool) ($data['hapus_lampiran'] ?? false);

        try {
            DB::transaction(function () use ($tugas, $data, $infoBaru, $hapusLampiran): void {
                $atribut = [
                    'judul' => $data['judul'],
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'deadline' => $data['deadline'],
                    'dipublikasikan_pada' => $data['status'] === 'dipublikasikan'
                        ? $data['dipublikasikan_pada']
                        : null,
                    'nilai_maksimal' => $data['nilai_maksimal'],
                    'izinkan_terlambat' => $data['izinkan_terlambat'],
                ];

                if ($infoBaru !== null) {
                    $atribut['lampiran_path'] = $infoBaru['path'];
                    $atribut['lampiran_nama'] = $infoBaru['file_name'];
                    $atribut['lampiran_mime'] = $infoBaru['file_mime'];
                    $atribut['lampiran_size'] = $infoBaru['file_size'];
                } elseif ($hapusLampiran) {
                    $atribut['lampiran_path'] = null;
                    $atribut['lampiran_nama'] = null;
                    $atribut['lampiran_mime'] = null;
                    $atribut['lampiran_size'] = null;
                }

                $tugas->fill($atribut)->save();
            });
        } catch (Throwable $exception) {
            Log::error('Gagal memperbarui data tugas', ['error' => $exception->getMessage()]);

            if ($infoBaru !== null) {
                $this->file->hapus($infoBaru['path'], $kelas->getKey());
            }

            return $this->gagal('Tugas gagal diperbarui. Silakan coba lagi.', null, $kelas, $tugas);
        }

        if (($infoBaru !== null || $hapusLampiran) && $pathLama !== null && ! $this->file->hapus($pathLama, $kelas->getKey())) {
            Log::warning('Gagal menghapus lampiran tugas lama setelah pembaruan', ['path' => $pathLama]);
        }

        return [
            'berhasil' => true,
            'pesan' => 'Tugas berhasil diperbarui.',
            'field' => null,
            'kelas' => $kelas,
            'tugas' => $tugas,
        ];
    }

    /**
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah}
     */
    public function hapus(int $dosenId, mixed $kelasId, mixed $tugasId): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $tugas = $this->tugasMilikKelas($kelas, $tugasId);

        if ($tugas->pengumpulan()->exists()) {
            return $this->gagal(
                'Tugas tidak dapat dihapus karena sudah memiliki pengumpulan.',
                null,
                $kelas,
                $tugas,
            );
        }

        $path = $tugas->lampiran_path;

        try {
            DB::transaction(fn () => $tugas->delete());
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus data tugas', ['error' => $exception->getMessage()]);

            return $this->gagal('Tugas gagal dihapus. Silakan coba lagi.', null, $kelas, $tugas);
        }

        $this->file->hapus($path, $kelas->getKey());

        return [
            'berhasil' => true,
            'pesan' => 'Tugas berhasil dihapus.',
            'field' => null,
            'kelas' => $kelas,
        ];
    }

    public function unduh(int $dosenId, mixed $kelasId, mixed $tugasId): Response
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $tugas = $this->tugasMilikKelas($kelas, $tugasId);

        if ($tugas->lampiran_path === null || ! $this->file->pathMilikKelas($tugas->lampiran_path, $kelas->getKey())) {
            abort(404);
        }

        return $this->file->unduh(
            $tugas->lampiran_path,
            $tugas->lampiran_nama ?? 'lampiran',
            $tugas->lampiran_mime,
        );
    }

    /**
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah, tugas: ?Tugas}
     */
    private function gagal(
        string $pesan,
        ?string $field,
        KelasMataKuliah $kelas,
        ?Tugas $tugas = null,
    ): array {
        return [
            'berhasil' => false,
            'pesan' => $pesan,
            'field' => $field,
            'kelas' => $kelas,
            'tugas' => $tugas,
        ];
    }

    private function labelUkuran(int $byte): string
    {
        if ($byte >= 1048576) {
            return number_format($byte / 1048576, 1, ',', '.').' MB';
        }

        if ($byte >= 1024) {
            return (string) round($byte / 1024).' KB';
        }

        return $byte.' B';
    }
}
