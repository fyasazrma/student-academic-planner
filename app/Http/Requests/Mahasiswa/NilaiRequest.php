<?php

namespace App\Http\Requests\Mahasiswa;

use App\Models\PesertaMataKuliah;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Mahasiswa hanya dapat melihat nilai dan riwayat pengambilan miliknya.
 * ID mata kuliah pada URL divalidasi terhadap kelas yang benar-benar diikuti.
 */
class NilaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()?->isMahasiswa() !== true) {
            return false;
        }

        $mataKuliahId = $this->route('mata_kuliah');

        if ($mataKuliahId === null) {
            return true;
        }

        return PesertaMataKuliah::query()
            ->where('mahasiswa_id', $this->user()->getAuthIdentifier())
            ->whereHas('kelasMataKuliah', fn ($query): mixed => $query
                ->where('mata_kuliah_id', is_numeric($mataKuliahId) ? (int) $mataKuliahId : 0))
            ->exists();
    }
}
