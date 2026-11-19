<?php

namespace App\Http\Requests\Dosen;

use App\Enums\KomponenNilai;
use App\Services\Akademik\PenilaianService;

/**
 * Hanya UTS dan UAS yang dapat ditulis. Komponen tugas tidak memiliki writer
 * karena nilainya bersumber dari pengumpulan_tugas.nilai.
 */
class SimpanNilaiRequest extends NilaiKelasRequest
{
    public function authorize(PenilaianService $penilaian): bool
    {
        if (! parent::authorize($penilaian)) {
            return false;
        }

        $komponen = KomponenNilai::tryFrom((string) $this->route('komponen'));

        if ($komponen === null || ! in_array($komponen, PenilaianService::KOMPONEN_DITULIS, true)) {
            return false;
        }

        $kelas = $penilaian->kelasMilikDosen(
            (int) $this->user()->getAuthIdentifier(),
            $this->route('kelas'),
        );
        $penilaian->pesertaMilikKelas($kelas, $this->route('peserta'));

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nilai' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
