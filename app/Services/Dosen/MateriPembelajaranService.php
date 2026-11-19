<?php

namespace App\Services\Dosen;

use App\Models\KelasMataKuliah;
use App\Models\MateriPembelajaran;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Pengelolaan materi perkuliahan milik dosen.
 *
 * Setiap aksi diawali scoped lookup kelas berdasarkan dosen_id, lalu materi
 * diverifikasi masih berada di kelas tersebut. Identitas kelas, pembuat, path,
 * dan status publikasi tidak pernah diambil dari input formulir.
 */
class MateriPembelajaranService
{
    public function __construct(
        private readonly MataKuliahService $mataKuliah,
        private readonly MateriFileService $file,
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
     * Pastikan materi benar-benar berada pada kelas yang diberikan.
     * Materi dari kelas lain menghasilkan 404.
     */
    public function materiMilikKelas(KelasMataKuliah $kelas, mixed $materiId): MateriPembelajaran
    {
        return MateriPembelajaran::query()
            ->whereKey(is_numeric($materiId) ? (int) $materiId : 0)
            ->where('kelas_mata_kuliah_id', $kelas->getKey())
            ->firstOrFail();
    }

    /**
     * @return array{kelas: KelasMataKuliah, materi: \Illuminate\Support\Collection<int, array<string, mixed>>}
     */
    public function daftar(int $dosenId, mixed $kelasId): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);

