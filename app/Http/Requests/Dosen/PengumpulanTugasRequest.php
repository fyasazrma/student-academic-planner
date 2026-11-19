<?php

namespace App\Http\Requests\Dosen;

use App\Services\Dosen\PengumpulanTugasService;
use App\Services\Dosen\TugasService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Otorisasi akses daftar dan detail pengumpulan dosen.
 *
 * Role, kelas, tugas, dan pengumpulan ditentukan ulang oleh server dari
 * identitas dosen yang login serta parameter URL yang sudah dibatasi.
 * Scoped lookup yang gagal menghasilkan 404.
 */
class PengumpulanTugasRequest extends FormRequest
{
    public function authorize(TugasService $tugas, PengumpulanTugasService $pengumpulan): bool
    {
        if ($this->user()?->isDosen() !== true) {
            return false;
        }

        $kelas = $tugas->kelasMilikDosen(
            (int) $this->user()->getAuthIdentifier(),
            $this->route('kelas'),
        );
        $tugasMilik = $tugas->tugasMilikKelas($kelas, $this->route('tugas'));

        if ($this->route('pengumpulan') !== null) {
            $pengumpulan->pengumpulanMilikTugasKelas(
                $kelas,
                $tugasMilik,
                $this->route('pengumpulan'),
            );
        }

        return true;
    }
}
