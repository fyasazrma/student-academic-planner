<?php

namespace Tests\Concerns;

use App\Enums\KomponenNilai;
use App\Enums\Semester;
use App\Enums\StatusPeserta;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\PengumpulanTugas;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\Tugas;
use App\Models\User;

/**
 * Data uji dibuat lewat model secara tepercaya, bukan lewat request publik.
 * Helper ini hanya dipakai oleh test penilaian akademik.
 */
trait MembuatDataNilai
{
    private int $urutanNilai = 0;

    private function dosenNilai(?string $nama = null): User
    {
        return User::factory()->dosen()->create([
            'name' => $nama ?? 'Dosen Nilai '.++$this->urutanNilai,
        ]);
    }

    private function mahasiswaNilai(?string $nama = null, ?string $nim = null): User
    {
        return User::factory()->mahasiswa()->create([
            'name' => $nama ?? 'Mahasiswa Nilai '.++$this->urutanNilai,
            'nim' => $nim ?? 'NIM'.str_pad((string) $this->urutanNilai, 5, '0', STR_PAD_LEFT),
        ]);
    }

    private function tahunNilai(int $tahunMulai, string $semester = 'ganjil'): TahunAkademik
    {
        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => $tahunMulai,
            'tahun_selesai' => $tahunMulai + 1,
            'semester' => Semester::from($semester),
            'tanggal_mulai' => $tahunMulai.'-'.($semester === 'ganjil' ? '08-01' : '02-01'),
            'tanggal_selesai' => ($tahunMulai + 1).'-'.($semester === 'ganjil' ? '01-31' : '07-31'),
            'aktif' => $semester === 'ganjil',
        ])->save();

        return $tahun;
    }

    private function mataKuliahNilai(?string $kode = null, int $sks = 3): MataKuliah
    {
        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => $kode ?? 'MK-N'.str_pad((string) ++$this->urutanNilai, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Nilai '.$this->urutanNilai,
            'sks' => $sks,
            'aktif' => true,
        ])->save();

        return $mataKuliah;
    }

    private function kelasNilai(
        User $dosen,
        MataKuliah $mataKuliah,
        TahunAkademik $tahun,
        ?string $kodeKelas = null,
        ?bool $gunakanTugas = false,
    ): KelasMataKuliah {
        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->getKey(),
            'tahun_akademik_id' => $tahun->getKey(),
            'dosen_id' => $dosen->getKey(),
            'kode_kelas' => $kodeKelas ?? 'K'.str_pad((string) ++$this->urutanNilai, 3, '0', STR_PAD_LEFT),
            'nama_kelas' => 'Kelas Nilai '.$this->urutanNilai,
            'kapasitas' => 30,
            'status_pendaftaran' => 'tutup',
            'gunakan_tugas' => $gunakanTugas,
        ])->save();

        return $kelas;
    }

    private function pesertaNilai(
        KelasMataKuliah $kelas,
        User $mahasiswa,
        StatusPeserta $status = StatusPeserta::Aktif,
    ): PesertaMataKuliah {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->getKey(),
            'mahasiswa_id' => $mahasiswa->getKey(),
            'status' => $status,
            'tanggal_daftar' => now()->toDateString(),
        ])->save();

        return $peserta;
    }

    private function nilaiKomponen(
        PesertaMataKuliah $peserta,
        KomponenNilai $komponen,
        string $nilai,
        ?User $penilai = null,
        ?string $keterangan = null,
    ): Nilai {
        $model = new Nilai();
        $model->forceFill([
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'komponen' => $komponen,
            'nilai' => $nilai,
            'dinilai_oleh' => $penilai?->getKey(),
            'dinilai_pada' => now(),
            'keterangan' => $keterangan,
        ])->save();

        return $model;
    }

    private function tugasNilai(KelasMataKuliah $kelas, User $dosen, ?string $judul = null): Tugas
    {
        $tugas = new Tugas();
        $tugas->forceFill([
            'kelas_mata_kuliah_id' => $kelas->getKey(),
            'dibuat_oleh' => $dosen->getKey(),
            'judul' => $judul ?? 'Tugas Nilai '.++$this->urutanNilai,
            'dipublikasikan_pada' => now()->subDays(7),
            'deadline' => now()->addDays(3),
            'nilai_maksimal' => 100,
        ])->save();

        return $tugas;
    }

    private function kumpulkanNilai(
        Tugas $tugas,
        PesertaMataKuliah $peserta,
        ?string $nilai,
        ?User $penilai = null,
    ): PengumpulanTugas {
        $pengumpulan = new PengumpulanTugas();
        $pengumpulan->forceFill([
            'tugas_id' => $tugas->getKey(),
            'peserta_mata_kuliah_id' => $peserta->getKey(),
            'dikumpulkan_pada' => now()->subDay(),
            'terlambat' => false,
            'nilai' => $nilai,
            'dinilai_oleh' => $penilai?->getKey(),
            'dinilai_pada' => $nilai === null ? null : now(),
        ])->save();

        return $pengumpulan;
    }
}
