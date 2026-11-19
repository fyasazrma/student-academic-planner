<?php

namespace App\Http\Requests\Dosen;

use App\Services\Akademik\PenilaianService;

class RiwayatNilaiRequest extends NilaiKelasRequest
{
    public function authorize(PenilaianService $penilaian): bool
    {
        if (! parent::authorize($penilaian)) {
            return false;
        }

        $kelas = $penilaian->kelasMilikDosen(
            (int) $this->user()->getAuthIdentifier(),
            $this->route('kelas'),
        );
        $penilaian->pesertaMilikKelas($kelas, $this->route('peserta'));

        return true;
    }
}
