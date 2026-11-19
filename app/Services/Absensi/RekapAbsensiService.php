<?php

namespace App\Services\Absensi;

use App\Enums\StatusAbsensi;
use App\Enums\StatusPeserta;
use App\Enums\StatusSesiAbsensi;
use App\Models\Absensi;
use App\Models\KelasMataKuliah;
use App\Models\PesertaMataKuliah;
use App\Models\SesiAbsensi;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rekap kehadiran. Hanya sesi yang sudah ditutup yang dihitung. Peserta aktif
 * dan mengulang masuk rekap; peserta nonaktif tidak dihitung.
 */
class RekapAbsensiService
{
    /**
     * @return array<string, mixed>
     */
    public function rekapPeserta(PesertaMataKuliah $peserta): array
    {
        $kelasId = (int) $peserta->kelas_mata_kuliah_id;
        $totalSesi = $this->totalSesiDitutup($kelasId);

        $jumlah = Absensi::query()
            ->join('sesi_absensi', 'sesi_absensi.id', '=', 'absensi.sesi_absensi_id')
            ->where('absensi.peserta_mata_kuliah_id', $peserta->getKey())
            ->where('sesi_absensi.kelas_mata_kuliah_id', $kelasId)
            ->where('sesi_absensi.status', StatusSesiAbsensi::Ditutup->value)
            ->selectRaw('absensi.status, COUNT(*) as jumlah')
            ->groupBy('absensi.status')
            ->pluck('jumlah', 'status');

        $hadir = (int) ($jumlah[StatusAbsensi::Hadir->value] ?? 0);
        $izin = (int) ($jumlah[StatusAbsensi::Izin->value] ?? 0);
        $sakit = (int) ($jumlah[StatusAbsensi::Sakit->value] ?? 0);
        $alfa = (int) ($jumlah[StatusAbsensi::Alfa->value] ?? 0);

        return [
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'alfa' => $alfa,
            'total_sesi_ditutup' => $totalSesi,
            'persentase' => $totalSesi === 0 ? 0.0 : round(($hadir / $totalSesi) * 100, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rekapKelas(KelasMataKuliah $kelas): array
    {
        $kelas->loadMissing(['mataKuliah', 'tahunAkademik']);

        $peserta = $kelas->peserta()
            ->with('mahasiswa')
            ->whereIn('status', [
                StatusPeserta::Aktif->value,
                StatusPeserta::Mengulang->value,
            ])
            ->get()
            ->sortBy(fn (PesertaMataKuliah $item): string => Str::lower((string) $item->mahasiswa?->name))
            ->values();

        $baris = $peserta->map(function (PesertaMataKuliah $item) use ($kelas): array {
            $rekap = $this->rekapPeserta($item);

            return [
                ...$rekap,
                'peserta_id' => $item->getKey(),
                'nama' => $item->mahasiswa?->name ?? 'Mahasiswa tidak tersedia',
                'nim' => $item->mahasiswa?->nim ?? '-',
                'status_peserta' => $item->status?->value ?? '-',
                'status_peserta_label' => $item->status === StatusPeserta::Mengulang ? 'Mengulang' : 'Aktif',
                'persentase_label' => number_format($rekap['persentase'], 2, '.', '').'%',
            ];
        })->values();

        return [
            'kelas' => $kelas,
            'baris' => $baris,
            'jumlah_peserta' => $baris->count(),
            'total_sesi_ditutup' => $this->totalSesiDitutup((int) $kelas->getKey()),
            'hadir' => (int) $baris->sum('hadir'),
            'izin' => (int) $baris->sum('izin'),
            'sakit' => (int) $baris->sum('sakit'),
            'alfa' => (int) $baris->sum('alfa'),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function rekapMahasiswa(int $mahasiswaId): Collection
    {
        $peserta = PesertaMataKuliah::query()
            ->where('mahasiswa_id', $mahasiswaId)
            ->whereIn('status', [
                StatusPeserta::Aktif->value,
                StatusPeserta::Mengulang->value,
            ])
            ->with(['kelasMataKuliah.mataKuliah', 'kelasMataKuliah.tahunAkademik'])
            ->get();

        return $peserta
            ->map(function (PesertaMataKuliah $item): array {
                $kelas = $item->kelasMataKuliah;
                $rekap = $this->rekapPeserta($item);

                return [
                    ...$rekap,
                    'mata_kuliah' => $kelas?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                    'kode_mata_kuliah' => $kelas?->mataKuliah?->kode ?? '-',
                    'kode_kelas' => $kelas?->kode_kelas ?? '-',
                    'periode' => $kelas?->tahunAkademik?->label() ?? '-',
                    'persentase_label' => number_format($rekap['persentase'], 2, '.', '').'%',
                ];
            })
            ->sortByDesc('mata_kuliah')
            ->values();
    }

    private function totalSesiDitutup(int $kelasId): int
    {
        return (int) SesiAbsensi::query()
            ->where('kelas_mata_kuliah_id', $kelasId)
            ->where('status', StatusSesiAbsensi::Ditutup->value)
            ->count();
    }
}
