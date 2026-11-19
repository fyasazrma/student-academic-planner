<?php

namespace App\Services\Admin;

use App\Enums\JenisJadwal;
use App\Enums\Semester;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\User;
use App\Services\Dosen\DashboardDataService;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Pengelolaan seluruh jadwal perkuliahan oleh admin.
 *
 * Konflik diperiksa di dalam transaction dengan lockForUpdate: kelas, dosen
 * pengampu, dan ruangan tidak boleh dipakai dua kali pada hari dan rentang
 * waktu yang sama. Jadwal yang sedang disunting tidak dibandingkan dengan
 * dirinya sendiri.
 */
class JadwalKuliahService
{
    private const URUTAN_HARI = [1, 2, 3, 4, 5, 6, 0];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function daftar(array $filters): LengthAwarePaginator
    {
        $query = JadwalKuliah::query()
            ->with([
                'kelasMataKuliah.mataKuliah',
                'kelasMataKuliah.tahunAkademik',
                'kelasMataKuliah.dosen',
            ]);

        if (($filters['q'] ?? '') !== '') {
            $cari = '%'.strtolower((string) $filters['q']).'%';
            $query->whereHas('kelasMataKuliah', function ($kelas) use ($cari): void {
                $kelas->where(function ($group) use ($cari): void {
                    $group->whereRaw('lower(kode_kelas) like ?', [$cari])
                        ->orWhereRaw('lower(nama_kelas) like ?', [$cari])
                        ->orWhereHas('mataKuliah', function ($mataKuliah) use ($cari): void {
                            $mataKuliah->whereRaw('lower(nama) like ?', [$cari])
                                ->orWhereRaw('lower(kode) like ?', [$cari]);
                        })
                        ->orWhereHas('dosen', function ($dosen) use ($cari): void {
                            $dosen->whereRaw('lower(name) like ?', [$cari])
                                ->orWhereRaw('lower(nidn) like ?', [$cari]);
                        });
                });
            });
        }

        if (($filters['tahun_akademik'] ?? '') !== '') {
            $query->whereHas(
                'kelasMataKuliah',
                fn ($kelas) => $kelas->where('tahun_akademik_id', (int) $filters['tahun_akademik']),
            );
        }

        if (($filters['semester'] ?? '') !== '') {
            $query->whereHas(
                'kelasMataKuliah.tahunAkademik',
                fn ($tahun) => $tahun->where('semester', $filters['semester']),
            );
        }

        if (($filters['hari'] ?? '') !== '') {
            $query->where('hari', (int) $filters['hari']);
        }

        if (($filters['dosen'] ?? '') !== '') {
            $query->whereHas(
                'kelasMataKuliah',
                fn ($kelas) => $kelas->where('dosen_id', (int) $filters['dosen']),
            );
        }

        $query->orderBy('hari')->orderBy('jam_mulai')->orderBy('id');

        return $query->paginate(15)->through(fn (JadwalKuliah $item): array => $this->baris($item));
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
     * @return array<string, string>
     */
    public function opsiSemester(): array
    {
        return [
            Semester::Ganjil->value => 'Ganjil',
            Semester::Genap->value => 'Genap',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function opsiTahunAkademik(): array
    {
        return \App\Models\TahunAkademik::query()
            ->orderByDesc('tahun_mulai')
            ->orderByDesc('semester')
            ->get()
            ->mapWithKeys(fn ($tahun): array => [$tahun->id => $tahun->label()])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function opsiDosen(): array
    {
        return User::query()
            ->where('role', 'dosen')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $dosen): array => [$dosen->id => $dosen->name.($dosen->nidn ? ' — '.$dosen->nidn : '')])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function opsiKelas(): array
    {
        return KelasMataKuliah::query()
            ->with(['mataKuliah', 'tahunAkademik', 'dosen'])
            ->get()
            ->sortBy(fn (KelasMataKuliah $kelas): string => (string) ($kelas->tahunAkademik?->label().' '.$kelas->mataKuliah?->nama.' '.$kelas->kode_kelas))
            ->mapWithKeys(fn (KelasMataKuliah $kelas): array => [
                $kelas->id => implode(' · ', array_filter([
                    $kelas->tahunAkademik?->label(),
                    $kelas->mataKuliah?->nama ?? 'Mata kuliah',
                    $kelas->kode_kelas,
                    $kelas->dosen?->name ?? 'Dosen',
                ])),
            ])
            ->all();
    }

    public function temukan(mixed $id): JadwalKuliah
    {
        return JadwalKuliah::query()
            ->whereKey(is_numeric($id) ? (int) $id : 0)
            ->with(['kelasMataKuliah.mataKuliah', 'kelasMataKuliah.tahunAkademik', 'kelasMataKuliah.dosen'])
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, field: ?string}
     */
    public function simpan(array $data): array
    {
        return $this->tulis($data, null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, field: ?string}
     */
    public function perbarui(mixed $id, array $data): array
    {
        return $this->tulis($data, $this->temukan($id));
    }

    /**
     * @return array{berhasil: bool, pesan: string}
     */
    public function hapus(mixed $id): array
    {
        $jadwal = $this->temukan($id);
        $jadwal->delete();

        return ['berhasil' => true, 'pesan' => 'Jadwal kuliah berhasil dihapus.'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, field: ?string}
     */
    private function tulis(array $data, ?JadwalKuliah $jadwal): array
    {
        $kelas = KelasMataKuliah::query()
            ->whereKey((int) $data['kelas_mata_kuliah_id'])
            ->firstOrFail();

        $mulai = $this->jam((string) $data['jam_mulai']);
        $selesai = $this->jam((string) $data['jam_selesai']);
        $hari = (int) $data['hari'];
        $ruang = $this->ruang($data['ruang'] ?? null);

        try {
            $hasil = DB::transaction(function () use ($data, $jadwal, $kelas, $hari, $mulai, $selesai, $ruang): array {
                $bentrok = $this->cekBentrok(
                    $hari,
                    $mulai,
                    $selesai,
                    (int) $kelas->getKey(),
                    (int) $kelas->dosen_id,
                    $ruang,
                    $jadwal?->getKey(),
                );

                if ($bentrok !== null) {
                    return $bentrok;
                }

                $atribut = [
                    'kelas_mata_kuliah_id' => $kelas->getKey(),
                    'hari' => $hari,
                    'jam_mulai' => $mulai.':00',
                    'jam_selesai' => $selesai.':00',
                    'ruang' => $ruang,
                    'jenis' => $data['jenis'],
                ];

                if ($jadwal === null) {
                    JadwalKuliah::query()->create($atribut);
                    $pesan = 'Jadwal kuliah berhasil ditambahkan.';
                } else {
                    $jadwal->fill($atribut)->save();
                    $pesan = 'Jadwal kuliah berhasil diperbarui.';
                }

                return ['berhasil' => true, 'pesan' => $pesan, 'field' => null];
            });
        } catch (QueryException $exception) {
            if ($this->adalahDuplikatJadwal($exception)) {
                return [
                    'berhasil' => false,
                    'pesan' => 'Kombinasi hari dan jam mulai sudah digunakan pada kelas ini.',
                    'field' => 'jam_mulai',
                ];
            }

            throw $exception;
        }

        return $hasil;
    }

    /**
     * @return array{berhasil: bool, pesan: string, field: string}|null
     */
    private function cekBentrok(
        int $hari,
        string $mulai,
        string $selesai,
        int $kelasId,
        int $dosenId,
        ?string $ruang,
        ?int $abaikanId,
    ): ?array {
        $calon = JadwalKuliah::query()
            ->with('kelasMataKuliah.dosen')
            ->where('hari', $hari)
            ->whereRaw('substr(jam_mulai, 1, 5) < ?', [$selesai])
            ->whereRaw('substr(jam_selesai, 1, 5) > ?', [$mulai])
            ->when($abaikanId !== null, fn ($query) => $query->where('id', '<>', $abaikanId))
            ->lockForUpdate()
            ->get();

        foreach ($calon as $item) {
            if ((int) $item->kelas_mata_kuliah_id === $kelasId) {
                return [
                    'berhasil' => false,
                    'field' => 'jam_mulai',
                    'pesan' => 'Kelas sudah memiliki jadwal yang bertabrakan pada hari dan jam ini.',
                ];
            }

            if ((int) ($item->kelasMataKuliah?->dosen_id ?? 0) === $dosenId) {
                return [
                    'berhasil' => false,
                    'field' => 'jam_mulai',
                    'pesan' => 'Dosen pengampu sudah mengajar kelas lain pada hari dan jam ini.',
                ];
            }

            if ($ruang !== null && strtolower(trim((string) $item->ruang)) === strtolower($ruang)) {
                return [
                    'berhasil' => false,
                    'field' => 'ruang',
                    'pesan' => 'Ruangan sudah digunakan jadwal lain pada hari dan jam ini.',
                ];
            }
        }

        return null;
    }

    private function jam(string $nilai): string
    {
        return preg_match('/^\d{2}:\d{2}$/', $nilai) === 1 ? $nilai : substr($nilai, 0, 5);
    }

    private function ruang(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $ruang = trim($nilai);

        return $ruang === '' ? null : $ruang;
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(JadwalKuliah $item): array
    {
        $kelas = $item->kelasMataKuliah;
        $jenis = $item->jenis?->value ?? JenisJadwal::Kuliah->value;

        return [
            'id' => $item->getKey(),
            'kelas_id' => $kelas?->getKey(),
            'kode_kelas' => $kelas?->kode_kelas ?? '-',
            'nama_kelas' => $kelas?->nama_kelas,
            'mata_kuliah' => $kelas?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
            'dosen' => $kelas?->dosen?->name ?? 'Dosen tidak tersedia',
            'tahun' => $kelas?->tahunAkademik?->label() ?? '-',
            'semester' => $kelas?->tahunAkademik?->semester
                ? ucfirst($kelas->tahunAkademik->semester->value)
                : '-',
            'hari' => (int) $item->hari,
            'hari_label' => $this->opsiHari()[(int) $item->hari] ?? 'Tidak diketahui',
            'jam_mulai' => substr((string) $item->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $item->jam_selesai, 0, 5),
            'ruang' => $item->ruang,
            'jenis' => $jenis,
            'jenis_label' => $this->opsiJenis()[$jenis] ?? 'Lainnya',
        ];
    }

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
