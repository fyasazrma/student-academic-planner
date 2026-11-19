<?php

namespace Tests\Concerns;

use App\Enums\MetodeAbsensi;
use App\Enums\Semester;
use App\Enums\StatusAbsensi;
use App\Enums\StatusPeserta;
use App\Enums\StatusSesiAbsensi;
use App\Models\Absensi;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\PesertaMataKuliah;
use App\Models\SesiAbsensi;
use App\Models\TahunAkademik;
use App\Models\User;

/**
 * Helper data uji absensi. Semua model dibuat lewat alur internal tepercaya,
 * bukan lewat request publik.
 */
trait MembuatDataAbsensi
{
    private int $urutanAbsensi = 0;

    private function dosenAbsensi(?string $nama = null): User
    {
        return User::factory()->dosen()->create([
            'name' => $nama ?? 'Dosen Absensi '.++$this->urutanAbsensi,
        ]);
    }

    private function mahasiswaAbsensi(?string $nama = null, ?string $nim = null): User
    {
        $this->urutanAbsensi++;

        return User::factory()->mahasiswa()->create([
            'name' => $nama ?? 'Mahasiswa Absensi '.$this->urutanAbsensi,
            'nim' => $nim ?? 'AB'.str_pad((string) $this->urutanAbsensi, 5, '0', STR_PAD_LEFT),
        ]);
    }

    private function tahunAbsensi(int $tahunMulai = 2026): TahunAkademik
    {
        // Kombinasi tahun/semester unik; beberapa kelas dalam satu uji memakai
        // periode yang sama, sehingga helper harus memakai ulang baris yang ada.
        return TahunAkademik::query()->firstOrCreate(
            [
                'tahun_mulai' => $tahunMulai,
                'tahun_selesai' => $tahunMulai + 1,
                'semester' => Semester::Ganjil,
            ],
            [
                'tanggal_mulai' => $tahunMulai.'-08-01',
                'tanggal_selesai' => ($tahunMulai + 1).'-01-31',
                'aktif' => true,
            ],
        );
    }

    private function mataKuliahAbsensi(?string $kode = null): MataKuliah
    {
        $this->urutanAbsensi++;
        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => $kode ?? 'ABK'.str_pad((string) $this->urutanAbsensi, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Absensi '.$this->urutanAbsensi,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        return $mataKuliah;
    }

    private function kelasAbsensi(
        User $dosen,
        ?MataKuliah $mataKuliah = null,
        ?TahunAkademik $tahun = null,
        ?string $kodeKelas = null,
    ): KelasMataKuliah {
        $this->urutanAbsensi++;
        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => ($mataKuliah ?? $this->mataKuliahAbsensi())->getKey(),
            'tahun_akademik_id' => ($tahun ?? $this->tahunAbsensi())->getKey(),
            'dosen_id' => $dosen->getKey(),
            'kode_kelas' => $kodeKelas ?? 'AB'.str_pad((string) $this->urutanAbsensi, 3, '0', STR_PAD_LEFT),
            'nama_kelas' => 'Kelas Absensi '.$this->urutanAbsensi,
            'kapasitas' => 30,
            'status_pendaftaran' => 'tutup',
        ])->save();

        return $kelas;
    }

    private function pesertaAbsensi(
        KelasMataKuliah $kelas,
        User $mahasiswa,
        StatusPeserta $status = StatusPeserta::Aktif,
    ): PesertaMataKuliah {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->getKey(),
            'mahasiswa_id' => $mahasiswa->getKey(),
            'status' => $status,
            'tanggal_daftar' => '2026-08-01',
        ])->save();

        return $peserta;
    }

    private function sesiAbsensi(
        KelasMataKuliah $kelas,
        User $dosen,
        int $pertemuanKe = 1,
        string $tanggal = '2026-11-19',
        string $mulai = '08:00',
        string $selesai = '10:00',
        StatusSesiAbsensi $status = StatusSesiAbsensi::Dibuka,
    ): SesiAbsensi {
        $sesi = new SesiAbsensi();
        $sesi->forceFill([
            'kelas_mata_kuliah_id' => $kelas->getKey(),
            'dibuat_oleh' => $dosen->getKey(),
            'pertemuan_ke' => $pertemuanKe,
            'tanggal' => $tanggal,
            'topik' => 'Topik pertemuan '.$pertemuanKe,
            'waktu_mulai' => $mulai,
            'waktu_selesai' => $selesai,
            'status' => $status,
        ])->save();

        return $sesi;
    }

    private function catatAbsensi(
        SesiAbsensi $sesi,
        PesertaMataKuliah $peserta,
        StatusAbsensi $status,
        string $metode = MetodeAbsensi::Manual->value,
        ?User $pencatat = null,
        ?string $keterangan = null,
    ): Absensi {
        $absensi = new Absensi();
        $absensi->forceFill([
            'sesi_absensi_id' => $sesi->getKey(),
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'status' => $status,
            'keterangan' => $keterangan,
            'metode' => $metode,
            'dicatat_pada' => '2026-11-19 09:30:00',
            'dicatat_oleh' => $pencatat?->getKey(),
        ])->save();

        return $absensi;
    }
}
