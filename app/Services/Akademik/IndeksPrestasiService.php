<?php

namespace App\Services\Akademik;

use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Perhitungan IP semester dan IPK secara dinamis dari sumber nilai.
 *
 * IP memakai nilai pengambilan pada semester yang bersangkutan. IPK memilih
 * satu nilai terbaik per mata_kuliah_id dari seluruh riwayat pengambilan,
 * sehingga SKS tidak dihitung ganda. IP/IPK tidak disimpan permanen.
 */
class IndeksPrestasiService
{
    public function __construct(private readonly PenilaianService $penilaian)
    {
    }

    /**
     * @return Collection<int, TahunAkademik>
     */
    public function daftarPeriode(int $mahasiswaId): Collection
    {
        return TahunAkademik::query()
            ->whereHas('kelasMataKuliah.peserta', fn (Builder $query): Builder => $query
                ->where('mahasiswa_id', $mahasiswaId))
            ->get()
            ->sortBy(fn (TahunAkademik $item): array => [
                $item->tahun_mulai,
                $this->urutanSemester($item->semester?->value),
                $item->getKey(),
            ])
            ->values();
    }

    /**
     * @return array{
     *     periode: Collection<int, TahunAkademik>,
     *     dipilih: ?TahunAkademik,
     *     ip: ?string,
     *     ipk: ?string,
     *     total_sks: int,
     *     jumlah_lengkap: int,
     *     baris: Collection<int, array<string, mixed>>
     * }
     */
    public function ringkasan(int $mahasiswaId, mixed $periodeId): array
    {
        $periode = $this->daftarPeriode($mahasiswaId);
        $dipilih = $periode->first(
            fn (TahunAkademik $item): bool => (string) $item->getKey() === (string) $periodeId,
        ) ?? $periode->first();

        $semester = $this->semester($mahasiswaId, $dipilih?->getKey());
        $ipk = $this->ipk($mahasiswaId);

        return [
            'periode' => $periode,
            'dipilih' => $dipilih,
            'ip' => $semester['ip'],
            'ipk' => $ipk['ipk'],
            'total_sks' => $semester['total_sks'],
            'jumlah_lengkap' => $semester['jumlah_lengkap'],
            'baris' => $semester['baris'],
        ];
    }

    /**
     * @return array{ip: ?string, total_sks: int, jumlah_lengkap: int, baris: Collection<int, array<string, mixed>>}
     */
    public function semester(int $mahasiswaId, ?int $tahunAkademikId): array
    {
        $query = $this->queryPeserta($mahasiswaId);

        if ($tahunAkademikId !== null) {
            $query->whereHas('kelasMataKuliah', fn (Builder $item): Builder => $item
                ->where('tahun_akademik_id', $tahunAkademikId));
        }

        $baris = $query->get()
            ->map(fn (PesertaMataKuliah $item): array => $this->baris($item))
            ->sortBy(fn (array $item): array => [
                $item['kode'],
                $item['tahun_mulai'],
                $item['urutan_semester'],
                $item['peserta_id'],
            ])
            ->values();

        return $this->rataBobot($baris);
    }

    /**
     * @return array{ipk: ?string, total_sks: int, jumlah_lengkap: int, baris: Collection<int, array<string, mixed>>}
     */
    public function ipk(int $mahasiswaId): array
    {
        $lengkap = $this->queryPeserta($mahasiswaId)
            ->get()
            ->map(fn (PesertaMataKuliah $item): array => $this->baris($item))
            ->filter(fn (array $item): bool => $item['status'] === 'lengkap');

        $terbaik = $lengkap
            ->groupBy('mata_kuliah_id')
            ->map(fn (Collection $items): array => $this->pilihTerbaik($items))
            ->values();

        $hasil = $this->rataBobot($terbaik);

        return [
            'ipk' => $hasil['ip'],
            'total_sks' => $hasil['total_sks'],
            'jumlah_lengkap' => $hasil['jumlah_lengkap'],
            'baris' => $terbaik,
        ];
    }

