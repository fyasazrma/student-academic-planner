<?php

namespace App\Http\Requests\Mahasiswa;

use App\Enums\StatusPeserta;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

/**
 * Validasi bersama target belajar.
 *
 * Status, persentase, dan mahasiswa_id tidak diterima dari browser.
 * Mata kuliah harus berasal dari kelas aktif yang benar-benar diikuti.
 */
abstract class TargetBelajarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMahasiswa() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliah,id'],
            'satuan' => ['required', 'string', 'max:30'],
            'target_nilai' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'capaian' => ['sometimes', 'numeric', 'min:0', 'max:9999999999'],
            'tanggal_target' => ['nullable', 'date'],
            'tanggal_mulai' => ['required', 'date'],
            'deadline' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul target wajib diisi.',
            'judul.max' => 'Judul target maksimal 200 karakter.',
            'satuan.required' => 'Satuan capaian wajib diisi.',
            'satuan.max' => 'Satuan capaian maksimal 30 karakter.',
            'target_nilai.required' => 'Nilai target wajib diisi.',
            'target_nilai.numeric' => 'Nilai target harus berupa angka.',
            'target_nilai.min' => 'Nilai target harus lebih besar dari nol.',
            'capaian.numeric' => 'Capaian harus berupa angka.',
            'capaian.min' => 'Capaian tidak boleh negatif.',
            'mata_kuliah_id.exists' => 'Mata kuliah tidak ditemukan.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'deadline.required' => 'Deadline wajib diisi.',
            'deadline.after_or_equal' => 'Deadline tidak boleh mendahului tanggal mulai.',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['judul', 'deskripsi', 'satuan'] as $field) {
            if (is_string($this->input($field))) {
                $nilai = trim($this->input($field));
                $this->merge([$field => $nilai === '' ? null : $nilai]);
            }
        }

        if ($this->input('mata_kuliah_id') === '') {
            $this->merge(['mata_kuliah_id' => null]);
        }
    }

    /**
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mataKuliahId = $this->input('mata_kuliah_id');

            if ($mataKuliahId !== null && ! $this->mataKuliahDiikuti((int) $mataKuliahId)) {
                $validator->errors()->add('mata_kuliah_id', 'Mata kuliah harus berasal dari kelas yang Anda ikuti.');
            }

            $targetNilai = $this->input('target_nilai');
            $capaian = $this->input('capaian');

            if (is_numeric($targetNilai) && is_numeric($capaian) && (float) $capaian > (float) $targetNilai) {
                $validator->errors()->add('capaian', 'Capaian tidak boleh melebihi nilai target.');
            }
        });
    }

    private function mataKuliahDiikuti(int $mataKuliahId): bool
    {
        return DB::table('peserta_mata_kuliah as pmk')
            ->join('kelas_mata_kuliah as kmk', 'kmk.id', '=', 'pmk.kelas_mata_kuliah_id')
            ->where('pmk.mahasiswa_id', $this->user()->getAuthIdentifier())
            ->where('pmk.status', StatusPeserta::Aktif->value)
            ->where('kmk.mata_kuliah_id', $mataKuliahId)
            ->exists();
    }
}
