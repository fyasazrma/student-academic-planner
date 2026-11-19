<?php

namespace App\Http\Requests\Dosen;

use App\Services\Akademik\PenilaianService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Otorisasi seluruh halaman nilai dosen. Kelas ditemukan kembali oleh server
 * berdasarkan dosen_id yang login; kelas dosen lain menghasilkan 404.
 */
class NilaiKelasRequest extends FormRequest
{
    public function authorize(PenilaianService $penilaian): bool
    {
        if ($this->user()?->isDosen() !== true) {
            return false;
        }

        if ($this->route('kelas') !== null) {
            $penilaian->kelasMilikDosen(
                (int) $this->user()->getAuthIdentifier(),
                $this->route('kelas'),
            );
        }

        return true;
    }
}
