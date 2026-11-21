<?php

namespace Tests\Concerns;

use App\Enums\JenisAgenda;
use App\Enums\JenisUjian;
use App\Enums\StatusPeserta;
use App\Models\AgendaAkademik;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use Carbon\Carbon;

trait BatchDData
{
    private int $batchDUrutan = 0;

    private function tahunBatchD(): TahunAkademik
    {
        $tahun = TahunAkademik::query()->first();
        if ($tahun !== null) {
            return $tahun;
        }

        $tahun = new TahunAkademik();
        $tahun->forceFill([
            'tahun_mulai' => 2026,
            'tahun_selesai' => 2027,
            'semester' => 'ganjil',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-12-31',
            'aktif' => true,
        ])->save();

        return $tahun;
    }

    private function mahasiswaBatchD(array $attributes = []): User
    {
        $this->batchDUrutan++;
        $nim = 'BD'.str_pad((string) $this->batchDUrutan, 6, '0', STR_PAD_LEFT);

        return User::factory()->mahasiswa()->create(array_merge(['nim' => $nim], $attributes));
    }

    private function dosenBatchD(): User
    {
        return User::factory()->dosen()->create();
    }

    private function kelasBatchD(User $dosen, ?TahunAkademik $tahun = null): KelasMataKuliah
    {
        $this->batchDUrutan++;
        $kode = 'MK-BD'.str_pad((string) $this->batchDUrutan, 3, '0', STR_PAD_LEFT);

        $mataKuliah = new MataKuliah();
        $mataKuliah->forceFill([
            'kode' => $kode,
            'nama' => 'Mata Kuliah Batch D '.$this->batchDUrutan,
            'sks' => 3,
            'aktif' => true,
        ])->save();

        $kelas = new KelasMataKuliah();
        $kelas->forceFill([
            'mata_kuliah_id' => $mataKuliah->id,
            'tahun_akademik_id' => ($tahun ?? $this->tahunBatchD())->id,
            'dosen_id' => $dosen->id,
            'kode_kelas' => $kode.'-01',
            'nama_kelas' => 'Kelas '.$kode.'-01',
            'kapasitas' => 30,
        ])->save();

        return $kelas;
    }

    private function pesertaBatchD(
        User $mahasiswa,
        KelasMataKuliah $kelas,
        StatusPeserta $status = StatusPeserta::Aktif,
    ): PesertaMataKuliah {
        $peserta = new PesertaMataKuliah();
        $peserta->forceFill([
            'kelas_mata_kuliah_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status->value,
            'tanggal_daftar' => now()->subWeek(),
        ])->save();

        return $peserta;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadPengumumanBatchD(array $overrides = []): array
    {
        return array_merge([
            'judul' => 'Pengumuman Batch D',
            'isi' => 'Isi pengumuman Batch D untuk pengujian.',
            'kelas_mata_kuliah_id' => null,
            'status' => 'terbit',
            'tanggal_publikasi' => null,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => null,
        ], $overrides);
    }

    private function pengumumanBatchD(
        User $pembuat,
        ?KelasMataKuliah $kelas,
        ?Carbon $publikasi = null,
        string $judul = 'Pengumuman tersimpan',
    ): AgendaAkademik {
        $agenda = new AgendaAkademik();
        $agenda->forceFill([
            'dibuat_oleh' => $pembuat->id,
            'kelas_mata_kuliah_id' => $kelas?->id,
            'tahun_akademik_id' => null,
            'judul' => $judul,
            'deskripsi' => 'Deskripsi pengumuman tersimpan.',
            'jenis' => JenisAgenda::Pengumuman->value,
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'dipublikasikan_pada' => $publikasi?->toDateTimeString(),
        ])->save();

        return $agenda;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadUjianBatchD(TahunAkademik $tahun, array $overrides = []): array
    {
        return array_merge([
            'jenis_ujian' => JenisUjian::Uts->value,
            'tahun_akademik_id' => $tahun->id,
            'semester' => $tahun->semester->value,
            'judul' => 'Jadwal Ujian Tengah Semester (UTS) — Ganjil 2026/2027',
            'tanggal_mulai' => '2026-12-01',
            'tanggal_selesai' => '2026-12-01',
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '11:00',
            'lokasi' => 'Aula Batch D',
            'deskripsi' => 'Ujian institusional.',
            'dipublikasikan' => 1,
        ], $overrides);
    }
}
