<?php

namespace App\Http\Requests\Mahasiswa;

use App\Models\PesertaMataKuliah;
use App\Models\SesiAbsensi;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Mahasiswa hanya dapat mengisi absensi pada sesi milik kelas yang benar-benar
 * diikutinya. Status absensi yang dikirim browser tidak pernah dipercaya.
 */
class AbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()?->isMahasiswa() !== true) {
            return false;
        }

        $sesiId = $this->route('sesi');

        if ($sesiId === null) {
            return true;
        }

        $sesi = SesiAbsensi::query()
            ->whereKey(is_numeric($sesiId) ? (int) $sesiId : 0)
            ->firstOrFail();

        return PesertaMataKuliah::query()
            ->where('mahasiswa_id', $this->user()->getAuthIdentifier())
            ->where('kelas_mata_kuliah_id', $sesi->kelas_mata_kuliah_id)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        return [
            'status' => ['sometimes', 'in:hadir'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Mahasiswa hanya dapat mengirim status hadir.',
        ];
    }
}
