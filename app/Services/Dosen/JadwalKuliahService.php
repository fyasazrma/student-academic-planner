<?php

namespace App\Services\Dosen;

use App\Enums\JenisJadwal;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * Pengelolaan jadwal perkuliahan milik dosen.
 *
 * Semua aksi diawali scoped lookup kelas berdasarkan dosen_id, lalu jadwal
 * diverifikasi masih berada di kelas tersebut. ID kelas tidak pernah diambil
 * dari input formulir.
 */
class JadwalKuliahService
{
    private const URUTAN_HARI = [1, 2, 3, 4, 5, 6, 0];

    public function __construct(private readonly MataKuliahService $mataKuliah)
    {
    }

    /**
     * Resolver kelas milik dosen. Kelas dosen lain menghasilkan 404.
     */
    public function kelasMilikDosen(int $dosenId, mixed $kelasId): KelasMataKuliah
    {
        return $this->mataKuliah->milikDosen($dosenId, $kelasId);
    }

    /**
     * Pastikan jadwal benar-benar berada pada kelas yang diberikan.
     * Jadwal dari kelas lain menghasilkan 404.
     */
    public function jadwalMilikKelas(KelasMataKuliah $kelas, mixed $jadwalId): JadwalKuliah
    {
        return JadwalKuliah::query()
            ->whereKey(is_numeric($jadwalId) ? (int) $jadwalId : 0)
            ->where('kelas_mata_kuliah_id', $kelas->getKey())
            ->firstOrFail();
    }

    /**
     * @return array<int, string>
     */
    public function opsiHari(): array
    {
        $opsi = [];

        foreach (self::URUTAN_HARI as $hari) {
            $opsi[$hari] = DashboardDataService::labelHari($hari);
        }

        return $opsi;
    }

    /**
     * @return array<string, string>
     */
    public function opsiJenis(): array
    {
        return [
            JenisJadwal::Kuliah->value => 'Kuliah',
            JenisJadwal::Praktikum->value => 'Praktikum',
            JenisJadwal::Lainnya->value => 'Lainnya',
        ];
    }

    /**
     * @return array{kelas: KelasMataKuliah, jadwal: Collection<int, array<string, mixed>>}
     */
    public function daftar(int $dosenId, mixed $kelasId): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $hariOptions = $this->opsiHari();
        $jenisOptions = $this->opsiJenis();

        $jadwal = $kelas->jadwal
            ->sortBy(function (JadwalKuliah $item): array {
                $urutan = array_search((int) $item->hari, self::URUTAN_HARI, true);

                return [$urutan === false ? 99 : $urutan, (string) $item->jam_mulai];
            })
            ->values()
            ->map(fn (JadwalKuliah $item): array => [
                'id' => $item->getKey(),
                'hari' => (int) $item->hari,
                'hari_label' => $hariOptions[(int) $item->hari] ?? 'Tidak diketahui',
                'jam_mulai' => substr((string) $item->jam_mulai, 0, 5),
                'jam_selesai' => substr((string) $item->jam_selesai, 0, 5),
                'ruang' => $item->ruang,
                'jenis' => $item->jenis?->value ?? '',
                'jenis_label' => $jenisOptions[$item->jenis?->value] ?? 'Lainnya',
            ]);

        return ['kelas' => $kelas, 'jadwal' => $jadwal];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah, jadwal: ?JadwalKuliah}
     */
    public function simpan(int $dosenId, mixed $kelasId, array $data): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);

        return $this->tulis($kelas, $data, null);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah, jadwal: ?JadwalKuliah}
     */
    public function perbarui(int $dosenId, mixed $kelasId, mixed $jadwalId, array $data): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $jadwal = $this->jadwalMilikKelas($kelas, $jadwalId);

        return $this->tulis($kelas, $data, $jadwal);
    }

    /**
     * @return array{berhasil: bool, pesan: string, kelas: KelasMataKuliah}
     */
    public function hapus(int $dosenId, mixed $kelasId, mixed $jadwalId): array
    {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $jadwal = $this->jadwalMilikKelas($kelas, $jadwalId);
        $jadwal->delete();

        return [
            'berhasil' => true,
            'pesan' => 'Jadwal berhasil dihapus.',
            'kelas' => $kelas,
        ];
    }

    private function waktu(mixed $nilai): string
    {
        $nilai = is_string($nilai) ? trim($nilai) : '';

        return preg_match('/^\d{2}:\d{2}$/', $nilai) === 1 ? $nilai.':00' : $nilai;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{berhasil: bool, pesan: string, field: ?string, kelas: KelasMataKuliah, jadwal: ?JadwalKuliah}
     */
    private function tulis(KelasMataKuliah $kelas, array $data, ?JadwalKuliah $jadwal): array
    {
        $atribut = [
            'hari' => (int) $data['hari'],
            'jam_mulai' => $this->waktu($data['jam_mulai']),
            'jam_selesai' => $this->waktu($data['jam_selesai']),
            'ruang' => $data['ruang'] ?? null,
            'jenis' => $data['jenis'],
        ];

        try {
            if ($jadwal === null) {
                $jadwal = $kelas->jadwal()->create($atribut);
                $pesan = 'Jadwal berhasil ditambahkan.';
            } else {
                $jadwal->fill($atribut)->save();
                $pesan = 'Jadwal berhasil diperbarui.';
            }
        } catch (QueryException $exception) {
            if ($this->adalahDuplikatJadwal($exception)) {
                return [
                    'berhasil' => false,
                    'pesan' => 'Kombinasi hari dan jam mulai sudah digunakan pada kelas ini.',
                    'field' => 'jam_mulai',
                    'kelas' => $kelas,
                    'jadwal' => $jadwal,
                ];
            }

            throw $exception;
        }

        return [
            'berhasil' => true,
            'pesan' => $pesan,
            'field' => null,
            'kelas' => $kelas,
            'jadwal' => $jadwal,
        ];
    }

    /**
     * Hanya pelanggaran unique pada pasangan hari dan jam mulai kelas yang
     * diterjemahkan sebagai duplikasi jadwal. Error integritas lain tetap
     * dilempar, dan detail SQL tidak pernah dikembalikan ke pengguna.
     */
    private function adalahDuplikatJadwal(QueryException $exception): bool
    {
        $kode = (string) $exception->getCode();
        $sqlstate = (string) ($exception->errorInfo[0] ?? '');

        if ($kode !== '23000' && $sqlstate !== '23000') {
            return false;
        }

        $pesan = strtolower($exception->getMessage());

        return str_contains($pesan, 'jadwal_kuliah_unik')
            || (
                str_contains($pesan, 'unique constraint failed: jadwal_kuliah.kelas_mata_kuliah_id')
                && str_contains($pesan, 'jadwal_kuliah.hari')
                && str_contains($pesan, 'jadwal_kuliah.jam_mulai')
            );
    }
}
