<?php

namespace App\Services\Mahasiswa;

use App\Enums\StatusPeserta;
use App\Enums\StatusTargetBelajar;
use App\Support\KelayakanPeserta;
use App\Support\StatusTargetResolver;
use App\Models\PesertaMataKuliah;
use App\Models\TargetBelajar;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Target belajar pribadi mahasiswa.
 *
 * Seluruh query dibatasi oleh mahasiswa yang login. Status target tidak pernah
 * diterima dari request; service menghitung status efektif dari capaian,
 * deadline, dan aksi eksplisit mahasiswa.
 */
class TargetBelajarService
{
    /**
     * @return array{
     *     targets: LengthAwarePaginator<int, TargetBelajar>,
     *     statuses: array<int, array{value: string, label: string}>,
     *     mataKuliah: Collection<int, array<string, mixed>>,
     *     ringkasan: array<string, mixed>
     * }
     */
    public function daftar(int $userId, ?string $statusFilter = null, ?int $mataKuliahId = null): array
    {
        $filterStatus = $statusFilter !== null
            && in_array($statusFilter, StatusTargetBelajar::nilaiFilter(), true);

        $query = TargetBelajar::query()
            ->where('mahasiswa_id', $userId)
            ->with('mataKuliah');

        if ($mataKuliahId !== null) {
            $query->where('mata_kuliah_id', $mataKuliahId);
        }

        $models = $query->orderByDesc('created_at')->get();

        if ($filterStatus) {
            $models = $models
                ->filter(fn (TargetBelajar $target): bool => $this->statusEfetif($target)->value === $statusFilter)
                ->values();
        }

        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $targets = new LengthAwarePaginator(
            $models->slice(($page - 1) * $perPage, $perPage)
                ->map(fn (TargetBelajar $target): array => $this->format($target))
                ->all(),
            $models->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
        $targets->withQueryString();

        return [
            'targets' => $targets,
            'statuses' => collect(StatusTargetBelajar::cases())
                ->map(fn (StatusTargetBelajar $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])->all(),
            'mataKuliah' => $this->mataKuliahOptions($userId),
            'ringkasan' => $this->ringkasan($userId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function daftarSatu(int $userId, int $targetId): array
    {
        $target = $this->targetMilik($userId, $targetId)->load('mataKuliah');

        return ['target' => $this->format($target)];
    }

    /**
     * Ringkasan target untuk dashboard.
     *
     * @return array{aktif: int, selesai: int, terlambat: int, dibatalkan: int, terdekat: array<int, array<string, mixed>>}
     */
    public function ringkasan(int $userId): array
    {
        $targets = TargetBelajar::query()
            ->where('mahasiswa_id', $userId)
            ->with('mataKuliah')
            ->get();

        $terdekat = $targets
            ->filter(function (TargetBelajar $target): bool {
                $status = $this->statusEfetif($target);

                return ! in_array($status, [StatusTargetBelajar::Selesai, StatusTargetBelajar::Dibatalkan], true)
                    && $target->deadline !== null
                    && $target->deadline->greaterThanOrEqualTo(today())
                    && $target->deadline->lessThanOrEqualTo(today()->addDays(7));
            })
            ->sortBy('deadline')
            ->take(3)
            ->map(fn (TargetBelajar $target): array => $this->format($target))
            ->values()
            ->all();

        return [
            'aktif' => $targets->filter(fn (TargetBelajar $target): bool => ! in_array(
                $this->statusEfetif($target),
                [StatusTargetBelajar::Selesai, StatusTargetBelajar::Dibatalkan],
                true
            ))->count(),
            'selesai' => $targets->filter(fn (TargetBelajar $target): bool => $this->statusEfetif($target) === StatusTargetBelajar::Selesai)->count(),
            'terlambat' => $targets->filter(fn (TargetBelajar $target): bool => $this->statusEfetif($target) === StatusTargetBelajar::Terlambat)->count(),
            'dibatalkan' => $targets->filter(fn (TargetBelajar $target): bool => $this->statusEfetif($target) === StatusTargetBelajar::Dibatalkan)->count(),
            'terdekat' => $terdekat,
        ];
    }

    /**
     * Mata kuliah yang boleh dipilih sebagai target.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function mataKuliahOptions(int $userId): Collection
    {
        return PesertaMataKuliah::query()
            ->where('mahasiswa_id', $userId)
            ->whereIn('status', KelayakanPeserta::nilaiStatusBerhak())
            ->with('kelasMataKuliah.mataKuliah')
            ->get()
            ->pluck('kelasMataKuliah.mataKuliah')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($mataKuliah): array => [
                'id' => $mataKuliah->id,
                'kode' => $mataKuliah->kode,
                'nama' => $mataKuliah->nama,
                'label' => $mataKuliah->kode.' — '.$mataKuliah->nama,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpan(int $userId, array $data): TargetBelajar
    {
        return DB::transaction(function () use ($userId, $data): TargetBelajar {
            $target = new TargetBelajar();
            $target->forceFill([
                'mahasiswa_id' => $userId,
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'mata_kuliah_id' => $data['mata_kuliah_id'] ?? null,
                'satuan' => $data['satuan'] ?? null,
                'target_nilai' => $data['target_nilai'] ?? null,
                'capaian' => $data['capaian'] ?? 0,
                'tanggal_target' => $data['tanggal_target'] ?? null,
                'tanggal_mulai' => $data['tanggal_mulai'] ?? null,
                'deadline' => $data['deadline'] ?? null,
            ]);

            $this->terapkanStatus($target);
            $target->save();

            return $target;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(int $userId, int $targetId, array $data): TargetBelajar
    {
        return DB::transaction(function () use ($userId, $targetId, $data): TargetBelajar {
            $target = $this->targetMilik($userId, $targetId);

            $target->fill([
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'mata_kuliah_id' => $data['mata_kuliah_id'] ?? null,
                'satuan' => $data['satuan'] ?? null,
                'target_nilai' => $data['target_nilai'] ?? null,
                'capaian' => $data['capaian'] ?? 0,
                'tanggal_target' => $data['tanggal_target'] ?? null,
                'tanggal_mulai' => $data['tanggal_mulai'] ?? null,
                'deadline' => $data['deadline'] ?? null,
            ]);

            $this->terapkanStatus($target);
            $target->save();

            return $target;
        });
    }

    public function hapus(int $userId, int $targetId): void
    {
        DB::transaction(function () use ($userId, $targetId): void {
            $this->targetMilik($userId, $targetId)->delete();
        });
    }

    public function perbaruiCapaian(int $userId, int $targetId, float $capaian): TargetBelajar
    {
        return DB::transaction(function () use ($userId, $targetId, $capaian): TargetBelajar {
            $target = $this->targetMilik($userId, $targetId);
            $this->validasiCapaian($target, $capaian);

            $target->capaian = $capaian;
            $this->terapkanStatus($target);
            $target->save();

            return $target;
        });
    }

    public function tandaiSelesai(int $userId, int $targetId): TargetBelajar
    {
        return DB::transaction(function () use ($userId, $targetId): TargetBelajar {
            $target = $this->targetMilik($userId, $targetId);

            if ($this->statusEfetif($target) === StatusTargetBelajar::Dibatalkan) {
                throw new RuntimeException('Target yang dibatalkan tidak dapat ditandai selesai.');
            }

            // Capaian apa adanya dipertahankan. Penandaan selesai manual tidak
            // mengubah data capaian agar riwayat tetap jujur.
            $target->status = StatusTargetBelajar::Selesai;
            $target->progres = 100;
            $target->selesai_pada = now();
            $target->save();

            return $target;
        });
    }

    public function batalkanSelesai(int $userId, int $targetId): TargetBelajar
    {
        return DB::transaction(function () use ($userId, $targetId): TargetBelajar {
            $target = $this->targetMilik($userId, $targetId);

            if ($this->statusEfetif($target) !== StatusTargetBelajar::Selesai) {
                return $target;
            }

            $target->selesai_pada = null;
            $target->status = (float) $target->capaian > 0
                ? StatusTargetBelajar::Berlangsung
                : StatusTargetBelajar::BelumDimulai;
            $target->progres = $this->persentase((float) $target->capaian, $target->target_nilai);
            $target->save();

            return $target;
        });
    }

    public function batalkan(int $userId, int $targetId): TargetBelajar
    {
        return DB::transaction(function () use ($userId, $targetId): TargetBelajar {
            $target = $this->targetMilik($userId, $targetId);
            $target->status = StatusTargetBelajar::Dibatalkan;
            $target->selesai_pada = null;
            $target->save();

            return $target;
        });
    }

    public function targetMilik(int $userId, int $targetId): TargetBelajar
    {
        return TargetBelajar::query()
            ->where('mahasiswa_id', $userId)
            ->whereKey($targetId)
            ->firstOrFail();
    }

    /**
     * Hitung status dan persentaseberdasarkan data yang tersimpan.
     */
    private function terapkanStatus(TargetBelajar $target): void
    {
        $status = $this->statusEfetif($target);
        $target->status = $status;
        $target->progres = $this->persentase((float) $target->capaian, $target->target_nilai);

        if ($status === StatusTargetBelajar::Selesai && $target->selesai_pada === null) {
            $target->selesai_pada = now();
        }

        if ($status !== StatusTargetBelajar::Selesai) {
            $target->selesai_pada = null;
        }
    }

    private function statusEfetif(TargetBelajar $target): StatusTargetBelajar
    {
        return StatusTargetResolver::hitung($target);
    }

    private function capaianMencapaiTarget(TargetBelajar $target): bool
    {
        return StatusTargetResolver::capaianMencapaiTarget($target);
    }

    private function persentase(float $capaian, ?float $targetNilai): int
    {
        if ($targetNilai === null || $targetNilai <= 0) {
            return 0;
        }

        return (int) round(min(100, max(0, $capaian / $targetNilai * 100)));
    }

    private function validasiCapaian(TargetBelajar $target, float $capaian): void
    {
        if ($capaian < 0) {
            throw new RuntimeException('Capaian tidak boleh negatif.');
        }

        if ($target->target_nilai !== null && $capaian > $target->target_nilai) {
            throw new RuntimeException('Capaian tidak boleh melebihi nilai target.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function format(TargetBelajar $target): array
    {
        $status = $this->statusEfetif($target);

        return [
            'id' => $target->id,
            'judul' => $target->judul,
            'deskripsi' => $target->deskripsi,
            'mata_kuliah_id' => $target->mata_kuliah_id,
            'mata_kuliah' => $target->mataKuliah
                ? $target->mataKuliah->kode.' — '.$target->mataKuliah->nama
                : 'Umum',
            'satuan' => $target->satuan,
            'target_nilai' => $target->target_nilai,
            'capaian' => $target->capaian,
            'target_label' => $target->target_nilai !== null
                ? number_format($target->target_nilai, 2, ',', '.').' '.$target->satuan
                : 'Tidak ditentukan',
            'capaian_label' => number_format((float) $target->capaian, 2, ',', '.').' '.$target->satuan,
            'tanggal_mulai_label' => $target->tanggal_mulai?->translatedFormat('d M Y') ?? '-',
            'tanggal_target_label' => $target->tanggal_target?->translatedFormat('d M Y') ?? '-',
            'deadline_label' => $target->deadline?->translatedFormat('d M Y') ?? '-',
            'status' => $status->value,
            'status_label' => $status->label(),
            'status_tone' => $status->tone(),
            'persentase' => $this->persentase((float) $target->capaian, $target->target_nilai),
            'bisa_tandai_selesai' => ! in_array($status, [StatusTargetBelajar::Selesai, StatusTargetBelajar::Dibatalkan], true),
            'bisa_dibatalkan_selesai' => $status === StatusTargetBelajar::Selesai && ! $this->capaianMencapaiTarget($target),
            'bisa_dibatalkan' => ! in_array($status, [StatusTargetBelajar::Dibatalkan], true),
            'dibuat_label' => $target->created_at?->translatedFormat('d M Y H:i') ?? '-',
        ];
    }
}