    /**
     * Riwayat pengambilan satu mata kuliah milik mahasiswa yang login.
     *
     * @return array{
     *     mata_kuliah: ?\App\Models\MataKuliah,
     *     baris: Collection<int, array<string, mixed>>,
     *     terbaik_peserta_id: ?int
     * }
     */
    public function riwayatMataKuliah(int $mahasiswaId, int $mataKuliahId): array
    {
        $models = $this->queryPeserta($mahasiswaId)
            ->whereHas('kelasMataKuliah', fn (Builder $item): Builder => $item
                ->where('mata_kuliah_id', $mataKuliahId))
            ->get();

        if ($models->isEmpty()) {
            abort(404);
        }

        $baris = $models
            ->map(fn (PesertaMataKuliah $item): array => $this->baris($item))
            ->sortBy(fn (array $item): array => [
                $item['tahun_mulai'],
                $item['urutan_semester'],
                $item['peserta_id'],
            ])
            ->values();

        $terbaik = $baris->filter(fn (array $item): bool => $item['status'] === 'lengkap');
        $pilihan = $terbaik->isNotEmpty() ? $this->pilihTerbaik($terbaik) : null;

        return [
            'mata_kuliah' => $models->first()?->kelasMataKuliah?->mataKuliah,
            'baris' => $baris,
            'terbaik_peserta_id' => $pilihan['peserta_id'] ?? null,
        ];
    }

    private function queryPeserta(int $mahasiswaId): Builder
    {
        return PesertaMataKuliah::query()
            ->where('mahasiswa_id', $mahasiswaId)
            ->with([
                'kelasMataKuliah.mataKuliah',
                'kelasMataKuliah.tahunAkademik',
                'nilai',
                'pengumpulanTugas.tugas',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(PesertaMataKuliah $peserta): array
    {
        $kelas = $peserta->kelasMataKuliah;
        $tahun = $kelas?->tahunAkademik;
        $mataKuliah = $kelas?->mataKuliah;
        $detail = $this->penilaian->detailNilai($peserta);

        return [
            'peserta_id' => $peserta->getKey(),
            'mata_kuliah_id' => $mataKuliah?->getKey(),
            'kode' => $mataKuliah?->kode ?? '-',
            'nama' => $mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
            'sks' => (int) ($mataKuliah?->sks ?? 0),
            'periode' => $tahun?->label() ?? '-',
            'tahun_mulai' => (int) ($tahun?->tahun_mulai ?? 0),
            'semester' => $tahun?->semester?->value ?? '',
            'urutan_semester' => $this->urutanSemester($tahun?->semester?->value),
            'uts_label' => $detail['uts_label'],
            'uas_label' => $detail['uas_label'],
            'tugas_label' => $detail['tugas_label'],
            'akhir' => $detail['akhir'],
            'akhir_label' => $detail['akhir_label'],
            'huruf' => $detail['huruf'],
            'bobot_mutu' => $detail['bobot_mutu'],
            'bobot_mutu_label' => $detail['bobot_mutu_label'],
            'status' => $detail['status'],
            'status_label' => $detail['status_label'],
            'alasan_label' => $detail['alasan_label'],
            'detail_url' => route('mahasiswa.nilai.show', $mataKuliah?->getKey() ?? 0),
        ];
    }

    /**
     * Nilai tertinggi; ties dipecah oleh periode paling awal, lalu peserta_id
     * terkecil. Urutan ini deterministik dan tidak menghapus riwayat lama.
     *
     * @param Collection<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function pilihTerbaik(Collection $items): array
    {
        return $items->sort(function (array $a, array $b): int {
            $nilai = (float) $b['akhir'] <=> (float) $a['akhir'];

            if ($nilai !== 0) {
                return $nilai;
            }

            $periode = [$a['tahun_mulai'], $a['urutan_semester']] <=> [$b['tahun_mulai'], $b['urutan_semester']];

            if ($periode !== 0) {
                return $periode;
            }

            return $a['peserta_id'] <=> $b['peserta_id'];
        })->first();
    }

    /**
     * @param Collection<int, array<string, mixed>> $baris
     * @return array{ip: ?string, total_sks: int, jumlah_lengkap: int, baris: Collection<int, array<string, mixed>>}
     */
    private function rataBobot(Collection $baris): array
    {
        $lengkap = $baris->filter(fn (array $item): bool => $item['status'] === 'lengkap');
        $totalSks = $lengkap->sum('sks');
        $bobot = $lengkap->sum(fn (array $item): float => $item['sks'] * (float) $item['bobot_mutu']);

        return [
            'ip' => $totalSks > 0 ? number_format($bobot / $totalSks, 2, '.', '') : null,
            'total_sks' => (int) $totalSks,
            'jumlah_lengkap' => $lengkap->count(),
            'baris' => $baris,
        ];
    }

    private function urutanSemester(?string $semester): int
    {
        return match ($semester) {
            'ganjil' => 1,
            'genap' => 2,
            default => 3,
        };
    }
}
