<?php

namespace App\Http\Requests\Dosen;

use App\Models\PesertaMataKuliah;
use App\Services\Absensi\SesiAbsensiService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Otorisasi seluruh halaman absensi dosen. Kelas, sesi, dan peserta dicari
 * ulang oleh server berdasarkan dosen yang login; data milik dosen lain 404.
 */
class AbsensiKelasRequest extends FormRequest
{
    public function authorize(SesiAbsensiService $absensi): bool
    {
        if ($this->user()?->isDosen() !== true) {
            return false;
        }

        $kelasId = $this->route('kelas');

        if ($kelasId === null) {
            return true;
        }

        $kelas = $absensi->kelasMilikDosen(
            (int) $this->user()->getAuthIdentifier(),
            $kelasId,
        );

        if ($this->route('sesi') !== null) {
            $absensi->sesiMilikKelas($kelas, $this->route('sesi'));
        }

        if ($this->route('peserta') !== null) {
            PesertaMataKuliah::query()
                ->whereKey(is_numeric($this->route('peserta')) ? (int) $this->route('peserta') : 0)
                ->where('kelas_mata_kuliah_id', $kelas->getKey())
                ->firstOrFail();
        }

        return true;
    }
}
