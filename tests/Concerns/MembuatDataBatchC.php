<?php

namespace Tests\Concerns;

use App\Enums\StatusPeserta;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\MateriPembelajaran;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Helper pembuat data akademik untuk test Target Belajar dan Progres Materi.
 */
trait MembuatDataBatchC
{
    protected int $urutanBatchC = 0;

    protected function buatMahasiswa(): User
    {
        return User::factory()->mahasiswa()->create();
    }

    protected function buatDosen(): User
    {
        return User::factory()->dosen()->create();
    }

    protected function buatTahun(): TahunAkademik
    {
        $this->urutanBatchC++;

        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => 2026 + $this->urutanBatchC,
            'tahun_selesai' => 2027 + $this->urutanBatchC,
            'semester' => 'ganjil',
            'tanggal_mulai' => (2026 + $this->urutanBatchC).'-09-01',
            'tanggal_selesai' => (2026 + $this->urutanBatchC).'-12-31',
            'aktif' => true,
        ])->save();

        return $tahun;
    }

    protected function buatMataKuliah(): MataKuliah
    {
        $this->urutanBatchC++;

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => 'MKC-'.str_pad((string) $this->urutanBatchC, 3, '0', STR_PAD_LEFT),
            'nama' => 'Mata Kuliah Batch C '.$this->urutanBatchC,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        return $mataKuliah;
    }

    protected function buatKelas(User $dosen, TahunAkademik $tahun, MataKuliah $mataKuliah, string $kode = 'A01'): KelasMataKuliah
    {
        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => $tahun->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kode,
            'nama_kelas' => 'Kelas '.$kode,
            'kapasitas' => 30,
        ])->save();

        return $kelas;
    }

    protected function buatPeserta(KelasMataKuliah $kelas, User $mahasiswa, StatusPeserta $status = StatusPeserta::Aktif): PesertaMataKuliah
    {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status->value,
            'tanggal_daftar' => now(),
        ])->save();

        return $peserta;
    }

    /**
     * @param  array<string, mixed>  $override
     */
    protected function buatMateri(KelasMataKuliah $kelas, User $pembuat, array $override = []): MateriPembelajaran
    {
        $this->urutanBatchC++;
        $path = $override['file_path'] ?? ('materi/'.$kelas->id.'/2026/11/file-c-'.$this->urutanBatchC.'.pdf');

        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, '%PDF-1.4 dummy');
        }

        $materi = new MateriPembelajaran();
        $materi->forceFill(array_merge([
            'kelas_mata_kuliah_id' => $kelas->id,
            'dibuat_oleh' => $pembuat->id,
            'judul' => 'Materi Batch C '.$this->urutanBatchC,
            'deskripsi' => 'Deskripsi materi perkuliahan.',
            'file_path' => $path,
            'file_name' => 'dokumen-'.$this->urutanBatchC.'.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => 1024,
            'pertemuan_ke' => 1,
            'dipublikasikan_pada' => now()->subDay(),
        ], $override))->save();

        return $materi;
    }

    /**
     * @return array<string, mixed>
     */
    protected function dataTarget(array $override = []): array
    {
        return array_merge([
            'judul' => 'Belajar 2 jam sehari',
            'deskripsi' => 'Target belajar mandiri.',
            'mata_kuliah_id' => null,
            'satuan' => 'jam',
            'target_nilai' => 10,
            'capaian' => 0,
            'tanggal_mulai' => now()->toDateString(),
            'deadline' => now()->addDays(7)->toDateString(),
            'tanggal_target' => null,
        ], $override);
    }
}