        $materi = $kelas->materi()
            ->with('pembuat')
            ->orderByDesc('dipublikasikan_pada')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MateriPembelajaran $item): array => [
                'id' => $item->getKey(),
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi,
                'deskripsi_ringkas' => Str::limit((string) $item->deskripsi, 120),
                'file_name' => $item->file_name,
                'file_mime' => $item->file_mime,
                'file_size_label' => $this->labelUkuran((int) $item->file_size),
                'status' => $item->dipublikasikan_pada === null ? 'draft' : 'dipublikasikan',
                'status_label' => $item->dipublikasikan_pada === null ? 'Draft' : 'Dipublikasikan',
                'dipublikasikan_label' => $item->dipublikasikan_pada?->format('d M Y H:i') ?? 'Belum dipublikasikan',
                'dibuat_oleh' => $item->pembuat?->name ?? '-',
            ]);

        return ['kelas' => $kelas, 'materi' => $materi];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataFormulir(?MateriPembelajaran $materi = null): array
    {
        return [
            'judul' => $materi?->judul ?? '',
            'deskripsi' => $materi?->deskripsi ?? '',
            'status' => $materi !== null && $materi->dipublikasikan_pada !== null
                ? 'dipublikasikan'
                : 'draft',
            'file_name' => $materi?->file_name,
            'file_size_label' => $materi === null ? null : $this->labelUkuran((int) $materi->file_size),
            'dipublikasikan_label' => $materi?->dipublikasikan_pada?->format('d M Y H:i') ?? 'Belum dipublikasikan',
            'ukuran_maksimal_kb' => MateriFileService::UKURAN_MAKSIMAL_KB,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah, materi: ?MateriPembelajaran}
     */
    public function simpan(
        int $dosenId,
        mixed $kelasId,
        array $data,
        ?UploadedFile $file,
        User $pembuat,
    ): array {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);

        if ($file === null) {
            return $this->gagal('File materi wajib diunggah.', 'file', $kelas);
        }

        $pesanValidasi = $this->file->validasiBerkas($file);

        if ($pesanValidasi !== null) {
            return $this->gagal($pesanValidasi, 'file', $kelas);
        }

        try {
            $info = $this->file->simpan($file, $kelas->getKey());
        } catch (Throwable) {
            return $this->gagal('File materi gagal disimpan. Silakan coba lagi.', 'file', $kelas);
        }

        try {
            $materi = DB::transaction(function () use ($kelas, $data, $info, $pembuat): MateriPembelajaran {
                $materi = $kelas->materi()->make([
                    'judul' => $data['judul'],
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'file_path' => $info['path'],
                    'file_name' => $info['file_name'],
                    'file_mime' => $info['file_mime'],
                    'file_size' => $info['file_size'],
                    'dipublikasikan_pada' => $data['status'] === 'dipublikasikan' ? now() : null,
                ]);

                $materi->pembuat()->associate($pembuat);
                $materi->save();

                return $materi;
            });
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan data materi', ['error' => $exception->getMessage()]);
            $this->file->hapus($info['path']);

            return $this->gagal('Materi gagal disimpan. Silakan coba lagi.', null, $kelas);
        }

        return [
            'berhasil' => true,
            'pesan' => 'Materi berhasil ditambahkan.',
            'field' => null,
            'kelas' => $kelas,
            'materi' => $materi,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah, materi: ?MateriPembelajaran}
     */
    public function perbarui(
        int $dosenId,
        mixed $kelasId,
        mixed $materiId,
        array $data,
        ?UploadedFile $file,
    ): array {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $materi = $this->materiMilikKelas($kelas, $materiId);
        $infoBaru = null;

        if ($file !== null) {
            $pesanValidasi = $this->file->validasiBerkas($file);

            if ($pesanValidasi !== null) {
                return $this->gagal($pesanValidasi, 'file', $kelas, $materi);
            }

            try {
                $infoBaru = $this->file->simpan($file, $kelas->getKey());
            } catch (Throwable) {
                return $this->gagal('File materi gagal disimpan. Silakan coba lagi.', 'file', $kelas, $materi);
            }
        }

        $pathLama = $materi->file_path;

        try {
            DB::transaction(function () use ($materi, $data, $infoBaru): void {
                $dipublikasikanPada = $data['status'] === 'dipublikasikan'
                    ? ($materi->dipublikasikan_pada ?? now())
                    : null;

                $atribut = [
                    'judul' => $data['judul'],
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'dipublikasikan_pada' => $dipublikasikanPada,
                ];

                if ($infoBaru !== null) {
                    $atribut['file_path'] = $infoBaru['path'];
                    $atribut['file_name'] = $infoBaru['file_name'];
                    $atribut['file_mime'] = $infoBaru['file_mime'];
                    $atribut['file_size'] = $infoBaru['file_size'];
                }

                $materi->fill($atribut)->save();
            });
        } catch (Throwable $exception) {
            Log::error('Gagal memperbarui data materi', ['error' => $exception->getMessage()]);

            if ($infoBaru !== null) {
                $this->file->hapus($infoBaru['path']);
            }

            return $this->gagal('Materi gagal diperbarui. Silakan coba lagi.', null, $kelas, $materi);
        }

        if ($infoBaru !== null && ! $this->file->hapus($pathLama)) {
            Log::warning('Gagal menghapus file materi lama setelah pembaruan', ['path' => $pathLama]);
        }

        return [
            'berhasil' => true,
            'pesan' => 'Materi berhasil diperbarui.',
            'field' => null,
            'kelas' => $kelas,
            'materi' => $materi,
        ];
    }

    /**
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah}
     */
    public function hapus(int $dosenId, mixed $kelasId, mixed $materiId): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $materi = $this->materiMilikKelas($kelas, $materiId);
        $path = $materi->file_path;

        try {
            DB::transaction(fn () => $materi->delete());
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus data materi', ['error' => $exception->getMessage()]);

            return $this->gagal('Materi gagal dihapus. Silakan coba lagi.', null, $kelas);
        }

        $this->file->hapus($path);

        return [
            'berhasil' => true,
            'pesan' => 'Materi berhasil dihapus.',
            'field' => null,
            'kelas' => $kelas,
        ];
    }

    public function unduh(int $dosenId, mixed $kelasId, mixed $materiId): Response
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $materi = $this->materiMilikKelas($kelas, $materiId);

        if (! $this->file->pathMilikKelas($materi->file_path, $kelas->getKey())) {
            abort(404);
        }

        return $this->file->unduh($materi->file_path, $materi->file_name, $materi->file_mime);
    }

    /**
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah, materi: ?MateriPembelajaran}
     */
    private function gagal(
        string $pesan,
        ?string $field,
        KelasMataKuliah $kelas,
        ?MateriPembelajaran $materi = null,
    ): array {
        return [
            'berhasil' => false,
            'pesan' => $pesan,
            'field' => $field,
            'kelas' => $kelas,
            'materi' => $materi,
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
