<?php

namespace App\Http\Requests\Dosen;

use App\Services\Dosen\JadwalKuliahService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest jadwal dosen memeriksa role dan kepemilikan kelas serta jadwal.
 * Scoped lookup yang gagal akan menghasilkan 404, bukan 403 atau 422.
 */
abstract class JadwalKuliahRequest extends FormRequest
{
    public function authorize(JadwalKuliahService $jadwal): bool
    {
        if ($this->user()?->isDosen() !== true) {
            return false;
        }

        $kelas = $jadwal->kelasMilikDosen(
            (int) $this->user()->getAuthIdentifier(),
            $this->route('kelas'),
        );

        if ($this->route('jadwal') !== null) {
            $jadwal->jadwalMilikKelas($kelas, $this->route('jadwal'));
        }

        return true;
    }
}
